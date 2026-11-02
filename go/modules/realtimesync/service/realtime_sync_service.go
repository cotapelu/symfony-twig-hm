package service

import (
	"context"
	"fmt"
	"sync"
	"time"

	"community.local/modules/realtimesync/entity"
	"community.local/modules/realtimesync/repository"
)

type RealtimeSyncService struct {
	mu                sync.RWMutex
	sessionRepo       repository.SyncSessionRepository
	itemRepo          repository.SyncItemRepository
	pendingChangeRepo repository.PendingChangeRepository
	nextID            int
}

func NewRealtimeSyncService(
	sessionRepo repository.SyncSessionRepository,
	itemRepo repository.SyncItemRepository,
	pendingChangeRepo repository.PendingChangeRepository,
) *RealtimeSyncService {
	s := &RealtimeSyncService{
		sessionRepo:       sessionRepo,
		itemRepo:          itemRepo,
		pendingChangeRepo: pendingChangeRepo,
		nextID:            1,
	}
	return s
}

func (s *RealtimeSyncService) CreateSession(ctx context.Context, userID, deviceID, deviceType string) (*entity.SyncSession, error) {
	s.mu.Lock()
	defer s.mu.Unlock()

	sessionID := fmt.Sprintf("sync_%d", s.nextID)
	s.nextID++

	now := time.Now()
	device := entity.Device{
		DeviceID:   deviceID,
		DeviceType: deviceType,
		Online:     true,
		LastSeen:   now,
	}

	session := &entity.SyncSession{
		ID:        sessionID,
		UserID:    userID,
		OwnerID:   userID,
		Devices:   []entity.Device{device},
		Items:     []string{},
		CreatedAt: now,
		EndedAt:   nil,
	}

	if err := s.sessionRepo.Save(ctx, session); err != nil {
		return nil, err
	}

	return session, nil
}

func (s *RealtimeSyncService) JoinSession(ctx context.Context, sessionID, userID, deviceID string) (*entity.SyncSession, error) {
	session, err := s.sessionRepo.GetByID(ctx, sessionID)
	if err != nil {
		return nil, err
	}
	if session == nil {
		return nil, nil
	}

	newDevice := entity.Device{
		DeviceID:   deviceID,
		DeviceType: "unknown",
		Online:     true,
		LastSeen:   time.Now(),
	}

	s.mu.Lock()
	session.Devices = append(session.Devices, newDevice)
	s.mu.Unlock()

	if err := s.sessionRepo.Save(ctx, session); err != nil {
		return nil, err
	}

	return session, nil
}

func (s *RealtimeSyncService) SyncItem(ctx context.Context, sessionID, itemID, itemType string, data map[string]interface{}, version int) (*entity.SyncItem, error) {
	session, err := s.sessionRepo.GetByID(ctx, sessionID)
	if err != nil {
		return nil, err
	}
	if session == nil {
		return nil, nil
	}

	item := &entity.SyncItem{
		ID:        itemID,
		SessionID: sessionID,
		Type:      itemType,
		Data:      data,
		Version:   version,
		UpdatedAt: time.Now(),
		UpdatedBy: nil,
	}

	if err := s.itemRepo.Save(ctx, item); err != nil {
		return nil, err
	}

	s.mu.Lock()
	session.Items = append(session.Items, itemID)
	s.mu.Unlock()

	if err := s.sessionRepo.Save(ctx, session); err != nil {
		return nil, err
	}

	return item, nil
}

func (s *RealtimeSyncService) GetSyncItem(ctx context.Context, sessionID, itemID string) (*entity.SyncItem, error) {
	return s.itemRepo.GetByID(ctx, itemID)
}

func (s *RealtimeSyncService) GetAllItems(ctx context.Context, sessionID string) ([]entity.SyncItem, error) {
	items, err := s.itemRepo.GetBySessionID(ctx, sessionID)
	if err != nil {
		return nil, err
	}

	result := make([]entity.SyncItem, 0, len(items))
	for _, item := range items {
		result = append(result, *item)
	}

	return result, nil
}

func (s *RealtimeSyncService) DeleteItem(ctx context.Context, sessionID, itemID string) error {
	item, err := s.itemRepo.GetByID(ctx, itemID)
	if err != nil {
		return err
	}
	if item == nil {
		return nil
	}

	if err := s.itemRepo.Delete(ctx, itemID); err != nil {
		return err
	}

	session, err := s.sessionRepo.GetByID(ctx, sessionID)
	if err != nil || session == nil {
		return nil
	}

	s.mu.Lock()
	for i, id := range session.Items {
		if id == itemID {
			session.Items = append(session.Items[:i], session.Items[i+1:]...)
			break
		}
	}
	s.mu.Unlock()

	return s.sessionRepo.Save(ctx, session)
}

func (s *RealtimeSyncService) SubscribeToItem(ctx context.Context, sessionID, itemID string) error {
	return nil
}

func (s *RealtimeSyncService) UnsubscribeFromItem(ctx context.Context, sessionID, itemID string) error {
	return nil
}

func (s *RealtimeSyncService) GetOnlineDevices(ctx context.Context, sessionID string) ([]entity.Device, error) {
	session, err := s.sessionRepo.GetByID(ctx, sessionID)
	if err != nil {
		return nil, err
	}
	if session == nil {
		return nil, nil
	}

	var online []entity.Device
	for _, device := range session.Devices {
		if device.Online {
			online = append(online, device)
		}
	}

	return online, nil
}

func (s *RealtimeSyncService) EndSession(ctx context.Context, sessionID string) error {
	session, err := s.sessionRepo.GetByID(ctx, sessionID)
	if err != nil {
		return err
	}
	if session == nil {
		return nil
	}

	now := time.Now()
	session.EndedAt = &now

	return s.sessionRepo.Save(ctx, session)
}

func (s *RealtimeSyncService) PushChange(ctx context.Context, sessionID, itemID string, changeData map[string]interface{}) (*entity.PendingChange, error) {
	s.mu.Lock()
	changeID := fmt.Sprintf("change_%d", s.nextID)
	s.nextID++
	s.mu.Unlock()

	change := &entity.PendingChange{
		ID:        changeID,
		SessionID: sessionID,
		ItemID:    itemID,
		Data:      changeData,
		Timestamp: time.Now(),
	}

	if err := s.pendingChangeRepo.Save(ctx, change); err != nil {
		return nil, err
	}

	return change, nil
}

func (s *RealtimeSyncService) GetPendingChanges(ctx context.Context, sessionID, deviceID string) ([]entity.PendingChange, error) {
	changes, err := s.pendingChangeRepo.GetBySessionID(ctx, sessionID)
	if err != nil {
		return nil, err
	}

	result := make([]entity.PendingChange, 0, len(changes))
	for _, change := range changes {
		result = append(result, *change)
	}

	return result, nil
}

func (s *RealtimeSyncService) GetSession(ctx context.Context, sessionID string) (*entity.SyncSession, error) {
	return s.sessionRepo.GetByID(ctx, sessionID)
}

func (s *RealtimeSyncService) Terminate(ctx context.Context) {
	// Cleanup can be performed here if needed
}
