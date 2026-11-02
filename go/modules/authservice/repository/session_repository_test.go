package repository

import (
	"os"
	"path/filepath"
	"testing"
	"time"

	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"

	"community.local/modules/authservice/entity"
)

func newTempSessionRepo(t *testing.T) (*SessionRepository, string) {
	dir := t.TempDir()
	filePath := filepath.Join(dir, "sessions.json")
	// Ensure file doesn't exist initially
	os.Remove(filePath)
	repo := NewSessionRepository(filePath)
	return repo, filePath
}

func makeSession(id, userID string, expiresAt time.Time) entity.Session {
	now := time.Now()
	return entity.Session{
		ID:           id,
		UserID:       userID,
		UserAgent:    "test-agent",
		IPAddress:    "127.0.0.1",
		ExpiresAt:    expiresAt,
		CreatedAt:    now,
		LastActiveAt: now,
		IsValid:      true,
	}
}

func TestSessionRepository_Create(t *testing.T) {
	repo, _ := newTempSessionRepo(t)

	expires := time.Now().Add(1 * time.Hour)
	sess := makeSession("sess-1", "user-1", expires)

	err := repo.Create(&sess)
	require.NoError(t, err)

	// Retrieve
	got, err := repo.GetByID("sess-1")
	require.NoError(t, err)
	assert.Equal(t, "sess-1", got.ID)
	assert.Equal(t, "user-1", got.UserID)
	assert.True(t, got.IsValid)
	assert.WithinDuration(t, sess.CreatedAt, got.CreatedAt, time.Second)
	assert.WithinDuration(t, sess.LastActiveAt, got.LastActiveAt, time.Second)
}

func TestSessionRepository_Create_DuplicateID(t *testing.T) {
	repo, _ := newTempSessionRepo(t)

	expires := time.Now().Add(1 * time.Hour)
	sess1 := makeSession("sess-1", "user-1", expires)
	require.NoError(t, repo.Create(&sess1))

	sess2 := makeSession("sess-1", "user-2", expires)
	err := repo.Create(&sess2)
	assert.Error(t, err)
	assert.Contains(t, err.Error(), "session already exists")
}

func TestSessionRepository_GetByID_NotFound(t *testing.T) {
	repo, _ := newTempSessionRepo(t)

	_, err := repo.GetByID("nonexistent")
	assert.Error(t, err)
	assert.Contains(t, err.Error(), "session not found")
}

func TestSessionRepository_GetByUserID(t *testing.T) {
	repo, _ := newTempSessionRepo(t)

	expires := time.Now().Add(1 * time.Hour)
	s1 := makeSession("s1", "user-1", expires)
	s2 := makeSession("s2", "user-1", expires)
	s3 := makeSession("s3", "user-2", expires)
	require.NoError(t, repo.Create(&s1))
	require.NoError(t, repo.Create(&s2))
	require.NoError(t, repo.Create(&s3))

	// Get for user-1
	user1Sessions, err := repo.GetByUserID("user-1")
	require.NoError(t, err)
	assert.Len(t, user1Sessions, 2)
	var ids []string
	for _, s := range user1Sessions {
		ids = append(ids, s.ID)
	}
	assert.ElementsMatch(t, []string{"s1", "s2"}, ids)

	// User with no sessions
	noSessions, err := repo.GetByUserID("nonexistent")
	require.NoError(t, err)
	assert.Empty(t, noSessions)
}

func TestSessionRepository_Update(t *testing.T) {
	repo, _ := newTempSessionRepo(t)

	expires := time.Now().Add(1 * time.Hour)
	sess := makeSession("sess-1", "user-1", expires)
	require.NoError(t, repo.Create(&sess))

	// Update IsValid and LastActiveAt
	time.Sleep(10 * time.Millisecond) // ensure different timestamp
	sess.IsValid = false
	err := repo.Update(&sess)
	require.NoError(t, err)

	got, err := repo.GetByID("sess-1")
	require.NoError(t, err)
	assert.False(t, got.IsValid)
	assert.True(t, got.LastActiveAt.After(sess.LastActiveAt) || got.LastActiveAt.Equal(sess.LastActiveAt))
}

func TestSessionRepository_Update_NotFound(t *testing.T) {
	repo, _ := newTempSessionRepo(t)

	expires := time.Now().Add(1 * time.Hour)
	sess := makeSession("nonexistent", "user-1", expires)
	err := repo.Update(&sess)
	assert.Error(t, err)
	assert.Contains(t, err.Error(), "session not found")
}

func TestSessionRepository_Delete(t *testing.T) {
	repo, _ := newTempSessionRepo(t)

	expires := time.Now().Add(1 * time.Hour)
	sess := makeSession("sess-1", "user-1", expires)
	require.NoError(t, repo.Create(&sess))

	// Verify exists
	_, err := repo.GetByID("sess-1")
	require.NoError(t, err)

	// Delete
	err = repo.Delete("sess-1")
	require.NoError(t, err)

	// Verify deleted
	_, err = repo.GetByID("sess-1")
	assert.Error(t, err)

	// User index should also be cleared
	userSessions, _ := repo.GetByUserID("user-1")
	assert.Empty(t, userSessions)
}

func TestSessionRepository_Delete_NotFound(t *testing.T) {
	repo, _ := newTempSessionRepo(t)

	err := repo.Delete("nonexistent")
	assert.Error(t, err)
	assert.Contains(t, err.Error(), "session not found")
}

func TestSessionRepository_InvalidateUserSessions(t *testing.T) {
	repo, _ := newTempSessionRepo(t)

	expires := time.Now().Add(1 * time.Hour)
	s1 := makeSession("s1", "user-1", expires)
	s2 := makeSession("s2", "user-1", expires)
	s3 := makeSession("s3", "user-2", expires)
	require.NoError(t, repo.Create(&s1))
	require.NoError(t, repo.Create(&s2))
	require.NoError(t, repo.Create(&s3))

	// Invalidate user-1 sessions
	err := repo.InvalidateUserSessions("user-1")
	require.NoError(t, err)

	// Check
	user1Sessions, _ := repo.GetByUserID("user-1")
	for _, s := range user1Sessions {
		assert.False(t, s.IsValid)
	}
	// user-2 should still be valid
	user2Sessions, _ := repo.GetByUserID("user-2")
	for _, s := range user2Sessions {
		assert.True(t, s.IsValid)
	}
}

func TestSessionRepository_InvalidateUserSessions_NoSessions(t *testing.T) {
	repo, _ := newTempSessionRepo(t)

	err := repo.InvalidateUserSessions("user-none")
	require.NoError(t, err)
}

func TestSessionRepository_CleanExpiredSessions(t *testing.T) {
	repo, _ := newTempSessionRepo(t)

	now := time.Now()
	// Create sessions:
	// s1: expired yesterday
	// s2: expired in future (valid)
	// s3: expired now
	s1 := makeSession("s1", "user-1", now.Add(-1*time.Hour))
	s2 := makeSession("s2", "user-2", now.Add(1*time.Hour))
	s3 := makeSession("s3", "user-3", now)
	require.NoError(t, repo.Create(&s1))
	require.NoError(t, repo.Create(&s2))
	require.NoError(t, repo.Create(&s3))

	// Clean
	err := repo.CleanExpiredSessions()
	require.NoError(t, err)

	// s1 and s3 should be deleted; only s2 remains
	_, err = repo.GetByID("s1")
	assert.Error(t, err)
	_, err = repo.GetByID("s3")
	assert.Error(t, err)
	got, err := repo.GetByID("s2")
	require.NoError(t, err)
	assert.True(t, got.IsValid)
}

func TestSessionRepository_CleanExpiredSessions_IndexCleanup(t *testing.T) {
	repo, _ := newTempSessionRepo(t)

	now := time.Now()
	s1 := makeSession("s1", "user-1", now.Add(-1*time.Hour))
	s2 := makeSession("s2", "user-1", now.Add(1*time.Hour))
	require.NoError(t, repo.Create(&s1))
	require.NoError(t, repo.Create(&s2))

	// Clean
	err := repo.CleanExpiredSessions()
	require.NoError(t, err)

	// User index for user-1 should only contain s2
	sessions, _ := repo.GetByUserID("user-1")
	assert.Len(t, sessions, 1)
	assert.Equal(t, "s2", sessions[0].ID)
}

func TestSessionRepository_Persistence(t *testing.T) {
	dir := t.TempDir()
	filePath := filepath.Join(dir, "sessions.json")

	// First repo: create session
	repo1 := NewSessionRepository(filePath)
	expires := time.Now().Add(1 * time.Hour)
	sess := makeSession("persisted-sess", "user-1", expires)
	require.NoError(t, repo1.Create(&sess))

	// Second repo loads same file
	repo2 := NewSessionRepository(filePath)
	got, err := repo2.GetByID("persisted-sess")
	require.NoError(t, err)
	assert.Equal(t, sess.ID, got.ID)
	assert.Equal(t, sess.UserID, got.UserID)
}

func TestSessionRepository_MultipleOperations(t *testing.T) {
	repo, _ := newTempSessionRepo(t)

	now := time.Now()
	sessions := []entity.Session{
		makeSession("s1", "u1", now.Add(1*time.Hour)),
		makeSession("s2", "u1", now.Add(2*time.Hour)),
		makeSession("s3", "u2", now.Add(1*time.Hour)),
	}
	for i := range sessions {
		require.NoError(t, repo.Create(&sessions[i]))
	}

	// List for u1
	u1Sessions, _ := repo.GetByUserID("u1")
	assert.Len(t, u1Sessions, 2)

	// Delete one
	require.NoError(t, repo.Delete("s2"))

	// u1 now has 1
	u1Sessions, _ = repo.GetByUserID("u1")
	assert.Len(t, u1Sessions, 1)
	assert.Equal(t, "s1", u1Sessions[0].ID)
}
