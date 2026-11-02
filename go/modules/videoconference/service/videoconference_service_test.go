package service

import (
	"context"
	"testing"

	"community.local/modules/videoconference/entity"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"
)

type mockVideoConferenceRepo struct {
	rooms        map[string]*entity.Room
	participants map[string]map[string]*entity.Participant
}

func newMockVideoConferenceRepo() *mockVideoConferenceRepo {
	return &mockVideoConferenceRepo{
		rooms:        make(map[string]*entity.Room),
		participants: make(map[string]map[string]*entity.Participant),
	}
}

func (m *mockVideoConferenceRepo) CreateRoom(ctx context.Context, room *entity.Room) error {
	m.rooms[room.ID] = room
	m.participants[room.ID] = make(map[string]*entity.Participant)
	return nil
}

func (m *mockVideoConferenceRepo) GetRoom(ctx context.Context, id string) (*entity.Room, error) {
	if room, ok := m.rooms[id]; ok {
		return room, nil
	}
	return nil, nil
}

func (m *mockVideoConferenceRepo) UpdateRoom(ctx context.Context, room *entity.Room) error {
	m.rooms[room.ID] = room
	return nil
}

func (m *mockVideoConferenceRepo) DeleteRoom(ctx context.Context, id string) error {
	delete(m.rooms, id)
	delete(m.participants, id)
	return nil
}

func (m *mockVideoConferenceRepo) ListRooms(ctx context.Context) ([]*entity.Room, error) {
	rooms := make([]*entity.Room, 0)
	for _, room := range m.rooms {
		rooms = append(rooms, room)
	}
	return rooms, nil
}

func (m *mockVideoConferenceRepo) AddParticipant(ctx context.Context, roomID string, participant *entity.Participant) error {
	if m.participants[roomID] == nil {
		m.participants[roomID] = make(map[string]*entity.Participant)
	}
	m.participants[roomID][participant.ID] = participant
	return nil
}

func (m *mockVideoConferenceRepo) RemoveParticipant(ctx context.Context, roomID, participantID string) error {
	if m.participants[roomID] != nil {
		delete(m.participants[roomID], participantID)
	}
	return nil
}

func (m *mockVideoConferenceRepo) GetParticipant(ctx context.Context, roomID, participantID string) (*entity.Participant, error) {
	if m.participants[roomID] != nil {
		if p, ok := m.participants[roomID][participantID]; ok {
			return p, nil
		}
	}
	return nil, nil
}

func (m *mockVideoConferenceRepo) GetParticipants(ctx context.Context, roomID string) ([]*entity.Participant, error) {
	participants := make([]*entity.Participant, 0)
	if m.participants[roomID] != nil {
		for _, p := range m.participants[roomID] {
			participants = append(participants, p)
		}
	}
	return participants, nil
}

func (m *mockVideoConferenceRepo) UpdateParticipant(ctx context.Context, roomID string, participant *entity.Participant) error {
	if m.participants[roomID] != nil {
		m.participants[roomID][participant.ID] = participant
	}
	return nil
}

func TestVideoConferenceService_CreateRoom(t *testing.T) {
	repo := newMockVideoConferenceRepo()
	svc := NewVideoConferenceService(repo)
	ctx := context.Background()

	room, err := svc.CreateRoom(ctx, "Test Room")
	require.NoError(t, err)
	assert.NotEmpty(t, room.ID)
	assert.Equal(t, "Test Room", room.Name)
	assert.True(t, room.Active)
}

func TestVideoConferenceService_GetRoom(t *testing.T) {
	repo := newMockVideoConferenceRepo()
	svc := NewVideoConferenceService(repo)
	ctx := context.Background()

	created, err := svc.CreateRoom(ctx, "Test Room")
	require.NoError(t, err)

	room, err := svc.GetRoom(ctx, created.ID)
	require.NoError(t, err)
	assert.Equal(t, created.ID, room.ID)
	assert.Equal(t, "Test Room", room.Name)
}

func TestVideoConferenceService_GetRoom_NotFound(t *testing.T) {
	repo := newMockVideoConferenceRepo()
	svc := NewVideoConferenceService(repo)
	ctx := context.Background()

	_, err := svc.GetRoom(ctx, "nonexistent")
	assert.Error(t, err)
	assert.Equal(t, ErrRoomNotFound, err)
}

func TestVideoConferenceService_DeleteRoom(t *testing.T) {
	repo := newMockVideoConferenceRepo()
	svc := NewVideoConferenceService(repo)
	ctx := context.Background()

	room, err := svc.CreateRoom(ctx, "Test Room")
	require.NoError(t, err)

	err = svc.DeleteRoom(ctx, room.ID)
	require.NoError(t, err)

	_, err = svc.GetRoom(ctx, room.ID)
	assert.Error(t, err)
}

func TestVideoConferenceService_ListRooms(t *testing.T) {
	repo := newMockVideoConferenceRepo()
	svc := NewVideoConferenceService(repo)
	ctx := context.Background()

	_, err := svc.CreateRoom(ctx, "Room 1")
	require.NoError(t, err)
	_, err = svc.CreateRoom(ctx, "Room 2")
	require.NoError(t, err)

	rooms, err := svc.ListRooms(ctx)
	require.NoError(t, err)
	assert.Len(t, rooms, 2)
}

func TestVideoConferenceService_JoinRoom(t *testing.T) {
	repo := newMockVideoConferenceRepo()
	svc := NewVideoConferenceService(repo)
	ctx := context.Background()

	room, err := svc.CreateRoom(ctx, "Test Room")
	require.NoError(t, err)

	participant, err := svc.JoinRoom(ctx, room.ID, "user_123", "John Doe")
	require.NoError(t, err)
	assert.NotEmpty(t, participant.ID)
	assert.Equal(t, "John Doe", participant.DisplayName)
	assert.Equal(t, entity.RoleHost, participant.Role)
}

func TestVideoConferenceService_JoinRoom_SecondParticipant(t *testing.T) {
	repo := newMockVideoConferenceRepo()
	svc := NewVideoConferenceService(repo)
	ctx := context.Background()

	room, err := svc.CreateRoom(ctx, "Test Room")
	require.NoError(t, err)

	_, err = svc.JoinRoom(ctx, room.ID, "user_123", "John")
	require.NoError(t, err)

	participant, err := svc.JoinRoom(ctx, room.ID, "user_456", "Jane")
	require.NoError(t, err)
	assert.Equal(t, entity.RoleAttendee, participant.Role)
}

func TestVideoConferenceService_JoinRoom_RoomNotFound(t *testing.T) {
	repo := newMockVideoConferenceRepo()
	svc := NewVideoConferenceService(repo)
	ctx := context.Background()

	_, err := svc.JoinRoom(ctx, "nonexistent", "user_123", "John")
	assert.Error(t, err)
	assert.Equal(t, ErrRoomNotFound, err)
}

func TestVideoConferenceService_LeaveRoom(t *testing.T) {
	repo := newMockVideoConferenceRepo()
	svc := NewVideoConferenceService(repo)
	ctx := context.Background()

	room, err := svc.CreateRoom(ctx, "Test Room")
	require.NoError(t, err)

	participant, err := svc.JoinRoom(ctx, room.ID, "user_123", "John")
	require.NoError(t, err)

	err = svc.LeaveRoom(ctx, room.ID, participant.ID)
	require.NoError(t, err)

	participants, err := svc.GetParticipants(ctx, room.ID)
	require.NoError(t, err)
	assert.Len(t, participants, 0)
}

func TestVideoConferenceService_GetParticipants(t *testing.T) {
	repo := newMockVideoConferenceRepo()
	svc := NewVideoConferenceService(repo)
	ctx := context.Background()

	room, err := svc.CreateRoom(ctx, "Test Room")
	require.NoError(t, err)

	_, err = svc.JoinRoom(ctx, room.ID, "user_1", "User1")
	require.NoError(t, err)
	_, err = svc.JoinRoom(ctx, room.ID, "user_2", "User2")
	require.NoError(t, err)

	participants, err := svc.GetParticipants(ctx, room.ID)
	require.NoError(t, err)
	assert.Len(t, participants, 2)
}

func TestVideoConferenceService_ToggleAudio(t *testing.T) {
	repo := newMockVideoConferenceRepo()
	svc := NewVideoConferenceService(repo)
	ctx := context.Background()

	room, err := svc.CreateRoom(ctx, "Test Room")
	require.NoError(t, err)

	participant, err := svc.JoinRoom(ctx, room.ID, "user_123", "John")
	require.NoError(t, err)

	assert.True(t, participant.Media.AudioEnabled)

	err = svc.ToggleAudio(ctx, room.ID, participant.ID)
	require.NoError(t, err)

	participants, _ := svc.GetParticipants(ctx, room.ID)
	assert.False(t, participants[0].Media.AudioEnabled)
}

func TestVideoConferenceService_ToggleVideo(t *testing.T) {
	repo := newMockVideoConferenceRepo()
	svc := NewVideoConferenceService(repo)
	ctx := context.Background()

	room, err := svc.CreateRoom(ctx, "Test Room")
	require.NoError(t, err)

	participant, err := svc.JoinRoom(ctx, room.ID, "user_123", "John")
	require.NoError(t, err)

	assert.True(t, participant.Media.VideoEnabled)

	err = svc.ToggleVideo(ctx, room.ID, participant.ID)
	require.NoError(t, err)

	participants, _ := svc.GetParticipants(ctx, room.ID)
	assert.False(t, participants[0].Media.VideoEnabled)
}

func TestVideoConferenceService_StartScreenShare(t *testing.T) {
	repo := newMockVideoConferenceRepo()
	svc := NewVideoConferenceService(repo)
	ctx := context.Background()

	room, err := svc.CreateRoom(ctx, "Test Room")
	require.NoError(t, err)

	participant, err := svc.JoinRoom(ctx, room.ID, "user_123", "John")
	require.NoError(t, err)

	assert.False(t, participant.Media.ScreenSharing)

	err = svc.StartScreenShare(ctx, room.ID, participant.ID)
	require.NoError(t, err)

	participants, _ := svc.GetParticipants(ctx, room.ID)
	assert.True(t, participants[0].Media.ScreenSharing)
}

func TestVideoConferenceService_StopScreenShare(t *testing.T) {
	repo := newMockVideoConferenceRepo()
	svc := NewVideoConferenceService(repo)
	ctx := context.Background()

	room, err := svc.CreateRoom(ctx, "Test Room")
	require.NoError(t, err)

	participant, err := svc.JoinRoom(ctx, room.ID, "user_123", "John")
	require.NoError(t, err)

	err = svc.StartScreenShare(ctx, room.ID, participant.ID)
	require.NoError(t, err)

	err = svc.StopScreenShare(ctx, room.ID, participant.ID)
	require.NoError(t, err)

	participants, _ := svc.GetParticipants(ctx, room.ID)
	assert.False(t, participants[0].Media.ScreenSharing)
}
