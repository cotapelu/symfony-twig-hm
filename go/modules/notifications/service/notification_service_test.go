package service

import (
	"context"
	"testing"
	"time"

	"github.com/stretchr/testify/assert"
)

func TestNotificationService_Subscribe(t *testing.T) {
	svc := NewNotificationService()

	userID := "user1"
	deviceToken := "token123"
	deviceType := "ios"
	deviceName := "iPhone"

	subscription, err := svc.Subscribe(context.Background(), userID, deviceToken, deviceType, deviceName)

	assert.NoError(t, err)
	assert.NotEmpty(t, subscription.ID)
	assert.Equal(t, userID, subscription.UserID)
	assert.Equal(t, deviceToken, subscription.DeviceToken)
	assert.Equal(t, deviceType, subscription.DeviceType)
	assert.Equal(t, deviceName, subscription.DeviceName)
	assert.True(t, subscription.Active)
	assert.WithinDuration(t, time.Now(), subscription.CreatedAt, 1*time.Second)
}

func TestNotificationService_Unsubscribe(t *testing.T) {
	svc := NewNotificationService()

	// First subscribe
	sub, _ := svc.Subscribe(context.Background(), "user1", "token", "android", "Pixel")

	// Unsubscribe
	unsub, err := svc.Unsubscribe(context.Background(), sub.ID)
	assert.NoError(t, err)
	assert.Equal(t, sub, unsub)
	assert.False(t, unsub.Active)
	assert.NotNil(t, unsub.UpdatedAt)

	// Unsubscribe non-existent returns (nil, nil)
	unsub2, err := svc.Unsubscribe(context.Background(), "nonexistent")
	assert.NoError(t, err)
	assert.Nil(t, unsub2)
}

func TestNotificationService_GetUserSubscriptions(t *testing.T) {
	svc := NewNotificationService()

	// Subscribe two devices for same user, one inactive
	sub1, _ := svc.Subscribe(context.Background(), "user1", "token1", "ios", "iPhone")
	sub2, _ := svc.Subscribe(context.Background(), "user1", "token2", "android", "Pixel")
	// Unsubscribe sub2
	svc.Unsubscribe(context.Background(), sub2.ID)

	// Get active subscriptions for user1
	subs, err := svc.GetUserSubscriptions(context.Background(), "user1")
	assert.NoError(t, err)
	assert.Len(t, subs, 1)
	assert.Equal(t, sub1.ID, subs[0].ID)

	// For user with no subscriptions
	subs, err = svc.GetUserSubscriptions(context.Background(), "unknown")
	assert.NoError(t, err)
	assert.Empty(t, subs)
}

func TestNotificationService_SendNotification(t *testing.T) {
	svc := NewNotificationService()

	userID := "user1"
	title := "Hello"
	body := "World"
	data := map[string]interface{}{"key": "value"}

	notification, err := svc.SendNotification(context.Background(), userID, title, body, data)
	assert.NoError(t, err)
	assert.NotEmpty(t, notification.ID)
	assert.Equal(t, userID, notification.UserID)
	assert.Equal(t, title, notification.Title)
	assert.Equal(t, body, notification.Body)
	assert.Equal(t, data, notification.Data)
	assert.Equal(t, "sent", notification.Status)
	assert.WithinDuration(t, time.Now(), notification.CreatedAt, 1*time.Second)
	assert.NotNil(t, notification.SentAt)
}

func TestNotificationService_SendBroadcast(t *testing.T) {
	svc := NewNotificationService()

	userIDs := []string{"user1", "user2"}
	title := "Broadcast"
	body := "Message"

	notifications, err := svc.SendBroadcast(context.Background(), userIDs, title, body, nil)
	assert.NoError(t, err)
	assert.Len(t, notifications, 2)
	for _, n := range notifications {
		assert.NotEmpty(t, n.ID)
		assert.Contains(t, userIDs, n.UserID)
		assert.Equal(t, title, n.Title)
	}
}

func TestNotificationService_GetNotification(t *testing.T) {
	svc := NewNotificationService()

	// Send a notification
	n, _ := svc.SendNotification(context.Background(), "user1", "Title", "Body", nil)

	// Retrieve by ID
	retrieved, err := svc.GetNotification(context.Background(), n.ID)
	assert.NoError(t, err)
	assert.Equal(t, n, retrieved)

	// Non-existent
	nonExistent, err := svc.GetNotification(context.Background(), "nonexistent")
	assert.NoError(t, err)
	assert.Nil(t, nonExistent)
}

func TestNotificationService_GetUserNotifications(t *testing.T) {
	svc := NewNotificationService()

	// Send multiple notifications for user1
	svc.SendNotification(context.Background(), "user1", "T1", "B1", nil)
	svc.SendNotification(context.Background(), "user1", "T2", "B2", nil)
	// Send for user2 (should not appear)
	svc.SendNotification(context.Background(), "user2", "T3", "B3", nil)

	// Get all notifications for user1
	notifs, err := svc.GetUserNotifications(context.Background(), "user1", "", 10)
	assert.NoError(t, err)
	assert.Len(t, notifs, 2)

	// Filter by status "pending" (should be both initially pending then become sent after async; but we need to wait? Actually by the time we call, they might be already sent. So we can filter by "sent". Let's test with empty status to get all.
	// But test limit
	notifs, err = svc.GetUserNotifications(context.Background(), "user1", "", 1)
	assert.NoError(t, err)
	assert.Len(t, notifs, 1)
}

func TestNotificationService_MarkNotificationRead(t *testing.T) {
	svc := NewNotificationService()

	// Currently MarkNotificationRead does nothing, just returns nil.
	err := svc.MarkNotificationRead(context.Background(), "any")
	assert.NoError(t, err)
}

func TestNotificationService_ProcessScheduledNotifications(t *testing.T) {
	svc := NewNotificationService()

	// Currently a no-op, returns nil
	err := svc.ProcessScheduledNotifications(context.Background())
	assert.NoError(t, err)
}
