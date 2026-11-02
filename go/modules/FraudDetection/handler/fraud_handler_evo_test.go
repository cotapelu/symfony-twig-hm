package handler

import (
	"context"
	"database/sql"
	"encoding/json"
	"fmt"
	"net/http"
	"net/http/httptest"
	"strings"
	"testing"
	"time"

	"github.com/gin-gonic/gin"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"

	fraudmodels "community.local/modules/FraudDetection/internal/models"
	fd "community.local/modules/FraudDetection/service"
)

// ==================== In-memory repository for tests ====================

type inMemoryFraudRepository struct {
	alerts         []*fraudmodels.FraudAlert
	rules          []*fraudmodels.FraudRule
	nextID         int64
	getActiveRulesErr error
	createAlertErr    error
	resolveAlertErr   error
	// errors for other methods
	getOpenAlertsErr error
	getUserAlertsErr error
	getAlertByIDErr  error
	createRuleErr    error
}

func (r *inMemoryFraudRepository) GetActiveRules(ctx context.Context) ([]*fraudmodels.FraudRule, error) {
	if r.getActiveRulesErr != nil {
		return nil, r.getActiveRulesErr
	}
	return r.rules, nil
}

func (r *inMemoryFraudRepository) CreateAlert(ctx context.Context, alert *fraudmodels.FraudAlert) error {
	if r.createAlertErr != nil {
		return r.createAlertErr
	}
	r.nextID++
	alert.ID = r.nextID
	alert.CreatedAt = time.Now()
	r.alerts = append(r.alerts, alert)
	return nil
}

func (r *inMemoryFraudRepository) GetOpenAlerts(ctx context.Context) ([]*fraudmodels.FraudAlert, error) {
	if r.getOpenAlertsErr != nil {
		return nil, r.getOpenAlertsErr
	}
	var open []*fraudmodels.FraudAlert
	for _, a := range r.alerts {
		if a.Status == "open" {
			open = append(open, a)
		}
	}
	return open, nil
}

func (r *inMemoryFraudRepository) GetAlertsByUser(ctx context.Context, userID string) ([]*fraudmodels.FraudAlert, error) {
	if r.getUserAlertsErr != nil {
		return nil, r.getUserAlertsErr
	}
	var userAlerts []*fraudmodels.FraudAlert
	for _, a := range r.alerts {
		if a.UserID == userID {
			userAlerts = append(userAlerts, a)
		}
	}
	return userAlerts, nil
}

func (r *inMemoryFraudRepository) GetAlertByID(ctx context.Context, id int64) (*fraudmodels.FraudAlert, error) {
	if r.getAlertByIDErr != nil {
		return nil, r.getAlertByIDErr
	}
	for _, a := range r.alerts {
		if a.ID == id {
			return a, nil
		}
	}
	return nil, sql.ErrNoRows
}

func (r *inMemoryFraudRepository) ResolveAlert(ctx context.Context, id int64, resolvedBy, resolution string) error {
	if r.resolveAlertErr != nil {
		return r.resolveAlertErr
	}
	for _, a := range r.alerts {
		if a.ID == id {
			now := time.Now()
			a.ResolvedAt = &now
			a.Status = "resolved"
			return nil
		}
	}
	return sql.ErrNoRows
}

// Stubs for other repository methods (not used in current handler tests)
func (r *inMemoryFraudRepository) CreateRule(ctx context.Context, rule *fraudmodels.FraudRule) error {
	if r.createRuleErr != nil {
		return r.createRuleErr
	}
	rule.ID = r.nextID + 1 // naive
	rule.CreatedAt = time.Now()
	rule.UpdatedAt = time.Now()
	r.rules = append(r.rules, rule)
	return nil
}

func (r *inMemoryFraudRepository) GetRuleByID(ctx context.Context, id int64) (*fraudmodels.FraudRule, error) { return nil, nil }
func (r *inMemoryFraudRepository) UpdateRule(ctx context.Context, rule *fraudmodels.FraudRule) error    { return nil }
func (r *inMemoryFraudRepository) DeleteRule(ctx context.Context, id int64) error                 { return nil }

// ==================== Test helpers ====================

func newTestRouter(auth, admin bool, h *FraudDetectionHandler) *gin.Engine {
	gin.SetMode(gin.TestMode)
	router := gin.New()
	if auth {
		router.Use(func(c *gin.Context) {
			userID := c.GetHeader("X-User-ID")
			if userID == "" {
				c.JSON(401, gin.H{"error": "authentication required"})
				c.Abort()
				return
			}
			c.Set("user_id", userID)
			if role := c.GetHeader("X-User-Role"); role != "" {
				roles := strings.Split(role, ",")
				c.Set("user_roles", roles)
			}
			c.Next()
		})
	}
	// admin flag ignored; handler performs its own admin checks
	h.RegisterRoutes(router)
	return router
}

func newTestFraudHandler(t *testing.T) (*FraudDetectionHandler, *inMemoryFraudRepository) {
	t.Helper()
	repo := &inMemoryFraudRepository{}
	svc := fd.NewFraudService(repo)
	h := NewFraudDetectionHandler(svc)
	return h, repo
}

// ==================== Tests ====================

func TestFraudDetectionHandler_AnalyzeTransaction(t *testing.T) {
	// Setup: rule that triggers when amount > 100, risk score 30
	rule := &fraudmodels.FraudRule{
		Name:        "High Amount",
		Description: "Flag transactions above threshold",
		RuleType:    "amount",
		Condition:   `100`, // JSON number threshold
		Action:      "flag",
		RiskScore:   50,
		IsActive:    true,
		IsEnabled:   true,
	}

	t.Run("success_triggers_rule", func(t *testing.T) {
		h, repo := newTestFraudHandler(t)
		repo.rules = []*fraudmodels.FraudRule{rule}
		router := newTestRouter(true, true, h)

		payload := `{"user_id":"user1","amount":200,"transaction_type":"purchase","payment_method":"card","ip_address":"127.0.0.1","device_id":"dev123","location":"VN"}`
		req := httptest.NewRequest(http.MethodPost, "/api/fraud/analyze", strings.NewReader(payload))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "admin")
		req.Header.Set("X-User-Role", "ROLE_ADMIN")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)

		assert.Equal(t, http.StatusOK, w.Code)
		var resp map[string]interface{}
		require.NoError(t, json.NewDecoder(w.Body).Decode(&resp))
		score, ok := resp["risk_score"].(float64)
		assert.True(t, ok, "risk_score should be number")
		assert.Greater(t, score, float64(0))
		alert, ok := resp["alert"].(map[string]interface{})
		assert.True(t, ok, "alert should be present")
		assert.Equal(t, "user1", alert["user_id"])
		assert.Equal(t, "open", alert["status"])
	})

	t.Run("validation_missing_transaction_type", func(t *testing.T) {
		h, _ := newTestFraudHandler(t)
		router := newTestRouter(true, true, h)
		payload := `{"user_id":"user1","amount":200}`
		req := httptest.NewRequest(http.MethodPost, "/api/fraud/analyze", strings.NewReader(payload))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "admin")
		req.Header.Set("X-User-Role", "ROLE_ADMIN")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusBadRequest, w.Code)
	})

	t.Run("validation_amount_negative", func(t *testing.T) {
		h, _ := newTestFraudHandler(t)
		router := newTestRouter(true, true, h)
		payload := `{"user_id":"user1","amount":-10,"transaction_type":"purchase"}`
		req := httptest.NewRequest(http.MethodPost, "/api/fraud/analyze", strings.NewReader(payload))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "admin")
		req.Header.Set("X-User-Role", "ROLE_ADMIN")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusBadRequest, w.Code)
		var resp map[string]interface{}
		require.NoError(t, json.NewDecoder(w.Body).Decode(&resp))
		assert.Contains(t, resp["error"].(string), "amount must be positive")
	})

	t.Run("validation_user_id_invalid_length", func(t *testing.T) {
		h, _ := newTestFraudHandler(t)
		router := newTestRouter(true, true, h)

		t.Run("too_short", func(t *testing.T) {
			payload := `{"user_id":"","amount":100,"transaction_type":"purchase"}`
			req := httptest.NewRequest(http.MethodPost, "/api/fraud/analyze", strings.NewReader(payload))
			req.Header.Set("Content-Type", "application/json")
			req.Header.Set("X-User-ID", "admin")
			req.Header.Set("X-User-Role", "ROLE_ADMIN")
			w := httptest.NewRecorder()
			router.ServeHTTP(w, req)
			assert.Equal(t, http.StatusBadRequest, w.Code)
		})

		t.Run("too_long", func(t *testing.T) {
			longID := strings.Repeat("u", 101)
			payload := fmt.Sprintf(`{"user_id":"%s","amount":100,"transaction_type":"purchase"}`, longID)
			req := httptest.NewRequest(http.MethodPost, "/api/fraud/analyze", strings.NewReader(payload))
			req.Header.Set("Content-Type", "application/json")
			req.Header.Set("X-User-ID", "admin")
			req.Header.Set("X-User-Role", "ROLE_ADMIN")
			w := httptest.NewRecorder()
			router.ServeHTTP(w, req)
			assert.Equal(t, http.StatusBadRequest, w.Code)
		})
	})

	t.Run("unauthorized", func(t *testing.T) {
		h, _ := newTestFraudHandler(t)
		router := newTestRouter(false, false, h) // no auth
		payload := `{"user_id":"user1","amount":100,"transaction_type":"purchase"}`
		req := httptest.NewRequest(http.MethodPost, "/api/fraud/analyze", strings.NewReader(payload))
		req.Header.Set("Content-Type", "application/json")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusUnauthorized, w.Code)
	})

	t.Run("forbidden_non_admin", func(t *testing.T) {
		h, _ := newTestFraudHandler(t)
		router := newTestRouter(true, true, h) // admin middleware enabled
		payload := `{"user_id":"user1","amount":100,"transaction_type":"purchase"}`
		req := httptest.NewRequest(http.MethodPost, "/api/fraud/analyze", strings.NewReader(payload))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "user1")
		req.Header.Set("X-User-Role", "user") // not admin
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusForbidden, w.Code)
	})

	t.Run("service_error_get_rules", func(t *testing.T) {
		h, repo := newTestFraudHandler(t)
		repo.getActiveRulesErr = fmt.Errorf("db error")
		router := newTestRouter(true, true, h)
		payload := `{"user_id":"user1","amount":200,"transaction_type":"purchase"}`
		req := httptest.NewRequest(http.MethodPost, "/api/fraud/analyze", strings.NewReader(payload))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "admin")
		req.Header.Set("X-User-Role", "ROLE_ADMIN")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusInternalServerError, w.Code)
	})

	t.Run("idempotency", func(t *testing.T) {
		h, repo := newTestFraudHandler(t)
		repo.rules = []*fraudmodels.FraudRule{rule}
		router := newTestRouter(true, true, h)
		payload := `{"user_id":"user1","amount":200,"transaction_type":"purchase"}`
		req := httptest.NewRequest(http.MethodPost, "/api/fraud/analyze", strings.NewReader(payload))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "admin")
		req.Header.Set("X-User-Role", "ROLE_ADMIN")
		req.Header.Set("Idempotency-Key", "test-key-123")
		w1 := httptest.NewRecorder()
		router.ServeHTTP(w1, req)
		if w1.Code != http.StatusOK {
			t.Fatalf("First call failed: status=%d, body: %s", w1.Code, w1.Body.String())
		}
		var resp1 map[string]interface{}
		require.NoError(t, json.NewDecoder(w1.Body).Decode(&resp1))
		score1 := int(resp1["risk_score"].(float64))

		// Second call with same key (recreate request to reset body)
		req2 := httptest.NewRequest(http.MethodPost, "/api/fraud/analyze", strings.NewReader(payload))
		req2.Header.Set("Content-Type", "application/json")
		req2.Header.Set("X-User-ID", "admin")
		req2.Header.Set("X-User-Role", "ROLE_ADMIN")
		req2.Header.Set("Idempotency-Key", "test-key-123")
		w2 := httptest.NewRecorder()
		router.ServeHTTP(w2, req2)
		assert.Equal(t, http.StatusOK, w2.Code)
		var resp2 map[string]interface{}
		require.NoError(t, json.NewDecoder(w2.Body).Decode(&resp2))
		score2 := int(resp2["risk_score"].(float64))
		assert.Equal(t, score1, score2)
		_, ok := resp2["idempotent"]
		assert.True(t, ok)
	})
}

func TestFraudDetectionHandler_GetOpenAlerts(t *testing.T) {
	t.Run("success", func(t *testing.T) {
		h, repo := newTestFraudHandler(t)
		// create some alerts directly via repo
		now := time.Now()
		repo.alerts = []*fraudmodels.FraudAlert{
			{ID: 1, UserID: "u1", TransactionID: "tx1", Type: "transaction", RiskLevel: "high", Score: 80, Status: "open", CreatedAt: now},
			{ID: 2, UserID: "u2", TransactionID: "tx2", Type: "transaction", RiskLevel: "medium", Score: 50, Status: "open", CreatedAt: now},
		}
		router := newTestRouter(true, true, h)
		req := httptest.NewRequest(http.MethodGet, "/api/fraud/alerts", nil)
		req.Header.Set("X-User-ID", "admin")
		req.Header.Set("X-User-Role", "ROLE_ADMIN")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)
		var resp map[string]interface{}
		require.NoError(t, json.NewDecoder(w.Body).Decode(&resp))
		alerts, _ := resp["alerts"].([]interface{})
		assert.Len(t, alerts, 2)
	})

	t.Run("unauthorized", func(t *testing.T) {
		h, _ := newTestFraudHandler(t)
		router := newTestRouter(false, true, h)
		req := httptest.NewRequest(http.MethodGet, "/api/fraud/alerts", nil)
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusUnauthorized, w.Code)
	})

	t.Run("forbidden_non_admin", func(t *testing.T) {
		h, _ := newTestFraudHandler(t)
		router := newTestRouter(true, true, h)
		req := httptest.NewRequest(http.MethodGet, "/api/fraud/alerts", nil)
		req.Header.Set("X-User-ID", "user1")
		req.Header.Set("X-User-Role", "user")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusForbidden, w.Code)
	})

	t.Run("service_error", func(t *testing.T) {
		h, repo := newTestFraudHandler(t)
		repo.getOpenAlertsErr = fmt.Errorf("db error")
		router := newTestRouter(true, true, h)
		req := httptest.NewRequest(http.MethodGet, "/api/fraud/alerts", nil)
		req.Header.Set("X-User-ID", "admin")
		req.Header.Set("X-User-Role", "ROLE_ADMIN")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusInternalServerError, w.Code)
	})
}

func TestFraudDetectionHandler_GetUserAlerts(t *testing.T) {
	now := time.Now()
	alerts := []*fraudmodels.FraudAlert{
		{ID: 1, UserID: "u1", TransactionID: "tx1", Type: "transaction", RiskLevel: "medium", Score: 50, Status: "open", CreatedAt: now},
		{ID: 2, UserID: "u1", TransactionID: "tx2", Type: "transaction", RiskLevel: "low", Score: 30, Status: "resolved", CreatedAt: now, ResolvedAt: &now},
		{ID: 3, UserID: "u2", TransactionID: "tx3", Type: "transaction", RiskLevel: "high", Score: 80, Status: "open", CreatedAt: now},
	}

	t.Run("success_own_alerts", func(t *testing.T) {
		h, repo := newTestFraudHandler(t)
		repo.alerts = alerts
		router := newTestRouter(true, false, h)
		req := httptest.NewRequest(http.MethodGet, "/api/fraud/alerts/user/u1", nil)
		req.Header.Set("X-User-ID", "u1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)
		var resp map[string]interface{}
		require.NoError(t, json.NewDecoder(w.Body).Decode(&resp))
		assert.Equal(t, "u1", resp["user_id"])
		as, _ := resp["alerts"].([]interface{})
		assert.Len(t, as, 2) // two alerts for u1
	})

	t.Run("access_denied_other_user", func(t *testing.T) {
		h, repo := newTestFraudHandler(t)
		repo.alerts = alerts
		router := newTestRouter(true, false, h)
		req := httptest.NewRequest(http.MethodGet, "/api/fraud/alerts/user/u2", nil)
		req.Header.Set("X-User-ID", "u1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusForbidden, w.Code)
	})

	t.Run("admin_allowed", func(t *testing.T) {
		h, repo := newTestFraudHandler(t)
		repo.alerts = alerts
		router := newTestRouter(true, true, h)
		req := httptest.NewRequest(http.MethodGet, "/api/fraud/alerts/user/u1", nil)
		req.Header.Set("X-User-ID", "admin")
		req.Header.Set("X-User-Role", "ROLE_ADMIN")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)
		var resp map[string]interface{}
		require.NoError(t, json.NewDecoder(w.Body).Decode(&resp))
		as, _ := resp["alerts"].([]interface{})
		assert.Len(t, as, 2)
	})

	t.Run("unauthorized", func(t *testing.T) {
		h, _ := newTestFraudHandler(t)
		router := newTestRouter(false, false, h)
		req := httptest.NewRequest(http.MethodGet, "/api/fraud/alerts/user/u1", nil)
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusUnauthorized, w.Code)
	})

	t.Run("service_error", func(t *testing.T) {
		h, repo := newTestFraudHandler(t)
		repo.getUserAlertsErr = fmt.Errorf("db error")
		router := newTestRouter(true, false, h)
		req := httptest.NewRequest(http.MethodGet, "/api/fraud/alerts/user/u1", nil)
		req.Header.Set("X-User-ID", "u1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusInternalServerError, w.Code)
	})
}

func TestFraudDetectionHandler_GetAlertByID(t *testing.T) {
	now := time.Now()
	alerts := []*fraudmodels.FraudAlert{
		{ID: 1, UserID: "u1", TransactionID: "tx1", Type: "transaction", RiskLevel: "medium", Score: 50, Status: "open", CreatedAt: now},
		{ID: 2, UserID: "u2", TransactionID: "tx2", Type: "transaction", RiskLevel: "low", Score: 30, Status: "open", CreatedAt: now},
	}

	t.Run("found", func(t *testing.T) {
		h, repo := newTestFraudHandler(t)
		repo.alerts = alerts
		router := newTestRouter(true, false, h)
		req := httptest.NewRequest(http.MethodGet, "/api/fraud/alerts/1", nil)
		req.Header.Set("X-User-ID", "u1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)
		var resp map[string]interface{}
		require.NoError(t, json.NewDecoder(w.Body).Decode(&resp))
		alert, ok := resp["alert"].(map[string]interface{})
		assert.True(t, ok)
		assert.Equal(t, float64(1), alert["id"])
	})

	t.Run("not_found", func(t *testing.T) {
		h, repo := newTestFraudHandler(t)
		repo.alerts = alerts
		router := newTestRouter(true, false, h)
		req := httptest.NewRequest(http.MethodGet, "/api/fraud/alerts/999", nil)
		req.Header.Set("X-User-ID", "u1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusNotFound, w.Code)
	})

	t.Run("unauthorized", func(t *testing.T) {
		h, repo := newTestFraudHandler(t)
		repo.alerts = alerts
		router := newTestRouter(false, false, h)
		req := httptest.NewRequest(http.MethodGet, "/api/fraud/alerts/1", nil)
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusUnauthorized, w.Code)
	})
}

func TestFraudDetectionHandler_ResolveAlert(t *testing.T) {
	now := time.Now()
	alerts := []*fraudmodels.FraudAlert{
		{ID: 1, UserID: "u1", TransactionID: "tx1", Type: "transaction", RiskLevel: "medium", Score: 50, Status: "open", CreatedAt: now},
	}

	t.Run("success", func(t *testing.T) {
		h, repo := newTestFraudHandler(t)
		repo.alerts = alerts
		router := newTestRouter(true, true, h)
		payload := `{"resolved_by":"admin1","resolution":"false positive"}`
		req := httptest.NewRequest(http.MethodPost, "/api/fraud/alerts/1/resolve", strings.NewReader(payload))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "admin")
		req.Header.Set("X-User-Role", "ROLE_ADMIN")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)
		var resp map[string]interface{}
		require.NoError(t, json.NewDecoder(w.Body).Decode(&resp))
		msg, _ := resp["message"].(string)
		assert.Contains(t, msg, "resolved")
		// Check alert status changed
		require.Len(t, repo.alerts, 1)
		assert.Equal(t, "resolved", repo.alerts[0].Status)
		assert.NotNil(t, repo.alerts[0].ResolvedAt)
	})

	t.Run("unauthorized", func(t *testing.T) {
		h, repo := newTestFraudHandler(t)
		repo.alerts = alerts
		router := newTestRouter(false, true, h)
		payload := `{"resolved_by":"admin1","resolution":"test"}`
		req := httptest.NewRequest(http.MethodPost, "/api/fraud/alerts/1/resolve", strings.NewReader(payload))
		req.Header.Set("Content-Type", "application/json")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusUnauthorized, w.Code)
	})

	t.Run("forbidden_non_admin", func(t *testing.T) {
		h, repo := newTestFraudHandler(t)
		repo.alerts = alerts
		router := newTestRouter(true, true, h)
		payload := `{"resolved_by":"admin1","resolution":"test"}`
		req := httptest.NewRequest(http.MethodPost, "/api/fraud/alerts/1/resolve", strings.NewReader(payload))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "user1")
		req.Header.Set("X-User-Role", "user")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusForbidden, w.Code)
	})

	t.Run("invalid_alert_id", func(t *testing.T) {
		h, _ := newTestFraudHandler(t)
		router := newTestRouter(true, true, h)
		req := httptest.NewRequest(http.MethodPost, "/api/fraud/alerts/abc/resolve", strings.NewReader(`{}`))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "admin")
		req.Header.Set("X-User-Role", "ROLE_ADMIN")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusBadRequest, w.Code)
	})

	t.Run("missing_body", func(t *testing.T) {
		h, repo := newTestFraudHandler(t)
		repo.alerts = alerts
		router := newTestRouter(true, true, h)
		payload := `{}`
		req := httptest.NewRequest(http.MethodPost, "/api/fraud/alerts/1/resolve", strings.NewReader(payload))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "admin")
		req.Header.Set("X-User-Role", "ROLE_ADMIN")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusBadRequest, w.Code)
	})

	t.Run("service_error", func(t *testing.T) {
		h, repo := newTestFraudHandler(t)
		repo.alerts = alerts
		repo.resolveAlertErr = fmt.Errorf("db error")
		router := newTestRouter(true, true, h)
		payload := `{"resolved_by":"admin","resolution":"test"}`
		req := httptest.NewRequest(http.MethodPost, "/api/fraud/alerts/1/resolve", strings.NewReader(payload))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "admin")
		req.Header.Set("X-User-Role", "ROLE_ADMIN")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusInternalServerError, w.Code)
	})
}

func TestFraudDetectionHandler_CreateRule(t *testing.T) {
	t.Run("success", func(t *testing.T) {
		h, repo := newTestFraudHandler(t)
		router := newTestRouter(true, true, h)
		payload := `{"name":"Test Rule","description":"Test","rule_type":"amount","condition":"5000","action":"flag","risk_score":20}`
		req := httptest.NewRequest(http.MethodPost, "/api/fraud/rules", strings.NewReader(payload))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "admin")
		req.Header.Set("X-User-Role", "ROLE_ADMIN")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusCreated, w.Code)
		var resp map[string]interface{}
		require.NoError(t, json.NewDecoder(w.Body).Decode(&resp))
		msg, _ := resp["message"].(string)
		assert.Contains(t, msg, "rule created")
		assert.Len(t, repo.rules, 1)
	})

	t.Run("unauthorized", func(t *testing.T) {
		h, _ := newTestFraudHandler(t)
		router := newTestRouter(false, true, h)
		payload := `{"name":"Test","rule_type":"amount","condition":"100","action":"flag","risk_score":10}`
		req := httptest.NewRequest(http.MethodPost, "/api/fraud/rules", strings.NewReader(payload))
		req.Header.Set("Content-Type", "application/json")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusUnauthorized, w.Code)
	})

	t.Run("forbidden_non_admin", func(t *testing.T) {
		h, _ := newTestFraudHandler(t)
		router := newTestRouter(true, true, h)
		payload := `{"name":"Test","rule_type":"amount","condition":"100","action":"flag","risk_score":10}`
		req := httptest.NewRequest(http.MethodPost, "/api/fraud/rules", strings.NewReader(payload))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "user1")
		req.Header.Set("X-User-Role", "user")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusForbidden, w.Code)
	})

	t.Run("validation_missing_required", func(t *testing.T) {
		h, _ := newTestFraudHandler(t)
		router := newTestRouter(true, true, h)
		payload := `{"name":"Test"}` // missing rule_type, condition, action, risk_score
		req := httptest.NewRequest(http.MethodPost, "/api/fraud/rules", strings.NewReader(payload))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "admin")
		req.Header.Set("X-User-Role", "ROLE_ADMIN")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusBadRequest, w.Code)
	})

	t.Run("service_error", func(t *testing.T) {
		h, repo := newTestFraudHandler(t)
		repo.createRuleErr = fmt.Errorf("db error")
		router := newTestRouter(true, true, h)
		payload := `{"name":"Test","rule_type":"amount","condition":"100","action":"flag","risk_score":10}`
		req := httptest.NewRequest(http.MethodPost, "/api/fraud/rules", strings.NewReader(payload))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "admin")
		req.Header.Set("X-User-Role", "ROLE_ADMIN")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusInternalServerError, w.Code)
	})
}

func TestFraudDetectionHandler_GetActiveRules(t *testing.T) {
	// The handler returns hardcoded default rules; no service dependency
	h, _ := newTestFraudHandler(t)
	router := newTestRouter(true, false, h) // auth required but not admin
	req := httptest.NewRequest(http.MethodGet, "/api/fraud/rules", nil)
	req.Header.Set("X-User-ID", "user1")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)
	assert.Equal(t, http.StatusOK, w.Code)
	var resp map[string]interface{}
	require.NoError(t, json.NewDecoder(w.Body).Decode(&resp))
	rules, ok := resp["rules"].([]interface{})
	assert.True(t, ok)
	assert.Len(t, rules, 3) // three default rules
}

func TestFraudDetectionHandler_GetFraudReports(t *testing.T) {
	t.Run("success_admin", func(t *testing.T) {
		h, _ := newTestFraudHandler(t)
		router := newTestRouter(true, true, h)
		req := httptest.NewRequest(http.MethodGet, "/api/fraud/reports", nil)
		req.Header.Set("X-User-ID", "admin")
		req.Header.Set("X-User-Role", "ROLE_ADMIN")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)
		var resp map[string]interface{}
		require.NoError(t, json.NewDecoder(w.Body).Decode(&resp))
		assert.Equal(t, float64(0), resp["count"])
	})

	t.Run("unauthorized", func(t *testing.T) {
		h, _ := newTestFraudHandler(t)
		router := newTestRouter(false, true, h)
		req := httptest.NewRequest(http.MethodGet, "/api/fraud/reports", nil)
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusUnauthorized, w.Code)
	})

	t.Run("forbidden_non_admin", func(t *testing.T) {
		h, _ := newTestFraudHandler(t)
		router := newTestRouter(true, true, h)
		req := httptest.NewRequest(http.MethodGet, "/api/fraud/reports", nil)
		req.Header.Set("X-User-ID", "user1")
		req.Header.Set("X-User-Role", "user")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusForbidden, w.Code)
	})
}

func TestFraudDetectionHandler_CreateReport(t *testing.T) {
	t.Run("success", func(t *testing.T) {
		h, _ := newTestFraudHandler(t)
		router := newTestRouter(true, false, h)
		payload := `{"description":"Suspicious activity"}`
		req := httptest.NewRequest(http.MethodPost, "/api/fraud/reports", strings.NewReader(payload))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "user1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusCreated, w.Code)
		var resp map[string]interface{}
		require.NoError(t, json.NewDecoder(w.Body).Decode(&resp))
		msg, _ := resp["message"].(string)
		assert.Contains(t, msg, "submitted")
	})

	t.Run("unauthorized", func(t *testing.T) {
		h, _ := newTestFraudHandler(t)
		router := newTestRouter(false, false, h)
		req := httptest.NewRequest(http.MethodPost, "/api/fraud/reports", strings.NewReader(`{}`))
		req.Header.Set("Content-Type", "application/json")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusUnauthorized, w.Code)
	})
}
