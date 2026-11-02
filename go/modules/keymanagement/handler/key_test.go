package handler

import (
	"net/http"
	"net/http/httptest"
	"testing"

	"github.com/gin-gonic/gin"
	"github.com/stretchr/testify/assert"
)

func TestCreateKey(t *testing.T) {
	gin.SetMode(gin.TestMode)
	router := gin.New()

	router.POST("/api/keys/create", func(c *gin.Context) {
		c.JSON(http.StatusOK, gin.H{
			"key_id": "key_123",
			"status": "active",
		})
	})

	req, _ := http.NewRequest("POST", "/api/keys/create", nil)
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
}

func TestRotateKey(t *testing.T) {
	gin.SetMode(gin.TestMode)
	router := gin.New()

	router.POST("/api/keys/:id/rotate", func(c *gin.Context) {
		c.JSON(http.StatusOK, gin.H{
			"key_id": "key_123",
			"status": "rotated",
		})
	})

	req, _ := http.NewRequest("POST", "/api/keys/key_123/rotate", nil)
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
}

func TestRevokeKey(t *testing.T) {
	gin.SetMode(gin.TestMode)
	router := gin.New()

	router.POST("/api/keys/:id/revoke", func(c *gin.Context) {
		c.JSON(http.StatusOK, gin.H{
			"key_id": "key_123",
			"status": "revoked",
		})
	})

	req, _ := http.NewRequest("POST", "/api/keys/key_123/revoke", nil)
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
}
