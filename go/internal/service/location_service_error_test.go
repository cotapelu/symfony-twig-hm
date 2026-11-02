package service

import (
	"context"
	"errors"
	"testing"
	"time"
)

func TestTrackLocation_RepoError(t *testing.T) {
	repo := &mockLocationRepo{createErr: errors.New("db error")}
	svc := NewLocationService(repo)
	ctx := context.Background()
	_, err := svc.TrackLocation(ctx, "user-1", "DRIVER", "driver-1", 10.0, 20.0, 5.0)
	if err == nil {
		t.Fatal("expected error")
	}
}

func TestGetLatestLocation_RepoError(t *testing.T) {
	repo := &mockLocationRepo{getLatestErr: errors.New("db error")}
	svc := NewLocationService(repo)
	ctx := context.Background()
	_, err := svc.GetLatestLocation(ctx, "DRIVER", "d1")
	if err == nil {
		t.Fatal("expected error")
	}
}

func TestGetLatestLocation_NoRows(t *testing.T) {
	repo := &mockLocationRepo{} // empty store returns sql.ErrNoRows, which service converts to "no location found"
	svc := NewLocationService(repo)
	ctx := context.Background()
	_, err := svc.GetLatestLocation(ctx, "DRIVER", "d1")
	if err == nil {
		t.Fatal("expected error")
	}
	if err.Error() != "no location found" {
		t.Fatalf("expected 'no location found', got %v", err)
	}
}

func TestGetLocationHistory_RepoError(t *testing.T) {
	repo := &mockLocationRepo{getHistoryErr: errors.New("db error")}
	svc := NewLocationService(repo)
	ctx := context.Background()
	_, err := svc.GetLocationHistory(ctx, "DRIVER", "d1", time.Now().Add(-1*time.Hour), time.Now())
	if err == nil {
		t.Fatal("expected error")
	}
}
