package repository

import (
	"context"
	"testing"
	"time"

	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"

	"community.local/modules/realtimesync/entity"
)

func newNow() time.Time {
	return time.Now().Truncate(time.Microsecond)
}

// SyncSessionRepository tests

func TestInMemorySyncSessionRepository_SaveAndGet(t *testing.T) {
	repo := NewInMemorySyncSessionRepository()
	now := newNow()
	sess := &entity.SyncSession{
		ID:        "sess-1",
		UserID:    "u1",
		OwnerID:   "u1",
		Devices:   []entity.Device{{DeviceID: "d1", DeviceType: "mobile", Online: true, LastSeen: now}},
		Items:     []string{"item1"},
		CreatedAt: now,
		EndedAt:   nil,
	}
	require.NoError(t, repo.Save(context.Background(), sess))
	retrieved, err := repo.GetByID(context.Background(), "sess-1")
	require.NoError(t, err)
	assert.Equal(t, sess, retrieved)
}

func TestInMemorySyncSessionRepository_GetByUserID(t *testing.T) {
	repo := NewInMemorySyncSessionRepository()
	now := newNow()
	sess := &entity.SyncSession{
		ID:        "sess-1",
		UserID:    "u1",
		OwnerID:   "u1",
		Devices:   nil,
		Items:     []string{},
		CreatedAt: now,
		EndedAt:   nil,
	}
	require.NoError(t, repo.Save(context.Background(), sess))
	sessions, err := repo.GetByUserID(context.Background(), "u1")
	require.NoError(t, err)
	assert.Len(t, sessions, 1)
	assert.Equal(t, sess, sessions[0])
}

func TestInMemorySyncSessionRepository_GetByUserID_NotFound(t *testing.T) {
	repo := NewInMemorySyncSessionRepository()
	sessions, err := repo.GetByUserID(context.Background(), "unknown")
	require.NoError(t, err)
	assert.Nil(t, sessions)
}

func TestInMemorySyncSessionRepository_GetActiveByUserID(t *testing.T) {
	repo := NewInMemorySyncSessionRepository()
	now := newNow()
	active := &entity.SyncSession{
		ID:        "active",
		UserID:    "u1",
		OwnerID:   "u1",
		CreatedAt: now,
		EndedAt:   nil,
	}
	endedTime := now.Add(-time.Hour)
	ended := &entity.SyncSession{
		ID:        "ended",
		UserID:    "u1",
		OwnerID:   "u1",
		CreatedAt: now,
		EndedAt:   &endedTime,
	}
	require.NoError(t, repo.Save(context.Background(), active))
	require.NoError(t, repo.Save(context.Background(), ended))
	activeSessions, err := repo.GetActiveByUserID(context.Background(), "u1")
	require.NoError(t, err)
	assert.Len(t, activeSessions, 1)
	assert.Equal(t, "active", activeSessions[0].ID)
}

func TestInMemorySyncSessionRepository_Delete(t *testing.T) {
	repo := NewInMemorySyncSessionRepository()
	now := newNow()
	sess := &entity.SyncSession{
		ID:        "sess-1",
		UserID:    "u1",
		OwnerID:   "u1",
		CreatedAt: now,
		EndedAt:   nil,
	}
	require.NoError(t, repo.Save(context.Background(), sess))
	require.NoError(t, repo.Delete(context.Background(), "sess-1"))
	retrieved, err := repo.GetByID(context.Background(), "sess-1")
	require.NoError(t, err)
	assert.Nil(t, retrieved)
	// Check index cleanup: GetByUserID returns nil
	sessions, _ := repo.GetByUserID(context.Background(), "u1")
	assert.Empty(t, sessions)
}

func TestInMemorySyncSessionRepository_ListActive(t *testing.T) {
	repo := NewInMemorySyncSessionRepository()
	now := newNow()
	active1 := &entity.SyncSession{ID: "a1", UserID: "u1", OwnerID: "u1", CreatedAt: now, EndedAt: nil}
	active2 := &entity.SyncSession{ID: "a2", UserID: "u2", OwnerID: "u2", CreatedAt: now, EndedAt: nil}
	endedTime := now.Add(-time.Hour)
	ended := &entity.SyncSession{ID: "e1", UserID: "u1", OwnerID: "u1", CreatedAt: now, EndedAt: &endedTime}
	require.NoError(t, repo.Save(context.Background(), active1))
	require.NoError(t, repo.Save(context.Background(), active2))
	require.NoError(t, repo.Save(context.Background(), ended))
	actives, err := repo.ListActive(context.Background())
	require.NoError(t, err)
	assert.Len(t, actives, 2)
	ids := []string{actives[0].ID, actives[1].ID}
	assert.ElementsMatch(t, []string{"a1", "a2"}, ids)
}

// SyncItemRepository tests

func TestInMemorySyncItemRepository_SaveAndGet(t *testing.T) {
	repo := NewInMemorySyncItemRepository()
	now := newNow()
	item := &entity.SyncItem{
		ID:        "item-1",
		SessionID: "sess-1",
		Type:      "contact",
		Data:      map[string]interface{}{"name": "Alice"},
		Version:   1,
		UpdatedAt: now,
	}
	require.NoError(t, repo.Save(context.Background(), item))
	retrieved, err := repo.GetByID(context.Background(), "item-1")
	require.NoError(t, err)
	assert.Equal(t, item, retrieved)
}

func TestInMemorySyncItemRepository_GetBySessionID(t *testing.T) {
	repo := NewInMemorySyncItemRepository()
	now := newNow()
	item := &entity.SyncItem{
		ID:        "i1",
		SessionID: "s1",
		Type:      "type1",
		Data:      map[string]interface{}{"x": 1},
		Version:   1,
		UpdatedAt: now,
	}
	require.NoError(t, repo.Save(context.Background(), item))
	items, err := repo.GetBySessionID(context.Background(), "s1")
	require.NoError(t, err)
	assert.Len(t, items, 1)
	assert.Equal(t, item, items[0])
}

func TestInMemorySyncItemRepository_GetByType(t *testing.T) {
	repo := NewInMemorySyncItemRepository()
	now := newNow()
	item1 := &entity.SyncItem{ID: "i1", SessionID: "s1", Type: "contact", Data: map[string]interface{}{"x": 1}, Version: 1, UpdatedAt: now}
	item2 := &entity.SyncItem{ID: "i2", SessionID: "s2", Type: "contact", Data: map[string]interface{}{"y": 2}, Version: 1, UpdatedAt: now}
	item3 := &entity.SyncItem{ID: "i3", SessionID: "s3", Type: "event", Data: map[string]interface{}{"z": 3}, Version: 1, UpdatedAt: now}
	for _, it := range []*entity.SyncItem{item1, item2, item3} {
		require.NoError(t, repo.Save(context.Background(), it))
	}
	contacts, err := repo.GetByType(context.Background(), "contact")
	require.NoError(t, err)
	assert.Len(t, contacts, 2)
	ids := []string{contacts[0].ID, contacts[1].ID}
	assert.ElementsMatch(t, []string{"i1", "i2"}, ids)
}

func TestInMemorySyncItemRepository_Delete(t *testing.T) {
	repo := NewInMemorySyncItemRepository()
	now := newNow()
	item := &entity.SyncItem{
		ID:        "i1",
		SessionID: "s1",
		Type:      "t",
		Data:      map[string]interface{}{"a": 1},
		Version:   1,
		UpdatedAt: now,
	}
	require.NoError(t, repo.Save(context.Background(), item))
	require.NoError(t, repo.Delete(context.Background(), "i1"))
	retrieved, err := repo.GetByID(context.Background(), "i1")
	require.NoError(t, err)
	assert.Nil(t, retrieved)
	// Check indexes cleared
	bySession, _ := repo.GetBySessionID(context.Background(), "s1")
	assert.Empty(t, bySession)
	byType, _ := repo.GetByType(context.Background(), "t")
	assert.Empty(t, byType)
}

func TestInMemorySyncItemRepository_UpdateVersion(t *testing.T) {
	repo := NewInMemorySyncItemRepository()
	now := newNow()
	item := &entity.SyncItem{
		ID:        "i1",
		SessionID: "s1",
		Type:      "t",
		Data:      map[string]interface{}{"a": 1},
		Version:   1,
		UpdatedAt: now,
	}
	require.NoError(t, repo.Save(context.Background(), item))
	updatedBy := "user2"
	require.NoError(t, repo.UpdateVersion(context.Background(), "i1", 2, updatedBy))
	updated, _ := repo.GetByID(context.Background(), "i1")
	assert.Equal(t, 2, updated.Version)
	assert.NotNil(t, updated.UpdatedBy)
	assert.Equal(t, updatedBy, *updated.UpdatedBy)
	assert.True(t, updated.UpdatedAt.After(now))
}

// PendingChangeRepository tests

func TestInMemoryPendingChangeRepository_SaveAndGet(t *testing.T) {
	repo := NewInMemoryPendingChangeRepository()
	now := newNow()
	change := &entity.PendingChange{
		ID:        "change-1",
		SessionID: "sess-1",
		ItemID:    "item-1",
		Data:      map[string]interface{}{"field": "value"},
		Timestamp: now,
	}
	require.NoError(t, repo.Save(context.Background(), change))
	retrieved, err := repo.GetByID(context.Background(), "change-1")
	require.NoError(t, err)
	assert.Equal(t, change, retrieved)
}

func TestInMemoryPendingChangeRepository_GetBySessionID(t *testing.T) {
	repo := NewInMemoryPendingChangeRepository()
	now := newNow()
	change := &entity.PendingChange{
		ID:        "c1",
		SessionID: "s1",
		ItemID:    "i1",
		Data:      map[string]interface{}{"a": 1},
		Timestamp: now,
	}
	require.NoError(t, repo.Save(context.Background(), change))
	changes, err := repo.GetBySessionID(context.Background(), "s1")
	require.NoError(t, err)
	assert.Len(t, changes, 1)
	assert.Equal(t, change, changes[0])
}

func TestInMemoryPendingChangeRepository_GetByItemID(t *testing.T) {
	repo := NewInMemoryPendingChangeRepository()
	now := newNow()
	change := &entity.PendingChange{
		ID:        "c1",
		SessionID: "s1",
		ItemID:    "i1",
		Data:      map[string]interface{}{"a": 1},
		Timestamp: now,
	}
	require.NoError(t, repo.Save(context.Background(), change))
	changes, err := repo.GetByItemID(context.Background(), "i1")
	require.NoError(t, err)
	assert.Len(t, changes, 1)
	assert.Equal(t, change, changes[0])
}

func TestInMemoryPendingChangeRepository_Delete(t *testing.T) {
	repo := NewInMemoryPendingChangeRepository()
	now := newNow()
	change := &entity.PendingChange{
		ID:        "c1",
		SessionID: "s1",
		ItemID:    "i1",
		Data:      map[string]interface{}{"a": 1},
		Timestamp: now,
	}
	require.NoError(t, repo.Save(context.Background(), change))
	require.NoError(t, repo.Delete(context.Background(), "c1"))
	retrieved, err := repo.GetByID(context.Background(), "c1")
	require.NoError(t, err)
	assert.Nil(t, retrieved)
	// Index cleanup
	bySession, _ := repo.GetBySessionID(context.Background(), "s1")
	assert.Empty(t, bySession)
	byItem, _ := repo.GetByItemID(context.Background(), "i1")
	assert.Empty(t, byItem)
}

func TestInMemoryPendingChangeRepository_DeleteBySessionID(t *testing.T) {
	repo := NewInMemoryPendingChangeRepository()
	now := newNow()
	change1 := &entity.PendingChange{
		ID:        "c1",
		SessionID: "s1",
		ItemID:    "i1",
		Data:      map[string]interface{}{"a": 1},
		Timestamp: now,
	}
	change2 := &entity.PendingChange{
		ID:        "c2",
		SessionID: "s1",
		ItemID:    "i2",
		Data:      map[string]interface{}{"b": 2},
		Timestamp: now,
	}
	other := &entity.PendingChange{
		ID:        "c3",
		SessionID: "s2",
		ItemID:    "i3",
		Data:      map[string]interface{}{"c": 3},
		Timestamp: now,
	}
	for _, c := range []*entity.PendingChange{change1, change2, other} {
		require.NoError(t, repo.Save(context.Background(), c))
	}
	require.NoError(t, repo.DeleteBySessionID(context.Background(), "s1"))
	// s1 changes gone
	bySession, _ := repo.GetBySessionID(context.Background(), "s1")
	assert.Empty(t, bySession)
	// other still exists
	bySession2, _ := repo.GetBySessionID(context.Background(), "s2")
	assert.Len(t, bySession2, 1)
	assert.Equal(t, "c3", bySession2[0].ID)
}

func TestInMemoryPendingChangeRepository_DeleteOlderThan(t *testing.T) {
	repo := NewInMemoryPendingChangeRepository()
	now := newNow()
	old := &entity.PendingChange{
		ID:        "old",
		SessionID: "s1",
		ItemID:    "i1",
		Data:      map[string]interface{}{"x": 1},
		Timestamp: now.Add(-2 * time.Hour),
	}
	newer := &entity.PendingChange{
		ID:        "newer",
		SessionID: "s1",
		ItemID:    "i2",
		Data:      map[string]interface{}{"y": 2},
		Timestamp: now.Add(-30 * time.Minute),
	}
	require.NoError(t, repo.Save(context.Background(), old))
	require.NoError(t, repo.Save(context.Background(), newer))
	deleted, err := repo.DeleteOlderThan(context.Background(), now.Add(-90*time.Minute))
	require.NoError(t, err)
	assert.Equal(t, int64(1), deleted)
	// Verify remaining
	remaining, _ := repo.GetBySessionID(context.Background(), "s1")
	assert.Len(t, remaining, 1)
	assert.Equal(t, "newer", remaining[0].ID)
}
