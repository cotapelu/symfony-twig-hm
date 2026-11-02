package repository

import (
	"context"
	"testing"

	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"
	"community.local/modules/Escrow/models"
)

func TestEscrowRepository_NilDB(t *testing.T) {
	repo := NewEscrowRepository(nil)

	_, err := repo.GetByID(context.Background(), "escrow-1")
	require.Error(t, err)

	err = repo.Create(context.Background(), &models.Escrow{})
	require.Error(t, err)

	err = repo.Update(context.Background(), &models.Escrow{ID: "escrow-1"})
	require.Error(t, err)
}

func TestEscrowRepository_NilDB_NoPanic(t *testing.T) {
	assert.NotPanics(t, func() {
		_ = NewEscrowRepository(nil)
	})
}
