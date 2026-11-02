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

type LiveStreamingHandler struct{}

func NewLiveStreamingHandler() *LiveStreamingHandler {
	return &LiveStreamingHandler{}
}

func randomString(n int) string {
	const letters = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"
	b := make([]byte, n)
	for i := range b {
		b[i] = letters[i%len(letters)]
	}
	return string(b)
}

func (h *LiveStreamingHandler) RegisterRoutes(router *gin.Engine) {
	ls := router.Group("/api/stream")
	{
		ls.POST("/start", h.StartStream)
		ls.POST("/stop/:id", h.StopStream)
		ls.GET("/:id", h.GetStream)
		ls.GET("/list", h.ListStreams)
		ls.POST("/:id/chat", h.SendChat)
		ls.GET("/:id/chat", h.GetChat)
		ls.POST("/:id/viewer", h.AddViewer)
	}
}

func (h *LiveStreamingHandler) StartStream(c *gin.Context) {
	// Require authentication
	if _, ok := requireAuth(c); !ok {
		return
	}

	c.JSON(http.StatusCreated, gin.H{
		"streamId": "stream_" + randomString(8),
		"rtmpUrl":  "rtmp://localhost:1935/live",
		"hlsUrl":   "http://localhost:8080/hls/stream.m3u8",
		"status":   "live",
	})
}

func (h *LiveStreamingHandler) StopStream(c *gin.Context) {
	// Require authentication to stop a stream
	if _, ok := requireAuth(c); !ok {
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"streamId": c.Param("id"),
		"status":   "ended",
	})
}

func (h *LiveStreamingHandler) GetStream(c *gin.Context) {
	c.JSON(http.StatusOK, gin.H{
		"id":          c.Param("id"),
		"title":       "Live Stream",
		"viewerCount": 156,
		"status":      "live",
		"duration":    3600,
	})
}

func (h *LiveStreamingHandler) ListStreams(c *gin.Context) {
	c.JSON(http.StatusOK, gin.H{
		"streams": []gin.H{
			{"id": "stream1", "title": "Stream 1", "viewers": 100},
			{"id": "stream2", "title": "Stream 2", "viewers": 50},
		},
	})
}

func (h *LiveStreamingHandler) SendChat(c *gin.Context) {
	// Require authentication to send chat
	if _, ok := requireAuth(c); !ok {
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"success": true,
		"message": "Chat sent",
	})
}

func (h *LiveStreamingHandler) GetChat(c *gin.Context) {
	c.JSON(http.StatusOK, gin.H{
		"messages": []gin.H{
			{"user": "User1", "message": "Hello!", "timestamp": "2024-01-01T10:00:00Z"},
		},
	})
}

func (h *LiveStreamingHandler) AddViewer(c *gin.Context) {
	// Require authentication to add viewer
	if _, ok := requireAuth(c); !ok {
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"success":  true,
		"viewerId": "viewer_" + randomString(6),
	})
}
