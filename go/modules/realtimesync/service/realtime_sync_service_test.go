package service

import (
	"context"
	"errors"
	"testing"
	"time"

	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/mock"

	"community.local/modules/realtimesync/entity"
)

// MockSyncSessionRepository mocks repository.SyncSessionRepository
type MockSyncSessionRepository struct {
	mock.Mock
}

func (m *MockSyncSessionRepository) Save(ctx context.Context, session *entity.SyncSession) error {
	args := m.Called(ctx, session)
	return args.Error(0)
}

func (m *MockSyncSessionRepository) GetByID(ctx context.Context, id string) (*entity.SyncSession, error) {
	args := m.Called(ctx, id)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).(*entity.SyncSession), args.Error(1)
}

func (m *MockSyncSessionRepository) GetByUserID(ctx context.Context, userID string) ([]*entity.SyncSession, error) {
	args := m.Called(ctx, userID)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).([]*entity.SyncSession), args.Error(1)
}

func (m *MockSyncSessionRepository) GetActiveByUserID(ctx context.Context, userID string) ([]*entity.SyncSession, error) {
	args := m.Called(ctx, userID)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).([]*entity.SyncSession), args.Error(1)
}

func (m *MockSyncSessionRepository) Delete(ctx context.Context, id string) error {
	args := m.Called(ctx, id)
	return args.Error(0)
}

func (m *MockSyncSessionRepository) ListActive(ctx context.Context) ([]*entity.SyncSession, error) {
	args := m.Called(ctx)
	if args.Get(0) == nil {
		return nil, args.Error(0)
	}
	return args.Get(0).([]*entity.SyncSession), args.Error(0)
}

// MockSyncItemRepository mocks repository.SyncItemRepository
type MockSyncItemRepository struct {
	mock.Mock
}

func (m *MockSyncItemRepository) Save(ctx context.Context, item *entity.SyncItem) error {
	args := m.Called(ctx, item)
	return args.Error(0)
}

func (m *MockSyncItemRepository) GetByID(ctx context.Context, id string) (*entity.SyncItem, error) {
	args := m.Called(ctx, id)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).(*entity.SyncItem), args.Error(1)
}

func (m *MockSyncItemRepository) GetBySessionID(ctx context.Context, sessionID string) ([]*entity.SyncItem, error) {
	args := m.Called(ctx, sessionID)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).([]*entity.SyncItem), args.Error(1)
}

func (m *MockSyncItemRepository) GetByType(ctx context.Context, itemType string) ([]*entity.SyncItem, error) {
	args := m.Called(ctx, itemType)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).([]*entity.SyncItem), args.Error(1)
}

func (m *MockSyncItemRepository) Delete(ctx context.Context, id string) error {
	args := m.Called(ctx, id)
	return args.Error(0)
}

func (m *MockSyncItemRepository) UpdateVersion(ctx context.Context, id string, newVersion int, updatedBy string) error {
	args := m.Called(ctx, id, newVersion, updatedBy)
	return args.Error(0)
}

// MockPendingChangeRepository mocks repository.PendingChangeRepository
type MockPendingChangeRepository struct {
	mock.Mock
}

func (m *MockPendingChangeRepository) Save(ctx context.Context, change *entity.PendingChange) error {
	args := m.Called(ctx, change)
	return args.Error(0)
}

func (m *MockPendingChangeRepository) GetByID(ctx context.Context, id string) (*entity.PendingChange, error) {
	args := m.Called(ctx, id)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).(*entity.PendingChange), args.Error(1)
}

func (m *MockPendingChangeRepository) GetBySessionID(ctx context.Context, sessionID string) ([]*entity.PendingChange, error) {
	args := m.Called(ctx, sessionID)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).([]*entity.PendingChange), args.Error(1)
}

func (m *MockPendingChangeRepository) GetByItemID(ctx context.Context, itemID string) ([]*entity.PendingChange, error) {
	args := m.Called(ctx, itemID)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).([]*entity.PendingChange), args.Error(1)
}

func (m *MockPendingChangeRepository) Delete(ctx context.Context, id string) error {
	args := m.Called(ctx, id)
	return args.Error(0)
}

func (m *MockPendingChangeRepository) DeleteBySessionID(ctx context.Context, sessionID string) error {
	args := m.Called(ctx, sessionID)
	return args.Error(0)
}

func (m *MockPendingChangeRepository) DeleteOlderThan(ctx context.Context, before time.Time) (int64, error) {
	args := m.Called(ctx, before)
	if args.Get(0) == nil {
		return 0, args.Error(0)
	}
	return args.Get(0).(int64), args.Error(0)
}

// Tests

func TestRealtimeSyncService_CreateSession(t *testing.T) {
	ctx := context.Background()
	sessionRepo := &MockSyncSessionRepository{}
	itemRepo := &MockSyncItemRepository{}
	pendingRepo := &MockPendingChangeRepository{}
	svc := NewRealtimeSyncService(sessionRepo, itemRepo, pendingRepo)

	userID := "user1"
	deviceID := "device123"
	deviceType := "android"

	sessionRepo.On("Save", ctx, mock.AnythingOfType("*entity.SyncSession")).Return(nil).Once().Run(func(args mock.Arguments) {
		s := args.Get(1).(*entity.SyncSession)
		s.ID = "sync_1"
		s.CreatedAt = time.Now()
	})

	sess, err := svc.CreateSession(ctx, userID, deviceID, deviceType)
	assert.NoError(t, err)
	assert.NotEmpty(t, sess.ID)
	assert.Equal(t, userID, sess.UserID)
	assert.Len(t, sess.Devices, 1)
	assert.Equal(t, deviceID, sess.Devices[0].DeviceID)
	assert.True(t, sess.Devices[0].Online)
	assert.Empty(t, sess.Items)

	sessionRepo.AssertExpectations(t)
}

func TestRealtimeSyncService_JoinSession(t *testing.T) {
	ctx := context.Background()
	sessionRepo := &MockSyncSessionRepository{}
	itemRepo := &MockSyncItemRepository{}
	pendingRepo := &MockPendingChangeRepository{}
	svc := NewRealtimeSyncService(sessionRepo, itemRepo, pendingRepo)

	existing := &entity.SyncSession{
		ID:        "sync_1",
		UserID:    "user1",
		Devices:   []entity.Device{{DeviceID: "dev1", DeviceType: "ios", Online: true}},
		CreatedAt: time.Now(),
	}
	sessionRepo.On("GetByID", ctx, "sync_1").Return(existing, nil)
	sessionRepo.On("Save", ctx, mock.AnythingOfType("*entity.SyncSession")).Return(nil).Once()

	sess, err := svc.JoinSession(ctx, "sync_1", "user1", "dev2")
	assert.NoError(t, err)
	assert.Len(t, sess.Devices, 2)
	assert.Equal(t, "dev2", sess.Devices[1].DeviceID)

	sessionRepo.AssertExpectations(t)
}

func TestRealtimeSyncService_JoinSession_NotFound(t *testing.T) {
	ctx := context.Background()
	sessionRepo := &MockSyncSessionRepository{}
	itemRepo := &MockSyncItemRepository{}
	pendingRepo := &MockPendingChangeRepository{}
	svc := NewRealtimeSyncService(sessionRepo, itemRepo, pendingRepo)

	sessionRepo.On("GetByID", ctx, "unknown").Return(nil, errors.New("not found"))

	sess, err := svc.JoinSession(ctx, "unknown", "user1", "dev1")
	assert.Error(t, err)
	assert.Nil(t, sess)
}

func TestRealtimeSyncService_SyncItem(t *testing.T) {
	ctx := context.Background()
	sessionRepo := &MockSyncSessionRepository{}
	itemRepo := &MockSyncItemRepository{}
	pendingRepo := &MockPendingChangeRepository{}
	svc := NewRealtimeSyncService(sessionRepo, itemRepo, pendingRepo)

	session := &entity.SyncSession{
		ID:        "sync_1",
		UserID:    "user1",
		Devices:   []entity.Device{},
		Items:     []string{},
		CreatedAt: time.Now(),
	}
	sessionRepo.On("GetByID", ctx, "sync_1").Return(session, nil)

	itemRepo.On("Save", ctx, mock.AnythingOfType("*entity.SyncItem")).Return(nil).Once().Run(func(args mock.Arguments) {
		item := args.Get(1).(*entity.SyncItem)
		item.ID = "item_1"
		item.Version = 1
		item.UpdatedAt = time.Now()
	})
	sessionRepo.On("Save", ctx, mock.AnythingOfType("*entity.SyncSession")).Return(nil).Once()

	item, err := svc.SyncItem(ctx, "sync_1", "item_1", "document", map[string]interface{}{"key": "value"}, 1)
	assert.NoError(t, err)
	assert.Equal(t, "item_1", item.ID)
	assert.Equal(t, "sync_1", item.SessionID)
	assert.Equal(t, 1, item.Version)

	// Check session.Items updated
	assert.Contains(t, session.Items, "item_1")
}

func TestRealtimeSyncService_GetSyncItem(t *testing.T) {
	ctx := context.Background()
	sessionRepo := &MockSyncSessionRepository{}
	itemRepo := &MockSyncItemRepository{}
	pendingRepo := &MockPendingChangeRepository{}
	svc := NewRealtimeSyncService(sessionRepo, itemRepo, pendingRepo)

	item := &entity.SyncItem{
		ID:        "item_1",
		SessionID: "sync_1",
		Type:      "text",
		Data:      map[string]interface{}{"content": "hello"},
		Version:   2,
	}
	itemRepo.On("GetByID", ctx, "item_1").Return(item, nil)

	result, err := svc.GetSyncItem(ctx, "sync_1", "item_1")
	assert.NoError(t, err)
	assert.Equal(t, item, result)
}

func TestRealtimeSyncService_GetAllItems(t *testing.T) {
	ctx := context.Background()
	sessionRepo := &MockSyncSessionRepository{}
	itemRepo := &MockSyncItemRepository{}
	pendingRepo := &MockPendingChangeRepository{}
	svc := NewRealtimeSyncService(sessionRepo, itemRepo, pendingRepo)

	items := []*entity.SyncItem{
		{ID: "item_1", SessionID: "sync_1"},
		{ID: "item_2", SessionID: "sync_1"},
	}
	itemRepo.On("GetBySessionID", ctx, "sync_1").Return(items, nil)

	result, err := svc.GetAllItems(ctx, "sync_1")
	assert.NoError(t, err)
	assert.Len(t, result, 2)
}

func TestRealtimeSyncService_DeleteItem(t *testing.T) {
	ctx := context.Background()
	sessionRepo := &MockSyncSessionRepository{}
	itemRepo := &MockSyncItemRepository{}
	pendingRepo := &MockPendingChangeRepository{}
	svc := NewRealtimeSyncService(sessionRepo, itemRepo, pendingRepo)

	item := &entity.SyncItem{ID: "item_1", SessionID: "sync_1"}
	session := &entity.SyncSession{
		ID:        "sync_1",
		UserID:    "user1",
		Items:     []string{"item_1"},
		CreatedAt: time.Now(),
	}
	itemRepo.On("GetByID", ctx, "item_1").Return(item, nil)
	itemRepo.On("Delete", ctx, "item_1").Return(nil).Once()
	sessionRepo.On("GetByID", ctx, "sync_1").Return(session, nil)
	sessionRepo.On("Save", ctx, mock.AnythingOfType("*entity.SyncSession")).Return(nil).Once()

	err := svc.DeleteItem(ctx, "sync_1", "item_1")
	assert.NoError(t, err)

	// Ensure item removed from session.Items
	assert.Empty(t, session.Items)
}

func TestRealtimeSyncService_SubscribeToItem(t *testing.T) {
	svc := NewRealtimeSyncService(nil, nil, nil)
	// Does nothing, no error
	err := svc.SubscribeToItem(context.Background(), "sync_1", "item_1")
	assert.NoError(t, err)
}

func TestRealtimeSyncService_UnsubscribeFromItem(t *testing.T) {
	svc := NewRealtimeSyncService(nil, nil, nil)
	err := svc.UnsubscribeFromItem(context.Background(), "sync_1", "item_1")
	assert.NoError(t, err)
}

func TestRealtimeSyncService_GetOnlineDevices(t *testing.T) {
	ctx := context.Background()
	sessionRepo := &MockSyncSessionRepository{}
	itemRepo := &MockSyncItemRepository{}
	pendingRepo := &MockPendingChangeRepository{}
	svc := NewRealtimeSyncService(sessionRepo, itemRepo, pendingRepo)

	session := &entity.SyncSession{
		ID:        "sync_1",
		Devices: []entity.Device{
			{DeviceID: "dev1", Online: true},
			{DeviceID: "dev2", Online: false},
		},
	}
	sessionRepo.On("GetByID", ctx, "sync_1").Return(session, nil)

	online, err := svc.GetOnlineDevices(ctx, "sync_1")
	assert.NoError(t, err)
	assert.Len(t, online, 1)
	assert.Equal(t, "dev1", online[0].DeviceID)
}

func TestRealtimeSyncService_EndSession(t *testing.T) {
	ctx := context.Background()
	sessionRepo := &MockSyncSessionRepository{}
	itemRepo := &MockSyncItemRepository{}
	pendingRepo := &MockPendingChangeRepository{}
	svc := NewRealtimeSyncService(sessionRepo, itemRepo, pendingRepo)

	session := &entity.SyncSession{
		ID:        "sync_1",
		CreatedAt: time.Now(),
		EndedAt:   nil,
	}
	sessionRepo.On("GetByID", ctx, "sync_1").Return(session, nil)
	sessionRepo.On("Save", ctx, mock.AnythingOfType("*entity.SyncSession")).Return(nil).Once()

	err := svc.EndSession(ctx, "sync_1")
	assert.NoError(t, err)
	assert.NotNil(t, session.EndedAt)
}

func TestRealtimeSyncService_PushChange(t *testing.T) {
	ctx := context.Background()
	sessionRepo := &MockSyncSessionRepository{}
	itemRepo := &MockSyncItemRepository{}
	pendingRepo := &MockPendingChangeRepository{}
	svc := NewRealtimeSyncService(sessionRepo, itemRepo, pendingRepo)

	changeData := map[string]interface{}{"field": "value"}
	pendingRepo.On("Save", ctx, mock.AnythingOfType("*entity.PendingChange")).Return(nil).Once().Run(func(args mock.Arguments) {
		c := args.Get(1).(*entity.PendingChange)
		c.ID = "change_1"
		c.Timestamp = time.Now()
	})

	created, err := svc.PushChange(ctx, "sync_1", "item_1", changeData)
	assert.NoError(t, err)
	assert.NotEmpty(t, created.ID)
	assert.Equal(t, "sync_1", created.SessionID)
	assert.Equal(t, "item_1", created.ItemID)
}

func TestRealtimeSyncService_GetPendingChanges(t *testing.T) {
	ctx := context.Background()
	sessionRepo := &MockSyncSessionRepository{}
	itemRepo := &MockSyncItemRepository{}
	pendingRepo := &MockPendingChangeRepository{}
	svc := NewRealtimeSyncService(sessionRepo, itemRepo, pendingRepo)

	changes := []*entity.PendingChange{
		{ID: "c1", SessionID: "sync_1", ItemID: "item_1"},
		{ID: "c2", SessionID: "sync_1", ItemID: "item_2"},
	}
	pendingRepo.On("GetBySessionID", ctx, "sync_1").Return(changes, nil)

	result, err := svc.GetPendingChanges(ctx, "sync_1", "dev1")
	assert.NoError(t, err)
	assert.Len(t, result, 2)
}

func TestRealtimeSyncService_GetSession(t *testing.T) {
	ctx := context.Background()
	sessionRepo := &MockSyncSessionRepository{}
	itemRepo := &MockSyncItemRepository{}
	pendingRepo := &MockPendingChangeRepository{}
	svc := NewRealtimeSyncService(sessionRepo, itemRepo, pendingRepo)

	session := &entity.SyncSession{ID: "sync_1", UserID: "user1"}
	sessionRepo.On("GetByID", ctx, "sync_1").Return(session, nil)

	result, err := svc.GetSession(ctx, "sync_1")
	assert.NoError(t, err)
	assert.Equal(t, session, result)
}

func TestRealtimeSyncService_Terminate(t *testing.T) {
	svc := NewRealtimeSyncService(nil, nil, nil)
	// Should not panic
	svc.Terminate(context.Background())
}
