package handler

import (
	"context"
	"log"
	"strconv"
	"time"

	"community.local/modules/coreservices/entity"
	coreserviceservice "community.local/modules/coreservices/service"
	"github.com/gin-gonic/gin"
)

type ServicesHandler struct {
	serviceService *coreserviceservice.ServicesService
}

func NewServicesHandler(serviceService *coreserviceservice.ServicesService) *ServicesHandler {
	return &ServicesHandler{
		serviceService: serviceService,
	}
}

func (sh *ServicesHandler) RegisterRoutes(router *gin.Engine) {
	api := router.Group("/api/services")
	{
		api.POST("/bookings", sh.CreateBooking)
		api.GET("/bookings/:id", sh.GetBooking)
		api.GET("/bookings", sh.ListBookings)
		api.GET("/nearby", sh.GetNearbyServices)
		api.GET("/directions", sh.CalculateRoute)
	}
}

type CreateBookingRequest struct {
	ServiceType string          `json:"service_type" binding:"required"`
	UserID      string          `json:"user_id" binding:"required"`
	ProviderID  string          `json:"provider_id"`
	Pickup      entity.Location `json:"pickup" binding:"required"`
	Dropoff     entity.Location `json:"dropoff" binding:"required"`
	ScheduledAt string          `json:"scheduled_at" binding:"required"`
}

func getUserIDFromContext(c *gin.Context) string {
	if userID, exists := c.Get("user_id"); exists {
		return userID.(string)
	}
	return c.GetHeader("X-User-ID")
}

func (sh *ServicesHandler) CreateBooking(c *gin.Context) {
	var req CreateBookingRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(400, gin.H{"error": err.Error()})
		return
	}

	authUserID := getUserIDFromContext(c)
	if authUserID == "" {
		authUserID = req.UserID
	}

	scheduledAt, err := time.Parse(time.RFC3339, req.ScheduledAt)
	if err != nil {
		c.JSON(400, gin.H{"error": "Invalid scheduled_at format, use RFC3339"})
		return
	}

	if scheduledAt.Before(time.Now()) {
		c.JSON(400, gin.H{"error": "Scheduled time must be in the future"})
		return
	}

	ctx, cancel := contextWithTimeout(c, 5 * time.Second)
	defer cancel()

	booking, err := sh.serviceService.CreateBooking(ctx, req.ServiceType, authUserID, req.ProviderID, req.Pickup, req.Dropoff, scheduledAt)
	if err != nil {
		log.Printf("Error creating booking: %v", err)
		c.JSON(400, gin.H{"error": err.Error()})
		return
	}

	c.JSON(201, booking)
}

func (sh *ServicesHandler) GetBooking(c *gin.Context) {
	bookingID := c.Param("id")
	if bookingID == "" {
		c.JSON(400, gin.H{"error": "Booking ID is required"})
		return
	}

	userID := getUserIDFromContext(c)
	if userID == "" {
		c.JSON(401, gin.H{"error": "Unauthorized: user ID required"})
		return
	}

	booking, err := sh.serviceService.GetBooking(bookingID, userID)
	if err != nil {
		if err.Error() == "unauthorized access to booking" {
			c.JSON(403, gin.H{"error": "Forbidden: you can only access your own bookings"})
			return
		}
		c.JSON(404, gin.H{"error": "Booking not found"})
		return
	}

	c.JSON(200, booking)
}

func (sh *ServicesHandler) ListBookings(c *gin.Context) {
	userID := getUserIDFromContext(c)
	if userID == "" {
		c.JSON(401, gin.H{"error": "Unauthorized: user ID required"})
		return
	}

	ctx, cancel := contextWithTimeout(c, 5 * time.Second)
	defer cancel()

	bookings := sh.serviceService.ListBookings(ctx, userID)

	c.JSON(200, gin.H{
		"count":    len(bookings),
		"bookings": bookings,
	})
}

func (sh *ServicesHandler) GetNearbyServices(c *gin.Context) {
	serviceType := c.Query("service_type")
	latStr := c.Query("lat")
	lonStr := c.Query("lon")
	radiusKm, err := strconv.ParseFloat(c.DefaultQuery("radius", "5"), 64)
	if err != nil {
		c.JSON(400, gin.H{"error": "Invalid radius"})
		return
	}
	limit, _ := strconv.Atoi(c.DefaultQuery("limit", "20"))

	if latStr == "" || lonStr == "" {
		c.JSON(400, gin.H{"error": "lat and lon are required"})
		return
	}

	lat, err := strconv.ParseFloat(latStr, 64)
	if err != nil {
		c.JSON(400, gin.H{"error": "Invalid lat"})
		return
	}
	if lat < -90 || lat > 90 {
		c.JSON(400, gin.H{"error": "lat must be between -90 and 90"})
		return
	}
	lon, err := strconv.ParseFloat(lonStr, 64)
	if err != nil {
		c.JSON(400, gin.H{"error": "Invalid lon"})
		return
	}
	if lon < -180 || lon > 180 {
		c.JSON(400, gin.H{"error": "lon must be between -180 and 180"})
		return
	}

	ctx, cancel := contextWithTimeout(c, 5 * time.Second)
	defer cancel()

	location := entity.Location{Latitude: lat, Longitude: lon}
	providers, err := sh.serviceService.GetNearbyProviders(ctx, serviceType, location, radiusKm)
	if err != nil {
		c.JSON(400, gin.H{"error": err.Error()})
		return
	}

	if len(providers) > limit {
		providers = providers[:limit]
	}

	c.JSON(200, gin.H{
		"service_type": serviceType,
		"radius_km":    radiusKm,
		"count":        len(providers),
		"providers":    providers,
	})
}

func (sh *ServicesHandler) CalculateRoute(c *gin.Context) {
	originLatStr := c.Query("origin_lat")
	originLonStr := c.Query("origin_lon")
	destLatStr := c.Query("dest_lat")
	destLonStr := c.Query("dest_lon")

	if originLatStr == "" || originLonStr == "" || destLatStr == "" || destLonStr == "" {
		c.JSON(400, gin.H{"error": "origin_lat, origin_lon, dest_lat, dest_lon are required"})
		return
	}

	originLat, err := strconv.ParseFloat(originLatStr, 64)
	if err != nil {
		c.JSON(400, gin.H{"error": "Invalid origin_lat"})
		return
	}
	originLon, err := strconv.ParseFloat(originLonStr, 64)
	if err != nil {
		c.JSON(400, gin.H{"error": "Invalid origin_lon"})
		return
	}
	destLat, err := strconv.ParseFloat(destLatStr, 64)
	if err != nil {
		c.JSON(400, gin.H{"error": "Invalid dest_lat"})
		return
	}
	destLon, err := strconv.ParseFloat(destLonStr, 64)
	if err != nil {
		c.JSON(400, gin.H{"error": "Invalid dest_lon"})
		return
	}

	origin := entity.Location{Latitude: originLat, Longitude: originLon}
	dest := entity.Location{Latitude: destLat, Longitude: destLon}

	distance, err := sh.serviceService.CalculateRoute(origin, dest)
	if err != nil {
		c.JSON(400, gin.H{"error": err.Error()})
		return
	}
	duration := int(distance * 15)

	c.JSON(200, gin.H{
		"origin": gin.H{
			"latitude":  originLat,
			"longitude": originLon,
		},
		"destination": gin.H{
			"latitude":  destLat,
			"longitude": destLon,
		},
		"distance_km":  distance,
		"duration_min": duration,
	})
}

func (sh *ServicesHandler) CheckCache(c *gin.Context) bool {
	log.Println("Checking cache connection...")
	return true
}

func contextWithTimeout(c *gin.Context, d time.Duration) (context.Context, context.CancelFunc) {
	return context.WithTimeout(c.Request.Context(), d)
}
