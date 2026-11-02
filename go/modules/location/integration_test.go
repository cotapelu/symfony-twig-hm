package location

import (
	"context"
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"strings"
	"testing"

	"github.com/gin-gonic/gin"
	"github.com/stretchr/testify/assert"

	lh "community.local/modules/location/handler"
	lr "community.local/modules/location/repository"
	ls "community.local/modules/location/service"
)

func getTestLocationService() *ls.LocationService {
	locationRepo := lr.NewInMemoryLocationRepository()
	locationHistoryRepo := lr.NewInMemoryLocationHistoryRepository()
	geofenceRepo := lr.NewInMemoryGeofenceRepository()

	var eventHandler ls.EventEmitter = eventEmitterFunc(func(ctx context.Context, eventType string, data interface{}) error {
		return nil
	})

	return ls.NewLocationService(locationRepo, locationHistoryRepo, geofenceRepo, eventHandler)
}

type eventEmitterFunc func(ctx context.Context, eventType string, data interface{}) error

func (f eventEmitterFunc) EmitEvent(ctx context.Context, eventType string, data interface{}) error {
	return f(ctx, eventType, data)
}

func setupRouter(handler *lh.LocationHandler) *gin.Engine {
	gin.SetMode(gin.TestMode)
	r := gin.New()
	// Add auth middleware to extract user_id from header
	r.Use(func(c *gin.Context) {
		if uid := c.GetHeader("X-User-ID"); uid != "" {
			c.Set("user_id", uid)
		}
		c.Next()
	})
	handler.RegisterRoutes(r)
	return r
}

func TestLocationService_CRUDOperations(t *testing.T) {
	userID := "test_user_123"

	// Test updating location
	t.Run("UpdateLocation", func(t *testing.T) {
		locationService := getTestLocationService()
		handler := lh.NewLocationHandler(locationService)
		r := setupRouter(handler)

		body := `{"user_id":"` + userID + `","latitude":10.7769,"longitude":106.7009}`
		req := httptest.NewRequest("POST", "/api/location/update", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", userID)
		w := httptest.NewRecorder()
		r.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)

		var resp map[string]interface{}
		if err := json.Unmarshal(w.Body.Bytes(), &resp); err != nil {
			t.Fatalf("failed to parse response: %v", err)
		}
		assert.Equal(t, "location updated", resp["status"])
		assert.Equal(t, userID, resp["user_id"])
		assert.Equal(t, 10.7769, resp["latitude"])
		assert.Equal(t, 106.7009, resp["longitude"])
		assert.NotNil(t, resp["timestamp"])
	})

	// Test getting location
	t.Run("GetLocation", func(t *testing.T) {
		locationService := getTestLocationService()
		handler := lh.NewLocationHandler(locationService)
		r := setupRouter(handler)

		body := `{"user_id":"` + userID + `","latitude":10.7769,"longitude":106.7009}`
		req := httptest.NewRequest("POST", "/api/location/update", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", userID)
		w := httptest.NewRecorder()
		r.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)

		req = httptest.NewRequest("GET", "/api/location/history/"+userID, nil)
		req.Header.Set("X-User-ID", userID)
		w = httptest.NewRecorder()
		r.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)

		var resp map[string]interface{}
		if err := json.Unmarshal(w.Body.Bytes(), &resp); err != nil {
			t.Fatalf("failed to parse response: %v", err)
		}
		assert.Equal(t, userID, resp["user_id"])
		assert.GreaterOrEqual(t, int(resp["count"].(float64)), 1)
	})

	// Test getting nearby users
	t.Run("GetNearbyUsers", func(t *testing.T) {
		locationService := getTestLocationService()
		handler := lh.NewLocationHandler(locationService)
		r := setupRouter(handler)

		body := `{"user_id":"` + userID + `","latitude":10.7769,"longitude":106.7009}`
		req := httptest.NewRequest("POST", "/api/location/update", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", userID)
		w := httptest.NewRecorder()
		r.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)

		req = httptest.NewRequest("GET", "/api/location/nearby?user_id="+userID+"&radius=5&limit=20", nil)
		req.Header.Set("X-User-ID", userID)
		w = httptest.NewRecorder()
		r.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)

		var resp map[string]interface{}
		if err := json.Unmarshal(w.Body.Bytes(), &resp); err != nil {
			t.Fatalf("failed to parse response: %v", err)
		}
		assert.Equal(t, userID, resp["user_id"])
		assert.Equal(t, 5.0, resp["radius_km"])
		assert.Equal(t, 0, int(resp["nearby_count"].(float64)))
	})

	// Test creating a geofence
	t.Run("CreateGeofence", func(t *testing.T) {
		locationService := getTestLocationService()
		handler := lh.NewLocationHandler(locationService)
		r := setupRouter(handler)

		body := `{"name":"Test Geofence","radius_km":1.0,"points":[{"latitude":10.7769,"longitude":106.7009},{"latitude":10.7869,"longitude":106.7109},{"latitude":10.7669,"longitude":106.6909}]}`
		req := httptest.NewRequest("POST", "/api/location/geofence", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", userID)
		w := httptest.NewRecorder()
		r.ServeHTTP(w, req)
		assert.Equal(t, http.StatusCreated, w.Code)

		var resp map[string]interface{}
		if err := json.Unmarshal(w.Body.Bytes(), &resp); err != nil {
			t.Fatalf("failed to parse response: %v", err)
		}
		assert.Equal(t, "geofence created", resp["status"])
		assert.NotNil(t, resp["geofence"])
	})

	// Test listing geofences
	t.Run("ListGeofences", func(t *testing.T) {
		locationService := getTestLocationService()
		handler := lh.NewLocationHandler(locationService)
		r := setupRouter(handler)

		body := `{"name":"Test Geofence","radius_km":1.0,"points":[{"latitude":10.7769,"longitude":106.7009},{"latitude":10.7869,"longitude":106.7109},{"latitude":10.7669,"longitude":106.6909}]}`
		req := httptest.NewRequest("POST", "/api/location/geofence", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", userID)
		w := httptest.NewRecorder()
		r.ServeHTTP(w, req)
		assert.Equal(t, http.StatusCreated, w.Code)

		req = httptest.NewRequest("GET", "/api/location/geofences", nil)
		req.Header.Set("X-User-ID", userID)
		w = httptest.NewRecorder()
		r.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)

		var resp map[string]interface{}
		if err := json.Unmarshal(w.Body.Bytes(), &resp); err != nil {
			t.Fatalf("failed to parse response: %v", err)
		}
		assert.GreaterOrEqual(t, int(resp["count"].(float64)), 1)
		assert.NotNil(t, resp["geofences"])
	})

	// Test deleting current location
	t.Run("DeleteCurrentLocation", func(t *testing.T) {
		locationService := getTestLocationService()
		handler := lh.NewLocationHandler(locationService)
		r := setupRouter(handler)

		body := `{"user_id":"` + userID + `","latitude":10.7769,"longitude":106.7009}`
		req := httptest.NewRequest("POST", "/api/location/update", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", userID)
		w := httptest.NewRecorder()
		r.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)

		req = httptest.NewRequest("DELETE", "/api/location/current/"+userID, nil)
		req.Header.Set("X-User-ID", userID)
		w = httptest.NewRecorder()
		r.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)

		var resp map[string]interface{}
		if err := json.Unmarshal(w.Body.Bytes(), &resp); err != nil {
			t.Fatalf("failed to parse response: %v", err)
		}
		assert.Equal(t, "location deleted", resp["status"])
		assert.Equal(t, userID, resp["user_id"])
	})

	// Test getting location after deletion
	t.Run("GetLocationAfterDelete", func(t *testing.T) {
		locationService := getTestLocationService()
		handler := lh.NewLocationHandler(locationService)
		r := setupRouter(handler)

		body := `{"user_id":"` + userID + `","latitude":10.7769,"longitude":106.7009}`
		req := httptest.NewRequest("POST", "/api/location/update", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", userID)
		w := httptest.NewRecorder()
		r.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)

		req = httptest.NewRequest("DELETE", "/api/location/current/"+userID, nil)
		req.Header.Set("X-User-ID", userID)
		w = httptest.NewRecorder()
		r.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)

		req = httptest.NewRequest("GET", "/api/location/history/"+userID, nil)
		req.Header.Set("X-User-ID", userID)
		w = httptest.NewRecorder()
		r.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)

		var resp map[string]interface{}
		if err := json.Unmarshal(w.Body.Bytes(), &resp); err != nil {
			t.Fatalf("failed to parse response: %v", err)
		}
		assert.Equal(t, userID, resp["user_id"])
		assert.GreaterOrEqual(t, int(resp["count"].(float64)), 1)
	})
}
