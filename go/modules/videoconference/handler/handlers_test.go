package handler

import (
	"encoding/json"
	"net/http"
	"net/http/httptest"
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
		c.Set("user_roles", []string{"USER"})
	}
}

func newTestRouter(auth bool) *gin.Engine {
	gin.SetMode(gin.TestMode)
	router := gin.New()
	if auth {
		router.Use(authMiddleware())
	}
	h := NewVideoConferenceHandler()
	h.RegisterRoutes(router)
	return router
}

// ==================== Tests ====================

func TestCreateRoom(t *testing.T) {
	t.Run("success", func(t *testing.T) {
		router := newTestRouter(true)
		req := httptest.NewRequest("POST", "/api/video/rooms", nil)
		req.Header.Set("X-User-ID", "user1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusCreated, w.Code)
		var resp map[string]interface{}
		err := json.NewDecoder(w.Body).Decode(&resp)
		if err != nil {
			t.Fatalf("decode error: %v", err)
		}
		assert.Contains(t, resp, "roomId")
		assert.Contains(t, resp, "webrtcConfig")
		ice := resp["webrtcConfig"].(map[string]interface{})
		assert.Contains(t, ice, "iceServers")
	})

	t.Run("unauthorized", func(t *testing.T) {
		router := newTestRouter(false)
		req := httptest.NewRequest("POST", "/api/video/rooms", nil)
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusUnauthorized, w.Code)
	})
}

func TestGetRoom(t *testing.T) {
	t.Run("success", func(t *testing.T) {
		router := newTestRouter(true)
		req := httptest.NewRequest("GET", "/api/video/rooms/room123", nil)
		req.Header.Set("X-User-ID", "user1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)
		var resp map[string]interface{}
		err := json.NewDecoder(w.Body).Decode(&resp)
		if err != nil {
			t.Fatalf("decode error: %v", err)
		}
		assert.Equal(t, "room123", resp["id"])
		assert.Equal(t, float64(3), resp["participants"])
		assert.Equal(t, "active", resp["status"])
	})

	t.Run("unauthorized", func(t *testing.T) {
		router := newTestRouter(false)
		req := httptest.NewRequest("GET", "/api/video/rooms/room123", nil)
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusUnauthorized, w.Code)
	})
}

func TestJoinRoom(t *testing.T) {
	t.Run("success", func(t *testing.T) {
		router := newTestRouter(true)
		req := httptest.NewRequest("POST", "/api/video/rooms/room123/join", nil)
		req.Header.Set("X-User-ID", "user1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)
		var resp map[string]interface{}
		err := json.NewDecoder(w.Body).Decode(&resp)
		if err != nil {
			t.Fatalf("decode error: %v", err)
		}
		assert.True(t, resp["success"].(bool))
		assert.Equal(t, "Joined room", resp["message"])
	})

	t.Run("unauthorized", func(t *testing.T) {
		router := newTestRouter(false)
		req := httptest.NewRequest("POST", "/api/video/rooms/room123/join", nil)
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusUnauthorized, w.Code)
	})
}

func TestLeaveRoom(t *testing.T) {
	t.Run("success", func(t *testing.T) {
		router := newTestRouter(true)
		req := httptest.NewRequest("POST", "/api/video/rooms/room123/leave", nil)
		req.Header.Set("X-User-ID", "user1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)
		var resp map[string]interface{}
		err := json.NewDecoder(w.Body).Decode(&resp)
		if err != nil {
			t.Fatalf("decode error: %v", err)
		}
		assert.True(t, resp["success"].(bool))
		assert.Equal(t, "Left room", resp["message"])
	})

	t.Run("unauthorized", func(t *testing.T) {
		router := newTestRouter(false)
		req := httptest.NewRequest("POST", "/api/video/rooms/room123/leave", nil)
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusUnauthorized, w.Code)
	})
}

func TestGetParticipants(t *testing.T) {
	t.Run("success", func(t *testing.T) {
		router := newTestRouter(true)
		req := httptest.NewRequest("GET", "/api/video/rooms/room123/participants", nil)
		req.Header.Set("X-User-ID", "user1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)
		var resp map[string]interface{}
		err := json.NewDecoder(w.Body).Decode(&resp)
		if err != nil {
			t.Fatalf("decode error: %v", err)
		}
		participants, ok := resp["participants"].([]interface{})
		assert.True(t, ok)
		assert.Len(t, participants, 2)
	})

	t.Run("unauthorized", func(t *testing.T) {
		router := newTestRouter(false)
		req := httptest.NewRequest("GET", "/api/video/rooms/room123/participants", nil)
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusUnauthorized, w.Code)
	})
}

func TestSignal(t *testing.T) {
	t.Run("success", func(t *testing.T) {
		router := newTestRouter(true)
		req := httptest.NewRequest("POST", "/api/video/signal", nil)
		req.Header.Set("X-User-ID", "user1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)
		var resp map[string]interface{}
		err := json.NewDecoder(w.Body).Decode(&resp)
		if err != nil {
			t.Fatalf("decode error: %v", err)
		}
		assert.Equal(t, "signal received", resp["status"])
	})

	t.Run("unauthorized", func(t *testing.T) {
		router := newTestRouter(false)
		req := httptest.NewRequest("POST", "/api/video/signal", nil)
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusUnauthorized, w.Code)
	})
}
