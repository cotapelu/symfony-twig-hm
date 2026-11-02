package handler

import (
	"bytes"
	"context"
	"encoding/json"
	"errors"
	"net/http"
	"net/http/httptest"
	"testing"
	"time"

	"github.com/stretchr/testify/assert"
	"community.local/modules/notifications/service"
)

// mockNotificationService is a mock implementation of NotificationService for testing error paths.
type mockNotificationService struct {
	errSubscribe                     error
	errUnsubscribe                   error
	errGetUserSubscriptions          error
	errSendNotification              error
	errSendBroadcast                 error
	errGetUserNotifications          error
	errMarkNotificationRead          error
	errProcessScheduledNotifications error
	errRetryFailedNotifications      error
}

func (m *mockNotificationService) Subscribe(ctx context.Context, userID, deviceToken, deviceType, deviceName string) (*service.DeviceSubscription, error) {
	if m.errSubscribe != nil {
		return nil, m.errSubscribe
	}
	return &service.DeviceSubscription{
		ID:           "sub-123",
		UserID:       userID,
		DeviceToken:  deviceToken,
		DeviceType:   deviceType,
		DeviceName:   deviceName,
		Active:       true,
		CreatedAt:    time.Now(),
		UpdatedAt:    time.Now(),
	}, nil
}

func (m *mockNotificationService) Unsubscribe(ctx context.Context, subscriptionID string) (*service.DeviceSubscription, error) {
	if m.errUnsubscribe != nil {
		return nil, m.errUnsubscribe
	}
	return &service.DeviceSubscription{
		ID:       subscriptionID,
		Active:   false,
		UpdatedAt: time.Now(),
	}, nil
}

func (m *mockNotificationService) GetUserSubscriptions(ctx context.Context, userID string) ([]*service.DeviceSubscription, error) {
	if m.errGetUserSubscriptions != nil {
		return nil, m.errGetUserSubscriptions
	}
	return []*service.DeviceSubscription{
		{ID: "sub-1", UserID: userID, Active: true},
	}, nil
}

func (m *mockNotificationService) SendNotification(ctx context.Context, userID, title, body string, data map[string]interface{}) (*service.Notification, error) {
	if m.errSendNotification != nil {
		return nil, m.errSendNotification
	}
	return &service.Notification{
		ID:        "notif-123",
		UserID:    userID,
		Title:     title,
		Body:      body,
		Data:      data,
		Status:    "pending",
		CreatedAt: time.Now(),
	}, nil
}

func (m *mockNotificationService) SendBroadcast(ctx context.Context, userIDs []string, title, body string, data map[string]interface{}) ([]*service.Notification, error) {
	if m.errSendBroadcast != nil {
		return nil, m.errSendBroadcast
	}
	notifs := make([]*service.Notification, 0, len(userIDs))
	for _, uid := range userIDs {
		notifs = append(notifs, &service.Notification{
			ID:        "broadcast-notif",
			UserID:    uid,
			Title:     title,
			Body:      body,
			Data:      data,
			Status:    "pending",
			CreatedAt: time.Now(),
		})
	}
	return notifs, nil
}

func (m *mockNotificationService) GetUserNotifications(ctx context.Context, userID, status string, limit int) ([]*service.Notification, error) {
	if m.errGetUserNotifications != nil {
		return nil, m.errGetUserNotifications
	}
	return []*service.Notification{
		{ID: "n1", UserID: userID, Title: "Test", Body: "Body", CreatedAt: time.Now()},
	}, nil
}

func (m *mockNotificationService) MarkNotificationRead(ctx context.Context, notificationID string) error {
	if m.errMarkNotificationRead != nil {
		return m.errMarkNotificationRead
	}
	return nil
}

func (m *mockNotificationService) ProcessScheduledNotifications(ctx context.Context) error {
	if m.errProcessScheduledNotifications != nil {
		return m.errProcessScheduledNotifications
	}
	return nil
}

func (m *mockNotificationService) RetryFailedNotifications(ctx context.Context) error {
	if m.errRetryFailedNotifications != nil {
		return m.errRetryFailedNotifications
	}
	return nil
}

func TestSubscribe_ServiceError(t *testing.T) {
	mockSvc := &mockNotificationService{errSubscribe: errors.New("service unavailable")}
	h := NewNotificationHandler(mockSvc)

	payload := `{"user_id":"u1","device_token":"token123","device_type":"mobile"}`
	req := httptest.NewRequest(http.MethodPost, "/api/notifications/subscribe", bytes.NewReader([]byte(payload)))
	req.Header.Set("Content-Type", "application/json")
	w := httptest.NewRecorder()

	h.Subscribe(w, req)
	assert.Equal(t, http.StatusBadRequest, w.Code)
	var resp map[string]string
	json.Unmarshal(w.Body.Bytes(), &resp)
	assert.Contains(t, resp["error"], "service unavailable")
}

func TestUnsubscribe_ServiceError(t *testing.T) {
	mockSvc := &mockNotificationService{errUnsubscribe: errors.New("db error")}
	h := NewNotificationHandler(mockSvc)

	req := httptest.NewRequest(http.MethodDelete, "/api/notifications/unsubscribe/sub123", nil)
	w := httptest.NewRecorder()
	// The handler expects subscriptionID from path; the path is exactly /api/notifications/unsubscribe/:subscriptionId
	// We set URL.Path accordingly: need to set the full path so that code can slice.
	req.URL.Path = "/api/notifications/unsubscribe/sub123"
	h.Unsubscribe(w, req)

	assert.Equal(t, http.StatusInternalServerError, w.Code)
	var resp map[string]string
	json.Unmarshal(w.Body.Bytes(), &resp)
	assert.Contains(t, resp["error"], "db error")
}

func TestGetUserSubscriptions_ServiceError(t *testing.T) {
	mockSvc := &mockNotificationService{errGetUserSubscriptions: errors.New("db down")}
	h := NewNotificationHandler(mockSvc)

	req := httptest.NewRequest(http.MethodGet, "/api/notifications/subscriptions?user_id=u1", nil)
	w := httptest.NewRecorder()
	h.GetUserSubscriptions(w, req)

	assert.Equal(t, http.StatusInternalServerError, w.Code)
}

func TestSendNotification_ServiceError(t *testing.T) {
	mockSvc := &mockNotificationService{errSendNotification: errors.New("send failed")}
	h := NewNotificationHandler(mockSvc)

	payload := `{"user_id":"u1","title":"Hello","body":"World"}`
	req := httptest.NewRequest(http.MethodPost, "/api/notifications/send", bytes.NewReader([]byte(payload)))
	req.Header.Set("Content-Type", "application/json")
	w := httptest.NewRecorder()
	h.SendNotification(w, req)

	assert.Equal(t, http.StatusInternalServerError, w.Code)
}

func TestSendBroadcast_ServiceError(t *testing.T) {
	mockSvc := &mockNotificationService{errSendBroadcast: errors.New("broadcast failed")}
	h := NewNotificationHandler(mockSvc)

	payload := `{"user_ids":["u1","u2"],"title":"Hello","body":"World"}`
	req := httptest.NewRequest(http.MethodPost, "/api/notifications/broadcast", bytes.NewReader([]byte(payload)))
	req.Header.Set("Content-Type", "application/json")
	w := httptest.NewRecorder()
	h.SendBroadcast(w, req)

	assert.Equal(t, http.StatusInternalServerError, w.Code)
}

func TestGetUserNotifications_ServiceError(t *testing.T) {
	mockSvc := &mockNotificationService{errGetUserNotifications: errors.New("query error")}
	h := NewNotificationHandler(mockSvc)

	req := httptest.NewRequest(http.MethodGet, "/api/notifications/user?user_id=u1", nil)
	w := httptest.NewRecorder()
	h.GetUserNotifications(w, req)

	assert.Equal(t, http.StatusInternalServerError, w.Code)
}

func TestMarkNotificationRead_ServiceError(t *testing.T) {
	mockSvc := &mockNotificationService{errMarkNotificationRead: errors.New("mark error")}
	h := NewNotificationHandler(mockSvc)

	req := httptest.NewRequest(http.MethodPost, "/api/notifications/mark-read/notif123", nil)
	// Need to set URL.Path as the full path so slicing works
	req.URL.Path = "/api/notifications/mark-read/notif123"
	w := httptest.NewRecorder()
	h.MarkNotificationRead(w, req)

	assert.Equal(t, http.StatusInternalServerError, w.Code)
}
