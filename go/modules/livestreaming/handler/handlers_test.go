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
    h := NewLiveStreamingHandler()
    h.RegisterRoutes(router)
    return router
}

func TestStartStream(t *testing.T) {
    t.Run("success", func(t *testing.T) {
        router := newTestRouter(true)
        req := httptest.NewRequest("POST", "/api/stream/start", nil)
        req.Header.Set("X-User-ID", "user1")
        w := httptest.NewRecorder()
        router.ServeHTTP(w, req)
        assert.Equal(t, http.StatusCreated, w.Code)
        var resp map[string]interface{}
        json.NewDecoder(w.Body).Decode(&resp)
        assert.Contains(t, resp, "streamId")
        assert.Contains(t, resp, "rtmpUrl")
        assert.Contains(t, resp, "hlsUrl")
        assert.Equal(t, "live", resp["status"])
    })

    t.Run("unauthorized", func(t *testing.T) {
        router := newTestRouter(false)
        req := httptest.NewRequest("POST", "/api/stream/start", nil)
        w := httptest.NewRecorder()
        router.ServeHTTP(w, req)
        assert.Equal(t, http.StatusUnauthorized, w.Code)
    })
}

func TestStopStream(t *testing.T) {
    t.Run("success", func(t *testing.T) {
        router := newTestRouter(true)
        req := httptest.NewRequest("POST", "/api/stream/stop/123", nil)
        req.Header.Set("X-User-ID", "user1")
        w := httptest.NewRecorder()
        router.ServeHTTP(w, req)
        assert.Equal(t, http.StatusOK, w.Code)
        var resp map[string]interface{}
        json.NewDecoder(w.Body).Decode(&resp)
        assert.Equal(t, "123", resp["streamId"])
        assert.Equal(t, "ended", resp["status"])
    })

    t.Run("unauthorized", func(t *testing.T) {
        router := newTestRouter(false)
        req := httptest.NewRequest("POST", "/api/stream/stop/123", nil)
        w := httptest.NewRecorder()
        router.ServeHTTP(w, req)
        assert.Equal(t, http.StatusUnauthorized, w.Code)
    })
}

func TestGetStream(t *testing.T) {
    t.Run("success", func(t *testing.T) {
        router := newTestRouter(false)
        req := httptest.NewRequest("GET", "/api/stream/abc123", nil)
        w := httptest.NewRecorder()
        router.ServeHTTP(w, req)
        assert.Equal(t, http.StatusOK, w.Code)
        var resp map[string]interface{}
        json.NewDecoder(w.Body).Decode(&resp)
        assert.Equal(t, "abc123", resp["id"])
        assert.Equal(t, "Live Stream", resp["title"])
        assert.Equal(t, float64(156), resp["viewerCount"])
        assert.Equal(t, "live", resp["status"])
        assert.Equal(t, float64(3600), resp["duration"])
    })
}

func TestListStreams(t *testing.T) {
    t.Run("success", func(t *testing.T) {
        router := newTestRouter(false)
        req := httptest.NewRequest("GET", "/api/stream/list", nil)
        w := httptest.NewRecorder()
        router.ServeHTTP(w, req)
        assert.Equal(t, http.StatusOK, w.Code)
        var resp map[string]interface{}
        json.NewDecoder(w.Body).Decode(&resp)
        streams, ok := resp["streams"].([]interface{})
        assert.True(t, ok)
        assert.Len(t, streams, 2)
    })
}

func TestSendChat(t *testing.T) {
    t.Run("success", func(t *testing.T) {
        router := newTestRouter(true)
        req := httptest.NewRequest("POST", "/api/stream/s123/chat", strings.NewReader(`{"message":"Hello"}`))
        req.Header.Set("Content-Type", "application/json")
        req.Header.Set("X-User-ID", "user1")
        w := httptest.NewRecorder()
        router.ServeHTTP(w, req)
        assert.Equal(t, http.StatusOK, w.Code)
        var resp map[string]interface{}
        json.NewDecoder(w.Body).Decode(&resp)
        assert.True(t, resp["success"].(bool))
    })

    t.Run("unauthorized", func(t *testing.T) {
        router := newTestRouter(false)
        req := httptest.NewRequest("POST", "/api/stream/s123/chat", strings.NewReader(`{"message":"Hi"}`))
        req.Header.Set("Content-Type", "application/json")
        w := httptest.NewRecorder()
        router.ServeHTTP(w, req)
        assert.Equal(t, http.StatusUnauthorized, w.Code)
    })
}

func TestGetChat(t *testing.T) {
    t.Run("success", func(t *testing.T) {
        router := newTestRouter(false)
        req := httptest.NewRequest("GET", "/api/stream/s123/chat", nil)
        w := httptest.NewRecorder()
        router.ServeHTTP(w, req)
        assert.Equal(t, http.StatusOK, w.Code)
        var resp map[string]interface{}
        json.NewDecoder(w.Body).Decode(&resp)
        msgs, ok := resp["messages"].([]interface{})
        assert.True(t, ok)
        assert.Len(t, msgs, 1)
    })
}

func TestAddViewer(t *testing.T) {
    t.Run("success", func(t *testing.T) {
        router := newTestRouter(true)
        req := httptest.NewRequest("POST", "/api/stream/s123/viewer", nil)
        req.Header.Set("X-User-ID", "user1")
        w := httptest.NewRecorder()
        router.ServeHTTP(w, req)
        assert.Equal(t, http.StatusOK, w.Code)
        var resp map[string]interface{}
        json.NewDecoder(w.Body).Decode(&resp)
        assert.True(t, resp["success"].(bool))
        assert.Contains(t, resp, "viewerId")
    })

    t.Run("unauthorized", func(t *testing.T) {
        router := newTestRouter(false)
        req := httptest.NewRequest("POST", "/api/stream/s123/viewer", nil)
        w := httptest.NewRecorder()
        router.ServeHTTP(w, req)
        assert.Equal(t, http.StatusUnauthorized, w.Code)
    })
}
