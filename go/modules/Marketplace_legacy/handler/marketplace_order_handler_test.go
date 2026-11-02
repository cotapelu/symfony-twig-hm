package handlers

import (
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"strings"
	"testing"

	"github.com/gin-gonic/gin"
	"github.com/stretchr/testify/assert"
)

// ==================== Test Helpers ====================

func authMiddleware() gin.HandlerFunc {
	return func(c *gin.Context) {
		userID := c.GetHeader("X-User-ID")
		if userID == "" {
			c.JSON(http.StatusUnauthorized, gin.H{"error": "authentication required"})
			c.Abort()
			return
		}
		c.Set("user_id", userID)
		roles := []string{"USER"}
		if c.GetHeader("X-User-Role") == "admin" {
			roles = []string{"admin"}
		}
		c.Set("user_roles", roles)
	}
}

func newTestRouter(auth bool) *gin.Engine {
	gin.SetMode(gin.TestMode)
	router := gin.New()
	if auth {
		router.Use(authMiddleware())
	}
	h := NewMarketplaceOrderHandler()
	router.POST("/orders", h.CreateOrder)
	router.GET("/orders/:id", h.GetOrder)
	return router
}

// ==================== Tests ====================

func TestCreateOrder(t *testing.T) {
	t.Run("success", func(t *testing.T) {
		router := newTestRouter(true)
		body := `{"item_id":"item1","buyer_id":"user1","amount":99.99,"quantity":2}`
		req := httptest.NewRequest("POST", "/orders", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "user1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusCreated, w.Code)
		var resp map[string]interface{}
		json.NewDecoder(w.Body).Decode(&resp)
		assert.Equal(t, "pending", resp["status"])
		assert.Equal(t, "user1", resp["buyer_id"])
	})

	t.Run("success_admin_on_behalf", func(t *testing.T) {
		router := newTestRouter(true)
		body := `{"item_id":"item1","buyer_id":"otheruser","amount":50,"quantity":1}`
		req := httptest.NewRequest("POST", "/orders", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "admin")
		req.Header.Set("X-User-Role", "admin")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusCreated, w.Code)
	})

	t.Run("unauthorized", func(t *testing.T) {
		router := newTestRouter(false)
		body := `{"item_id":"item1","buyer_id":"user1","amount":100,"quantity":1}`
		req := httptest.NewRequest("POST", "/orders", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusUnauthorized, w.Code)
	})

	t.Run("invalid_json", func(t *testing.T) {
		router := newTestRouter(true)
		body := `{"item_id":"item1", bad}`
		req := httptest.NewRequest("POST", "/orders", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "user1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusBadRequest, w.Code)
	})

	t.Run("missing_required_fields", func(t *testing.T) {
		router := newTestRouter(true)
		body := `{"item_id":"item1"}` // missing buyer_id leads to forbidden due to ownership check
		req := httptest.NewRequest("POST", "/orders", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "user1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusForbidden, w.Code)
	})

	t.Run("forbidden", func(t *testing.T) {
		router := newTestRouter(true)
		body := `{"item_id":"item1","buyer_id":"otheruser","amount":100,"quantity":1}`
		req := httptest.NewRequest("POST", "/orders", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "user1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusForbidden, w.Code)
	})
}

func TestGetOrder(t *testing.T) {
	t.Run("success", func(t *testing.T) {
		router := newTestRouter(true)
		req := httptest.NewRequest("GET", "/orders/order123", nil)
		req.Header.Set("X-User-ID", "user1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)
		var resp map[string]interface{}
		json.NewDecoder(w.Body).Decode(&resp)
		assert.Equal(t, "order123", resp["id"])
		assert.Equal(t, "completed", resp["status"])
		assert.Equal(t, "user1", resp["buyer_id"])
	})

	t.Run("unauthorized", func(t *testing.T) {
		router := newTestRouter(false)
		req := httptest.NewRequest("GET", "/orders/order123", nil)
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusUnauthorized, w.Code)
	})
}
