package repository

import (
	"path/filepath"
	"testing"
	"time"

	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"

	"community.local/modules/keymanagement/entity"
)

func newRepo(t *testing.T) *KeyRepository {
	dir := t.TempDir()
	path := filepath.Join(dir, "keys.json")
	return NewKeyRepository(path)
}

func newNow() time.Time {
	return time.Now().Truncate(time.Microsecond)
}

// NewKeyRepository

func TestNewKeyRepository_Empty(t *testing.T) {
	r := newRepo(t)
	keys, _ := r.ListMasterKeys()
	assert.Empty(t, keys)
	// DEK not found
	_, err := r.GetDEK("nonexistent")
	assert.Error(t, err)
}

// MasterKey

func TestCreateMasterKey(t *testing.T) {
	r := newRepo(t)
	now := newNow()
	key := &entity.MasterKey{
		ID:        "mk-1",
		Name:      "MasterKey1",
		Version:   0, // will be set to 1
		Key:       "key-data",
		Algorithm: "aes-256-gcm",
	}
	require.NoError(t, r.CreateMasterKey(key))
	assert.Equal(t, 1, key.Version)
	assert.Equal(t, now.Format(time.RFC3339), key.CreatedAt.Format(time.RFC3339)) // close enough
	assert.False(t, key.IsActive)

	retrieved, err := r.GetMasterKey("mk-1")
	require.NoError(t, err)
	assert.Equal(t, key.ID, retrieved.ID)
	assert.Equal(t, key.Name, retrieved.Name)
	assert.Equal(t, 1, retrieved.Version)
}

func TestCreateMasterKey_Duplicate(t *testing.T) {
	r := newRepo(t)
	key := &entity.MasterKey{
		ID:     "mk-1",
		Name:   "MasterKey1",
		Key:    "key",
		Algorithm: "aes",
	}
	require.NoError(t, r.CreateMasterKey(key))
	err := r.CreateMasterKey(key)
	assert.Error(t, err)
}

func TestGetMasterKey_NotFound(t *testing.T) {
	r := newRepo(t)
	_, err := r.GetMasterKey("unknown")
	assert.Error(t, err)
}

func TestGetMasterKeyByName(t *testing.T) {
	r := newRepo(t)
	now := newNow()
	key := &entity.MasterKey{
		ID:        "mk-1",
		Name:      "my-master-key",
		Key:       "key",
		Algorithm: "aes",
		CreatedAt: now,
		Version:   1,
		IsActive:  false,
	}
	require.NoError(t, r.CreateMasterKey(key))

	retrieved, err := r.GetMasterKeyByName("my-master-key")
	require.NoError(t, err)
	assert.Equal(t, key.ID, retrieved.ID)

	_, err = r.GetMasterKeyByName("nonexistent")
	assert.Error(t, err)
}

func TestUpdateMasterKey(t *testing.T) {
	r := newRepo(t)
	now := newNow()
	key := &entity.MasterKey{
		ID:     "mk-1",
		Name:   "original",
		Key:    "key",
		Algorithm: "aes",
		CreatedAt: now,
		Version: 1,
		IsActive: false,
	}
	require.NoError(t, r.CreateMasterKey(key))

	key.Name = "updated"
	key.Algorithm = "chacha20"
	require.NoError(t, r.UpdateMasterKey(key))

	retrieved, _ := r.GetMasterKey("mk-1")
	assert.Equal(t, "updated", retrieved.Name)
	assert.Equal(t, "chacha20", retrieved.Algorithm)
	assert.Equal(t, 1, retrieved.Version) // version unchanged
	assert.Equal(t, now.Format(time.RFC3339), retrieved.CreatedAt.Format(time.RFC3339))
}

func TestUpdateMasterKey_NotFound(t *testing.T) {
	r := newRepo(t)
	key := &entity.MasterKey{
		ID:     "unknown",
		Name:   "name",
		Key:    "key",
		Algorithm: "aes",
	}
	err := r.UpdateMasterKey(key)
	assert.Error(t, err)
}

func TestListMasterKeys(t *testing.T) {
	r := newRepo(t)
	expected := []*entity.MasterKey{
		&entity.MasterKey{ID: "mk1", Name: "A", Key: "k1", Algorithm: "aes", Version: 1, IsActive: false},
		&entity.MasterKey{ID: "mk2", Name: "B", Key: "k2", Algorithm: "aes", Version: 1, IsActive: false},
	}
	for _, k := range expected {
		require.NoError(t, r.CreateMasterKey(k))
	}
	all, err := r.ListMasterKeys()
	require.NoError(t, err)
	assert.Len(t, all, 2)
	// Build map for lookup (values)
	valMap := make(map[string]entity.MasterKey)
	for _, k := range all {
		valMap[k.ID] = k
	}
	for _, exp := range expected {
		act, ok := valMap[exp.ID]
		assert.True(t, ok, "key %s not found", exp.ID)
		assert.Equal(t, exp.ID, act.ID)
		assert.Equal(t, exp.Name, act.Name)
		assert.Equal(t, exp.Key, act.Key)
		assert.Equal(t, exp.Algorithm, act.Algorithm)
		assert.Equal(t, exp.Version, act.Version)
		assert.Equal(t, exp.IsActive, act.IsActive)
	}
}

// DEK

func TestCreateDEK(t *testing.T) {
	r := newRepo(t)
	dek := &entity.DataEncryptionKey{
		ID:           "dek-1",
		Name:         "DEK1",
		EncryptedKey: "enc-key",
		Algorithm:    "aes-256-gcm",
		MasterKeyID:  "mk-1",
	}
	require.NoError(t, r.CreateDEK(dek))

	retrieved, err := r.GetDEK("dek-1")
	require.NoError(t, err)
	assert.Equal(t, dek.ID, retrieved.ID)
	assert.Equal(t, dek.Name, retrieved.Name)
}

func TestCreateDEK_Duplicate(t *testing.T) {
	r := newRepo(t)
	dek := &entity.DataEncryptionKey{
		ID:           "dek-1",
		Name:         "DEK1",
		EncryptedKey: "enc",
		Algorithm:    "aes",
		MasterKeyID:  "mk-1",
	}
	require.NoError(t, r.CreateDEK(dek))
	err := r.CreateDEK( dek)
	assert.Error(t, err)
}

func TestGetDEK_NotFound(t *testing.T) {
	r := newRepo(t)
	_, err := r.GetDEK("unknown")
	assert.Error(t, err)
}

func TestGetDEKByName(t *testing.T) {
	r := newRepo(t)
	now := newNow()
	dek := &entity.DataEncryptionKey{
		ID:           "dek-1",
		Name:         "my-dek",
		EncryptedKey: "enc",
		Algorithm:    "aes",
		MasterKeyID:  "mk-1",
		CreatedAt:    now,
	}
	require.NoError(t, r.CreateDEK(dek))

	retrieved, err := r.GetDEKByName("my-dek")
	require.NoError(t, err)
	assert.Equal(t, dek.ID, retrieved.ID)

	_, err = r.GetDEKByName("nonexistent")
	assert.Error(t, err)
}

func TestRetireDEK(t *testing.T) {
	r := newRepo(t)
	now := newNow()
	dek := &entity.DataEncryptionKey{
		ID:           "dek-1",
		Name:         "DEK1",
		EncryptedKey: "enc",
		Algorithm:    "aes",
		MasterKeyID:  "mk-1",
		CreatedAt:    now,
	}
	require.NoError(t, r.CreateDEK(dek))

	require.NoError(t, r.RetireDEK("dek-1"))
	retrieved, _ := r.GetDEK("dek-1")
	assert.NotNil(t, retrieved.RetiredAt)
}

func TestRetireDEK_NotFound(t *testing.T) {
	r := newRepo(t)
	err := r.RetireDEK("unknown")
	assert.Error(t, err)
}

// Key Versions

func TestAddKeyVersion(t *testing.T) {
	r := newRepo(t)
	now := newNow()
	mk := &entity.MasterKey{
		ID:     "mk-1",
		Name:   "key",
		Key:    "keydata",
		Algorithm: "aes",
		CreatedAt: now,
		Version: 1,
		IsActive: false,
	}
	require.NoError(t, r.CreateMasterKey(mk))

	version := entity.KeyVersion{
		ID:            "mk-1-v2",
		MasterKeyID:   "mk-1",
		Version:       2,
		EncryptedKey:  "newkey",
		CreatedAt:     now,
		EncryptedByID: "mk-1",
	}
	require.NoError(t, r.AddKeyVersion("mk-1", version))

	updated, _ := r.GetMasterKey("mk-1")
	assert.Equal(t, 2, updated.Version)

	versions, err := r.GetKeyVersions("mk-1")
	require.NoError(t, err)
	assert.Len(t, versions, 2) // initial version from CreateMasterKey and added one
	// The versions are in order they were added
	assert.Equal(t, 1, versions[0].Version)
	assert.Equal(t, 2, versions[1].Version)
}

func TestAddKeyVersion_NotFound(t *testing.T) {
	r := newRepo(t)
	version := entity.KeyVersion{Version: 2}
	err := r.AddKeyVersion("nonexistent", version)
	assert.Error(t, err)
}

func TestGetKeyVersions_Empty(t *testing.T) {
	r := newRepo(t)
	versions, err := r.GetKeyVersions("nonexistent")
	require.NoError(t, err)
	assert.Empty(t, versions)
}

// Audit Logs

func TestAddAuditLog(t *testing.T) {
	r := newRepo(t)
	log := entity.KeyAuditLog{
		Operation: "create",
		KeyID:     "mk-1",
		KeyType:   "master",
		UserID:    "u1",
		IPAddress: "127.0.0.1",
		Success:   true,
	}
	require.NoError(t, r.AddAuditLog(log))

	logs, err := r.GetAuditLogs("mk-1", 1)
	require.NoError(t, err)
	require.Len(t, logs, 1)
	saved := logs[0]
	assert.Equal(t, "create", saved.Operation)
	assert.Equal(t, "mk-1", saved.KeyID)
	assert.Equal(t, "master", saved.KeyType)
	assert.Equal(t, "u1", saved.UserID)
	assert.Equal(t, "127.0.0.1", saved.IPAddress)
	assert.True(t, saved.Success)
	assert.NotEmpty(t, saved.ID)
	assert.True(t, !saved.CreatedAt.IsZero())
}

func TestGetAuditLogs_WithLimitAndOrder(t *testing.T) {
	r := newRepo(t)
	now := time.Now().Truncate(time.Microsecond)
	base := now
	for i := 0; i < 5; i++ {
		log := entity.KeyAuditLog{
			Operation: "op",
			KeyID:     "mk-1",
			KeyType:   "master",
			UserID:    "u1",
			CreatedAt: base.Add(time.Duration(i) * time.Second),
		}
		require.NoError(t, r.AddAuditLog(log))
	}
	// fetch 3 most recent
	logs, err := r.GetAuditLogs("mk-1", 3)
	require.NoError(t, err)
	assert.Len(t, logs, 3)
	// Should be in descending order (newest first)
	assert.Equal(t, "log_5", logs[0].ID)
	assert.Equal(t, "log_4", logs[1].ID)
	assert.Equal(t, "log_3", logs[2].ID)
}

func TestGetAuditLogs_FilterByKeyID(t *testing.T) {
	r := newRepo(t)
	now := time.Now().Truncate(time.Microsecond)
	base := now
	// create 2 for key1, 2 for key2
	for i := 0; i < 2; i++ {
		log1 := entity.KeyAuditLog{Operation: "op", KeyID: "mk-1", UserID: "u1", CreatedAt: base.Add(time.Duration(i)*time.Second)}
		require.NoError(t, r.AddAuditLog(log1))
	}
	for i := 0; i < 2; i++ {
		log2 := entity.KeyAuditLog{Operation: "op", KeyID: "mk-2", UserID: "u2", CreatedAt: base.Add(time.Duration(i+2)*time.Second)}
		require.NoError(t, r.AddAuditLog(log2))
	}
	logs, err := r.GetAuditLogs("mk-1", 10)
	require.NoError(t, err)
	assert.Len(t, logs, 2)
	for _, l := range logs {
		assert.Equal(t, "mk-1", l.KeyID)
	}
}

// ActivateMasterKey

func TestActivateMasterKey(t *testing.T) {
	r := newRepo(t)
	now := newNow()
	key1 := &entity.MasterKey{
		ID:       "mk-1",
		Name:     "key1",
		Key:      "k1",
		Algorithm: "aes",
		CreatedAt: now,
		Version:  1,
		IsActive: false,
	}
	key2 := &entity.MasterKey{
		ID:       "mk-2",
		Name:     "key2",
		Key:      "k2",
		Algorithm: "aes",
		CreatedAt: now,
		Version:  1,
		IsActive: false,
	}
	require.NoError(t, r.CreateMasterKey(key1))
	require.NoError(t, r.CreateMasterKey(key2))

	require.NoError(t, r.ActivateMasterKey("mk-1"))
	activeKeys, _ := r.ListMasterKeys()
	assert.Len(t, activeKeys, 2)
	// Check active status
	check1, _ := r.GetMasterKey("mk-1")
	check2, _ := r.GetMasterKey("mk-2")
	assert.True(t, check1.IsActive)
	assert.False(t, check2.IsActive)

	// Activate mk-2 should deactivate mk-1
	require.NoError(t, r.ActivateMasterKey("mk-2"))
	check1, _ = r.GetMasterKey("mk-1")
	check2, _ = r.GetMasterKey("mk-2")
	assert.False(t, check1.IsActive)
	assert.True(t, check2.IsActive)
}

func TestActivateMasterKey_NotFound(t *testing.T) {
	r := newRepo(t)
	err := r.ActivateMasterKey("nonexistent")
	assert.Error(t, err)
}

// Persistence

func TestPersistence(t *testing.T) {
	dir := t.TempDir()
	path := filepath.Join(dir, "keys.json")

	// First repo: create keys, DEK, logs
	r1 := NewKeyRepository(path)
	now := newNow()
	mk := &entity.MasterKey{
		ID:       "persist-mk",
		Name:     "persist-mk",
		Key:      "key",
		Algorithm: "aes",
		Version:  0,
		IsActive: false,
	}
	require.NoError(t, r1.CreateMasterKey(mk))
	dek := &entity.DataEncryptionKey{
		ID:           "persist-dek",
		Name:         "persist-dek",
		EncryptedKey: "enc",
		Algorithm:    "aes",
		MasterKeyID:  "persist-mk",
	}
	require.NoError(t, r1.CreateDEK(dek))
	log := entity.KeyAuditLog{
		Operation: "test",
		KeyID:     "persist-mk",
		KeyType:   "master",
		UserID:    "tester",
		IPAddress: "127.0.0.1",
		Success:   true,
	}
	require.NoError(t, r1.AddAuditLog(log))
	// Add version
	ver := entity.KeyVersion{
		ID:            "persist-mk-v2",
		MasterKeyID:   "persist-mk",
		Version:       2,
		EncryptedKey:  "newkey",
		CreatedAt:     now,
		EncryptedByID: "persist-mk",
	}
	require.NoError(t, r1.AddKeyVersion("persist-mk", ver))

	// Second repo: same file
	r2 := NewKeyRepository(path)
	mk2, err := r2.GetMasterKey("persist-mk")
	require.NoError(t, err)
	assert.Equal(t, "persist-mk", mk2.ID)
	assert.Equal(t, "persist-mk", mk2.Name)
	assert.Equal(t, 2, mk2.Version)
	assert.False(t, mk2.IsActive)

	dek2, err := r2.GetDEK("persist-dek")
	require.NoError(t, err)
	assert.Equal(t, "persist-dek", dek2.ID)

	logs, err := r2.GetAuditLogs("persist-mk", 10)
	require.NoError(t, err)
	assert.Len(t, logs, 1)
	assert.Equal(t, "test", logs[0].Operation)

	versions, err := r2.GetKeyVersions("persist-mk")
	require.NoError(t, err)
	assert.Len(t, versions, 2)
	assert.Equal(t, 2, versions[1].Version)
}
