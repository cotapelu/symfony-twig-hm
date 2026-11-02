package repository

import (
	"context"
	"time"

	"community.local/modules/location/entity"
)

// LocationRepository interface for storing and retrieving user locations
type LocationRepository interface {
	// Save or update a user's current location
	Save(ctx context.Context, location *entity.UserLocation) error

	// Get user's current location
	GetByUserID(ctx context.Context, userID string) (*entity.UserLocation, error)

	// Delete user's current location
	Delete(ctx context.Context, userID string) error

	// Get all stored locations (for nearby user search)
	GetAll(ctx context.Context) ([]*entity.UserLocation, error)

	// Find locations within a bounding box (optimization for nearby search)
	GetWithinBounds(ctx context.Context, minLat, maxLat, minLon, maxLon float64) ([]*entity.UserLocation, error)
}

// LocationHistoryRepository interface for storing location history
type LocationHistoryRepository interface {
	// Add a location to user's history
	Add(ctx context.Context, location *entity.UserLocation) error

	// Get user's location history with limit
	GetByUserID(ctx context.Context, userID string, limit int) ([]*entity.UserLocation, error)

	// Get location history within time range
	GetByUserIDTimeRange(ctx context.Context, userID string, from, to time.Time) ([]*entity.UserLocation, error)

	// Delete old history entries (for cleanup)
	DeleteOlderThan(ctx context.Context, before time.Time) (int64, error)

	// Get total count of history entries for a user
	Count(ctx context.Context, userID string) (int64, error)
}

// GeofenceRepository interface for managing geofences
type GeofenceRepository interface {
	// Save a geofence
	Save(ctx context.Context, geofence *entity.Geofence) error

	// Get geofence by ID
	GetByID(ctx context.Context, id string) (*entity.Geofence, error)

	// Delete geofence
	Delete(ctx context.Context, id string) error

	// List all geofences
	List(ctx context.Context) ([]*entity.Geofence, error)

	// Find geofences by name pattern
	FindByName(ctx context.Context, namePattern string) ([]*entity.Geofence, error)
}
