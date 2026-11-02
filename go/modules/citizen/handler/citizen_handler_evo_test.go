package handler

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
	h := NewCitizenHandler()
	h.RegisterRoutes(router)
	return router
}

// ==================== Tests ====================

func TestCitizen_Create(t *testing.T) {
	t.Run("success", func(t *testing.T) {
		router := newTestRouter(true)
		body := `{"citizenId":"user1","fullName":"John Doe","dateOfBirth":"1990-01-01"}`
		req := httptest.NewRequest("POST", "/api/citizen", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "user1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusCreated, w.Code)
		var resp map[string]interface{}
		requireNoError(t, json.NewDecoder(w.Body).Decode(&resp))
		assert.Equal(t, "user1", resp["citizenId"])
		assert.Equal(t, "John Doe", resp["fullName"])
		assert.Equal(t, "active", resp["status"])
	})

	t.Run("unauthorized", func(t *testing.T) {
		router := newTestRouter(false)
		body := `{"citizenId":"user1","fullName":"John"}`
		req := httptest.NewRequest("POST", "/api/citizen", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusUnauthorized, w.Code)
	})

	t.Run("invalid_json", func(t *testing.T) {
		router := newTestRouter(true)
		body := `{"citizenId":"user1", bad}`
		req := httptest.NewRequest("POST", "/api/citizen", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "user1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusBadRequest, w.Code)
	})

	t.Run("missing_required_fields", func(t *testing.T) {
		router := newTestRouter(true)
		body := `{"citizenId":"user1"}` // missing fullName
		req := httptest.NewRequest("POST", "/api/citizen", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "user1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusBadRequest, w.Code)
	})
}

func TestCitizen_Get(t *testing.T) {
	t.Run("success_own_profile", func(t *testing.T) {
		router := newTestRouter(true)
		req := httptest.NewRequest("GET", "/api/citizen/user1", nil)
		req.Header.Set("X-User-ID", "user1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)
		var resp map[string]interface{}
		requireNoError(t, json.NewDecoder(w.Body).Decode(&resp))
		assert.Equal(t, "user1", resp["id"])
	})

	t.Run("forbidden_other_user", func(t *testing.T) {
		router := newTestRouter(true)
		req := httptest.NewRequest("GET", "/api/citizen/otheruser", nil)
		req.Header.Set("X-User-ID", "user1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusForbidden, w.Code)
	})

	t.Run("admin_can_view_any", func(t *testing.T) {
		router := newTestRouter(true)
		req := httptest.NewRequest("GET", "/api/citizen/otheruser", nil)
		req.Header.Set("X-User-ID", "admin")
		req.Header.Set("X-User-Role", "admin")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)
		var resp map[string]interface{}
		requireNoError(t, json.NewDecoder(w.Body).Decode(&resp))
		assert.Equal(t, "otheruser", resp["id"])
	})

	t.Run("unauthorized", func(t *testing.T) {
		router := newTestRouter(false)
		req := httptest.NewRequest("GET", "/api/citizen/user1", nil)
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusUnauthorized, w.Code)
	})
}

func TestCitizen_Search(t *testing.T) {
	t.Run("success_admin", func(t *testing.T) {
		router := newTestRouter(true)
		req := httptest.NewRequest("GET", "/api/citizen/search?q=john&page=1&limit=10", nil)
		req.Header.Set("X-User-ID", "admin")
		req.Header.Set("X-User-Role", "admin")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)
		var resp map[string]interface{}
		requireNoError(t, json.NewDecoder(w.Body).Decode(&resp))
		dataVal, ok := resp["data"]
		assert.True(t, ok)
		data, ok := dataVal.([]interface{})
		assert.True(t, ok)
		assert.IsType(t, []interface{}{}, data)
		pagination, ok := resp["pagination"].(map[string]interface{})
		assert.True(t, ok)
		assert.Equal(t, "1", pagination["current_page"])
		assert.Equal(t, "10", pagination["per_page"])
	})

	t.Run("forbidden_non_admin", func(t *testing.T) {
		router := newTestRouter(true)
		req := httptest.NewRequest("GET", "/api/citizen/search?q=john", nil)
		req.Header.Set("X-User-ID", "user1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusForbidden, w.Code)
	})

	t.Run("unauthorized", func(t *testing.T) {
		router := newTestRouter(false)
		req := httptest.NewRequest("GET", "/api/citizen/search?q=john", nil)
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusUnauthorized, w.Code)
	})
}

// requireNoError fails test if error is not nil
func requireNoError(t *testing.T, err error) {
	if err != nil {
		t.Fatalf("expected no error, got %v", err)
	}
}
