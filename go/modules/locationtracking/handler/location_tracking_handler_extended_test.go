package handler

import (
	"bytes"
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"os"
	"testing"
	"time"

	"community.local/modules/locationtracking/service"

	"github.com/gin-gonic/gin"
	"github.com/stretchr/testify/assert"
)

func TestLocationTrackingHandler_GetLocationsByTimeRange(t *testing.T) {
	gin.SetMode(gin.TestMode)

	tmpFile := "/tmp/location_timerange_test.ets"
	defer os.Remove(tmpFile)

	svc := service.NewLocationTrackingService(tmpFile)
	h := NewLocationTrackingHandler(svc)

	router := gin.New()
	router.Use(func(c *gin.Context) {
		if userID := c.GetHeader("X-User-ID"); userID != "" {
			c.Set("user_id", userID)
		}
		c.Next()
	})
	h.RegisterRoutes(router)

	// Create a location for entity "user1"
	svc.UpdateLocation("user1", "user", 10.0, 20.0, nil)

	// Prepare query times: start=now-1h, end=now+1h
	now := time.Now()
	start := now.Add(-time.Hour).Format(time.RFC3339)
	end := now.Add(time.Hour).Format(time.RFC3339)

	req := httptest.NewRequest(http.MethodGet, "/api/locationtracking/timerange?latitude=10&longitude=20&start_time="+start+"&end_time="+end, nil)
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
	var resp map[string]interface{}
	json.Unmarshal(w.Body.Bytes(), &resp)
	_, ok := resp["locations"].([]interface{})
	assert.True(t, ok)
}

func TestLocationTrackingHandler_CreateGeofence(t *testing.T) {
	gin.SetMode(gin.TestMode)

	tmpFile := "/tmp/location_geofence_test.ets"
	defer os.Remove(tmpFile)

	svc := service.NewLocationTrackingService(tmpFile)
	h := NewLocationTrackingHandler(svc)

	router :=gin.New()
	router.Use(func(c *gin.Context) {
		if userID := c.GetHeader("X-User-ID"); userID != "" {
			c.Set("user_id", userID)
		}
		c.Next()
	})
	h.RegisterRoutes(router)

	reqBody := map[string]interface{}{
		"name":         "Test Geofence",
		"latitude":     10.0,
		"longitude":    20.0,
		"radius_meters": 100.0,
		"entity_type":  "user",
	}
	body, _ := json.Marshal(reqBody)

	req := httptest.NewRequest(http.MethodPost, "/api/locationtracking/geofence", bytes.NewReader(body))
	req.Header.Set("Content-Type", "application/json")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusCreated, w.Code)
	var resp map[string]interface{}
	json.Unmarshal(w.Body.Bytes(), &resp)
	assert.Equal(t, "geofence created", resp["status"])
	_, ok := resp["geofence"].(map[string]interface{})
	assert.True(t, ok)
}

func TestLocationTrackingHandler_GetGeofences(t *testing.T) {
	gin.SetMode(gin.TestMode)

	tmpFile := "/tmp/location_getgeofences_test.ets"
	defer os.Remove(tmpFile)

	svc := service.NewLocationTrackingService(tmpFile)
	h := NewLocationTrackingHandler(svc)

	router := gin.New()
	router.Use(func(c *gin.Context) {
		if userID := c.GetHeader("X-User-ID"); userID != "" {
			c.Set("user_id", userID)
		}
		c.Next()
	})
	h.RegisterRoutes(router)

	// First create a geofence via service directly
	svc.CreateGeofence("Test GF", 10.0, 20.0, 100.0, "user")

	// GET with entity_type=user
	req := httptest.NewRequest(http.MethodGet, "/api/locationtracking/geofences?entity_type=user", nil)
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
	var resp map[string]interface{}
	json.Unmarshal(w.Body.Bytes(), &resp)
	count := resp["count"].(float64)
	assert.GreaterOrEqual(t, count, 1.0)
	geofences, _ := resp["geofences"].([]interface{})
	assert.Len(t, geofences, 1)
}

func TestLocationTrackingHandler_CheckGeofence(t *testing.T) {
	gin.SetMode(gin.TestMode)

	tmpFile := "/tmp/location_checkgeofence_test.ets"
	defer os.Remove(tmpFile)

	svc := service.NewLocationTrackingService(tmpFile)
	h := NewLocationTrackingHandler(svc)

	router := gin.New()
	router.Use(func(c *gin.Context) {
		if userID := c.GetHeader("X-User-ID"); userID != "" {
			c.Set("user_id", userID)
		}
		c.Next()
	})
	h.RegisterRoutes(router)

	// Create a geofence covering (10,20) radius 100m
	svc.CreateGeofence("Check GF", 10.0, 20.0, 100.0, "user")

	// Entity "user1" location inside geofence
	svc.UpdateLocation("user1", "user", 10.0, 20.0, nil)

	// Check geofence for entity user1
	req := httptest.NewRequest(http.MethodGet, "/api/locationtracking/geofence/check/user1", nil)
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
	var resp map[string]interface{}
	json.Unmarshal(w.Body.Bytes(), &resp)
	entityID := resp["entity_id"].(string)
	assert.Equal(t, "user1", entityID)
	geofences, _ := resp["geofences"].([]interface{})
	// Expect at least one geofence containing the point
	assert.Len(t, geofences, 1)
}

// TestLocationTrackingHandler_GetLocationsByTimeRange_errors tests error handling (missing params)
func TestLocationTrackingHandler_GetLocationsByTimeRange_errors(t *testing.T) {
	gin.SetMode(gin.TestMode)

	tmpFile := "/tmp/location_timerange_err_test.ets"
	defer os.Remove(tmpFile)

	svc := service.NewLocationTrackingService(tmpFile)
	h := NewLocationTrackingHandler(svc)

	router := gin.New()
	router.Use(func(c *gin.Context) {
		if userID := c.GetHeader("X-User-ID"); userID != "" {
			c.Set("user_id", userID)
		}
		c.Next()
	})
	h.RegisterRoutes(router)

	// Missing start_time or end_time -> 400
	req := httptest.NewRequest(http.MethodGet, "/api/locationtracking/timerange?latitude=10&longitude=20", nil)
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)
	assert.Equal(t, http.StatusBadRequest, w.Code)
}
