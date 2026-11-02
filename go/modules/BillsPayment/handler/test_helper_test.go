package handler

import (
	"github.com/gin-gonic/gin"
)

// authMiddleware creates a test middleware that sets up JWT authentication
func authMiddleware() gin.HandlerFunc {
	return func(c *gin.Context) {
		// Check for X-User-ID header and create JWT token if present
		if uid := c.GetHeader("X-User-ID"); uid != "" {
			roles := []string{}
			if roleHeader := c.GetHeader("X-User-Roles"); roleHeader != "" {
				roles = []string{roleHeader}
			}
			token := createTestToken(uid, roles)
			c.Request.Header.Set("Authorization", "Bearer "+token)
		}
		c.Next()
	}
}