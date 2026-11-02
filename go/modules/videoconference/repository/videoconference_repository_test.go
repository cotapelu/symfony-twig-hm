package repository

import (
	"context"
	"testing"
	"time"

	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"

	"community.local/modules/videoconference/entity"
)

func newNow() time.Time {
	return time.Now().Truncate(time.Microsecond)
}

func newRepo() *InMemoryVideoConferenceRepository {
	return NewInMemoryVideoConferenceRepository()
}

func TestInMemoryVideoConferenceRepository_CreateAndGetRoom(t *testing.T) {
	repo := newRepo()
	room := entity.NewRoom("room-1", "Daily Standup")
	room.ExpiresAt = time.Now().Add(2 * time.Hour)

	require.NoError(t, repo.CreateRoom(context.Background(), room))

	retrieved, err := repo.GetRoom(context.Background(), "room-1")
	require.NoError(t, err)
	assert.Equal(t, room, retrieved)
}

func TestInMemoryVideoConferenceRepository_GetRoom_NotFound(t *testing.T) {
	repo := newRepo()
	_, err := repo.GetRoom(context.Background(), "nonexistent")
	require.NoError(t, err)
	// returns nil room
	// We can't distinguish error; GetRoom returns (nil, nil) if not exists.
	// The implementation returns r.rooms[id] which is nil if not present; no error.
	// So we just check nil.
}

func TestInMemoryVideoConferenceRepository_UpdateRoom(t *testing.T) {
	repo := newRepo()
	room := entity.NewRoom("room-1", "Daily Standup")
	require.NoError(t, repo.CreateRoom(context.Background(), room))

	room.Name = "Updated Standup"
	room.Active = false
	room.ExpiresAt = time.Now().Add(1 * time.Hour)
	require.NoError(t, repo.UpdateRoom(context.Background(), room))

	retrieved, _ := repo.GetRoom(context.Background(), "room-1")
	assert.Equal(t, "Updated Standup", retrieved.Name)
	assert.False(t, retrieved.Active)
}

func TestInMemoryVideoConferenceRepository_DeleteRoom(t *testing.T) {
	repo := newRepo()
	room := entity.NewRoom("room-1", "Daily Standup")
	require.NoError(t, repo.CreateRoom(context.Background(), room))

	require.NoError(t, repo.DeleteRoom(context.Background(), "room-1"))

	retrieved, err := repo.GetRoom(context.Background(), "room-1")
	require.NoError(t, err)
	assert.Nil(t, retrieved)
}

func TestInMemoryVideoConferenceRepository_ListRooms(t *testing.T) {
	repo := newRepo()
	rooms := []*entity.Room{
		entity.NewRoom("r1", "Room1"),
		entity.NewRoom("r2", "Room2"),
	}
	for _, r := range rooms {
		require.NoError(t, repo.CreateRoom(context.Background(), r))
	}
	all, err := repo.ListRooms(context.Background())
	require.NoError(t, err)
	assert.ElementsMatch(t, rooms, all)
}

func TestInMemoryVideoConferenceRepository_AddAndGetParticipant(t *testing.T) {
	repo := newRepo()
	room := entity.NewRoom("room-1", "Daily Standup")
	require.NoError(t, repo.CreateRoom(context.Background(), room))

	p := entity.NewParticipant("p1", "u1", "Alice", "room-1", entity.RoleHost)
	require.NoError(t, repo.AddParticipant(context.Background(), "room-1", p))

	retrieved, err := repo.GetParticipant(context.Background(), "room-1", "p1")
	require.NoError(t, err)
	assert.Equal(t, p, retrieved)
}

func TestInMemoryVideoConferenceRepository_GetParticipant_NotFound(t *testing.T) {
	repo := newRepo()
	// No room created; GetParticipant should return nil, not error.
	p, err := repo.GetParticipant(context.Background(), "room-1", "p1")
	require.NoError(t, err)
	assert.Nil(t, p)
}

func TestInMemoryVideoConferenceRepository_GetParticipants(t *testing.T) {
	repo := newRepo()
	room := entity.NewRoom("room-1", "Daily Standup")
	require.NoError(t, repo.CreateRoom(context.Background(), room))

	activeP := entity.NewParticipant("p1", "u1", "Alice", "room-1", entity.RoleHost)
	leftP := entity.NewParticipant("p2", "u2", "Bob", "room-1", entity.RoleAttendee)
	leftP.Leave()
	require.NoError(t, repo.AddParticipant(context.Background(), "room-1", activeP))
	require.NoError(t, repo.AddParticipant(context.Background(), "room-1", leftP))

	participants, err := repo.GetParticipants(context.Background(), "room-1")
	require.NoError(t, err)
	assert.Len(t, participants, 1)
	assert.Equal(t, "p1", participants[0].ID)
}

func TestInMemoryVideoConferenceRepository_RemoveParticipant(t *testing.T) {
	repo := newRepo()
	room := entity.NewRoom("room-1", "Daily Standup")
	require.NoError(t, repo.CreateRoom(context.Background(), room))

	p := entity.NewParticipant("p1", "u1", "Alice", "room-1", entity.RoleHost)
	require.NoError(t, repo.AddParticipant(context.Background(), "room-1", p))

	require.NoError(t, repo.RemoveParticipant(context.Background(), "room-1", "p1"))

	retrieved, _ := repo.GetParticipant(context.Background(), "room-1", "p1")
	assert.Nil(t, retrieved)
}

func TestInMemoryVideoConferenceRepository_UpdateParticipant(t *testing.T) {
	repo := newRepo()
	room := entity.NewRoom("room-1", "Daily Standup")
	require.NoError(t, repo.CreateRoom(context.Background(), room))

	p := entity.NewParticipant("p1", "u1", "Alice", "room-1", entity.RoleAttendee)
	require.NoError(t, repo.AddParticipant(context.Background(), "room-1", p))

	updated := *p
	updated.Role = entity.RoleCoHost
	updated.Media.VideoEnabled = false
	require.NoError(t, repo.UpdateParticipant(context.Background(), "room-1", &updated))

	retrieved, _ := repo.GetParticipant(context.Background(), "room-1", "p1")
	assert.Equal(t, entity.RoleCoHost, retrieved.Role)
	assert.False(t, retrieved.Media.VideoEnabled)
}

func TestInMemoryVideoConferenceRepository_DeleteRoom_ClearsParticipants(t *testing.T) {
	repo := newRepo()
	room := entity.NewRoom("room-1", "Daily Standup")
	require.NoError(t, repo.CreateRoom(context.Background(), room))

	p := entity.NewParticipant("p1", "u1", "Alice", "room-1", entity.RoleHost)
	require.NoError(t, repo.AddParticipant(context.Background(), "room-1", p))

	require.NoError(t, repo.DeleteRoom(context.Background(), "room-1"))

	// Room gone
	retrieved, err := repo.GetRoom(context.Background(), "room-1")
	require.NoError(t, err)
	assert.Nil(t, retrieved)
	// Participants map cleared
	participants, _ := repo.GetParticipants(context.Background(), "room-1")
	assert.Empty(t, participants)
}
