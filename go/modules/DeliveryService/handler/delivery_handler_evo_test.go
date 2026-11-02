package handler

import (
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"fmt"
	"path/filepath"
	"strings"
	"testing"

	"community.local/modules/DeliveryService/service"
	"github.com/gin-gonic/gin"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"
)

// createService creates a real DeliveryService with a temp ETS file.
func createService(t *testing.T) *service.DeliveryService {
	t.Helper()
	dir := t.TempDir()
	etsFile := filepath.Join(dir, "delivery.ets")
	return service.NewDeliveryService(etsFile)
}

// newTestRouter creates a router with optional auth and admin middleware.
func newTestRouter(auth, admin bool, h *DeliveryHandler) *gin.Engine {
	gin.SetMode(gin.TestMode)
	router := gin.New()
	if auth {
		router.Use(func(c *gin.Context) {
			userID := c.GetHeader("X-User-ID")
			if userID == "" {
				c.JSON(401, gin.H{"error": "Authentication required"})
				c.Abort()
				return
			}
			c.Set("userID", userID)
			c.Next()
		})
	}
	if admin {
		router.Use(func(c *gin.Context) {
			role := c.GetHeader("X-User-Role")
			if role != "ADMIN" {
				c.JSON(403, gin.H{"error": "Admin access required"})
				c.Abort()
				return
			}
		})
	}
	h.RegisterRoutes(router)
	return router
}

// requireNoError fails test if error is not nil.
func requireNoError(t *testing.T, err error) {
	if err != nil {
		t.Fatalf("expected no error, got %v", err)
	}
}

// ==================== Tests ====================

func TestDeliveryHandler_SetOnline(t *testing.T) {
	svc := createService(t)
	h := NewDeliveryHandler(svc)

	t.Run("success", func(t *testing.T) {
		router := newTestRouter(true, false, h)
		body := `{"user_id":"user1","latitude":10.0,"longitude":20.0}`
		req := httptest.NewRequest("POST", "/api/delivery/online", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "user1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)
		var resp map[string]interface{}
		require.NoError(t, json.NewDecoder(w.Body).Decode(&resp))
		assert.True(t, resp["success"].(bool))
		dp := resp["deliveryPerson"].(map[string]interface{})
		assert.Equal(t, "user1", dp["user_id"])
		assert.True(t, dp["is_online"].(bool))
	})

	t.Run("unauthorized", func(t *testing.T) {
		router := newTestRouter(false, false, h)
		body := `{"user_id":"user1","latitude":10.0,"longitude":20.0}`
		req := httptest.NewRequest("POST", "/api/delivery/online", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusUnauthorized, w.Code)
	})

	t.Run("invalid_json", func(t *testing.T) {
		router := newTestRouter(true, false, h)
		body := `{"user_id": bad}`
		req := httptest.NewRequest("POST", "/api/delivery/online", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "user1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusBadRequest, w.Code)
	})

	t.Run("missing_required_fields", func(t *testing.T) {
		router := newTestRouter(true, false, h)
		body := `{"user_id":"user1"}`
		req := httptest.NewRequest("POST", "/api/delivery/online", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "user1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusBadRequest, w.Code)
	})

	t.Run("validation_user_id_empty", func(t *testing.T) {
		router := newTestRouter(true, false, h)
		body := `{"user_id":"","latitude":10.0,"longitude":20.0}`
		req := httptest.NewRequest("POST", "/api/delivery/online", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "user1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusBadRequest, w.Code)
	})

	t.Run("validation_latitude_invalid", func(t *testing.T) {
		router := newTestRouter(true, false, h)
		body := `{"user_id":"user1","latitude":100.0,"longitude":20.0}`
		req := httptest.NewRequest("POST", "/api/delivery/online", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "user1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusBadRequest, w.Code)
	})

	t.Run("validation_longitude_invalid", func(t *testing.T) {
		router := newTestRouter(true, false, h)
		body := `{"user_id":"user1","latitude":10.0,"longitude":200.0}`
		req := httptest.NewRequest("POST", "/api/delivery/online", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "user1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusBadRequest, w.Code)
	})

	t.Run("forbidden_user_mismatch", func(t *testing.T) {
		router := newTestRouter(true, false, h)
		body := `{"user_id":"otheruser","latitude":10.0,"longitude":20.0}`
		req := httptest.NewRequest("POST", "/api/delivery/online", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "user1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusForbidden, w.Code)
	})

	t.Run("validation_user_id_too_long", func(t *testing.T) {
		router := newTestRouter(true, false, h)
		longID := strings.Repeat("a", 101)
		body := fmt.Sprintf(`{"user_id":"%s","latitude":10.0,"longitude":20.0}`, longID)
		req := httptest.NewRequest("POST", "/api/delivery/online", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "user1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusBadRequest, w.Code)
	})
}

func TestDeliveryHandler_SetOffline(t *testing.T) {
	svc := createService(t)
	h := NewDeliveryHandler(svc)

	t.Run("success", func(t *testing.T) {
		svc.SetOnline("user1", 10.0, 20.0)
		router := newTestRouter(true, false, h)
		body := `{"user_id":"user1"}`
		req := httptest.NewRequest("POST", "/api/delivery/offline", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "user1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)
		var resp map[string]interface{}
		require.NoError(t, json.NewDecoder(w.Body).Decode(&resp))
		assert.True(t, resp["success"].(bool))
	})

	t.Run("unauthorized", func(t *testing.T) {
		router := newTestRouter(false, false, h)
		body := `{"user_id":"user1"}`
		req := httptest.NewRequest("POST", "/api/delivery/offline", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusUnauthorized, w.Code)
	})

	t.Run("missing_user_id", func(t *testing.T) {
		router := newTestRouter(true, false, h)
		body := `{}`
		req := httptest.NewRequest("POST", "/api/delivery/offline", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "user1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusBadRequest, w.Code)
	})

	t.Run("forbidden_user_mismatch", func(t *testing.T) {
		router := newTestRouter(true, false, h)
		body := `{"user_id":"otheruser"}`
		req := httptest.NewRequest("POST", "/api/delivery/offline", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "user1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusForbidden, w.Code)
	})
}

func TestDeliveryHandler_UpdateLocation(t *testing.T) {
	svc := createService(t)
	h := NewDeliveryHandler(svc)

	t.Run("success", func(t *testing.T) {
		svc.SetOnline("user1", 10.0, 20.0)
		router := newTestRouter(true, false, h)
		body := `{"user_id":"user1","latitude":11.0,"longitude":21.0}`
		req := httptest.NewRequest("POST", "/api/delivery/location", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "user1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)
		var resp map[string]interface{}
		require.NoError(t, json.NewDecoder(w.Body).Decode(&resp))
		assert.True(t, resp["success"].(bool))
	})

	t.Run("unauthorized", func(t *testing.T) {
		router := newTestRouter(false, false, h)
		body := `{"user_id":"user1","latitude":11.0,"longitude":21.0}`
		req := httptest.NewRequest("POST", "/api/delivery/location", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusUnauthorized, w.Code)
	})

	t.Run("validation_error_latitude", func(t *testing.T) {
		router := newTestRouter(true, false, h)
		body := `{"user_id":"user1","latitude":200.0,"longitude":21.0}`
		req := httptest.NewRequest("POST", "/api/delivery/location", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "user1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusBadRequest, w.Code)
	})

	t.Run("validation_error_longitude", func(t *testing.T) {
		router := newTestRouter(true, false, h)
		body := `{"user_id":"user1","latitude":10.0,"longitude":200.0}`
		req := httptest.NewRequest("POST", "/api/delivery/location", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "user1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusBadRequest, w.Code)
	})

	t.Run("forbidden_user_mismatch", func(t *testing.T) {
		router := newTestRouter(true, false, h)
		body := `{"user_id":"otheruser","latitude":11.0,"longitude":21.0}`
		req := httptest.NewRequest("POST", "/api/delivery/location", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "user1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusForbidden, w.Code)
	})
}

func TestDeliveryHandler_GetNearbyDeliveryPersons(t *testing.T) {
	svc := createService(t)
	h := NewDeliveryHandler(svc)

	svc.SetOnline("dp1", 10.0, 20.0)
	svc.SetOnline("dp2", 10.001, 20.001)

	t.Run("success", func(t *testing.T) {
		router := newTestRouter(true, false, h)
		req := httptest.NewRequest("GET", "/api/delivery/nearby?latitude=10.0&longitude=20.0&radius=5.0", nil)
		req.Header.Set("X-User-ID", "user1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)
		var resp map[string]interface{}
		require.NoError(t, json.NewDecoder(w.Body).Decode(&resp))
		count := int(resp["count"].(float64))
		assert.GreaterOrEqual(t, count, 2)
		persons := resp["deliveryPersons"].([]interface{})
		assert.Len(t, persons, count)
	})



	t.Run("invalid_latitude", func(t *testing.T) {
		router := newTestRouter(true, false, h)
		req := httptest.NewRequest("GET", "/api/delivery/nearby?latitude=abc&longitude=20&radius=5", nil)
		req.Header.Set("X-User-ID", "user1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusBadRequest, w.Code)
	})

	t.Run("invalid_longitude", func(t *testing.T) {
		router := newTestRouter(true, false, h)
		req := httptest.NewRequest("GET", "/api/delivery/nearby?latitude=10&longitude=abc&radius=5", nil)
		req.Header.Set("X-User-ID", "user1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusBadRequest, w.Code)
	})

	t.Run("invalid_radius_too_large", func(t *testing.T) {
		router := newTestRouter(true, false, h)
		req := httptest.NewRequest("GET", "/api/delivery/nearby?latitude=10&longitude=20&radius=100", nil)
		req.Header.Set("X-User-ID", "user1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusBadRequest, w.Code)
	})

	t.Run("unauthorized", func(t *testing.T) {
		router := newTestRouter(false, false, h)
		req := httptest.NewRequest("GET", "/api/delivery/nearby?latitude=10&longitude=20&radius=5", nil)
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusUnauthorized, w.Code)
	})

	t.Run("default_params", func(t *testing.T) {
		router := newTestRouter(true, false, h)
		req := httptest.NewRequest("GET", "/api/delivery/nearby", nil)
		req.Header.Set("X-User-ID", "user1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)
		var resp map[string]interface{}
		require.NoError(t, json.NewDecoder(w.Body).Decode(&resp))
		count := int(resp["count"].(float64))
		assert.Equal(t, 0, count)
	})

	t.Run("no_delivery_persons", func(t *testing.T) {
		svc2 := createService(t)
		h2 := NewDeliveryHandler(svc2)
		router := newTestRouter(true, false, h2)
		req := httptest.NewRequest("GET", "/api/delivery/nearby?latitude=10.0&longitude=20.0&radius=5.0", nil)
		req.Header.Set("X-User-ID", "user1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)
		var resp map[string]interface{}
		require.NoError(t, json.NewDecoder(w.Body).Decode(&resp))
		count := int(resp["count"].(float64))
		assert.Equal(t, 0, count)
	})

	t.Run("negative_radius", func(t *testing.T) {
		router := newTestRouter(true, false, h)
		req := httptest.NewRequest("GET", "/api/delivery/nearby?latitude=10.0&longitude=20.0&radius=-5", nil)
		req.Header.Set("X-User-ID", "user1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusBadRequest, w.Code)
	})
}

func TestDeliveryHandler_AssignOrder(t *testing.T) {
	svc := createService(t)
	h := NewDeliveryHandler(svc)
	svc.SetOnline("dp1", 10.0, 20.0)

	t.Run("success_admin", func(t *testing.T) {
		router := newTestRouter(true, true, h)
		body := `{"order_id":"order123","restaurant_latitude":10.0,"restaurant_longitude":20.0,"customer_latitude":30.0,"customer_longitude":40.0,"customer_address":"custAddr","restaurant_address":"restAddr"}`
		req := httptest.NewRequest("POST", "/api/delivery/assign", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "admin")
		req.Header.Set("X-User-Role", "ADMIN")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)
		var resp map[string]interface{}
		require.NoError(t, json.NewDecoder(w.Body).Decode(&resp))
		assert.True(t, resp["success"].(bool))
		order := resp["deliveryOrder"].(map[string]interface{})
		assert.Equal(t, "order123", order["order_id"])
	})

	t.Run("unauthorized", func(t *testing.T) {
		router := newTestRouter(false, true, h)
		body := `{"order_id":"order123","restaurant_latitude":10.0,"restaurant_longitude":20.0,"customer_latitude":30.0,"customer_longitude":40.0,"customer_address":"custAddr","restaurant_address":"restAddr"}`
		req := httptest.NewRequest("POST", "/api/delivery/assign", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusForbidden, w.Code)
	})

	t.Run("forbidden_non_admin", func(t *testing.T) {
		router := newTestRouter(true, true, h)
		body := `{"order_id":"order123","restaurant_latitude":10.0,"restaurant_longitude":20.0,"customer_latitude":30.0,"customer_longitude":40.0,"customer_address":"custAddr","restaurant_address":"restAddr"}`
		req := httptest.NewRequest("POST", "/api/delivery/assign", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "user1")
		req.Header.Set("X-User-Role", "user")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusForbidden, w.Code)
	})

	t.Run("missing_required_fields", func(t *testing.T) {
		router := newTestRouter(true, true, h)
		body := `{"order_id":"order123"}`
		req := httptest.NewRequest("POST", "/api/delivery/assign", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "admin")
		req.Header.Set("X-User-Role", "ADMIN")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusBadRequest, w.Code)
	})

	t.Run("validation_restaurant_latitude_invalid", func(t *testing.T) {
		router := newTestRouter(true, true, h)
		body := `{"order_id":"order123","restaurant_latitude":200,"restaurant_longitude":20.0,"customer_latitude":30.0,"customer_longitude":40.0,"customer_address":"custAddr","restaurant_address":"restAddr"}`
		req := httptest.NewRequest("POST", "/api/delivery/assign", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "admin")
		req.Header.Set("X-User-Role", "ADMIN")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusBadRequest, w.Code)
	})

	t.Run("validation_order_id_empty", func(t *testing.T) {
		router := newTestRouter(true, true, h)
		body := `{"order_id":"","restaurant_latitude":10.0,"restaurant_longitude":20.0,"customer_latitude":30.0,"customer_longitude":40.0,"customer_address":"custAddr","restaurant_address":"restAddr"}`
		req := httptest.NewRequest("POST", "/api/delivery/assign", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "admin")
		req.Header.Set("X-User-Role", "ADMIN")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusBadRequest, w.Code)
	})

	t.Run("validation_address_too_long", func(t *testing.T) {
		router := newTestRouter(true, true, h)
		longAddr := strings.Repeat("a", 501)
		body := fmt.Sprintf(`{"order_id":"order123","restaurant_latitude":10.0,"restaurant_longitude":20.0,"customer_latitude":30.0,"customer_longitude":40.0,"customer_address":"%s","restaurant_address":"addr"}`, longAddr)
		req := httptest.NewRequest("POST", "/api/delivery/assign", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "admin")
		req.Header.Set("X-User-Role", "ADMIN")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusBadRequest, w.Code)
	})

	t.Run("no_delivery_persons_available", func(t *testing.T) {
		svc2 := createService(t)
		defer svc2.Terminate()
		h2 := NewDeliveryHandler(svc2)
		router := newTestRouter(true, true, h2)
		body := `{"order_id":"order123","restaurant_latitude":10.0,"restaurant_longitude":20.0,"customer_latitude":30.0,"customer_longitude":40.0,"customer_address":"custAddr","restaurant_address":"restAddr"}`
		req := httptest.NewRequest("POST", "/api/delivery/assign", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "admin")
		req.Header.Set("X-User-Role", "ADMIN")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusBadRequest, w.Code)
	})
}

func TestDeliveryHandler_StartDelivery(t *testing.T) {
	svc := createService(t)
	h := NewDeliveryHandler(svc)
	svc.SetOnline("dp1", 10.0, 20.0)

	t.Run("success", func(t *testing.T) {
		// Create an assigned order and capture internal ID
		order, err := svc.AssignOrder("order123", 10.0, 20.0, 30.0, 40.0, "custAddr", "restAddr")
		require.NoError(t, err)
		router := newTestRouter(true, false, h)
		body := fmt.Sprintf(`{"order_id":"%s"}`, order.ID)
		req := httptest.NewRequest("POST", "/api/delivery/start", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "dp1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)
		var resp map[string]interface{}
		require.NoError(t, json.NewDecoder(w.Body).Decode(&resp))
		assert.True(t, resp["success"].(bool))
		startedOrder := resp["deliveryOrder"].(map[string]interface{})
		assert.Equal(t, "picked_up", startedOrder["status"])
	})

	t.Run("unauthorized", func(t *testing.T) {
		router := newTestRouter(false, false, h)
		body := `{"order_id":"order123"}`
		req := httptest.NewRequest("POST", "/api/delivery/start", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusUnauthorized, w.Code)
	})

	t.Run("missing_order_id", func(t *testing.T) {
		router := newTestRouter(true, false, h)
		body := `{}`
		req := httptest.NewRequest("POST", "/api/delivery/start", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "dp1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusBadRequest, w.Code)
	})

	t.Run("order_not_found", func(t *testing.T) {
		router := newTestRouter(true, false, h)
		body := `{"order_id":"nonexistent"}`
		req := httptest.NewRequest("POST", "/api/delivery/start", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "dp1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusBadRequest, w.Code)
	})
}

func TestDeliveryHandler_CompleteDelivery(t *testing.T) {
	svc := createService(t)
	h := NewDeliveryHandler(svc)
	svc.SetOnline("dp1", 10.0, 20.0)
	order, err := svc.AssignOrder("order123", 10.0, 20.0, 30.0, 40.0, "custAddr", "restAddr")
	require.NoError(t, err)
	_, err = svc.StartDelivery(order.ID)
	require.NoError(t, err)

	t.Run("success", func(t *testing.T) {
		router := newTestRouter(true, false, h)
		body := fmt.Sprintf(`{"order_id":"%s"}`, order.ID)
		req := httptest.NewRequest("POST", "/api/delivery/complete", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "dp1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)
		var resp map[string]interface{}
		require.NoError(t, json.NewDecoder(w.Body).Decode(&resp))
		assert.True(t, resp["success"].(bool))
		completedOrder := resp["deliveryOrder"].(map[string]interface{})
		assert.Equal(t, "delivered", completedOrder["status"])
	})

	t.Run("unauthorized", func(t *testing.T) {
		router := newTestRouter(false, false, h)
		body := `{"order_id":"order123"}`
		req := httptest.NewRequest("POST", "/api/delivery/complete", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusUnauthorized, w.Code)
	})

	t.Run("missing_order_id", func(t *testing.T) {
		router := newTestRouter(true, false, h)
		body := `{}`
		req := httptest.NewRequest("POST", "/api/delivery/complete", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "dp1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusBadRequest, w.Code)
	})

	t.Run("order_not_found", func(t *testing.T) {
		router := newTestRouter(true, false, h)
		body := `{"order_id":"nonexistent"}`
		req := httptest.NewRequest("POST", "/api/delivery/complete", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "dp1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusBadRequest, w.Code)
	})
}

func TestDeliveryHandler_GetDeliveryOrder(t *testing.T) {
	svc := createService(t)
	h := NewDeliveryHandler(svc)
	svc.SetOnline("dp1", 10.0, 20.0)
	order, err := svc.AssignOrder("order123", 10.0, 20.0, 30.0, 40.0, "custAddr", "restAddr")
	require.NoError(t, err)

	t.Run("success", func(t *testing.T) {
		router := newTestRouter(true, false, h)
		req := httptest.NewRequest("GET", "/api/delivery/order/"+order.ID, nil)
		req.Header.Set("X-User-ID", "dp1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)
		var resp map[string]interface{}
		require.NoError(t, json.NewDecoder(w.Body).Decode(&resp))
		orderResp := resp["deliveryOrder"].(map[string]interface{})
		assert.Equal(t, "order123", orderResp["order_id"])
	})

	t.Run("unauthorized", func(t *testing.T) {
		router := newTestRouter(false, false, h)
		req := httptest.NewRequest("GET", "/api/delivery/order/order123", nil)
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusUnauthorized, w.Code)
	})

	t.Run("forbidden_other_user", func(t *testing.T) {
		router := newTestRouter(true, false, h)
		req := httptest.NewRequest("GET", "/api/delivery/order/"+order.ID, nil)
		req.Header.Set("X-User-ID", "otheruser")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusForbidden, w.Code)
	})

	t.Run("admin_allowed", func(t *testing.T) {
		router := newTestRouter(true, false, h)
		req := httptest.NewRequest("GET", "/api/delivery/order/"+order.ID, nil)
		req.Header.Set("X-User-ID", "admin")
		req.Header.Set("X-User-Role", "ADMIN")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)
	})

	t.Run("missing_order_id", func(t *testing.T) {
		router := newTestRouter(true, false, h)
		req := httptest.NewRequest("GET", "/api/delivery/order/", nil)
		req.Header.Set("X-User-ID", "dp1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusNotFound, w.Code) // gin 404
	})

	t.Run("not_found", func(t *testing.T) {
		router := newTestRouter(true, false, h)
		req := httptest.NewRequest("GET", "/api/delivery/order/nonexistent", nil)
		req.Header.Set("X-User-ID", "dp1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusNotFound, w.Code)
	})
}

func TestDeliveryHandler_GetActiveDeliveries(t *testing.T) {
	svc := createService(t)
	h := NewDeliveryHandler(svc)
	svc.SetOnline("dp1", 10.0, 20.0)
	order1, _ := svc.AssignOrder("order1", 10.0, 20.0, 30.0, 40.0, "custAddr", "restAddr")
	order2, _ := svc.AssignOrder("order2", 10.0, 20.0, 30.1, 40.1, "custAddr2", "restAddr2")

	t.Run("success", func(t *testing.T) {
		router := newTestRouter(true, false, h)
		req := httptest.NewRequest("GET", "/api/delivery/active/dp1", nil)
		req.Header.Set("X-User-ID", "dp1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)
		var resp map[string]interface{}
		require.NoError(t, json.NewDecoder(w.Body).Decode(&resp))
		count := int(resp["count"].(float64))
		assert.Equal(t, 2, count)
		orders := resp["orders"].([]interface{})
		assert.Len(t, orders, 2)
		ids := make([]string, 0, len(orders))
		for _, o := range orders {
			if m, ok := o.(map[string]interface{}); ok {
				if id, ok := m["order_id"]; ok {
					ids = append(ids, id.(string))
				}
			}
		}
		assert.Contains(t, ids, order1.OrderID)
		assert.Contains(t, ids, order2.OrderID)
	})

	t.Run("unauthorized", func(t *testing.T) {
		router := newTestRouter(false, false, h)
		req := httptest.NewRequest("GET", "/api/delivery/active/dp1", nil)
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusUnauthorized, w.Code)
	})

	t.Run("forbidden_other_user", func(t *testing.T) {
		router := newTestRouter(true, false, h)
		req := httptest.NewRequest("GET", "/api/delivery/active/dp1", nil)
		req.Header.Set("X-User-ID", "otheruser")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusForbidden, w.Code)
	})

	t.Run("admin_allowed", func(t *testing.T) {
		router := newTestRouter(true, false, h)
		req := httptest.NewRequest("GET", "/api/delivery/active/dp1", nil)
		req.Header.Set("X-User-ID", "admin")
		req.Header.Set("X-User-Role", "ADMIN")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)
	})

	t.Run("missing_delivery_person_id", func(t *testing.T) {
		router := newTestRouter(true, false, h)
		req := httptest.NewRequest("GET", "/api/delivery/active/", nil)
		req.Header.Set("X-User-ID", "dp1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusNotFound, w.Code) // gin 404
	})
}

func TestDeliveryHandler_GetStats(t *testing.T) {
	svc := createService(t)
	h := NewDeliveryHandler(svc)
	svc.SetOnline("dp1", 10.0, 20.0)

	t.Run("success_admin", func(t *testing.T) {
		router := newTestRouter(false, true, h) // admin required, but no user ID needed
		req := httptest.NewRequest("GET", "/api/delivery/stats", nil)
		req.Header.Set("X-User-Role", "ADMIN")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)
		var resp map[string]interface{}
		require.NoError(t, json.NewDecoder(w.Body).Decode(&resp))
		assert.Equal(t, "operational", resp["status"])
		assert.Equal(t, "DeliveryService", resp["service"])
		_, hasTimestamp := resp["timestamp"]
		assert.True(t, hasTimestamp)
	})

	t.Run("forbidden_non_admin", func(t *testing.T) {
		router := newTestRouter(false, false, h) // no admin middleware
		req := httptest.NewRequest("GET", "/api/delivery/stats", nil)
		// Without admin role, handler will return 403 directly
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusForbidden, w.Code)
	})
}

func TestDeliveryHandler_HealthCheck(t *testing.T) {
	svc := createService(t)
	h := NewDeliveryHandler(svc)

	gin.SetMode(gin.TestMode)
	router := gin.New()
	router.GET("/health", h.HealthCheck)

	req := httptest.NewRequest("GET", "/health", nil)
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)
	assert.Equal(t, http.StatusOK, w.Code)
	var resp map[string]interface{}
	require.NoError(t, json.NewDecoder(w.Body).Decode(&resp))
	assert.Equal(t, "healthy", resp["status"])
}
