package banking

import (
	"context"
	"database/sql"
	"fmt"
	"testing"

	_ "github.com/mattn/go-sqlite3"
	"github.com/stretchr/testify/assert"

	"community.local/testing/helper"
)

func setupBankingSQLiteSchema(t *testing.T, db *sql.DB) {
	t.Helper()
	// Enable foreign keys
	_, _ = db.Exec("PRAGMA foreign_keys = ON")

	// Accounts table (SQLite)
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
		t.Fatalf("create accounts table failed: %v", err)
	}

	// Transactions table (SQLite)
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
		t.Fatalf("create transactions table failed: %v", err)
	}
}

func TestAccountRepository_CreateAccount(t *testing.T) {
	db := helper.SetupTestDB(t)
	defer db.Close()
	setupBankingSQLiteSchema(t, db)

	repo := NewAccountRepository(db)

	ctx := context.Background()
	acc := &Account{
		UserID:        "user123",
		AccountNumber: "ACC-001",
		AccountType:   "savings",
		Balance:       0,
		Currency:      "VND",
		Status:        "active",
	}

	err := repo.CreateAccount(ctx, acc)
	assert.NoError(t, err)
	assert.NotZero(t, acc.ID)
	assert.NotNil(t, acc.CreatedAt)
	assert.Equal(t, "user123", acc.UserID)
	assert.Equal(t, "ACC-001", acc.AccountNumber)

	// Verify persisted
	fetched, err := repo.GetAccountByID(ctx, acc.ID)
	assert.NoError(t, err)
	assert.Equal(t, acc.ID, fetched.ID)
	assert.Equal(t, "ACC-001", fetched.AccountNumber)
}

func TestAccountRepository_GetAccountByNumber(t *testing.T) {
	db := helper.SetupTestDB(t)
	defer db.Close()
	setupBankingSQLiteSchema(t, db)

	repo := NewAccountRepository(db)

	// Insert test account
	acc := &Account{
		UserID:        "user456",
		AccountNumber: "ACC-002",
		AccountType:   "checking",
		Balance:       100000,
		Currency:      "VND",
		Status:        "active",
	}
	ctx := context.Background()
	err := repo.CreateAccount(ctx, acc)
	assert.NoError(t, err)

	// Lookup by number
	fetched, err := repo.GetAccountByNumber(ctx, "ACC-002")
	assert.NoError(t, err)
	assert.Equal(t, acc.ID, fetched.ID)
	assert.Equal(t, "user456", fetched.UserID)

	// Not found
	_, err = repo.GetAccountByNumber(ctx, "ACC-999")
	assert.Error(t, err)
}

func TestAccountRepository_GetAccountsByUser(t *testing.T) {
	db := helper.SetupTestDB(t)
	defer db.Close()
	setupBankingSQLiteSchema(t, db)

	repo := NewAccountRepository(db)
	ctx := context.Background()

	// Create two accounts for same user
	for i := 1; i <= 2; i++ {
		acc := &Account{
			UserID:        "user999",
			AccountNumber: fmt.Sprintf("ACC-%03d", i),
			AccountType:   "savings",
			Balance:       float64(i * 1000),
			Currency:      "VND",
			Status:        "active",
		}
		err := repo.CreateAccount(ctx, acc)
		assert.NoError(t, err, "create account %d", i)
	}

	accounts, err := repo.GetAccountsByUser(ctx, "user999")
	assert.NoError(t, err)
	assert.Len(t, accounts, 2)
}

func TestAccountRepository_UpdateBalance(t *testing.T) {
	db := helper.SetupTestDB(t)
	defer db.Close()
	setupBankingSQLiteSchema(t, db)

	repo := NewAccountRepository(db)
	ctx := context.Background()

	acc := &Account{
		UserID:        "userupd",
		AccountNumber: "ACC-UPD",
		AccountType:   "savings",
		Balance:       1000,
		Currency:      "VND",
		Status:        "active",
	}
	err := repo.CreateAccount(ctx, acc)
	assert.NoError(t, err)

	// Update balance
	err = repo.UpdateBalance(ctx, acc.ID, 5000)
	assert.NoError(t, err)

	// Check new balance (initial 1000 + 5000 = 6000)
	updated, err := repo.GetAccountByID(ctx, acc.ID)
	assert.NoError(t, err)
	assert.Equal(t, 6000.0, updated.Balance)
}
