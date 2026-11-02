package service

import (
	"testing"
	"time"

	"github.com/stretchr/testify/assert"

	"community.local/modules/notificationsrealtime/entity"
)

// hasNotificationWithTitle returns true if any notification in the list has the given title.
func hasNotificationWithTitle(notifs []entity.Notification, title string) bool {
	for _, n := range notifs {
		if n.Title == title {
			return true
		}
	}
	return false
}

func TestNotificationService_Subscribe(t *testing.T) {
	svc := NewNotificationService("")

	userID := "user1"
	channel := "email"

	sub := svc.Subscribe(userID, channel)
	assert.Equal(t, userID, sub.UserID)
	assert.Equal(t, channel, sub.Channel)
	assert.WithinDuration(t, time.Now(), sub.SubscribedAt, 1*time.Second)
}

func TestNotificationService_Unsubscribe(t *testing.T) {
	svc := NewNotificationService("")

	// Subscribe first
	svc.Subscribe("user1", "email")

	err := svc.Unsubscribe("user1", "email")
	assert.NoError(t, err)

	// Unsubscribe non-existent returns nil error
	err = svc.Unsubscribe("unknown", "email")
	assert.NoError(t, err)
}

func TestNotificationService_RegisterPushToken(t *testing.T) {
	svc := NewNotificationService("")

	userID := "user1"
	token := "token123"
	platform := "ios"

	push := svc.RegisterPushToken(userID, token, platform)
	assert.Equal(t, userID, push.UserID)
	assert.Equal(t, token, push.Token)
	assert.Equal(t, platform, push.Platform)
	assert.WithinDuration(t, time.Now(), push.RegisteredAt, 1*time.Second)
}

func TestNotificationService_SendNotification(t *testing.T) {
	svc := NewNotificationService("")

	userID := "user1"
	title := "Hello"
	body := "World"
	nType := "info"
	data := map[string]interface{}{"key": "value"}

	notif := svc.SendNotification(userID, title, body, nType, data)
	assert.NotEmpty(t, notif.ID)
	assert.Equal(t, userID, notif.UserID)
	assert.Equal(t, title, notif.Title)
	assert.Equal(t, body, notif.Body)
	assert.Equal(t, nType, notif.Type)
	assert.Equal(t, data, notif.Data)
	assert.False(t, notif.Read)
	assert.NotZero(t, notif.SentAt)
}

func TestNotificationService_SendBroadcast(t *testing.T) {
	svc := NewNotificationService("")

	title := "Broadcast"
	body := "Message to all"
	nType := "broadcast"

	notif := svc.SendBroadcast(title, body, nType, nil)
	assert.NotEmpty(t, notif.ID)
	assert.Equal(t, "broadcast", notif.UserID)
	assert.Equal(t, title, notif.Title)
	assert.Equal(t, body, notif.Body)
}

func TestNotificationService_GetNotifications(t *testing.T) {
	svc := NewNotificationService("")

	// Send notifications for user1 and broadcast
	svc.SendNotification("user1", "T1", "B1", "info", nil)
	svc.SendNotification("user1", "T2", "B2", "info", nil)
	svc.SendBroadcast("BroadcastTitle", "BroadcastBody", "broadcast", nil)

	userNotifs := svc.GetNotifications("user1", 10)
	assert.True(t, hasNotificationWithTitle(userNotifs, "T1"))
	assert.True(t, hasNotificationWithTitle(userNotifs, "T2"))
	assert.True(t, hasNotificationWithTitle(userNotifs, "BroadcastTitle"))

	// Limit test
	limited := svc.GetNotifications("user1", 1)
	assert.Len(t, limited, 1)
}

func TestNotificationService_MarkRead(t *testing.T) {
	svc := NewNotificationService("")

	notif := svc.SendNotification("user1", "T", "B", "info", nil)
	assert.False(t, notif.Read)

	retrieved, err := svc.MarkRead(notif.ID)
	assert.NoError(t, err)
	assert.True(t, retrieved.Read)
	assert.NotNil(t, retrieved.ReadAt)

	// Non-existent returns nil, nil
	notif2, err := svc.MarkRead("nonexistent")
	assert.NoError(t, err)
	assert.Nil(t, notif2)
}

func TestNotificationService_MarkAllRead(t *testing.T) {
	svc := NewNotificationService("")

	svc.SendNotification("user1", "T1", "B1", "info", nil)
	svc.SendNotification("user1", "T2", "B2", "info", nil)
	// Also send for another user
	svc.SendNotification("user2", "T3", "B3", "info", nil)

	err := svc.MarkAllRead("user1")
	assert.NoError(t, err)

	notifs := svc.GetNotifications("user1", 10)
	for _, n := range notifs {
		assert.True(t, n.Read, "notification %s should be read", n.ID)
	}

	// Other user's notifications should remain unread
	notifs2 := svc.GetNotifications("user2", 10)
	for _, n := range notifs2 {
		assert.False(t, n.Read)
	}
}

func TestNotificationService_GetUnreadCount(t *testing.T) {
	svc := NewNotificationService("")

	svc.SendNotification("user1", "T1", "B1", "info", nil) // unread
	svc.SendNotification("user1", "T2", "B2", "info", nil) // unread
	n3 := svc.SendNotification("user1", "T3", "B3", "info", nil)
	// Mark third as read
	svc.MarkRead(n3.ID)

	count := svc.GetUnreadCount("user1")
	assert.Equal(t, 2, count)
}
