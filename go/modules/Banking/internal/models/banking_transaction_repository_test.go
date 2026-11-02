package banking

import (
	"context"
	"database/sql"
	"testing"

	_ "github.com/mattn/go-sqlite3"
	"github.com/stretchr/testify/assert"

	"community.local/testing/helper"
)

func setupBankingSQLiteSchemaForTx(t *testing.T, db *sql.DB) {
	t.Helper()
	_, _ = db.Exec("PRAGMA foreign_keys = ON")

	// accounts (simplified)
	_, err := db.Exec(`
		CREATE TABLE banking_accounts (
			id INTEGER PRIMARY KEY AUTOINCREMENT,
			user_id TEXT NOT NULL,
			account_number TEXT UNIQUE NOT NULL,
			account_type TEXT NOT NULL,
			balance REAL NOT NULL DEFAULT 0,
			currency TEXT NOT NULL DEFAULT 'VND',
			status TEXT NOT NULL DEFAULT 'active',
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
		);
	`)
	if err != nil {
		t.Fatalf("create accounts failed: %v", err)
	}

	// transactions
	_, err = db.Exec(`
		CREATE TABLE banking_transactions (
			id INTEGER PRIMARY KEY AUTOINCREMENT,
			account_id INTEGER NOT NULL REFERENCES banking_accounts(id),
			type TEXT NOT NULL,
			amount REAL NOT NULL,
			balance_after REAL NOT NULL,
			description TEXT,
			reference_id TEXT UNIQUE,
			status TEXT NOT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
		);
	`)
	if err != nil {
		t.Fatalf("create transactions failed: %v", err)
	}
}

func TestTransactionRepository_CreateAndGet(t *testing.T) {
	db := helper.SetupTestDB(t)
	defer db.Close()
	setupBankingSQLiteSchemaForTx(t, db)

	accRepo := NewAccountRepository(db)
	ctx := context.Background()

	acc := &Account{
		UserID:        "user-tx",
		AccountNumber: "ACC-TX",
		AccountType:   "savings",
		Balance:       1000,
		Currency:      "VND",
		Status:        "active",
	}
	err := accRepo.CreateAccount(ctx, acc)
	assert.NoError(t, err)

	txn := &Transaction{
		AccountID:     acc.ID,
		Type:          "deposit",
		Amount:        500,
		BalanceAfter: 1500,
		Description:   "test deposit",
		ReferenceID:   "REF-123",
		Status:        "completed",
	}
	txnRepo := NewTransactionRepository(db)
	err = txnRepo.CreateTransaction(ctx, txn)
	assert.NoError(t, err)
	assert.NotZero(t, txn.ID)

	// Get by reference
	fetched, err := txnRepo.GetTransactionByReference(ctx, "REF-123")
	assert.NoError(t, err)
	assert.Equal(t, txn.ID, fetched.ID)
	assert.Equal(t, acc.ID, fetched.AccountID)

	// Get by account
	txns, err := txnRepo.GetTransactionsByAccount(ctx, acc.ID, 10, 0)
	assert.NoError(t, err)
	assert.Len(t, txns, 1)
}
