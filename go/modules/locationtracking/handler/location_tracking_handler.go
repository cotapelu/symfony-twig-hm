package handler

import (
	"context"
	"strconv"
	"time"

	"community.local/modules/locationtracking/entity"
	"community.local/modules/locationtracking/service"
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

type LocationTrackingHandler struct {
	locationTrackingService *service.LocationTrackingService
}

func NewLocationTrackingHandler(locationTrackingService *service.LocationTrackingService) *LocationTrackingHandler {
	return &LocationTrackingHandler{
		locationTrackingService: locationTrackingService,
	}
}

func (lth *LocationTrackingHandler) RegisterRoutes(router *gin.Engine) {
	api := router.Group("/api/locationtracking")
	{
		api.POST("/update", lth.UpdateLocation)
		api.POST("/batch", lth.BatchUpdateLocations)
		api.GET("/:entityId", lth.GetLocation)
		api.GET("/type/:entityType", lth.GetLocationsByType)
		api.GET("/history/:entityId", lth.GetLocationHistory)
		api.GET("/timerange", lth.GetLocationsByTimeRange)
		api.GET("/frequent/:entityId", lth.GetFrequentLocations)
		api.GET("/pattern/:entityId", lth.GetLocationPattern)
		api.GET("/nearby", lth.GetNearbyEntities)
		api.POST("/geofence", lth.CreateGeofence)
		api.GET("/geofences", lth.GetGeofences)
		api.GET("/geofence/check/:entityId", lth.CheckGeofence)
		api.GET("/eta/:entityId", lth.GetETA)
		api.DELETE("/:entityId", lth.StopTracking)
	}
}

type UpdateLocationRequest struct {
	EntityID   string                 `json:"entity_id" binding:"required"`
	EntityType string                 `json:"entity_type" binding:"required"`
	Latitude   float64                `json:"latitude" binding:"required,min=-90,max=90"`
	Longitude  float64                `json:"longitude" binding:"required,min=-180,max=180"`
	Metadata   map[string]interface{} `json:"metadata"`
}

func (lth *LocationTrackingHandler) UpdateLocation(c *gin.Context) {
	// Require authentication
	authUserID, ok := requireAuth(c)
	if !ok {
		return
	}

	var req UpdateLocationRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(400, gin.H{"error": err.Error()})
		return
	}

	// User can only update their own location unless admin
	if req.EntityID != authUserID && !isAdmin(c) {
		c.JSON(403, gin.H{"error": "access denied: can only update your own location"})
		return
	}

	location := lth.locationTrackingService.UpdateLocation(req.EntityID, req.EntityType, req.Latitude, req.Longitude, req.Metadata)

	c.JSON(200, gin.H{
		"status":   "location updated",
		"location": location,
	})
}

func (lth *LocationTrackingHandler) GetLocation(c *gin.Context) {
	// Require authentication
	authUserID, ok := requireAuth(c)
	if !ok {
		return
	}

	entityID := c.Param("entityId")

	// User can only view their own location unless admin
	if entityID != authUserID && !isAdmin(c) {
		c.JSON(403, gin.H{"error": "access denied: can only view your own location"})
		return
	}

	location, err := lth.locationTrackingService.GetLocation(entityID)
	if err != nil {
		c.JSON(404, gin.H{"error": err.Error()})
		return
	}

	c.JSON(200, location)
}

func (lth *LocationTrackingHandler) GetLocationsByType(c *gin.Context) {
	entityType := c.Param("entityType")

	locations := lth.locationTrackingService.GetLocationsByType(entityType)

	c.JSON(200, gin.H{
		"entity_type": entityType,
		"count":       len(locations),
		"locations":   locations,
	})
}

func (lth *LocationTrackingHandler) GetLocationHistory(c *gin.Context) {
	entityID := c.Param("entityId")

	var since *time.Time
	if sinceStr := c.Query("since"); sinceStr != "" {
		sinceTime, err := time.Parse(time.RFC3339, sinceStr)
		if err == nil {
			since = &sinceTime
		}
	}

	history := lth.locationTrackingService.GetLocationHistory(entityID, since)

	c.JSON(200, gin.H{
		"entity_id": entityID,
		"count":     len(history),
		"history":   history,
	})
}

type CreateGeofenceRequest struct {
	Name         string  `json:"name" binding:"required"`
	Latitude     float64 `json:"latitude" binding:"required"`
	Longitude    float64 `json:"longitude" binding:"required"`
	RadiusMeters float64 `json:"radius_meters" binding:"required,min=0"`
	EntityType   string  `json:"entity_type"`
}

func (lth *LocationTrackingHandler) CreateGeofence(c *gin.Context) {
	var req CreateGeofenceRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(400, gin.H{"error": err.Error()})
		return
	}

	entityType := req.EntityType
	if entityType == "" {
		entityType = "all"
	}

	geofence := lth.locationTrackingService.CreateGeofence(req.Name, req.Latitude, req.Longitude, req.RadiusMeters, entityType)

	c.JSON(201, gin.H{
		"status":   "geofence created",
		"geofence": geofence,
	})
}

func (lth *LocationTrackingHandler) CheckGeofence(c *gin.Context) {
	entityID := c.Param("entityId")

	geofences, err := lth.locationTrackingService.CheckGeofence(entityID)
	if err != nil {
		c.JSON(404, gin.H{"error": err.Error()})
		return
	}

	c.JSON(200, gin.H{
		"entity_id": entityID,
		"geofences": geofences,
		"count":     len(geofences),
	})
}

func (lth *LocationTrackingHandler) GetNearbyEntities(c *gin.Context) {
	entityType := c.Query("entity_type")
	if entityType == "" {
		c.JSON(400, gin.H{"error": "entity_type is required"})
		return
	}

	latitude, _ := strconv.ParseFloat(c.DefaultQuery("latitude", "0"), 64)
	longitude, _ := strconv.ParseFloat(c.DefaultQuery("longitude", "0"), 64)
	radiusMeters, _ := strconv.ParseFloat(c.DefaultQuery("radius", "1000"), 64)
	limit, _ := strconv.Atoi(c.DefaultQuery("limit", "20"))

	nearby := lth.locationTrackingService.GetNearbyEntities(entityType, latitude, longitude, radiusMeters, limit)

	c.JSON(200, gin.H{
		"entity_type": entityType,
		"count":       len(nearby),
		"nearby":      nearby,
	})
}

func (lth *LocationTrackingHandler) GetETA(c *gin.Context) {
	entityID := c.Param("entityId")

	destinationLat, _ := strconv.ParseFloat(c.DefaultQuery("dest_lat", "0"), 64)
	destinationLon, _ := strconv.ParseFloat(c.DefaultQuery("dest_lon", "0"), 64)
	averageSpeedKmh, _ := strconv.ParseFloat(c.DefaultQuery("speed", "40"), 64)

	eta, err := lth.locationTrackingService.GetETA(entityID, destinationLat, destinationLon, averageSpeedKmh)
	if err != nil {
		c.JSON(404, gin.H{"error": err.Error()})
		return
	}

	c.JSON(200, gin.H{
		"entity_id": entityID,
		"eta":       eta,
	})
}

func (lth *LocationTrackingHandler) StopTracking(c *gin.Context) {
	// Require authentication
	authUserID, ok := requireAuth(c)
	if !ok {
		return
	}

	entityID := c.Param("entityId")

	// User can only stop tracking their own entity unless admin
	if entityID != authUserID && !isAdmin(c) {
		c.JSON(403, gin.H{"error": "access denied: can only stop tracking your own entity"})
		return
	}

	err := lth.locationTrackingService.StopTracking(entityID)
	if err != nil {
		c.JSON(500, gin.H{"error": err.Error()})
		return
	}

	c.JSON(200, gin.H{"status": "tracking stopped"})
}

type BatchUpdateLocationsRequest struct {
	Locations []entity.Location `json:"locations" binding:"required"`
}

func (lth *LocationTrackingHandler) BatchUpdateLocations(c *gin.Context) {
	var req BatchUpdateLocationsRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(400, gin.H{"error": err.Error()})
		return
	}

	updated := lth.locationTrackingService.BatchUpdateLocations(req.Locations)

	c.JSON(200, gin.H{
		"status":   "locations batch updated",
		"count":    len(updated),
		"updated":  updated,
	})
}

func (lth *LocationTrackingHandler) GetLocationsByTimeRange(c *gin.Context) {
	lat, _ := strconv.ParseFloat(c.DefaultQuery("latitude", "0"), 64)
	lon, _ := strconv.ParseFloat(c.DefaultQuery("longitude", "0"), 64)

	startTimeStr := c.Query("start_time")
	endTimeStr := c.Query("end_time")

	startTime, err := time.Parse(time.RFC3339, startTimeStr)
	if err != nil {
		c.JSON(400, gin.H{"error": "invalid start_time format"})
		return
	}

	endTime, err := time.Parse(time.RFC3339, endTimeStr)
	if err != nil {
		c.JSON(400, gin.H{"error": "invalid end_time format"})
		return
	}

	locations := lth.locationTrackingService.GetLocationsByTimeRange(lat, lon, startTime, endTime)

	c.JSON(200, gin.H{
		"count":     len(locations),
		"locations": locations,
	})
}

func (lth *LocationTrackingHandler) GetFrequentLocations(c *gin.Context) {
	entityID := c.Param("entityId")
	hours, _ := strconv.Atoi(c.DefaultQuery("hours", "24"))

	frequentLocations := lth.locationTrackingService.GetFrequentLocations(entityID, hours, 100)

	c.JSON(200, gin.H{
		"entity_id": entityID,
		"count":     len(frequentLocations),
		"clusters":  frequentLocations,
	})
}

func (lth *LocationTrackingHandler) GetLocationPattern(c *gin.Context) {
	entityID := c.Param("entityId")
	timePeriod := c.DefaultQuery("time_period", "daily")

	pattern := lth.locationTrackingService.GetLocationPattern(entityID, timePeriod)

	c.JSON(200, gin.H{
		"entity_id":  entityID,
		"time_period": timePeriod,
		"pattern":     pattern,
	})
}

func (lth *LocationTrackingHandler) GetGeofences(c *gin.Context) {
	entityType := c.DefaultQuery("entity_type", "all")

	geofences := lth.locationTrackingService.GetGeofences(entityType)

	c.JSON(200, gin.H{
		"entity_type": entityType,
		"count":      len(geofences),
		"geofences":  geofences,
	})
}



func contextWithTimeout(c *gin.Context, d time.Duration) (context.Context, context.CancelFunc) {
	return context.WithTimeout(c.Request.Context(), d)
}
