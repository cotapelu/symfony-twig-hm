package service

import (
	"context"
	"crypto/rand"
	"encoding/hex"
	"fmt"
	"math"
	"time"

	"community.local/modules/location/entity"
	"community.local/modules/location/repository"
)

type LocationService struct {
	locationRepo        repository.LocationRepository
	locationHistoryRepo repository.LocationHistoryRepository
	geofenceRepo        repository.GeofenceRepository
	eventHandler        EventEmitter
}

type EventEmitter interface {
	EmitEvent(ctx context.Context, eventType string, data interface{}) error
}

func NewLocationService(
	locationRepo repository.LocationRepository,
	locationHistoryRepo repository.LocationHistoryRepository,
	geofenceRepo repository.GeofenceRepository,
	eventHandler EventEmitter,
) *LocationService {
	return &LocationService{
		locationRepo:        locationRepo,
		locationHistoryRepo: locationHistoryRepo,
		geofenceRepo:        geofenceRepo,
		eventHandler:        eventHandler,
	}
}

func (s *LocationService) UpdateLocation(ctx context.Context, userID string, latitude, longitude float64) error {
	// Input validation
	if userID == "" {
		return fmt.Errorf("user ID is required")
	}
	if latitude < -90 || latitude > 90 {
		return fmt.Errorf("latitude must be between -90 and 90")
	}
	if longitude < -180 || longitude > 180 {
		return fmt.Errorf("longitude must be between -180 and 180")
	}

	location := &entity.UserLocation{
		ID:        generateID(),
		UserID:    userID,
		Location:  entity.Location{Latitude: latitude, Longitude: longitude},
		UpdatedAt: time.Now(),
	}

	// Save current location
	if err := s.locationRepo.Save(ctx, location); err != nil {
		return err
	}

	// Add to history
	if err := s.locationHistoryRepo.Add(ctx, location); err != nil {
		return err
	}

	if s.eventHandler != nil {
		event := map[string]interface{}{
			"event_type": "location.updated",
			"user_id":    userID,
			"latitude":   latitude,
			"longitude":  longitude,
			"timestamp":  time.Now(),
		}
		_ = s.eventHandler.EmitEvent(ctx, "location.update", event)
	}

	return nil
}

func (s *LocationService) GetLocation(ctx context.Context, userID string) (*entity.UserLocation, error) {
	return s.locationRepo.GetByUserID(ctx, userID)
}

func (s *LocationService) GetNearbyUsers(ctx context.Context, userID string, radiusKm float64, limit int) ([]entity.NearbyUser, error) {
	// Input validation
	if userID == "" {
		return []entity.NearbyUser{}, fmt.Errorf("user ID is required")
	}
	if radiusKm <= 0 {
		return []entity.NearbyUser{}, fmt.Errorf("radius must be positive")
	}
	if limit <= 0 {
		limit = 20
	}
	if limit > 100 {
		limit = 100
	}

	currentUserLoc, err := s.GetLocation(ctx, userID)
	if err != nil {
		return []entity.NearbyUser{}, err
	}

	if currentUserLoc == nil {
		return []entity.NearbyUser{}, nil
	}

	allLocations, err := s.locationRepo.GetAll(ctx)
	if err != nil {
		return []entity.NearbyUser{}, err
	}

	var nearby []entity.NearbyUser

	for _, userLoc := range allLocations {
		if userLoc.UserID == userID {
			continue
		}

		distance := calculateDistance(
			currentUserLoc.Location.Latitude, currentUserLoc.Location.Longitude,
			userLoc.Location.Latitude, userLoc.Location.Longitude,
		)

		if distance <= radiusKm {
			nearby = append(nearby, entity.NearbyUser{
				UserID:   userLoc.UserID,
				Username: userLoc.UserID,
				Location: userLoc.Location,
				Distance: distance,
			})
		}

		if len(nearby) >= limit {
			break
		}
	}

	return nearby, nil
}

func (s *LocationService) CheckGeofences(ctx context.Context, userID string, latitude, longitude float64) ([]string, error) {
	geofences, err := s.geofenceRepo.List(ctx)
	if err != nil {
		return nil, err
	}

	var matchedGeofences []string

	for _, geofence := range geofences {
		if s.isPointInGeofence(latitude, longitude, geofence) {
			matchedGeofences = append(matchedGeofences, geofence.ID)
		}
	}

	if len(matchedGeofences) > 0 && s.eventHandler != nil {
		for _, geoID := range matchedGeofences {
			geofence, _ := s.geofenceRepo.GetByID(ctx, geoID)
			event := map[string]interface{}{
				"event_type":    "geofence.triggered",
				"user_id":       userID,
				"geofence_id":   geoID,
				"geofence_name": geofence.Name,
				"timestamp":     time.Now(),
			}
			_ = s.eventHandler.EmitEvent(ctx, "geofence.trigger", event)
		}
	}

	return matchedGeofences, nil
}

func (s *LocationService) GetLocationHistory(ctx context.Context, userID string, limit int) ([]*entity.UserLocation, error) {
	return s.locationHistoryRepo.GetByUserID(ctx, userID, limit)
}

func (s *LocationService) DeleteCurrentLocation(ctx context.Context, userID string) error {
	userLocation, err := s.locationRepo.GetByUserID(ctx, userID)
	if err != nil {
		return err
	}

	if userLocation == nil {
		return fmt.Errorf("user location not found")
	}

	if err := s.locationRepo.Delete(ctx, userID); err != nil {
		return err
	}

	if s.eventHandler != nil {
		event := map[string]interface{}{
			"event_type": "location.deleted",
			"user_id":    userID,
			"latitude":   userLocation.Location.Latitude,
			"longitude":  userLocation.Location.Longitude,
			"timestamp":  time.Now(),
		}
		_ = s.eventHandler.EmitEvent(ctx, "location.delete", event)
	}

	return nil
}

func (s *LocationService) isPointInGeofence(lat, lon float64, geofence *entity.Geofence) bool {
	centerLat := 0.0
	centerLon := 0.0

	for _, point := range geofence.Points {
		centerLat += point.Latitude
		centerLon += point.Longitude
	}

	centerLat /= float64(len(geofence.Points))
	centerLon /= float64(len(geofence.Points))

	distance := calculateDistance(lat, lon, centerLat, centerLon)

	return distance <= geofence.Radius
}

func calculateDistance(lat1, lon1, lat2, lon2 float64) float64 {
	const earthRadius = 6371

	latDiff := toRadians(lat2 - lat1)
	lonDiff := toRadians(lon2 - lon1)

	a := math.Sin(latDiff/2)*math.Sin(latDiff/2) +
		math.Cos(toRadians(lat1))*math.Cos(toRadians(lat2))*
			math.Sin(lonDiff/2)*math.Sin(lonDiff/2)

	c := 2 * math.Atan2(math.Sqrt(a), math.Sqrt(1-a))

	return earthRadius * c
}

func toRadians(degrees float64) float64 {
	return degrees * (math.Pi / 180)
}

func generateID() string {
	bytes := make([]byte, 4)
	rand.Read(bytes)
	return fmt.Sprintf("%s-%s", time.Now().Format("20060102150405"), hex.EncodeToString(bytes))
}

func (s *LocationService) CreateGeofence(ctx context.Context, geofence *entity.Geofence) error {
	geofence.ID = generateID()
	geofence.CreatedAt = time.Now()

	return s.geofenceRepo.Save(ctx, geofence)
}

func (s *LocationService) GetGeofence(ctx context.Context, geofenceID string) (*entity.Geofence, error) {
	return s.geofenceRepo.GetByID(ctx, geofenceID)
}

func (s *LocationService) DeleteGeofence(ctx context.Context, geofenceID string) error {
	return s.geofenceRepo.Delete(ctx, geofenceID)
}

func (s *LocationService) ListGeofences(ctx context.Context) ([]*entity.Geofence, error) {
	return s.geofenceRepo.List(ctx)
}