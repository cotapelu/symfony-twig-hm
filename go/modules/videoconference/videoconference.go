package videoconference

import (
	"crypto/rand"
	"encoding/json"
	"fmt"
	"log"
	"net/http"
	"sync"
	"time"
)

type Room struct {
	ID        string           `json:"id"`
	Name      string           `json:"name"`
	CreatedAt time.Time        `json:"createdAt"`
	Active    bool             `json:"active"`
	Users     map[string]*User `json:"users"`
}

type User struct {
	ID       string    `json:"id"`
	Name     string    `json:"name"`
	Active   bool      `json:"active"`
	JoinedAt time.Time `json:"joinedAt"`
}

type VideoConferenceService struct {
	mu    sync.RWMutex
	rooms map[string]*Room
}

func NewVideoConferenceService() *VideoConferenceService {
	return &VideoConferenceService{
		rooms: make(map[string]*Room),
	}
}

func (s *VideoConferenceService) CreateRoom(name string) *Room {
	s.mu.Lock()
	defer s.mu.Unlock()

	room := &Room{
		ID:        generateRoomID(),
		Name:      name,
		CreatedAt: time.Now(),
		Active:    true,
		Users:     make(map[string]*User),
	}
	s.rooms[room.ID] = room
	return room
}

func (s *VideoConferenceService) JoinRoom(roomID, userID, userName string) (*Room, error) {
	s.mu.Lock()
	defer s.mu.Unlock()

	room, ok := s.rooms[roomID]
	if !ok {
		return nil, ErrRoomNotFound
	}

	user := &User{
		ID:       userID,
		Name:     userName,
		Active:   true,
		JoinedAt: time.Now(),
	}
	room.Users[userID] = user
	return room, nil
}

func (s *VideoConferenceService) LeaveRoom(roomID, userID string) error {
	s.mu.Lock()
	defer s.mu.Unlock()

	room, ok := s.rooms[roomID]
	if !ok {
		return ErrRoomNotFound
	}

	delete(room.Users, userID)
	return nil
}

func (s *VideoConferenceService) GetRoom(roomID string) (*Room, error) {
	s.mu.RLock()
	defer s.mu.RUnlock()

	room, ok := s.rooms[roomID]
	if !ok {
		return nil, ErrRoomNotFound
	}
	return room, nil
}

var ErrRoomNotFound = fmt.Errorf("room not found")

func generateRoomID() string {
	return "room-" + time.Now().Format("20060102150405") + "-" + randomString(8)
}

func randomString(n int) string {
	const letters = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"
	b := make([]byte, n)
	rand.Read(b)
	for i := range b {
		b[i] = letters[int(b[i])%len(letters)]
	}
	return string(b)
}

func main() {
	service := NewVideoConferenceService()
	http.HandleFunc("/rooms", func(w http.ResponseWriter, r *http.Request) {
		rooms := make([]*Room, 0, len(service.rooms))
		for _, room := range service.rooms {
			rooms = append(rooms, room)
		}
		json.NewEncoder(w).Encode(rooms)
	})
	log.Fatal(http.ListenAndServe(":8081", nil))
}
