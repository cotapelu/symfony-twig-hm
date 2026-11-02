package entity

import (
	"context"
	"time"
)

type Room struct {
	ID        string           `json:"id"`
	Name      string           `json:"name"`
	CreatedAt time.Time        `json:"created_at"`
	ExpiresAt time.Time        `json:"expires_at"`
	Active    bool             `json:"active"`
	Users     map[string]*User `json:"users"`
}

type User struct {
	ID       string    `json:"id"`
	Name     string    `json:"name"`
	Active   bool      `json:"active"`
	JoinedAt time.Time `json:"joined_at"`
}

func NewRoom(id, name string) *Room {
	return &Room{
		ID:        id,
		Name:      name,
		CreatedAt: time.Now(),
		Active:    true,
		Users:     make(map[string]*User),
	}
}

type VideoConferenceRepository interface {
	CreateRoom(ctx context.Context, room *Room) error
	GetRoom(ctx context.Context, id string) (*Room, error)
	UpdateRoom(ctx context.Context, room *Room) error
	DeleteRoom(ctx context.Context, id string) error
	ListRooms(ctx context.Context) ([]*Room, error)

	AddParticipant(ctx context.Context, roomID string, participant *Participant) error
	RemoveParticipant(ctx context.Context, roomID, participantID string) error
	GetParticipant(ctx context.Context, roomID, participantID string) (*Participant, error)
	GetParticipants(ctx context.Context, roomID string) ([]*Participant, error)
	UpdateParticipant(ctx context.Context, roomID string, participant *Participant) error
}
