package middleware

import (
	"net/http"
	"net/http/httptest"
	"testing"
	"time"

	"github.com/gin-gonic/gin"
	"github.com/golang-jwt/jwt/v5"
)

const testSecret = "test-secret"

func newValidToken(userID string, roles []string) string {
	claims := jwt.MapClaims{
		"userId": userID,
		"roles":  roles,
		"exp":    jwt.NewNumericDate(time.Now().Add(1 * time.Hour)), // future
	}
	token := jwt.NewWithClaims(jwt.SigningMethodHS256, claims)
	signed, _ := token.SignedString([]byte(testSecret))
	return signed
}

func TestAuthMiddleware_MissingAuthorization(t *testing.T) {
	m := NewAuthMiddleware(testSecret)
	handler := m.AuthRequired()

	w := httptest.NewRecorder()
	c, _ := gin.CreateTestContext(w)
	req := httptest.NewRequest("GET", "/", nil)
	c.Request = req
	handler(c)

	if w.Code != http.StatusUnauthorized {
		t.Errorf("Status code = %d, want %d", w.Code, http.StatusUnauthorized)
	}
}

func TestAuthMiddleware_InvalidFormat(t *testing.T) {
	m := NewAuthMiddleware(testSecret)
	handler := m.AuthRequired()

	w := httptest.NewRecorder()
	c, _ := gin.CreateTestContext(w)
	req := httptest.NewRequest("GET", "/", nil)
	req.Header.Set("Authorization", "BadToken")
	c.Request = req
	handler(c)

	if w.Code != http.StatusUnauthorized {
		t.Errorf("Status code = %d, want %d", w.Code, http.StatusUnauthorized)
	}
}

func TestAuthMiddleware_InvalidToken(t *testing.T) {
	m := NewAuthMiddleware(testSecret)
	handler := m.AuthRequired()

	w := httptest.NewRecorder()
	c, _ := gin.CreateTestContext(w)
	req := httptest.NewRequest("GET", "/", nil)
	req.Header.Set("Authorization", "Bearer invalid.token.here")
	c.Request = req
	handler(c)

	if w.Code != http.StatusUnauthorized {
		t.Errorf("Status code = %d, want %d", w.Code, http.StatusUnauthorized)
	}
}

func TestAuthMiddleware_ValidToken(t *testing.T) {
	m := NewAuthMiddleware(testSecret)
	handler := m.AuthRequired()

	// Generate token
	userID := "user-123"
	roles := []string{"admin", "user"}
	token := newValidToken(userID, roles)

	w := httptest.NewRecorder()
	c, _ := gin.CreateTestContext(w)
	req := httptest.NewRequest("GET", "/", nil)
	req.Header.Set("Authorization", "Bearer "+token)
	c.Request = req
	handler(c)

	if w.Code != http.StatusOK {
		t.Errorf("Status code = %d, want %d", w.Code, http.StatusOK)
	}
	// Check that context contains user_id
	val, exists := c.Get("user_id")
	if !exists || val != userID {
		t.Errorf("user_id in context = %v, want %s", val, userID)
	}
	// Check roles
	rolesVal, exists := c.Get("user_roles")
	if !exists {
		t.Fatal("user_roles not set")
	}
	rolesSlice, ok := rolesVal.([]string)
	if !ok {
		t.Fatalf("user_roles type = %T, want []string", rolesVal)
	}
	if len(rolesSlice) != len(roles) {
		t.Errorf("roles count = %d, want %d", len(rolesSlice), len(roles))
	}
	for i, r := range roles {
		if rolesSlice[i] != r {
			t.Errorf("role[%d] = %s, want %s", i, rolesSlice[i], r)
		}
	}
}

func TestAuthMiddleware_MissingUserId(t *testing.T) {
	m := NewAuthMiddleware(testSecret)
	handler := m.AuthRequired()

	// Token with no userId
	claims := jwt.MapClaims{
		"roles": []string{"user"},
		"exp":   jwt.NewNumericDate(time.Now().Add(1 * time.Hour)),
	}
	token := jwt.NewWithClaims(jwt.SigningMethodHS256, claims)
	signed, _ := token.SignedString([]byte(testSecret))

	w := httptest.NewRecorder()
	c, _ := gin.CreateTestContext(w)
	req := httptest.NewRequest("GET", "/", nil)
	req.Header.Set("Authorization", "Bearer "+signed)
	c.Request = req
	handler(c)

	if w.Code != http.StatusUnauthorized {
		t.Errorf("Status code = %d, want %d", w.Code, http.StatusUnauthorized)
	}
}
