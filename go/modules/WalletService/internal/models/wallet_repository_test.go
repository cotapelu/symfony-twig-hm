package walletservice

import (
	"context"
	"database/sql"
	"testing"

	_ "github.com/mattn/go-sqlite3"
	"github.com/stretchr/testify/assert"

	"community.local/testing/helper"
)

func setupWalletSQLiteSchema(t *testing.T, db *sql.DB) {
	t.Helper()
	_, _ = db.Exec("PRAGMA foreign_keys = ON")

	// wallets
	_, err := db.Exec(`
		CREATE TABLE wallets (
			id INTEGER PRIMARY KEY AUTOINCREMENT,
			user_id TEXT NOT NULL,
			balance INTEGER NOT NULL DEFAULT 0,
			currency TEXT NOT NULL DEFAULT 'VND',
			status TEXT NOT NULL DEFAULT 'active',
			is_default BOOLEAN NOT NULL DEFAULT false,
			version INTEGER NOT NULL DEFAULT 1,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
		);
	`)
	if err != nil {
		t.Fatalf("create wallets failed: %v", err)
	}

	// wallet_transactions
	_, err = db.Exec(`
		CREATE TABLE wallet_transactions (
			id INTEGER PRIMARY KEY AUTOINCREMENT,
			wallet_id INTEGER NOT NULL REFERENCES wallets(id),
			type TEXT NOT NULL,
			amount REAL NOT NULL,
			balance_after REAL NOT NULL,
			description TEXT,
			reference_id TEXT UNIQUE,
			metadata TEXT,
			status TEXT NOT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
		);
	`)
	if err != nil {
		t.Fatalf("create wallet_transactions failed: %v", err)
	}
}

func TestWalletRepository_CreateAndGetWallet(t *testing.T) {
	db := helper.SetupTestDB(t)
	defer db.Close()
	setupWalletSQLiteSchema(t, db)

	repo := NewWalletRepository(db)
	ctx := context.Background()

	wallet := &Wallet{
		UserID:   "user-w1",
		Balance:  0,
		Currency: "VND",
		Status:   "active",
	}
	err := repo.CreateWallet(ctx, wallet)
	assert.NoError(t, err)
	assert.NotZero(t, wallet.ID)
	assert.NotZero(t, wallet.CreatedAt)

	fetched, err := repo.GetWallet(ctx, wallet.ID)
	assert.NoError(t, err)
	assert.Equal(t, wallet.UserID, fetched.UserID)
	assert.Equal(t, "VND", fetched.Currency)
}

func TestWalletRepository_GetWalletByUser(t *testing.T) {
	db := helper.SetupTestDB(t)
	defer db.Close()
	setupWalletSQLiteSchema(t, db)

	repo := NewWalletRepository(db)
	ctx := context.Background()

	// Create two wallets for same user
	for i := 0; i < 2; i++ {
		wallet := &Wallet{
			UserID:   "user-multi",
			Balance:  int64(i * 1000),
			Currency: "VND",
			Status:   "active",
		}
		err := repo.CreateWallet(ctx, wallet)
		assert.NoError(t, err, "create wallet %d", i)
	}

	// GetWalletByUser returns first active wallet (or one)
	w, err := repo.GetWalletByUser(ctx, "user-multi")
	assert.NoError(t, err)
	assert.NotZero(t, w.ID)
}

func TestWalletRepository_UpdateBalance(t *testing.T) {
	db := helper.SetupTestDB(t)
	defer db.Close()
	setupWalletSQLiteSchema(t, db)

	repo := NewWalletRepository(db)
	ctx := context.Background()

	wallet := &Wallet{
		UserID:   "user-upd",
		Balance:  500,
		Currency: "VND",
		Status:   "active",
	}
	err := repo.CreateWallet(ctx, wallet)
	assert.NoError(t, err)

	// Update balance (add 1500)
	err = repo.UpdateBalance(ctx, wallet.ID, 1500)
	assert.NoError(t, err)

	// Check
	updated, err := repo.GetWallet(ctx, wallet.ID)
	assert.NoError(t, err)
	assert.Equal(t, int64(2000), updated.Balance) // 500 + 1500
}

func TestWalletRepository_HasSufficientBalance(t *testing.T) {
	db := helper.SetupTestDB(t)
	defer db.Close()
	setupWalletSQLiteSchema(t, db)

	repo := NewWalletRepository(db)
	ctx := context.Background()

	wallet := &Wallet{
		UserID:   "user-bal",
		Balance:  1000000,
		Currency: "VND",
		Status:   "active",
	}
	err := repo.CreateWallet(ctx, wallet)
	assert.NoError(t, err)

	// Sufficient
	ok, err := repo.HasSufficientBalance(ctx, "user-bal", 500000)
	assert.NoError(t, err)
	assert.True(t, ok)

	// Not sufficient
	ok, err = repo.HasSufficientBalance(ctx, "user-bal", 2000000)
	assert.NoError(t, err)
	assert.False(t, ok)
}
