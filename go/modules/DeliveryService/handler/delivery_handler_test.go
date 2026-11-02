package handler

import (
	"bytes"
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"testing"

	"github.com/gin-gonic/gin"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/mock"

	"community.local/modules/DeliveryService/entity"
	"community.local/modules/DeliveryService/service"
)

// MockDeliveryService implements DeliveryService
type MockDeliveryService struct {
	mock.Mock
}

func (m *MockDeliveryService) SetOnline(userID string, lat, lng float64) (*entity.DeliveryPerson, error) {
	args := m.Called(userID, lat, lng)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).(*entity.DeliveryPerson), args.Error(1)
}

func (m *MockDeliveryService) SetOffline(userID string) {
	m.Called(userID)
}

func (m *MockDeliveryService) UpdateLocation(userID string, lat, lng float64) {
	m.Called(userID, lat, lng)
}

func (m *MockDeliveryService) FindNearbyDeliveryPersons(lat, lng, radius float64) []entity.DeliveryPerson {
	args := m.Called(lat, lng, radius)
	return args.Get(0).([]entity.DeliveryPerson)
}

func (m *MockDeliveryService) AssignOrder(orderID string, restLat, restLng, custLat, custLng float64, custAddr, restAddr string) (*entity.DeliveryOrder, error) {
	args := m.Called(orderID, restLat, restLng, custLat, custLng, custAddr, restAddr)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).(*entity.DeliveryOrder), args.Error(1)
}

func (m *MockDeliveryService) StartDelivery(orderID string) (*entity.DeliveryOrder, error) {
	args := m.Called(orderID)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).(*entity.DeliveryOrder), args.Error(1)
}

func (m *MockDeliveryService) CompleteDelivery(orderID string) (*entity.DeliveryOrder, error) {
	args := m.Called(orderID)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).(*entity.DeliveryOrder), args.Error(1)
}

func (m *MockDeliveryService) GetDeliveryOrder(orderID string) (*entity.DeliveryOrder, error) {
	args := m.Called(orderID)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).(*entity.DeliveryOrder), args.Error(1)
}

func (m *MockDeliveryService) GetActiveDeliveries(deliveryPersonID string) []entity.DeliveryOrder {
	args := m.Called(deliveryPersonID)
	return args.Get(0).([]entity.DeliveryOrder)
}

func TestNewDeliveryHandler(t *testing.T) {
	h := NewDeliveryHandler(&service.DeliveryService{})
	assert.NotNil(t, h)
}

func TestSetOnline_Unauthorized(t *testing.T) {
	gin.SetMode(gin.TestMode)
	
	srv := &service.DeliveryService{}
	h := NewDeliveryHandler(srv)
	
	router := gin.New()
	router.POST("/api/delivery/online", h.SetOnline)
	
	reqBody := SetOnlineRequest{
		UserID:    "user123",
		Latitude:  10.0,
		Longitude: 100.0,
	}
	body, _ := json.Marshal(reqBody)
	
	req := httptest.NewRequest("POST", "/api/delivery/online", bytes.NewReader(body))
	req.Header.Set("Content-Type", "application/json")
	// No X-User-ID header = unauthorized
	
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)
	
	assert.Equal(t, http.StatusUnauthorized, w.Code)
}

func TestSetOnline_BadRequest(t *testing.T) {
	gin.SetMode(gin.TestMode)
	
	srv := &service.DeliveryService{}
	h := NewDeliveryHandler(srv)
	
	router := gin.New()
	router.POST("/api/delivery/online", h.SetOnline)
	
	req := httptest.NewRequest("POST", "/api/delivery/online", bytes.NewReader([]byte{}))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("X-User-ID", "user123")
	
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)
	
	assert.Equal(t, http.StatusBadRequest, w.Code)
}

func TestSetOffline_Unauthorized(t *testing.T) {
	gin.SetMode(gin.TestMode)
	
	srv := &service.DeliveryService{}
	h := NewDeliveryHandler(srv)
	
	router := gin.New()
	router.POST("/api/delivery/offline", h.SetOffline)
	
	reqBody := SetOfflineRequest{UserID: "user123"}
	body, _ := json.Marshal(reqBody)
	
	req := httptest.NewRequest("POST", "/api/delivery/offline", bytes.NewReader(body))
	req.Header.Set("Content-Type", "application/json")
	// No X-User-ID header
	
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)
	
	assert.Equal(t, http.StatusUnauthorized, w.Code)
}

func TestGetNearbyDeliveryPersons_Unauthorized(t *testing.T) {
	gin.SetMode(gin.TestMode)
	
	srv := &service.DeliveryService{}
	h := NewDeliveryHandler(srv)
	
	router := gin.New()
	router.GET("/api/delivery/nearby", h.GetNearbyDeliveryPersons)
	
	req := httptest.NewRequest("GET", "/api/delivery/nearby?latitude=10&longitude=100&radius=5", nil)
	// No X-User-ID header
	
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)
	
	assert.Equal(t, http.StatusUnauthorized, w.Code)
}

func TestAssignOrder_Unauthorized(t *testing.T) {
	gin.SetMode(gin.TestMode)
	
	srv := &service.DeliveryService{}
	h := NewDeliveryHandler(srv)
	
	router := gin.New()
	router.POST("/api/delivery/assign", h.AssignOrder)
	
	reqBody := AssignOrderRequest{
		OrderID:        "order123",
		RestaurantLat:  10.0,
		RestaurantLng:  100.0,
		CustomerLat:    11.0,
		CustomerLng:    101.0,
	}
	body, _ := json.Marshal(reqBody)
	
	req := httptest.NewRequest("POST", "/api/delivery/assign", bytes.NewReader(body))
	req.Header.Set("Content-Type", "application/json")
	// No X-User-Role header (need ADMIN)
	
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)
	
	assert.Equal(t, http.StatusForbidden, w.Code)
}

func TestStartDelivery_Unauthorized(t *testing.T) {
	gin.SetMode(gin.TestMode)
	
	srv := &service.DeliveryService{}
	h := NewDeliveryHandler(srv)
	
	router := gin.New()
	router.POST("/api/delivery/start", h.StartDelivery)
	
	reqBody := StartDeliveryRequest{OrderID: "order123"}
	body, _ := json.Marshal(reqBody)
	
	req := httptest.NewRequest("POST", "/api/delivery/start", bytes.NewReader(body))
	req.Header.Set("Content-Type", "application/json")
	// No X-User-ID header
	
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)
	
	assert.Equal(t, http.StatusUnauthorized, w.Code)
}

func TestCompleteDelivery_Unauthorized(t *testing.T) {
	gin.SetMode(gin.TestMode)
	
	srv := &service.DeliveryService{}
	h := NewDeliveryHandler(srv)
	
	router := gin.New()
	router.POST("/api/delivery/complete", h.CompleteDelivery)
	
	reqBody := CompleteDeliveryRequest{OrderID: "order123"}
	body, _ := json.Marshal(reqBody)
	
	req := httptest.NewRequest("POST", "/api/delivery/complete", bytes.NewReader(body))
	req.Header.Set("Content-Type", "application/json")
	// No X-User-ID header
	
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)
	
	assert.Equal(t, http.StatusUnauthorized, w.Code)
}

func TestGetStats_Unauthorized(t *testing.T) {
	gin.SetMode(gin.TestMode)
	
	srv := &service.DeliveryService{}
	h := NewDeliveryHandler(srv)
	
	router := gin.New()
	router.GET("/api/delivery/stats", h.GetStats)
	// No X-User-Role header (need ADMIN)
	
	req := httptest.NewRequest("GET", "/api/delivery/stats", nil)
	
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)
	
	assert.Equal(t, http.StatusForbidden, w.Code)
}

func TestValidateCoordinate(t *testing.T) {
	h := DeliveryHandler{}
	
	// Valid
	err := h.validateCoordinate(0, 0)
	assert.Empty(t, err)
	
	err = h.validateCoordinate(90, 180)
	assert.Empty(t, err)
	
	err = h.validateCoordinate(-90, -180)
	assert.Empty(t, err)
	
	// Invalid latitude
	err = h.validateCoordinate(100, 0)
	assert.NotEmpty(t, err)
	
	// Invalid longitude
	err = h.validateCoordinate(0, 200)
	assert.NotEmpty(t, err)
}

func TestValidateRadius(t *testing.T) {
	h := DeliveryHandler{}
	
	// Valid
	err := h.validateRadius(0)
	assert.Empty(t, err)
	
	err = h.validateRadius(25)
	assert.Empty(t, err)
	
	err = h.validateRadius(50)
	assert.Empty(t, err)
	
	// Invalid
	err = h.validateRadius(-1)
	assert.NotEmpty(t, err)
	
	err = h.validateRadius(51)
	assert.NotEmpty(t, err)
}

func TestValidateUserID(t *testing.T) {
	h := DeliveryHandler{}
	
	// Valid
	err := h.validateUserID("user123")
	assert.Empty(t, err)
	
	// Empty
	err = h.validateUserID("")
	assert.NotEmpty(t, err)
	
	// Too long
	longID := ""
	for i := 0; i < 101; i++ {
		longID += "a"
	}
	err = h.validateUserID(longID)
	assert.NotEmpty(t, err)
}

func TestGetNearbyDeliveryPersons_Admin(t *testing.T) {
	gin.SetMode(gin.TestMode)
	
	srv := &service.DeliveryService{}
	h := NewDeliveryHandler(srv)
	
	router := gin.New()
	router.GET("/api/delivery/nearby", h.GetNearbyDeliveryPersons)
	
	req := httptest.NewRequest("GET", "/api/delivery/nearby?latitude=10&longitude=100&radius=5", nil)
	req.Header.Set("X-User-ID", "user123")
	req.Header.Set("X-User-Role", "ADMIN")
	
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)
	
	assert.Equal(t, http.StatusOK, w.Code)
}

func TestHealthCheck(t *testing.T) {
	gin.SetMode(gin.TestMode)
	
	srv := &service.DeliveryService{}
	h := NewDeliveryHandler(srv)
	
	router := gin.New()
	router.GET("/health", h.HealthCheck)
	
	req := httptest.NewRequest("GET", "/health", nil)
	
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)
	
	assert.Equal(t, http.StatusOK, w.Code)
}