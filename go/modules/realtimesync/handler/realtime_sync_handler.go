package handler

import (
	"community.local/modules/realtimesync/service"
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

func isAdmin(c *gin.Context) bool {
	if roles, ok := c.Get("user_roles"); ok {
		for _, r := range roles.([]string) {
			if r == "ROLE_ADMIN" || r == "admin" {
				return true
			}
		}
	}
	return false
}

type RealtimeSyncHandler struct {
	realtimeSyncService *service.RealtimeSyncService
}

func NewRealtimeSyncHandler(realtimeSyncService *service.RealtimeSyncService) *RealtimeSyncHandler {
	return &RealtimeSyncHandler{
		realtimeSyncService: realtimeSyncService,
	}
}

func (rsh *RealtimeSyncHandler) RegisterRoutes(router *gin.Engine) {
	api := router.Group("/api/realtimesync")
	{
		api.POST("/session", rsh.CreateSession)
		api.GET("/session/:sessionId", rsh.GetSession)
		api.POST("/session/:sessionId/join", rsh.JoinSession)
		api.POST("/session/:sessionId/end", rsh.EndSession)
		api.POST("/session/:sessionId/item", rsh.SyncItem)
		api.GET("/session/:sessionId/items", rsh.GetAllItems)
		api.GET("/session/:sessionId/devices", rsh.GetOnlineDevices)
		api.DELETE("/session/:sessionId/item/:itemId", rsh.DeleteItem)
		api.POST("/session/:sessionId/push", rsh.PushChange)
		api.GET("/session/:sessionId/changes", rsh.GetPendingChanges)
	}
}

type CreateSessionRequest struct {
	UserID     string `json:"user_id" binding:"required"`
	DeviceID   string `json:"device_id" binding:"required"`
	DeviceType string `json:"device_type" binding:"required"`
}

func (rsh *RealtimeSyncHandler) CreateSession(c *gin.Context) {
	// Require authentication
	authUserID, ok := requireAuth(c)
	if !ok {
		return
	}

	var req CreateSessionRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(400, gin.H{"error": err.Error()})
		return
	}

	// User can only create sessions for themselves unless admin
	if req.UserID != authUserID && !isAdmin(c) {
		c.JSON(403, gin.H{"error": "access denied: can only create sessions for yourself"})
		return
	}

	session, err := rsh.realtimeSyncService.CreateSession(c.Request.Context(), req.UserID, req.DeviceID, req.DeviceType)
	if err != nil {
		c.JSON(500, gin.H{"error": err.Error()})
		return
	}

	c.JSON(201, gin.H{
		"status":  "session created",
		"session": session,
	})
}

func (rsh *RealtimeSyncHandler) GetSession(c *gin.Context) {
	sessionID := c.Param("sessionId")

	session, err := rsh.realtimeSyncService.GetSession(c.Request.Context(), sessionID)
	if err != nil || session == nil {
		c.JSON(404, gin.H{"error": "session not found"})
		return
	}

	c.JSON(200, session)
}

type JoinSessionRequest struct {
	UserID   string `json:"user_id" binding:"required"`
	DeviceID string `json:"device_id" binding:"required"`
}

func (rsh *RealtimeSyncHandler) JoinSession(c *gin.Context) {
	sessionID := c.Param("sessionId")

	var req JoinSessionRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(400, gin.H{"error": err.Error()})
		return
	}

	session, err := rsh.realtimeSyncService.JoinSession(c.Request.Context(), sessionID, req.UserID, req.DeviceID)
	if err != nil || session == nil {
		c.JSON(404, gin.H{"error": "session not found"})
		return
	}

	c.JSON(200, gin.H{
		"status":  "joined session",
		"session": session,
	})
}

func (rsh *RealtimeSyncHandler) EndSession(c *gin.Context) {
	// Require authentication
	authUserID, ok := requireAuth(c)
	if !ok {
		return
	}

	sessionID := c.Param("sessionId")

	// Get session to check ownership
	session, err := rsh.realtimeSyncService.GetSession(c.Request.Context(), sessionID)
	if err != nil || session == nil {
		c.JSON(404, gin.H{"error": "session not found"})
		return
	}

	// Only owner or admin can end session
	if session.OwnerID != authUserID && !isAdmin(c) {
		c.JSON(403, gin.H{"error": "access denied: only session owner can end session"})
		return
	}

	err = rsh.realtimeSyncService.EndSession(c.Request.Context(), sessionID)
	if err != nil {
		c.JSON(500, gin.H{"error": err.Error()})
		return
	}

	c.JSON(200, gin.H{"status": "session ended"})
}

type SyncItemRequest struct {
	ItemID   string                 `json:"item_id" binding:"required"`
	ItemType string                 `json:"item_type" binding:"required"`
	Data     map[string]interface{} `json:"data"`
	Version  int                    `json:"version"`
}

func (rsh *RealtimeSyncHandler) SyncItem(c *gin.Context) {
	sessionID := c.Param("sessionId")

	var req SyncItemRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(400, gin.H{"error": err.Error()})
		return
	}

	item, err := rsh.realtimeSyncService.SyncItem(c.Request.Context(), sessionID, req.ItemID, req.ItemType, req.Data, req.Version)
	if err != nil || item == nil {
		c.JSON(404, gin.H{"error": "session not found"})
		return
	}

	c.JSON(201, gin.H{
		"status": "item synced",
		"item":   item,
	})
}

func (rsh *RealtimeSyncHandler) GetAllItems(c *gin.Context) {
	sessionID := c.Param("sessionId")

	items, err := rsh.realtimeSyncService.GetAllItems(c.Request.Context(), sessionID)
	if err != nil {
		c.JSON(404, gin.H{"error": "session not found"})
		return
	}

	c.JSON(200, gin.H{
		"session_id": sessionID,
		"count":      len(items),
		"items":      items,
	})
}

func (rsh *RealtimeSyncHandler) GetOnlineDevices(c *gin.Context) {
	sessionID := c.Param("sessionId")

	devices, err := rsh.realtimeSyncService.GetOnlineDevices(c.Request.Context(), sessionID)
	if err != nil {
		c.JSON(404, gin.H{"error": "session not found"})
		return
	}

	c.JSON(200, gin.H{
		"session_id": sessionID,
		"count":      len(devices),
		"devices":    devices,
	})
}

func (rsh *RealtimeSyncHandler) DeleteItem(c *gin.Context) {
	sessionID := c.Param("sessionId")
	itemID := c.Param("itemId")

	err := rsh.realtimeSyncService.DeleteItem(c.Request.Context(), sessionID, itemID)
	if err != nil {
		c.JSON(500, gin.H{"error": err.Error()})
		return
	}

	c.JSON(200, gin.H{"status": "item deleted"})
}

type PushChangeRequest struct {
	ItemID string                 `json:"item_id" binding:"required"`
	Data   map[string]interface{} `json:"data"`
}

func (rsh *RealtimeSyncHandler) PushChange(c *gin.Context) {
	sessionID := c.Param("sessionId")

	var req PushChangeRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(400, gin.H{"error": err.Error()})
		return
	}

	change, err := rsh.realtimeSyncService.PushChange(c.Request.Context(), sessionID, req.ItemID, req.Data)
	if err != nil {
		c.JSON(500, gin.H{"error": err.Error()})
		return
	}

	c.JSON(201, gin.H{
		"status": "change pushed",
		"change": change,
	})
}

func (rsh *RealtimeSyncHandler) GetPendingChanges(c *gin.Context) {
	sessionID := c.Param("sessionId")
	deviceID := c.Query("device_id")

	changes, err := rsh.realtimeSyncService.GetPendingChanges(c.Request.Context(), sessionID, deviceID)
	if err != nil {
		c.JSON(500, gin.H{"error": err.Error()})
		return
	}

	c.JSON(200, gin.H{
		"session_id": sessionID,
		"count":      len(changes),
		"changes":    changes,
	})
}
