package handler

import (
    "bytes"
    "context"
    "encoding/json"
    "fmt"
    "net/http"
    "net/http/httptest"
    "testing"

    "github.com/gin-gonic/gin"
    "github.com/stretchr/testify/assert"
    "github.com/stretchr/testify/require"

    "community.local/modules/notifications/service"
)

// ==================== Original tests ====================

func TestSplitRoles(t *testing.T) {
	t.Run("splits roles correctly", func(t *testing.T) {
		roles := "admin,user,citizen"
		result := splitRoles(roles)
		assert.Equal(t, []string{"admin", "user", "citizen"}, result)
	})

	t.Run("handles empty string", func(t *testing.T) {
		result := splitRoles("")
		assert.Empty(t, result)
	})

	t.Run("handles single role", func(t *testing.T) {
		result := splitRoles("admin")
		assert.Equal(t, []string{"admin"}, result)
	})
}

func TestNotificationMiddleware(t *testing.T) {
	gin.SetMode(gin.TestMode)

	t.Run("rejects missing auth header", func(t *testing.T) {
		router := gin.New()
		router.Use(func(c *gin.Context) {
			userID := c.GetHeader("X-User-ID")
			if userID == "" {
				c.AbortWithStatusJSON(http.StatusUnauthorized, gin.H{"error": "authentication required"})
				return
			}
			c.Next()
		})
		router.GET("/test", func(c *gin.Context) {
			c.JSON(http.StatusOK, gin.H{"status": "ok"})
		})

		req, _ := http.NewRequest(http.MethodGet, "/test", nil)
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)

		assert.Equal(t, http.StatusUnauthorized, w.Code)
	})

	t.Run("accepts valid auth header", func(t *testing.T) {
		router := gin.New()
		router.Use(func(c *gin.Context) {
			userID := c.GetHeader("X-User-ID")
			if userID == "" {
				c.AbortWithStatusJSON(http.StatusUnauthorized, gin.H{"error": "authentication required"})
				return
			}
			c.Next()
		})
		router.GET("/test", func(c *gin.Context) {
			c.JSON(http.StatusOK, gin.H{"status": "ok"})
		})

		req, _ := http.NewRequest(http.MethodGet, "/test", nil)
		req.Header.Set("X-User-ID", "user123")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)

		assert.Equal(t, http.StatusOK, w.Code)
	})
}

func TestNotificationHandler_Subscribe(t *testing.T) {
	gin.SetMode(gin.TestMode)

	t.Run("subscribe without auth rejected", func(t *testing.T) {
		router := gin.New()
		router.Use(func(c *gin.Context) {
			userID := c.GetHeader("X-User-ID")
			if userID == "" {
				c.AbortWithStatusJSON(http.StatusUnauthorized, gin.H{"error": "authentication required"})
				return
			}
			c.Next()
		})
		router.POST("/api/notifications/subscribe", func(c *gin.Context) {
			c.JSON(http.StatusOK, gin.H{"status": "ok"})
		})

		req, _ := http.NewRequest(http.MethodPost, "/api/notifications/subscribe", bytes.NewReader([]byte("{}")))
		req.Header.Set("Content-Type", "application/json")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)

		assert.Equal(t, http.StatusUnauthorized, w.Code)
	})
}

func TestNewNotificationHandler(t *testing.T) {
	// Test that handler creation works with nil service
	h := NewNotificationHandler(nil)
	assert.NotNil(t, h)
}

// ==================== Test helpers ====================

func newTestNotificationHandler(t *testing.T) (*NotificationHandler, *service.NotificationService) {
	t.Helper()
	svc := service.NewNotificationService()
	h := NewNotificationHandler(svc)
	return h, svc
}

func authMiddleware() gin.HandlerFunc {
	return func(c *gin.Context) {
		userID := c.GetHeader("X-User-ID")
		if userID == "" {
			c.AbortWithStatusJSON(http.StatusUnauthorized, gin.H{"error": "authentication required"})
			return
		}
		c.Next()
	}
}

func setupRouter(handler *NotificationHandler) *gin.Engine {
	r := gin.New()
	r.Use(authMiddleware())
	r.POST("/api/notifications/subscribe", func(c *gin.Context) { handler.Subscribe(c.Writer, c.Request) })
	r.DELETE("/api/notifications/unsubscribe/:subscriptionId", func(c *gin.Context) { handler.Unsubscribe(c.Writer, c.Request) })
	r.GET("/api/notifications/subscriptions", func(c *gin.Context) { handler.GetUserSubscriptions(c.Writer, c.Request) })
	r.POST("/api/notifications/send", func(c *gin.Context) { handler.SendNotification(c.Writer, c.Request) })
	r.POST("/api/notifications/broadcast", func(c *gin.Context) { handler.SendBroadcast(c.Writer, c.Request) })
	r.GET("/api/notifications/user", func(c *gin.Context) { handler.GetUserNotifications(c.Writer, c.Request) })
	r.POST("/api/notifications/mark-read/:notificationId", func(c *gin.Context) { handler.MarkNotificationRead(c.Writer, c.Request) })
	return r
}

// ==================== Comprehensive tests ====================

func TestNotificationHandler_Subscribe_Success(t *testing.T) {
	gin.SetMode(gin.TestMode)
	h, _ := newTestNotificationHandler(t)
	r := setupRouter(h)

	payload := `{"user_id":"u1","device_token":"token123","device_type":"mobile","device_name":"TestDevice"}`
	req := httptest.NewRequest(http.MethodPost, "/api/notifications/subscribe", bytes.NewReader([]byte(payload)))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("X-User-ID", "anyuser")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusCreated, w.Code)
	var resp map[string]interface{}
	json.NewDecoder(w.Body).Decode(&resp)
	assert.Contains(t, resp, "id")
	assert.Equal(t, "u1", resp["user_id"])
	assert.True(t, resp["active"].(bool))
}

func TestNotificationHandler_Subscribe_InvalidJSON(t *testing.T) {
	gin.SetMode(gin.TestMode)
	h, _ := newTestNotificationHandler(t)
	r := setupRouter(h)

	payload := "invalid json"
	req := httptest.NewRequest(http.MethodPost, "/api/notifications/subscribe", bytes.NewReader([]byte(payload)))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("X-User-ID", "anyuser")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusBadRequest, w.Code)
}

func TestNotificationHandler_Subscribe_ValidationErrors(t *testing.T) {
	gin.SetMode(gin.TestMode)
	h, _ := newTestNotificationHandler(t)
	r := setupRouter(h)

	tests := []struct {
		name string
		body string
	}{
		{"missing user_id", `{"device_token":"t","device_type":"mobile"}`},
		{"missing device_token", `{"user_id":"u","device_type":"mobile"}`},
		{"missing device_type", `{"user_id":"u","device_token":"t"}`},
	}
	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			req := httptest.NewRequest(http.MethodPost, "/api/notifications/subscribe", bytes.NewReader([]byte(tt.body)))
			req.Header.Set("Content-Type", "application/json")
			req.Header.Set("X-User-ID", "anyuser")
			w := httptest.NewRecorder()
			r.ServeHTTP(w, req)
			assert.Equal(t, http.StatusBadRequest, w.Code)
		})
	}
}

func TestNotificationHandler_Unsubscribe(t *testing.T) {
	gin.SetMode(gin.TestMode)
	h, svc := newTestNotificationHandler(t)
	r := setupRouter(h)

	// Create a subscription
	sub, _ := svc.Subscribe(context.Background(), "u1", "token", "mobile", "")
	subID := sub.ID

	t.Run("without auth", func(t *testing.T) {
		req := httptest.NewRequest(http.MethodDelete, "/api/notifications/unsubscribe/"+subID, nil)
		// No auth header
		w := httptest.NewRecorder()
		r.ServeHTTP(w, req)
		assert.Equal(t, http.StatusUnauthorized, w.Code)
	})

	t.Run("missing subscription id", func(t *testing.T) {
		req := httptest.NewRequest(http.MethodDelete, "/api/notifications/unsubscribe/", nil)
		req.Header.Set("X-User-ID", "anyuser")
		w := httptest.NewRecorder()
		r.ServeHTTP(w, req)
		// No route matches, should be 404
		assert.Equal(t, http.StatusNotFound, w.Code)
	})

	t.Run("not found returns 404", func(t *testing.T) {
		req := httptest.NewRequest(http.MethodDelete, "/api/notifications/unsubscribe/nonexistent", nil)
		req.Header.Set("X-User-ID", "anyuser")
		w := httptest.NewRecorder()
		r.ServeHTTP(w, req)
		assert.Equal(t, http.StatusNotFound, w.Code)
	})

	t.Run("success", func(t *testing.T) {
		req := httptest.NewRequest(http.MethodDelete, "/api/notifications/unsubscribe/"+subID, nil)
		req.Header.Set("X-User-ID", "anyuser")
		w := httptest.NewRecorder()
		r.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)
		var resp map[string]interface{}
		json.NewDecoder(w.Body).Decode(&resp)
		if active, ok := resp["active"].(bool); ok {
			assert.False(t, active)
		} else {
			t.Fatal("active field missing or not bool")
		}
	})
}

func TestNotificationHandler_GetUserSubscriptions(t *testing.T) {
	gin.SetMode(gin.TestMode)
	h, svc := newTestNotificationHandler(t)
	r := setupRouter(h)

	// Create subscriptions
	sub1, _ := svc.Subscribe(context.Background(), "u1", "t1", "mobile", "")
	sub2, _ := svc.Subscribe(context.Background(), "u2", "t2", "web", "")

	t.Run("without auth", func(t *testing.T) {
		req := httptest.NewRequest(http.MethodGet, "/api/notifications/subscriptions?user_id=u1", nil)
		w := httptest.NewRecorder()
		r.ServeHTTP(w, req)
		assert.Equal(t, http.StatusUnauthorized, w.Code)
	})

	t.Run("missing user_id", func(t *testing.T) {
		req := httptest.NewRequest(http.MethodGet, "/api/notifications/subscriptions", nil)
		req.Header.Set("X-User-ID", "anyuser")
		w := httptest.NewRecorder()
		r.ServeHTTP(w, req)
		assert.Equal(t, http.StatusBadRequest, w.Code)
	})

	t.Run("empty list", func(t *testing.T) {
		req := httptest.NewRequest(http.MethodGet, "/api/notifications/subscriptions?user_id=u3", nil)
		req.Header.Set("X-User-ID", "anyuser")
		w := httptest.NewRecorder()
		r.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)
		var resp map[string]interface{}
		json.NewDecoder(w.Body).Decode(&resp)
		subsVal := resp["subscriptions"]
		var subs []interface{}
		if subsVal != nil {
			subs = subsVal.([]interface{})
		}
		assert.Len(t, subs, 0)
	})

	t.Run("returns active subscriptions for user", func(t *testing.T) {
		// Deactivate one subscription
		svc.Unsubscribe(context.Background(), sub2.ID)
		req := httptest.NewRequest(http.MethodGet, "/api/notifications/subscriptions?user_id=u1", nil)
		req.Header.Set("X-User-ID", "anyuser")
		w := httptest.NewRecorder()
		r.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)
		var resp map[string]interface{}
		json.NewDecoder(w.Body).Decode(&resp)
		subs := resp["subscriptions"].([]interface{})
		assert.Len(t, subs, 1)
		// Check the active subscription id matches sub1.ID
		subMap := subs[0].(map[string]interface{})
		assert.Equal(t, sub1.ID, subMap["id"])
	})
}

func TestNotificationHandler_SendNotification(t *testing.T) {
	gin.SetMode(gin.TestMode)
	h, _ := newTestNotificationHandler(t)
	r := setupRouter(h)

	t.Run("without auth", func(t *testing.T) {
		payload := `{"user_id":"u1","title":"Hello","body":"World"}`
		req := httptest.NewRequest(http.MethodPost, "/api/notifications/send", bytes.NewReader([]byte(payload)))
		req.Header.Set("Content-Type", "application/json")
		w := httptest.NewRecorder()
		r.ServeHTTP(w, req)
		assert.Equal(t, http.StatusUnauthorized, w.Code)
	})

	t.Run("missing required fields", func(t *testing.T) {
		payload := `{"title":"Hello","body":"World"}`
		req := httptest.NewRequest(http.MethodPost, "/api/notifications/send", bytes.NewReader([]byte(payload)))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "anyuser")
		w := httptest.NewRecorder()
		r.ServeHTTP(w, req)
		assert.Equal(t, http.StatusBadRequest, w.Code)
	})

	t.Run("success", func(t *testing.T) {
		payload := `{"user_id":"u1","title":"Hello","body":"World"}`
		req := httptest.NewRequest(http.MethodPost, "/api/notifications/send", bytes.NewReader([]byte(payload)))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "anyuser")
		w := httptest.NewRecorder()
		r.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)
		var resp map[string]interface{}
		json.NewDecoder(w.Body).Decode(&resp)
		require.NotNil(t, resp["id"])
		assert.Equal(t, "u1", resp["user_id"])
		assert.Equal(t, "Hello", resp["title"])
		// Status may be pending or sent, just ensure exists
		assert.NotNil(t, resp["status"])
	})
}

func TestNotificationHandler_SendBroadcast(t *testing.T) {
	gin.SetMode(gin.TestMode)
	h, _ := newTestNotificationHandler(t)
	r := setupRouter(h)

	t.Run("without auth", func(t *testing.T) {
		payload := `{"user_ids":["u1","u2"],"title":"Hello","body":"World"}`
		req := httptest.NewRequest(http.MethodPost, "/api/notifications/broadcast", bytes.NewReader([]byte(payload)))
		req.Header.Set("Content-Type", "application/json")
		w := httptest.NewRecorder()
		r.ServeHTTP(w, req)
		assert.Equal(t, http.StatusUnauthorized, w.Code)
	})

	t.Run("missing user_ids", func(t *testing.T) {
		payload := `{"title":"Hello","body":"World"}`
		req := httptest.NewRequest(http.MethodPost, "/api/notifications/broadcast", bytes.NewReader([]byte(payload)))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "anyuser")
		w := httptest.NewRecorder()
		r.ServeHTTP(w, req)
		assert.Equal(t, http.StatusBadRequest, w.Code)
	})

	t.Run("success", func(t *testing.T) {
		payload := `{"user_ids":["u1","u2"],"title":"Hello","body":"World"}`
		req := httptest.NewRequest(http.MethodPost, "/api/notifications/broadcast", bytes.NewReader([]byte(payload)))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "anyuser")
		w := httptest.NewRecorder()
		r.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)
		var resp map[string]interface{}
		json.NewDecoder(w.Body).Decode(&resp)
		notifs := resp["notifications"].([]interface{})
		assert.Len(t, notifs, 2)
	})
}

func TestNotificationHandler_GetUserNotifications(t *testing.T) {
	gin.SetMode(gin.TestMode)
	h, svc := newTestNotificationHandler(t)
	r := setupRouter(h)

	// Create notifications for two users
	n1, _ := svc.SendNotification(context.Background(), "u1", "Title1", "Body1", nil)
	_ = n1
	n2, _ := svc.SendNotification(context.Background(), "u1", "Title2", "Body2", nil)
	_ = n2
	svc.SendNotification(context.Background(), "u2", "TitleX", "BodyX", nil)

	t.Run("without auth", func(t *testing.T) {
		req := httptest.NewRequest(http.MethodGet, "/api/notifications/user?user_id=u1", nil)
		w := httptest.NewRecorder()
		r.ServeHTTP(w, req)
		assert.Equal(t, http.StatusUnauthorized, w.Code)
	})

	t.Run("returns notifications for user", func(t *testing.T) {
		req := httptest.NewRequest(http.MethodGet, "/api/notifications/user?user_id=u1", nil)
		req.Header.Set("X-User-ID", "anyuser")
		w := httptest.NewRecorder()
		r.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)
		var resp map[string]interface{}
		json.NewDecoder(w.Body).Decode(&resp)
		notifs := resp["notifications"].([]interface{})
		assert.Len(t, notifs, 2)
	})

	t.Run("pagination works", func(t *testing.T) {
		// Create more notifications for u1
		for i := 0; i < 5; i++ {
			svc.SendNotification(context.Background(), "u1", fmt.Sprintf("TitleExtra%d", i), "Body", nil)
		}
		req := httptest.NewRequest(http.MethodGet, "/api/notifications/user?user_id=u1&limit=2", nil)
		req.Header.Set("X-User-ID", "anyuser")
		w := httptest.NewRecorder()
		r.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)
		var resp map[string]interface{}
		json.NewDecoder(w.Body).Decode(&resp)
		notifs := resp["notifications"].([]interface{})
		assert.Len(t, notifs, 2)
	})
}

func TestNotificationHandler_MarkNotificationRead(t *testing.T) {
	gin.SetMode(gin.TestMode)
	h, svc := newTestNotificationHandler(t)
	r := setupRouter(h)

	n, _ := svc.SendNotification(context.Background(), "u1", "Title", "Body", nil)
	nid := n.ID

	t.Run("without auth", func(t *testing.T) {
		req := httptest.NewRequest(http.MethodPost, "/api/notifications/mark-read/"+nid, nil)
		w := httptest.NewRecorder()
		r.ServeHTTP(w, req)
		assert.Equal(t, http.StatusUnauthorized, w.Code)
	})

	t.Run("missing notification id", func(t *testing.T) {
		req := httptest.NewRequest(http.MethodPost, "/api/notifications/mark-read/", nil)
		req.Header.Set("X-User-ID", "anyuser")
		w := httptest.NewRecorder()
		r.ServeHTTP(w, req)
		// No route matches, expect 404
		assert.Equal(t, http.StatusNotFound, w.Code)
	})

	t.Run("success", func(t *testing.T) {
		req := httptest.NewRequest(http.MethodPost, "/api/notifications/mark-read/"+nid, nil)
		req.Header.Set("X-User-ID", "anyuser")
		w := httptest.NewRecorder()
		r.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)
	})
}