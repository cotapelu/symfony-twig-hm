package service

import (
	"context"
	"testing"
	"time"

	"community.local/internal/models"
)

func TestFraud_CreateEvent_InvalidUserID(t *testing.T) {
	repo := &mockFraudRepo{}
	svc := NewFraudService(repo)
	ctx := context.Background()

	_, err := svc.CreateEvent(ctx, "", "LOGIN_FAILED", 50.0, "test")
	if err == nil {
		t.Fatal("Expected error for empty userID")
	}
	if err.Error() != "user ID is required" {
		t.Errorf("Expected 'user ID is required', got %s", err.Error())
	}
}

func TestFraud_CreateEvent_InvalidEventType(t *testing.T) {
	repo := &mockFraudRepo{}
	svc := NewFraudService(repo)
	ctx := context.Background()

	_, err := svc.CreateEvent(ctx, "user-1", "", 50.0, "test")
	if err == nil {
		t.Fatal("Expected error for empty eventType")
	}
	if err.Error() != "event type is required" {
		t.Errorf("Expected 'event type is required', got %s", err.Error())
	}
}

func TestFraud_CreateEvent_InvalidScoreLow(t *testing.T) {
	repo := &mockFraudRepo{}
	svc := NewFraudService(repo)
	ctx := context.Background()

	_, err := svc.CreateEvent(ctx, "user-1", "LOGIN_FAILED", -5.0, "test")
	if err == nil {
		t.Fatal("Expected error for negative score")
	}
	if err.Error() != "score must be 0-100" {
		t.Errorf("Expected 'score must be 0-100', got %s", err.Error())
	}
}

func TestFraud_CreateEvent_InvalidScoreHigh(t *testing.T) {
	repo := &mockFraudRepo{}
	svc := NewFraudService(repo)
	ctx := context.Background()

	_, err := svc.CreateEvent(ctx, "user-1", "LOGIN_FAILED", 150.0, "test")
	if err == nil {
		t.Fatal("Expected error for score > 100")
	}
	if err.Error() != "score must be 0-100" {
		t.Errorf("Expected 'score must be 0-100', got %s", err.Error())
	}
}

func TestFraud_GetEventsByUser_DefaultLimit(t *testing.T) {
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

	// Test with limit=0 (should default to 100)
	events, err := svc.GetEventsByUser(ctx, "user-1", 0)
	if err != nil {
		t.Fatalf("GetEventsByUser failed: %v", err)
	}
	if len(events) != 2 {
		t.Errorf("Expected 2 events for user-1 with default limit, got %d", len(events))
	}
}

func TestFraud_MarkProcessed_Success(t *testing.T) {
	now := time.Now()
	repo := &mockFraudRepo{
		events: []*models.FraudEvent{
			{ID: "e1", UserID: "user-1", Processed: false, CreatedAt: now},
		},
	}
	svc := NewFraudService(repo)
	ctx := context.Background()

	err := svc.MarkProcessed(ctx, "e1")
	if err != nil {
		t.Fatalf("MarkProcessed failed: %v", err)
	}
	if repo.events[0].Processed != true {
		t.Errorf("Expected event to be marked processed")
	}
}

func TestFraud_MarkProcessed_NotFound(t *testing.T) {
	repo := &mockFraudRepo{}
	svc := NewFraudService(repo)
	ctx := context.Background()

	err := svc.MarkProcessed(ctx, "non-existent")
	if err == nil {
		t.Fatal("Expected error for non-existent event")
	}
	if err.Error() != "not found" {
		t.Errorf("Expected 'not found' error, got %v", err)
	}
}

func TestFraud_GetEvent(t *testing.T) {
	repo := &mockFraudRepo{}
	svc := NewFraudService(repo)
	ctx := context.Background()

	_, err := svc.GetEvent(ctx, "any-id")
	if err == nil {
		t.Fatal("Expected error from GetEvent")
	}
	if err.Error() != "not implemented" {
		t.Errorf("Expected 'not implemented', got %v", err)
	}
}
