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
	h := NewLandPermitHandler()
	router.POST("/permits", h.CreatePermit)
	router.GET("/permits/:id", h.GetPermit)
	return router
}

// ==================== Tests ====================

func TestCreatePermit(t *testing.T) {
	t.Run("success", func(t *testing.T) {
		router := newTestRouter(true)
		body := `{"land_record_id":"lr1","owner_id":"user1","permit_type":"xây dựng mới","purpose":"Build house","area":150.5}`
		req := httptest.NewRequest("POST", "/permits", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "user1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusCreated, w.Code)
		var resp map[string]interface{}
		json.NewDecoder(w.Body).Decode(&resp)
		assert.Equal(t, "pending", resp["status"])
		assert.Equal(t, "lr1", resp["land_record_id"])
		assert.Equal(t, "user1", resp["owner_id"])
	})

	t.Run("success_admin", func(t *testing.T) {
		router := newTestRouter(true)
		body := `{"land_record_id":"lr1","owner_id":"otheruser","permit_type":"xây dựng mới","purpose":"Build house","area":100}`
		req := httptest.NewRequest("POST", "/permits", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "admin")
		req.Header.Set("X-User-Role", "admin")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusCreated, w.Code)
	})

	t.Run("unauthorized", func(t *testing.T) {
		router := newTestRouter(false)
		body := `{"land_record_id":"lr1","owner_id":"user1","permit_type":"xây dựng mới","purpose":"Build house","area":100}`
		req := httptest.NewRequest("POST", "/permits", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusUnauthorized, w.Code)
	})

	t.Run("invalid_json", func(t *testing.T) {
		router := newTestRouter(true)
		body := `{"land_record_id":"lr1", invalid}`
		req := httptest.NewRequest("POST", "/permits", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "user1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusBadRequest, w.Code)
	})

	t.Run("missing_required_fields", func(t *testing.T) {
		router := newTestRouter(true)
		body := `{"land_record_id":"lr1"}` // missing owner_id leads to forbidden because owner_id != user
		req := httptest.NewRequest("POST", "/permits", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "user1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusForbidden, w.Code)
	})

	t.Run("forbidden", func(t *testing.T) {
		router := newTestRouter(true)
		body := `{"land_record_id":"lr1","owner_id":"otheruser","permit_type":"xây dựng mới","purpose":"Build house","area":100}`
		req := httptest.NewRequest("POST", "/permits", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "user1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusForbidden, w.Code)
	})
}

func TestGetPermit(t *testing.T) {
	t.Run("success", func(t *testing.T) {
		router := newTestRouter(true)
		req := httptest.NewRequest("GET", "/permits/123", nil)
		req.Header.Set("X-User-ID", "user1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)
		var resp map[string]interface{}
		json.NewDecoder(w.Body).Decode(&resp)
		assert.Equal(t, "123", resp["id"])
		assert.Equal(t, "approved", resp["status"])
		assert.Equal(t, "user1", resp["owner_id"])
	})

	t.Run("unauthorized", func(t *testing.T) {
		router := newTestRouter(false)
		req := httptest.NewRequest("GET", "/permits/123", nil)
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusUnauthorized, w.Code)
	})
}
