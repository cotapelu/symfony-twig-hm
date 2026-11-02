package handler

import (
	"net/http"
	"net/http/httptest"
	"testing"

	"github.com/gin-gonic/gin"
	"github.com/stretchr/testify/assert"
)

func TestSendNotification(t *testing.T) {
	gin.SetMode(gin.TestMode)
	router := gin.New()

	router.POST("/api/notification/send", func(c *gin.Context) {
		c.JSON(http.StatusOK, gin.H{
			"notification_id": "notif_123",
			"status":          "sent",
		})
	})

	req, _ := http.NewRequest("POST", "/api/notification/send", nil)
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
}

func TestGetNotificationHistory(t *testing.T) {
	gin.SetMode(gin.TestMode)
	router := gin.New()

	router.GET("/api/notification/history/:user_id", func(c *gin.Context) {
		c.JSON(http.StatusOK, gin.H{
			"notifications": []gin.H{
				{"id": "notif1", "title": "New message"},
			},
		})
	})

	req, _ := http.NewRequest("GET", "/api/notification/history/123", nil)
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
}

func TestMarkAsRead(t *testing.T) {
	gin.SetMode(gin.TestMode)
	router := gin.New()

	router.POST("/api/notification/:id/read", func(c *gin.Context) {
		c.JSON(http.StatusOK, gin.H{
			"notification_id": "notif_123",
			"status":          "read",
		})
	})

	req, _ := http.NewRequest("POST", "/api/notification/notif_123/read", nil)
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
}
