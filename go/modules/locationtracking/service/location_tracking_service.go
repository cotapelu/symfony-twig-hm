package service

import (
	"encoding/json"
	"fmt"
	"math"
	"os"
	"path/filepath"
	"strings"
	"sync"
	"time"

	"community.local/modules/locationtracking/entity"
)

type LocationTrackingService struct {
	mu           sync.RWMutex
	locations    map[string]entity.Location
	geofences    map[string]entity.Geofence
	history      map[string]entity.HistoryEntry
	nextID       int
	etsFile      string
	locationCache map[string]entity.Location
	cacheTTL     time.Duration
}

func NewLocationTrackingService(etsFile string) *LocationTrackingService {
	s := &LocationTrackingService{
		locations:     make(map[string]entity.Location),
		geofences:     make(map[string]entity.Geofence),
		history:       make(map[string]entity.HistoryEntry),
		nextID:        1,
		etsFile:       etsFile,
		locationCache: make(map[string]entity.Location),
		cacheTTL:      5 * time.Minute,
	}
	s.ensureDataDirectory(etsFile)
	s.restoreFromETS()
	return s
}

func (s *LocationTrackingService) ensureDataDirectory(filePath string) {
	dir := filepath.Dir(filePath)
	if dir != "" && dir != "." {
		os.MkdirAll(dir, 0755)
	}
}

func (s *LocationTrackingService) restoreFromETS() {
	data, err := os.ReadFile(s.etsFile)
	if err != nil {
		if os.IsNotExist(err) {
			return
		}
		return
	}

	var saved struct {
		Locations map[string]entity.Location     `json:"locations"`
		Geofences map[string]entity.Geofence     `json:"geofences"`
		History   map[string]entity.HistoryEntry `json:"history"`
		NextID    int                            `json:"next_id"`
	}

	if err := json.Unmarshal(data, &saved); err != nil {
		return
	}

	s.locations = saved.Locations
	s.geofences = saved.Geofences
	s.history = saved.History
	s.nextID = saved.NextID
}

func (s *LocationTrackingService) persistToETS() {
	data, _ := json.MarshalIndent(map[string]interface{}{
		"locations": s.locations,
		"geofences": s.geofences,
		"history":   s.history,
		"next_id":   s.nextID,
	}, "", "  ")
	os.WriteFile(s.etsFile, data, 0644)
}

func (s *LocationTrackingService) UpdateLocation(entityID, entityType string, latitude, longitude float64, metadata map[string]interface{}) entity.Location {
	s.mu.Lock()
	defer s.mu.Unlock()

	accuracy := 10.0
	var altitude, speed, heading *float64

	if m, ok := metadata["accuracy"].(float64); ok {
		accuracy = m
	}
	if m, ok := metadata["altitude"].(float64); ok {
		altitude = &m
	}
	if m, ok := metadata["speed"].(float64); ok {
		speed = &m
	}
	if m, ok := metadata["heading"].(float64); ok {
		heading = &m
	}

	now := time.Now()
	location := entity.Location{
		EntityID:   entityID,
		EntityType: entityType,
		Latitude:   latitude,
		Longitude:  longitude,
		Accuracy:   accuracy,
		Altitude:   altitude,
		Speed:      speed,
		Heading:    heading,
		Timestamp:  now,
		UpdatedAt:  now,
		Metadata:   metadata,
	}

	s.locations[entityID] = location

	historyEntry := entity.HistoryEntry{
		EntityID:  entityID,
		Latitude:  latitude,
		Longitude: longitude,
		Timestamp: now,
	}
	historyKey := fmt.Sprintf("%s_%d", entityID, s.nextID)
	s.history[historyKey] = historyEntry
	s.nextID++

	return location
}

func (s *LocationTrackingService) GetLocation(entityID string) (*entity.Location, error) {
	s.mu.RLock()
	defer s.mu.RUnlock()

	location, exists := s.locations[entityID]
	if !exists {
		return nil, fmt.Errorf("location not found")
	}
	return &location, nil
}

func (s *LocationTrackingService) GetLocationsByType(entityType string) []entity.Location {
	s.mu.RLock()
	defer s.mu.RUnlock()

	var locations []entity.Location
	for _, loc := range s.locations {
		if loc.EntityType == entityType {
			locations = append(locations, loc)
		}
	}
	return locations
}

func (s *LocationTrackingService) GetLocationHistory(entityID string, since *time.Time) []entity.HistoryEntry {
	s.mu.RLock()
	defer s.mu.RUnlock()

	var entries []entity.HistoryEntry
	for key, entry := range s.history {
		if !strings.HasPrefix(key, entityID+"_") {
			continue
		}
		if since != nil && !entry.Timestamp.After(*since) {
			continue
		}
		entries = append(entries, entry)
	}

	return entries
}

func (s *LocationTrackingService) CreateGeofence(name string, latitude, longitude, radiusMeters float64, entityType string) entity.Geofence {
	s.mu.Lock()
	defer s.mu.Unlock()

	geoID := fmt.Sprintf("geo_%d", s.nextID)
	s.nextID++

	geofence := entity.Geofence{
		ID:           geoID,
		Name:         name,
		Latitude:     latitude,
		Longitude:    longitude,
		RadiusMeters: radiusMeters,
		EntityType:   entityType,
		CreatedAt:    time.Now(),
	}

	s.geofences[geoID] = geofence
	return geofence
}

func (s *LocationTrackingService) CheckGeofence(entityID string) ([]entity.Geofence, error) {
	s.mu.RLock()
	defer s.mu.RUnlock()

	location, exists := s.locations[entityID]
	if !exists {
		return nil, fmt.Errorf("location not found")
	}

	var triggered []entity.Geofence
	for _, geo := range s.geofences {
		if geo.EntityType != location.EntityType && geo.EntityType != "all" {
			continue
		}
		distance := s.calculateDistance(location.Latitude, location.Longitude, geo.Latitude, geo.Longitude)
		if distance <= geo.RadiusMeters {
			triggered = append(triggered, geo)
		}
	}

	return triggered, nil
}

func (s *LocationTrackingService) GetNearbyEntities(entityType string, latitude, longitude, radiusMeters float64, limit int) []entity.Location {
	s.mu.RLock()
	defer s.mu.RUnlock()

	var nearby []entity.Location
	for _, loc := range s.locations {
		if loc.EntityType != entityType {
			continue
		}
		distance := s.calculateDistance(latitude, longitude, loc.Latitude, loc.Longitude)
		if distance <= radiusMeters {
			nearby = append(nearby, loc)
		}
	}

	return nearby
}

func (s *LocationTrackingService) CalculateDistance(lat1, lon1, lat2, lon2 float64) float64 {
	return s.calculateDistance(lat1, lon1, lat2, lon2)
}

func (s *LocationTrackingService) calculateDistance(lat1, lon1, lat2, lon2 float64) float64 {
	earthRadiusMeters := 6371000.0

	lat1Rad := lat1 * math.Pi / 180
	lat2Rad := lat2 * math.Pi / 180
	deltaLat := (lat2 - lat1) * math.Pi / 180
	deltaLon := (lon2 - lon1) * math.Pi / 180

	a := math.Sin(deltaLat/2)*math.Sin(deltaLat/2) +
		math.Cos(lat1Rad)*math.Cos(lat2Rad)*
			math.Sin(deltaLon/2)*math.Sin(deltaLon/2)

	c := 2 * math.Atan2(math.Sqrt(a), math.Sqrt(1-a))

	return earthRadiusMeters * c
}

func (s *LocationTrackingService) GetETA(entityID string, destinationLat, destinationLon float64, averageSpeedKmh float64) (map[string]interface{}, error) {
	s.mu.RLock()
	defer s.mu.RUnlock()

	location, exists := s.locations[entityID]
	if !exists {
		return nil, fmt.Errorf("location not found")
	}

	distanceMeters := s.calculateDistance(location.Latitude, location.Longitude, destinationLat, destinationLon)
	distanceKm := distanceMeters / 1000
	etaHours := distanceKm / averageSpeedKmh
	etaSeconds := int(etaHours * 3600)

	return map[string]interface{}{
		"distance_meters": distanceMeters,
		"eta_seconds":     etaSeconds,
		"eta_minutes":     etaSeconds / 60,
	}, nil
}

func (s *LocationTrackingService) StopTracking(entityID string) error {
	s.mu.Lock()
	defer s.mu.Unlock()

	delete(s.locations, entityID)
	return nil
}

// UpdateLocationForUser updates location for a user (REST API compatible)
func (s *LocationTrackingService) UpdateLocationForUser(userID string, latitude, longitude, accuracy, altitude, speed, heading float64, deviceID string) entity.Location {
	metadata := map[string]interface{}{
		"accuracy":  accuracy,
		"altitude":  altitude,
		"speed":     speed,
		"heading":   heading,
		"device_id": deviceID,
	}
	return s.UpdateLocation(userID, "user", latitude, longitude, metadata)
}

// GetUserLocation retrieves a user's latest location (REST API compatible)
func (s *LocationTrackingService) GetUserLocation(userID string) (*entity.Location, error) {
	return s.GetLocation(userID)
}

// GetUserLocationHistory retrieves a user's location history (REST API compatible)
func (s *LocationTrackingService) GetUserLocationHistory(userID string, hours int) ([]entity.HistoryEntry, error) {
	since := time.Now().Add(time.Duration(-hours) * time.Hour)
	history := s.GetLocationHistory(userID, &since)
	return history, nil
}

// GetNearbyUsers retrieves users near a location (REST API compatible)
func (s *LocationTrackingService) GetNearbyUsers(lat, lon, radiusMeters float64, limit int) ([]entity.Location, error) {
	nearby := s.GetNearbyEntities("user", lat, lon, radiusMeters, limit)
	return nearby, nil
}

// CreateGeofenceForUser creates a geofence for users (REST API compatible)
func (s *LocationTrackingService) CreateGeofenceForUser(name, description string, latitude, longitude, radius float64) entity.Geofence {
	return s.CreateGeofence(name, latitude, longitude, radius, "user")
}

// GetActiveGeofences retrieves all active geofences (REST API compatible)
func (s *LocationTrackingService) GetActiveGeofences() []entity.Geofence {
	s.mu.RLock()
	defer s.mu.RUnlock()

	fences := make([]entity.Geofence, 0, len(s.geofences))
	for _, fence := range s.geofences {
		fences = append(fences, fence)
	}
	return fences
}

// BatchUpdateLocations updates multiple locations in a single operation for performance
func (s *LocationTrackingService) BatchUpdateLocations(locations []entity.Location) []entity.Location {
	s.mu.Lock()
	defer s.mu.Unlock()

	updated := make([]entity.Location, 0, len(locations))
	now := time.Now()

	for _, loc := range locations {
		loc.UpdatedAt = now
		if loc.Timestamp.IsZero() {
			loc.Timestamp = now
		}

		s.locations[loc.EntityID] = loc

		historyEntry := entity.HistoryEntry{
			EntityID:  loc.EntityID,
			Latitude:  loc.Latitude,
			Longitude: loc.Longitude,
			Timestamp: now,
		}
		historyKey := fmt.Sprintf("%s_%d", loc.EntityID, s.nextID)
		s.history[historyKey] = historyEntry
		s.nextID++

		updated = append(updated, loc)
	}

	return updated
}

// GetLocationsByTimeRange retrieves locations within a time range for a specific radius
func (s *LocationTrackingService) GetLocationsByTimeRange(lat, lon float64, startTime, endTime time.Time) []entity.Location {
	s.mu.RLock()
	defer s.mu.RUnlock()

	var result []entity.Location
	for _, loc := range s.locations {
		if loc.Timestamp.Before(startTime) || loc.Timestamp.After(endTime) {
			continue
		}
		result = append(result, loc)
	}

	return result
}

// GetFrequentLocations retrieves frequently visited locations for a user
func (s *LocationTrackingService) GetFrequentLocations(entityID string, hours int, thresholdMeters float64) []entity.LocationCluster {
	s.mu.RLock()
	defer s.mu.RUnlock()

	since := time.Now().Add(time.Duration(-hours) * time.Hour)
	locationClusters := make(map[string]*entity.LocationCluster)

	// Group locations by proximity
	for key, entry := range s.history {
		if !strings.HasPrefix(key, entityID+"_") || !entry.Timestamp.After(since) {
			continue
		}

		clusterKey := fmt.Sprintf("%.6f_%.6f", entry.Latitude, entry.Longitude)
		cluster, exists := locationClusters[clusterKey]
		if !exists {
			locationClusters[clusterKey] = &entity.LocationCluster{
				Latitude:     entry.Latitude,
				Longitude:    entry.Longitude,
				VisitCount:   0,
				LastVisited:  entry.Timestamp,
			}
			cluster = locationClusters[clusterKey]
		}

		cluster.VisitCount++
		if entry.Timestamp.After(cluster.LastVisited) {
			cluster.LastVisited = entry.Timestamp
		}
	}

	// Filter by visit count threshold
	var frequentLocations []entity.LocationCluster
	minVisits := 3  // Minimum visits to be considered "frequent"
	for _, cluster := range locationClusters {
		if cluster.VisitCount >= minVisits {
			frequentLocations = append(frequentLocations, *cluster)
		}
	}

	return frequentLocations
}

// GetLocationPattern analyzes movement patterns for an entity
func (s *LocationTrackingService) GetLocationPattern(entityID string, timePeriod string) map[string]interface{} {
	s.mu.RLock()
	defer s.mu.RUnlock()

	var startTime time.Time
	switch timePeriod {
	case "hourly":
		startTime = time.Now().Add(-1 * time.Hour)
	case "daily":
		startTime = time.Now().Add(-24 * time.Hour)
	case "weekly":
		startTime = time.Now().Add(-7 * 24 * time.Hour)
	default:
		startTime = time.Now().Add(-24 * time.Hour)
	}

	hourlyActivity := make(map[int]int)
	totalDistance := 0.0
	var prevLat, prevLon *float64

	for key, entry := range s.history {
		if !strings.HasPrefix(key, entityID+"_") || !entry.Timestamp.After(startTime) {
			continue
		}

		hour := entry.Timestamp.Hour()
		hourlyActivity[hour]++

		if prevLat != nil && prevLon != nil {
			distance := s.calculateDistance(*prevLat, *prevLon, entry.Latitude, entry.Longitude)
			totalDistance += distance
		}

		prevLat = &entry.Latitude
		prevLon = &entry.Longitude
	}

	return map[string]interface{}{
		"time_period":       timePeriod,
		"hourly_activity":   hourlyActivity,
		"total_distance_km": totalDistance / 1000,
		"entry_count":      len(hourlyActivity),
	}
}

// ValidateLocationAccuracy checks if a location is within acceptable accuracy range
func (s *LocationTrackingService) ValidateLocationAccuracy(location entity.Location) bool {
	if location.Accuracy > 100.0 {
		return false
	}

	if location.Accuracy < 0 {
		return false
	}

	return true
}

// GetGeofences retrieves all geofences for a specific ward/entity type
func (s *LocationTrackingService) GetGeofences(entityType string) []entity.Geofence {
	s.mu.RLock()
	defer s.mu.RUnlock()

	if entityType == "all" {
		return s.GetActiveGeofences()
	}

	var fences []entity.Geofence
	for _, geo := range s.geofences {
		if geo.EntityType == entityType {
			fences = append(fences, geo)
		}
	}

	return fences
}


func (s *LocationTrackingService) Terminate() {
	s.persistToETS()
}


// getCachedLocation retrieves a location from cache if valid
func (s *LocationTrackingService) getCachedLocation(entityID string) (*entity.Location, bool) {
	s.mu.RLock()
	defer s.mu.RUnlock()

	loc, exists := s.locationCache[entityID]
	if !exists {
		return nil, false
	}

	if time.Since(loc.UpdatedAt) > s.cacheTTL {
		return nil, false
	}

	return &loc, true
}

// setCachedLocation stores a location in the cache
func (s *LocationTrackingService) setCachedLocation(loc entity.Location) {
	s.mu.Lock()
	defer s.mu.Unlock()

	s.locationCache[loc.EntityID] = loc
}

// invalidateCache removes a location from the cache
func (s *LocationTrackingService) invalidateCache(entityID string) {
	s.mu.Lock()
	defer s.mu.Unlock()

	delete(s.locationCache, entityID)
}
