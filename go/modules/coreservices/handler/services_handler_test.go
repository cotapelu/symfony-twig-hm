package handler

import (
	"bytes"
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"testing"
	"time"

	"github.com/gin-gonic/gin"
	"github.com/stretchr/testify/assert"

	"community.local/internal/eventbus"
	"community.local/modules/coreservices/service"
)

type testContext struct {
	t      *testing.T
	router *gin.Engine
	svc    *service.ServicesService
}

func newTestContext(t *testing.T) *testContext {
	t.Helper()
	eb := eventbus.NewEventBus()
	svc := service.NewServicesService(eb)
	h := NewServicesHandler(svc)

	r := gin.New()
	// Auth middleware: set user_id from X-User-ID header if present; allow any user_id for tests
	r.Use(func(c *gin.Context) {
		if uid := c.GetHeader("X-User-ID"); uid != "" {
			c.Set("user_id", uid)
		}
		c.Next()
	})
	h.RegisterRoutes(r)

	return &testContext{t: t, router: r, svc: svc}
}

func (tc *testContext) do(method, path string, body interface{}, headers map[string]string) *httptest.ResponseRecorder {
	var reqBody []byte
	if body != nil {
		jsonBytes, _ := json.Marshal(body)
		reqBody = jsonBytes
	}
	req := httptest.NewRequest(method, path, bytes.NewReader(reqBody))
	req.Header.Set("Content-Type", "application/json")
	for k, v := range headers {
		req.Header.Set(k, v)
	}
	w := httptest.NewRecorder()
	tc.router.ServeHTTP(w, req)
	return w
}

func (tc *testContext) close() {
	// nothing to clean
}

// ==================== Tests ====================

func TestCreateBooking(t *testing.T) {
	gin.SetMode(gin.TestMode)
	tc := newTestContext(t)
	defer tc.close()

	now := time.Now()
	future := now.Add(2 * time.Hour).Format(time.RFC3339)

	payload := func(pickupLat, pickupLon, dropoffLat, dropoffLon float64) map[string]interface{} {
		return map[string]interface{}{
			"service_type": "ride",
			"user_id":      "user123",
			"pickup": map[string]float64{
				"latitude":  pickupLat,
				"longitude": pickupLon,
			},
			"dropoff": map[string]float64{
				"latitude":  dropoffLat,
				"longitude": dropoffLon,
			},
			"scheduled_at": future,
		}
	}

	base := payload(40.7128, -74.0060, 40.7580, -73.9855)

	// Success
	w := tc.do(http.MethodPost, "/api/services/bookings", base, nil)
	assert.Equal(t, http.StatusCreated, w.Code)
	var resp map[string]interface{}
	json.NewDecoder(w.Body).Decode(&resp)
	assert.Contains(t, resp, "id")
	assert.Equal(t, "ride", resp["service_type"])
	assert.Equal(t, "pending", resp["status"])

	// Missing service_type
	bad := payload(40.7128, -74.0060, 40.7580, -73.9855)
	bad["service_type"] = ""
	w = tc.do(http.MethodPost, "/api/services/bookings", bad, nil)
	assert.Equal(t, http.StatusBadRequest, w.Code)

	// Invalid coordinates: latitude out of range
	bad = payload(100.0, -74.0060, 40.7580, -73.9855)
	w = tc.do(http.MethodPost, "/api/services/bookings", bad, nil)
	assert.Equal(t, http.StatusBadRequest, w.Code)

	// Invalid coordinates: longitude out of range
	bad = payload(40.7128, -200.0, 40.7580, -73.9855)
	w = tc.do(http.MethodPost, "/api/services/bookings", bad, nil)
	assert.Equal(t, http.StatusBadRequest, w.Code)

	// Past scheduled_at
	past := now.Add(-1 * time.Hour).Format(time.RFC3339)
	bad = payload(40.7128, -74.0060, 40.7580, -73.9855)
	bad["scheduled_at"] = past
	w = tc.do(http.MethodPost, "/api/services/bookings", bad, nil)
	assert.Equal(t, http.StatusBadRequest, w.Code)

	// Missing pickup (field omitted entirely)
	bad = map[string]interface{}{
		"service_type": "ride",
		"user_id":      "user123",
		"dropoff": map[string]float64{
			"latitude":  40.7580,
			"longitude": -73.9855,
		},
		"scheduled_at": future,
	}
	w = tc.do(http.MethodPost, "/api/services/bookings", bad, nil)
	assert.Equal(t, http.StatusBadRequest, w.Code)

	// Missing dropoff (field omitted entirely)
	bad = map[string]interface{}{
		"service_type": "ride",
		"user_id":      "user123",
		"pickup": map[string]float64{
			"latitude":  40.7128,
			"longitude": -74.0060,
		},
		"scheduled_at": future,
	}
	w = tc.do(http.MethodPost, "/api/services/bookings", bad, nil)
	assert.Equal(t, http.StatusBadRequest, w.Code)
}

func TestGetBooking(t *testing.T) {
	gin.SetMode(gin.TestMode)
	tc := newTestContext(t)
	defer tc.close()

	// Create a booking first
	now := time.Now()
	future := now.Add(2 * time.Hour).Format(time.RFC3339)
	createPayload := map[string]interface{}{
		"service_type": "ride",
		"user_id":      "user123",
		"pickup": map[string]float64{
			"latitude":  40.7128,
			"longitude": -74.0060,
		},
		"dropoff": map[string]float64{
			"latitude":  40.7580,
			"longitude": -73.9855,
		},
		"scheduled_at": future,
	}
	w := tc.do(http.MethodPost, "/api/services/bookings", createPayload, nil)
	assert.Equal(t, http.StatusCreated, w.Code)
	var created map[string]interface{}
	json.NewDecoder(w.Body).Decode(&created)
	bookingID := created["id"].(string)

	// Success: own booking
	w = tc.do(http.MethodGet, "/api/services/bookings/"+bookingID, nil, map[string]string{"X-User-ID": "user123"})
	assert.Equal(t, http.StatusOK, w.Code)
	var got map[string]interface{}
	json.NewDecoder(w.Body).Decode(&got)
	assert.Equal(t, bookingID, got["id"])

	// Not found
	w = tc.do(http.MethodGet, "/api/services/bookings/doesnotexist", nil, map[string]string{"X-User-ID": "user123"})
	assert.Equal(t, http.StatusNotFound, w.Code)

	// Unauthorized (no user ID)
	w = tc.do(http.MethodGet, "/api/services/bookings/"+bookingID, nil, nil)
	assert.Equal(t, http.StatusUnauthorized, w.Code)

	// Forbidden: access another user's booking
	w = tc.do(http.MethodGet, "/api/services/bookings/"+bookingID, nil, map[string]string{"X-User-ID": "otheruser"})
	assert.Equal(t, http.StatusForbidden, w.Code)
}

func TestListBookings(t *testing.T) {
	gin.SetMode(gin.TestMode)
	tc := newTestContext(t)
	defer tc.close()

	userID := "user1"
	now := time.Now()
	future := now.Add(2 * time.Hour).Format(time.RFC3339)
	payload := func(idx int) map[string]interface{} {
		return map[string]interface{}{
			"service_type": "ride",
			"user_id":      userID,
			"pickup": map[string]float64{
				"latitude":  40.7128 + float64(idx)*0.01,
				"longitude": -74.0060,
			},
			"dropoff": map[string]float64{
				"latitude":  40.7580 + float64(idx)*0.01,
				"longitude": -73.9855,
			},
			"scheduled_at": future,
		}
	}
	tc.do(http.MethodPost, "/api/services/bookings", payload(1), nil)
	time.Sleep(1 * time.Second)
	tc.do(http.MethodPost, "/api/services/bookings", payload(2), nil)

	w := tc.do(http.MethodGet, "/api/services/bookings", nil, map[string]string{"X-User-ID": userID})
	assert.Equal(t, http.StatusOK, w.Code)
	var resp map[string]interface{}
	json.NewDecoder(w.Body).Decode(&resp)
	bookings := resp["bookings"].([]interface{})
	assert.Len(t, bookings, 2)

	// Unauthorized
	w = tc.do(http.MethodGet, "/api/services/bookings", nil, nil)
	assert.Equal(t, http.StatusUnauthorized, w.Code)
}

func TestGetNearbyServices(t *testing.T) {
	gin.SetMode(gin.TestMode)
	tc := newTestContext(t)
	defer tc.close()

	// Use coordinates near sample providers (NYC)
	params := "?service_type=ride&lat=40.7128&lon=-74.0060&radius=10"
	w := tc.do(http.MethodGet, "/api/services/nearby"+params, nil, nil)
	assert.Equal(t, http.StatusOK, w.Code)
	var resp map[string]interface{}
	json.NewDecoder(w.Body).Decode(&resp)
	assert.Equal(t, "ride", resp["service_type"])
	providers, ok := resp["providers"].([]interface{})
	assert.True(t, ok)
	assert.NotEmpty(t, providers)

	// Missing lat/lon
	w = tc.do(http.MethodGet, "/api/services/nearby?service_type=ride", nil, nil)
	assert.Equal(t, http.StatusBadRequest, w.Code)

	// Invalid radius (non-numeric)
	w = tc.do(http.MethodGet, "/api/services/nearby?service_type=ride&lat=40.7128&lon=-74.0060&radius=abc", nil, nil)
	assert.Equal(t, http.StatusBadRequest, w.Code)

	// Radius out of range
	w = tc.do(http.MethodGet, "/api/services/nearby?service_type=ride&lat=40.7128&lon=-74.0060&radius=200", nil, nil)
	assert.Equal(t, http.StatusBadRequest, w.Code)
}

func TestCalculateRoute(t *testing.T) {
	gin.SetMode(gin.TestMode)
	tc := newTestContext(t)
	defer tc.close()

	params := "?origin_lat=40.7128&origin_lon=-74.0060&dest_lat=40.7580&dest_lon=-73.9855"
	w := tc.do(http.MethodGet, "/api/services/directions"+params, nil, nil)
	assert.Equal(t, http.StatusOK, w.Code)
	var resp map[string]interface{}
	json.NewDecoder(w.Body).Decode(&resp)
	dist, ok := resp["distance_km"].(float64)
	assert.True(t, ok)
	assert.Greater(t, dist, 0.0)
	duration, ok := resp["duration_min"].(float64)
	assert.True(t, ok)
	assert.Greater(t, duration, 0.0)

	// Missing parameters
	w = tc.do(http.MethodGet, "/api/services/directions?origin_lat=40.7128&origin_lon=-74.0060", nil, nil)
	assert.Equal(t, http.StatusBadRequest, w.Code)
}
