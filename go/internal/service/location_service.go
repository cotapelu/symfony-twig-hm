package service

import (
	"context"
	"database/sql"
	"errors"
	"time"

	"community.local/internal/models"
)

type LocationService struct {
	repo models.LocationRepository
}

func NewLocationService(repo models.LocationRepository) *LocationService {
	return &LocationService{repo: repo}
}

func (s *LocationService) TrackLocation(ctx context.Context, userID, entityType, entityID string, lat, lng, accuracy float64) (*models.LocationTracking, error) {
	if entityType == "" || entityID == "" {
		return nil, errors.New("entity type and ID required")
	}
	loc := &models.LocationTracking{
		UserID:     userID,
		EntityType: entityType,
		EntityID:   entityID,
		Latitude:   lat,
		Longitude:  lng,
		Accuracy:   accuracy,
		Timestamp:  time.Now(),
	}
	err := s.repo.Create(ctx, loc)
	if err != nil {
		return nil, err
	}
	return loc, nil
}

func (s *LocationService) GetLatestLocation(ctx context.Context, entityType, entityID string) (*models.LocationTracking, error) {
	if entityType == "" || entityID == "" {
		return nil, errors.New("entity type and ID required")
	}
	loc, err := s.repo.GetLatest(ctx, entityType, entityID)
	if err != nil {
		if errors.Is(err, sql.ErrNoRows) {
			return nil, errors.New("no location found")
		}
		return nil, err
	}
	return loc, nil
}

func (s *LocationService) GetLocationHistory(ctx context.Context, entityType, entityID string, from, to time.Time) ([]*models.LocationTracking, error) {
	if entityType == "" || entityID == "" {
		return nil, errors.New("entity type and ID required")
	}
	if from.IsZero() || to.IsZero() {
		return nil, errors.New("from and to times required")
	}
	history, err := s.repo.GetHistory(ctx, entityType, entityID, from, to)
	if err != nil {
		return nil, err
	}
	return history, nil
}
