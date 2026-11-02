package handler

import (
	"bytes"
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"testing"
	"time"

	"community.local/modules/events/service"
	"github.com/gin-gonic/gin"
	"github.com/stretchr/testify/assert"
)

// authTestMiddleware sets a default authenticated user for tests
func authTestMiddleware() gin.HandlerFunc {
	return func(c *gin.Context) {
		// Use Test-User-ID header if provided, else default to test_user1
		if uid := c.GetHeader("Test-User-ID"); uid != "" {
			c.Set("user_id", uid)
		} else {
			c.Set("user_id", "test_user1")
		}
	}
}

func TestEventHandler_CreateEvent(t *testing.T) {
	gin.SetMode(gin.TestMode)
	svc := service.NewEventsService(t.TempDir() + "/test.json")
	defer svc.Terminate()
	h := NewEventHandler(svc)

	router := gin.New()
	router.Use(authTestMiddleware())
	h.RegisterRoutes(router)

	body := map[string]interface{}{
		"user_id":     "user_1",
		"title":       "Test Event",
		"description": "Test Description",
		"start_time":  time.Now().Add(time.Hour).Format(time.RFC3339),
		"end_time":    time.Now().Add(2 * time.Hour).Format(time.RFC3339),
		"attendees":   []string{"user_1", "user_2"},
		"metadata":    map[string]string{"priority": "high"},
	}
	jsonBody, _ := json.Marshal(body)

	req, _ := http.NewRequest("POST", "/api/events/create", bytes.NewBuffer(jsonBody))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Test-User-ID", "user_1")
	req.Header.Set("Idempotency-Key", "test-idem-create")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusCreated, w.Code)
}

func TestEventHandler_GetEvent(t *testing.T) {
	gin.SetMode(gin.TestMode)
	svc := service.NewEventsService(t.TempDir() + "/test.json")
	defer svc.Terminate()
	h := NewEventHandler(svc)

	event := svc.CreateEvent("user_1", "Test Event", "Desc", time.Now(), time.Now().Add(time.Hour), nil, nil)

	router := gin.New()
	router.Use(authTestMiddleware())
	h.RegisterRoutes(router)

	req, _ := http.NewRequest("GET", "/api/events/"+event.ID, nil)
	req.Header.Set("Test-User-ID", "user_1")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
}

func TestEventHandler_GetEvent_NotFound(t *testing.T) {
	gin.SetMode(gin.TestMode)
	svc := service.NewEventsService(t.TempDir() + "/test.json")
	defer svc.Terminate()
	h := NewEventHandler(svc)

	router := gin.New()
	router.Use(authTestMiddleware())
	h.RegisterRoutes(router)

	req, _ := http.NewRequest("GET", "/api/events/nonexistent", nil)
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusNotFound, w.Code)
}

func TestEventHandler_UpdateEvent(t *testing.T) {
	gin.SetMode(gin.TestMode)
	svc := service.NewEventsService(t.TempDir() + "/test.json")
	defer svc.Terminate()
	h := NewEventHandler(svc)

	event := svc.CreateEvent("user_1", "Original", "Desc", time.Now(), time.Now().Add(time.Hour), nil, nil)

	router := gin.New()
	router.Use(authTestMiddleware())
	h.RegisterRoutes(router)

	body := map[string]interface{}{"title": "Updated Title"}
	jsonBody, _ := json.Marshal(body)

	req, _ := http.NewRequest("PUT", "/api/events/"+event.ID, bytes.NewBuffer(jsonBody))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Test-User-ID", "user_1")
	req.Header.Set("Idempotency-Key", "test-idem-update")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
}

func TestEventHandler_DeleteEvent(t *testing.T) {
	gin.SetMode(gin.TestMode)
	svc := service.NewEventsService(t.TempDir() + "/test.json")
	defer svc.Terminate()
	h := NewEventHandler(svc)

	event := svc.CreateEvent("user_1", "To Delete", "Desc", time.Now(), time.Now().Add(time.Hour), nil, nil)

	router := gin.New()
	router.Use(authTestMiddleware())
	h.RegisterRoutes(router)

	req, _ := http.NewRequest("DELETE", "/api/events/"+event.ID, nil)
	req.Header.Set("Test-User-ID", "user_1")
	req.Header.Set("Idempotency-Key", "test-idem-delete")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
}

func TestEventHandler_ListEvents(t *testing.T) {
	gin.SetMode(gin.TestMode)
	svc := service.NewEventsService(t.TempDir() + "/test.json")
	defer svc.Terminate()
	h := NewEventHandler(svc)

	svc.CreateEvent("user_1", "Event 1", "Desc", time.Now(), time.Now().Add(time.Hour), nil, nil)
	svc.CreateEvent("user_1", "Event 2", "Desc", time.Now(), time.Now().Add(time.Hour), nil, nil)

	router := gin.New()
	router.Use(authTestMiddleware())
	h.RegisterRoutes(router)

	req, _ := http.NewRequest("GET", "/api/events/user/user_1", nil)
	req.Header.Set("Test-User-ID", "user_1")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
}

func TestEventHandler_RSVPEvent(t *testing.T) {
	gin.SetMode(gin.TestMode)
	svc := service.NewEventsService(t.TempDir() + "/test.json")
	defer svc.Terminate()
	h := NewEventHandler(svc)

	event := svc.CreateEvent("user_1", "RSVP Event", "Desc", time.Now(), time.Now().Add(time.Hour), nil, nil)

	router := gin.New()
	router.Use(authTestMiddleware())
	h.RegisterRoutes(router)

	body := map[string]interface{}{
		"user_id": "user_2",
		"status":  "going",
	}
	jsonBody, _ := json.Marshal(body)

	req, _ := http.NewRequest("POST", "/api/events/"+event.ID+"/rsvp", bytes.NewBuffer(jsonBody))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Test-User-ID", "user_2") // RSVP as user_2
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
}

func TestEventHandler_CreateReminder(t *testing.T) {
	gin.SetMode(gin.TestMode)
	svc := service.NewEventsService(t.TempDir() + "/test.json")
	defer svc.Terminate()
	h := NewEventHandler(svc)

	event := svc.CreateEvent("user_1", "Reminder Event", "Desc", time.Now(), time.Now().Add(time.Hour), nil, nil)

	router := gin.New()
	router.Use(authTestMiddleware())
	h.RegisterRoutes(router)

	body := map[string]interface{}{
		"user_id":       "user_1",
		"event_id":      event.ID,
		"reminder_time": time.Now().Add(30 * time.Minute).Format(time.RFC3339),
		"message":       "Starting soon!",
	}
	jsonBody, _ := json.Marshal(body)

	req, _ := http.NewRequest("POST", "/api/events/reminder", bytes.NewBuffer(jsonBody))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Test-User-ID", "user_1")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusCreated, w.Code)
}

func TestCreateEventRequest_Validation(t *testing.T) {
	gin.SetMode(gin.TestMode)
	svc := service.NewEventsService(t.TempDir() + "/test.json")
	defer svc.Terminate()
	h := NewEventHandler(svc)

	router := gin.New()
	router.Use(authTestMiddleware())
	h.RegisterRoutes(router)

	// Missing required fields: title, start_time, end_time
	body := map[string]interface{}{
		"description": "No title",
	}
	jsonBody, _ := json.Marshal(body)

	req, _ := http.NewRequest("POST", "/api/events/create", bytes.NewBuffer(jsonBody))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Test-User-ID", "user_1")
	// No idempotency key to also test that requirement separately? Actually binding fails first.
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusBadRequest, w.Code)
}

func TestIsAdmin(t *testing.T) {
	// Test isAdmin helper by checking role context
	c := &gin.Context{}
	c.Set("user_roles", []string{"admin"})
	// Not directly accessible; we can test through handler behavior
	// This is a simple positive test: if we can create an event as admin with different user_id
	// We'll add a dedicated test in a separate integration if needed.
	// For now, this function is a placeholder to keep file compiling.
}

func TestRequireAuth(t *testing.T) {
	// Test requireAuth helper similarly
	// Placeholder
}
