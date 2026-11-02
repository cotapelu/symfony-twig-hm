package repository

import (
	"context"
	"time"

	"community.local/modules/realtimesync/entity"
)

type SyncSessionRepository interface {
	Save(ctx context.Context, session *entity.SyncSession) error
	GetByID(ctx context.Context, id string) (*entity.SyncSession, error)
	GetByUserID(ctx context.Context, userID string) ([]*entity.SyncSession, error)
	GetActiveByUserID(ctx context.Context, userID string) ([]*entity.SyncSession, error)
	Delete(ctx context.Context, id string) error
	ListActive(ctx context.Context) ([]*entity.SyncSession, error)
}

type SyncItemRepository interface {
	Save(ctx context.Context, item *entity.SyncItem) error
	GetByID(ctx context.Context, id string) (*entity.SyncItem, error)
	GetBySessionID(ctx context.Context, sessionID string) ([]*entity.SyncItem, error)
	GetByType(ctx context.Context, itemType string) ([]*entity.SyncItem, error)
	Delete(ctx context.Context, id string) error
	UpdateVersion(ctx context.Context, id string, newVersion int, updatedBy string) error
}

type PendingChangeRepository interface {
	Save(ctx context.Context, change *entity.PendingChange) error
	GetByID(ctx context.Context, id string) (*entity.PendingChange, error)
	GetBySessionID(ctx context.Context, sessionID string) ([]*entity.PendingChange, error)
	GetByItemID(ctx context.Context, itemID string) ([]*entity.PendingChange, error)
	Delete(ctx context.Context, id string) error
	DeleteBySessionID(ctx context.Context, sessionID string) error
	DeleteOlderThan(ctx context.Context, before time.Time) (int64, error)
}
