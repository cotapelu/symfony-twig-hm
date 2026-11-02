package handler

import (
	"bytes"
	"context"
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"testing"

	"community.local/modules/realtimesync/entity"
	"community.local/modules/realtimesync/repository"
	"community.local/modules/realtimesync/service"
	"github.com/gin-gonic/gin"
	"github.com/stretchr/testify/assert"
)

func setupRouterWithAuth(userID string, roles ...string) *gin.Engine {
	gin.SetMode(gin.TestMode)
	router := gin.New()
	router.Use(func(c *gin.Context) {
		if userID != "" {
			c.Set("user_id", userID)
		}
		if len(roles) > 0 {
			c.Set("user_roles", roles)
		}
	})
	return router
}

func TestRealtimeSyncHandler_CreateSession(t *testing.T) {
	gin.SetMode(gin.TestMode)

	sessionRepo := repository.NewInMemorySyncSessionRepository()
	itemRepo := repository.NewInMemorySyncItemRepository()
	pendingRepo := repository.NewInMemoryPendingChangeRepository()
	svc := service.NewRealtimeSyncService(sessionRepo, itemRepo, pendingRepo)
	h := NewRealtimeSyncHandler(svc)

	// Auth user for creating session
	router := setupRouterWithAuth("user123")
	h.RegisterRoutes(router)

	tests := []struct {
		name       string
		request    CreateSessionRequest
		wantStatus int
	}{
		{
			name: "valid session creation",
			request: CreateSessionRequest{
				UserID:     "user123",
				DeviceID:   "device456",
				DeviceType: "mobile",
			},
			wantStatus: http.StatusCreated,
		},
		{
			name: "missing required fields",
			request: CreateSessionRequest{
				UserID: "",
			},
			wantStatus: http.StatusBadRequest,
		},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			body, _ := json.Marshal(tt.request)
			req, _ := http.NewRequest(http.MethodPost, "/api/realtimesync/session", bytes.NewReader(body))
			req.Header.Set("Content-Type", "application/json")
			w := httptest.NewRecorder()
			router.ServeHTTP(w, req)

			assert.Equal(t, tt.wantStatus, w.Code)
		})
	}
}

func TestRealtimeSyncHandler_GetSession(t *testing.T) {
	gin.SetMode(gin.TestMode)

	sessionRepo := repository.NewInMemorySyncSessionRepository()
	itemRepo := repository.NewInMemorySyncItemRepository()
	pendingRepo := repository.NewInMemoryPendingChangeRepository()
	svc := service.NewRealtimeSyncService(sessionRepo, itemRepo, pendingRepo)
	h := NewRealtimeSyncHandler(svc)

	router := gin.New()
	h.RegisterRoutes(router)

	// Create a session directly via service
	ctx := context.Background()
	created, err := svc.CreateSession(ctx, "user123", "device456", "mobile")
	assert.NoError(t, err)
	assert.NotEmpty(t, created.ID)

	// Get existing session
	t.Run("get existing session", func(t *testing.T) {
		req, _ := http.NewRequest(http.MethodGet, "/api/realtimesync/session/"+created.ID, nil)
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)
		// Check response body contains session
		var resp entity.SyncSession
		json.Unmarshal(w.Body.Bytes(), &resp)
		assert.Equal(t, created.ID, resp.ID)
	})

	// Get non-existent session
	t.Run("get non-existent session", func(t *testing.T) {
		req, _ := http.NewRequest(http.MethodGet, "/api/realtimesync/session/nonexistent", nil)
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusNotFound, w.Code)
	})
}

func TestRealtimeSyncHandler_JoinSession(t *testing.T) {
	gin.SetMode(gin.TestMode)

	sessionRepo := repository.NewInMemorySyncSessionRepository()
	itemRepo := repository.NewInMemorySyncItemRepository()
	pendingRepo := repository.NewInMemoryPendingChangeRepository()
	svc := service.NewRealtimeSyncService(sessionRepo, itemRepo, pendingRepo)
	h := NewRealtimeSyncHandler(svc)

	router := gin.New()
	h.RegisterRoutes(router)

	ctx := context.Background()
	session, _ := svc.CreateSession(ctx, "user1", "dev1", "android")

	joinReq := JoinSessionRequest{
		UserID:   "user1",
		DeviceID: "dev2",
	}
	body, _ := json.Marshal(joinReq)
	req, _ := http.NewRequest(http.MethodPost, "/api/realtimesync/session/"+session.ID+"/join", bytes.NewReader(body))
	req.Header.Set("Content-Type", "application/json")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
	var resp struct {
		Session entity.SyncSession `json:"session"`
	}
	json.Unmarshal(w.Body.Bytes(), &resp)
	assert.Len(t, resp.Session.Devices, 2) // now two devices
}

func TestRealtimeSyncHandler_EndSession(t *testing.T) {
	gin.SetMode(gin.TestMode)

	sessionRepo := repository.NewInMemorySyncSessionRepository()
	itemRepo := repository.NewInMemorySyncItemRepository()
	pendingRepo := repository.NewInMemoryPendingChangeRepository()
	svc := service.NewRealtimeSyncService(sessionRepo, itemRepo, pendingRepo)
	h := NewRealtimeSyncHandler(svc)

	ctx := context.Background()
	session, _ := svc.CreateSession(ctx, "owner", "device1", "mobile")

	// Non-owner user
	routerNonOwner := setupRouterWithAuth("other_user")
	h.RegisterRoutes(routerNonOwner)
	req, _ := http.NewRequest(http.MethodPost, "/api/realtimesync/session/"+session.ID+"/end", nil)
	w := httptest.NewRecorder()
	routerNonOwner.ServeHTTP(w, req)
	assert.Equal(t, http.StatusForbidden, w.Code)

	// Owner
	routerOwner := setupRouterWithAuth("owner")
	h.RegisterRoutes(routerOwner)
	req, _ = http.NewRequest(http.MethodPost, "/api/realtimesync/session/"+session.ID+"/end", nil)
	w = httptest.NewRecorder()
	routerOwner.ServeHTTP(w, req)
	assert.Equal(t, http.StatusOK, w.Code)

	// Check ended
	endedSession, _ := svc.GetSession(ctx, session.ID)
	assert.NotNil(t, endedSession.EndedAt)
}

func TestRealtimeSyncHandler_SyncItem(t *testing.T) {
	gin.SetMode(gin.TestMode)

	sessionRepo := repository.NewInMemorySyncSessionRepository()
	itemRepo := repository.NewInMemorySyncItemRepository()
	pendingRepo := repository.NewInMemoryPendingChangeRepository()
	svc := service.NewRealtimeSyncService(sessionRepo, itemRepo, pendingRepo)
	h := NewRealtimeSyncHandler(svc)

	router := gin.New()
	h.RegisterRoutes(router)

	ctx := context.Background()
	session, _ := svc.CreateSession(ctx, "user1", "dev1", "mobile")

	syncReq := SyncItemRequest{
		ItemID:   "item1",
		ItemType: "note",
		Data:     map[string]interface{}{"content": "hello"},
		Version:  1,
	}
	body, _ := json.Marshal(syncReq)
	url := "/api/realtimesync/session/" + session.ID + "/item"
	req, _ := http.NewRequest(http.MethodPost, url, bytes.NewReader(body))
	req.Header.Set("Content-Type", "application/json")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusCreated, w.Code)
	var resp map[string]interface{}
	json.Unmarshal(w.Body.Bytes(), &resp)
	assert.Equal(t, "item synced", resp["status"])

	// Check item persisted
	items, _ := svc.GetAllItems(ctx, session.ID)
	assert.Len(t, items, 1)
	assert.Equal(t, "item1", items[0].ID)
}

func TestRealtimeSyncHandler_GetOnlineDevices(t *testing.T) {
	gin.SetMode(gin.TestMode)

	sessionRepo := repository.NewInMemorySyncSessionRepository()
	itemRepo := repository.NewInMemorySyncItemRepository()
	pendingRepo := repository.NewInMemoryPendingChangeRepository()
	svc := service.NewRealtimeSyncService(sessionRepo, itemRepo, pendingRepo)
	h := NewRealtimeSyncHandler(svc)

	router := gin.New()
	h.RegisterRoutes(router)

	ctx := context.Background()
	session, _ := svc.CreateSession(ctx, "user1", "dev1", "mobile")

	req, _ := http.NewRequest(http.MethodGet, "/api/realtimesync/session/"+session.ID+"/devices", nil)
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
	var resp struct {
		Devices []entity.Device `json:"devices"`
	}
	json.Unmarshal(w.Body.Bytes(), &resp)
	assert.Len(t, resp.Devices, 1)
	assert.True(t, resp.Devices[0].Online)
}

func TestRealtimeSyncHandler_DeleteItem(t *testing.T) {
	gin.SetMode(gin.TestMode)

	sessionRepo := repository.NewInMemorySyncSessionRepository()
	itemRepo := repository.NewInMemorySyncItemRepository()
	pendingRepo := repository.NewInMemoryPendingChangeRepository()
	svc := service.NewRealtimeSyncService(sessionRepo, itemRepo, pendingRepo)
	h := NewRealtimeSyncHandler(svc)

	router := gin.New()
	h.RegisterRoutes(router)

	ctx := context.Background()
	session, _ := svc.CreateSession(ctx, "user1", "dev1", "mobile")

	// First sync an item
	item := &entity.SyncItem{
		ID:        "item1",
		SessionID: session.ID,
		Type:      "note",
		Data:      map[string]interface{}{"content": "to delete"},
		Version:   1,
	}
	svc.SyncItem(ctx, session.ID, item.ID, item.Type, item.Data, item.Version)

	// Delete item
	url := "/api/realtimesync/session/" + session.ID + "/item/" + item.ID
	req, _ := http.NewRequest(http.MethodDelete, url, nil)
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)

	// Verify item gone
	items, _ := svc.GetAllItems(ctx, session.ID)
	assert.Len(t, items, 0)
}
