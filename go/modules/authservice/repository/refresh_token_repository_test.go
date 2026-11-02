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

func newTempRefreshRepo(t *testing.T) (*RefreshTokenRepository, string) {
	dir := t.TempDir()
	filePath := filepath.Join(dir, "refresh_tokens.json")
	os.Remove(filePath)
	repo := NewRefreshTokenRepository(filePath)
	return repo, filePath
}

func makeToken(tokenStr, userID string, expiresAt time.Time) entity.RefreshToken {
	now := time.Now()
	return entity.RefreshToken{
		Token:     tokenStr,
		UserID:    userID,
		ExpiresAt: expiresAt,
		CreatedAt: now,
		IsRevoked: false,
	}
}

func TestRefreshTokenRepository_Create(t *testing.T) {
	repo, _ := newTempRefreshRepo(t)

	expires := time.Now().Add(1 * time.Hour)
	token := makeToken("token-1", "user-1", expires)

	err := repo.Create(&token)
	require.NoError(t, err)

	// Retrieve
	got, err := repo.GetByToken("token-1")
	require.NoError(t, err)
	assert.Equal(t, "token-1", got.Token)
	assert.Equal(t, "user-1", got.UserID)
	assert.False(t, got.IsRevoked)
	assert.WithinDuration(t, token.CreatedAt, got.CreatedAt, time.Second)
}

func TestRefreshTokenRepository_Create_DuplicateToken(t *testing.T) {
	repo, _ := newTempRefreshRepo(t)

	expires := time.Now().Add(1 * time.Hour)
	token1 := makeToken("token-1", "user-1", expires)
	require.NoError(t, repo.Create(&token1))

	token2 := makeToken("token-1", "user-2", expires)
	err := repo.Create(&token2)
	assert.Error(t, err)
	assert.Contains(t, err.Error(), "refresh token already exists")
}

func TestRefreshTokenRepository_GetByToken_NotFound(t *testing.T) {
	repo, _ := newTempRefreshRepo(t)

	_, err := repo.GetByToken("nonexistent")
	assert.Error(t, err)
	assert.Contains(t, err.Error(), "refresh token not found")
}

func TestRefreshTokenRepository_GetByUserID(t *testing.T) {
	repo, _ := newTempRefreshRepo(t)

	expires := time.Now().Add(1 * time.Hour)
	t1 := makeToken("t1", "user-1", expires)
	t2 := makeToken("t2", "user-1", expires)
	t3 := makeToken("t3", "user-2", expires)
	require.NoError(t, repo.Create(&t1))
	require.NoError(t, repo.Create(&t2))
	require.NoError(t, repo.Create(&t3))

	// Get for user-1
	tokens, err := repo.GetByUserID("user-1")
	require.NoError(t, err)
	assert.Len(t, tokens, 2)
	var tokenStrs []string
	for _, tk := range tokens {
		tokenStrs = append(tokenStrs, tk.Token)
	}
	assert.ElementsMatch(t, []string{"t1", "t2"}, tokenStrs)

	// User with no tokens
	none, err := repo.GetByUserID("nonexistent")
	require.NoError(t, err)
	assert.Empty(t, none)
}

func TestRefreshTokenRepository_Revoke(t *testing.T) {
	repo, _ := newTempRefreshRepo(t)

	expires := time.Now().Add(1 * time.Hour)
	token := makeToken("t1", "user-1", expires)
	require.NoError(t, repo.Create(&token))

	// Revoke
	err := repo.Revoke("t1")
	require.NoError(t, err)

	got, err := repo.GetByToken("t1")
	require.NoError(t, err)
	assert.True(t, got.IsRevoked)
	assert.NotNil(t, got.RevokedAt)
}

func TestRefreshTokenRepository_Revoke_NotFound(t *testing.T) {
	repo, _ := newTempRefreshRepo(t)

	err := repo.Revoke("nonexistent")
	assert.Error(t, err)
	assert.Contains(t, err.Error(), "refresh token not found")
}

func TestRefreshTokenRepository_RevokeAllForUser(t *testing.T) {
	repo, _ := newTempRefreshRepo(t)

	expires := time.Now().Add(1 * time.Hour)
	t1 := makeToken("t1", "user-1", expires)
	t2 := makeToken("t2", "user-1", expires)
	t3 := makeToken("t3", "user-2", expires)
	require.NoError(t, repo.Create(&t1))
	require.NoError(t, repo.Create(&t2))
	require.NoError(t, repo.Create(&t3))

	// Revoke all for user-1
	err := repo.RevokeAllForUser("user-1")
	require.NoError(t, err)

	// Check all revoked
	tokens1, _ := repo.GetByUserID("user-1")
	for _, tk := range tokens1 {
		assert.True(t, tk.IsRevoked)
	}
	// user-2 unchanged
	tokens2, _ := repo.GetByUserID("user-2")
	for _, tk := range tokens2 {
		assert.False(t, tk.IsRevoked)
	}
}

func TestRefreshTokenRepository_RevokeAllForUser_NoSessions(t *testing.T) {
	repo, _ := newTempRefreshRepo(t)

	// No tokens for user
	err := repo.RevokeAllForUser("user-none")
	require.NoError(t, err)
}

func TestRefreshTokenRepository_CleanExpiredTokens(t *testing.T) {
	repo, _ := newTempRefreshRepo(t)

	now := time.Now()
	// Expired token
	t1 := makeToken("t1", "user-1", now.Add(-1*time.Hour))
	// Valid token
	t2 := makeToken("t2", "user-1", now.Add(1*time.Hour))
	// Revoked but not expired (should be removed as well per code)
	t3 := makeToken("t3", "user-2", now.Add(1*time.Hour))
	t3.IsRevoked = true
	require.NoError(t, repo.Create(&t1))
	require.NoError(t, repo.Create(&t2))
	require.NoError(t, repo.Create(&t3))

	err := repo.CleanExpiredTokens()
	require.NoError(t, err)

	// t1 and t3 should be deleted
	_, err = repo.GetByToken("t1")
	assert.Error(t, err)
	_, err = repo.GetByToken("t3")
	assert.Error(t, err)
	// t2 still exists
	got, err := repo.GetByToken("t2")
	require.NoError(t, err)
	assert.False(t, got.IsRevoked)
}

func TestRefreshTokenRepository_CleanExpiredTokens_IndexCleanup(t *testing.T) {
	repo, _ := newTempRefreshRepo(t)

	now := time.Now()
	t1 := makeToken("t1", "user-1", now.Add(-1*time.Hour))
	t2 := makeToken("t2", "user-1", now.Add(1*time.Hour))
	require.NoError(t, repo.Create(&t1))
	require.NoError(t, repo.Create(&t2))

	err := repo.CleanExpiredTokens()
	require.NoError(t, err)

	// User index for user-1 should only contain t2
	tokens, _ := repo.GetByUserID("user-1")
	assert.Len(t, tokens, 1)
	assert.Equal(t, "t2", tokens[0].Token)
}

func TestRefreshTokenRepository_Persistence(t *testing.T) {
	dir := t.TempDir()
	filePath := filepath.Join(dir, "refresh_tokens.json")

	// First repo: create token
	repo1 := NewRefreshTokenRepository(filePath)
	expires := time.Now().Add(1 * time.Hour)
	token := makeToken("persisted-token", "user-1", expires)
	require.NoError(t, repo1.Create(&token))

	// Second repo loads same file
	repo2 := NewRefreshTokenRepository(filePath)
	got, err := repo2.GetByToken("persisted-token")
	require.NoError(t, err)
	assert.Equal(t, token.Token, got.Token)
	assert.Equal(t, token.UserID, got.UserID)
}

func TestRefreshTokenRepository_MultipleOperations(t *testing.T) {
	repo, _ := newTempRefreshRepo(t)

	now := time.Now()
	tokens := []entity.RefreshToken{
		makeToken("t1", "u1", now.Add(1*time.Hour)),
		makeToken("t2", "u1", now.Add(2*time.Hour)),
		makeToken("t3", "u2", now.Add(1*time.Hour)),
	}
	for i := range tokens {
		require.NoError(t, repo.Create(&tokens[i]))
	}

	// List for u1
	u1Tokens, _ := repo.GetByUserID("u1")
	assert.Len(t, u1Tokens, 2)

	// Revoke one
	require.NoError(t, repo.Revoke("t1"))

	// u1Tokens should still contain both but revoked flag set
	u1Tokens2, _ := repo.GetByUserID("u1")
	for _, tk := range u1Tokens2 {
		if tk.Token == "t1" {
			assert.True(t, tk.IsRevoked)
		} else {
			assert.False(t, tk.IsRevoked)
		}
	}

	// CleanExpiredTokens removes revoked? Actually code removes revoked regardless of expiry. So not needed.

	// Delete via revoke all? Not needed.
}
