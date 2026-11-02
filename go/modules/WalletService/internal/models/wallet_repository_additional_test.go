package walletservice

import (
	"context"
	"testing"

	_ "github.com/mattn/go-sqlite3"
	"github.com/stretchr/testify/assert"

	"community.local/testing/helper"
)

func TestWalletRepository_GetWalletForUpdate(t *testing.T) {
	db := helper.SetupTestDB(t)
	defer db.Close()
	setupWalletSQLiteSchema(t, db)

	repo := NewWalletRepository(db)
	ctx := context.Background()

	wallet := &Wallet{
		UserID:   "user-lock",
		Balance:  1000,
		Currency: "VND",
		Status:   "active",
	}
	err := repo.CreateWallet(ctx, wallet)
	assert.NoError(t, err)

	// GetWalletForUpdate should retrieve the wallet (FOR UPDATE locks row, but we just test successful fetch)
	fetched, err := repo.GetWalletForUpdate(ctx, "user-lock")
	assert.NoError(t, err)
	assert.Equal(t, wallet.ID, fetched.ID)
	assert.Equal(t, wallet.Balance, fetched.Balance)
}

func TestWalletRepository_UpdateBalanceWithVersion(t *testing.T) {
	db := helper.SetupTestDB(t)
	defer db.Close()
	setupWalletSQLiteSchema(t, db)

	repo := NewWalletRepository(db)
	ctx := context.Background()

	wallet := &Wallet{
		UserID:   "user-version",
		Balance:  1000,
		Currency: "VND",
		Status:   "active",
		Version:  1,
	}
	err := repo.CreateWallet(ctx, wallet)
	assert.NoError(t, err)

	// Successful update with correct version
	err = repo.UpdateBalanceWithVersion(ctx, wallet.ID, 500, wallet.Version)
	assert.NoError(t, err)

	// Verify new balance and version incremented
	updated, err := repo.GetWallet(ctx, wallet.ID)
	assert.NoError(t, err)
	assert.Equal(t, int64(1500), updated.Balance)
	assert.Equal(t, int64(2), updated.Version)

	// Failed update with stale version
	err = repo.UpdateBalanceWithVersion(ctx, wallet.ID, 200, 1) // old version 1
	assert.Error(t, err)
	assert.Contains(t, err.Error(), "optimistic lock failed")
}

func TestWalletRepository_UpdateStatus(t *testing.T) {
	db := helper.SetupTestDB(t)
	defer db.Close()
	setupWalletSQLiteSchema(t, db)

	repo := NewWalletRepository(db)
	ctx := context.Background()

	wallet := &Wallet{
		UserID:   "user-status",
		Balance:  1000,
		Currency: "VND",
		Status:   "active",
	}
	err := repo.CreateWallet(ctx, wallet)
	assert.NoError(t, err)

	// Update status to 'suspended'
	err = repo.UpdateStatus(ctx, wallet.ID, "suspended")
	assert.NoError(t, err)

	// Verify
	updated, err := repo.GetWallet(ctx, wallet.ID)
	assert.NoError(t, err)
	assert.Equal(t, "suspended", updated.Status)
}

func TestWalletRepository_DeleteWallet(t *testing.T) {
	db := helper.SetupTestDB(t)
	defer db.Close()
	setupWalletSQLiteSchema(t, db)

	repo := NewWalletRepository(db)
	ctx := context.Background()

	wallet := &Wallet{
		UserID:   "user-del",
		Balance:  0,
		Currency: "VND",
		Status:   "active",
	}
	err := repo.CreateWallet(ctx, wallet)
	assert.NoError(t, err)

	// Delete wallet
	err = repo.DeleteWallet(ctx, wallet.ID)
	assert.NoError(t, err)

	// Verify deletion
	_, err = repo.GetWallet(ctx, wallet.ID)
	assert.Error(t, err)
}
