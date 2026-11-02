package handlers

import (
	"net/http"

	"github.com/gin-gonic/gin"
)

// LandPermitHandler handles land permit operations
type LandPermitHandler struct{}

// NewLandPermitHandler creates a new handler
func NewLandPermitHandler() *LandPermitHandler {
	return &LandPermitHandler{}
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

// CreatePermit creates a new land permit application
func (h *LandPermitHandler) CreatePermit(c *gin.Context) {
	userID, ok := requireAuth(c)
	if !ok {
		return
	}

	var permit struct {
		LandRecordID string  `json:"land_record_id"`
		OwnerID      string  `json:"owner_id"`
		PermitType   string  `json:"permit_type"` // building, farming, commercial
		Purpose      string  `json:"purpose"`
		Area         float64 `json:"area"`
	}

	if err := c.ShouldBindJSON(&permit); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	// User can only create permits for themselves unless admin
	if permit.OwnerID != userID && !isAdmin(c) {
		c.JSON(http.StatusForbidden, gin.H{"error": "can only create permits for yourself"})
		return
	}

	// Create permit logic
	c.JSON(http.StatusCreated, gin.H{
		"id":            "permit-123",
		"status":        "pending",
		"land_record_id": permit.LandRecordID,
		"owner_id":      permit.OwnerID,
	})
}

// GetPermit retrieves a permit
func (h *LandPermitHandler) GetPermit(c *gin.Context) {
	userID, ok := requireAuth(c)
	if !ok {
		return
	}

	permitID := c.Param("id")

	// TODO: Check if user owns this permit or is admin
	c.JSON(http.StatusOK, gin.H{
		"id":          permitID,
		"status":      "approved",
		"permit_type": "building",
		"owner_id":    userID,
	})
}