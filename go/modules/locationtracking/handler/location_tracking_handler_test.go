package handler

import (
	"bytes"
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"os"
	"testing"

	"community.local/modules/locationtracking/entity"
	"community.local/modules/locationtracking/service"
	"github.com/gin-gonic/gin"
	"github.com/stretchr/testify/assert"
)

func TestLocationTrackingHandler_UpdateLocation(t *testing.T) {
	gin.SetMode(gin.TestMode)
	
	// Create temp file for service
	tmpFile := "/tmp/location_test.ets"
	defer os.Remove(tmpFile)
	
	// Create service and handler
	svc := service.NewLocationTrackingService(tmpFile)
	h := NewLocationTrackingHandler(svc)
	
	router := gin.New()
	// Add auth middleware to extract user_id from header
	router.Use(func(c *gin.Context) {
		if userID := c.GetHeader("X-User-ID"); userID != "" {
			c.Set("user_id", userID)
		}
		c.Next()
	})
	h.RegisterRoutes(router)
	
	tests := []struct {
		name       string
		request    UpdateLocationRequest
		userID     string
		wantStatus int
	}{
		{
			name: "valid location update with auth",
			request: UpdateLocationRequest{
				EntityID:   "user123",
				EntityType: "user",
				Latitude:   37.7749,
				Longitude:  -122.4194,
				Metadata:   map[string]interface{}{"battery": 80},
			},
			userID:     "user123",
			wantStatus: http.StatusOK,
		},
		{
			name: "update other user location rejected",
			request: UpdateLocationRequest{
				EntityID:   "user456",
				EntityType: "user",
				Latitude:   37.7749,
				Longitude:  -122.4194,
			},
			userID:     "user123",
			wantStatus: http.StatusForbidden,
		},
	}
	
	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			body, _ := json.Marshal(tt.request)
			req, _ := http.NewRequest(http.MethodPost, "/api/locationtracking/update", bytes.NewReader(body))
			req.Header.Set("Content-Type", "application/json")
			req.Header.Set("X-User-ID", tt.userID)
			
			w := httptest.NewRecorder()
			router.ServeHTTP(w, req)
			
			assert.Equal(t, tt.wantStatus, w.Code)
		})
	}
}

func TestLocationTrackingHandler_GetLocation(t *testing.T) {
	gin.SetMode(gin.TestMode)
	
	tmpFile := "/tmp/location_test2.ets"
	defer os.Remove(tmpFile)
	
	svc := service.NewLocationTrackingService(tmpFile)
	h := NewLocationTrackingHandler(svc)
	
	router := gin.New()
	// Add auth middleware to extract user_id from header
	router.Use(func(c *gin.Context) {
		if userID := c.GetHeader("X-User-ID"); userID != "" {
			c.Set("user_id", userID)
		}
		c.Next()
	})
	h.RegisterRoutes(router)
	
	// First update a location
	updateReq := UpdateLocationRequest{
		EntityID:   "user123",
		EntityType: "user",
		Latitude:   37.7749,
		Longitude:  -122.4194,
	}
	body, _ := json.Marshal(updateReq)
	req, _ := http.NewRequest(http.MethodPost, "/api/locationtracking/update", bytes.NewReader(body))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("X-User-ID", "user123")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)
	
	t.Run("get own location with auth", func(t *testing.T) {
		req, _ := http.NewRequest(http.MethodGet, "/api/locationtracking/user123", nil)
		req.Header.Set("X-User-ID", "user123")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		
		assert.Equal(t, http.StatusOK, w.Code)
	})
	
	t.Run("get other user location rejected", func(t *testing.T) {
		req, _ := http.NewRequest(http.MethodGet, "/api/locationtracking/user456", nil)
		req.Header.Set("X-User-ID", "user123")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		
		assert.Equal(t, http.StatusForbidden, w.Code)
	})
	
	t.Run("get location without auth rejected", func(t *testing.T) {
		req, _ := http.NewRequest(http.MethodGet, "/api/locationtracking/user123", nil)
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		
		assert.Equal(t, http.StatusUnauthorized, w.Code)
	})
}

func TestLocationTrackingHandler_ByEntityType(t *testing.T) {
	gin.SetMode(gin.TestMode)
	
	tmpFile := "/tmp/location_test3.ets"
	defer os.Remove(tmpFile)
	
	svc := service.NewLocationTrackingService(tmpFile)
	h := NewLocationTrackingHandler(svc)
	
	router := gin.New()
	h.RegisterRoutes(router)
	
	t.Run("get locations by type", func(t *testing.T) {
		req, _ := http.NewRequest(http.MethodGet, "/api/locationtracking/type/user", nil)
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		
		assert.Equal(t, http.StatusOK, w.Code)
	})
}

func TestLocationTrackingHandler_StopTracking(t *testing.T) {
	gin.SetMode(gin.TestMode)
	
	tmpFile := "/tmp/location_test4.ets"
	defer os.Remove(tmpFile)
	
	svc := service.NewLocationTrackingService(tmpFile)
	h := NewLocationTrackingHandler(svc)
	
	router := gin.New()
	// Add auth middleware to extract user_id from header
	router.Use(func(c *gin.Context) {
		if userID := c.GetHeader("X-User-ID"); userID != "" {
			c.Set("user_id", userID)
		}
		c.Next()
	})
	h.RegisterRoutes(router)
	
	// First add a location
	updateReq := UpdateLocationRequest{
		EntityID:   "user123",
		EntityType: "user",
		Latitude:   37.7749,
		Longitude:  -122.4194,
	}
	body, _ := json.Marshal(updateReq)
	req, _ := http.NewRequest(http.MethodPost, "/api/locationtracking/update", bytes.NewReader(body))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("X-User-ID", "user123")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)
	
	t.Run("stop tracking own entity", func(t *testing.T) {
		req, _ := http.NewRequest(http.MethodDelete, "/api/locationtracking/user123", nil)
		req.Header.Set("X-User-ID", "user123")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		
		assert.Equal(t, http.StatusOK, w.Code)
	})
	
	t.Run("stop tracking other entity rejected", func(t *testing.T) {
		// First create location for user456
		updateReq := UpdateLocationRequest{EntityID: "user456", EntityType: "user", Latitude: 1.0, Longitude: 1.0}
		body, _ := json.Marshal(updateReq)
		req, _ := http.NewRequest(http.MethodPost, "/api/locationtracking/update", bytes.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "user456")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		
		// Try to stop tracking as user123
		req, _ = http.NewRequest(http.MethodDelete, "/api/locationtracking/user456", nil)
		req.Header.Set("X-User-ID", "user123")
		w = httptest.NewRecorder()
		router.ServeHTTP(w, req)
		
		assert.Equal(t, http.StatusForbidden, w.Code)
	})
}

func TestLocationTrackingHandler_HistoryAndNearby(t *testing.T) {
	gin.SetMode(gin.TestMode)
	
	tmpFile := "/tmp/location_test5.ets"
	defer os.Remove(tmpFile)
	
	svc := service.NewLocationTrackingService(tmpFile)
	h := NewLocationTrackingHandler(svc)
	
	router := gin.New()
	h.RegisterRoutes(router)
	
	t.Run("get location history", func(t *testing.T) {
		req, _ := http.NewRequest(http.MethodGet, "/api/locationtracking/history/user123", nil)
		req.Header.Set("X-User-ID", "user123")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		
		assert.Equal(t, http.StatusOK, w.Code)
	})
	
	t.Run("get nearby entities", func(t *testing.T) {
		req, _ := http.NewRequest(http.MethodGet, "/api/locationtracking/nearby?entity_type=user&latitude=37.77&longitude=-122.41&radius=1000", nil)
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		
		assert.Equal(t, http.StatusOK, w.Code)
	})
}

func TestLocationTrackingHandler_FrequentLocations(t *testing.T) {
	gin.SetMode(gin.TestMode)
	
	tmpFile := "/tmp/location_test6.ets"
	defer os.Remove(tmpFile)
	
	svc := service.NewLocationTrackingService(tmpFile)
	h := NewLocationTrackingHandler(svc)
	
	router := gin.New()
	h.RegisterRoutes(router)
	
	t.Run("get frequent locations", func(t *testing.T) {
		req, _ := http.NewRequest(http.MethodGet, "/api/locationtracking/frequent/user123?hours=24", nil)
		req.Header.Set("X-User-ID", "user123")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		
		assert.Equal(t, http.StatusOK, w.Code)
	})
}

// Verify entity structure
func TestLocationEntity(t *testing.T) {
	t.Run("location entity creation", func(t *testing.T) {
		loc := entity.Location{
			EntityID:   "user123",
			EntityType: "user",
			Latitude:   37.7749,
			Longitude:  -122.4194,
		}
		assert.Equal(t, "user123", loc.EntityID)
		assert.Equal(t, 37.7749, loc.Latitude)
	})
	
	t.Run("geofence entity creation", func(t *testing.T) {
		gf := entity.Geofence{
			Name:         "HQ",
			Latitude:     37.7749,
			Longitude:    -122.4194,
			RadiusMeters: 100,
		}
		assert.Equal(t, "HQ", gf.Name)
		assert.Equal(t, 100.0, gf.RadiusMeters)
	})
}