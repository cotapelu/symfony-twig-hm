package middleware

import (
	"net/http"
	"os"
	"strings"

	"github.com/gin-gonic/gin"
	"github.com/golang-jwt/jwt/v5"
)

type AuthMiddleware struct {
	JWTSecret string
}

func NewAuthMiddleware(jwtSecret string) *AuthMiddleware {
	return &AuthMiddleware{JWTSecret: jwtSecret}
}

// DefaultAuthMiddleware creates an AuthMiddleware with secret from environment
func DefaultAuthMiddleware() *AuthMiddleware {
	secret := os.Getenv("JWT_SECRET")
	if secret == "" {
		secret = "dev-secret-change-in-production"
	}
	return NewAuthMiddleware(secret)
}

func (m *AuthMiddleware) AuthRequired() gin.HandlerFunc {
	return func(c *gin.Context) {
		authHeader := c.GetHeader("Authorization")
		if authHeader == "" {
			c.JSON(http.StatusUnauthorized, gin.H{"error": "Authorization header required"})
			c.Abort()
			return
		}

		// Extract token from "Bearer <token>"
		parts := strings.SplitN(authHeader, " ", 2)
		if len(parts) != 2 || !strings.EqualFold(parts[0], "Bearer") {
			c.JSON(http.StatusUnauthorized, gin.H{"error": "Invalid Authorization header format"})
			c.Abort()
			return
		}
		tokenString := parts[1]

		// Validate JWT token
		token, err := jwt.Parse(tokenString, func(token *jwt.Token) (interface{}, error) {
			// Validate signing method
			if _, ok := token.Method.(*jwt.SigningMethodHMAC); !ok {
				return nil, jwt.ErrTokenInvalidClaims
			}
			return []byte(m.JWTSecret), nil
		})

		if err != nil || !token.Valid {
			c.JSON(http.StatusUnauthorized, gin.H{"error": "Invalid or expired token"})
			c.Abort()
			return
		}

		// Extract claims
		if claims, ok := token.Claims.(jwt.MapClaims); ok {
			userID, ok := claims["userId"].(string)
			if !ok || userID == "" {
				c.JSON(http.StatusUnauthorized, gin.H{"error": "Token missing user ID"})
				c.Abort()
				return
			}
			// Set user info in context
			c.Set("user_id", userID)
			if roles, ok := claims["roles"].([]interface{}); ok {
				// Convert to string array
				roleStrs := make([]string, 0, len(roles))
				for _, r := range roles {
					if s, ok := r.(string); ok {
						roleStrs = append(roleStrs, s)
					}
				}
				c.Set("user_roles", roleStrs)
			}
		} else {
			c.JSON(http.StatusUnauthorized, gin.H{"error": "Invalid token claims"})
			c.Abort()
			return
		}

		c.Next()
	}
}

// AuthRequired returns a Gin handler that validates JWT authentication
func AuthRequired() gin.HandlerFunc {
	return DefaultAuthMiddleware().AuthRequired()
}
