package handlers

import (
	"net/http"
	"github.com/gin-gonic/gin"
)

// MarketplaceOrderHandler handles marketplace order operations
type MarketplaceOrderHandler struct{}

// NewMarketplaceOrderHandler creates a new handler
func NewMarketplaceOrderHandler() *MarketplaceOrderHandler {
	return &MarketplaceOrderHandler{}
}

// getCurrentUserID extracts user ID from context (set by auth middleware)
func getCurrentUserID(c *gin.Context) (string, bool) {
	userID, exists := c.Get("user_id")
	if !exists {
		return "", false
	}
	return userID.(string), true
}

// requireAuth ensures user is authenticated
func requireAuth(c *gin.Context) (string, bool) {
	userID, ok := getCurrentUserID(c)
	if !ok {
		c.JSON(http.StatusUnauthorized, gin.H{"error": "authentication required"})
		return "", false
	}
	return userID, true
}

// isAdmin checks if user has admin role
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

// CreateOrder creates a new marketplace order
func (h *MarketplaceOrderHandler) CreateOrder(c *gin.Context) {
	// Require authentication
	userID, ok := requireAuth(c)
	if !ok {
		return
	}

	var order struct {
		ItemID   string  `json:"item_id"`
		BuyerID  string  `json:"buyer_id"`
		Amount   float64 `json:"amount"`
		Quantity int     `json:"quantity"`
	}

	if err := c.ShouldBindJSON(&order); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	// User can only create orders for themselves unless admin
	if order.BuyerID != userID && !isAdmin(c) {
		c.JSON(http.StatusForbidden, gin.H{"error": "can only create orders for yourself"})
		return
	}

	// Create order logic
	c.JSON(http.StatusCreated, gin.H{
		"id":        order.ItemID,
		"status":    "pending",
		"buyer_id":  order.BuyerID,
	})
}

// GetOrder retrieves an order
func (h *MarketplaceOrderHandler) GetOrder(c *gin.Context) {
	// Require authentication
	userID, ok := requireAuth(c)
	if !ok {
		return
	}

	orderID := c.Param("id")

	// For now, return basic order info
	// In real implementation, would check ownership vs buyer/seller
	c.JSON(http.StatusOK, gin.H{
		"id":     orderID,
		"status": "completed",
		"buyer_id": userID,
	})
}