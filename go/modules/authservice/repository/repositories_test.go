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

func newTempRepo(t *testing.T) (*UserRepository, string) {
	dir := t.TempDir()
	filePath := filepath.Join(dir, "users.json")
	// Ensure file doesn't exist initially
	os.Remove(filePath)
	repo := NewUserRepository(filePath)
	return repo, filePath
}

func nowTrunc() time.Time {
	return time.Now().Truncate(time.Microsecond)
}

func TestUserRepository_Create(t *testing.T) {
	repo, _ := newTempRepo(t)

	user := &entity.User{
		ID:       "user-1",
		Email:    "test@example.com",
		Username: "tester",
		Role:     "member",
	}
	err := repo.Create(user)
	require.NoError(t, err)

	// timestamps set
	assert.NotZero(t, user.CreatedAt)
	assert.NotZero(t, user.UpdatedAt)

	// retrieve by ID
	got, err := repo.GetByID("user-1")
	require.NoError(t, err)
	assert.Equal(t, user, got)

	// retrieve by email
	gotEmail, err := repo.GetByEmail("test@example.com")
	require.NoError(t, err)
	assert.Equal(t, user, gotEmail)
}

func TestUserRepository_Create_DuplicateID(t *testing.T) {
	repo, _ := newTempRepo(t)

	user := &entity.User{
		ID:       "user-1",
		Email:    "test@example.com",
		Username: "tester",
		Role:     "member",
	}
	require.NoError(t, repo.Create(user))

	user2 := &entity.User{
		ID:       "user-1",
		Email:    "another@example.com",
		Username: "tester2",
		Role:     "member",
	}
	err := repo.Create(user2)
	assert.Error(t, err)
	assert.Contains(t, err.Error(), "user already exists")
}

func TestUserRepository_Create_DuplicateEmail(t *testing.T) {
	repo, _ := newTempRepo(t)

	user := &entity.User{
		ID:       "user-1",
		Email:    "test@example.com",
		Username: "tester",
		Role:     "member",
	}
	require.NoError(t, repo.Create(user))

	user2 := &entity.User{
		ID:       "user-2",
		Email:    "test@example.com",
		Username: "tester2",
		Role:     "member",
	}
	err := repo.Create(user2)
	assert.Error(t, err)
	assert.Contains(t, err.Error(), "email already registered")
}

func TestUserRepository_GetByID_NotFound(t *testing.T) {
	repo, _ := newTempRepo(t)

	_, err := repo.GetByID("nonexistent")
	assert.Error(t, err)
	assert.Contains(t, err.Error(), "user not found")
}

func TestUserRepository_GetByEmail_NotFound(t *testing.T) {
	repo, _ := newTempRepo(t)

	_, err := repo.GetByEmail("unknown@example.com")
	assert.Error(t, err)
	assert.Contains(t, err.Error(), "user not found")
}

func TestUserRepository_Update(t *testing.T) {
	repo, _ := newTempRepo(t)

	createdAt := nowTrunc()
	user := &entity.User{
		ID:          "user-1",
		Email:       "test@example.com",
		Username:    "tester",
		Role:        "member",
		CreatedAt:   createdAt,
		UpdatedAt:   createdAt,
		IsActive:    true,
		IsMFAEnabled: false,
	}
	require.NoError(t, repo.Create(user))

	// Update role and username
	updated := *user
	updated.Role = "admin"
	updated.Username = "updated_tester"
	// UpdateEmail: change email
	updatedEmail := "new@example.com"
	updated.Email = updatedEmail

	err := repo.Update(&updated)
	require.NoError(t, err)

	// UpdatedAt should be newer
	assert.True(t, updated.UpdatedAt.After(createdAt) || updated.UpdatedAt.Equal(createdAt), "UpdatedAt should be updated")

	// Retrieve and verify
	got, err := repo.GetByID("user-1")
	require.NoError(t, err)
	assert.Equal(t, "admin", got.Role)
	assert.Equal(t, "updated_tester", got.Username)
	assert.Equal(t, updatedEmail, got.Email)

	// Email index should point to user ID
	gotByEmail, err := repo.GetByEmail(updatedEmail)
	require.NoError(t, err)
	assert.Equal(t, "user-1", gotByEmail.ID)

	// Old email no longer works
	_, err = repo.GetByEmail("test@example.com")
	assert.Error(t, err)
}

func TestUserRepository_Update_NotFound(t *testing.T) {
	repo, _ := newTempRepo(t)

	user := &entity.User{
		ID:    "non-existent",
		Email: "no@example.com",
	}
	err := repo.Update(user)
	assert.Error(t, err)
	assert.Contains(t, err.Error(), "user not found")
}

func TestUserRepository_Delete(t *testing.T) {
	repo, _ := newTempRepo(t)

	user := &entity.User{
		ID:       "user-1",
		Email:    "test@example.com",
		Username: "tester",
		Role:     "member",
	}
	require.NoError(t, repo.Create(user))

	// Verify exists
	_, err := repo.GetByID("user-1")
	require.NoError(t, err)

	// Delete
	err = repo.Delete("user-1")
	require.NoError(t, err)

	// Verify deleted
	_, err = repo.GetByID("user-1")
	assert.Error(t, err)
	_, err = repo.GetByEmail("test@example.com")
	assert.Error(t, err)
}

func TestUserRepository_Delete_NotFound(t *testing.T) {
	repo, _ := newTempRepo(t)

	err := repo.Delete("nonexistent")
	assert.Error(t, err)
	assert.Contains(t, err.Error(), "user not found")
}

func TestUserRepository_List(t *testing.T) {
	repo, _ := newTempRepo(t)

	users := []*entity.User{
		{ID: "u1", Email: "a@example.com", Username: "a", Role: "user"},
		{ID: "u2", Email: "b@example.com", Username: "b", Role: "admin"},
		{ID: "u3", Email: "c@example.com", Username: "c", Role: "user"},
	}
	for _, u := range users {
		require.NoError(t, repo.Create(u))
	}

	all, err := repo.List()
	require.NoError(t, err)
	// List returns values, compare IDs
	assert.Len(t, all, 3)
	var ids []string
	for _, u := range all {
		ids = append(ids, u.ID)
	}
	assert.ElementsMatch(t, []string{"u1", "u2", "u3"}, ids)
}

func TestUserRepository_Persistence(t *testing.T) {
	dir := t.TempDir()
	filePath := filepath.Join(dir, "users.json")

	// First repo: create user
	repo1 := NewUserRepository(filePath)
	user := &entity.User{
		ID:       "persisted-user",
		Email:    "persisted@example.com",
		Username: "persisted",
		Role:     "member",
	}
	require.NoError(t, repo1.Create(user))

	// Close repo1 (no explicit close, but we will let it be GC'd; but file is written)
	// Create a new repo that loads from same file
	repo2 := NewUserRepository(filePath)
	got, err := repo2.GetByID("persisted-user")
	require.NoError(t, err)
	assert.Equal(t, user.ID, got.ID)
	assert.Equal(t, user.Email, got.Email)
	assert.Equal(t, user.Username, got.Username)
}

func TestUserRepository_MultipleOperations(t *testing.T) {
	repo, _ := newTempRepo(t)

	// Create several users
	now := nowTrunc()
	users := []*entity.User{
		{ID: "u1", Email: "u1@example.com", Username: "user1", Role: "user", CreatedAt: now, UpdatedAt: now},
		{ID: "u2", Email: "u2@example.com", Username: "user2", Role: "user", CreatedAt: now, UpdatedAt: now},
		{ID: "u3", Email: "u3@example.com", Username: "user3", Role: "admin", CreatedAt: now, UpdatedAt: now},
	}
	for _, u := range users {
		require.NoError(t, repo.Create(u))
	}

	// List should contain all
	all, err := repo.List()
	require.NoError(t, err)
	assert.Len(t, all, 3)
	var ids []string
	for _, u := range all {
		ids = append(ids, u.ID)
	}
	assert.ElementsMatch(t, []string{"u1", "u2", "u3"}, ids)

	// Delete one
	require.NoError(t, repo.Delete("u2"))

	// List again should have 2
	all, err = repo.List()
	require.NoError(t, err)
	assert.Len(t, all, 2)
	// Ensure u2 not present
	for _, u := range all {
		assert.NotEqual(t, "u2", u.ID)
	}
}
