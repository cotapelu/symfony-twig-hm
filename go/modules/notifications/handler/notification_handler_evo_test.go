package handler

import (
	"bytes"
	"context"
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"strings"
	"testing"

	"github.com/gin-gonic/gin"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"

	"community.local/modules/notifications/service"
)

// ==================== Test Setup Helpers ====================

func setupService() *service.NotificationService {
	return service.NewNotificationService()
}

func newTestHandler(t *testing.T) (*NotificationHandler, *service.NotificationService) {
	t.Helper()
	svc := setupService()
	h := NewNotificationHandler(svc)
	return h, svc
}

func authMiddlewareEvo() gin.HandlerFunc {
	return func(c *gin.Context) {
		userID := c.GetHeader("X-User-ID")
		if userID == "" {
			c.AbortWithStatusJSON(http.StatusUnauthorized, gin.H{"error": "authentication required"})
			return
		}
		// Optional admin role parsing
		userRoles := c.GetHeader("X-User-Roles")
		if userRoles != "" {
			roles := splitRoles(userRoles)
			for _, r := range roles {
				if r == "admin" || r == "ROLE_ADMIN" {
					// Just set a flag in context (not used by handlers currently)
					c.Set("isAdmin", true)
					break
				}
			}
		}
		c.Next()
	}
}

func setupRouterEvo(handler *NotificationHandler) *gin.Engine {
	r := gin.New()
	r.Use(authMiddlewareEvo())
	r.POST("/api/notifications/subscribe", func(c *gin.Context) { handler.Subscribe(c.Writer, c.Request) })
	r.DELETE("/api/notifications/unsubscribe/:subscriptionId", func(c *gin.Context) { handler.Unsubscribe(c.Writer, c.Request) })
	r.GET("/api/notifications/subscriptions", func(c *gin.Context) { handler.GetUserSubscriptions(c.Writer, c.Request) })
	r.POST("/api/notifications/send", func(c *gin.Context) { handler.SendNotification(c.Writer, c.Request) })
	r.POST("/api/notifications/broadcast", func(c *gin.Context) { handler.SendBroadcast(c.Writer, c.Request) })
	r.GET("/api/notifications/user", func(c *gin.Context) { handler.GetUserNotifications(c.Writer, c.Request) })
	r.POST("/api/notifications/mark-read/:notificationId", func(c *gin.Context) { handler.MarkNotificationRead(c.Writer, c.Request) })
	r.POST("/api/notifications/process-scheduled", func(c *gin.Context) { handler.ProcessScheduled(c.Writer, c.Request) })
	return r
}

// ==================== Subscription Tests ====================

func TestSubscribe_Success(t *testing.T) {
	h, _ := newTestHandler(t)
	r := setupRouterEvo(h)

	body := map[string]interface{}{
		"user_id":      "user123",
		"device_token": "token-abc",
		"device_type":  "ios",
		"device_name":  "iPhone",
	}
	jsonBody, _ := json.Marshal(body)

	req := httptest.NewRequest(http.MethodPost, "/api/notifications/subscribe", bytes.NewReader(jsonBody))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("X-User-ID", "user123")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusCreated, w.Code)
	var resp map[string]interface{}
	require.NoError(t, json.Unmarshal(w.Body.Bytes(), &resp))
	assert.Contains(t, resp, "id")
	assert.Equal(t, "user123", resp["user_id"])
	assert.Equal(t, "token-abc", resp["device_token"])
	assert.True(t, resp["active"].(bool))
}

func TestSubscribe_MissingAuth(t *testing.T) {
	h, _ := newTestHandler(t)
	r := setupRouterEvo(h)

	body := map[string]interface{}{
		"user_id":      "user123",
		"device_token": "token-abc",
		"device_type":  "ios",
	}
	jsonBody, _ := json.Marshal(body)

	req := httptest.NewRequest(http.MethodPost, "/api/notifications/subscribe", bytes.NewReader(jsonBody))
	req.Header.Set("Content-Type", "application/json")
	// No X-User-ID
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusUnauthorized, w.Code)
}

func TestSubscribe_InvalidJSON(t *testing.T) {
	h, _ := newTestHandler(t)
	r := setupRouterEvo(h)

	req := httptest.NewRequest(http.MethodPost, "/api/notifications/subscribe", strings.NewReader(`{invalid}`))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("X-User-ID", "user123")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusBadRequest, w.Code)
}

func TestSubscribe_MissingRequiredFields(t *testing.T) {
	h, _ := newTestHandler(t)
	r := setupRouterEvo(h)

	cases := []struct {
		name  string
		body  map[string]interface{}
	}{
		{"missing user_id", map[string]interface{}{"device_token": "tok", "device_type": "ios"}},
		{"missing device_token", map[string]interface{}{"user_id": "u1", "device_type": "ios"}},
		{"missing device_type", map[string]interface{}{"user_id": "u1", "device_token": "tok"}},
	}

	for _, tc := range cases {
		t.Run(tc.name, func(t *testing.T) {
			jsonBody, _ := json.Marshal(tc.body)
			req := httptest.NewRequest(http.MethodPost, "/api/notifications/subscribe", bytes.NewReader(jsonBody))
			req.Header.Set("Content-Type", "application/json")
			req.Header.Set("X-User-ID", "user123")
			w := httptest.NewRecorder()
			r.ServeHTTP(w, req)
			assert.Equal(t, http.StatusBadRequest, w.Code)
		})
	}
}

// ==================== Unsubscribe Tests ====================

func TestUnsubscribe_Success(t *testing.T) {
	h, svc := newTestHandler(t)
	r := setupRouterEvo(h)

	sub, _ := svc.Subscribe(context.Background(), "user123", "token-abc", "ios", "")
	subID := sub.ID

	req := httptest.NewRequest(http.MethodDelete, "/api/notifications/unsubscribe/"+subID, nil)
	req.Header.Set("X-User-ID", "user123")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
	var resp map[string]interface{}
	require.NoError(t, json.Unmarshal(w.Body.Bytes(), &resp))
	assert.Equal(t, subID, resp["id"])
	assert.False(t, resp["active"].(bool))
}

func TestUnsubscribe_NotFound(t *testing.T) {
	h, _ := newTestHandler(t)
	r := setupRouterEvo(h)

	req := httptest.NewRequest(http.MethodDelete, "/api/notifications/unsubscribe/nonexistent", nil)
	req.Header.Set("X-User-ID", "user123")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusNotFound, w.Code)
	var resp map[string]string
	require.NoError(t, json.Unmarshal(w.Body.Bytes(), &resp))
	assert.Contains(t, resp["error"], "not found")
}

func TestUnsubscribe_MissingID(t *testing.T) {
	h, _ := newTestHandler(t)

	req := httptest.NewRequest(http.MethodDelete, "/api/notifications/unsubscribe/", nil)
	req.Header.Set("X-User-ID", "user123")
	w := httptest.NewRecorder()
	h.Unsubscribe(w, req)

	assert.Equal(t, http.StatusBadRequest, w.Code)
}

func TestUnsubscribe_MissingAuth(t *testing.T) {
	h, _ := newTestHandler(t)
	r := setupRouterEvo(h)

	// Even if subscription ID exists, auth required
	req := httptest.NewRequest(http.MethodDelete, "/api/notifications/unsubscribe/someid", nil)
	// No X-User-ID
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusUnauthorized, w.Code)
}

// ==================== GetUserSubscriptions Tests ====================

func TestGetUserSubscriptions_Success(t *testing.T) {
	h, svc := newTestHandler(t)
	r := setupRouterEvo(h)

	// Create subscriptions for user123
	svc.Subscribe(context.Background(), "user123", "token1", "ios", "")
	svc.Subscribe(context.Background(), "user123", "token2", "android", "")

	req := httptest.NewRequest(http.MethodGet, "/api/notifications/subscriptions?user_id=user123", nil)
	req.Header.Set("X-User-ID", "user123")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
	var resp map[string]interface{}
	require.NoError(t, json.Unmarshal(w.Body.Bytes(), &resp))
	subs, ok := resp["subscriptions"].([]interface{})
	require.True(t, ok)
	assert.Len(t, subs, 2)
}

func TestGetUserSubscriptions_Empty(t *testing.T) {
	h, _ := newTestHandler(t)
	r := setupRouterEvo(h)

	// No subscriptions

	req := httptest.NewRequest(http.MethodGet, "/api/notifications/subscriptions?user_id=unknown", nil)
	req.Header.Set("X-User-ID", "user123")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
	var resp struct {
		Subscriptions []*service.DeviceSubscription `json:"subscriptions"`
		Total         int                         `json:"total"`
	}
	require.NoError(t, json.Unmarshal(w.Body.Bytes(), &resp))
	assert.Len(t, resp.Subscriptions, 0)
	assert.Equal(t, 0, resp.Total)
}

func TestGetUserSubscriptions_MissingUserID(t *testing.T) {
	h, _ := newTestHandler(t)
	r := setupRouterEvo(h)

	req := httptest.NewRequest(http.MethodGet, "/api/notifications/subscriptions", nil)
	req.Header.Set("X-User-ID", "user123")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusBadRequest, w.Code)
}

func TestGetUserSubscriptions_MissingAuth(t *testing.T) {
	h, _ := newTestHandler(t)
	r := setupRouterEvo(h)

	req := httptest.NewRequest(http.MethodGet, "/api/notifications/subscriptions?user_id=user123", nil)
	// No X-User-ID
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusUnauthorized, w.Code)
}

// ==================== SendNotification Tests ====================

func TestSendNotification_Success(t *testing.T) {
	h, _ := newTestHandler(t)
	r := setupRouterEvo(h)

	body := map[string]interface{}{
		"user_id": "user123",
		"title":   "Hello",
		"body":    "World",
	}
	jsonBody, _ := json.Marshal(body)

	req := httptest.NewRequest(http.MethodPost, "/api/notifications/send", bytes.NewReader(jsonBody))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("X-User-ID", "user123")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
	var resp map[string]interface{}
	require.NoError(t, json.Unmarshal(w.Body.Bytes(), &resp))
	assert.Equal(t, "Hello", resp["title"])
	assert.Equal(t, "World", resp["body"])
	assert.Equal(t, "user123", resp["user_id"])
	assert.Equal(t, "sent", resp["status"])
}

func TestSendNotification_MissingFields(t *testing.T) {
	h, _ := newTestHandler(t)
	r := setupRouterEvo(h)

	cases := []struct {
		name string
		body map[string]interface{}
	}{
		{"missing user_id", map[string]interface{}{"title": "T", "body": "B"}},
		{"missing title", map[string]interface{}{"user_id": "u1", "body": "B"}},
		{"missing body", map[string]interface{}{"user_id": "u1", "title": "T"}},
	}

	for _, tc := range cases {
		t.Run(tc.name, func(t *testing.T) {
			jsonBody, _ := json.Marshal(tc.body)
			req := httptest.NewRequest(http.MethodPost, "/api/notifications/send", bytes.NewReader(jsonBody))
			req.Header.Set("Content-Type", "application/json")
			req.Header.Set("X-User-ID", "user123")
			w := httptest.NewRecorder()
			r.ServeHTTP(w, req)
			assert.Equal(t, http.StatusBadRequest, w.Code)
		})
	}
}

func TestSendNotification_InvalidJSON(t *testing.T) {
	h, _ := newTestHandler(t)
	r := setupRouterEvo(h)

	req := httptest.NewRequest(http.MethodPost, "/api/notifications/send", strings.NewReader(`{bad}`))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("X-User-ID", "user123")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusBadRequest, w.Code)
}

func TestSendNotification_MissingAuth(t *testing.T) {
	h, _ := newTestHandler(t)
	r := setupRouterEvo(h)

	body := map[string]interface{}{"user_id": "u1", "title": "T", "body": "B"}
	jsonBody, _ := json.Marshal(body)
	req := httptest.NewRequest(http.MethodPost, "/api/notifications/send", bytes.NewReader(jsonBody))
	req.Header.Set("Content-Type", "application/json")
	// No X-User-ID
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusUnauthorized, w.Code)
}

// ==================== SendBroadcast Tests ====================

func TestSendBroadcast_Success(t *testing.T) {
	h, _ := newTestHandler(t)
	r := setupRouterEvo(h)

	body := map[string]interface{}{
		"user_ids": []string{"user1", "user2"},
		"title":    "Broadcast",
		"body":     "Important",
	}
	jsonBody, _ := json.Marshal(body)

	req := httptest.NewRequest(http.MethodPost, "/api/notifications/broadcast", bytes.NewReader(jsonBody))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("X-User-ID", "admin")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
	var resp map[string]interface{}
	require.NoError(t, json.Unmarshal(w.Body.Bytes(), &resp))
	notifs, ok := resp["notifications"].([]interface{})
	require.True(t, ok)
	assert.Len(t, notifs, 2)
}

func TestSendBroadcast_EmptyUserIDs(t *testing.T) {
	h, _ := newTestHandler(t)
	r := setupRouterEvo(h)

	body := map[string]interface{}{
		"user_ids": []string{},
		"title":    "Broadcast",
		"body":     "Important",
	}
	jsonBody, _ := json.Marshal(body)

	req := httptest.NewRequest(http.MethodPost, "/api/notifications/broadcast", bytes.NewReader(jsonBody))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("X-User-ID", "admin")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusBadRequest, w.Code)
}

func TestSendBroadcast_MissingTitleBody(t *testing.T) {
	h, _ := newTestHandler(t)
	r := setupRouterEvo(h)

	body := map[string]interface{}{
		"user_ids": []string{"user1"},
		"title":    "",
		"body":     "",
	}
	jsonBody, _ := json.Marshal(body)

	req := httptest.NewRequest(http.MethodPost, "/api/notifications/broadcast", bytes.NewReader(jsonBody))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("X-User-ID", "admin")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusBadRequest, w.Code)
}

func TestSendBroadcast_InvalidJSON(t *testing.T) {
	h, _ := newTestHandler(t)
	r := setupRouterEvo(h)

	req := httptest.NewRequest(http.MethodPost, "/api/notifications/broadcast", strings.NewReader(`bad`))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("X-User-ID", "admin")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusBadRequest, w.Code)
}

func TestSendBroadcast_MissingAuth(t *testing.T) {
	h, _ := newTestHandler(t)
	r := setupRouterEvo(h)

	body := map[string]interface{}{
		"user_ids": []string{"user1"},
		"title":    "Broadcast",
		"body":     "Important",
	}
	jsonBody, _ := json.Marshal(body)
	req := httptest.NewRequest(http.MethodPost, "/api/notifications/broadcast", bytes.NewReader(jsonBody))
	req.Header.Set("Content-Type", "application/json")
	// No X-User-ID
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusUnauthorized, w.Code)
}

// ==================== GetUserNotifications Tests ====================

func TestGetUserNotifications_Success(t *testing.T) {
	h, svc := newTestHandler(t)
	r := setupRouter(h)

	// Create notifications for user123
	svc.SendNotification(context.Background(), "user123", "Title1", "Body1", nil)
	svc.SendNotification(context.Background(), "user123", "Title2", "Body2", nil)

	req := httptest.NewRequest(http.MethodGet, "/api/notifications/user?user_id=user123", nil)
	req.Header.Set("X-User-ID", "user123")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
	var resp map[string]interface{}
	require.NoError(t, json.Unmarshal(w.Body.Bytes(), &resp))
	notifs, ok := resp["notifications"].([]interface{})
	require.True(t, ok)
	assert.Len(t, notifs, 2)
}

func TestGetUserNotifications_WithLimit(t *testing.T) {
	h, svc := newTestHandler(t)
	r := setupRouter(h)

	svc.SendNotification(context.Background(), "user123", "T1", "B1", nil)
	svc.SendNotification(context.Background(), "user123", "T2", "B2", nil)
	svc.SendNotification(context.Background(), "user123", "T3", "B3", nil)

	req := httptest.NewRequest(http.MethodGet, "/api/notifications/user?user_id=user123&limit=2", nil)
	req.Header.Set("X-User-ID", "user123")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
	var resp map[string]interface{}
	require.NoError(t, json.Unmarshal(w.Body.Bytes(), &resp))
	notifs, ok := resp["notifications"].([]interface{})
	require.True(t, ok)
	assert.Len(t, notifs, 2)
}

func TestGetUserNotifications_InvalidLimit(t *testing.T) {
	h, svc := newTestHandler(t)
	r := setupRouter(h)

	svc.SendNotification(context.Background(), "user123", "T1", "B1", nil)

	req := httptest.NewRequest(http.MethodGet, "/api/notifications/user?user_id=user123&limit=abc", nil)
	req.Header.Set("X-User-ID", "user123")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
	// Should default to 20, returns all (1)
	var resp map[string]interface{}
	require.NoError(t, json.Unmarshal(w.Body.Bytes(), &resp))
	notifs, ok := resp["notifications"].([]interface{})
	require.True(t, ok)
	assert.Len(t, notifs, 1)
}

func TestGetUserNotifications_MissingUserID(t *testing.T) {
	h, _ := newTestHandler(t)
	r := setupRouterEvo(h)

	req := httptest.NewRequest(http.MethodGet, "/api/notifications/user", nil)
	req.Header.Set("X-User-ID", "user123")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusBadRequest, w.Code)
}

func TestGetUserNotifications_MissingAuth(t *testing.T) {
	h, _ := newTestHandler(t)
	r := setupRouterEvo(h)

	req := httptest.NewRequest(http.MethodGet, "/api/notifications/user?user_id=user123", nil)
	// No X-User-ID
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusUnauthorized, w.Code)
}

// ==================== MarkNotificationRead Tests ====================

func TestMarkNotificationRead_Success(t *testing.T) {
	h, svc := newTestHandler(t)
	r := setupRouter(h)

	// Create a notification
	notif, _ := svc.SendNotification(context.Background(), "user123", "T", "B", nil)
	notifID := notif.ID

	req := httptest.NewRequest(http.MethodPost, "/api/notifications/mark-read/"+notifID, nil)
	req.Header.Set("X-User-ID", "user123")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
	var resp map[string]string
	require.NoError(t, json.Unmarshal(w.Body.Bytes(), &resp))
	assert.Contains(t, resp["message"], "marked as read")
}

func TestMarkNotificationRead_MissingID(t *testing.T) {
	h, _ := newTestHandler(t)
	req := httptest.NewRequest(http.MethodPost, "/api/notifications/mark-read/", nil)
	req.Header.Set("X-User-ID", "user123")
	w := httptest.NewRecorder()
	h.MarkNotificationRead(w, req)

	assert.Equal(t, http.StatusBadRequest, w.Code)
}

func TestMarkNotificationRead_MissingAuth(t *testing.T) {
	h, _ := newTestHandler(t)
	r := setupRouterEvo(h)

	req := httptest.NewRequest(http.MethodPost, "/api/notifications/mark-read/someid", nil)
	// No X-User-ID
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusUnauthorized, w.Code)
}

// ==================== ProcessScheduled Tests ====================

func TestProcessScheduled_Success(t *testing.T) {
	h, _ := newTestHandler(t)
	r := setupRouterEvo(h)

	req := httptest.NewRequest(http.MethodPost, "/api/notifications/process-scheduled", nil)
	req.Header.Set("X-User-ID", "admin")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
	var resp map[string]string
	require.NoError(t, json.Unmarshal(w.Body.Bytes(), &resp))
	assert.Contains(t, resp["message"], "processed successfully")
}

func TestProcessScheduled_MissingAuth(t *testing.T) {
	h, _ := newTestHandler(t)
	r := setupRouterEvo(h)

	req := httptest.NewRequest(http.MethodPost, "/api/notifications/process-scheduled", nil)
	// No X-User-ID
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusUnauthorized, w.Code)
}

// ==================== Method Not Allowed ====================

func TestSubscribe_WrongMethod(t *testing.T) {
	h, _ := newTestHandler(t)
	req := httptest.NewRequest(http.MethodGet, "/api/notifications/subscribe", nil)
	req.Header.Set("X-User-ID", "user123")
	w := httptest.NewRecorder()
	h.Subscribe(w, req)

	assert.Equal(t, http.StatusMethodNotAllowed, w.Code)
}

// ==================== Edge Cases ====================

func TestGetUserNotifications_StatusFilter(t *testing.T) {
	h, svc := newTestHandler(t)
	r := setupRouter(h)

	// Create sent notifications (all are sent immediately now)
	svc.SendNotification(context.Background(), "user123", "T1", "B1", nil)
	svc.SendNotification(context.Background(), "user123", "T2", "B2", nil)

	// Query with status=sent (all notifications are sent immediately now)
	req := httptest.NewRequest(http.MethodGet, "/api/notifications/user?user_id=user123&status=sent", nil)
	req.Header.Set("X-User-ID", "user123")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
	// Should return only sent ones
	var resp map[string]interface{}
	require.NoError(t, json.Unmarshal(w.Body.Bytes(), &resp))
	notifs, ok := resp["notifications"].([]interface{})
	require.True(t, ok)
	// Should find both sent notifications
	assert.Len(t, notifs, 2)
}

func TestGetUserSubscriptions_InactiveFilter(t *testing.T) {
	h, svc := newTestHandler(t)
	r := setupRouter(h)

	sub1, _ := svc.Subscribe(context.Background(), "user123", "token1", "ios", "")
	_ = sub1
	// Unsubscribe one
	svc.Unsubscribe(context.Background(), sub1.ID)

	req := httptest.NewRequest(http.MethodGet, "/api/notifications/subscriptions?user_id=user123", nil)
	req.Header.Set("X-User-ID", "user123")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
	var resp struct {
		Subscriptions []*service.DeviceSubscription `json:"subscriptions"`
		Total         int                         `json:"total"`
	}
	require.NoError(t, json.Unmarshal(w.Body.Bytes(), &resp))
	// Inactive ones should be filtered out by service's GetUserSubscriptions
	assert.Len(t, resp.Subscriptions, 0)
	assert.Equal(t, 0, resp.Total)
}

// TestUnsubscribe_AdminAllowed? Actually Unsubscribe doesn't require admin; any authenticated user can unsubscribe any subscription? That's current behavior. We can test that any user can call Unsubscribe without admin check. Already covered.
