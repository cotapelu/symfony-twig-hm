package repository

import (
	"context"
	"testing"

	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"
	walletservice "community.local/modules/WalletService/internal/models"
)

func TestWalletTransactionRepository_NilDB(t *testing.T) {
	repo := NewWalletTransactionRepository(nil)

	_, err := repo.GetTransaction(context.Background(), 1)
	require.Error(t, err)

	_, err = repo.GetTransactionsByWallet(context.Background(), 1, 10, 0)
	require.Error(t, err)

	err = repo.UpdateTransactionStatus(context.Background(), 1, "completed")
	require.Error(t, err)

	err = repo.CreateTransaction(context.Background(), &walletservice.WalletTransaction{})
	require.Error(t, err)
}

func TestWalletTransactionRepository_NilDB_NoPanic(t *testing.T) {
	assert.NotPanics(t, func() {
		_ = NewWalletTransactionRepository(nil)
	})
}
