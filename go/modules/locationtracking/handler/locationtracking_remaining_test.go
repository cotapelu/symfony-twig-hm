package handler

import (
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

func TestLocationTrackingHandler_GetLocationPattern(t *testing.T) {
	gin.SetMode(gin.TestMode)

	tmpFile := "/tmp/location_pattern_test.ets"
	defer os.Remove(tmpFile)

	svc := service.NewLocationTrackingService(tmpFile)
	// Create some location history for entity "user1"
	svc.UpdateLocation("user1", "user", 10.0, 20.0, nil)
	// second location at different time
	time.Sleep(10 * time.Millisecond)
	svc.UpdateLocation("user1", "user", 10.1, 20.1, nil)

	h := NewLocationTrackingHandler(svc)

	router := gin.New()
	router.Use(func(c *gin.Context) {
		if userID := c.GetHeader("X-User-ID"); userID != "" {
			c.Set("user_id", userID)
		}
		c.Next()
	})
	h.RegisterRoutes(router)

	req := httptest.NewRequest(http.MethodGet, "/api/locationtracking/pattern/user1?time_period=daily", nil)
	req.Header.Set("X-User-ID", "anyuser")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
	var resp map[string]interface{}
	json.Unmarshal(w.Body.Bytes(), &resp)
	assert.Equal(t, "user1", resp["entity_id"])
	assert.Equal(t, "daily", resp["time_period"])
	pattern := resp["pattern"].(map[string]interface{})
	// Verify expected keys in pattern
	assert.Contains(t, pattern, "time_period")
	assert.Contains(t, pattern, "hourly_activity")
	assert.Contains(t, pattern, "total_distance_km")
	assert.Contains(t, pattern, "entry_count")
}

func TestLocationTrackingHandler_GetETA(t *testing.T) {
	gin.SetMode(gin.TestMode)

	tmpFile := "/tmp/location_eta_test.ets"
	defer os.Remove(tmpFile)

	svc := service.NewLocationTrackingService(tmpFile)
	svc.UpdateLocation("user1", "user", 10.0, 20.0, nil)

	h := NewLocationTrackingHandler(svc)
	router := gin.New()
	router.Use(func(c *gin.Context) {
		if userID := c.GetHeader("X-User-ID"); userID != "" {
			c.Set("user_id", userID)
		}
		c.Next()
	})
	h.RegisterRoutes(router)

	// valid ETA request
	req := httptest.NewRequest(http.MethodGet, "/api/locationtracking/eta/user1?dest_lat=10.001&dest_lon=20.001&speed=50", nil)
	req.Header.Set("X-User-ID", "anyuser")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
	var resp map[string]interface{}
	json.Unmarshal(w.Body.Bytes(), &resp)
	assert.Equal(t, "user1", resp["entity_id"])
	eta, ok := resp["eta"].(map[string]interface{})
	assert.True(t, ok)
	assert.Contains(t, eta, "distance_meters")
	assert.Contains(t, eta, "eta_seconds")
	assert.Contains(t, eta, "eta_minutes")
}

func TestLocationTrackingHandler_GetETA_LocationNotFound(t *testing.T) {
	gin.SetMode(gin.TestMode)

	tmpFile := "/tmp/location_eta_notfound.ets"
	defer os.Remove(tmpFile)

	svc := service.NewLocationTrackingService(tmpFile)
	// no location set

	h := NewLocationTrackingHandler(svc)
	router := gin.New()
	router.Use(func(c *gin.Context) {
		if userID := c.GetHeader("X-User-ID"); userID != "" {
			c.Set("user_id", userID)
		}
		c.Next()
	})
	h.RegisterRoutes(router)

	req := httptest.NewRequest(http.MethodGet, "/api/locationtracking/eta/nonexistent?dest_lat=10&dest_lon=20&speed=50", nil)
	req.Header.Set("X-User-ID", "anyuser")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusNotFound, w.Code)
}

func TestLocationTrackingHandler_GetLocationsByTimeRange_InvalidTimeFormat(t *testing.T) {
	gin.SetMode(gin.TestMode)

	tmpFile := "/tmp/location_timerange_invalid.ets"
	defer os.Remove(tmpFile)

	svc := service.NewLocationTrackingService(tmpFile)
	svc.UpdateLocation("user1", "user", 10.0, 20.0, nil)

	h := NewLocationTrackingHandler(svc)
	router := gin.New()
	router.Use(func(c *gin.Context) {
		if userID := c.GetHeader("X-User-ID"); userID != "" {
			c.Set("user_id", userID)
		}
		c.Next()
	})
	h.RegisterRoutes(router)

	// send invalid RFC3339 times
	req := httptest.NewRequest(http.MethodGet, "/api/locationtracking/timerange?latitude=10&longitude=20&start_time=invalid&end_time=also-invalid", nil)
	req.Header.Set("X-User-ID", "anyuser")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusBadRequest, w.Code)
}

func TestLocationTrackingHandler_GetFrequentLocations_WithHours(t *testing.T) {
	gin.SetMode(gin.TestMode)

	tmpFile := "/tmp/location_frequent_hours.ets"
	defer os.Remove(tmpFile)

	svc := service.NewLocationTrackingService(tmpFile)
	// Create multiple locations to generate clusters: need at least 3 visits to same location to be considered frequent
	svc.UpdateLocation("user1", "user", 10.0, 20.0, nil)
	time.Sleep(10 * time.Millisecond)
	svc.UpdateLocation("user1", "user", 10.0, 20.0, nil)
	time.Sleep(10 * time.Millisecond)
	svc.UpdateLocation("user1", "user", 10.0, 20.0, nil)

	h := NewLocationTrackingHandler(svc)
	router := gin.New()
	router.Use(func(c *gin.Context) {
		if userID := c.GetHeader("X-User-ID"); userID != "" {
			c.Set("user_id", userID)
		}
		c.Next()
	})
	h.RegisterRoutes(router)

	req := httptest.NewRequest(http.MethodGet, "/api/locationtracking/frequent/user1?hours=24", nil)
	req.Header.Set("X-User-ID", "anyuser")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
	var resp map[string]interface{}
	if err := json.Unmarshal(w.Body.Bytes(), &resp); err != nil {
		t.Fatalf("json unmarshal: %v, body: %s", err, w.Body.String())
	}
	assert.Equal(t, "user1", resp["entity_id"])
	clustersVal, exists := resp["clusters"]
	if !exists {
		t.Fatalf("clusters field missing in response. body: %s", w.Body.String())
	}
	if clustersVal == nil {
		t.Fatalf("clusters is nil. response: %s", w.Body.String())
	}
	clusters, ok := clustersVal.([]interface{})
	if !ok {
		t.Fatalf("clusters type is %T, expected []interface{}", clustersVal)
	}
	_ = clusters
}
