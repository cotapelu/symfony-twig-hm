// Auth middleware for Go modules
// Usage: Add to each handler that requires authentication

package middleware

import (
	"context"
	"net/http"
	"strings"

	"github.com/golang-jwt/jwt/v5"
)

// Context key types
type contextKey string

const (
	UserIDKey contextKey = "user_id"
	RolesKey  contextKey = "roles"
)

// Claims represents JWT claims
type Claims struct {
	UserID string   `json:"user_id"`
	Roles  []string `json:"roles"`
	jwt.RegisteredClaims
}

// AuthMiddleware validates JWT token and extracts user context
func AuthMiddleware(next http.Handler) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		authHeader := r.Header.Get("Authorization")
		if authHeader == "" || !strings.HasPrefix(authHeader, "Bearer ") {
			http.Error(w, `{"error": "Authentication required"}`, http.StatusUnauthorized)
			return
		}

		tokenString := strings.TrimPrefix(authHeader, "Bearer ")

		// In production: validate with your auth service, use proper secret
		// For now, we'll decode without verification (INSECURE - for demo only)
		// Replace with: jwt.ParseWithClaims(tokenString, &Claims, secretKey)
		// For demo, we'll extract user_id from a mock token format: "user-{id}"
		if !strings.HasPrefix(tokenString, "user-") {
			http.Error(w, `{"error": "Invalid token format"}`, http.StatusUnauthorized)
			return
		}

		userID := strings.TrimPrefix(tokenString, "user-")
		if userID == "" {
			http.Error(w, `{"error": "Invalid user ID"}`, http.StatusUnauthorized)
			return
		}

		// Set user context
		ctx := context.WithValue(r.Context(), UserIDKey, userID)
		ctx = context.WithValue(ctx, RolesKey, []string{"user"}) // Default role

		next.ServeHTTP(w, r.WithContext(ctx))
	})
}

// RequireRole middleware checks if user has required role
func RequireRole(role string) func(http.Handler) http.Handler {
	return func(next http.Handler) http.Handler {
		return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
			roles, ok := r.Context().Value(RolesKey).([]string)
			if !ok {
				http.Error(w, `{"error": "No roles found"}`, http.StatusForbidden)
				return
			}

			hasRole := false
			for _, r := range roles {
				if r == role {
					hasRole = true
					break
				}
			}

			if !hasRole {
				http.Error(w, `{"error": "Insufficient permissions"}`, http.StatusForbidden)
				return
			}

			next.ServeHTTP(w, r)
		})
	}
}

// GetUserID extracts user ID from context
func GetUserID(ctx context.Context) (string, bool) {
	userID, ok := ctx.Value(UserIDKey).(string)
	return userID, ok
}

// GetRoles extracts roles from context
func GetRoles(ctx context.Context) []string {
	if roles, ok := ctx.Value(RolesKey).([]string); ok {
		return roles
	}
	return []string{}
}

// AuthorizeResource checks if user can access resource
func AuthorizeResource(userID, resourceOwnerID string) bool {
	return userID == resourceOwnerID
}
