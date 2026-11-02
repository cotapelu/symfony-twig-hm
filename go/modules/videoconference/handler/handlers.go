package handler

import (
	"net/http"

	"github.com/gin-gonic/gin"
)

func getCurrentUserID(c *gin.Context) (string, bool) {
	userID, exists := c.Get("user_id")
	if !exists {
		return "", false
	}
	return userID.(string), true
}

func requireAuth(c *gin.Context) (string, bool) {
	userID, ok := getCurrentUserID(c)
	if !ok {
		c.JSON(401, gin.H{"error": "authentication required"})
		return "", false
	}
	return userID, true
}

type VideoConferenceHandler struct{}

func NewVideoConferenceHandler() *VideoConferenceHandler {
	return &VideoConferenceHandler{}
}

func (h *VideoConferenceHandler) RegisterRoutes(router *gin.Engine) {
	vc := router.Group("/api/video")
	{
		vc.POST("/rooms", h.CreateRoom)
		vc.GET("/rooms/:id", h.GetRoom)
		vc.POST("/rooms/:id/join", h.JoinRoom)
		vc.POST("/rooms/:id/leave", h.LeaveRoom)
		vc.GET("/rooms/:id/participants", h.GetParticipants)
		vc.POST("/signal", h.Signal)
	}
}

func (h *VideoConferenceHandler) CreateRoom(c *gin.Context) {
	// Require authentication to create a room
	if _, ok := requireAuth(c); !ok {
		return
	}

	c.JSON(http.StatusCreated, gin.H{
		"roomId": "room_" + randomString(8),
		"webrtcConfig": gin.H{
			"iceServers": []string{"stun:stun.l.google.com:19302"},
		},
	})
}

func (h *VideoConferenceHandler) GetRoom(c *gin.Context) {
	// Require authentication to view room details
	if _, ok := requireAuth(c); !ok {
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"id": c.Param("id"),
		"participants": 3,
		"status": "active",
	})
}

func (h *VideoConferenceHandler) JoinRoom(c *gin.Context) {
	// Require authentication to join a room
	if _, ok := requireAuth(c); !ok {
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"success": true,
		"message": "Joined room",
	})
}

func (h *VideoConferenceHandler) LeaveRoom(c *gin.Context) {
	// Require authentication to leave a room
	if _, ok := requireAuth(c); !ok {
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"success": true,
		"message": "Left room",
	})
}

func (h *VideoConferenceHandler) GetParticipants(c *gin.Context) {
	// Require authentication to view participants
	if _, ok := requireAuth(c); !ok {
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"participants": []gin.H{
			{"id": "user1", "name": "User 1", "audio": true, "video": true},
			{"id": "user2", "name": "User 2", "audio": true, "video": false},
		},
	})
}

func (h *VideoConferenceHandler) Signal(c *gin.Context) {
	// Require authentication to send signal
	if _, ok := requireAuth(c); !ok {
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"status": "signal received",
	})
}

func randomString(n int) string {
	const letters = "abcdefghijklmnopqrstuvwxyz0123456789"
	result := make([]byte, n)
	for i := range result {
		result[i] = letters[i%len(letters)]
	}
	return string(result)
}
