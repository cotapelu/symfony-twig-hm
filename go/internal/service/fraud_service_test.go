package service

import (
	"context"
	"errors"
	"testing"
	"time"

	"community.local/internal/models"
)

type mockFraudRepo struct {
	events []*models.FraudEvent
}

func (m *mockFraudRepo) CreateEvent(ctx context.Context, event *models.FraudEvent) error {
	event.ID = "evt-" + time.Now().Format("20060102150405")
	event.CreatedAt = time.Now()
	m.events = append(m.events, event)
	return nil
}
func (m *mockFraudRepo) GetEventsByUser(ctx context.Context, userID string, limit int) ([]*models.FraudEvent, error) {
	var res []*models.FraudEvent
	for _, e := range m.events {
		if e.UserID == userID {
			res = append(res, e)
		}
	}
	return res, nil
}
func (m *mockFraudRepo) MarkProcessed(ctx context.Context, id string) error {
	for _, e := range m.events {
		if e.ID == id {
			e.Processed = true
			return nil
		}
	}
	return errors.New("not found")
}

func TestFraud_CreateEvent(t *testing.T) {
	repo := &mockFraudRepo{}
	svc := NewFraudService(repo)
	ctx := context.Background()
	event, err := svc.CreateEvent(ctx, "user-1", "LOGIN_FAILED", 85.5, "Suspicious activity")
	if err != nil {
		t.Fatalf("CreateEvent failed: %v", err)
	}
	if event.UserID != "user-1" {
		t.Errorf("Wrong user ID")
	}
	if event.Processed {
		t.Errorf("Expected processed false, got true")
	}
}

func TestFraud_GetEventsByUser(t *testing.T) {
	now := time.Now()
	repo := &mockFraudRepo{
		events: []*models.FraudEvent{
			{ID: "e1", UserID: "user-1", EventType: "type1", Processed: false, CreatedAt: now},
			{ID: "e2", UserID: "user-1", EventType: "type2", Processed: true, CreatedAt: now},
			{ID: "e3", UserID: "user-2", EventType: "type3", Processed: false, CreatedAt: now},
		},
	}
	svc := NewFraudService(repo)
	ctx := context.Background()
	events, err := svc.GetEventsByUser(ctx, "user-1", 10)
	if err != nil {
		t.Fatalf("GetEventsByUser failed: %v", err)
	}
	if len(events) != 2 {
		t.Errorf("Expected 2 events for user-1, got %d", len(events))
	}
}
