package handler

import (
	"net/http"

	"github.com/gin-gonic/gin"
)

// CitizenHandler handles citizen HTTP requests
type CitizenHandler struct{}

// NewCitizenHandler creates a new citizen handler
func NewCitizenHandler() *CitizenHandler {
	return &CitizenHandler{}
}

// RegisterRoutes registers all citizen routes
func (h *CitizenHandler) RegisterRoutes(router *gin.Engine) {
	citizen := router.Group("/api/citizen")
	{
		citizen.POST("", h.Create)
		citizen.GET("/:id", h.Get)
		citizen.GET("/search", h.Search)
	}
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

// Create creates a new citizen
func (h *CitizenHandler) Create(c *gin.Context) {
	var req struct {
		CitizenID string `json:"citizenId" binding:"required"`
		FullName  string `json:"fullName" binding:"required"`
		DOB       string `json:"dateOfBirth"`
	}

	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	// Auth: user can only create their own citizen profile
	if userID, ok := requireAuth(c); !ok {
		return
	} else if userID != req.CitizenID && !isAdmin(c) {
		c.JSON(http.StatusForbidden, gin.H{"error": "can only create profile for yourself"})
		return
	}

	c.JSON(http.StatusCreated, gin.H{
		"id":        "citizen-123",
		"citizenId": req.CitizenID,
		"fullName":  req.FullName,
		"status":    "active",
	})
}

// Get retrieves a citizen by ID
func (h *CitizenHandler) Get(c *gin.Context) {
	id := c.Param("id")

	// Auth: user can only view their own profile (or admin)
	if userID, ok := requireAuth(c); !ok {
		return
	} else if userID != id && !isAdmin(c) {
		c.JSON(http.StatusForbidden, gin.H{"error": "can only view your own profile"})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"id":       id,
		"fullName": "Test Citizen",
		"status":   "active",
	})
}

// Search searches for citizens
func (h *CitizenHandler) Search(c *gin.Context) {
	// Auth: only admin can search citizens
	if _, ok := requireAuth(c); !ok {
		return
	}
	if !isAdmin(c) {
		c.JSON(http.StatusForbidden, gin.H{"error": "only admin can search citizens"})
		return
	}

	_ = c.Query("q") // query parameter for search
	page := c.DefaultQuery("page", "1")
	limit := c.DefaultQuery("limit", "20")

	c.JSON(http.StatusOK, gin.H{
		"data": []interface{}{},
		"pagination": gin.H{
			"current_page": page,
			"per_page":     limit,
			"total_items":  0,
			"total_pages":  0,
		},
	})
}