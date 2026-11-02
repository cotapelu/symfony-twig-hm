package handler

import (
	"net/http"
	"strconv"
	"time"

	"community.local/modules/locationtracking/service"

	"github.com/gin-gonic/gin"
)

// LocationHandler handles location tracking HTTP requests
type LocationHandler struct {
	service *service.LocationTrackingService
}

// NewLocationHandler creates a new location handler
func NewLocationHandler(svc *service.LocationTrackingService) *LocationHandler {
	return &LocationHandler{
		service: svc,
	}
}

// RegisterRoutes registers all location tracking routes
func (h *LocationHandler) RegisterRoutes(router *gin.Engine) {
	locations := router.Group("/api/locations")
	{
		locations.POST("/update", h.UpdateLocation)
		locations.GET("/user/:user_id", h.GetUserLocation)
		locations.GET("/user/:user_id/history", h.GetUserLocationHistory)
		locations.GET("/nearby", h.GetNearbyUsers)

		locations.POST("/geofences", h.CreateGeofence)
		locations.GET("/geofences", h.GetGeofences)

		locations.GET("/ws/:user_id", h.WebSocketHandler)
	}
}

// UpdateLocation handles location update requests
func (h *LocationHandler) UpdateLocation(c *gin.Context) {
	var req struct {
		UserID    string  `json:"user_id" binding:"required"`
		Latitude  float64 `json:"latitude" binding:"required"`
		Longitude float64 `json:"longitude" binding:"required"`
		Accuracy  float64 `json:"accuracy"`
		Altitude  float64 `json:"altitude"`
		Speed     float64 `json:"speed"`
		Heading   float64 `json:"heading"`
		DeviceID  string  `json:"device_id"`
	}

	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	loc := h.service.UpdateLocationForUser(req.UserID, req.Latitude, req.Longitude, req.Accuracy, req.Altitude, req.Speed, req.Heading, req.DeviceID)

	c.JSON(http.StatusOK, gin.H{
		"location": loc,
		"message":  "Location updated successfully",
	})
}

// GetUserLocation retrieves a user's latest location
func (h *LocationHandler) GetUserLocation(c *gin.Context) {
	userID := c.Param("user_id")

	loc, err := h.service.GetUserLocation(userID)
	if err != nil {
		c.JSON(http.StatusNotFound, gin.H{"error": "Location not found"})
		return
	}

	c.JSON(http.StatusOK, gin.H{"location": loc})
}

// GetUserLocationHistory retrieves a user's location history
func (h *LocationHandler) GetUserLocationHistory(c *gin.Context) {
	userID := c.Param("user_id")

	hours, _ := strconv.Atoi(c.DefaultQuery("hours", "24"))

	history, err := h.service.GetUserLocationHistory(userID, hours)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusOK, history)
}

// GetNearbyUsers retrieves users near a location
func (h *LocationHandler) GetNearbyUsers(c *gin.Context) {
	lat, _ := strconv.ParseFloat(c.Query("lat"), 64)
	lon, _ := strconv.ParseFloat(c.Query("lon"), 64)
	radius, _ := strconv.ParseFloat(c.DefaultQuery("radius", "5"), 64) // Default 5km
	limit, _ := strconv.Atoi(c.DefaultQuery("limit", "50"))

	locations, err := h.service.GetNearbyUsers(lat, lon, radius, limit)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"locations": locations,
		"count":     len(locations),
	})
}

// CreateGeofence creates a new geofence
func (h *LocationHandler) CreateGeofence(c *gin.Context) {
	var req struct {
		Name        string  `json:"name" binding:"required"`
		Description string  `json:"description"`
		Latitude    float64 `json:"latitude" binding:"required"`
		Longitude   float64 `json:"longitude" binding:"required"`
		Radius      float64 `json:"radius" binding:"required"` // in meters
	}

	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	fence := h.service.CreateGeofenceForUser(req.Name, req.Description, req.Latitude, req.Longitude, req.Radius)

	c.JSON(http.StatusCreated, gin.H{
		"geofence": fence,
		"message":  "Geofence created successfully",
	})
}

// GetGeofences retrieves all active geofences
func (h *LocationHandler) GetGeofences(c *gin.Context) {
	fences := h.service.GetActiveGeofences()

	c.JSON(http.StatusOK, gin.H{
		"geofences": fences,
		"count":     len(fences),
	})
}

// WebSocketHandler handles WebSocket connections for real-time location updates
func (h *LocationHandler) WebSocketHandler(c *gin.Context) {
	userID := c.Param("user_id")

	// For now, return a placeholder response
	// In production, this would upgrade to WebSocket
	c.JSON(http.StatusOK, gin.H{
		"message": "WebSocket endpoint for real-time location tracking",
		"user_id": userID,
		"note":    "Connect via WebSocket for real-time updates",
	})
}

// LocationUpdateRequest represents a batch location update request
type LocationUpdateRequest struct {
	UserID    string `json:"user_id"`
	Locations []struct {
		Latitude  float64   `json:"latitude"`
		Longitude float64   `json:"longitude"`
		Accuracy  float64   `json:"accuracy"`
		Timestamp time.Time `json:"timestamp"`
	} `json:"locations"`
}

// BatchUpdateLocations handles batch location updates
func (h *LocationHandler) BatchUpdateLocations(c *gin.Context) {
	var req LocationUpdateRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	updated := 0
	for _, loc := range req.Locations {
		h.service.UpdateLocationForUser(req.UserID, loc.Latitude, loc.Longitude, loc.Accuracy, 0, 0, 0, "")
		updated++
	}

	c.JSON(http.StatusOK, gin.H{
		"message":       "Batch update completed",
		"updated_count": updated,
		"total_count":   len(req.Locations),
	})
}
