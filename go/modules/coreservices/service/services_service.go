package service

import (
	"context"
	"errors"
	"log"
	"math"
	"sync"
	"time"

	"community.local/internal/eventbus"
	"community.local/modules/coreservices/entity"
)

var (
	ErrInvalidServiceType = errors.New("invalid service type")
	ErrInvalidCoordinates = errors.New("invalid coordinates")
	ErrInvalidUserID      = errors.New("user ID is required")
	ErrUnauthorized       = errors.New("unauthorized access to booking")
	ErrInvalidRadius      = errors.New("radius must be positive")
)

type ServicesService struct {
	eventBus  *eventbus.EventBus
	providers map[string]*entity.ServiceProvider
	bookings  map[string]*entity.Booking
	mu        sync.RWMutex
}

func NewServicesService(eventBus *eventbus.EventBus) *ServicesService {
	service := &ServicesService{
		eventBus:  eventBus,
		providers: make(map[string]*entity.ServiceProvider),
		bookings:  make(map[string]*entity.Booking),
	}
	service.initSampleProviders()
	return service
}

func (ss *ServicesService) initSampleProviders() {
	ss.providers["prov001"] = &entity.ServiceProvider{
		ID:          "prov001",
		Name:        "Quick Ride",
		ServiceType: "ride",
		Location:    entity.Location{Latitude: 40.7128, Longitude: -74.0060},
		IsActive:    true,
	}
	ss.providers["prov002"] = &entity.ServiceProvider{
		ID:          "prov002",
		Name:        "Fast Delivery",
		ServiceType: "delivery",
		Location:    entity.Location{Latitude: 40.7580, Longitude: -73.9855},
		IsActive:    true,
	}
}

func (ss *ServicesService) validateServiceType(serviceType string) error {
	validTypes := map[string]bool{"ride": true, "delivery": true, "cleaning": true, "repair": true}
	if !validTypes[serviceType] {
		return ErrInvalidServiceType
	}
	return nil
}

func (ss *ServicesService) validateCoordinates(location entity.Location) error {
	if location.Latitude < -90 || location.Latitude > 90 {
		return ErrInvalidCoordinates
	}
	if location.Longitude < -180 || location.Longitude > 180 {
		return ErrInvalidCoordinates
	}
	return nil
}

func (ss *ServicesService) validateUserID(userID string) error {
	if userID == "" {
		return ErrInvalidUserID
	}
	return nil
}

func (ss *ServicesService) validateRadius(radiusKm float64) error {
	if radiusKm <= 0 || radiusKm > 100 {
		return ErrInvalidRadius
	}
	return nil
}

func (ss *ServicesService) CreateBooking(ctx context.Context, serviceType string, userID string, providerID string, pickup entity.Location, dropoff entity.Location, scheduledAt time.Time) (*entity.Booking, error) {
	if pickup == (entity.Location{}) {
		return nil, errors.New("pickup location is required")
	}
	if dropoff == (entity.Location{}) {
		return nil, errors.New("dropoff location is required")
	}
	if err := ss.validateServiceType(serviceType); err != nil {
		return nil, err
	}
	if err := ss.validateUserID(userID); err != nil {
		return nil, err
	}
	if err := ss.validateCoordinates(pickup); err != nil {
		return nil, err
	}
	if err := ss.validateCoordinates(dropoff); err != nil {
		return nil, err
	}
	if providerID != "" {
		ss.mu.RLock()
		if _, ok := ss.providers[providerID]; !ok {
			ss.mu.RUnlock()
			return nil, errors.New("invalid provider ID")
		}
		ss.mu.RUnlock()
	}

	booking := &entity.Booking{
		ID:              generateID(),
		ServiceType:     serviceType,
		UserID:          userID,
		ProviderID:      providerID,
		PickupLocation:  pickup,
		DropoffLocation: dropoff,
		Status:          "pending",
		ScheduledAt:     scheduledAt,
		CreatedAt:       time.Now(),
		UpdatedAt:       time.Now(),
	}

	ss.mu.Lock()
	ss.bookings[booking.ID] = booking
	ss.mu.Unlock()

	go ss.processBooking(ctx, booking)

	return booking, nil
}

func (ss *ServicesService) processBooking(ctx context.Context, booking *entity.Booking) {
	time.Sleep(100 * time.Millisecond)

	ss.mu.Lock()
	updated := *booking
	updated.Status = "confirmed"
	updated.UpdatedAt = time.Now()
	ss.bookings[booking.ID] = &updated
	ss.mu.Unlock()

	log.Printf("Booking %s confirmed for service %s", booking.ID, booking.ServiceType)
}

func (ss *ServicesService) GetNearbyProviders(ctx context.Context, serviceType string, location entity.Location, radiusKm float64) ([]entity.ServiceProvider, error) {
	if err := ss.validateServiceType(serviceType); err != nil {
		return nil, err
	}
	if err := ss.validateCoordinates(location); err != nil {
		return nil, err
	}
	if err := ss.validateRadius(radiusKm); err != nil {
		return nil, err
	}

	ss.mu.RLock()
	defer ss.mu.RUnlock()

	var providers []entity.ServiceProvider

	for _, provider := range ss.providers {
		if provider.ServiceType != serviceType || !provider.IsActive {
			continue
		}

		distance := calculateDistance(location.Latitude, location.Longitude,
			provider.Location.Latitude, provider.Location.Longitude)

		if distance <= radiusKm {
			providers = append(providers, *provider)
		}
	}

	return providers, nil
}

func (ss *ServicesService) CalculateRoute(pickup entity.Location, dropoff entity.Location) (float64, error) {
	if err := ss.validateCoordinates(pickup); err != nil {
		return 0, err
	}
	if err := ss.validateCoordinates(dropoff); err != nil {
		return 0, err
	}
	return calculateDistance(pickup.Latitude, pickup.Longitude,
		dropoff.Latitude, dropoff.Longitude), nil
}

func (ss *ServicesService) GetBooking(bookingID string, requestingUserID string) (*entity.Booking, error) {
	ss.mu.RLock()
	defer ss.mu.RUnlock()

	booking, exists := ss.bookings[bookingID]
	if !exists {
		return nil, errors.New("booking not found")
	}
	if booking.UserID != requestingUserID {
		return nil, ErrUnauthorized
	}
	return booking, nil
}

func (ss *ServicesService) ListBookings(ctx context.Context, userID string) []*entity.Booking {
	ss.mu.RLock()
	defer ss.mu.RUnlock()

	bookings := make([]*entity.Booking, 0)
	for _, booking := range ss.bookings {
		if booking.UserID == userID {
			bookings = append(bookings, booking)
		}
	}
	return bookings
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
	return time.Now().Format("20060102150405")
}
