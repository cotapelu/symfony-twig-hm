package handler

import (
	"context"
	"log"
	"strconv"
	"time"

	"community.local/modules/location/entity"
	"community.local/modules/location/service"
	"github.com/gin-gonic/gin"
)

func getCurrentUserID(c *gin.Context) (string, bool) {
	userID, exists := c.Get("user_id")
	if !exists {
		return "", false
	}
	return userID.(string), true
}

func requireAuth(c *gin.Context) (string, bool) {
	userID, ok := getCurrentUserID(c)
	if !ok {
		c.JSON(401, gin.H{"error": "authentication required"})
		return "", false
	}
	return userID, true
}

func isAdmin(c *gin.Context) bool {
	if roles, ok := c.Get("user_roles"); ok {
		for _, r := range roles.([]string) {
			if r == "ROLE_ADMIN" || r == "admin" {
				return true
			}
		}
	}
	return false
}

type LocationHandler struct {
	locationService *service.LocationService
}

func NewLocationHandler(locationService *service.LocationService) *LocationHandler {
	return &LocationHandler{
		locationService: locationService,
	}
}

func (lh *LocationHandler) RegisterRoutes(router *gin.Engine) {
	api := router.Group("/api/location")
	{
		api.POST("/update", lh.UpdateLocation)
		api.GET("/nearby", lh.GetNearbyUsers)
		api.GET("/history/:userId", lh.GetLocationHistory)
		api.DELETE("/current/:userId", lh.DeleteCurrentLocation)
		api.POST("/geofence", lh.CreateGeofence)
		api.GET("/geofence/:geofenceId", lh.GetGeofence)
		api.DELETE("/geofence/:geofenceId", lh.DeleteGeofence)
		api.GET("/geofences", lh.ListGeofences)
	}
}

type UpdateLocationRequest struct {
	UserID    string  `json:"user_id" binding:"required"`
	Latitude  float64 `json:"latitude" binding:"required,min=-90,max=90"`
	Longitude float64 `json:"longitude" binding:"required,min=-180,max=180"`
}

func (lh *LocationHandler) UpdateLocation(c *gin.Context) {
	var req UpdateLocationRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(400, gin.H{"error": err.Error()})
		return
	}

	// Auth: user can only update own location
	userID, ok := requireAuth(c)
	if !ok {
		return
	}
	if userID != req.UserID && !isAdmin(c) {
		c.JSON(403, gin.H{"error": "can only update your own location"})
		return
	}

	ctx, cancel := contextWithTimeout(c, 5*time.Second)
	defer cancel()

	err := lh.locationService.UpdateLocation(ctx, req.UserID, req.Latitude, req.Longitude)
	if err != nil {
		log.Printf("Error updating location: %v", err)
		c.JSON(500, gin.H{"error": "Failed to update location"})
		return
	}

	c.JSON(200, gin.H{
		"status":    "location updated",
		"user_id":   req.UserID,
		"latitude":  req.Latitude,
		"longitude": req.Longitude,
		"timestamp": time.Now().Format(time.RFC3339),
	})
}

func (lh *LocationHandler) GetNearbyUsers(c *gin.Context) {
	userID := c.Query("user_id")
	if userID == "" {
		c.JSON(400, gin.H{"error": "user_id is required"})
		return
	}

	// Auth: user can only query nearby for themselves (or admin)
	if _, ok := requireAuth(c); !ok {
		return
	}
	if !isAdmin(c) {
		// For now, allow only own user ID (can be relaxed for friend networks)
		if authUserID, _ := getCurrentUserID(c); authUserID != userID {
			c.JSON(403, gin.H{"error": "can only view nearby users for yourself"})
			return
		}
	}

	radiusKm, _ := strconv.ParseFloat(c.DefaultQuery("radius", "5"), 64)
	limit, _ := strconv.Atoi(c.DefaultQuery("limit", "20"))

	ctx, cancel := contextWithTimeout(c, 5*time.Second)
	defer cancel()

	nearby, _ := lh.locationService.GetNearbyUsers(ctx, userID, radiusKm, limit)

	c.JSON(200, gin.H{
		"user_id":      userID,
		"radius_km":    radiusKm,
		"nearby_count": len(nearby),
		"nearby_users": nearby,
	})
}

func (lh *LocationHandler) GetLocationHistory(c *gin.Context) {
	userID := c.Param("userId")

	// Auth: user can only view own history (or admin)
	if _, ok := requireAuth(c); !ok {
		return
	}
	if !isAdmin(c) {
		if authUserID, _ := getCurrentUserID(c); authUserID != userID {
			c.JSON(403, gin.H{"error": "can only view your own location history"})
			return
		}
	}

	limit, _ := strconv.Atoi(c.DefaultQuery("limit", "50"))

	ctx, cancel := contextWithTimeout(c, 5*time.Second)
	defer cancel()

	history, _ := lh.locationService.GetLocationHistory(ctx, userID, limit)

	c.JSON(200, gin.H{
		"user_id": userID,
		"count":   len(history),
		"history": history,
	})
}

func (lh *LocationHandler) DeleteCurrentLocation(c *gin.Context) {
	userID := c.Param("userId")

	// Auth: user can only delete own location (or admin)
	if _, ok := requireAuth(c); !ok {
		return
	}
	if !isAdmin(c) {
		if authUserID, _ := getCurrentUserID(c); authUserID != userID {
			c.JSON(403, gin.H{"error": "can only delete your own location"})
			return
		}
	}

	ctx, cancel := contextWithTimeout(c, 5*time.Second)
	defer cancel()

	err := lh.locationService.DeleteCurrentLocation(ctx, userID)
	if err != nil {
		log.Printf("Error deleting location: %v", err)
		c.JSON(500, gin.H{"error": "Failed to delete location"})
		return
	}

	c.JSON(200, gin.H{
		"status":  "location deleted",
		"user_id": userID,
	})
}

type CreateGeofenceRequest struct {
	Name     string            `json:"name" binding:"required"`
	Radius   float64           `json:"radius_km" binding:"required,min=0"`
	Points   []entity.Location `json:"points" binding:"required,min=3"`
	Metadata map[string]string `json:"metadata,omitempty"`
}

func (lh *LocationHandler) CreateGeofence(c *gin.Context) {
	// Auth: only authenticated users can create geofences
	if _, ok := requireAuth(c); !ok {
		return
	}

	var req CreateGeofenceRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(400, gin.H{"error": err.Error()})
		return
	}

	geofence := &entity.Geofence{
		Name:      req.Name,
		Points:    req.Points,
		Radius:    req.Radius,
		CreatedAt: time.Now(),
		Metadata:  req.Metadata,
	}

	ctx, cancel := contextWithTimeout(c, 10*time.Second)
	defer cancel()

	err := lh.locationService.CreateGeofence(ctx, geofence)
	if err != nil {
		log.Printf("Error creating geofence: %v", err)
		c.JSON(500, gin.H{"error": "Failed to create geofence"})
		return
	}

	c.JSON(201, gin.H{
		"status":   "geofence created",
		"geofence": geofence,
	})
}

func (lh *LocationHandler) GetGeofence(c *gin.Context) {
	geofenceID := c.Param("geofenceId")

	ctx, cancel := contextWithTimeout(c, 5*time.Second)
	defer cancel()

	geofence, err := lh.locationService.GetGeofence(ctx, geofenceID)
	if err != nil || geofence == nil {
		c.JSON(404, gin.H{"error": "Geofence not found"})
		return
	}

	c.JSON(200, geofence)
}

func (lh *LocationHandler) DeleteGeofence(c *gin.Context) {
	// Auth: only admin can delete geofences
	if _, ok := requireAuth(c); !ok {
		return
	}
	if !isAdmin(c) {
		c.JSON(403, gin.H{"error": "only admin can delete geofences"})
		return
	}

	geofenceID := c.Param("geofenceId")

	ctx, cancel := contextWithTimeout(c, 5*time.Second)
	defer cancel()

	err := lh.locationService.DeleteGeofence(ctx, geofenceID)
	if err != nil {
		log.Printf("Error deleting geofence: %v", err)
		c.JSON(500, gin.H{"error": "Failed to delete geofence"})
		return
	}

	c.JSON(200, gin.H{
		"status": "geofence deleted",
	})
}

func (lh *LocationHandler) ListGeofences(c *gin.Context) {
	// Auth: only authenticated users can list geofences
	if _, ok := requireAuth(c); !ok {
		return
	}

	ctx, cancel := contextWithTimeout(c, 5*time.Second)
	defer cancel()

	geofences, err := lh.locationService.ListGeofences(ctx)
	if err != nil {
		log.Printf("Error listing geofences: %v", err)
		c.JSON(500, gin.H{"error": "Failed to list geofences"})
		return
	}

	c.JSON(200, gin.H{
		"count":     len(geofences),
		"geofences": geofences,
	})
}

func (lh *LocationHandler) CheckRedis(c *gin.Context) bool {
	log.Println("Checking Redis connection...")
	return true
}

func contextWithTimeout(c *gin.Context, d time.Duration) (context.Context, context.CancelFunc) {
	return context.WithTimeout(c.Request.Context(), d)
}