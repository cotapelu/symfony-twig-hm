package banking

import (
	"context"
	"database/sql"
	"errors"
	"testing"

	_ "github.com/mattn/go-sqlite3"

	bm "community.local/modules/Banking/internal/models"
)

// setupTestDB creates an in-memory SQLite database for testing
func setupTestDB(t *testing.T) *sql.DB {
	db, err := sql.Open("sqlite3", ":memory:")
	if err != nil {
		t.Fatalf("Failed to open SQLite: %v", err)
	}

	// Create tables
	schema := `
	CREATE TABLE banking_accounts (
		id INTEGER PRIMARY KEY AUTOINCREMENT,
		user_id TEXT NOT NULL,
		account_number TEXT NOT NULL UNIQUE,
		account_type TEXT NOT NULL,
		balance REAL NOT NULL DEFAULT 0,
		currency TEXT NOT NULL DEFAULT 'VND',
		status TEXT NOT NULL DEFAULT 'active',
		created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
	);
	CREATE TABLE banking_transactions (
		id INTEGER PRIMARY KEY AUTOINCREMENT,
		account_id INTEGER NOT NULL,
		type TEXT NOT NULL,
		amount REAL NOT NULL,
		balance_after REAL NOT NULL,
		description TEXT,
		reference_id TEXT,
		status TEXT NOT NULL DEFAULT 'pending',
		idempotency_key TEXT UNIQUE,
		created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		FOREIGN KEY (account_id) REFERENCES banking_accounts(id)
	);
	`
	_, err = db.Exec(schema)
	if err != nil {
		t.Fatalf("Failed to create schema: %v", err)
	}

	return db
}

// TestNewSQLiteBankingService tests the constructor
func TestNewSQLiteBankingService(t *testing.T) {
	db := setupTestDB(t)
	defer db.Close()

	svc := NewSQLiteBankingService(db)
	assertNotNil(t, svc)
}

// TestSQLiteCreateAccount tests CreateAccount
func TestSQLiteCreateAccount(t *testing.T) {
	db := setupTestDB(t)
	defer db.Close()

	svc := NewSQLiteBankingService(db)
	ctx := context.Background()

	// Test successful account creation
	acc, err := svc.CreateAccount(ctx, "user1", "savings", "VND")
	if err != nil {
		t.Fatalf("CreateAccount failed: %v", err)
	}
	assertNotNil(t, acc)
	assertEqual(t, "user1", acc.UserID)
	assertEqual(t, "savings", acc.AccountType)
	assertEqual(t, "VND", acc.Currency)
	assertEqual(t, "active", acc.Status)
	assertEqual(t, 0.0, acc.Balance)
	assertNotEmpty(t, acc.AccountNumber)
	assertTrue(t, acc.ID > 0)

	// Test with empty currency (service doesn't default, uses empty string)
	acc2, err := svc.CreateAccount(ctx, "user2", "checking", "")
	if err != nil {
		t.Fatalf("CreateAccount with empty currency failed: %v", err)
	}
	assertEqual(t, "", acc2.Currency)
}

// TestSQLiteGetAccount tests GetAccount
func TestSQLiteGetAccount(t *testing.T) {
	db := setupTestDB(t)
	defer db.Close()

	svc := NewSQLiteBankingService(db)
	ctx := context.Background()

	// Create an account first
	acc, err := svc.CreateAccount(ctx, "user1", "savings", "VND")
	if err != nil {
		t.Fatalf("CreateAccount failed: %v", err)
	}

	// Test successful retrieval
	retrieved, err := svc.GetAccount(ctx, acc.ID)
	if err != nil {
		t.Fatalf("GetAccount failed: %v", err)
	}
	assertEqual(t, acc.ID, retrieved.ID)
	assertEqual(t, acc.UserID, retrieved.UserID)
	assertEqual(t, acc.AccountNumber, retrieved.AccountNumber)

	// Test not found
	_, err = svc.GetAccount(ctx, 99999)
	if err == nil {
		t.Fatal("Expected error for non-existent account")
	}
	assertError(t, err, ErrAccountNotFound)
}

// TestSQLiteGetAccountByNumber tests GetAccountByNumber
func TestSQLiteGetAccountByNumber(t *testing.T) {
	db := setupTestDB(t)
	defer db.Close()

	svc := NewSQLiteBankingService(db)
	ctx := context.Background()

	// Create an account first
	acc, err := svc.CreateAccount(ctx, "user1", "savings", "VND")
	if err != nil {
		t.Fatalf("CreateAccount failed: %v", err)
	}

	// Test successful retrieval
	retrieved, err := svc.GetAccountByNumber(ctx, acc.AccountNumber)
	if err != nil {
		t.Fatalf("GetAccountByNumber failed: %v", err)
	}
	assertEqual(t, acc.ID, retrieved.ID)
	assertEqual(t, acc.AccountNumber, retrieved.AccountNumber)

	// Test not found
	_, err = svc.GetAccountByNumber(ctx, "NONEXISTENT")
	if err == nil {
		t.Fatal("Expected error for non-existent account")
	}
	assertError(t, err, ErrAccountNotFound)
}

// TestSQLiteGetUserAccounts tests GetUserAccounts
func TestSQLiteGetUserAccounts(t *testing.T) {
	db := setupTestDB(t)
	defer db.Close()

	svc := NewSQLiteBankingService(db)
	ctx := context.Background()

	// Create multiple accounts for the same user
	_, _ = svc.CreateAccount(ctx, "user1", "savings", "VND")
	_, _ = svc.CreateAccount(ctx, "user1", "checking", "USD")
	// Create account for another user
	_, _ = svc.CreateAccount(ctx, "user2", "savings", "VND")

	// Test retrieval
	accounts, err := svc.GetUserAccounts(ctx, "user1")
	if err != nil {
		t.Fatalf("GetUserAccounts failed: %v", err)
	}
	assertEqual(t, 2, len(accounts))
	for _, a := range accounts {
		assertEqual(t, "user1", a.UserID)
	}

	// Test user with no accounts
	accounts, err = svc.GetUserAccounts(ctx, "nonexistent")
	if err != nil {
		t.Fatalf("GetUserAccounts failed: %v", err)
	}
	assertEqual(t, 0, len(accounts))
}

// TestSQLiteGetBalance tests GetBalance
func TestSQLiteGetBalance(t *testing.T) {
	db := setupTestDB(t)
	defer db.Close()

	svc := NewSQLiteBankingService(db)
	ctx := context.Background()

	// Create an account
	acc, err := svc.CreateAccount(ctx, "user1", "savings", "VND")
	if err != nil {
		t.Fatalf("CreateAccount failed: %v", err)
	}

	// Test initial balance (should be 0)
	balance, err := svc.GetBalance(ctx, acc.ID)
	if err != nil {
		t.Fatalf("GetBalance failed: %v", err)
	}
	assertEqual(t, 0.0, balance)

	// Test not found
	_, err = svc.GetBalance(ctx, 99999)
	if err == nil {
		t.Fatal("Expected error for non-existent account")
	}
	assertError(t, err, ErrAccountNotFound)
}

// TestSQLiteGetTransactions tests GetTransactions
func TestSQLiteGetTransactions(t *testing.T) {
	db := setupTestDB(t)
	defer db.Close()

	svc := NewSQLiteBankingService(db)
	ctx := context.Background()

	// Create an account
	acc, err := svc.CreateAccount(ctx, "user1", "savings", "VND")
	if err != nil {
		t.Fatalf("CreateAccount failed: %v", err)
	}

	// Test with no transactions
	txns, err := svc.GetTransactions(ctx, acc.ID, 10, 0)
	if err != nil {
		t.Fatalf("GetTransactions failed: %v", err)
	}
	assertEqual(t, 0, len(txns))

	// Test default limit (should be 20)
	txns, err = svc.GetTransactions(ctx, acc.ID, 0, 0)
	if err != nil {
		t.Fatalf("GetTransactions failed: %v", err)
	}
	assertEqual(t, 0, len(txns))

	// Test limit > 100 (should be capped at 100)
	txns, err = svc.GetTransactions(ctx, acc.ID, 200, 0)
	if err != nil {
		t.Fatalf("GetTransactions failed: %v", err)
	}
	assertEqual(t, 0, len(txns))
}

// TestSQLiteDeposit tests Deposit
func TestSQLiteDeposit(t *testing.T) {
	db := setupTestDB(t)
	defer db.Close()

	svc := NewSQLiteBankingService(db)
	ctx := context.Background()

	// Create an account
	acc, err := svc.CreateAccount(ctx, "user1", "savings", "VND")
	if err != nil {
		t.Fatalf("CreateAccount failed: %v", err)
	}

	// Test successful deposit
	txn, err := svc.Deposit(ctx, acc.ID, 1000.0, "Test deposit")
	if err != nil {
		t.Fatalf("Deposit failed: %v", err)
	}
	assertNotNil(t, txn)
	assertEqual(t, "deposit", txn.Type)
	assertEqual(t, 1000.0, txn.Amount)
	assertEqual(t, 1000.0, txn.BalanceAfter)
	assertEqual(t, "Test deposit", txn.Description)
	assertEqual(t, "completed", txn.Status)

	// Verify balance updated
	balance, _ := svc.GetBalance(ctx, acc.ID)
	assertEqual(t, 1000.0, balance)

	// Test invalid amount (<= 0)
	_, err = svc.Deposit(ctx, acc.ID, -100, "Invalid")
	if err == nil {
		t.Fatal("Expected error for negative amount")
	}
	assertError(t, err, ErrInvalidAmount)

	_, err = svc.Deposit(ctx, acc.ID, 0, "Invalid")
	if err == nil {
		t.Fatal("Expected error for zero amount")
	}
	assertError(t, err, ErrInvalidAmount)

	// Test inactive account
	// Create another account and deactivate it
	acc2, _ := svc.CreateAccount(ctx, "user2", "savings", "VND")
	// Manually deactivate
	_, _ = db.Exec("UPDATE banking_accounts SET status = 'inactive' WHERE id = ?", acc2.ID)
	
	_, err = svc.Deposit(ctx, acc2.ID, 100, "Test")
	if err == nil {
		t.Fatal("Expected error for inactive account")
	}
	assertError(t, err, ErrAccountNotActive)

	// Test non-existent account
	_, err = svc.Deposit(ctx, 99999, 100, "Test")
	if err == nil {
		t.Fatal("Expected error for non-existent account")
	}
	assertError(t, err, ErrAccountNotFound)
}

// TestSQLiteWithdraw tests Withdraw
func TestSQLiteWithdraw(t *testing.T) {
	db := setupTestDB(t)
	defer db.Close()

	svc := NewSQLiteBankingService(db)
	ctx := context.Background()

	// Create an account and deposit some money
	acc, err := svc.CreateAccount(ctx, "user1", "savings", "VND")
	if err != nil {
		t.Fatalf("CreateAccount failed: %v", err)
	}
	_, _ = svc.Deposit(ctx, acc.ID, 1000.0, "Initial deposit")

	// Test successful withdrawal
	txn, err := svc.Withdraw(ctx, acc.ID, 300.0, "Test withdrawal")
	if err != nil {
		t.Fatalf("Withdraw failed: %v", err)
	}
	assertNotNil(t, txn)
	assertEqual(t, "withdrawal", txn.Type)
	assertEqual(t, 300.0, txn.Amount)
	assertEqual(t, 700.0, txn.BalanceAfter)
	assertEqual(t, "Test withdrawal", txn.Description)

	// Verify balance updated
	balance, _ := svc.GetBalance(ctx, acc.ID)
	assertEqual(t, 700.0, balance)

	// Test invalid amount
	_, err = svc.Withdraw(ctx, acc.ID, -100, "Invalid")
	if err == nil {
		t.Fatal("Expected error for negative amount")
	}
	assertError(t, err, ErrInvalidAmount)

	// Test insufficient balance
	_, err = svc.Withdraw(ctx, acc.ID, 10000.0, "Too much")
	if err == nil {
		t.Fatal("Expected error for insufficient balance")
	}
	assertError(t, err, ErrInsufficientBalance)

	// Test inactive account
	acc2, _ := svc.CreateAccount(ctx, "user2", "savings", "VND")
	_, _ = svc.Deposit(ctx, acc2.ID, 500.0, "Deposit")
	_, _ = db.Exec("UPDATE banking_accounts SET status = 'inactive' WHERE id = ?", acc2.ID)
	
	_, err = svc.Withdraw(ctx, acc2.ID, 100, "Test")
	if err == nil {
		t.Fatal("Expected error for inactive account")
	}
	assertError(t, err, ErrAccountNotActive)

	// Test non-existent account
	_, err = svc.Withdraw(ctx, 99999, 100, "Test")
	if err == nil {
		t.Fatal("Expected error for non-existent account")
	}
	assertError(t, err, ErrAccountNotFound)
}

// TestSQLiteTransfer tests Transfer
func TestSQLiteTransfer(t *testing.T) {
	db := setupTestDB(t)
	defer db.Close()

	svc := NewSQLiteBankingService(db)
	ctx := context.Background()

	// Create two accounts and deposit money in source
	fromAcc, err := svc.CreateAccount(ctx, "user1", "savings", "VND")
	if err != nil {
		t.Fatalf("CreateAccount failed: %v", err)
	}
	_, _ = svc.Deposit(ctx, fromAcc.ID, 1000.0, "Initial deposit")

	toAcc, err := svc.CreateAccount(ctx, "user1", "checking", "VND")
	if err != nil {
		t.Fatalf("CreateAccount failed: %v", err)
	}

	// Test successful transfer
	req := &bm.TransferRequest{
		FromAccount: fromAcc.AccountNumber,
		ToAccount:   toAcc.AccountNumber,
		Amount:      300.0,
		Description: "Test transfer",
	}
	txn, err := svc.Transfer(ctx, req)
	if err != nil {
		t.Fatalf("Transfer failed: %v", err)
	}
	assertNotNil(t, txn)
	assertEqual(t, "transfer", txn.Type)
	assertEqual(t, 300.0, txn.Amount)
	assertEqual(t, 700.0, txn.BalanceAfter)
	assertEqual(t, toAcc.AccountNumber, txn.ReferenceID)

	// Verify balances
	fromBalance, _ := svc.GetBalance(ctx, fromAcc.ID)
	toBalance, _ := svc.GetBalance(ctx, toAcc.ID)
	assertEqual(t, 700.0, fromBalance)
	assertEqual(t, 300.0, toBalance)

	// Test invalid amount
	req.Amount = -100
	_, err = svc.Transfer(ctx, req)
	if err == nil {
		t.Fatal("Expected error for negative amount")
	}
	assertError(t, err, ErrInvalidAmount)

	// Test source account not found
	req.Amount = 100
	req.FromAccount = "NONEXISTENT"
	req.ToAccount = toAcc.AccountNumber
	_, err = svc.Transfer(ctx, req)
	if err == nil {
		t.Fatal("Expected error for non-existent source account")
	}
	assertError(t, err, ErrAccountNotFound)

	// Test destination account not found
	req.FromAccount = fromAcc.AccountNumber
	req.ToAccount = "NONEXISTENT"
	_, err = svc.Transfer(ctx, req)
	if err == nil {
		t.Fatal("Expected error for non-existent destination account")
	}
	assertError(t, err, ErrAccountNotFound)

	// Test insufficient balance
	// Add more funds to cover the withdraw
	_, _ = svc.Deposit(ctx, fromAcc.ID, 500.0, "Add funds")
	// Now balance is 700 + 500 = 1200
	// Drain the account first (leave only 50)
	txn, withdrawErr := svc.Withdraw(ctx, fromAcc.ID, 1150.0, "Drain")
	if withdrawErr != nil {
		t.Fatalf("Withdraw failed: %v", withdrawErr)
	}
	t.Logf("After withdraw, balance after: %v", txn.BalanceAfter)
	balance, _ := svc.GetBalance(ctx, fromAcc.ID)
	t.Logf("Account balance after withdraw: %v", balance)
	req.FromAccount = fromAcc.AccountNumber
	req.ToAccount = toAcc.AccountNumber
	req.Amount = 100.0
	_, err = svc.Transfer(ctx, req)
	if err == nil {
		t.Fatal("Expected error for insufficient balance")
	}
	assertError(t, err, ErrInsufficientBalance)

	// Test inactive source account
	acc3, _ := svc.CreateAccount(ctx, "user2", "savings", "VND")
	_, _ = svc.Deposit(ctx, acc3.ID, 500.0, "Deposit")
	result, err := db.Exec("UPDATE banking_accounts SET status = 'inactive' WHERE id = ?", acc3.ID)
	if err != nil {
		t.Fatalf("Failed to update account status: %v", err)
	}
	rows, _ := result.RowsAffected()
	if rows == 0 {
		t.Fatalf("No rows affected for account %d", acc3.ID)
	}
	
	// Verify the status was actually updated
	var status string
	err = db.QueryRow("SELECT status FROM banking_accounts WHERE id = ?", acc3.ID).Scan(&status)
	if err != nil {
		t.Fatalf("Failed to query status: %v", err)
	}
	if status != "inactive" {
		t.Fatalf("Expected status inactive, got %s", status)
	}
	
	req.FromAccount = acc3.AccountNumber
	req.ToAccount = toAcc.AccountNumber
	
	// Debug: check the status of acc3
	var acc3Status string
	err = db.QueryRow("SELECT status FROM banking_accounts WHERE id = ?", acc3.ID).Scan(&acc3Status)
	t.Logf("acc3 status before transfer: %s", acc3Status)
	
	_, err = svc.Transfer(ctx, req)
	if err == nil {
		t.Fatal("Expected error for inactive source account")
	}
	t.Logf("Transfer error: %v", err)
	assertError(t, err, ErrAccountNotActive)

	// Test inactive destination account
	// Add funds to fromAcc so balance check passes
	_, _ = svc.Deposit(ctx, fromAcc.ID, 200.0, "Add funds for inactive dest test")
	result, err = db.Exec("UPDATE banking_accounts SET status = 'active' WHERE id = ?", acc3.ID)
	if err != nil {
		t.Fatalf("Failed to update account status: %v", err)
	}
	result, err = db.Exec("UPDATE banking_accounts SET status = 'inactive' WHERE id = ?", toAcc.ID)
	if err != nil {
		t.Fatalf("Failed to update account status: %v", err)
	}
	rows, _ = result.RowsAffected()
	if rows == 0 {
		t.Fatalf("No rows affected for account %d", toAcc.ID)
	}
	
	// Verify the status was actually updated
	var toAccStatus string
	err = db.QueryRow("SELECT status FROM banking_accounts WHERE id = ?", toAcc.ID).Scan(&toAccStatus)
	if err != nil {
		t.Fatalf("Failed to query status: %v", err)
	}
	if toAccStatus != "inactive" {
		t.Fatalf("Expected status inactive, got %s", toAccStatus)
	}
	
	req.FromAccount = fromAcc.AccountNumber
	req.ToAccount = toAcc.AccountNumber
	_, err = svc.Transfer(ctx, req)
	if err == nil {
		t.Fatal("Expected error for inactive destination account")
	}
	assertError(t, err, ErrAccountNotActive)
}

// TestGenerateAccountNumber tests generateAccountNumber
func TestGenerateAccountNumber(t *testing.T) {
	// This tests the private function indirectly through CreateAccount
	db := setupTestDB(t)
	defer db.Close()

	svc := NewSQLiteBankingService(db)
	ctx := context.Background()

	acc1, _ := svc.CreateAccount(ctx, "user1", "savings", "VND")
	acc2, _ := svc.CreateAccount(ctx, "user2", "savings", "VND")

	assertNotEmpty(t, acc1.AccountNumber)
	assertNotEmpty(t, acc2.AccountNumber)
	assertNotEqual(t, acc1.AccountNumber, acc2.AccountNumber, "Account numbers should be unique")
}

// Helper assertion functions
func assertNotNil(t *testing.T, val interface{}) {
	if val == nil {
		t.Helper()
		t.Fatal("Expected non-nil value")
	}
}

func assertEqual(t *testing.T, expected, actual interface{}) {
	t.Helper()
	if expected != actual {
		t.Fatalf("Expected %v, got %v", expected, actual)
	}
}

func assertNotEqual(t *testing.T, expected, actual interface{}, msg string) {
	t.Helper()
	if expected == actual {
		t.Fatal(msg)
	}
}

func assertNotEmpty(t *testing.T, val string) {
	t.Helper()
	if val == "" {
		t.Fatal("Expected non-empty string")
	}
}

func assertTrue(t *testing.T, condition bool) {
	t.Helper()
	if !condition {
		t.Fatal("Expected true")
	}
}

func assertError(t *testing.T, err error, expectedError error) {
	t.Helper()
	if err == nil {
		t.Fatal("Expected error but got nil")
	}
	if !errors.Is(err, expectedError) {
		t.Fatalf("Expected error %v, got %v", expectedError, err)
	}
}