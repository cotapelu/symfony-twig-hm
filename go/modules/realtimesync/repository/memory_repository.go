package repository

import (
	"context"
	"sync"
	"time"

	"community.local/modules/realtimesync/entity"
)

type InMemorySyncSessionRepository struct {
	mu       sync.RWMutex
	sessions map[string]*entity.SyncSession
	userIdx  map[string][]string // userID -> sessionIDs
}

func NewInMemorySyncSessionRepository() *InMemorySyncSessionRepository {
	return &InMemorySyncSessionRepository{
		sessions: make(map[string]*entity.SyncSession),
		userIdx:  make(map[string][]string),
	}
}

func (r *InMemorySyncSessionRepository) Save(ctx context.Context, session *entity.SyncSession) error {
	r.mu.Lock()
	defer r.mu.Unlock()

	r.sessions[session.ID] = session
	r.userIdx[session.UserID] = append(r.userIdx[session.UserID], session.ID)

	return nil
}

func (r *InMemorySyncSessionRepository) GetByID(ctx context.Context, id string) (*entity.SyncSession, error) {
	r.mu.RLock()
	defer r.mu.RUnlock()

	return r.sessions[id], nil
}

func (r *InMemorySyncSessionRepository) GetByUserID(ctx context.Context, userID string) ([]*entity.SyncSession, error) {
	r.mu.RLock()
	defer r.mu.RUnlock()

	sessionIDs, exists := r.userIdx[userID]
	if !exists {
		return nil, nil
	}

	sessions := make([]*entity.SyncSession, 0, len(sessionIDs))
	for _, id := range sessionIDs {
		if session, exists := r.sessions[id]; exists {
			sessions = append(sessions, session)
		}
	}

	return sessions, nil
}

func (r *InMemorySyncSessionRepository) GetActiveByUserID(ctx context.Context, userID string) ([]*entity.SyncSession, error) {
	r.mu.RLock()
	defer r.mu.RUnlock()

	sessionIDs, exists := r.userIdx[userID]
	if !exists {
		return nil, nil
	}

	var active []*entity.SyncSession
	for _, id := range sessionIDs {
		session, exists := r.sessions[id]
		if exists && session.EndedAt == nil {
			active = append(active, session)
		}
	}

	return active, nil
}

func (r *InMemorySyncSessionRepository) Delete(ctx context.Context, id string) error {
	r.mu.Lock()
	defer r.mu.Unlock()

	session, exists := r.sessions[id]
	if !exists {
		return nil
	}

	delete(r.sessions, id)
	r.userIdx[session.UserID] = removeID(r.userIdx[session.UserID], id)

	return nil
}

func (r *InMemorySyncSessionRepository) ListActive(ctx context.Context) ([]*entity.SyncSession, error) {
	r.mu.RLock()
	defer r.mu.RUnlock()

	var active []*entity.SyncSession
	for _, session := range r.sessions {
		if session.EndedAt == nil {
			active = append(active, session)
		}
	}

	return active, nil
}

func removeID(ids []string, target string) []string {
	for i, id := range ids {
		if id == target {
			return append(ids[:i], ids[i+1:]...)
		}
	}
	return ids
}

type InMemorySyncItemRepository struct {
	mu         sync.RWMutex
	items      map[string]*entity.SyncItem
	sessionIdx map[string][]string // sessionID -> itemIDs
	typeIdx    map[string][]string // type -> itemIDs
}

func NewInMemorySyncItemRepository() *InMemorySyncItemRepository {
	return &InMemorySyncItemRepository{
		items:      make(map[string]*entity.SyncItem),
		sessionIdx: make(map[string][]string),
		typeIdx:    make(map[string][]string),
	}
}

func (r *InMemorySyncItemRepository) Save(ctx context.Context, item *entity.SyncItem) error {
	r.mu.Lock()
	defer r.mu.Unlock()

	r.items[item.ID] = item
	r.sessionIdx[item.SessionID] = append(r.sessionIdx[item.SessionID], item.ID)
	r.typeIdx[item.Type] = append(r.typeIdx[item.Type], item.ID)

	return nil
}

func (r *InMemorySyncItemRepository) GetByID(ctx context.Context, id string) (*entity.SyncItem, error) {
	r.mu.RLock()
	defer r.mu.RUnlock()

	return r.items[id], nil
}

func (r *InMemorySyncItemRepository) GetBySessionID(ctx context.Context, sessionID string) ([]*entity.SyncItem, error) {
	r.mu.RLock()
	defer r.mu.RUnlock()

	itemIDs, exists := r.sessionIdx[sessionID]
	if !exists {
		return nil, nil
	}

	items := make([]*entity.SyncItem, 0, len(itemIDs))
	for _, id := range itemIDs {
		if item, exists := r.items[id]; exists {
			items = append(items, item)
		}
	}

	return items, nil
}

func (r *InMemorySyncItemRepository) GetByType(ctx context.Context, itemType string) ([]*entity.SyncItem, error) {
	r.mu.RLock()
	defer r.mu.RUnlock()

	itemIDs, exists := r.typeIdx[itemType]
	if !exists {
		return nil, nil
	}

	items := make([]*entity.SyncItem, 0, len(itemIDs))
	for _, id := range itemIDs {
		if item, exists := r.items[id]; exists {
			items = append(items, item)
		}
	}

	return items, nil
}

func (r *InMemorySyncItemRepository) Delete(ctx context.Context, id string) error {
	r.mu.Lock()
	defer r.mu.Unlock()

	item, exists := r.items[id]
	if !exists {
		return nil
	}

	delete(r.items, id)
	r.sessionIdx[item.SessionID] = removeID(r.sessionIdx[item.SessionID], id)
	r.typeIdx[item.Type] = removeID(r.typeIdx[item.Type], id)

	return nil
}

func (r *InMemorySyncItemRepository) UpdateVersion(ctx context.Context, id string, newVersion int, updatedBy string) error {
	r.mu.Lock()
	defer r.mu.Unlock()

	item, exists := r.items[id]
	if !exists {
		return nil
	}

	item.Version = newVersion
	item.UpdatedBy = &updatedBy
	item.UpdatedAt = time.Now()

	return nil
}

type InMemoryPendingChangeRepository struct {
	mu         sync.RWMutex
	changes    map[string]*entity.PendingChange
	sessionIdx map[string][]string // sessionID -> changeIDs
	itemIdx    map[string][]string // itemID -> changeIDs
}

func NewInMemoryPendingChangeRepository() *InMemoryPendingChangeRepository {
	return &InMemoryPendingChangeRepository{
		changes:    make(map[string]*entity.PendingChange),
		sessionIdx: make(map[string][]string),
		itemIdx:    make(map[string][]string),
	}
}

func (r *InMemoryPendingChangeRepository) Save(ctx context.Context, change *entity.PendingChange) error {
	r.mu.Lock()
	defer r.mu.Unlock()

	r.changes[change.ID] = change
	r.sessionIdx[change.SessionID] = append(r.sessionIdx[change.SessionID], change.ID)
	r.itemIdx[change.ItemID] = append(r.itemIdx[change.ItemID], change.ID)

	return nil
}

func (r *InMemoryPendingChangeRepository) GetByID(ctx context.Context, id string) (*entity.PendingChange, error) {
	r.mu.RLock()
	defer r.mu.RUnlock()

	return r.changes[id], nil
}

func (r *InMemoryPendingChangeRepository) GetBySessionID(ctx context.Context, sessionID string) ([]*entity.PendingChange, error) {
	r.mu.RLock()
	defer r.mu.RUnlock()

	changeIDs, exists := r.sessionIdx[sessionID]
	if !exists {
		return nil, nil
	}

	changes := make([]*entity.PendingChange, 0, len(changeIDs))
	for _, id := range changeIDs {
		if change, exists := r.changes[id]; exists {
			changes = append(changes, change)
		}
	}

	return changes, nil
}

func (r *InMemoryPendingChangeRepository) GetByItemID(ctx context.Context, itemID string) ([]*entity.PendingChange, error) {
	r.mu.RLock()
	defer r.mu.RUnlock()

	changeIDs, exists := r.itemIdx[itemID]
	if !exists {
		return nil, nil
	}

	changes := make([]*entity.PendingChange, 0, len(changeIDs))
	for _, id := range changeIDs {
		if change, exists := r.changes[id]; exists {
			changes = append(changes, change)
		}
	}

	return changes, nil
}

func (r *InMemoryPendingChangeRepository) Delete(ctx context.Context, id string) error {
	r.mu.Lock()
	defer r.mu.Unlock()

	change, exists := r.changes[id]
	if !exists {
		return nil
	}

	delete(r.changes, id)
	r.sessionIdx[change.SessionID] = removeID(r.sessionIdx[change.SessionID], id)
	r.itemIdx[change.ItemID] = removeID(r.itemIdx[change.ItemID], id)

	return nil
}

func (r *InMemoryPendingChangeRepository) DeleteBySessionID(ctx context.Context, sessionID string) error {
	r.mu.Lock()
	defer r.mu.Unlock()

	changeIDs, exists := r.sessionIdx[sessionID]
	if !exists {
		return nil
	}

	for _, id := range changeIDs {
		delete(r.changes, id)
	}
	delete(r.sessionIdx, sessionID)

	return nil
}

func (r *InMemoryPendingChangeRepository) DeleteOlderThan(ctx context.Context, before time.Time) (int64, error) {
	r.mu.Lock()
	defer r.mu.Unlock()

	var deleted int64
	for id, change := range r.changes {
		if change.Timestamp.Before(before) {
			delete(r.changes, id)
			r.sessionIdx[change.SessionID] = removeID(r.sessionIdx[change.SessionID], id)
			r.itemIdx[change.ItemID] = removeID(r.itemIdx[change.ItemID], id)
			deleted++
		}
	}

	return deleted, nil
}
