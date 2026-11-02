package walletservice

import (
	"context"
	"database/sql"
	"testing"

	_ "github.com/mattn/go-sqlite3"
	"github.com/stretchr/testify/assert"

	"community.local/testing/helper"
)

func setupWalletTxSQLiteSchema(t *testing.T, db *sql.DB) {
	t.Helper()
	_, _ = db.Exec("PRAGMA foreign_keys = ON")

	// wallets
	_, err := db.Exec(`
		CREATE TABLE wallets (
			id INTEGER PRIMARY KEY AUTOINCREMENT,
			user_id TEXT NOT NULL,
			balance REAL NOT NULL DEFAULT 0,
			currency TEXT NOT NULL DEFAULT 'VND',
			status TEXT NOT NULL DEFAULT 'active',
			is_default BOOLEAN NOT NULL DEFAULT false,
			version INTEGER NOT NULL DEFAULT 0,
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

func TestTransactionRepository_CreateAndGet(t *testing.T) {
	db := helper.SetupTestDB(t)
	defer db.Close()
	setupWalletTxSQLiteSchema(t, db)

	walletRepo := NewWalletRepository(db)
	ctx := context.Background()

	wallet := &Wallet{
		UserID:   "user-wtx",
		Balance:  1000,
		Currency: "VND",
		Status:   "active",
	}
	err := walletRepo.CreateWallet(ctx, wallet)
	assert.NoError(t, err)

	// Create two transactions
	txn1 := &WalletTransaction{
		WalletID:     wallet.ID,
		Type:         "topup",
		Amount:       500,
		BalanceAfter: 1500,
		ReferenceID:  "REF-001",
		Status:       "completed",
	}
	txn2 := &WalletTransaction{
		WalletID:     wallet.ID,
		Type:         "payment",
		Amount:       -200,
		BalanceAfter: 1300,
		ReferenceID:  "REF-002",
		Status:       "completed",
	}
	txnRepo := NewTransactionRepository(db)
	for _, txn := range []*WalletTransaction{txn1, txn2} {
		err = txnRepo.CreateTransaction(ctx, txn)
		assert.NoError(t, err)
		assert.NotZero(t, txn.ID)
	}

	// Get by wallet (should return both)
	txns, err := txnRepo.GetByWallet(ctx, wallet.ID, 10, 0)
	assert.NoError(t, err)
	assert.Len(t, txns, 2)

	// Get by reference REF-002
	byRef, err := txnRepo.GetByReference(ctx, "REF-002")
	assert.NoError(t, err)
	assert.Equal(t, int64(-200), byRef.Amount)
	assert.Equal(t, "payment", byRef.Type)
}
