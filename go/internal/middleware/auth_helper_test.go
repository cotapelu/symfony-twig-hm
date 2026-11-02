package middleware

import (
	"net/http"
	"net/http/httptest"
	"testing"

	"github.com/gin-gonic/gin"
	"github.com/stretchr/testify/assert"
)

func TestRequireAuth_WithoutAuth(t *testing.T) {
	gin.SetMode(gin.TestMode)
	w := httptest.NewRecorder()
	c, _ := gin.CreateTestContext(w)
	// Do not set user_id
	handler := RequireAuth()
	handler(c)
	assert.Equal(t, http.StatusUnauthorized, w.Code)
	assert.Contains(t, w.Body.String(), "authentication required")
}

func TestRequireAuth_WithAuth(t *testing.T) {
	gin.SetMode(gin.TestMode)
	w := httptest.NewRecorder()
	c, _ := gin.CreateTestContext(w)
	c.Set("user_id", "user-123")
	handler := RequireAuth()
	handler(c)
	assert.Equal(t, http.StatusOK, w.Code)
}

func TestRequireAdmin_WithoutAdmin(t *testing.T) {
	gin.SetMode(gin.TestMode)
	w := httptest.NewRecorder()
	c, _ := gin.CreateTestContext(w)
	c.Set("user_roles", []string{"user", "editor"})
	handler := RequireAdmin()
	handler(c)
	assert.Equal(t, http.StatusForbidden, w.Code)
	assert.Contains(t, w.Body.String(), "admin access required")
}

func TestRequireAdmin_WithAdmin(t *testing.T) {
	gin.SetMode(gin.TestMode)
	w := httptest.NewRecorder()
	c, _ := gin.CreateTestContext(w)
	c.Set("user_roles", []string{"admin", "user"})
	handler := RequireAdmin()
	handler(c)
	assert.Equal(t, http.StatusOK, w.Code)
}

func TestRequireAdmin_WithROLEADMIN(t *testing.T) {
	gin.SetMode(gin.TestMode)
	w := httptest.NewRecorder()
	c, _ := gin.CreateTestContext(w)
	c.Set("user_roles", []string{"ROLE_ADMIN"})
	handler := RequireAdmin()
	handler(c)
	assert.Equal(t, http.StatusOK, w.Code)
}

func TestGetCurrentUserID_WhenPresent(t *testing.T) {
	gin.SetMode(gin.TestMode)
	w := httptest.NewRecorder()
	c, _ := gin.CreateTestContext(w)
	c.Set("user_id", "user-123")
	userID, ok := GetCurrentUserID(c)
	assert.True(t, ok)
	assert.Equal(t, "user-123", userID)
}

func TestGetCurrentUserID_WhenMissing(t *testing.T) {
	gin.SetMode(gin.TestMode)
	w := httptest.NewRecorder()
	c, _ := gin.CreateTestContext(w)
	userID, ok := GetCurrentUserID(c)
	assert.False(t, ok)
	assert.Empty(t, userID)
}

func TestIsAdmin_WithAdminRole(t *testing.T) {
	gin.SetMode(gin.TestMode)
	w := httptest.NewRecorder()
	c, _ := gin.CreateTestContext(w)
	c.Set("user_roles", []string{"admin", "user"})
	assert.True(t, IsAdmin(c))
}

func TestIsAdmin_WithROLEADMIN(t *testing.T) {
	gin.SetMode(gin.TestMode)
	w := httptest.NewRecorder()
	c, _ := gin.CreateTestContext(w)
	c.Set("user_roles", []string{"ROLE_ADMIN"})
	assert.True(t, IsAdmin(c))
}

func TestIsAdmin_WithoutAdmin(t *testing.T) {
	gin.SetMode(gin.TestMode)
	w := httptest.NewRecorder()
	c, _ := gin.CreateTestContext(w)
	c.Set("user_roles", []string{"user", "editor"})
	assert.False(t, IsAdmin(c))
}

func TestIsAdmin_NoRoles(t *testing.T) {
	gin.SetMode(gin.TestMode)
	w := httptest.NewRecorder()
	c, _ := gin.CreateTestContext(w)
	assert.False(t, IsAdmin(c))
}
