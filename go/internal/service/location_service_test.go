package service

import (
	"context"
	"database/sql"
	"testing"
	"time"

	"community.local/internal/models"
)

type mockLocationRepo struct {
	locations   []*models.LocationTracking
	createErr   error
	getLatestErr error
	getHistoryErr error
}

func (m *mockLocationRepo) Create(ctx context.Context, loc *models.LocationTracking) error {
	if m.createErr != nil {
		return m.createErr
	}
	loc.ID = "loc-" + time.Now().Format("20060102150405")
	m.locations = append(m.locations, loc)
	return nil
}
func (m *mockLocationRepo) GetLatest(ctx context.Context, entityType, entityID string) (*models.LocationTracking, error) {
	if m.getLatestErr != nil {
		return nil, m.getLatestErr
	}
	for i := len(m.locations) - 1; i >= 0; i-- {
		loc := m.locations[i]
		if loc.EntityType == entityType && loc.EntityID == entityID {
			return loc, nil
		}
	}
	return nil, sql.ErrNoRows
}
func (m *mockLocationRepo) GetHistory(ctx context.Context, entityType, entityID string, from, to time.Time) ([]*models.LocationTracking, error) {
	if m.getHistoryErr != nil {
		return nil, m.getHistoryErr
	}
	var res []*models.LocationTracking
	for _, loc := range m.locations {
		if loc.EntityType == entityType && loc.EntityID == entityID && !loc.Timestamp.Before(from) && !loc.Timestamp.After(to) {
			res = append(res, loc)
		}
	}
	return res, nil
}

func TestLocation_Track(t *testing.T) {
	repo := &mockLocationRepo{}
	svc := NewLocationService(repo)
	ctx := context.Background()
	loc, err := svc.TrackLocation(ctx, "user-1", "DRIVER", "driver-1", 10.0, 20.0, 5.0)
	if err != nil {
		t.Fatalf("TrackLocation failed: %v", err)
	}
	if loc.EntityType != "DRIVER" {
		t.Errorf("Wrong entity type")
	}
}

func TestLocation_GetLatest(t *testing.T) {
	now := time.Now()
	repo := &mockLocationRepo{
		locations: []*models.LocationTracking{
			{ID: "l1", EntityType: "DRIVER", EntityID: "d1", Latitude: 10, Longitude: 20, Timestamp: now.Add(-10 * time.Minute)},
			{ID: "l2", EntityType: "DRIVER", EntityID: "d1", Latitude: 11, Longitude: 21, Timestamp: now},
		},
	}
	svc := NewLocationService(repo)
	ctx := context.Background()
	loc, err := svc.GetLatestLocation(ctx, "DRIVER", "d1")
	if err != nil {
		t.Fatalf("GetLatestLocation failed: %v", err)
	}
	if loc.ID != "l2" {
		t.Errorf("Expected latest location l2, got %s", loc.ID)
	}
}

func TestGetLocationHistory_Success(t *testing.T) {
	now := time.Now()
	repo := &mockLocationRepo{
		locations: []*models.LocationTracking{
			{ID: "l1", EntityType: "DRIVER", EntityID: "d1", Latitude: 10, Longitude: 20, Timestamp: now.Add(-2 * time.Hour)},
			{ID: "l2", EntityType: "DRIVER", EntityID: "d1", Latitude: 11, Longitude: 21, Timestamp: now.Add(-1 * time.Hour)},
			{ID: "l3", EntityType: "DRIVER", EntityID: "d1", Latitude: 12, Longitude: 22, Timestamp: now},
			{ID: "l4", EntityType: "DRIVER", EntityID: "d2", Latitude: 13, Longitude: 23, Timestamp: now}, // other driver
		},
	}
	svc := NewLocationService(repo)
	ctx := context.Background()

	from := now.Add(-3 * time.Hour)
	to := now

	history, err := svc.GetLocationHistory(ctx, "DRIVER", "d1", from, to)
	if err != nil {
		t.Fatalf("GetLocationHistory failed: %v", err)
	}
	if len(history) != 3 {
		t.Errorf("Expected 3 locations, got %d", len(history))
	}
}

func TestGetLocationHistory_NoResults(t *testing.T) {
	now := time.Now()
	repo := &mockLocationRepo{
		locations: []*models.LocationTracking{
			{ID: "l1", EntityType: "DRIVER", EntityID: "d1", Timestamp: now.Add(-1 * time.Hour)},
		},
	}
	svc := NewLocationService(repo)
	ctx := context.Background()

	from := now.Add(1 * time.Hour) // future, no results
	to := now.Add(2 * time.Hour)

	history, err := svc.GetLocationHistory(ctx, "DRIVER", "d1", from, to)
	if err != nil {
		t.Fatalf("GetLocationHistory unexpected error: %v", err)
	}
	if len(history) != 0 {
		t.Errorf("Expected no locations, got %d", len(history))
	}
}

func TestGetLocationHistory_Validation_EmptyEntity(t *testing.T) {
	repo := &mockLocationRepo{}
	svc := NewLocationService(repo)
	ctx := context.Background()

	_, err := svc.GetLocationHistory(ctx, "", "d1", time.Now(), time.Now())
	if err == nil {
		t.Fatal("Expected error for empty entityType")
	}
}

func TestGetLocationHistory_Validation_ZeroTimes(t *testing.T) {
	repo := &mockLocationRepo{}
	svc := NewLocationService(repo)
	ctx := context.Background()

	zero := time.Time{}
	_, err := svc.GetLocationHistory(ctx, "DRIVER", "d1", zero, time.Now())
	if err == nil {
		t.Fatal("Expected error for zero from time")
	}
	_, err = svc.GetLocationHistory(ctx, "DRIVER", "d1", time.Now(), zero)
	if err == nil {
		t.Fatal("Expected error for zero to time")
	}
}
