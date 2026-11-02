package handler

import (
	"database/sql"
	"net/http"
	"strconv"

	courier "community.local/modules/Courier/internal/models"

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

// CourierHandler handles courier HTTP requests
type CourierHandler struct {
	svc *courier.CourierService
}

// NewCourierHandler creates a new courier handler
func NewCourierHandler(db *sql.DB) *CourierHandler {
	return &CourierHandler{
		svc: courier.NewCourierService(db),
	}
}

// RegisterRoutes registers all courier routes
func (h *CourierHandler) RegisterRoutes(router *gin.Engine) {
	courier := router.Group("/api/courier")
	{
		// Static routes
		courier.POST("/register", h.RegisterCourier)
		courier.GET("/available", h.GetAvailableCouriers)

		// Order routes
		courier.POST("/orders", h.CreateOrder)
		courier.GET("/orders/:id", h.GetOrder)
		courier.POST("/orders/:id/assign", h.AssignOrder)
		courier.POST("/orders/:id/pickup", h.PickupOrder)
		courier.POST("/orders/:id/deliver", h.DeliverOrder)
		courier.GET("/orders/courier/:courier_id", h.GetCourierOrders)
		courier.GET("/orders/courier/:courier_id/active", h.GetActiveOrders)

		// Parametric routes (must come after static to avoid conflicts)
		courier.GET("/:id", h.GetCourier)
		courier.PUT("/:id/location", h.UpdateLocation)
		courier.PUT("/:id/status", h.UpdateStatus)
	}
}

// RegisterCourier registers a new courier
func (h *CourierHandler) RegisterCourier(c *gin.Context) {
	// Require authentication
	authUserID, ok := requireAuth(c)
	if !ok {
		return
	}

	var req struct {
		UserID        string `json:"user_id" binding:"required"`
		Name          string `json:"name" binding:"required"`
		Phone         string `json:"phone" binding:"required"`
		Email         string `json:"email"`
		VehicleType   string `json:"vehicle_type" binding:"required"`
		VehicleNumber string `json:"vehicle_number" binding:"required"`
	}

	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	// Check ownership: user can only register for themselves unless admin
	if req.UserID != authUserID && !isAdmin(c) {
		c.JSON(403, gin.H{"error": "access denied: can only register courier for yourself"})
		return
	}

	if req.Email == "" {
		req.Email = req.UserID + "@example.com"
	}

	courier, err := h.svc.RegisterCourier(req.UserID, req.Name, req.Phone, req.Email, req.VehicleType, req.VehicleNumber)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusCreated, gin.H{"courier": courier})
}

// GetCourier retrieves a courier by ID
func (h *CourierHandler) GetCourier(c *gin.Context) {
	// Require authentication
	if _, ok := requireAuth(c); !ok {
		return
	}

	id, err := strconv.ParseInt(c.Param("id"), 10, 64)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "invalid courier ID"})
		return
	}

	courier, err := h.svc.GetCourier(id)
	if err != nil {
		c.JSON(http.StatusNotFound, gin.H{"error": "courier not found"})
		return
	}

	// Check ownership: only the courier or admin can view details
	authUserID, _ := getCurrentUserID(c)
	if courier.UserID != authUserID && !isAdmin(c) {
		c.JSON(403, gin.H{"error": "access denied: you can only view your own courier profile"})
		return
	}

	c.JSON(http.StatusOK, gin.H{"courier": courier})
}

// GetAvailableCouriers retrieves all available couriers
func (h *CourierHandler) GetAvailableCouriers(c *gin.Context) {
	couriers, err := h.svc.GetAvailableCouriers()
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusOK, gin.H{"couriers": couriers, "count": len(couriers)})
}

// UpdateLocation updates courier location
func (h *CourierHandler) UpdateLocation(c *gin.Context) {
	// Require authentication
	if _, ok := requireAuth(c); !ok {
		return
	}

	id, err := strconv.ParseInt(c.Param("id"), 10, 64)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "invalid courier ID"})
		return
	}

	// Check ownership: fetch courier and verify
	courier, err := h.svc.GetCourier(id)
	if err != nil {
		c.JSON(http.StatusNotFound, gin.H{"error": "courier not found"})
		return
	}
	authUserID, _ := getCurrentUserID(c)
	if courier.UserID != authUserID && !isAdmin(c) {
		c.JSON(403, gin.H{"error": "access denied: you can only update your own location"})
		return
	}

	var req struct {
		Latitude  float64 `json:"latitude" binding:"required"`
		Longitude float64 `json:"longitude" binding:"required"`
	}

	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	if err := h.svc.UpdateLocation(id, req.Latitude, req.Longitude); err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusOK, gin.H{"message": "location updated"})
}

// UpdateStatus updates courier status
func (h *CourierHandler) UpdateStatus(c *gin.Context) {
	// Require authentication
	if _, ok := requireAuth(c); !ok {
		return
	}

	id, err := strconv.ParseInt(c.Param("id"), 10, 64)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "invalid courier ID"})
		return
	}

	// Check ownership: fetch courier and verify
	courier, err := h.svc.GetCourier(id)
	if err != nil {
		c.JSON(http.StatusNotFound, gin.H{"error": "courier not found"})
		return
	}
	authUserID, _ := getCurrentUserID(c)
	if courier.UserID != authUserID && !isAdmin(c) {
		c.JSON(403, gin.H{"error": "access denied: you can only update your own status"})
		return
	}

	var req struct {
		Status string `json:"status" binding:"required"`
	}

	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	if err := h.svc.UpdateStatus(id, req.Status); err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusOK, gin.H{"message": "status updated"})
}

// CreateOrder creates a new delivery order
func (h *CourierHandler) CreateOrder(c *gin.Context) {
	// Require authentication
	if _, ok := requireAuth(c); !ok {
		return
	}

	var req struct {
		OrderID         string  `json:"order_id" binding:"required"`
		PickupAddress   string  `json:"pickup_address" binding:"required"`
		PickupLat       float64 `json:"pickup_lat" binding:"required"`
		PickupLng       float64 `json:"pickup_lng" binding:"required"`
		DeliveryAddress string  `json:"delivery_address" binding:"required"`
		DeliveryLat     float64 `json:"delivery_lat" binding:"required"`
		DeliveryLng     float64 `json:"delivery_lng" binding:"required"`
		CustomerName    string  `json:"customer_name" binding:"required"`
		CustomerPhone   string  `json:"customer_phone" binding:"required"`
		PackageDesc     string  `json:"package_desc"`
		PackageWeight   float64 `json:"package_weight" binding:"required"`
	}

	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	if req.PackageDesc == "" {
		req.PackageDesc = "No description"
	}

	order, fee := h.svc.CreateOrder(
		req.OrderID, req.PickupAddress, req.PickupLat, req.PickupLng,
		req.DeliveryAddress, req.DeliveryLat, req.DeliveryLng,
		req.CustomerName, req.CustomerPhone, req.PackageDesc, req.PackageWeight,
	)

	c.JSON(http.StatusCreated, gin.H{"order": order, "estimated_fee": fee})
}

// GetOrder retrieves an order by ID
func (h *CourierHandler) GetOrder(c *gin.Context) {
	// Require authentication
	if _, ok := requireAuth(c); !ok {
		return
	}

	id, err := strconv.ParseInt(c.Param("id"), 10, 64)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "invalid order ID"})
		return
	}

	order, err := h.svc.GetOrderByID(id)
	if err != nil {
		c.JSON(http.StatusNotFound, gin.H{"error": "order not found"})
		return
	}

	// Check ownership: fetch courier and verify
	courier, err := h.svc.GetCourier(order.CourierID)
	if err != nil {
		c.JSON(http.StatusNotFound, gin.H{"error": "courier not found"})
		return
	}
	authUserID, _ := getCurrentUserID(c)
	if courier.UserID != authUserID && !isAdmin(c) {
		c.JSON(403, gin.H{"error": "access denied: you can only view your own orders"})
		return
	}

	c.JSON(http.StatusOK, gin.H{"order": order})
}

// AssignOrder assigns an order to a courier
func (h *CourierHandler) AssignOrder(c *gin.Context) {
	// Require authentication and admin role
	if _, ok := requireAuth(c); !ok {
		return
	}
	if !isAdmin(c) {
		c.JSON(403, gin.H{"error": "access denied: only admin can assign orders"})
		return
	}

	orderID, err := strconv.ParseInt(c.Param("id"), 10, 64)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "invalid order ID"})
		return
	}

	var req struct {
		CourierID int64 `json:"courier_id" binding:"required"`
	}

	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	order, err := h.svc.AssignOrder(orderID, req.CourierID)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusOK, gin.H{"order": order, "message": "order assigned"})
}

// PickupOrder marks an order as picked up
func (h *CourierHandler) PickupOrder(c *gin.Context) {
	// Require authentication
	if _, ok := requireAuth(c); !ok {
		return
	}

	orderID, err := strconv.ParseInt(c.Param("id"), 10, 64)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "invalid order ID"})
		return
	}

	// Check that the courier is assigned to this order
	order, err := h.svc.GetOrderByID(orderID)
	if err != nil {
		c.JSON(http.StatusNotFound, gin.H{"error": "order not found"})
		return
	}
	// Ownership: the order's courier's UserID must match auth user, or admin
	courier, err := h.svc.GetCourier(order.CourierID)
	if err != nil {
		c.JSON(http.StatusNotFound, gin.H{"error": "courier not found"})
		return
	}
	authUserID, _ := getCurrentUserID(c)
	if courier.UserID != authUserID && !isAdmin(c) {
		c.JSON(403, gin.H{"error": "access denied: only the assigned courier can pickup"})
		return
	}

	if err := h.svc.PickupOrder(orderID); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusOK, gin.H{"message": "order picked up"})
}

// DeliverOrder marks an order as delivered
func (h *CourierHandler) DeliverOrder(c *gin.Context) {
	// Require authentication
	if _, ok := requireAuth(c); !ok {
		return
	}

	orderID, err := strconv.ParseInt(c.Param("id"), 10, 64)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "invalid order ID"})
		return
	}

	// Check that the courier is assigned to this order
	order, err := h.svc.GetOrderByID(orderID)
	if err != nil {
		c.JSON(http.StatusNotFound, gin.H{"error": "order not found"})
		return
	}
	courier, err := h.svc.GetCourier(order.CourierID)
	if err != nil {
		c.JSON(http.StatusNotFound, gin.H{"error": "courier not found"})
		return
	}
	authUserID, _ := getCurrentUserID(c)
	if courier.UserID != authUserID && !isAdmin(c) {
		c.JSON(403, gin.H{"error": "access denied: only the assigned courier can deliver"})
		return
	}

	if err := h.svc.DeliverOrder(orderID); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusOK, gin.H{"message": "order delivered"})
}

// GetCourierOrders retrieves all orders for a courier with pagination
func (h *CourierHandler) GetCourierOrders(c *gin.Context) {
	// Require authentication
	if _, ok := requireAuth(c); !ok {
		return
	}

	courierID, err := strconv.ParseInt(c.Param("courier_id"), 10, 64)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "invalid courier ID"})
		return
	}

	// Check ownership: fetch courier and verify
	courier, err := h.svc.GetCourier(courierID)
	if err != nil {
		c.JSON(http.StatusNotFound, gin.H{"error": "courier not found"})
		return
	}
	authUserID, _ := getCurrentUserID(c)
	if courier.UserID != authUserID && !isAdmin(c) {
		c.JSON(403, gin.H{"error": "access denied: you can only view your own orders"})
		return
	}

	// Get pagination params
	page, _ := strconv.Atoi(c.DefaultQuery("page", "1"))
	limit, _ := strconv.Atoi(c.DefaultQuery("limit", "20"))
	
	paginatedResult, err := h.svc.GetCourierOrdersWithPagination(courierID, page, limit)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusOK, paginatedResult)
}

// GetActiveOrders retrieves active orders for a courier
func (h *CourierHandler) GetActiveOrders(c *gin.Context) {
	// Require authentication
	if _, ok := requireAuth(c); !ok {
		return
	}

	courierID, err := strconv.ParseInt(c.Param("courier_id"), 10, 64)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "invalid courier ID"})
		return
	}

	// Check ownership: fetch courier and verify
	courier, err := h.svc.GetCourier(courierID)
	if err != nil {
		c.JSON(http.StatusNotFound, gin.H{"error": "courier not found"})
		return
	}
	authUserID, _ := getCurrentUserID(c)
	if courier.UserID != authUserID && !isAdmin(c) {
		c.JSON(403, gin.H{"error": "access denied: you can only view your own orders"})
		return
	}

	orders, err := h.svc.GetActiveOrders(courierID)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusOK, gin.H{"orders": orders, "count": len(orders)})
}
