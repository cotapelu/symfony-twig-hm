package service

import (
	"context"
	"sync"
	"time"

	"github.com/google/uuid"
)

type Notification struct {
	ID        string                 `json:"id"`
	UserID    string                 `json:"user_id"`
	Title     string                 `json:"title"`
	Body      string                 `json:"body"`
	Data      map[string]interface{} `json:"data,omitempty"`
	Status    string                 `json:"status"`
	CreatedAt time.Time              `json:"created_at"`
	SentAt    *time.Time             `json:"sent_at,omitempty"`
}

type DeviceSubscription struct {
	ID           string    `json:"id"`
	UserID       string    `json:"user_id"`
	DeviceToken  string    `json:"device_token"`
	DeviceType   string    `json:"device_type"`
	DeviceName   string    `json:"device_name,omitempty"`
	Active       bool      `json:"active"`
	CreatedAt    time.Time `json:"created_at"`
	UpdatedAt    time.Time `json:"updated_at"`
}

type NotificationService struct {
	subscriptions map[string]*DeviceSubscription
	notifications map[string]*Notification
	mu           sync.RWMutex
}

func NewNotificationService() *NotificationService {
	return &NotificationService{
		subscriptions: make(map[string]*DeviceSubscription),
		notifications: make(map[string]*Notification),
	}
}

func (s *NotificationService) Subscribe(ctx context.Context, userID, deviceToken, deviceType, deviceName string) (*DeviceSubscription, error) {
	subscription := &DeviceSubscription{
		ID:          uuid.New().String(),
		UserID:      userID,
		DeviceToken: deviceToken,
		DeviceType:  deviceType,
		DeviceName:  deviceName,
		Active:      true,
		CreatedAt:   time.Now(),
		UpdatedAt:   time.Now(),
	}

	s.mu.Lock()
	s.subscriptions[subscription.ID] = subscription
	s.mu.Unlock()
	return subscription, nil
}

func (s *NotificationService) Unsubscribe(ctx context.Context, subscriptionID string) (*DeviceSubscription, error) {
	s.mu.Lock()
	defer s.mu.Unlock()

	subscription, exists := s.subscriptions[subscriptionID]
	if !exists {
		return nil, nil
	}

	subscription.Active = false
	subscription.UpdatedAt = time.Now()
	return subscription, nil
}

func (s *NotificationService) GetUserSubscriptions(ctx context.Context, userID string) ([]*DeviceSubscription, error) {
	s.mu.RLock()
	defer s.mu.RUnlock()

	var subscriptions []*DeviceSubscription
	for _, sub := range s.subscriptions {
		if sub.UserID == userID && sub.Active {
			subscriptions = append(subscriptions, sub)
		}
	}
	return subscriptions, nil
}

func (s *NotificationService) SendNotification(ctx context.Context, userID, title, body string, data map[string]interface{}) (*Notification, error) {
	now := time.Now()
	sentAt := now.Add(100 * time.Millisecond)

	notification := &Notification{
		ID:        uuid.New().String(),
		UserID:    userID,
		Title:     title,
		Body:      body,
		Data:      data,
		Status:    "sent",
		CreatedAt: now,
		SentAt:    &sentAt,
	}

	s.mu.Lock()
	s.notifications[notification.ID] = notification
	s.mu.Unlock()

	return notification, nil
}

func (s *NotificationService) SendBroadcast(ctx context.Context, userIDs []string, title, body string, data map[string]interface{}) ([]*Notification, error) {
	var notifications []*Notification

	for _, userID := range userIDs {
		n, err := s.SendNotification(ctx, userID, title, body, data)
		if err != nil {
			continue
		}
		notifications = append(notifications, n)
	}

	return notifications, nil
}

func (s *NotificationService) GetNotification(ctx context.Context, notificationID string) (*Notification, error) {
	s.mu.RLock()
	defer s.mu.RUnlock()

	notification, exists := s.notifications[notificationID]
	if !exists {
		return nil, nil
	}
	return notification, nil
}

func (s *NotificationService) GetUserNotifications(ctx context.Context, userID string, status string, limit int) ([]*Notification, error) {
	s.mu.RLock()
	defer s.mu.RUnlock()

	var notifications []*Notification
	count := 0

	for _, n := range s.notifications {
		if n.UserID == userID {
			if status == "" || n.Status == status {
				notifications = append(notifications, n)
				count++
				if count >= limit {
					break
				}
			}
		}
	}

	return notifications, nil
}

func (s *NotificationService) MarkNotificationRead(ctx context.Context, notificationID string) error {
	return nil
}

func (s *NotificationService) ProcessScheduledNotifications(ctx context.Context) error {
	return nil
}

func (s *NotificationService) RetryFailedNotifications(ctx context.Context) error {
	return nil
}