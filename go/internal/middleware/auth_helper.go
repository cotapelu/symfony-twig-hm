package middleware

import (
	"net/http"

	"github.com/gin-gonic/gin"
)

// RequireAuth ensures user is authenticated
func RequireAuth() gin.HandlerFunc {
	return func(c *gin.Context) {
		userID, exists := c.Get("user_id")
		if !exists {
			c.AbortWithStatusJSON(http.StatusUnauthorized, gin.H{"error": "authentication required"})
			return
		}
		_ = userID // validated
		c.Next()
	}
}

// RequireAdmin ensures user has admin role
func RequireAdmin() gin.HandlerFunc {
	return func(c *gin.Context) {
		if roles, ok := c.Get("user_roles"); ok {
			for _, r := range roles.([]string) {
				if r == "ROLE_ADMIN" || r == "admin" {
					c.Next()
					return
				}
			}
		}
		c.AbortWithStatusJSON(http.StatusForbidden, gin.H{"error": "admin access required"})
	}
}

// GetCurrentUserID extracts user ID from context
func GetCurrentUserID(c *gin.Context) (string, bool) {
	userID, exists := c.Get("user_id")
	if !exists {
		return "", false
	}
	return userID.(string), true
}

// IsAdmin checks if user has admin role
func IsAdmin(c *gin.Context) bool {
	if roles, ok := c.Get("user_roles"); ok {
		for _, r := range roles.([]string) {
			if r == "ROLE_ADMIN" || r == "admin" {
				return true
			}
		}
	}
	return false
}