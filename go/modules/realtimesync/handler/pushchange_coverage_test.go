package handler

import (
	"bytes"
	"context"
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"testing"

	"community.local/modules/realtimesync/repository"
	"community.local/modules/realtimesync/service"
	"github.com/gin-gonic/gin"
	"github.com/stretchr/testify/assert"
)

// TestRealtimeSyncHandler_PushChange_Coverage tests POST /session/:sessionId/push
// covering valid payload and missing fields, to increase handler coverage.
func TestRealtimeSyncHandler_PushChange_Coverage(t *testing.T) {
	gin.SetMode(gin.TestMode)

	sessionRepo := repository.NewInMemorySyncSessionRepository()
	itemRepo := repository.NewInMemorySyncItemRepository()
	pendingRepo := repository.NewInMemoryPendingChangeRepository()
	svc := service.NewRealtimeSyncService(sessionRepo, itemRepo, pendingRepo)
	h := NewRealtimeSyncHandler(svc)

	router := setupRouterWithAuth("user123")
	h.RegisterRoutes(router)

	ctx := context.Background()
	session, _ := svc.CreateSession(ctx, "user123", "device456", "mobile")

	// Test missing required fields (no item_id)
	req, _ := http.NewRequest(http.MethodPost, "/api/realtimesync/session/"+session.ID+"/push", bytes.NewReader([]byte(`{}`)))
	req.Header.Set("Content-Type", "application/json")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)
	assert.Equal(t, http.StatusBadRequest, w.Code)

	// Valid change
	change := map[string]interface{}{
		"item_id": "item1",
		"data":    map[string]interface{}{"content": "test"},
	}
	body, _ := json.Marshal(change)
	req, _ = http.NewRequest(http.MethodPost, "/api/realtimesync/session/"+session.ID+"/push", bytes.NewReader(body))
	req.Header.Set("Content-Type", "application/json")
	w = httptest.NewRecorder()
	router.ServeHTTP(w, req)
	// Handler returns 201 Created on success
	assert.Contains(t, []int{http.StatusOK, http.StatusCreated}, w.Code)
}
