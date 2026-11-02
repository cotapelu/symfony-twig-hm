package handler

import (
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"strings"
	"testing"

	"community.local/modules/notificationsrealtime/service"
	"github.com/gin-gonic/gin"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"
)

// ==================== Helpers ====================

func createService(t *testing.T) *service.NotificationService {
	tmpDir := t.TempDir()
	etsFile := tmpDir + "/notifications.ets"
	return service.NewNotificationService(etsFile)
}

func newTestRouter(h *NotificationHandler) *gin.Engine {
	gin.SetMode(gin.TestMode)
	router := gin.New()
	h.RegisterRoutes(router)
	return router
}

// subscribeUser creates subscription for user and returns token (channel string)
func subscribeUser(t *testing.T, svc *service.NotificationService, userID, channel string) string {
	// Use userID as channel for unique token
	svc.Subscribe(userID, userID)
	return userID
}

// ==================== Tests ====================

func TestNotificationHandler_SendNotification(t *testing.T) {
	svc := createService(t)
	h := NewNotificationHandler(svc)
	router := newTestRouter(h)

	t.Run("success", func(t *testing.T) {
		token := subscribeUser(t, svc, "user1", "user1")
		body := `{"user_id":"user1","title":"Hello","body":"World","type":"info"}`
		req := httptest.NewRequest("POST", "/api/notifications/send", strings.NewReader(body))
		req.Header.Set("Authorization", "Bearer "+token)
		req.Header.Set("Content-Type", "application/json")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusCreated, w.Code)
		var resp map[string]interface{}
		require.NoError(t, json.NewDecoder(w.Body).Decode(&resp))
		assert.Equal(t, "notification sent", resp["status"])
	})

	t.Run("unauthorized", func(t *testing.T) {
		body := `{"user_id":"user1","title":"Hello","body":"World","type":"info"}`
		req := httptest.NewRequest("POST", "/api/notifications/send", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusUnauthorized, w.Code)
	})

	t.Run("invalid_token", func(t *testing.T) {
		body := `{"user_id":"user1","title":"Hello","body":"World","type":"info"}`
		req := httptest.NewRequest("POST", "/api/notifications/send", strings.NewReader(body))
		req.Header.Set("Authorization", "Bearer wrongtoken")
		req.Header.Set("Content-Type", "application/json")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusUnauthorized, w.Code)
	})

	t.Run("missing_required_fields", func(t *testing.T) {
		token := subscribeUser(t, svc, "user1", "user1")
		body := `{"user_id":"user1"}`
		req := httptest.NewRequest("POST", "/api/notifications/send", strings.NewReader(body))
		req.Header.Set("Authorization", "Bearer "+token)
		req.Header.Set("Content-Type", "application/json")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusBadRequest, w.Code)
	})

	t.Run("invalid_title_length", func(t *testing.T) {
		token := subscribeUser(t, svc, "user1", "user1")
		body := `{"user_id":"user1","title":"","body":"World","type":"info"}`
		req := httptest.NewRequest("POST", "/api/notifications/send", strings.NewReader(body))
		req.Header.Set("Authorization", "Bearer "+token)
		req.Header.Set("Content-Type", "application/json")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusBadRequest, w.Code)
	})

	t.Run("invalid_json", func(t *testing.T) {
		token := subscribeUser(t, svc, "user1", "user1")
		body := `{"user_id":"user1","title": bad}`
		req := httptest.NewRequest("POST", "/api/notifications/send", strings.NewReader(body))
		req.Header.Set("Authorization", "Bearer "+token)
		req.Header.Set("Content-Type", "application/json")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusBadRequest, w.Code)
	})
}

func TestNotificationHandler_SendBroadcast(t *testing.T) {
	svc := createService(t)
	h := NewNotificationHandler(svc)
	router := newTestRouter(h)

	token := subscribeUser(t, svc, "admin", "web")

	t.Run("success", func(t *testing.T) {
		body := `{"title":"Broadcast","body":"Hello all","type":"announcement"}`
		req := httptest.NewRequest("POST", "/api/notifications/broadcast", strings.NewReader(body))
		req.Header.Set("Authorization", "Bearer "+token)
		req.Header.Set("Content-Type", "application/json")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusCreated, w.Code)
	})

	t.Run("unauthorized", func(t *testing.T) {
		body := `{"title":"Broadcast","body":"Hello all","type":"announcement"}`
		req := httptest.NewRequest("POST", "/api/notifications/broadcast", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusUnauthorized, w.Code)
	})

	t.Run("missing_required_fields", func(t *testing.T) {
		body := `{"title":"Broadcast"}`
		req := httptest.NewRequest("POST", "/api/notifications/broadcast", strings.NewReader(body))
		req.Header.Set("Authorization", "Bearer "+token)
		req.Header.Set("Content-Type", "application/json")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusBadRequest, w.Code)
	})
}

func TestNotificationHandler_GetNotifications(t *testing.T) {
	svc := createService(t)
	h := NewNotificationHandler(svc)
	router := newTestRouter(h)

	// Seed notifications
	svc.SendNotification("user1", "N1", "B1", "info", nil)
	svc.SendNotification("user1", "N2", "B2", "info", nil)

	token := subscribeUser(t, svc, "user1", "user1")

	t.Run("success", func(t *testing.T) {
		req := httptest.NewRequest("GET", "/api/notifications/user/user1?limit=10", nil)
		req.Header.Set("Authorization", "Bearer "+token)
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)
		var resp map[string]interface{}
		require.NoError(t, json.NewDecoder(w.Body).Decode(&resp))
		assert.Equal(t, "user1", resp["user_id"])
		count := int(resp["count"].(float64))
		assert.GreaterOrEqual(t, count, 2)
		notifs := resp["notifications"].([]interface{})
		assert.Len(t, notifs, count)
	})

	t.Run("forbidden_other_user", func(t *testing.T) {
		token2 := subscribeUser(t, svc, "user2", "user2")
		req := httptest.NewRequest("GET", "/api/notifications/user/user1", nil)
		req.Header.Set("Authorization", "Bearer "+token2)
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusForbidden, w.Code)
	})

	t.Run("unauthorized", func(t *testing.T) {
		req := httptest.NewRequest("GET", "/api/notifications/user/user1", nil)
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusUnauthorized, w.Code)
	})
}

func TestNotificationHandler_MarkAllRead(t *testing.T) {
	svc := createService(t)
	h := NewNotificationHandler(svc)
	router := newTestRouter(h)

	svc.SendNotification("user1", "N1", "B1", "info", nil)

	token := subscribeUser(t, svc, "user1", "user1")

	t.Run("success", func(t *testing.T) {
		req := httptest.NewRequest("POST", "/api/notifications/user/user1/read-all", nil)
		req.Header.Set("Authorization", "Bearer "+token)
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)
		var resp map[string]interface{}
		require.NoError(t, json.NewDecoder(w.Body).Decode(&resp))
		assert.Equal(t, "all notifications marked as read", resp["status"])
	})

	t.Run("forbidden_other_user", func(t *testing.T) {
		token2 := subscribeUser(t, svc, "user2", "user2")
		req := httptest.NewRequest("POST", "/api/notifications/user/user1/read-all", nil)
		req.Header.Set("Authorization", "Bearer "+token2)
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusForbidden, w.Code)
	})

	t.Run("unauthorized", func(t *testing.T) {
		req := httptest.NewRequest("POST", "/api/notifications/user/user1/read-all", nil)
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusUnauthorized, w.Code)
	})
}

func TestNotificationHandler_GetUnreadCount(t *testing.T) {
	svc := createService(t)
	h := NewNotificationHandler(svc)
	router := newTestRouter(h)

	svc.SendNotification("user1", "N1", "B1", "info", nil)

	token := subscribeUser(t, svc, "user1", "user1")

	t.Run("success", func(t *testing.T) {
		req := httptest.NewRequest("GET", "/api/notifications/user/user1/unread-count", nil)
		req.Header.Set("Authorization", "Bearer "+token)
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)
		var resp map[string]interface{}
		require.NoError(t, json.NewDecoder(w.Body).Decode(&resp))
		assert.Equal(t, "user1", resp["user_id"])
		count := int(resp["unread_count"].(float64))
		assert.GreaterOrEqual(t, count, 1)
	})

	t.Run("forbidden", func(t *testing.T) {
		token2 := subscribeUser(t, svc, "user2", "user2")
		req := httptest.NewRequest("GET", "/api/notifications/user/user1/unread-count", nil)
		req.Header.Set("Authorization", "Bearer "+token2)
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusForbidden, w.Code)
	})

	t.Run("unauthorized", func(t *testing.T) {
		req := httptest.NewRequest("GET", "/api/notifications/user/user1/unread-count", nil)
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusUnauthorized, w.Code)
	})
}

func TestNotificationHandler_MarkRead(t *testing.T) {
	svc := createService(t)
	h := NewNotificationHandler(svc)
	router := newTestRouter(h)

	svc.SendNotification("user1", "N1", "B1", "info", nil)

	token := subscribeUser(t, svc, "user1", "user1")

	notifs := svc.GetNotifications("user1", 10)
	require.Len(t, notifs, 1)
	notifID := notifs[0].ID

	t.Run("success", func(t *testing.T) {
		url := "/api/notifications/" + notifID + "/read"
		req := httptest.NewRequest("POST", url, nil)
		req.Header.Set("Authorization", "Bearer "+token)
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)
		var resp map[string]interface{}
		require.NoError(t, json.NewDecoder(w.Body).Decode(&resp))
		assert.Equal(t, "notification marked as read", resp["status"])
	})

	t.Run("not_found", func(t *testing.T) {
		req := httptest.NewRequest("POST", "/api/notifications/nonexistent/read", nil)
		req.Header.Set("Authorization", "Bearer "+token)
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusNotFound, w.Code)
	})

	t.Run("forbidden", func(t *testing.T) {
		token2 := subscribeUser(t, svc, "user2", "web")
		req := httptest.NewRequest("POST", "/api/notifications/"+notifID+"/read", nil)
		req.Header.Set("Authorization", "Bearer "+token2)
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusForbidden, w.Code)
	})

	t.Run("unauthorized", func(t *testing.T) {
		req := httptest.NewRequest("POST", "/api/notifications/"+notifID+"/read", nil)
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusUnauthorized, w.Code)
	})
}

func TestNotificationHandler_DeleteNotification(t *testing.T) {
	svc := createService(t)
	h := NewNotificationHandler(svc)
	router := newTestRouter(h)

	t.Run("success", func(t *testing.T) {
		// Create notification for user1 first
		svc.SendNotification("user1", "N1", "B1", "info", nil)
		// Then subscribe to get a valid token (after SendNotification to avoid overwrite)
		token := subscribeUser(t, svc, "user1", "user1")
		notifs := svc.GetNotifications("user1", 10)
		require.Greater(t, len(notifs), 0)
		notifID := notifs[0].ID

		req := httptest.NewRequest("DELETE", "/api/notifications/"+notifID, nil)
		req.Header.Set("Authorization", "Bearer "+token)
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)
		var resp map[string]interface{}
		require.NoError(t, json.NewDecoder(w.Body).Decode(&resp))
		assert.Equal(t, "notification deleted", resp["status"])
	})

	t.Run("not_found", func(t *testing.T) {
		token := subscribeUser(t, svc, "user1", "web")
		req := httptest.NewRequest("DELETE", "/api/notifications/nonexistent", nil)
		req.Header.Set("Authorization", "Bearer "+token)
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusNotFound, w.Code)
	})

	t.Run("forbidden", func(t *testing.T) {
		// Create notification for user1
		svc.SendNotification("user1", "N1", "B1", "info", nil)
		notifs := svc.GetNotifications("user1", 10)
		require.Greater(t, len(notifs), 0)
		notifID := notifs[0].ID

		// token for user2
		token2 := subscribeUser(t, svc, "user2", "web")
		req := httptest.NewRequest("DELETE", "/api/notifications/"+notifID, nil)
		req.Header.Set("Authorization", "Bearer "+token2)
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusForbidden, w.Code)
	})

	t.Run("unauthorized", func(t *testing.T) {
		req := httptest.NewRequest("DELETE", "/api/notifications/somenotif", nil)
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusUnauthorized, w.Code)
	})
}

func TestNotificationHandler_Subscribe(t *testing.T) {
	svc := createService(t)
	h := NewNotificationHandler(svc)
	router := newTestRouter(h)

	token := subscribeUser(t, svc, "admin", "web")

	t.Run("success", func(t *testing.T) {
		body := `{"user_id":"user2","channel":"web"}`
		req := httptest.NewRequest("POST", "/api/notifications/subscribe", strings.NewReader(body))
		req.Header.Set("Authorization", "Bearer "+token)
		req.Header.Set("Content-Type", "application/json")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusCreated, w.Code)
		var resp map[string]interface{}
		require.NoError(t, json.NewDecoder(w.Body).Decode(&resp))
		assert.Equal(t, "subscribed", resp["status"])
	})

	t.Run("unauthorized", func(t *testing.T) {
		body := `{"user_id":"user2","channel":"web"}`
		req := httptest.NewRequest("POST", "/api/notifications/subscribe", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusUnauthorized, w.Code)
	})

	t.Run("missing_required_fields", func(t *testing.T) {
		body := `{"user_id":"user2"}`
		req := httptest.NewRequest("POST", "/api/notifications/subscribe", strings.NewReader(body))
		req.Header.Set("Authorization", "Bearer "+token)
		req.Header.Set("Content-Type", "application/json")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusBadRequest, w.Code)
	})
}

func TestNotificationHandler_Unsubscribe(t *testing.T) {
	svc := createService(t)
	h := NewNotificationHandler(svc)
	router := newTestRouter(h)

	token := subscribeUser(t, svc, "admin", "web")

	t.Run("success", func(t *testing.T) {
		// First subscribe
		svc.Subscribe("user2", "web")
		body := `{"user_id":"user2","channel":"web"}`
		req := httptest.NewRequest("POST", "/api/notifications/unsubscribe", strings.NewReader(body))
		req.Header.Set("Authorization", "Bearer "+token)
		req.Header.Set("Content-Type", "application/json")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)
		var resp map[string]interface{}
		require.NoError(t, json.NewDecoder(w.Body).Decode(&resp))
		assert.Equal(t, "unsubscribed", resp["status"])
	})

	t.Run("unauthorized", func(t *testing.T) {
		body := `{"user_id":"user2","channel":"web"}`
		req := httptest.NewRequest("POST", "/api/notifications/unsubscribe", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusUnauthorized, w.Code)
	})

	t.Run("missing_required_fields", func(t *testing.T) {
		body := `{"user_id":"user2"}`
		req := httptest.NewRequest("POST", "/api/notifications/unsubscribe", strings.NewReader(body))
		req.Header.Set("Authorization", "Bearer "+token)
		req.Header.Set("Content-Type", "application/json")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusBadRequest, w.Code)
	})
}

func TestNotificationHandler_RegisterPushToken(t *testing.T) {
	svc := createService(t)
	h := NewNotificationHandler(svc)
	router := newTestRouter(h)

	token := subscribeUser(t, svc, "user1", "web")

	t.Run("success", func(t *testing.T) {
		body := `{"user_id":"user1","token":"abc123def456","platform":"ios"}`
		req := httptest.NewRequest("POST", "/api/notifications/push-token", strings.NewReader(body))
		req.Header.Set("Authorization", "Bearer "+token)
		req.Header.Set("Content-Type", "application/json")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusCreated, w.Code)
		var resp map[string]interface{}
		require.NoError(t, json.NewDecoder(w.Body).Decode(&resp))
		assert.Equal(t, "push token registered", resp["status"])
	})

	t.Run("unauthorized", func(t *testing.T) {
		body := `{"user_id":"user1","token":"abc123","platform":"ios"}`
		req := httptest.NewRequest("POST", "/api/notifications/push-token", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusUnauthorized, w.Code)
	})

	t.Run("missing_required_fields", func(t *testing.T) {
		body := `{"user_id":"user1"}`
		req := httptest.NewRequest("POST", "/api/notifications/push-token", strings.NewReader(body))
		req.Header.Set("Authorization", "Bearer "+token)
		req.Header.Set("Content-Type", "application/json")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusBadRequest, w.Code)
	})

	t.Run("invalid_platform", func(t *testing.T) {
		body := `{"user_id":"user1","token":"validtoken123","platform":"windows"}`
		req := httptest.NewRequest("POST", "/api/notifications/push-token", strings.NewReader(body))
		req.Header.Set("Authorization", "Bearer "+token)
		req.Header.Set("Content-Type", "application/json")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusBadRequest, w.Code)
	})

	t.Run("invalid_token_length", func(t *testing.T) {
		body := `{"user_id":"user1","token":"short","platform":"android"}`
		req := httptest.NewRequest("POST", "/api/notifications/push-token", strings.NewReader(body))
		req.Header.Set("Authorization", "Bearer "+token)
		req.Header.Set("Content-Type", "application/json")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusBadRequest, w.Code)
	})
}
