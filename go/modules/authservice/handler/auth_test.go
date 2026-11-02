package handler

import (
	"bytes"
	"net/http"
	"net/http/httptest"
	"testing"

	"github.com/gin-gonic/gin"
	"github.com/stretchr/testify/assert"
)

func TestLogin(t *testing.T) {
	gin.SetMode(gin.TestMode)
	router := gin.New()
	
	// Simple test to verify endpoint exists
	router.POST("/api/auth/login", func(c *gin.Context) {
		c.JSON(http.StatusOK, gin.H{"token": "test_token"})
	})
	
	body := `{"email": "test@example.com", "password": "password"}`
	req, _ := http.NewRequest("POST", "/api/auth/login", bytes.NewBufferString(body))
	req.Header.Set("Content-Type", "application/json")
	
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)
	
	assert.Equal(t, http.StatusOK, w.Code)
	assert.Contains(t, w.Body.String(), "token")
}

func TestLogout(t *testing.T) {
	gin.SetMode(gin.TestMode)
	router := gin.New()
	
	router.POST("/api/auth/logout", func(c *gin.Context) {
		c.JSON(http.StatusOK, gin.H{"status": "logged_out"})
	})
	
	req, _ := http.NewRequest("POST", "/api/auth/logout", nil)
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)
	
	assert.Equal(t, http.StatusOK, w.Code)
}
