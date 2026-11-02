package handler

import (
    "context"
    "encoding/json"
    "net/http"
    "net/http/httptest"
    "strings"
    "testing"

    "github.com/gin-gonic/gin"
    "github.com/stretchr/testify/assert"
    "github.com/stretchr/testify/require"

    "community.local/modules/location/entity"
    "community.local/modules/location/repository"
    "community.local/modules/location/service"
)

type nopEventHandler struct{}

func (n *nopEventHandler) EmitEvent(ctx context.Context, eventType string, data interface{}) error {
    return nil
}

func newTestLocationService(t *testing.T) *service.LocationService {
    locRepo := repository.NewInMemoryLocationRepository()
    histRepo := repository.NewInMemoryLocationHistoryRepository()
    geofenceRepo := repository.NewInMemoryGeofenceRepository()
    nop := &nopEventHandler{}
    return service.NewLocationService(locRepo, histRepo, geofenceRepo, nop)
}

func authMiddleware(userID string, roles []string) gin.HandlerFunc {
    return func(c *gin.Context) {
        c.Set("user_id", userID)
        c.Set("user_roles", roles)
    }
}

// ---------- UpdateLocation tests ----------

func TestUpdateLocation_Success(t *testing.T) {
    svc := newTestLocationService(t)
    h := NewLocationHandler(svc)
    r := gin.New()
    r.Use(authMiddleware("user_1", []string{"ROLE_USER"}))
    h.RegisterRoutes(r)

    payload := `{"user_id":"user_1","latitude":10.5,"longitude":20.3}`
    req := httptest.NewRequest("POST", "/api/location/update", strings.NewReader(payload))
    req.Header.Set("Content-Type", "application/json")
    w := httptest.NewRecorder()
    r.ServeHTTP(w, req)

    assert.Equal(t, http.StatusOK, w.Code)
    var resp map[string]interface{}
    json.NewDecoder(w.Body).Decode(&resp)
    assert.Equal(t, "location updated", resp["status"])
}

func TestUpdateLocation_InvalidLatitude(t *testing.T) {
    svc := newTestLocationService(t)
    h := NewLocationHandler(svc)
    r := gin.New()
    r.Use(authMiddleware("user_1", []string{"ROLE_USER"}))
    h.RegisterRoutes(r)

    payload := `{"user_id":"user_1","latitude":100,"longitude":20}`
    req := httptest.NewRequest("POST", "/api/location/update", strings.NewReader(payload))
    req.Header.Set("Content-Type", "application/json")
    w := httptest.NewRecorder()
    r.ServeHTTP(w, req)

    assert.Equal(t, http.StatusBadRequest, w.Code)
}

func TestUpdateLocation_InvalidLongitude(t *testing.T) {
    svc := newTestLocationService(t)
    h := NewLocationHandler(svc)
    r := gin.New()
    r.Use(authMiddleware("user_1", []string{"ROLE_USER"}))
    h.RegisterRoutes(r)

    payload := `{"user_id":"user_1","latitude":10,"longitude":-200}`
    req := httptest.NewRequest("POST", "/api/location/update", strings.NewReader(payload))
    req.Header.Set("Content-Type", "application/json")
    w := httptest.NewRecorder()
    r.ServeHTTP(w, req)

    assert.Equal(t, http.StatusBadRequest, w.Code)
}

func TestUpdateLocation_MissingUserID(t *testing.T) {
    svc := newTestLocationService(t)
    h := NewLocationHandler(svc)
    r := gin.New()
    r.Use(authMiddleware("user_1", []string{"ROLE_USER"}))
    h.RegisterRoutes(r)

    payload := `{"latitude":10,"longitude":10}`
    req := httptest.NewRequest("POST", "/api/location/update", strings.NewReader(payload))
    req.Header.Set("Content-Type", "application/json")
    w := httptest.NewRecorder()
    r.ServeHTTP(w, req)

    assert.Equal(t, http.StatusBadRequest, w.Code)
}

func TestUpdateLocation_Unauthorized(t *testing.T) {
    svc := newTestLocationService(t)
    h := NewLocationHandler(svc)
    r := gin.New()
    // No auth middleware
    h.RegisterRoutes(r)

    payload := `{"user_id":"user_1","latitude":10,"longitude":10}`
    req := httptest.NewRequest("POST", "/api/location/update", strings.NewReader(payload))
    req.Header.Set("Content-Type", "application/json")
    w := httptest.NewRecorder()
    r.ServeHTTP(w, req)

    assert.Equal(t, http.StatusUnauthorized, w.Code)
}

func TestUpdateLocation_Forbidden(t *testing.T) {
    svc := newTestLocationService(t)
    h := NewLocationHandler(svc)
    r := gin.New()
    r.Use(authMiddleware("user_1", []string{"ROLE_USER"}))
    h.RegisterRoutes(r)

    payload := `{"user_id":"user_2","latitude":10,"longitude":10}`
    req := httptest.NewRequest("POST", "/api/location/update", strings.NewReader(payload))
    req.Header.Set("Content-Type", "application/json")
    w := httptest.NewRecorder()
    r.ServeHTTP(w, req)

    assert.Equal(t, http.StatusForbidden, w.Code)
}

func TestUpdateLocation_AdminAllowed(t *testing.T) {
    svc := newTestLocationService(t)
    h := NewLocationHandler(svc)
    r := gin.New()
    r.Use(authMiddleware("admin", []string{"ROLE_ADMIN"}))
    h.RegisterRoutes(r)

    payload := `{"user_id":"user_1","latitude":10,"longitude":10}`
    req := httptest.NewRequest("POST", "/api/location/update", strings.NewReader(payload))
    req.Header.Set("Content-Type", "application/json")
    w := httptest.NewRecorder()
    r.ServeHTTP(w, req)

    assert.Equal(t, http.StatusOK, w.Code)
}

// ---------- GetNearbyUsers tests ----------

func TestGetNearbyUsers_Success(t *testing.T) {
    svc := newTestLocationService(t)
    // Seed locations
    ctx := context.Background()
    svc.UpdateLocation(ctx, "user_1", 0.0, 0.0)
    svc.UpdateLocation(ctx, "user_2", 0.001, 0.001) // ~111m offset, within 1km radius

    h := NewLocationHandler(svc)
    r := gin.New()
    r.Use(authMiddleware("user_1", []string{"ROLE_USER"}))
    h.RegisterRoutes(r)

    req := httptest.NewRequest("GET", "/api/location/nearby?user_id=user_1&radius=1", nil)
    w := httptest.NewRecorder()
    r.ServeHTTP(w, req)

    assert.Equal(t, http.StatusOK, w.Code)
    var resp map[string]interface{}
    json.NewDecoder(w.Body).Decode(&resp)
    assert.Equal(t, "user_1", resp["user_id"])
    nearbyCount := int(resp["nearby_count"].(float64))
    assert.GreaterOrEqual(t, nearbyCount, 1)
    // Ensure user_2 is in list
    nearbyUsers := resp["nearby_users"].([]interface{})
    found := false
    for _, u := range nearbyUsers {
        userMap := u.(map[string]interface{})
        if userMap["user_id"] == "user_2" {
            found = true
            break
        }
    }
    assert.True(t, found, "user_2 should be in nearby list")
}

func TestGetNearbyUsers_Unauthorized(t *testing.T) {
    svc := newTestLocationService(t)
    h := NewLocationHandler(svc)
    r := gin.New()
    h.RegisterRoutes(r)

    req := httptest.NewRequest("GET", "/api/location/nearby?user_id=user_1", nil)
    w := httptest.NewRecorder()
    r.ServeHTTP(w, req)

    assert.Equal(t, http.StatusUnauthorized, w.Code)
}

func TestGetNearbyUsers_Forbidden(t *testing.T) {
    svc := newTestLocationService(t)
    h := NewLocationHandler(svc)
    r := gin.New()
    r.Use(authMiddleware("user_2", []string{"ROLE_USER"}))
    h.RegisterRoutes(r)

    req := httptest.NewRequest("GET", "/api/location/nearby?user_id=user_1", nil)
    w := httptest.NewRecorder()
    r.ServeHTTP(w, req)

    assert.Equal(t, http.StatusForbidden, w.Code)
}

// ---------- GetLocationHistory tests ----------

func TestGetLocationHistory_Success(t *testing.T) {
    svc := newTestLocationService(t)
    ctx := context.Background()
    // Create multiple location updates
    svc.UpdateLocation(ctx, "user_1", 0.0, 0.0)
    svc.UpdateLocation(ctx, "user_1", 10.0, 20.0)
    svc.UpdateLocation(ctx, "user_1", 30.0, 40.0)

    h := NewLocationHandler(svc)
    r := gin.New()
    r.Use(authMiddleware("user_1", []string{"ROLE_USER"}))
    h.RegisterRoutes(r)

    req := httptest.NewRequest("GET", "/api/location/history/user_1?limit=10", nil)
    w := httptest.NewRecorder()
    r.ServeHTTP(w, req)

    assert.Equal(t, http.StatusOK, w.Code)
    var resp map[string]interface{}
    json.NewDecoder(w.Body).Decode(&resp)
    assert.Equal(t, "user_1", resp["user_id"])
    history := resp["history"].([]interface{})
    assert.Len(t, history, 3) // at least 3 entries
}

func TestGetLocationHistory_Unauthorized(t *testing.T) {
    svc := newTestLocationService(t)
    h := NewLocationHandler(svc)
    r := gin.New()
    h.RegisterRoutes(r)

    req := httptest.NewRequest("GET", "/api/location/history/user_1", nil)
    w := httptest.NewRecorder()
    r.ServeHTTP(w, req)

    assert.Equal(t, http.StatusUnauthorized, w.Code)
}

func TestGetLocationHistory_Forbidden(t *testing.T) {
    svc := newTestLocationService(t)
    h := NewLocationHandler(svc)
    r := gin.New()
    r.Use(authMiddleware("other", []string{"ROLE_USER"}))
    h.RegisterRoutes(r)

    req := httptest.NewRequest("GET", "/api/location/history/user_1", nil)
    w := httptest.NewRecorder()
    r.ServeHTTP(w, req)

    assert.Equal(t, http.StatusForbidden, w.Code)
}

// ---------- DeleteCurrentLocation tests ----------

func TestDeleteCurrentLocation_Success(t *testing.T) {
    svc := newTestLocationService(t)
    ctx := context.Background()
    svc.UpdateLocation(ctx, "user_1", 10.0, 20.0)

    h := NewLocationHandler(svc)
    r := gin.New()
    r.Use(authMiddleware("user_1", []string{"ROLE_USER"}))
    h.RegisterRoutes(r)

    req := httptest.NewRequest("DELETE", "/api/location/current/user_1", nil)
    w := httptest.NewRecorder()
    r.ServeHTTP(w, req)

    assert.Equal(t, http.StatusOK, w.Code)
    var resp map[string]interface{}
    json.NewDecoder(w.Body).Decode(&resp)
    assert.Equal(t, "location deleted", resp["status"])
}

func TestDeleteCurrentLocation_Unauthorized(t *testing.T) {
    svc := newTestLocationService(t)
    h := NewLocationHandler(svc)
    r := gin.New()
    h.RegisterRoutes(r)

    req := httptest.NewRequest("DELETE", "/api/location/current/user_1", nil)
    w := httptest.NewRecorder()
    r.ServeHTTP(w, req)

    assert.Equal(t, http.StatusUnauthorized, w.Code)
}

func TestDeleteCurrentLocation_Forbidden(t *testing.T) {
    svc := newTestLocationService(t)
    h := NewLocationHandler(svc)
    r := gin.New()
    r.Use(authMiddleware("other", []string{"ROLE_USER"}))
    h.RegisterRoutes(r)

    req := httptest.NewRequest("DELETE", "/api/location/current/user_1", nil)
    w := httptest.NewRecorder()
    r.ServeHTTP(w, req)

    assert.Equal(t, http.StatusForbidden, w.Code)
}

// ---------- CreateGeofence tests ----------

func TestCreateGeofence_Success(t *testing.T) {
    svc := newTestLocationService(t)
    h := NewLocationHandler(svc)
    r := gin.New()
    r.Use(authMiddleware("user", []string{"ROLE_USER"}))
    h.RegisterRoutes(r)

    payload := `{"name":"Test Geofence","points":[{"latitude":0,"longitude":0},{"latitude":0,"longitude":0.001},{"latitude":0.001,"longitude":0}],"radius_km":0.5}`
    req := httptest.NewRequest("POST", "/api/location/geofence", strings.NewReader(payload))
    req.Header.Set("Content-Type", "application/json")
    w := httptest.NewRecorder()
    r.ServeHTTP(w, req)

    assert.Equal(t, http.StatusCreated, w.Code)
    var resp map[string]interface{}
    json.NewDecoder(w.Body).Decode(&resp)
    assert.Equal(t, "geofence created", resp["status"])
    gf, ok := resp["geofence"].(map[string]interface{})
    assert.True(t, ok)
    assert.Equal(t, "Test Geofence", gf["name"])
}

func TestCreateGeofence_BadRequest(t *testing.T) {
    svc := newTestLocationService(t)
    h := NewLocationHandler(svc)
    r := gin.New()
    r.Use(authMiddleware("user", []string{"ROLE_USER"}))
    h.RegisterRoutes(r)

    payload := `{"name":"Test"}` // missing points
    req := httptest.NewRequest("POST", "/api/location/geofence", strings.NewReader(payload))
    req.Header.Set("Content-Type", "application/json")
    w := httptest.NewRecorder()
    r.ServeHTTP(w, req)

    assert.Equal(t, http.StatusBadRequest, w.Code)
}

// ---------- GetGeofence tests ----------

func TestGetGeofence_NotFound(t *testing.T) {
    svc := newTestLocationService(t)
    h := NewLocationHandler(svc)
    r := gin.New()
    h.RegisterRoutes(r)

    req := httptest.NewRequest("GET", "/api/location/geofence/nonexistent", nil)
    w := httptest.NewRecorder()
    r.ServeHTTP(w, req)

    assert.Equal(t, http.StatusNotFound, w.Code)
}

func TestGetGeofence_Success(t *testing.T) {
    svc := newTestLocationService(t)
    ctx := context.Background()
    // Create a geofence directly via service to have a known ID
    gf := &entity.Geofence{
        Name: "TestGF",
        Points: []entity.Location{
            {Latitude: 0, Longitude: 0},
            {Latitude: 0, Longitude: 0.001},
            {Latitude: 0.001, Longitude: 0},
        },
        Radius: 0.5,
    }
    err := svc.CreateGeofence(ctx, gf)
    require.NoError(t, err)
    gfID := gf.ID

    h := NewLocationHandler(svc)
    r := gin.New()
    h.RegisterRoutes(r)

    req := httptest.NewRequest("GET", "/api/location/geofence/"+gfID, nil)
    w := httptest.NewRecorder()
    r.ServeHTTP(w, req)

    assert.Equal(t, http.StatusOK, w.Code)
    var resp map[string]interface{}
    json.NewDecoder(w.Body).Decode(&resp)
    assert.Equal(t, "TestGF", resp["name"])
}

// ---------- DeleteGeofence tests ----------

func TestDeleteGeofence_Unauthorized(t *testing.T) {
    svc := newTestLocationService(t)
    h := NewLocationHandler(svc)
    r := gin.New()
    h.RegisterRoutes(r)

    req := httptest.NewRequest("DELETE", "/api/location/geofence/someid", nil)
    w := httptest.NewRecorder()
    r.ServeHTTP(w, req)

    assert.Equal(t, http.StatusUnauthorized, w.Code)
}

func TestDeleteGeofence_Forbidden(t *testing.T) {
    svc := newTestLocationService(t)
    h := NewLocationHandler(svc)
    r := gin.New()
    r.Use(authMiddleware("user", []string{"ROLE_USER"}))
    h.RegisterRoutes(r)

    req := httptest.NewRequest("DELETE", "/api/location/geofence/someid", nil)
    w := httptest.NewRecorder()
    r.ServeHTTP(w, req)

    assert.Equal(t, http.StatusForbidden, w.Code)
}

func TestDeleteGeofence_Success(t *testing.T) {
    svc := newTestLocationService(t)
    ctx := context.Background()
    gf := &entity.Geofence{
        Name: "ToDelete",
        Points: []entity.Location{{Latitude: 0, Longitude: 0}},
        Radius: 0.1,
    }
    require.NoError(t, svc.CreateGeofence(ctx, gf))
    gfID := gf.ID

    h := NewLocationHandler(svc)
    r := gin.New()
    r.Use(authMiddleware("admin", []string{"ROLE_ADMIN"}))
    h.RegisterRoutes(r)

    req := httptest.NewRequest("DELETE", "/api/location/geofence/"+gfID, nil)
    w := httptest.NewRecorder()
    r.ServeHTTP(w, req)

    assert.Equal(t, http.StatusOK, w.Code)
}
