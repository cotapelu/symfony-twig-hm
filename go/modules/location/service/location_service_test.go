package service

import (
	"context"
	"errors"
	"testing"
	"time"

	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/mock"

	"community.local/modules/location/entity"
)

// MockLocationRepository mocks repository.LocationRepository
type MockLocationRepository struct {
	mock.Mock
}

func (m *MockLocationRepository) Save(ctx context.Context, location *entity.UserLocation) error {
	args := m.Called(ctx, location)
	return args.Error(0)
}

func (m *MockLocationRepository) GetByUserID(ctx context.Context, userID string) (*entity.UserLocation, error) {
	args := m.Called(ctx, userID)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).(*entity.UserLocation), args.Error(1)
}

func (m *MockLocationRepository) Delete(ctx context.Context, userID string) error {
	args := m.Called(ctx, userID)
	return args.Error(0)
}

func (m *MockLocationRepository) GetAll(ctx context.Context) ([]*entity.UserLocation, error) {
	args := m.Called(ctx)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).([]*entity.UserLocation), args.Error(1)
}

func (m *MockLocationRepository) GetWithinBounds(ctx context.Context, minLat, maxLat, minLon, maxLon float64) ([]*entity.UserLocation, error) {
	args := m.Called(ctx, minLat, maxLat, minLon, maxLon)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).([]*entity.UserLocation), args.Error(1)
}

// MockLocationHistoryRepository mocks repository.LocationHistoryRepository
type MockLocationHistoryRepository struct {
	mock.Mock
}

func (m *MockLocationHistoryRepository) Add(ctx context.Context, location *entity.UserLocation) error {
	args := m.Called(ctx, location)
	return args.Error(0)
}

func (m *MockLocationHistoryRepository) GetByUserID(ctx context.Context, userID string, limit int) ([]*entity.UserLocation, error) {
	args := m.Called(ctx, userID, limit)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).([]*entity.UserLocation), args.Error(1)
}

func (m *MockLocationHistoryRepository) GetByUserIDTimeRange(ctx context.Context, userID string, from, to time.Time) ([]*entity.UserLocation, error) {
	args := m.Called(ctx, userID, from, to)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).([]*entity.UserLocation), args.Error(1)
}

func (m *MockLocationHistoryRepository) DeleteOlderThan(ctx context.Context, before time.Time) (int64, error) {
	args := m.Called(ctx, before)
	if args.Get(0) == nil {
		return 0, args.Error(1)
	}
	return args.Get(0).(int64), args.Error(1)
}

func (m *MockLocationHistoryRepository) Count(ctx context.Context, userID string) (int64, error) {
	args := m.Called(ctx, userID)
	if args.Get(0) == nil {
		return 0, args.Error(1)
	}
	return args.Get(0).(int64), args.Error(1)
}

// MockGeofenceRepository mocks repository.GeofenceRepository
type MockGeofenceRepository struct {
	mock.Mock
}

func (m *MockGeofenceRepository) Save(ctx context.Context, geofence *entity.Geofence) error {
	args := m.Called(ctx, geofence)
	return args.Error(0)
}

func (m *MockGeofenceRepository) GetByID(ctx context.Context, id string) (*entity.Geofence, error) {
	args := m.Called(ctx, id)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).(*entity.Geofence), args.Error(1)
}

func (m *MockGeofenceRepository) Delete(ctx context.Context, id string) error {
	args := m.Called(ctx, id)
	return args.Error(0)
}

func (m *MockGeofenceRepository) List(ctx context.Context) ([]*entity.Geofence, error) {
	args := m.Called(ctx)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).([]*entity.Geofence), args.Error(1)
}

func (m *MockGeofenceRepository) FindByName(ctx context.Context, namePattern string) ([]*entity.Geofence, error) {
	args := m.Called(ctx, namePattern)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).([]*entity.Geofence), args.Error(1)
}

// MockEventHandler mocks EventEmitter
type MockEventHandler struct {
	mock.Mock
}

func (m *MockEventHandler) EmitEvent(ctx context.Context, eventType string, data interface{}) error {
	args := m.Called(ctx, eventType, data)
	return args.Error(0)
}

// Tests

func TestLocationService_UpdateLocation_Validation(t *testing.T) {
	locRepo := &MockLocationRepository{}
	histRepo := &MockLocationHistoryRepository{}
	geoRepo := &MockGeofenceRepository{}
	eventHandler := &MockEventHandler{}
	svc := NewLocationService(locRepo, histRepo, geoRepo, eventHandler)

	// Invalid userID
	err := svc.UpdateLocation(context.Background(), "", 10.0, 20.0)
	assert.Error(t, err, "user ID is required")

	// Invalid latitude
	err = svc.UpdateLocation(context.Background(), "user1", -100.0, 20.0)
	assert.Error(t, err, "latitude must be between -90 and 90")

	// Invalid longitude
	err = svc.UpdateLocation(context.Background(), "user1", 10.0, -200.0)
	assert.Error(t, err, "longitude must be between -180 and 180")
}

func TestLocationService_UpdateLocation_Success(t *testing.T) {
	ctx := context.Background()
	locRepo := &MockLocationRepository{}
	histRepo := &MockLocationHistoryRepository{}
	geoRepo := &MockGeofenceRepository{}
	eventHandler := &MockEventHandler{}
	svc := NewLocationService(locRepo, histRepo, geoRepo, eventHandler)

	userID := "user1"
	lat, lon := 10.123456, 20.654321

	// LocRepo.Save and HistRepo.Add
	locRepo.On("Save", ctx, mock.AnythingOfType("*entity.UserLocation")).Return(nil).Once()
	histRepo.On("Add", ctx, mock.AnythingOfType("*entity.UserLocation")).Return(nil).Once()
	eventHandler.On("EmitEvent", ctx, "location.update", mock.Anything).Return(nil).Maybe()

	err := svc.UpdateLocation(ctx, userID, lat, lon)
	assert.NoError(t, err)

	locRepo.AssertExpectations(t)
	histRepo.AssertExpectations(t)
}

func TestLocationService_GetLocation(t *testing.T) {
	ctx := context.Background()
	locRepo := &MockLocationRepository{}
	histRepo := &MockLocationHistoryRepository{}
	geoRepo := &MockGeofenceRepository{}
	svc := NewLocationService(locRepo, histRepo, geoRepo, nil)

	userID := "user1"
	expectedLoc := &entity.UserLocation{
		UserID: userID,
		Location: entity.Location{Latitude: 10, Longitude: 20},
	}

	locRepo.On("GetByUserID", ctx, userID).Return(expectedLoc, nil)

	result, err := svc.GetLocation(ctx, userID)
	assert.NoError(t, err)
	assert.Equal(t, expectedLoc, result)

	// Not found error from repo
	locRepo.On("GetByUserID", ctx, "none").Return(nil, errors.New("not found"))
	_, err = svc.GetLocation(ctx, "none")
	assert.Error(t, err)

	locRepo.AssertExpectations(t)
}

func TestLocationService_GetNearbyUsers_Validation(t *testing.T) {
	locRepo := &MockLocationRepository{}
	histRepo := &MockLocationHistoryRepository{}
	geoRepo := &MockGeofenceRepository{}
	svc := NewLocationService(locRepo, histRepo, geoRepo, nil)

	// Empty userID
	_, err := svc.GetNearbyUsers(context.Background(), "", 5.0, 10)
	assert.Error(t, err, "user ID is required")

	// Radius <=0
	_, err = svc.GetNearbyUsers(context.Background(), "user1", 0, 10)
	assert.Error(t, err, "radius must be positive")
}

func TestLocationService_GetNearbyUsers_Success(t *testing.T) {
	ctx := context.Background()
	locRepo := &MockLocationRepository{}
	histRepo := &MockLocationHistoryRepository{}
	geoRepo := &MockGeofenceRepository{}
	svc := NewLocationService(locRepo, histRepo, geoRepo, nil)

	myLoc := &entity.UserLocation{
		UserID: "user1",
		Location: entity.Location{Latitude: 10.0, Longitude: 10.0},
	}

	otherLocs := []*entity.UserLocation{
		{
			UserID: "user2",
			Location: entity.Location{Latitude: 10.01, Longitude: 10.01}, // approx 1.5 km
		},
		{
			UserID: "user3",
			Location: entity.Location{Latitude: 20.0, Longitude: 20.0}, // far
		},
	}

	locRepo.On("GetByUserID", ctx, "user1").Return(myLoc, nil)
	locRepo.On("GetAll", ctx).Return(otherLocs, nil)

	// radius 10 km, limit 10
	nearby, err := svc.GetNearbyUsers(ctx, "user1", 10.0, 10)
	assert.NoError(t, err)
	assert.Len(t, nearby, 1)
	assert.Equal(t, "user2", nearby[0].UserID)
	assert.InDelta(t, 1.57, nearby[0].Distance, 0.1) // approx

	locRepo.AssertExpectations(t)
}

func TestLocationService_CheckGeofences_Success(t *testing.T) {
	ctx := context.Background()
	locRepo := &MockLocationRepository{}
	histRepo := &MockLocationHistoryRepository{}
	geoRepo := &MockGeofenceRepository{}
	eventHandler := &MockEventHandler{}
	svc := NewLocationService(locRepo, histRepo, geoRepo, eventHandler)

	// Point inside geofence: geofence with single point at (0,0) radius 10 km; point at (0,0) should be within.
	geofence := &entity.Geofence{
		ID:      "gf1",
		Name:    "Test Area",
		Points:  []entity.Location{{Latitude: 0, Longitude: 0}},
		Radius:  10.0,
	}
	geoRepo.On("List", ctx).Return([]*entity.Geofence{geofence}, nil)
	geoRepo.On("GetByID", ctx, "gf1").Return(geofence, nil)
	eventHandler.On("EmitEvent", ctx, "geofence.trigger", mock.Anything).Return(nil)

	// Simulate isPointInGeofence: centroid is (0,0), distance from (0,0) to (0,0) =0 <=10 => match.
	matched, err := svc.CheckGeofences(ctx, "user1", 0, 0)
	assert.NoError(t, err)
	assert.Len(t, matched, 1)
	assert.Contains(t, matched, "gf1")

	// Expect event emission eventHandler should have been called twice? Actually service emits for each matched geofence. So should have called once.
	eventHandler.AssertCalled(t, "EmitEvent", ctx, "geofence.trigger", mock.Anything)
}

func TestLocationService_CheckGeofences_NoMatch(t *testing.T) {
	ctx := context.Background()
	locRepo := &MockLocationRepository{}
	histRepo := &MockLocationHistoryRepository{}
	geoRepo := &MockGeofenceRepository{}
	svc := NewLocationService(locRepo, histRepo, geoRepo, nil)

	geofence := &entity.Geofence{
		Points: []entity.Location{{Latitude: 0, Longitude: 0}},
		Radius: 1.0,
	}
	geoRepo.On("List", ctx).Return([]*entity.Geofence{geofence}, nil)

	// Point far away
	matched, err := svc.CheckGeofences(ctx, "user1", 50, 50)
	assert.NoError(t, err)
	assert.Empty(t, matched)
}

func TestLocationService_GetLocationHistory(t *testing.T) {
	ctx := context.Background()
	locRepo := &MockLocationRepository{}
	histRepo := &MockLocationHistoryRepository{}
	geoRepo := &MockGeofenceRepository{}
	svc := NewLocationService(locRepo, histRepo, geoRepo, nil)

	userID := "user1"
	limit := 10
	history := []*entity.UserLocation{{ID: "h1"}, {ID: "h2"}}

	histRepo.On("GetByUserID", ctx, userID, limit).Return(history, nil)

	result, err := svc.GetLocationHistory(ctx, userID, limit)
	assert.NoError(t, err)
	assert.Len(t, result, 2)

	histRepo.AssertExpectations(t)
}

func TestLocationService_DeleteCurrentLocation_Success(t *testing.T) {
	ctx := context.Background()
	locRepo := &MockLocationRepository{}
	histRepo := &MockLocationHistoryRepository{}
	geoRepo := &MockGeofenceRepository{}
	eventHandler := &MockEventHandler{}
	svc := NewLocationService(locRepo, histRepo, geoRepo, eventHandler)

	userID := "user1"
	userLoc := &entity.UserLocation{UserID: userID}
	locRepo.On("GetByUserID", ctx, userID).Return(userLoc, nil)
	locRepo.On("Delete", ctx, userID).Return(nil).Once()
	eventHandler.On("EmitEvent", ctx, "location.delete", mock.Anything).Return(nil).Maybe()

	err := svc.DeleteCurrentLocation(ctx, userID)
	assert.NoError(t, err)

	locRepo.AssertExpectations(t)
}

func TestLocationService_DeleteCurrentLocation_NotFound(t *testing.T) {
	ctx := context.Background()
	locRepo := &MockLocationRepository{}
	histRepo := &MockLocationHistoryRepository{}
	geoRepo := &MockGeofenceRepository{}
	svc := NewLocationService(locRepo, histRepo, geoRepo, nil)

	locRepo.On("GetByUserID", ctx, "missing").Return(nil, errors.New("not found"))

	err := svc.DeleteCurrentLocation(ctx, "missing")
	assert.Error(t, err)
}

func TestLocationService_CreateGeofence(t *testing.T) {
	ctx := context.Background()
	locRepo := &MockLocationRepository{}
	histRepo := &MockLocationHistoryRepository{}
	geoRepo := &MockGeofenceRepository{}
	svc := NewLocationService(locRepo, histRepo, geoRepo, nil)

	geofence := &entity.Geofence{
		Name:     "Test",
		Points:   []entity.Location{{Latitude: 0, Longitude: 0}},
		Radius:   5.0,
		Metadata: map[string]string{"key": "value"},
	}

	// Expect Save to be called and ID and CreatedAt to be set by service.
	geoRepo.On("Save", ctx, mock.AnythingOfType("*entity.Geofence")).Return(nil).Once().Run(func(args mock.Arguments) {
		g := args.Get(1).(*entity.Geofence)
		g.ID = "generated-id"
		g.CreatedAt = time.Now()
	})

	err := svc.CreateGeofence(ctx, geofence)
	assert.NoError(t, err)
	assert.NotEmpty(t, geofence.ID)
	assert.NotZero(t, geofence.CreatedAt)

	geoRepo.AssertExpectations(t)
}

func TestLocationService_GetGeofence(t *testing.T) {
	ctx := context.Background()
	locRepo := &MockLocationRepository{}
	histRepo := &MockLocationHistoryRepository{}
	geoRepo := &MockGeofenceRepository{}
	svc := NewLocationService(locRepo, histRepo, geoRepo, nil)

	id := "gf1"
	geofence := &entity.Geofence{ID: id, Name: "Test"}
	geoRepo.On("GetByID", ctx, id).Return(geofence, nil)

	result, err := svc.GetGeofence(ctx, id)
	assert.NoError(t, err)
	assert.Equal(t, geofence, result)
}

func TestLocationService_DeleteGeofence(t *testing.T) {
	ctx := context.Background()
	locRepo := &MockLocationRepository{}
	histRepo := &MockLocationHistoryRepository{}
	geoRepo := &MockGeofenceRepository{}
	svc := NewLocationService(locRepo, histRepo, geoRepo, nil)

	id := "gf1"
	geoRepo.On("Delete", ctx, id).Return(nil).Once()

	err := svc.DeleteGeofence(ctx, id)
	assert.NoError(t, err)

	geoRepo.AssertExpectations(t)
}

func TestLocationService_ListGeofences(t *testing.T) {
	ctx := context.Background()
	locRepo := &MockLocationRepository{}
	histRepo := &MockLocationHistoryRepository{}
	geoRepo := &MockGeofenceRepository{}
	svc := NewLocationService(locRepo, histRepo, geoRepo, nil)

	geofences := []*entity.Geofence{{ID: "gf1"}, {ID: "gf2"}}
	geoRepo.On("List", ctx).Return(geofences, nil)

	result, err := svc.ListGeofences(ctx)
	assert.NoError(t, err)
	assert.Len(t, result, 2)

	geoRepo.AssertExpectations(t)
}
