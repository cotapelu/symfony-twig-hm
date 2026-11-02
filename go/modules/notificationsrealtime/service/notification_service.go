package service

import (
	"encoding/json"
	"fmt"
	"os"
	"path/filepath"
	"sync"
	"time"

	"community.local/modules/notificationsrealtime/entity"
)

type NotificationService struct {
	mu            sync.RWMutex
	notifications map[string]entity.Notification
	subscriptions map[string]string
	pushTokens    map[string]entity.PushToken
	nextID        int
	etsFile       string
}

func NewNotificationService(etsFile string) *NotificationService {
	s := &NotificationService{
		notifications: make(map[string]entity.Notification),
		subscriptions: make(map[string]string),
		pushTokens:    make(map[string]entity.PushToken),
		nextID:        1,
		etsFile:       etsFile,
	}
	s.ensureDataDirectory(etsFile)
	s.restoreFromETS()
	return s
}

func (s *NotificationService) ensureDataDirectory(filePath string) {
	dir := filepath.Dir(filePath)
	if dir != "" && dir != "." {
		os.MkdirAll(dir, 0755)
	}
}

func (s *NotificationService) restoreFromETS() {
	data, err := os.ReadFile(s.etsFile)
	if err != nil {
		if os.IsNotExist(err) {
			return
		}
		return
	}

	var saved struct {
		Notifications map[string]entity.Notification `json:"notifications"`
		Subscriptions map[string]string              `json:"subscriptions"`
		PushTokens    map[string]entity.PushToken    `json:"push_tokens"`
		NextID        int                            `json:"next_id"`
	}

	if err := json.Unmarshal(data, &saved); err != nil {
		return
	}

	s.notifications = saved.Notifications
	s.subscriptions = saved.Subscriptions
	s.pushTokens = saved.PushTokens
	s.nextID = saved.NextID
}

func (s *NotificationService) persistToETS() {
	data, _ := json.MarshalIndent(map[string]interface{}{
		"notifications": s.notifications,
		"subscriptions": s.subscriptions,
		"push_tokens":   s.pushTokens,
		"next_id":       s.nextID,
	}, "", "  ")
	os.WriteFile(s.etsFile, data, 0644)
}

func (s *NotificationService) SendNotification(userID, title, body, notificationType string, data map[string]interface{}) entity.Notification {
	s.mu.Lock()
	defer s.mu.Unlock()

	notificationID := fmt.Sprintf("notif_%d", s.nextID)
	s.nextID++

	now := time.Now()
	notification := entity.Notification{
		ID:     notificationID,
		UserID: userID,
		Title:  title,
		Body:   body,
		Type:   notificationType,
		Data:   data,
		Read:   false,
		SentAt: now,
	}

	s.notifications[notificationID] = notification
	s.subscriptions[userID] = notificationID

	return notification
}

func (s *NotificationService) SendBroadcast(title, body, notificationType string, data map[string]interface{}) entity.Notification {
	s.mu.Lock()
	defer s.mu.Unlock()

	notificationID := fmt.Sprintf("notif_%d", s.nextID)
	s.nextID++

	now := time.Now()
	notification := entity.Notification{
		ID:     notificationID,
		UserID: "broadcast",
		Title:  title,
		Body:   body,
		Type:   notificationType,
		Data:   data,
		Read:   false,
		SentAt: now,
	}

	s.notifications[notificationID] = notification
	return notification
}

func (s *NotificationService) Subscribe(userID, channel string) entity.Subscription {
	s.mu.Lock()
	defer s.mu.Unlock()

	s.subscriptions[userID] = channel

	return entity.Subscription{
		UserID:       userID,
		Channel:      channel,
		SubscribedAt: time.Now(),
	}
}

func (s *NotificationService) Unsubscribe(userID, channel string) error {
	s.mu.Lock()
	defer s.mu.Unlock()

	delete(s.subscriptions, userID)
	return nil
}

func (s *NotificationService) RegisterPushToken(userID, token, platform string) entity.PushToken {
	s.mu.Lock()
	defer s.mu.Unlock()

	pushToken := entity.PushToken{
		UserID:       userID,
		Token:        token,
		Platform:     platform,
		RegisteredAt: time.Now(),
	}

	s.pushTokens[token] = pushToken
	return pushToken
}

func (s *NotificationService) GetNotifications(userID string, limit int) []entity.Notification {
	s.mu.RLock()
	defer s.mu.RUnlock()

	var userNotifications []entity.Notification
	for _, notif := range s.notifications {
		if notif.UserID == userID || notif.UserID == "broadcast" {
			userNotifications = append(userNotifications, notif)
		}
	}

	if limit > 0 && limit < len(userNotifications) {
		return userNotifications[:limit]
	}
	return userNotifications
}

func (s *NotificationService) MarkRead(notificationID string) (*entity.Notification, error) {
	s.mu.Lock()
	defer s.mu.Unlock()

	notif, exists := s.notifications[notificationID]
	if !exists {
		return nil, nil
	}

	now := time.Now()
	notif.Read = true
	notif.ReadAt = &now
	s.notifications[notificationID] = notif

	return &notif, nil
}

func (s *NotificationService) MarkAllRead(userID string) error {
	s.mu.Lock()
	defer s.mu.Unlock()

	now := time.Now()
	for key, notif := range s.notifications {
		if notif.UserID == userID && !notif.Read {
			notif.Read = true
			notif.ReadAt = &now
			s.notifications[key] = notif
		}
	}

	return nil
}

func (s *NotificationService) GetUnreadCount(userID string) int {
	s.mu.RLock()
	defer s.mu.RUnlock()

	count := 0
	for _, notif := range s.notifications {
		if notif.UserID == userID && !notif.Read {
			count++
		}
	}

	return count
}

func (s *NotificationService) DeleteNotification(notificationID string) error {
	s.mu.Lock()
	defer s.mu.Unlock()

	delete(s.notifications, notificationID)
	return nil
}

func (s *NotificationService) GetNotification(notificationID string) (*entity.Notification, error) {
	s.mu.RLock()
	defer s.mu.RUnlock()

	notif, exists := s.notifications[notificationID]
	if !exists {
		return nil, fmt.Errorf("notification not found")
	}
	return &notif, nil
}

func (s *NotificationService) ValidateToken(token string) (string, error) {
	s.mu.RLock()
	defer s.mu.RUnlock()

	for userID, t := range s.subscriptions {
		if t == token {
			return userID, nil
		}
	}
	return "", fmt.Errorf("invalid token")
}

func (s *NotificationService) Terminate() {
	s.persistToETS()
}
