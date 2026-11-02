package videoconference

import (
	"bytes"
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"testing"

	"github.com/gorilla/mux"
	"github.com/stretchr/testify/assert"
)

// setupRouter creates a router with a fresh handler and returns both the router and the handler
func setupRouter() (*mux.Router, *VideoConferenceHandler) {
	handler := NewVideoConferenceHandler()
	router := mux.NewRouter()
	handler.RegisterRoutes(router)
	return router, handler
}

func TestCreateRoomHTTP(t *testing.T) {
	router, _ := setupRouter()

	// Success (with auth)
	payload := `{"name": "Daily Standup"}`
	req := httptest.NewRequest(http.MethodPost, "/rooms", bytes.NewReader([]byte(payload)))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Authorization", "Bearer longtoken123")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
	var resp map[string]interface{}
	json.Unmarshal(w.Body.Bytes(), &resp)
	assert.Equal(t, "Daily Standup", resp["name"])
	assert.NotEmpty(t, resp["id"])
	assert.True(t, resp["active"].(bool))

	// Invalid JSON (with auth)
	req2 := httptest.NewRequest(http.MethodPost, "/rooms", bytes.NewReader([]byte(`{invalid}`)))
	req2.Header.Set("Content-Type", "application/json")
	req2.Header.Set("Authorization", "Bearer longtoken123")
	w2 := httptest.NewRecorder()
	router.ServeHTTP(w2, req2)
	t.Logf("Invalid JSON response: status=%d, body=%s", w2.Code, w2.Body.String())
	assert.Equal(t, http.StatusBadRequest, w2.Code)

	// Missing name (with auth)
	req3 := httptest.NewRequest(http.MethodPost, "/rooms", bytes.NewReader([]byte(`{}`)))
	req3.Header.Set("Content-Type", "application/json")
	req3.Header.Set("Authorization", "Bearer longtoken123")
	w3 := httptest.NewRecorder()
	router.ServeHTTP(w3, req3)
	t.Logf("Missing name response: status=%d, body=%s", w3.Code, w3.Body.String())
	assert.Equal(t, http.StatusBadRequest, w3.Code)
}

func TestListRoomsHTTP(t *testing.T) {
	router, handler := setupRouter()

	// Initially empty
	req := httptest.NewRequest(http.MethodGet, "/rooms", nil)
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)
	assert.Equal(t, http.StatusOK, w.Code)
	var resp map[string]interface{}
	json.Unmarshal(w.Body.Bytes(), &resp)
	roomsVal := resp["rooms"].([]interface{})
	assert.Len(t, roomsVal, 0)
	assert.Equal(t, float64(0), resp["count"])

	// Create a room via service
	handler.svc.CreateRoom("Room1")

	req2 := httptest.NewRequest(http.MethodGet, "/rooms", nil)
	w2 := httptest.NewRecorder()
	router.ServeHTTP(w2, req2)
	assert.Equal(t, http.StatusOK, w2.Code)
	var resp2 map[string]interface{}
	json.Unmarshal(w2.Body.Bytes(), &resp2)
	roomsVal2 := resp2["rooms"].([]interface{})
	assert.Len(t, roomsVal2, 1)
}

func TestGetRoomHTTP(t *testing.T) {
	router, handler := setupRouter()

	room := handler.svc.CreateRoom("Test Room")
	roomID := room.ID

	// Success
	req := httptest.NewRequest(http.MethodGet, "/rooms/"+roomID, nil)
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)
	assert.Equal(t, http.StatusOK, w.Code)
	var resp map[string]interface{}
	json.Unmarshal(w.Body.Bytes(), &resp)
	assert.Equal(t, roomID, resp["id"])

	// Not found
	req2 := httptest.NewRequest(http.MethodGet, "/rooms/nonexistent", nil)
	w2 := httptest.NewRecorder()
	router.ServeHTTP(w2, req2)
	assert.Equal(t, http.StatusNotFound, w2.Code)
}

func TestJoinRoomHTTP(t *testing.T) {
	router, handler := setupRouter()

	room := handler.svc.CreateRoom("Test Room")
	roomID := room.ID

	payload := `{"user_id": "user1", "user_name": "Alice"}`
	req := httptest.NewRequest(http.MethodPost, "/rooms/"+roomID+"/join", bytes.NewReader([]byte(payload)))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Authorization", "Bearer longtoken123")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
	var resp map[string]interface{}
	json.Unmarshal(w.Body.Bytes(), &resp)
	roomResp := resp["room"].(map[string]interface{})
	assert.Equal(t, roomID, roomResp["id"])
	user := resp["user"].(string)
	assert.Equal(t, "Alice", user)
	// Check user in room
	users := roomResp["users"].(map[string]interface{})
	_, ok := users["user1"]
	assert.True(t, ok)

	// Invalid JSON (with auth)
	req2 := httptest.NewRequest(http.MethodPost, "/rooms/"+roomID+"/join", bytes.NewReader([]byte(`{invalid}`)))
	req2.Header.Set("Content-Type", "application/json")
	req2.Header.Set("Authorization", "Bearer longtoken123")
	w2 := httptest.NewRecorder()
	router.ServeHTTP(w2, req2)
	assert.Equal(t, http.StatusBadRequest, w2.Code)

	// Missing user_id
	payload3 := `{"user_name": "Bob"}`
	req3 := httptest.NewRequest(http.MethodPost, "/rooms/"+roomID+"/join", bytes.NewReader([]byte(payload3)))
	req3.Header.Set("Content-Type", "application/json")
	req3.Header.Set("Authorization", "Bearer longtoken123")
	w3 := httptest.NewRecorder()
	router.ServeHTTP(w3, req3)
	assert.Equal(t, http.StatusBadRequest, w3.Code)

	// Non-existent room
	req4 := httptest.NewRequest(http.MethodPost, "/rooms/nonexistent/join", bytes.NewReader([]byte(payload)))
	req4.Header.Set("Content-Type", "application/json")
	req4.Header.Set("Authorization", "Bearer longtoken123")
	w4 := httptest.NewRecorder()
	router.ServeHTTP(w4, req4)
	assert.Equal(t, http.StatusNotFound, w4.Code)
}

func TestLeaveRoomHTTP(t *testing.T) {
	router, handler := setupRouter()

	room := handler.svc.CreateRoom("Test Room")
	roomID := room.ID
	handler.svc.JoinRoom(roomID, "user1", "Alice")

	payload := `{"user_id": "user1"}`
	req := httptest.NewRequest(http.MethodPost, "/rooms/"+roomID+"/leave", bytes.NewReader([]byte(payload)))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Authorization", "Bearer longtoken123")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)

	// Verify user removed: GetRoom should show no users
	req2 := httptest.NewRequest(http.MethodGet, "/rooms/"+roomID, nil)
	w2 := httptest.NewRecorder()
	router.ServeHTTP(w2, req2)
	assert.Equal(t, http.StatusOK, w2.Code)
	var resp map[string]interface{}
	json.Unmarshal(w2.Body.Bytes(), &resp)
	users := resp["users"].(map[string]interface{})
	assert.Empty(t, users)

	// Invalid JSON (with auth)
	req3 := httptest.NewRequest(http.MethodPost, "/rooms/"+roomID+"/leave", bytes.NewReader([]byte(`{invalid}`)))
	req3.Header.Set("Content-Type", "application/json")
	req3.Header.Set("Authorization", "Bearer longtoken123")
	w3 := httptest.NewRecorder()
	router.ServeHTTP(w3, req3)
	assert.Equal(t, http.StatusBadRequest, w3.Code)

	// Missing user_id
	req4 := httptest.NewRequest(http.MethodPost, "/rooms/"+roomID+"/leave", bytes.NewReader([]byte(`{}`)))
	req4.Header.Set("Content-Type", "application/json")
	req4.Header.Set("Authorization", "Bearer longtoken123")
	w4 := httptest.NewRecorder()
	router.ServeHTTP(w4, req4)
	assert.Equal(t, http.StatusBadRequest, w4.Code)

	// Non-existent room (auth needed)
	req5 := httptest.NewRequest(http.MethodPost, "/rooms/nonexistent/leave", bytes.NewReader([]byte(payload)))
	req5.Header.Set("Content-Type", "application/json")
	req5.Header.Set("Authorization", "Bearer longtoken123")
	w5 := httptest.NewRecorder()
	router.ServeHTTP(w5, req5)
	assert.Equal(t, http.StatusNotFound, w5.Code)
}

func TestGetRoomUsersHTTP(t *testing.T) {
	router, handler := setupRouter()

	room := handler.svc.CreateRoom("Test Room")
	roomID := room.ID
	handler.svc.JoinRoom(roomID, "user1", "Alice")
	handler.svc.JoinRoom(roomID, "user2", "Bob")

	req := httptest.NewRequest(http.MethodGet, "/rooms/"+roomID+"/users", nil)
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
	var resp map[string]interface{}
	json.Unmarshal(w.Body.Bytes(), &resp)
	usersVal := resp["users"].([]interface{})
	assert.Len(t, usersVal, 2)
	// Verify user IDs are present
	ids := make([]string, 0, len(usersVal))
	for _, u := range usersVal {
		if m, ok := u.(map[string]interface{}); ok {
			ids = append(ids, m["id"].(string))
		}
	}
	assert.Contains(t, ids, "user1")
	assert.Contains(t, ids, "user2")
}

func TestRequireAuth(t *testing.T) {
	handler := NewVideoConferenceHandler()
	router := mux.NewRouter()
	router.Handle("/protected", http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		w.WriteHeader(http.StatusOK)
	})).Methods("GET")
	router.Use(handler.requireAuth)

	// Missing Authorization header
	req := httptest.NewRequest(http.MethodGet, "/protected", nil)
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)
	assert.Equal(t, http.StatusUnauthorized, w.Code)

	// Invalid format
	req2 := httptest.NewRequest(http.MethodGet, "/protected", nil)
	req2.Header.Set("Authorization", "Basic abc")
	w2 := httptest.NewRecorder()
	router.ServeHTTP(w2, req2)
	assert.Equal(t, http.StatusUnauthorized, w2.Code)

	// Token too short
	req3 := httptest.NewRequest(http.MethodGet, "/protected", nil)
	req3.Header.Set("Authorization", "Bearer short")
	w3 := httptest.NewRecorder()
	router.ServeHTTP(w3, req3)
	assert.Equal(t, http.StatusUnauthorized, w3.Code)

	// Valid token
	req4 := httptest.NewRequest(http.MethodGet, "/protected", nil)
	req4.Header.Set("Authorization", "Bearer longenoughtoken")
	w4 := httptest.NewRecorder()
	router.ServeHTTP(w4, req4)
	assert.Equal(t, http.StatusOK, w4.Code)
}
