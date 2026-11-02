package repository

import (
	"context"
	"sync"
	"time"

	"community.local/modules/location/entity"
)

// InMemoryLocationRepository implements LocationRepository with in-memory storage
type InMemoryLocationRepository struct {
	mu        sync.RWMutex
	locations map[string]*entity.UserLocation
	userIndex map[string]string // userID -> locationID
}

// NewInMemoryLocationRepository creates a new in-memory location repository
func NewInMemoryLocationRepository() *InMemoryLocationRepository {
	return &InMemoryLocationRepository{
		locations: make(map[string]*entity.UserLocation),
		userIndex: make(map[string]string),
	}
}

func (r *InMemoryLocationRepository) Save(ctx context.Context, location *entity.UserLocation) error {
	r.mu.Lock()
	defer r.mu.Unlock()

	// Use userID as key for current location
	r.locations[location.ID] = location
	r.userIndex[location.UserID] = location.ID

	return nil
}

func (r *InMemoryLocationRepository) GetByUserID(ctx context.Context, userID string) (*entity.UserLocation, error) {
	r.mu.RLock()
	defer r.mu.RUnlock()

	locationID, exists := r.userIndex[userID]
	if !exists {
		return nil, nil
	}

	return r.locations[locationID], nil
}

func (r *InMemoryLocationRepository) Delete(ctx context.Context, userID string) error {
	r.mu.Lock()
	defer r.mu.Unlock()

	locationID, exists := r.userIndex[userID]
	if !exists {
		return nil
	}

	delete(r.locations, locationID)
	delete(r.userIndex, userID)

	return nil
}

func (r *InMemoryLocationRepository) GetAll(ctx context.Context) ([]*entity.UserLocation, error) {
	r.mu.RLock()
	defer r.mu.RUnlock()

	locations := make([]*entity.UserLocation, 0, len(r.locations))
	for _, loc := range r.locations {
		locations = append(locations, loc)
	}

	return locations, nil
}

func (r *InMemoryLocationRepository) GetWithinBounds(ctx context.Context, minLat, maxLat, minLon, maxLon float64) ([]*entity.UserLocation, error) {
	r.mu.RLock()
	defer r.mu.RUnlock()

	var locations []*entity.UserLocation
	for _, loc := range r.locations {
		if loc.Location.Latitude >= minLat && loc.Location.Latitude <= maxLat &&
			loc.Location.Longitude >= minLon && loc.Location.Longitude <= maxLon {
			locations = append(locations, loc)
		}
	}

	return locations, nil
}

// InMemoryLocationHistoryRepository implements LocationHistoryRepository with in-memory storage
type InMemoryLocationHistoryRepository struct {
	mu      sync.RWMutex
	history map[string][]*entity.UserLocation // userID -> history entries
}

// NewInMemoryLocationHistoryRepository creates a new in-memory location history repository
func NewInMemoryLocationHistoryRepository() *InMemoryLocationHistoryRepository {
	return &InMemoryLocationHistoryRepository{
		history: make(map[string][]*entity.UserLocation),
	}
}

func (r *InMemoryLocationHistoryRepository) Add(ctx context.Context, location *entity.UserLocation) error {
	r.mu.Lock()
	defer r.mu.Unlock()

	r.history[location.UserID] = append(r.history[location.UserID], location)
	return nil
}

func (r *InMemoryLocationHistoryRepository) GetByUserID(ctx context.Context, userID string, limit int) ([]*entity.UserLocation, error) {
	r.mu.RLock()
	defer r.mu.RUnlock()

	entries, exists := r.history[userID]
	if !exists {
		return nil, nil
	}

	if len(entries) <= limit {
		return entries, nil
	}

	// Return last 'limit' entries
	return entries[len(entries)-limit:], nil
}

func (r *InMemoryLocationHistoryRepository) GetByUserIDTimeRange(ctx context.Context, userID string, from, to time.Time) ([]*entity.UserLocation, error) {
	r.mu.RLock()
	defer r.mu.RUnlock()

	entries, exists := r.history[userID]
	if !exists {
		return nil, nil
	}

	var filtered []*entity.UserLocation
	for _, entry := range entries {
		if (entry.UpdatedAt.Equal(from) || entry.UpdatedAt.After(from)) &&
			(entry.UpdatedAt.Equal(to) || entry.UpdatedAt.Before(to)) {
			filtered = append(filtered, entry)
		}
	}

	return filtered, nil
}

func (r *InMemoryLocationHistoryRepository) DeleteOlderThan(ctx context.Context, before time.Time) (int64, error) {
	r.mu.Lock()
	defer r.mu.Unlock()

	var deleted int64
	for userID, entries := range r.history {
		var filtered []*entity.UserLocation
		for _, entry := range entries {
			if entry.UpdatedAt.After(before) {
				filtered = append(filtered, entry)
			} else {
				deleted++
			}
		}
		r.history[userID] = filtered
	}

	return deleted, nil
}

func (r *InMemoryLocationHistoryRepository) Count(ctx context.Context, userID string) (int64, error) {
	r.mu.RLock()
	defer r.mu.RUnlock()

	entries, exists := r.history[userID]
	if !exists {
		return 0, nil
	}

	return int64(len(entries)), nil
}

// InMemoryGeofenceRepository implements GeofenceRepository with in-memory storage
type InMemoryGeofenceRepository struct {
	mu        sync.RWMutex
	geofences map[string]*entity.Geofence
}

// NewInMemoryGeofenceRepository creates a new in-memory geofence repository
func NewInMemoryGeofenceRepository() *InMemoryGeofenceRepository {
	return &InMemoryGeofenceRepository{
		geofences: make(map[string]*entity.Geofence),
	}
}

func (r *InMemoryGeofenceRepository) Save(ctx context.Context, geofence *entity.Geofence) error {
	r.mu.Lock()
	defer r.mu.Unlock()

	r.geofences[geofence.ID] = geofence
	return nil
}

func (r *InMemoryGeofenceRepository) GetByID(ctx context.Context, id string) (*entity.Geofence, error) {
	r.mu.RLock()
	defer r.mu.RUnlock()

	return r.geofences[id], nil
}

func (r *InMemoryGeofenceRepository) Delete(ctx context.Context, id string) error {
	r.mu.Lock()
	defer r.mu.Unlock()

	delete(r.geofences, id)
	return nil
}

func (r *InMemoryGeofenceRepository) List(ctx context.Context) ([]*entity.Geofence, error) {
	r.mu.RLock()
	defer r.mu.RUnlock()

	geofences := make([]*entity.Geofence, 0, len(r.geofences))
	for _, g := range r.geofences {
		geofences = append(geofences, g)
	}

	return geofences, nil
}

func (r *InMemoryGeofenceRepository) FindByName(ctx context.Context, namePattern string) ([]*entity.Geofence, error) {
	r.mu.RLock()
	defer r.mu.RUnlock()

	var geofences []*entity.Geofence
	for _, g := range r.geofences {
		if containsString(g.Name, namePattern) {
			geofences = append(geofences, g)
		}
	}

	return geofences, nil
}

func containsString(s, pattern string) bool {
	return len(s) >= len(pattern) &&
		(s == pattern || containsSubstring(s, pattern))
}

func containsSubstring(s, pattern string) bool {
	for i := 0; i <= len(s)-len(pattern); i++ {
		if s[i:i+len(pattern)] == pattern {
			return true
		}
	}
	return false
}
