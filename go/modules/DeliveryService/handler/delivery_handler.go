package handler

import (
	"encoding/json"
	"log"
	"math"
	"strconv"
	"strings"
	"time"

	"community.local/modules/DeliveryService/service"
	"github.com/gin-gonic/gin"
)

const (
	MaxUserIDLength  = 100
	MaxOrderIDLength = 100
	MaxAddressLength = 500
	MaxRadius        = 50.0
	MinLatitude      = -90.0
	MaxLatitude      = 90.0
	MinLongitude     = -180.0
	MaxLongitude     = 180.0
)

type DeliveryHandler struct {
	deliveryService *service.DeliveryService
}

func NewDeliveryHandler(deliveryService *service.DeliveryService) *DeliveryHandler {
	return &DeliveryHandler{
		deliveryService: deliveryService,
	}
}

func (h *DeliveryHandler) extractUserID(c *gin.Context) string {
	return c.GetHeader("X-User-ID")
}

func (h *DeliveryHandler) extractUserRole(c *gin.Context) string {
	return c.GetHeader("X-User-Role")
}

func (h *DeliveryHandler) requireAuth(c *gin.Context) bool {
	userID := h.extractUserID(c)
	return userID != ""
}

func (h *DeliveryHandler) requireAdmin(c *gin.Context) bool {
	role := h.extractUserRole(c)
	return role == "ADMIN"
}

func (h *DeliveryHandler) validateUserID(userID string) string {
	if userID == "" {
		return "User ID is required"
	}
	if len(userID) > MaxUserIDLength {
		return "User ID too long"
	}
	return ""
}

func (h *DeliveryHandler) validateOrderID(orderID string) string {
	if orderID == "" {
		return "Order ID is required"
	}
	if len(orderID) > MaxOrderIDLength {
		return "Order ID too long"
	}
	return ""
}

func (h *DeliveryHandler) validateCoordinate(lat, lng float64) string {
	if lat < MinLatitude || lat > MaxLatitude {
		return "Latitude must be between -90 and 90"
	}
	if lng < MinLongitude || lng > MaxLongitude {
		return "Longitude must be between -180 and 180"
	}
	return ""
}

func (h *DeliveryHandler) validateRadius(radius float64) string {
	if radius < 0 || radius > MaxRadius {
		return "Radius must be between 0 and 50 km"
	}
	return ""
}

func (h *DeliveryHandler) validateAddress(addr string) string {
	if len(addr) > MaxAddressLength {
		return "Address too long"
	}
	return ""
}

func (h *DeliveryHandler) RegisterRoutes(router *gin.Engine) {
	api := router.Group("/api/delivery")
	{
		api.POST("/online", h.SetOnline)
		api.POST("/offline", h.SetOffline)
		api.POST("/location", h.UpdateLocation)
		api.GET("/nearby", h.GetNearbyDeliveryPersons)
		api.POST("/assign", h.AssignOrder)
		api.POST("/start", h.StartDelivery)
		api.POST("/complete", h.CompleteDelivery)
		api.GET("/order/:orderId", h.GetDeliveryOrder)
		api.GET("/active/:deliveryPersonId", h.GetActiveDeliveries)
		api.GET("/stats", h.GetStats)
	}
}

type SetOnlineRequest struct {
	UserID    string  `json:"user_id" binding:"required"`
	Latitude  float64 `json:"latitude" binding:"required"`
	Longitude float64 `json:"longitude" binding:"required"`
}

func (h *DeliveryHandler) SetOnline(c *gin.Context) {
	if !h.requireAuth(c) {
		c.JSON(401, gin.H{"error": "Authentication required"})
		return
	}

	var req SetOnlineRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(400, gin.H{"error": err.Error()})
		return
	}

	if err := h.validateUserID(req.UserID); err != "" {
		c.JSON(400, gin.H{"error": err})
		return
	}

	if err := h.validateCoordinate(req.Latitude, req.Longitude); err != "" {
		c.JSON(400, gin.H{"error": err})
		return
	}

	authenticatedUserID := h.extractUserID(c)
	if authenticatedUserID != req.UserID {
		c.JSON(403, gin.H{"error": "Cannot set online for other users"})
		return
	}

	dp, err := h.deliveryService.SetOnline(req.UserID, req.Latitude, req.Longitude)
	if err != nil {
		c.JSON(500, gin.H{"error": err.Error()})
		return
	}

	c.JSON(200, gin.H{
		"success": true,
		"deliveryPerson": gin.H{
			"id":        dp.ID,
			"user_id":   dp.UserID,
			"status":    dp.Status,
			"is_online": dp.IsOnline,
			"rating":    dp.Rating,
		},
	})
}

type SetOfflineRequest struct {
	UserID string `json:"user_id" binding:"required"`
}

func (h *DeliveryHandler) SetOffline(c *gin.Context) {
	if !h.requireAuth(c) {
		c.JSON(401, gin.H{"error": "Authentication required"})
		return
	}

	var req SetOfflineRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(400, gin.H{"error": err.Error()})
		return
	}

	if err := h.validateUserID(req.UserID); err != "" {
		c.JSON(400, gin.H{"error": err})
		return
	}

	authenticatedUserID := h.extractUserID(c)
	if authenticatedUserID != req.UserID {
		c.JSON(403, gin.H{"error": "Cannot set offline for other users"})
		return
	}

	h.deliveryService.SetOffline(req.UserID)
	c.JSON(200, gin.H{"success": true})
}

type UpdateLocationRequest struct {
	UserID    string  `json:"user_id" binding:"required"`
	Latitude  float64 `json:"latitude" binding:"required"`
	Longitude float64 `json:"longitude" binding:"required"`
}

func (h *DeliveryHandler) UpdateLocation(c *gin.Context) {
	if !h.requireAuth(c) {
		c.JSON(401, gin.H{"error": "Authentication required"})
		return
	}

	var req UpdateLocationRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(400, gin.H{"error": err.Error()})
		return
	}

	if err := h.validateUserID(req.UserID); err != "" {
		c.JSON(400, gin.H{"error": err})
		return
	}

	if err := h.validateCoordinate(req.Latitude, req.Longitude); err != "" {
		c.JSON(400, gin.H{"error": err})
		return
	}

	authenticatedUserID := h.extractUserID(c)
	if authenticatedUserID != req.UserID {
		c.JSON(403, gin.H{"error": "Cannot update location for other users"})
		return
	}

	h.deliveryService.UpdateLocation(req.UserID, req.Latitude, req.Longitude)
	c.JSON(200, gin.H{"success": true})
}

func (h *DeliveryHandler) GetNearbyDeliveryPersons(c *gin.Context) {
	if !h.requireAuth(c) {
		c.JSON(401, gin.H{"error": "Authentication required"})
		return
	}

	lat, err := strconv.ParseFloat(c.DefaultQuery("latitude", "0"), 64)
	if err != nil {
		c.JSON(400, gin.H{"error": "Invalid latitude"})
		return
	}

	lng, err := strconv.ParseFloat(c.DefaultQuery("longitude", "0"), 64)
	if err != nil {
		c.JSON(400, gin.H{"error": "Invalid longitude"})
		return
	}

	radius, err := strconv.ParseFloat(c.DefaultQuery("radius", "5"), 64)
	if err != nil {
		c.JSON(400, gin.H{"error": "Invalid radius"})
		return
	}

	if err := h.validateCoordinate(lat, lng); err != "" {
		c.JSON(400, gin.H{"error": err})
		return
	}

	if err := h.validateRadius(radius); err != "" {
		c.JSON(400, gin.H{"error": err})
		return
	}

	persons := h.deliveryService.FindNearbyDeliveryPersons(lat, lng, radius)
	c.JSON(200, gin.H{
		"count":           len(persons),
		"deliveryPersons": persons,
	})
}

type AssignOrderRequest struct {
	OrderID        string  `json:"order_id" binding:"required"`
	RestaurantLat  float64 `json:"restaurant_latitude" binding:"required"`
	RestaurantLng  float64 `json:"restaurant_longitude" binding:"required"`
	RestaurantAddr string  `json:"restaurant_address"`
	CustomerLat    float64 `json:"customer_latitude" binding:"required"`
	CustomerLng    float64 `json:"customer_longitude" binding:"required"`
	CustomerAddr   string  `json:"customer_address"`
}

func (h *DeliveryHandler) AssignOrder(c *gin.Context) {
	if !h.requireAdmin(c) {
		c.JSON(403, gin.H{"error": "Admin access required"})
		return
	}

	var req AssignOrderRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(400, gin.H{"error": err.Error()})
		return
	}

	if err := h.validateOrderID(req.OrderID); err != "" {
		c.JSON(400, gin.H{"error": err})
		return
	}

	if err := h.validateCoordinate(req.RestaurantLat, req.RestaurantLng); err != "" {
		c.JSON(400, gin.H{"error": "Invalid restaurant location: " + err})
		return
	}

	if err := h.validateCoordinate(req.CustomerLat, req.CustomerLng); err != "" {
		c.JSON(400, gin.H{"error": "Invalid customer location: " + err})
		return
	}

	if err := h.validateAddress(req.RestaurantAddr); err != "" {
		c.JSON(400, gin.H{"error": err})
		return
	}

	if err := h.validateAddress(req.CustomerAddr); err != "" {
		c.JSON(400, gin.H{"error": err})
		return
	}

	order, err := h.deliveryService.AssignOrder(
		req.OrderID,
		req.RestaurantLat, req.RestaurantLng,
		req.CustomerLat, req.CustomerLng,
		req.CustomerAddr, req.RestaurantAddr,
	)
	if err != nil {
		c.JSON(400, gin.H{"error": err.Error()})
		return
	}

	c.JSON(200, gin.H{
		"success":       true,
		"deliveryOrder": order,
	})
}

type StartDeliveryRequest struct {
	OrderID string `json:"order_id" binding:"required"`
}

func (h *DeliveryHandler) StartDelivery(c *gin.Context) {
	if !h.requireAuth(c) {
		c.JSON(401, gin.H{"error": "Authentication required"})
		return
	}

	var req StartDeliveryRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(400, gin.H{"error": err.Error()})
		return
	}

	if err := h.validateOrderID(req.OrderID); err != "" {
		c.JSON(400, gin.H{"error": err})
		return
	}

	order, err := h.deliveryService.StartDelivery(req.OrderID)
	if err != nil {
		c.JSON(400, gin.H{"error": err.Error()})
		return
	}

	c.JSON(200, gin.H{
		"success":       true,
		"deliveryOrder": order,
		"started_at":    time.Now().Format(time.RFC3339),
	})
}

type CompleteDeliveryRequest struct {
	OrderID string `json:"order_id" binding:"required"`
}

func (h *DeliveryHandler) CompleteDelivery(c *gin.Context) {
	if !h.requireAuth(c) {
		c.JSON(401, gin.H{"error": "Authentication required"})
		return
	}

	var req CompleteDeliveryRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(400, gin.H{"error": err.Error()})
		return
	}

	if err := h.validateOrderID(req.OrderID); err != "" {
		c.JSON(400, gin.H{"error": err})
		return
	}

	order, err := h.deliveryService.CompleteDelivery(req.OrderID)
	if err != nil {
		c.JSON(400, gin.H{"error": err.Error()})
		return
	}

	c.JSON(200, gin.H{
		"success":       true,
		"deliveryOrder": order,
		"delivered_at":  time.Now().Format(time.RFC3339),
	})
}

func (h *DeliveryHandler) GetDeliveryOrder(c *gin.Context) {
	if !h.requireAuth(c) {
		c.JSON(401, gin.H{"error": "Authentication required"})
		return
	}

	orderID := c.Param("orderId")
	if err := h.validateOrderID(orderID); err != "" {
		c.JSON(400, gin.H{"error": err})
		return
	}

	order, err := h.deliveryService.GetDeliveryOrder(orderID)
	if err != nil {
		c.JSON(404, gin.H{"error": err.Error()})
		return
	}

	// Ownership check: user must be the assigned delivery person or admin
	userID := h.extractUserID(c)
	if order.DeliveryPersonID != userID && !h.requireAdmin(c) {
		c.JSON(403, gin.H{"error": "access denied: you can only view your own deliveries"})
		return
	}

	c.JSON(200, gin.H{
		"deliveryOrder": order,
	})
}

func (h *DeliveryHandler) GetActiveDeliveries(c *gin.Context) {
	if !h.requireAuth(c) {
		c.JSON(401, gin.H{"error": "Authentication required"})
		return
	}

	deliveryPersonID := c.Param("deliveryPersonId")
	if err := h.validateUserID(deliveryPersonID); err != "" {
		c.JSON(400, gin.H{"error": err})
		return
	}

	authenticatedUserID := h.extractUserID(c)
	userRole := h.extractUserRole(c)

	if authenticatedUserID != deliveryPersonID && userRole != "ADMIN" {
		c.JSON(403, gin.H{"error": "Cannot view other delivery person's orders"})
		return
	}

	orders := h.deliveryService.GetActiveDeliveries(deliveryPersonID)
	c.JSON(200, gin.H{
		"count":  len(orders),
		"orders": orders,
	})
}

func (h *DeliveryHandler) GetStats(c *gin.Context) {
	if !h.requireAdmin(c) {
		c.JSON(403, gin.H{"error": "Admin access required"})
		return
	}

	c.JSON(200, gin.H{
		"status":    "operational",
		"service":   "DeliveryService",
		"timestamp": time.Now().Format(time.RFC3339),
	})
}

func (h *DeliveryHandler) HealthCheck(c *gin.Context) {
	c.JSON(200, gin.H{
		"status":    "healthy",
		"timestamp": time.Now().Format(time.RFC3339),
	})
}

func (h *DeliveryHandler) ProcessOrderAssignment(orderData json.RawMessage) {
	var data map[string]interface{}
	if err := json.Unmarshal(orderData, &data); err != nil {
		log.Printf("Error parsing order data: %v", err)
		return
	}

	orderID, _ := data["order_id"].(string)
	restaurant, _ := data["restaurant"].(map[string]interface{})
	customer, _ := data["customer"].(map[string]interface{})

	if orderID == "" || restaurant == nil || customer == nil {
		log.Printf("Invalid order data")
		return
	}

	restaurantLat, _ := restaurant["latitude"].(float64)
	restaurantLng, _ := restaurant["longitude"].(float64)
	restaurantAddr, _ := restaurant["address"].(string)
	customerLat, _ := customer["latitude"].(float64)
	customerLng, _ := customer["longitude"].(float64)
	customerAddr, _ := customer["address"].(string)

	if !math.IsNaN(customerLat) && !math.IsNaN(customerLng) {
		order, err := h.deliveryService.AssignOrder(
			orderID,
			restaurantLat, restaurantLng,
			customerLat, customerLng,
			customerAddr, restaurantAddr,
		)
		if err != nil {
			log.Printf("Failed to assign order %s: %v", orderID, err)
			return
		}

		log.Printf("Order %s auto-assigned to delivery person %s", orderID, order.DeliveryPersonID)
	}
}

func (h *DeliveryHandler) HandleOrderStatusUpdate(orderID string, status string) {
	if orderID == "" {
		return
	}

	switch strings.ToLower(status) {
	case "ready":
		h.deliveryService.StartDelivery(orderID)
	case "delivered":
		h.deliveryService.CompleteDelivery(orderID)
	}
}
