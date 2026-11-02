package handler

import (
	"strconv"
	"strings"

	"community.local/modules/notificationsrealtime/service"
	"github.com/gin-gonic/gin"
	"community.local/internal/middleware"
)

type NotificationHandler struct {
	notificationService *service.NotificationService
}

func NewNotificationHandler(notificationService *service.NotificationService) *NotificationHandler {
	return &NotificationHandler{
		notificationService: notificationService,
	}
}

func (nh *NotificationHandler) RegisterRoutes(router *gin.Engine) {
	api := router.Group("/api/notifications")
	api.Use(middleware.TimeoutMiddleware(middleware.DefaultTimeoutConfig()))
	api.Use(nh.AuthMiddleware())
	{
		api.POST("/send", nh.SendNotification)
		api.POST("/broadcast", nh.SendBroadcast)
		api.GET("/user/:userId", nh.GetNotifications)
		api.POST("/user/:userId/read-all", nh.MarkAllRead)
		api.GET("/user/:userId/unread-count", nh.GetUnreadCount)
		api.POST("/:notificationId/read", nh.MarkRead)
		api.DELETE("/:notificationId", nh.DeleteNotification)
		api.POST("/subscribe", nh.Subscribe)
		api.POST("/unsubscribe", nh.Unsubscribe)
		api.POST("/push-token", nh.RegisterPushToken)
	}
}

func (nh *NotificationHandler) AuthMiddleware() gin.HandlerFunc {
	return func(c *gin.Context) {
		authHeader := c.GetHeader("Authorization")
		if authHeader == "" {
			c.JSON(401, gin.H{"error": "Authorization header required"})
			c.Abort()
			return
		}

		token := strings.TrimPrefix(authHeader, "Bearer ")
		if token == authHeader {
			c.JSON(401, gin.H{"error": "Invalid authorization format"})
			c.Abort()
			return
		}

		userID, err := nh.notificationService.ValidateToken(token)
		if err != nil {
			c.JSON(401, gin.H{"error": "Invalid or expired token"})
			c.Abort()
			return
		}

		c.Set("userID", userID)
		c.Next()
	}
}

func (nh *NotificationHandler) GetAuthenticatedUserID(c *gin.Context) string {
	userID, exists := c.Get("userID")
	if !exists {
		return ""
	}
	return userID.(string)
}

func (nh *NotificationHandler) ValidateOwnership(userIDParam string, c *gin.Context) bool {
	authenticatedUserID := nh.GetAuthenticatedUserID(c)
	if authenticatedUserID == "" {
		return false
	}
	return authenticatedUserID == userIDParam
}

type SendNotificationRequest struct {
	UserID string                 `json:"user_id" binding:"required"`
	Title  string                 `json:"title" binding:"required"`
	Body   string                 `json:"body" binding:"required"`
	Type   string                 `json:"type" binding:"required"`
	Data   map[string]interface{} `json:"data"`
}

func (nh *NotificationHandler) SendNotification(c *gin.Context) {
	var req SendNotificationRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(400, gin.H{"error": err.Error()})
		return
	}

	if len(req.Title) < 1 || len(req.Title) > 200 {
		c.JSON(400, gin.H{"error": "Title must be between 1 and 200 characters"})
		return
	}

	if len(req.Body) < 1 || len(req.Body) > 2000 {
		c.JSON(400, gin.H{"error": "Body must be between 1 and 2000 characters"})
		return
	}

	notification := nh.notificationService.SendNotification(req.UserID, req.Title, req.Body, req.Type, req.Data)

	c.JSON(201, gin.H{
		"status":       "notification sent",
		"notification": notification,
	})
}

type SendBroadcastRequest struct {
	Title string                 `json:"title" binding:"required"`
	Body  string                 `json:"body" binding:"required"`
	Type  string                 `json:"type" binding:"required"`
	Data  map[string]interface{} `json:"data"`
}

func (nh *NotificationHandler) SendBroadcast(c *gin.Context) {
	var req SendBroadcastRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(400, gin.H{"error": err.Error()})
		return
	}

	notification := nh.notificationService.SendBroadcast(req.Title, req.Body, req.Type, req.Data)

	c.JSON(201, gin.H{
		"status":       "broadcast sent",
		"notification": notification,
	})
}

func (nh *NotificationHandler) GetNotifications(c *gin.Context) {
	userID := c.Param("userId")

	if !nh.ValidateOwnership(userID, c) {
		c.JSON(403, gin.H{"error": "Access denied to this user's notifications"})
		return
	}

	limit, _ := strconv.Atoi(c.DefaultQuery("limit", "50"))
	if limit < 1 || limit > 100 {
		limit = 50
	}

	notifications := nh.notificationService.GetNotifications(userID, limit)

	c.JSON(200, gin.H{
		"user_id":       userID,
		"count":         len(notifications),
		"notifications": notifications,
	})
}

func (nh *NotificationHandler) MarkRead(c *gin.Context) {
	notificationID := c.Param("notificationId")

	notification, err := nh.notificationService.GetNotification(notificationID)
	if err != nil {
		c.JSON(404, gin.H{"error": "Notification not found"})
		return
	}

	if !nh.ValidateOwnership(notification.UserID, c) {
		c.JSON(403, gin.H{"error": "Access denied"})
		return
	}

	notification, err = nh.notificationService.MarkRead(notificationID)
	if err != nil {
		c.JSON(500, gin.H{"error": err.Error()})
		return
	}

	c.JSON(200, gin.H{
		"status":       "notification marked as read",
		"notification": notification,
	})
}

func (nh *NotificationHandler) MarkAllRead(c *gin.Context) {
	userID := c.Param("userId")

	if !nh.ValidateOwnership(userID, c) {
		c.JSON(403, gin.H{"error": "Access denied"})
		return
	}

	err := nh.notificationService.MarkAllRead(userID)
	if err != nil {
		c.JSON(500, gin.H{"error": err.Error()})
		return
	}

	c.JSON(200, gin.H{"status": "all notifications marked as read"})
}

func (nh *NotificationHandler) GetUnreadCount(c *gin.Context) {
	userID := c.Param("userId")

	if !nh.ValidateOwnership(userID, c) {
		c.JSON(403, gin.H{"error": "Access denied"})
		return
	}

	count := nh.notificationService.GetUnreadCount(userID)

	c.JSON(200, gin.H{
		"user_id":      userID,
		"unread_count": count,
	})
}

func (nh *NotificationHandler) DeleteNotification(c *gin.Context) {
	notificationID := c.Param("notificationId")

	notification, err := nh.notificationService.GetNotification(notificationID)
	if err != nil {
		c.JSON(404, gin.H{"error": "Notification not found"})
		return
	}

	if !nh.ValidateOwnership(notification.UserID, c) {
		c.JSON(403, gin.H{"error": "Access denied"})
		return
	}

	err = nh.notificationService.DeleteNotification(notificationID)
	if err != nil {
		c.JSON(500, gin.H{"error": err.Error()})
		return
	}

	c.JSON(200, gin.H{"status": "notification deleted"})
}

type SubscribeRequest struct {
	UserID  string `json:"user_id" binding:"required"`
	Channel string `json:"channel" binding:"required"`
}

func (nh *NotificationHandler) Subscribe(c *gin.Context) {
	var req SubscribeRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(400, gin.H{"error": err.Error()})
		return
	}

	subscription := nh.notificationService.Subscribe(req.UserID, req.Channel)

	c.JSON(201, gin.H{
		"status":       "subscribed",
		"subscription": subscription,
	})
}

type UnsubscribeRequest struct {
	UserID  string `json:"user_id" binding:"required"`
	Channel string `json:"channel" binding:"required"`
}

func (nh *NotificationHandler) Unsubscribe(c *gin.Context) {
	var req UnsubscribeRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(400, gin.H{"error": err.Error()})
		return
	}

	err := nh.notificationService.Unsubscribe(req.UserID, req.Channel)
	if err != nil {
		c.JSON(500, gin.H{"error": err.Error()})
		return
	}

	c.JSON(200, gin.H{"status": "unsubscribed"})
}

type RegisterPushTokenRequest struct {
	UserID   string `json:"user_id" binding:"required"`
	Token    string `json:"token" binding:"required"`
	Platform string `json:"platform" binding:"required"`
}

func (nh *NotificationHandler) RegisterPushToken(c *gin.Context) {
	var req RegisterPushTokenRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(400, gin.H{"error": err.Error()})
		return
	}

	validPlatforms := map[string]bool{"ios": true, "android": true, "web": true}
	if !validPlatforms[strings.ToLower(req.Platform)] {
		c.JSON(400, gin.H{"error": "Platform must be ios, android, or web"})
		return
	}

	if len(req.Token) < 10 {
		c.JSON(400, gin.H{"error": "Invalid token format"})
		return
	}

	pushToken := nh.notificationService.RegisterPushToken(req.UserID, req.Token, req.Platform)

	c.JSON(201, gin.H{
		"status":    "push token registered",
		"pushToken": pushToken,
	})
}

func contextWithTimeout(d interface{}) (interface{}, interface{}) {
	return nil, nil
}