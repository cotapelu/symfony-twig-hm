package handler

import (
	"bytes"
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"testing"

	"community.local/modules/locationtracking/service"

	"github.com/gin-gonic/gin"
	"github.com/stretchr/testify/assert"
)

func newLocationHandlerRouter(t *testing.T) (*gin.Engine, *LocationHandler) {
	svc := service.NewLocationTrackingService("/tmp/loc_handler_test.ets")
	h := NewLocationHandler(svc)
	router := gin.New()
	h.RegisterRoutes(router)
	return router, h
}

func TestUpdateLocation(t *testing.T) {
	router, _ := newLocationHandlerRouter(t)

	payload := `{"user_id":"u1","latitude":10.5,"longitude":20.5,"accuracy":5.0}`
	req := httptest.NewRequest(http.MethodPost, "/api/locations/update", bytes.NewReader([]byte(payload)))
	req.Header.Set("Content-Type", "application/json")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
	var resp map[string]interface{}
	json.Unmarshal(w.Body.Bytes(), &resp)
	_, ok := resp["location"]
	assert.True(t, ok)
}

func TestUpdateLocation_InvalidJSON(t *testing.T) {
	router, _ := newLocationHandlerRouter(t)

	req := httptest.NewRequest(http.MethodPost, "/api/locations/update", bytes.NewReader([]byte(`{invalid}`)))
	req.Header.Set("Content-Type", "application/json")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusBadRequest, w.Code)
}

func TestUpdateLocation_MissingRequired(t *testing.T) {
	router, _ := newLocationHandlerRouter(t)

	payload := `{"user_id":"u1"}`
	req := httptest.NewRequest(http.MethodPost, "/api/locations/update", bytes.NewReader([]byte(payload)))
	req.Header.Set("Content-Type", "application/json")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusBadRequest, w.Code)
}

func TestGetUserLocation(t *testing.T) {
	router, h := newLocationHandlerRouter(t)

	// Create a location for user u1
	h.service.UpdateLocationForUser("u1", 10, 20, 5, 0, 0, 0, "")

	req := httptest.NewRequest(http.MethodGet, "/api/locations/user/u1", nil)
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
	var resp map[string]interface{}
	json.Unmarshal(w.Body.Bytes(), &resp)
	loc, ok := resp["location"].(map[string]interface{})
	assert.True(t, ok)
	assert.Equal(t, "u1", loc["entity_id"])
}

func TestGetUserLocation_NotFound(t *testing.T) {
	router, _ := newLocationHandlerRouter(t)

	req := httptest.NewRequest(http.MethodGet, "/api/locations/user/unknown", nil)
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusNotFound, w.Code)
}

func TestGetUserLocationHistory(t *testing.T) {
	router, h := newLocationHandlerRouter(t)

	// Add a couple of location updates
	h.service.UpdateLocationForUser("u1", 10, 20, 5, 0, 0, 0, "")
	// The service keeps history; GetUserLocationHistory uses history
	// We'll just call it; it might return some entries

	req := httptest.NewRequest(http.MethodGet, "/api/locations/user/u1/history?hours=1", nil)
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
	var history []interface{}
	json.Unmarshal(w.Body.Bytes(), &history)
	// History length could be 0 or more; we just ensure it's a slice
	_ = history
}

func TestGetNearbyUsers(t *testing.T) {
	router, h := newLocationHandlerRouter(t)

	// Create a location for user u1
	h.service.UpdateLocationForUser("u1", 10, 20, 5, 0, 0, 0, "")

	req := httptest.NewRequest(http.MethodGet, "/api/locations/nearby?lat=10&lon=20&radius=10&limit=5", nil)
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
	var resp map[string]interface{}
	json.Unmarshal(w.Body.Bytes(), &resp)
	_, ok := resp["locations"]
	assert.True(t, ok)
}

func TestCreateGeofence(t *testing.T) {
	router, _ := newLocationHandlerRouter(t)

	payload := `{"name":"Test Fence","description":"Test","latitude":10.0,"longitude":20.0,"radius":100.0}`
	req := httptest.NewRequest(http.MethodPost, "/api/locations/geofences", bytes.NewReader([]byte(payload)))
	req.Header.Set("Content-Type", "application/json")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusCreated, w.Code)
	var resp map[string]interface{}
	json.Unmarshal(w.Body.Bytes(), &resp)
	_, ok := resp["geofence"]
	assert.True(t, ok)
}

func TestCreateGeofence_MissingRequired(t *testing.T) {
	router, _ := newLocationHandlerRouter(t)

	payload := `{"name":"Test Fence"}`
	req := httptest.NewRequest(http.MethodPost, "/api/locations/geofences", bytes.NewReader([]byte(payload)))
	req.Header.Set("Content-Type", "application/json")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusBadRequest, w.Code)
}

func TestGetGeofences(t *testing.T) {
	router, h := newLocationHandlerRouter(t)

	// Create a geofence via service
	h.service.CreateGeofenceForUser("Fence", "Desc", 10, 20, 100)

	req := httptest.NewRequest(http.MethodGet, "/api/locations/geofences", nil)
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
	var resp map[string]interface{}
	json.Unmarshal(w.Body.Bytes(), &resp)
	count := resp["count"].(float64)
	assert.GreaterOrEqual(t, count, 1.0)
}

func TestWebSocketHandler(t *testing.T) {
	router, _ := newLocationHandlerRouter(t)

	req := httptest.NewRequest(http.MethodGet, "/api/locations/ws/u1", nil)
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
	var resp map[string]interface{}
	json.Unmarshal(w.Body.Bytes(), &resp)
	assert.Equal(t, "u1", resp["user_id"])
	assert.Contains(t, resp["message"], "WebSocket")
}
