package service

import (
	"context"
	"errors"
	"time"

	"community.local/internal/models"
)

type FraudService struct {
	repo models.FraudRepository
}

func NewFraudService(repo models.FraudRepository) *FraudService {
	return &FraudService{repo: repo}
}

func (s *FraudService) CreateEvent(ctx context.Context, userID, eventType string, score float64, details string) (*models.FraudEvent, error) {
	if userID == "" {
		return nil, errors.New("user ID is required")
	}
	if eventType == "" {
		return nil, errors.New("event type is required")
	}
	if score < 0 || score > 100 {
		return nil, errors.New("score must be 0-100")
	}
	event := &models.FraudEvent{
		UserID:     userID,
		EventType:  eventType,
		Score:      score,
		Details:    details,
		Processed:  false,
		CreatedAt:  time.Now(),
	}
	err := s.repo.CreateEvent(ctx, event)
	if err != nil {
		return nil, err
	}
	return event, nil
}

func (s *FraudService) GetEvent(ctx context.Context, id string) (*models.FraudEvent, error) {
	// Assuming GetEvent exists in repo? Add if missing
	return nil, errors.New("not implemented")
}

func (s *FraudService) GetEventsByUser(ctx context.Context, userID string, limit int) ([]*models.FraudEvent, error) {
	if limit <= 0 {
		limit = 100
	}
	events, err := s.repo.GetEventsByUser(ctx, userID, limit)
	if err != nil {
		return nil, err
	}
	return events, nil
}

func (s *FraudService) MarkProcessed(ctx context.Context, id string) error {
	return s.repo.MarkProcessed(ctx, id)
}
