package videoconference

import (
	"encoding/json"
	"net/http"
	"strings"

	"github.com/gorilla/mux"
)

// VideoConferenceHandler handles video conference HTTP requests
type VideoConferenceHandler struct {
	svc *VideoConferenceService
}

// NewVideoConferenceHandler creates a new video conference handler
func NewVideoConferenceHandler() *VideoConferenceHandler {
	return &VideoConferenceHandler{
		svc: NewVideoConferenceService(),
	}
}

func (h *VideoConferenceHandler) requireAuth(next http.Handler) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		authHeader := r.Header.Get("Authorization")
		if authHeader == "" {
			http.Error(w, "Authorization required", http.StatusUnauthorized)
			return
		}
		if !strings.HasPrefix(authHeader, "Bearer ") {
			http.Error(w, "Invalid authorization format", http.StatusUnauthorized)
			return
		}
		token := authHeader[7:]
		if len(token) < 10 {
			http.Error(w, "Invalid token", http.StatusUnauthorized)
			return
		}
		r.Header.Set("X-User-ID", token)
		next.ServeHTTP(w, r)
	})
}

// RegisterRoutes registers all video conference routes
func (h *VideoConferenceHandler) RegisterRoutes(router *mux.Router) {
	authRouter := router.NewRoute().Subrouter()
	authRouter.Use(h.requireAuth)

	authRouter.HandleFunc("/rooms", h.CreateRoom).Methods("POST")
	authRouter.HandleFunc("/rooms/{room_id}/join", h.JoinRoom).Methods("POST")
	authRouter.HandleFunc("/rooms/{room_id}/leave", h.LeaveRoom).Methods("POST")

	router.HandleFunc("/rooms", h.ListRooms).Methods("GET")
	router.HandleFunc("/rooms/{room_id}", h.GetRoom).Methods("GET")
	router.HandleFunc("/rooms/{room_id}/users", h.GetRoomUsers).Methods("GET")
}

// CreateRoomRequest represents a create room request
type CreateRoomRequest struct {
	Name string `json:"name"`
}

// CreateRoom creates a new video conference room
func (h *VideoConferenceHandler) CreateRoom(w http.ResponseWriter, r *http.Request) {
	var req CreateRoomRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		http.Error(w, err.Error(), http.StatusBadRequest)
		return
	}

	if strings.TrimSpace(req.Name) == "" {
		http.Error(w, "name is required", http.StatusBadRequest)
		return
	}

	room := h.svc.CreateRoom(req.Name)
	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(room)
}

// ListRooms lists all active rooms
func (h *VideoConferenceHandler) ListRooms(w http.ResponseWriter, r *http.Request) {
	rooms := make([]*Room, 0, len(h.svc.rooms))
	h.svc.mu.RLock()
	for _, room := range h.svc.rooms {
		if room.Active {
			rooms = append(rooms, room)
		}
	}
	h.svc.mu.RUnlock()

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(map[string]interface{}{
		"rooms": rooms,
		"count": len(rooms),
	})
}

// GetRoom retrieves a room by ID
func (h *VideoConferenceHandler) GetRoom(w http.ResponseWriter, r *http.Request) {
	vars := mux.Vars(r)
	roomID := vars["room_id"]

	room, err := h.svc.GetRoom(roomID)
	if err != nil {
		http.Error(w, err.Error(), http.StatusNotFound)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(room)
}

// JoinRoomRequest represents a join room request
type JoinRoomRequest struct {
	UserID   string `json:"user_id"`
	UserName string `json:"user_name"`
}

// JoinRoom adds a user to a room
func (h *VideoConferenceHandler) JoinRoom(w http.ResponseWriter, r *http.Request) {
	vars := mux.Vars(r)
	roomID := vars["room_id"]

	var req JoinRoomRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		http.Error(w, err.Error(), http.StatusBadRequest)
		return
	}

	if strings.TrimSpace(req.UserID) == "" {
		http.Error(w, "user_id is required", http.StatusBadRequest)
		return
	}

	room, err := h.svc.JoinRoom(roomID, req.UserID, req.UserName)
	if err != nil {
		http.Error(w, err.Error(), http.StatusNotFound)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(map[string]interface{}{
		"room": room,
		"user": req.UserName,
	})
}

// LeaveRoomRequest represents a leave room request
type LeaveRoomRequest struct {
	UserID string `json:"user_id"`
}

// LeaveRoom removes a user from a room
func (h *VideoConferenceHandler) LeaveRoom(w http.ResponseWriter, r *http.Request) {
	vars := mux.Vars(r)
	roomID := vars["room_id"]

	var req LeaveRoomRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		http.Error(w, err.Error(), http.StatusBadRequest)
		return
	}

	if strings.TrimSpace(req.UserID) == "" {
		http.Error(w, "user_id is required", http.StatusBadRequest)
		return
	}

	if err := h.svc.LeaveRoom(roomID, req.UserID); err != nil {
		http.Error(w, err.Error(), http.StatusNotFound)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(map[string]string{"status": "left"})
}

// GetRoomUsers retrieves all users in a room
func (h *VideoConferenceHandler) GetRoomUsers(w http.ResponseWriter, r *http.Request) {
	vars := mux.Vars(r)
	roomID := vars["room_id"]

	room, err := h.svc.GetRoom(roomID)
	if err != nil {
		http.Error(w, err.Error(), http.StatusNotFound)
		return
	}

	users := make([]*User, 0, len(room.Users))
	for _, user := range room.Users {
		users = append(users, user)
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(map[string]interface{}{
		"users": users,
		"count": len(users),
	})
}
