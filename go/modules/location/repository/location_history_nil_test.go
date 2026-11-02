package repository

import (
	"context"
	"testing"
	"time"

	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"
	"community.local/modules/location/entity"
)

func TestLocationHistoryRepositoryImpl_NilDB_Errors(t *testing.T) {
	repo := NewLocationHistoryRepository(nil)

	loc := &entity.UserLocation{
		ID:        "loc-1",
		UserID:    "user-1",
		Location:  entity.Location{Latitude: 1.0, Longitude: 2.0},
		UpdatedAt: time.Now(),
	}

	err := repo.CreateHistory(context.Background(), loc)
	require.Error(t, err)

	_, err = repo.GetHistory(context.Background(), 1)
	require.Error(t, err)

	_, err = repo.GetHistoryByUser(context.Background(), "user-1", 10, 0)
	require.Error(t, err)

	_, err = repo.GetHistoryByDateRange(context.Background(), "user-1", "2024-01-01", "2024-12-31", 10, 0)
	require.Error(t, err)

	err = repo.DeleteOldHistory(context.Background(), 30)
	require.Error(t, err)
}

func TestLocationHistoryRepositoryImpl_NilDB_NoPanic(t *testing.T) {
	assert.NotPanics(t, func() {
		_ = NewLocationHistoryRepository(nil)
	})
}
