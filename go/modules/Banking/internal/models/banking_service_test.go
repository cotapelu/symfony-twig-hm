package banking

import (
	"context"
	"database/sql"
	"testing"

	_ "github.com/mattn/go-sqlite3"
	"github.com/stretchr/testify/assert"

	"community.local/testing/helper"
)

// mockEventHandler implements EventHandler for testing
type mockEventHandler struct {
	emitCalled bool
	lastEvent  interface{}
}

func (m *mockEventHandler) EmitEvent(ctx context.Context, eventType string, data interface{}) error {
	m.emitCalled = true
	m.lastEvent = data
	return nil
}

func setupBankingDB(t *testing.T) *sql.DB {
	t.Helper()
	db := helper.SetupTestDB(t)
	// Enable foreign keys
	_, _ = db.Exec("PRAGMA foreign_keys = ON")

	// Accounts table
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

	// Transactions table
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
	return db
}

func TestBankingService_CreateAccount(t *testing.T) {
	db := setupBankingDB(t)
	defer db.Close()

	eventHandler := &mockEventHandler{}
	svc := NewBankingService(db, eventHandler)

	ctx := context.Background()
	acc, err := svc.CreateAccount(ctx, "user123", "savings", "VND")
	assert.NoError(t, err)
	assert.NotZero(t, acc.ID)
	assert.Equal(t, "user123", acc.UserID)
	assert.Equal(t, "savings", acc.AccountType)
	assert.Equal(t, "VND", acc.Currency)
	assert.Equal(t, 0.0, acc.Balance)
	assert.Equal(t, "active", acc.Status)
	assert.NotNil(t, acc.AccountNumber)
	assert.NotNil(t, acc.CreatedAt)

	// Verify persisted
	fetched, err := svc.GetAccount(ctx, acc.ID)
	assert.NoError(t, err)
	assert.Equal(t, acc.ID, fetched.ID)
}

func TestBankingService_GetAccount_NotFound(t *testing.T) {
	db := setupBankingDB(t)
	defer db.Close()
	svc := NewBankingService(db, nil)

	ctx := context.Background()
	_, err := svc.GetAccount(ctx, 99999)
	assert.Error(t, err)
}

func TestBankingService_GetAccountByNumber_NotFound(t *testing.T) {
	db := setupBankingDB(t)
	defer db.Close()
	svc := NewBankingService(db, nil)

	ctx := context.Background()
	_, err := svc.GetAccountByNumber(ctx, "ACC-NOTFOUND")
	assert.Error(t, err)
}

func TestBankingService_GetUserAccounts(t *testing.T) {
	db := setupBankingDB(t)
	defer db.Close()
	svc := NewBankingService(db, nil)

	ctx := context.Background()
	// Create two accounts for same user
	_, err := svc.CreateAccount(ctx, "user999", "savings", "VND")
	assert.NoError(t, err)
	_, err = svc.CreateAccount(ctx, "user999", "checking", "USD")
	assert.NoError(t, err)

	accounts, err := svc.GetUserAccounts(ctx, "user999")
	assert.NoError(t, err)
	assert.Len(t, accounts, 2)
}

func TestBankingService_Deposit_Success(t *testing.T) {
	db := setupBankingDB(t)
	defer db.Close()
	eventHandler := &mockEventHandler{}
	svc := NewBankingService(db, eventHandler)

	ctx := context.Background()
	acc, err := svc.CreateAccount(ctx, "user_dep", "savings", "VND")
	assert.NoError(t, err)
	initialBalance := acc.Balance

	txn, err := svc.Deposit(ctx, acc.ID, 5000.0, "Test deposit")
	assert.NoError(t, err)
	assert.NotZero(t, txn.ID)
	assert.Equal(t, "deposit", txn.Type)
	assert.Equal(t, 5000.0, txn.Amount)
	assert.Greater(t, txn.BalanceAfter, initialBalance)
	assert.Equal(t, "Test deposit", txn.Description)
	assert.Equal(t, "completed", txn.Status)
	assert.True(t, eventHandler.emitCalled)
	assert.IsType(t, TransactionEvent{}, eventHandler.lastEvent)

	// Verify balance updated
	updatedAcc, _ := svc.GetAccount(ctx, acc.ID)
	assert.Equal(t, initialBalance+5000.0, updatedAcc.Balance)
}

func TestBankingService_Deposit_InvalidAmount(t *testing.T) {
	db := setupBankingDB(t)
	defer db.Close()
	svc := NewBankingService(db, nil)

	ctx := context.Background()
	acc, err := svc.CreateAccount(ctx, "user_dep2", "savings", "VND")
	assert.NoError(t, err)

	_, err = svc.Deposit(ctx, acc.ID, -100, "Negative")
	assert.Error(t, err)
	assert.Contains(t, err.Error(), "positive")

	_, err = svc.Deposit(ctx, acc.ID, 0, "Zero")
	assert.Error(t, err)
}

func TestBankingService_Deposit_AccountNotFound(t *testing.T) {
	db := setupBankingDB(t)
	defer db.Close()
	svc := NewBankingService(db, nil)

	ctx := context.Background()
	_, err := svc.Deposit(ctx, 99999, 100, "Test")
	assert.Error(t, err)
}

func TestBankingService_Withdraw_Success(t *testing.T) {
	db := setupBankingDB(t)
	defer db.Close()
	eventHandler := &mockEventHandler{}
	svc := NewBankingService(db, eventHandler)

	ctx := context.Background()
	acc, err := svc.CreateAccount(ctx, "user_wdr", "savings", "VND")
	assert.NoError(t, err)
	// Initial balance 0, need to deposit first to have funds
	_, err = svc.Deposit(ctx, acc.ID, 10000.0, "Initial")
	assert.NoError(t, err)

	txn, err := svc.Withdraw(ctx, acc.ID, 3000.0, "Test withdraw")
	assert.NoError(t, err)
	assert.NotZero(t, txn.ID)
	assert.Equal(t, "withdraw", txn.Type)
	assert.Equal(t, -3000.0, txn.Amount) // withdraw amounts are negative
	assert.True(t, eventHandler.emitCalled)

	// Verify balance reduced
	updatedAcc, _ := svc.GetAccount(ctx, acc.ID)
	assert.Equal(t, 7000.0, updatedAcc.Balance)
}

func TestBankingService_Withdraw_InvalidAmount(t *testing.T) {
	db := setupBankingDB(t)
	defer db.Close()
	svc := NewBankingService(db, nil)

	ctx := context.Background()
	acc, err := svc.CreateAccount(ctx, "user_wdr3", "savings", "VND")
	assert.NoError(t, err)
	_, err = svc.Deposit(ctx, acc.ID, 100, "Init")
	assert.NoError(t, err)

	_, err = svc.Withdraw(ctx, acc.ID, -50, "Negative")
	assert.Error(t, err)
	assert.Contains(t, err.Error(), "positive")
}

func TestBankingService_Withdraw_InsufficientBalance(t *testing.T) {
	db := setupBankingDB(t)
	defer db.Close()
	svc := NewBankingService(db, nil)

	ctx := context.Background()
	acc, err := svc.CreateAccount(ctx, "user_wdr2", "savings", "VND")
	assert.NoError(t, err)
	// Balance 0

	_, err = svc.Withdraw(ctx, acc.ID, 100, "Too much")
	assert.Error(t, err)
	assert.Contains(t, err.Error(), "insufficient balance")
}

func TestBankingService_Transfer_Success(t *testing.T) {
	db := setupBankingDB(t)
	defer db.Close()
	eventHandler := &mockEventHandler{}
	svc := NewBankingService(db, eventHandler)

	ctx := context.Background()
	fromAcc, err := svc.CreateAccount(ctx, "user_xfer1", "savings", "VND")
	assert.NoError(t, err)
	toAcc, err := svc.CreateAccount(ctx, "user_xfer2", "savings", "VND")
	assert.NoError(t, err)

	// Fund source account
	_, err = svc.Deposit(ctx, fromAcc.ID, 10000.0, "Funding")
	assert.NoError(t, err)

	req := &TransferRequest{
		FromAccount: fromAcc.AccountNumber,
		ToAccount:   toAcc.AccountNumber,
		Amount:      2500.0,
		Description: "Test transfer",
	}

	txn, err := svc.Transfer(ctx, req)
	assert.NoError(t, err)
	assert.NotZero(t, txn.ID)
	assert.Equal(t, "transfer", txn.Type)
	assert.Equal(t, -2500.0, txn.Amount)
	assert.True(t, eventHandler.emitCalled)

	// Verify balances
	fromUpdated, _ := svc.GetAccount(ctx, fromAcc.ID)
	toUpdated, _ := svc.GetAccount(ctx, toAcc.ID)
	assert.Equal(t, 7500.0, fromUpdated.Balance)
	assert.Equal(t, 2500.0, toUpdated.Balance)
}

func TestBankingService_Transfer_SameAccount(t *testing.T) {
	db := setupBankingDB(t)
	defer db.Close()
	svc := NewBankingService(db, nil)

	ctx := context.Background()
	acc, err := svc.CreateAccount(ctx, "user_xfersame", "savings", "VND")
	assert.NoError(t, err)

	req := &TransferRequest{
		FromAccount: acc.AccountNumber,
		ToAccount:   acc.AccountNumber,
		Amount:      100,
	}

	_, err = svc.Transfer(ctx, req)
	assert.Error(t, err)
	assert.Contains(t, err.Error(), "same account")
}

func TestBankingService_Transfer_InvalidAmount(t *testing.T) {
	db := setupBankingDB(t)
	defer db.Close()
	svc := NewBankingService(db, nil)

	ctx := context.Background()
	from, err := svc.CreateAccount(ctx, "user_xferamt", "savings", "VND")
	assert.NoError(t, err)
	to, err := svc.CreateAccount(ctx, "user_xferto", "savings", "VND")
	assert.NoError(t, err)

	req := &TransferRequest{
		FromAccount: from.AccountNumber,
		ToAccount:   to.AccountNumber,
		Amount:      -100,
	}

	_, err = svc.Transfer(ctx, req)
	assert.Error(t, err)
	assert.Contains(t, err.Error(), "positive")
}

func TestBankingService_Transfer_InsufficientBalance(t *testing.T) {
	db := setupBankingDB(t)
	defer db.Close()
	svc := NewBankingService(db, nil)

	ctx := context.Background()
	from, err := svc.CreateAccount(ctx, "user_xferins", "savings", "VND")
	assert.NoError(t, err)
	to, err := svc.CreateAccount(ctx, "user_xferto2", "savings", "VND")
	assert.NoError(t, err)
	_, err = svc.Deposit(ctx, from.ID, 500, "Initial")
	assert.NoError(t, err)

	req := &TransferRequest{
		FromAccount: from.AccountNumber,
		ToAccount:   to.AccountNumber,
		Amount:      1000,
	}

	_, err = svc.Transfer(ctx, req)
	assert.Error(t, err)
	assert.Contains(t, err.Error(), "insufficient balance")

	// Verify balances unchanged
	fromAcc, _ := svc.GetAccount(ctx, from.ID)
	toAcc, _ := svc.GetAccount(ctx, to.ID)
	assert.Equal(t, 500.0, fromAcc.Balance)
	assert.Equal(t, 0.0, toAcc.Balance)
}

func TestBankingService_Transfer_ToAccountNotFound(t *testing.T) {
	db := setupBankingDB(t)
	defer db.Close()
	svc := NewBankingService(db, nil)

	ctx := context.Background()
	from, err := svc.CreateAccount(ctx, "user_xfertoNF", "savings", "VND")
	assert.NoError(t, err)
	_, err = svc.Deposit(ctx, from.ID, 1000, "Initial")
	assert.NoError(t, err)

	req := &TransferRequest{
		FromAccount: from.AccountNumber,
		ToAccount:   "ACC-NOTEXIST",
		Amount:      100,
	}

	_, err = svc.Transfer(ctx, req)
	assert.Error(t, err)
	assert.Contains(t, err.Error(), "destination account not found")

	// Verify source balance unchanged
	fromAcc, _ := svc.GetAccount(ctx, from.ID)
	assert.Equal(t, 1000.0, fromAcc.Balance)
}

func TestBankingService_GetBalance_Success(t *testing.T) {
	db := setupBankingDB(t)
	defer db.Close()
	svc := NewBankingService(db, nil)

	ctx := context.Background()
	acc, err := svc.CreateAccount(ctx, "user_bal", "savings", "VND")
	assert.NoError(t, err)
	_, err = svc.Deposit(ctx, acc.ID, 5000, "Deposit")
	assert.NoError(t, err)

	balance, err := svc.GetBalance(ctx, acc.ID)
	assert.NoError(t, err)
	assert.Equal(t, 5000.0, balance)
}

func TestBankingService_GetBalance_AccountNotFound(t *testing.T) {
	db := setupBankingDB(t)
	defer db.Close()
	svc := NewBankingService(db, nil)

	ctx := context.Background()
	_, err := svc.GetBalance(ctx, 99999)
	assert.Error(t, err)
}

func TestBankingService_GetTransactions(t *testing.T) {
	db := setupBankingDB(t)
	defer db.Close()
	svc := NewBankingService(db, nil)

	ctx := context.Background()
	acc, err := svc.CreateAccount(ctx, "user_txn", "savings", "VND")
	assert.NoError(t, err)

	// Create multiple transactions
	_, err = svc.Deposit(ctx, acc.ID, 1000, "Dep1")
	assert.NoError(t, err)
	_, err = svc.Withdraw(ctx, acc.ID, 200, "Wd1")
	assert.NoError(t, err)
	_, err = svc.Deposit(ctx, acc.ID, 300, "Dep2")
	assert.NoError(t, err)

	txns, err := svc.GetTransactions(ctx, acc.ID, 10, 0)
	assert.NoError(t, err)
	assert.Len(t, txns, 3) // all transactions
	// They are ordered DESC by created_at, so latest first
	types := make([]string, len(txns))
	for i, tx := range txns {
		types[i] = tx.Type
	}
	assert.ElementsMatch(t, []string{"deposit", "withdraw", "deposit"}, types)
}
