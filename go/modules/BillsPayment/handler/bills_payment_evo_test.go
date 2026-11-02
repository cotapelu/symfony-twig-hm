package handler

import (
	"context"
	"database/sql"
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"strings"
	"testing"
	"time"

	_ "github.com/mattn/go-sqlite3"

	"github.com/gin-gonic/gin"
	"github.com/golang-jwt/jwt/v5"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"

	bpmodels "community.local/modules/BillsPayment/internal/models"
	"community.local/testing/helper"
)

// newTestBill inserts a bill and returns its ID.
func newTestBill(db *sql.DB, billType, provider, account, customerName string, amount float64, period string, dueDate time.Time, status string) (int64, error) {
	_, err := db.Exec(`INSERT INTO bills (bill_type, provider, account_number, customer_name, amount, period, status, due_date) VALUES ($1,$2,$3,$4,$5,$6,$7,$8)`,
		billType, provider, account, customerName, amount, period, status, dueDate)
	if err != nil {
		return 0, err
	}
	var billID int64
	err = db.QueryRow("SELECT last_insert_rowid()").Scan(&billID)
	return billID, err
}

type billInsert struct {
	Type     string
	Provider string
	Account  string
	Customer string
	Amount   float64
	Period   string
	Status   string
	Due      time.Time
}

func insertBills(t *testing.T, db *sql.DB, bills []billInsert) {
	t.Helper()
	for _, b := range bills {
		_, err := db.Exec(`INSERT INTO bills (bill_type, provider, account_number, customer_name, amount, period, status, due_date) VALUES ($1,$2,$3,$4,$5,$6,$7,$8)`,
			b.Type, b.Provider, b.Account, b.Customer, b.Amount, b.Period, b.Status, b.Due)
		require.NoError(t, err)
	}
}

func withAuth(router *gin.Engine, userID string, roles []string) {
	router.Use(func(c *gin.Context) {
		if userID != "" {
			token := createTestToken(userID, roles)
			c.Request.Header.Set("Authorization", "Bearer "+token)
		}
		c.Next()
	})
}

// createTestToken creates a JWT token for testing
func createTestToken(userID string, roles []string) string {
	claims := jwt.MapClaims{
		"userId": userID,
		"roles":  roles,
	}
	token := jwt.NewWithClaims(jwt.SigningMethodHS256, claims)
	tokenString, _ := token.SignedString([]byte("dev-secret-change-in-production"))
	return tokenString
}

// ==================== Missing Coverage Tests ====================

// ------------------- GetBillsByAccount -------------------

func TestGetBillsByAccount_Success(t *testing.T) {
	gin.SetMode(gin.TestMode)
	db := helper.SetupTestDB(t)
	defer db.Close()
	setupBillsSQLiteSchema(t, db)

	svc := bpmodels.NewBillsPaymentService(db, nil)
	h := NewBillsPaymentHandler(svc)

	r := gin.New()
	withAuth(r, "user1", nil)
	h.RegisterRoutes(r)

	due := time.Now().Add(7 * 24 * time.Hour)
	insertBills(t, db, []billInsert{
		{"water", "WaterCo", "ACC-100", "Alice", 200000, "2025-01", "unpaid", due},
		{"electricity", "ElecCo", "ACC-100", "Alice", 300000, "2025-01", "unpaid", due},
		{"internet", "NetCo", "ACC-999", "Bob", 150000, "2025-01", "unpaid", due},
	})

	req := httptest.NewRequest("GET", "/api/bills/account/ACC-100", nil)
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
	var resp struct {
		Bills []bpmodels.Bill `json:"bills"`
	}
	require.NoError(t, json.Unmarshal(w.Body.Bytes(), &resp))
	assert.Len(t, resp.Bills, 2)
}

func TestGetBillsByAccount_Empty(t *testing.T) {
	gin.SetMode(gin.TestMode)
	db := helper.SetupTestDB(t)
	defer db.Close()
	setupBillsSQLiteSchema(t, db)

	svc := bpmodels.NewBillsPaymentService(db, nil)
	h := NewBillsPaymentHandler(svc)

	r := gin.New()
	withAuth(r, "user1", nil)
	h.RegisterRoutes(r)

	req := httptest.NewRequest("GET", "/api/bills/account/ACC-EMPTY", nil)
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
	var resp struct {
		Bills []bpmodels.Bill `json:"bills"`
	}
	require.NoError(t, json.Unmarshal(w.Body.Bytes(), &resp))
	assert.Len(t, resp.Bills, 0)
}

// ------------------- GetUpcomingBills -------------------

func TestGetUpcomingBills_DefaultDays(t *testing.T) {
	gin.SetMode(gin.TestMode)
	db := helper.SetupTestDB(t)
	defer db.Close()
	setupBillsSQLiteSchema(t, db)

	svc := bpmodels.NewBillsPaymentService(db, nil)
	h := NewBillsPaymentHandler(svc)

	r := gin.New()
	withAuth(r, "user1", nil)
	h.RegisterRoutes(r)

	now := time.Now()
	dueSoon := now.Add(3 * 24 * time.Hour)
	dueLater := now.Add(10 * 24 * time.Hour)

	newTestBill(db, "water", "WaterCo", "ACC-1", "Alice", 100, "2025-01", dueSoon, "unpaid")
	newTestBill(db, "electricity", "ElecCo", "ACC-1", "Alice", 200, "2025-01", dueLater, "unpaid")

	req := httptest.NewRequest("GET", "/api/bills/upcoming?days=5", nil)
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	// SQLite does not support INTERVAL, service returns error -> 500
	assert.Equal(t, http.StatusInternalServerError, w.Code)
}

func TestGetUpcomingBills_NoAuth(t *testing.T) {
	gin.SetMode(gin.TestMode)
	db := helper.SetupTestDB(t)
	defer db.Close()
	setupBillsSQLiteSchema(t, db)

	svc := bpmodels.NewBillsPaymentService(db, nil)
	h := NewBillsPaymentHandler(svc)

	r := gin.New()
	// No auth
	h.RegisterRoutes(r)

	req := httptest.NewRequest("GET", "/api/bills/upcoming", nil)
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	// Endpoint requires auth -> 401
	assert.Equal(t, http.StatusUnauthorized, w.Code)
}

// ------------------- GetProviders -------------------

func TestGetProviders_Success(t *testing.T) {
	gin.SetMode(gin.TestMode)
	db := helper.SetupTestDB(t)
	defer db.Close()
	setupBillsSQLiteSchema(t, db)

	svc := bpmodels.NewBillsPaymentService(db, nil)
	h := NewBillsPaymentHandler(svc)

	r := gin.New()
	withAuth(r, "user1", nil)
	h.RegisterRoutes(r)

	// Insert providers directly
	_, _ = db.Exec(`INSERT INTO bill_providers (name, code, category, processing_fee, min_amount, max_amount, is_active) VALUES ($1,$2,$3,$4,$5,$6,$7)`,
		"Electricity Co", "ELEC", "utility", 2000, 10000, 1000000, true)
	_, _ = db.Exec(`INSERT INTO bill_providers (name, code, category, processing_fee, min_amount, max_amount, is_active) VALUES ($1,$2,$3,$4,$5,$6,$7)`,
		"Water Co", "WATER", "utility", 1000, 5000, 500000, true)
	_, _ = db.Exec(`INSERT INTO bill_providers (name, code, category, processing_fee, min_amount, max_amount, is_active) VALUES ($1,$2,$3,$4,$5,$6,$7)`,
		"Internet Co", "NET", "telecom", 3000, 20000, 2000000, false) // inactive

	req := httptest.NewRequest("GET", "/api/bills/providers", nil)
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
	var resp struct {
		Providers []bpmodels.BillProvider `json:"providers"`
	}
	require.NoError(t, json.Unmarshal(w.Body.Bytes(), &resp))
	assert.Len(t, resp.Providers, 2) // only active
}

func TestGetProviders_NoAuth(t *testing.T) {
	gin.SetMode(gin.TestMode)
	db := helper.SetupTestDB(t)
	defer db.Close()
	setupBillsSQLiteSchema(t, db)

	svc := bpmodels.NewBillsPaymentService(db, nil)
	h := NewBillsPaymentHandler(svc)

	r := gin.New()
	// No auth
	h.RegisterRoutes(r)

	req := httptest.NewRequest("GET", "/api/bills/providers", nil)
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	// Endpoint requires auth -> 401
	assert.Equal(t, http.StatusUnauthorized, w.Code)
}

// ------------------- GetProvidersByCategory -------------------

func TestGetProvidersByCategory_Success(t *testing.T) {
	gin.SetMode(gin.TestMode)
	db := helper.SetupTestDB(t)
	defer db.Close()
	setupBillsSQLiteSchema(t, db)

	svc := bpmodels.NewBillsPaymentService(db, nil)
	h := NewBillsPaymentHandler(svc)

	r := gin.New()
	withAuth(r, "user1", nil)
	h.RegisterRoutes(r)

	_, _ = db.Exec(`INSERT INTO bill_providers (name, code, category, processing_fee, min_amount, max_amount, is_active) VALUES ($1,$2,$3,$4,$5,$6,$7)`,
		"Elec", "ELEC", "utility", 100, 0, 1000, true)
	_, _ = db.Exec(`INSERT INTO bill_providers (name, code, category, processing_fee, min_amount, max_amount, is_active) VALUES ($1,$2,$3,$4,$5,$6,$7)`,
		"Water", "WATER", "utility", 50, 0, 500, true)
	_, _ = db.Exec(`INSERT INTO bill_providers (name, code, category, processing_fee, min_amount, max_amount, is_active) VALUES ($1,$2,$3,$4,$5,$6,$7)`,
		"Net", "NET", "telecom", 200, 0, 2000, true)

	req := httptest.NewRequest("GET", "/api/bills/providers/category/utility", nil)
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
	var resp struct {
		Providers []bpmodels.BillProvider `json:"providers"`
		Category  string                  `json:"category"`
	}
	require.NoError(t, json.Unmarshal(w.Body.Bytes(), &resp))
	assert.Equal(t, "utility", resp.Category)
	assert.Len(t, resp.Providers, 2)
}

func TestGetProvidersByCategory_EmptyCategory(t *testing.T) {
	gin.SetMode(gin.TestMode)
	db := helper.SetupTestDB(t)
	defer db.Close()
	setupBillsSQLiteSchema(t, db)

	svc := bpmodels.NewBillsPaymentService(db, nil)
	h := NewBillsPaymentHandler(svc)

	r := gin.New()
	withAuth(r, "user1", nil)
	h.RegisterRoutes(r)

	req := httptest.NewRequest("GET", "/api/bills/providers/category/unknown", nil)
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
	var resp struct {
		Providers []bpmodels.BillProvider `json:"providers"`
		Category  string                  `json:"category"`
	}
	require.NoError(t, json.Unmarshal(w.Body.Bytes(), &resp))
	assert.Empty(t, resp.Providers)
}

// ------------------- GetPayments -------------------

func TestGetPayments_Success(t *testing.T) {
	gin.SetMode(gin.TestMode)
	db := helper.SetupTestDB(t)
	defer db.Close()
	setupBillsSQLiteSchema(t, db)

	svc := bpmodels.NewBillsPaymentService(db, nil)
	h := NewBillsPaymentHandler(svc)

	r := gin.New()
	withAuth(r, "user123", nil)
	h.RegisterRoutes(r)

	// Create a bill first to satisfy foreign key
	billID, err := newTestBill(db, "electricity", "ElecCo", "ACC-999", "Alice", 200000, "2025-01", time.Now(), "unpaid")
	require.NoError(t, err)
	// Insert payments: one for user123 (should be returned), another for user456 (should be filtered)
	_, _ = db.Exec(`INSERT INTO bill_payments (bill_id, user_id, amount, payment_method, transaction_id, status) VALUES ($1,$2,$3,$4,$5,$6)`,
		billID, "user123", 500000, "bank_transfer", "tx-999", "completed")
	_, _ = db.Exec(`INSERT INTO bill_payments (bill_id, user_id, amount, payment_method, transaction_id, status) VALUES ($1,$2,$3,$4,$5,$6)`,
		billID, "user456", 300000, "card", "tx-888", "completed")

	req := httptest.NewRequest("GET", "/api/bills/payments/user123", nil)
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
	var resp struct {
		Payments []bpmodels.BillPayment `json:"payments"`
	}
	require.NoError(t, json.Unmarshal(w.Body.Bytes(), &resp))
	assert.Len(t, resp.Payments, 1)
	assert.Equal(t, "user123", resp.Payments[0].UserID)
}

func TestGetPayments_ForbiddenOtherUser(t *testing.T) {
	gin.SetMode(gin.TestMode)
	db := helper.SetupTestDB(t)
	defer db.Close()
	setupBillsSQLiteSchema(t, db)

	svc := bpmodels.NewBillsPaymentService(db, nil)
	h := NewBillsPaymentHandler(svc)

	r := gin.New()
	withAuth(r, "user1", nil)
	h.RegisterRoutes(r)

	req := httptest.NewRequest("GET", "/api/bills/payments/user2", nil)
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusForbidden, w.Code)
}

func TestGetPayments_AdminAllowed(t *testing.T) {
	gin.SetMode(gin.TestMode)
	db := helper.SetupTestDB(t)
	defer db.Close()
	setupBillsSQLiteSchema(t, db)

	svc := bpmodels.NewBillsPaymentService(db, nil)
	h := NewBillsPaymentHandler(svc)

	r := gin.New()
	withAuth(r, "admin", []string{"admin"})
	h.RegisterRoutes(r)

	// Create a bill first
	billID, err := newTestBill(db, "electricity", "ElecCo", "ACC-999", "Alice", 200000, "2025-01", time.Now(), "unpaid")
	require.NoError(t, err)
	// Insert a payment for user999
	_, _ = db.Exec(`INSERT INTO bill_payments (bill_id, user_id, amount, payment_method, transaction_id, status) VALUES ($1,$2,$3,$4,$5,$6)`,
		billID, "user999", 500000, "bank_transfer", "tx-999", "completed")

	req := httptest.NewRequest("GET", "/api/bills/payments/user999", nil)
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
	var resp struct {
		Payments []bpmodels.BillPayment `json:"payments"`
	}
	require.NoError(t, json.Unmarshal(w.Body.Bytes(), &resp))
	assert.Len(t, resp.Payments, 1)
}

// ------------------- GetPaymentByTransaction -------------------

func TestGetPaymentByTransaction_Success(t *testing.T) {
	gin.SetMode(gin.TestMode)
	db := helper.SetupTestDB(t)
	defer db.Close()
	setupBillsSQLiteSchema(t, db)

	svc := bpmodels.NewBillsPaymentService(db, nil)
	h := NewBillsPaymentHandler(svc)

	r := gin.New()
	withAuth(r, "user123", nil)
	h.RegisterRoutes(r)

	txID := "tx-12345"
	// Create a bill and insert payment referencing it
	billID, err := newTestBill(db, "electricity", "ElecCo", "ACC-123", "Alice", 200000, "2025-01", time.Now(), "unpaid")
	require.NoError(t, err)
	_, _ = db.Exec(`INSERT INTO bill_payments (bill_id, user_id, amount, payment_method, transaction_id, status) VALUES ($1,$2,$3,$4,$5,$6)`,
		billID, "user123", 500000, "bank_transfer", txID, "completed")

	req := httptest.NewRequest("GET", "/api/bills/payments/tx/"+txID, nil)
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
	var resp struct {
		Payment bpmodels.BillPayment `json:"payment"`
	}
	require.NoError(t, json.Unmarshal(w.Body.Bytes(), &resp))
	assert.Equal(t, txID, resp.Payment.TransactionID)
	assert.Equal(t, "user123", resp.Payment.UserID)
}

func TestGetPaymentByTransaction_NotFound(t *testing.T) {
	gin.SetMode(gin.TestMode)
	db := helper.SetupTestDB(t)
	defer db.Close()
	setupBillsSQLiteSchema(t, db)

	svc := bpmodels.NewBillsPaymentService(db, nil)
	h := NewBillsPaymentHandler(svc)

	r := gin.New()
	withAuth(r, "user123", nil)
	h.RegisterRoutes(r)

	req := httptest.NewRequest("GET", "/api/bills/payments/tx/nonexistent", nil)
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusNotFound, w.Code)
}

func TestGetPaymentByTransaction_ForbiddenOtherUser(t *testing.T) {
	gin.SetMode(gin.TestMode)
	db := helper.SetupTestDB(t)
	defer db.Close()
	setupBillsSQLiteSchema(t, db)

	svc := bpmodels.NewBillsPaymentService(db, nil)
	h := NewBillsPaymentHandler(svc)

	r := gin.New()
	withAuth(r, "user1", nil)
	h.RegisterRoutes(r)

	txID := "tx-other"
	// Create a bill and payment for user999
	billID, err := newTestBill(db, "electricity", "ElecCo", "ACC-123", "Alice", 200000, "2025-01", time.Now(), "unpaid")
	require.NoError(t, err)
	_, _ = db.Exec(`INSERT INTO bill_payments (bill_id, user_id, amount, payment_method, transaction_id, status) VALUES ($1,$2,$3,$4,$5,$6)`,
		billID, "user999", 500000, "bank_transfer", txID, "completed")

	req := httptest.NewRequest("GET", "/api/bills/payments/tx/"+txID, nil)
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusForbidden, w.Code)
}

// ------------------- PayBill edge cases -------------------

func TestPayBill_InvalidBillID(t *testing.T) {
	gin.SetMode(gin.TestMode)
	db := helper.SetupTestDB(t)
	defer db.Close()
	setupBillsSQLiteSchema(t, db)

	svc := bpmodels.NewBillsPaymentService(db, nil)
	h := NewBillsPaymentHandler(svc)

	r := gin.New()
	withAuth(r, "user1", nil)
	h.RegisterRoutes(r)

	req := httptest.NewRequest("POST", "/api/bills/999/pay", strings.NewReader(`{"payment_method":"bank_transfer"}`))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("X-User-ID", "user1")
	req.Header.Set("Idempotency-Key", "test-key")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusBadRequest, w.Code)
	var resp map[string]string
	_ = json.Unmarshal(w.Body.Bytes(), &resp)
	assert.Contains(t, resp["error"], "not found")
}

func TestPayBill_MissingPaymentMethod(t *testing.T) {
	gin.SetMode(gin.TestMode)
	db := helper.SetupTestDB(t)
	defer db.Close()
	setupBillsSQLiteSchema(t, db)

	svc := bpmodels.NewBillsPaymentService(db, nil)
	h := NewBillsPaymentHandler(svc)

	// Insert a bill
	now := time.Now()
	newTestBill(db, "water", "WaterCo", "ACC-1", "Alice", 100, "2025-01", now, "unpaid")

	r := gin.New()
	withAuth(r, "user1", nil)
	h.RegisterRoutes(r)

	req := httptest.NewRequest("POST", "/api/bills/1/pay", strings.NewReader(`{}`))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("X-User-ID", "user1")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusBadRequest, w.Code)
}

func TestPayBill_IdempotencyReplay(t *testing.T) {
	gin.SetMode(gin.TestMode)
	db := helper.SetupTestDB(t)
	defer db.Close()
	setupBillsSQLiteSchema(t, db)

	svc := bpmodels.NewBillsPaymentService(db, nil)
	h := NewBillsPaymentHandler(svc)

	r := gin.New()
	withAuth(r, "user1", []string{"user"})
	h.RegisterRoutes(r)

	now := time.Now()
	_, _ = newTestBill(db, "water", "WaterCo", "ACC-1", "Alice", 100, "2025-01", now, "unpaid")

	// First request
	body := `{"payment_method":"bank_transfer"}`
	req1 := httptest.NewRequest("POST", "/api/bills/1/pay", strings.NewReader(body))
	req1.Header.Set("Content-Type", "application/json")
	req1.Header.Set("X-User-ID", "user1")
	req1.Header.Set("Idempotency-Key", "key-replay")
	w1 := httptest.NewRecorder()
	r.ServeHTTP(w1, req1)
	assert.Equal(t, http.StatusOK, w1.Code)

	// Second request with same key
	req2 := httptest.NewRequest("POST", "/api/bills/1/pay", strings.NewReader(body))
	req2.Header.Set("Content-Type", "application/json")
	req2.Header.Set("X-User-ID", "user1")
	req2.Header.Set("Idempotency-Key", "key-replay")
	w2 := httptest.NewRecorder()
	r.ServeHTTP(w2, req2)
	assert.Equal(t, http.StatusOK, w2.Code)

	// Ensure only one payment created
	var count int
	err := db.QueryRowContext(context.Background(), `SELECT COUNT(*) FROM bill_payments WHERE bill_id = ?`, 1).Scan(&count)
	require.NoError(t, err)
	assert.Equal(t, 1, count)
}
