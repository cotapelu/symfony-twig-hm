package handler

import (
	"context"
	"time"

	"community.local/modules/events/service"
	"community.local/internal/idempotency"
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

type EventHandler struct {
	eventService *service.EventsService
}

func NewEventHandler(eventService *service.EventsService) *EventHandler {
	return &EventHandler{
		eventService: eventService,
	}
}

func (eh *EventHandler) RegisterRoutes(router *gin.Engine) {
	api := router.Group("/api/events")
	{
		api.POST("/create", eh.CreateEvent)
		api.GET("/:eventId", eh.GetEvent)
		api.PUT("/:eventId", eh.UpdateEvent)
		api.DELETE("/:eventId", eh.DeleteEvent)
		api.GET("/user/:userId", eh.ListEvents)
		api.POST("/:eventId/rsvp", eh.RSVPEvent)
		api.POST("/reminder", eh.CreateReminder)
	}
}

type CreateEventRequest struct {
	UserID      string            `json:"user_id" binding:"required"`
	Title       string            `json:"title" binding:"required"`
	Description string            `json:"description"`
	StartTime   time.Time         `json:"start_time" binding:"required"`
	EndTime     time.Time         `json:"end_time" binding:"required"`
	Attendees   []string          `json:"attendees"`
	Metadata    map[string]string `json:"metadata"`
}

func (eh *EventHandler) CreateEvent(c *gin.Context) {
	// Require authentication
	authUserID, ok := requireAuth(c)
	if !ok {
		return
	}

	var req CreateEventRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(400, gin.H{"error": err.Error()})
		return
	}

	// Idempotency check
	idemKey := c.GetHeader("Idempotency-Key")
	if idemKey == "" {
		c.JSON(400, gin.H{"error": "Idempotency-Key header required"})
		return
	}
	if cached, ok := idempotency.Get(idemKey); ok {
		c.JSON(200, cached)
		return
	}

	// User can only create events for themselves unless admin
	if req.UserID != authUserID && !isAdmin(c) {
		c.JSON(403, gin.H{"error": "access denied: can only create events for yourself"})
		return
	}

	event := eh.eventService.CreateEvent(authUserID, req.Title, req.Description, req.StartTime, req.EndTime, req.Attendees, req.Metadata)

	resp := gin.H{"status": "event created", "event": event}
	idempotency.Set(idemKey, resp)

	c.JSON(201, resp)
}

func (eh *EventHandler) GetEvent(c *gin.Context) {
	// Require authentication
	if _, ok := requireAuth(c); !ok {
		return
	}

	eventID := c.Param("eventId")

	event, err := eh.eventService.GetEvent(eventID)
	if err != nil {
		c.JSON(404, gin.H{"error": "Event not found"})
		return
	}

	// Check ownership: only event owner or admin can view
	authUserID, _ := getCurrentUserID(c)
	if event.UserID != authUserID && !isAdmin(c) {
		c.JSON(403, gin.H{"error": "access denied: you can only view your own events"})
		return
	}

	c.JSON(200, event)
}

type UpdateEventRequest struct {
	Title       string            `json:"title"`
	Description string            `json:"description"`
	StartTime   time.Time         `json:"start_time"`
	EndTime     time.Time         `json:"end_time"`
	Attendees   []string          `json:"attendees"`
	Metadata    map[string]string `json:"metadata"`
}

func (eh *EventHandler) UpdateEvent(c *gin.Context) {
	// Require authentication
	if _, ok := requireAuth(c); !ok {
		return
	}

	eventID := c.Param("eventId")

	// Idempotency check
	idemKey := c.GetHeader("Idempotency-Key")
	if idemKey == "" {
		c.JSON(400, gin.H{"error": "Idempotency-Key header required"})
		return
	}
	if cached, ok := idempotency.Get(idemKey); ok {
		c.JSON(200, cached)
		return
	}

	// First get event to check ownership
	event, err := eh.eventService.GetEvent(eventID)
	if err != nil {
		c.JSON(404, gin.H{"error": "Event not found"})
		return
	}
	authUserID, _ := getCurrentUserID(c)
	if event.UserID != authUserID && !isAdmin(c) {
		c.JSON(403, gin.H{"error": "access denied: you can only update your own events"})
		return
	}

	var req UpdateEventRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(400, gin.H{"error": err.Error()})
		return
	}

	updates := make(map[string]interface{})
	if req.Title != "" {
		updates["title"] = req.Title
	}
	if req.Description != "" {
		updates["description"] = req.Description
	}
	if !req.StartTime.IsZero() {
		updates["start_time"] = req.StartTime
	}
	if !req.EndTime.IsZero() {
		updates["end_time"] = req.EndTime
	}
	if req.Attendees != nil {
		updates["attendees"] = req.Attendees
	}
	if req.Metadata != nil {
		updates["metadata"] = req.Metadata
	}

	if len(updates) == 0 {
		c.JSON(400, gin.H{"error": "no updates provided"})
		return
	}

	event, err = eh.eventService.UpdateEvent(eventID, updates)
	if err != nil {
		c.JSON(400, gin.H{"error": err.Error()})
		return
	}

	resp := gin.H{"status": "event updated", "event": event}
	idempotency.Set(idemKey, resp)

	c.JSON(200, resp)
}

func (eh *EventHandler) DeleteEvent(c *gin.Context) {
	// Require authentication
	if _, ok := requireAuth(c); !ok {
		return
	}

	eventID := c.Param("eventId")

	// Idempotency check
	idemKey := c.GetHeader("Idempotency-Key")
	if idemKey == "" {
		c.JSON(400, gin.H{"error": "Idempotency-Key header required"})
		return
	}
	if cached, ok := idempotency.Get(idemKey); ok {
		c.JSON(200, cached)
		return
	}

	// Get event to check ownership
	event, err := eh.eventService.GetEvent(eventID)
	if err != nil {
		c.JSON(404, gin.H{"error": "Event not found"})
		return
	}
	authUserID, _ := getCurrentUserID(c)
	if event.UserID != authUserID && !isAdmin(c) {
		c.JSON(403, gin.H{"error": "access denied: you can only delete your own events"})
		return
	}

	err = eh.eventService.DeleteEvent(eventID)
	if err != nil {
		c.JSON(404, gin.H{"error": "Event not found"})
		return
	}

	resp := gin.H{"status": "event deleted"}
	idempotency.Set(idemKey, resp)

	c.JSON(200, resp)
}

func (eh *EventHandler) ListEvents(c *gin.Context) {
	// Require authentication
	if _, ok := requireAuth(c); !ok {
		return
	}

	userID := c.Param("userId")
	// Check ownership: user can list their own events, admin can list any
	authUserID, _ := getCurrentUserID(c)
	if userID != authUserID && !isAdmin(c) {
		c.JSON(403, gin.H{"error": "access denied: you can only list your own events"})
		return
	}

	events := eh.eventService.ListEvents(userID, nil)

	c.JSON(200, gin.H{
		"user_id": userID,
		"count":   len(events),
		"events":  events,
	})
}

type RSVPEventRequest struct {
	UserID string `json:"user_id" binding:"required"`
	Status string `json:"status" binding:"required"`
}

func (eh *EventHandler) RSVPEvent(c *gin.Context) {
	// Require authentication
	if _, ok := requireAuth(c); !ok {
		return
	}

	eventID := c.Param("eventId")

	var req RSVPEventRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(400, gin.H{"error": err.Error()})
		return
	}

	// User can only RSVP for themselves unless admin
	authUserID, _ := getCurrentUserID(c)
	if req.UserID != authUserID && !isAdmin(c) {
		c.JSON(403, gin.H{"error": "access denied: can only RSVP for yourself"})
		return
	}

	event, err := eh.eventService.RSVPEvent(eventID, req.UserID, req.Status)
	if err != nil {
		c.JSON(404, gin.H{"error": err.Error()})
		return
	}

	c.JSON(200, gin.H{
		"status": "rsvp recorded",
		"event":  event,
	})
}

type CreateReminderRequest struct {
	UserID       string    `json:"user_id" binding:"required"`
	EventID      string    `json:"event_id" binding:"required"`
	ReminderTime time.Time `json:"reminder_time" binding:"required"`
	Message      string    `json:"message"`
}

func (eh *EventHandler) CreateReminder(c *gin.Context) {
	// Require authentication
	if _, ok := requireAuth(c); !ok {
		return
	}

	var req CreateReminderRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(400, gin.H{"error": err.Error()})
		return
	}

	// User can only create reminders for themselves unless admin
	authUserID, _ := getCurrentUserID(c)
	if req.UserID != authUserID && !isAdmin(c) {
		c.JSON(403, gin.H{"error": "access denied: can only create reminders for yourself"})
		return
	}

	reminder := eh.eventService.CreateReminder(req.UserID, req.EventID, req.ReminderTime, req.Message)

	c.JSON(201, gin.H{
		"status":   "reminder created",
		"reminder": reminder,
	})
}

func contextWithTimeout(c *gin.Context, d time.Duration) (context.Context, context.CancelFunc) {
	return context.WithTimeout(c.Request.Context(), d)
}
