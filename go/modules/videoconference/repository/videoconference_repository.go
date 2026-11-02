package repository

import (
	"context"
	"sync"

	"community.local/modules/videoconference/entity"
)

type InMemoryVideoConferenceRepository struct {
	mu           sync.RWMutex
	rooms        map[string]*entity.Room
	participants map[string]map[string]*entity.Participant
}

func NewInMemoryVideoConferenceRepository() *InMemoryVideoConferenceRepository {
	return &InMemoryVideoConferenceRepository{
		rooms:        make(map[string]*entity.Room),
		participants: make(map[string]map[string]*entity.Participant),
	}
}

func (r *InMemoryVideoConferenceRepository) CreateRoom(ctx context.Context, room *entity.Room) error {
	r.mu.Lock()
	defer r.mu.Unlock()
	r.rooms[room.ID] = room
	r.participants[room.ID] = make(map[string]*entity.Participant)
	return nil
}

func (r *InMemoryVideoConferenceRepository) GetRoom(ctx context.Context, id string) (*entity.Room, error) {
	r.mu.RLock()
	defer r.mu.RUnlock()
	return r.rooms[id], nil
}

func (r *InMemoryVideoConferenceRepository) UpdateRoom(ctx context.Context, room *entity.Room) error {
	r.mu.Lock()
	defer r.mu.Unlock()
	r.rooms[room.ID] = room
	return nil
}

func (r *InMemoryVideoConferenceRepository) DeleteRoom(ctx context.Context, id string) error {
	r.mu.Lock()
	defer r.mu.Unlock()
	delete(r.rooms, id)
	delete(r.participants, id)
	return nil
}

func (r *InMemoryVideoConferenceRepository) ListRooms(ctx context.Context) ([]*entity.Room, error) {
	r.mu.RLock()
	defer r.mu.RUnlock()
	rooms := make([]*entity.Room, 0, len(r.rooms))
	for _, room := range r.rooms {
		rooms = append(rooms, room)
	}
	return rooms, nil
}

func (r *InMemoryVideoConferenceRepository) AddParticipant(ctx context.Context, roomID string, participant *entity.Participant) error {
	r.mu.Lock()
	defer r.mu.Unlock()
	if r.participants[roomID] == nil {
		r.participants[roomID] = make(map[string]*entity.Participant)
	}
	r.participants[roomID][participant.ID] = participant
	return nil
}

func (r *InMemoryVideoConferenceRepository) RemoveParticipant(ctx context.Context, roomID, participantID string) error {
	r.mu.Lock()
	defer r.mu.Unlock()
	if r.participants[roomID] != nil {
		delete(r.participants[roomID], participantID)
	}
	return nil
}

func (r *InMemoryVideoConferenceRepository) GetParticipant(ctx context.Context, roomID, participantID string) (*entity.Participant, error) {
	r.mu.RLock()
	defer r.mu.RUnlock()
	if r.participants[roomID] == nil {
		return nil, nil
	}
	return r.participants[roomID][participantID], nil
}

func (r *InMemoryVideoConferenceRepository) GetParticipants(ctx context.Context, roomID string) ([]*entity.Participant, error) {
	r.mu.RLock()
	defer r.mu.RUnlock()
	if r.participants[roomID] == nil {
		return nil, nil
	}
	participants := make([]*entity.Participant, 0, len(r.participants[roomID]))
	for _, p := range r.participants[roomID] {
		if p.IsActive() {
			participants = append(participants, p)
		}
	}
	return participants, nil
}

func (r *InMemoryVideoConferenceRepository) UpdateParticipant(ctx context.Context, roomID string, participant *entity.Participant) error {
	r.mu.Lock()
	defer r.mu.Unlock()
	if r.participants[roomID] != nil {
		r.participants[roomID][participant.ID] = participant
	}
	return nil
}
