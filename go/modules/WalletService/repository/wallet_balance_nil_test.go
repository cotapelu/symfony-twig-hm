package repository

import (
	"context"
	"testing"

	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"
	walletservice "community.local/modules/WalletService/internal/models"
)

func TestWalletBalanceRepository_NilDB(t *testing.T) {
	repo := NewWalletBalanceRepository(nil)

	_, err := repo.GetBalance(context.Background(), 1)
	require.Error(t, err)

	err = repo.UpdateBalance(context.Background(), 1, 100)
	require.Error(t, err)

	err = repo.CreateBalance(context.Background(), &walletservice.WalletBalance{})
	require.Error(t, err)
}

func TestWalletBalanceRepository_NilDB_NoPanic(t *testing.T) {
	assert.NotPanics(t, func() {
		_ = NewWalletBalanceRepository(nil)
	})
}
