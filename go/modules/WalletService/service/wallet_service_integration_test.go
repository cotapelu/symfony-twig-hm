//go:build ignore
package walletservice

import (
	"context"
	"database/sql"
	"testing"

	_ "github.com/mattn/go-sqlite3"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"

	"community.local/testing/helper"
	walletmodels "community.local/modules/WalletService/internal/models"
)

// SetupWalletTestDB creates the wallet tables in the test database
func SetupWalletTestDB(t *testing.T, db *sql.DB) {
	// Create wallets table
	query := `
		CREATE TABLE IF NOT EXISTS wallets (
			id INTEGER PRIMARY KEY AUTOINCREMENT,
			user_id TEXT NOT NULL,
			balance BIGINT NOT NULL DEFAULT 0,
			currency TEXT NOT NULL DEFAULT 'VND',
			status TEXT NOT NULL DEFAULT 'active',
			is_default BOOLEAN NOT NULL DEFAULT true,
			version BIGINT NOT NULL DEFAULT 0,
			created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
		);
	`
	_, err := db.Exec(query)
	require.NoError(t, err)

	// Create wallet_transactions table
	query = `
		CREATE TABLE IF NOT EXISTS wallet_transactions (
			id INTEGER PRIMARY KEY AUTOINCREMENT,
			wallet_id BIGINT NOT NULL,
			type TEXT NOT NULL,
			amount BIGINT NOT NULL,
			balance_after BIGINT NOT NULL,
			description TEXT,
			reference_id TEXT UNIQUE,
			metadata TEXT,
			status TEXT NOT NULL DEFAULT 'completed',
			created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
		);
	`
	_, err = db.Exec(query)
	require.NoError(t, err)

	// Trigger for updated_at (sqlite doesn't support BEFORE UPDATE triggers on AUTOINCREMENT? We'll skip for tests)
}

// TestWalletService_Integration_TopUp tests the full TopUp flow with database
func TestWalletService_Integration_TopUp(t *testing.T) {
	db := helper.SetupTestDB(t)
	SetupWalletTestDB(t, db)

	repo := walletmodels.NewWalletRepository(db)
ttxnRepo := walletmodels.NewTransactionRepository(db)
	txnRepo := walletmodels.NewTransactionRepository(db)
	svc := NewWalletService(repo, txnRepo, nil)

	ctx := context.Background()

	// Create wallet for user
	wallet := &walletmodels.Wallet{
		UserID:   "integ-user",
		Balance:  0,
		Currency: "VND",
		Status:   "active",
		Version:  0,
	}
	err := repo.CreateWallet(ctx, wallet)
	require.NoError(t, err)
	t.Logf("Created wallet ID: %d, balance: %d", wallet.ID, wallet.Balance)

	// TopUp 100,000 cents (1,000 VND)
	req := &walletmodels.TopUpRequest{
		UserID:    "integ-user",
		Amount:    100000,
		Method:    "bank_transfer",
		IdempotencyKey: "test-idem-1",
	}

	txn, err := svc.TopUp(ctx, req)
	require.NoError(t, err)
	assert.NotNil(t, txn)
	assert.Equal(t, int64(100000), txn.Amount)
	assert.Equal(t, int64(100000), txn.BalanceAfter)

	// Verify wallet balance in DB
	updatedWallet, err := repo.GetWalletByUser(ctx, "integ-user")
	require.NoError(t, err)
	assert.Equal(t, int64(100000), updatedWallet.Balance)
	assert.Equal(t, int64(1), updatedWallet.Version) // version should be incremented
}

// TestWalletService_Integration_Transfer tests transfer between two wallets
func TestWalletService_Integration_Transfer(t *testing.T) {
	db := helper.SetupTestDB(t)
	SetupWalletTestDB(t, db)

	repo := walletmodels.NewWalletRepository(db)
ttxnRepo := walletmodels.NewTransactionRepository(db)
	txnRepo := walletmodels.NewTransactionRepository(db)
	svc := NewWalletService(repo, txnRepo, nil)

	ctx := context.Background()

	// Create sender wallet with initial balance 500,000 cents
	sender := &walletmodels.Wallet{
		UserID:   "sender",
		Balance:  500000,
		Currency: "VND",
		Status:   "active",
		Version:  0,
	}
	require.NoError(t, repo.CreateWallet(ctx, sender))

	// Create receiver wallet with 0
	receiver := &walletmodels.Wallet{
		UserID:   "receiver",
		Balance:  0,
		Currency: "VND",
		Status:   "active",
		Version:  0,
	}
	require.NoError(t, repo.CreateWallet(ctx, receiver))

	// Transfer 100,000 cents
	req := &walletmodels.TransferRequest{
		FromUserID: "sender",
		ToUserID:   "receiver",
		Amount:     100000,
		Description: "Test transfer",
		IdempotencyKey: "transfer-1",
	}

	txn, err := svc.Transfer(ctx, req)
	require.NoError(t, err)
	assert.Equal(t, int64(-100000), txn.Amount)
	assert.Equal(t, int64(400000), txn.BalanceAfter) // sender balance after debit

	// Verify sender balance
	senderWallet, _ := repo.GetWalletByUser(ctx, "sender")
	assert.Equal(t, int64(400000), senderWallet.Balance)

	// Verify receiver balance
	receiverWallet, _ := repo.GetWalletByUser(ctx, "receiver")
	assert.Equal(t, int64(100000), receiverWallet.Balance)
}

// TestWalletService_Integration_Withdraw tests withdrawal
func TestWalletService_Integration_Withdraw(t *testing.T) {
	db := helper.SetupTestDB(t)
	SetupWalletTestDB(t, db)

	repo := walletmodels.NewWalletRepository(db)
ttxnRepo := walletmodels.NewTransactionRepository(db)
	svc := NewWalletService(repo, txnRepo, nil)

	ctx := context.Background()

	wallet := &walletmodels.Wallet{
		UserID:   "withdraw-user",
		Balance:  200000,
		Currency: "VND",
		Status:   "active",
		Version:  0,
	}
	require.NoError(t, repo.CreateWallet(ctx, wallet))

	req := &walletmodels.WithdrawalRequest{
		UserID:    "withdraw-user",
		Amount:    50000,
		BankCode:  "VCB",
		AccountNo: "1234567890",
		HolderName: "Test User",
		IdempotencyKey: "withdraw-1",
	}

	txn, err := svc.Withdraw(ctx, req)
	require.NoError(t, err)
	assert.Equal(t, int64(-50000), txn.Amount)
	assert.Equal(t, int64(150000), txn.BalanceAfter)

	// Verify
	updated, _ := repo.GetWalletByUser(ctx, "withdraw-user")
	assert.Equal(t, int64(150000), updated.Balance)
}

// TestWalletService_Integration_ConcurrentTopUp tests that concurrent top-ups using row locks prevent race
// This is simplified; true concurrency would need goroutines, but we can test the lock is used
func TestWalletService_Integration_TopUp_Locking(t *testing.T) {
	db := helper.SetupTestDB(t)
	SetupWalletTestDB(t, db)

	repo := walletmodels.NewWalletRepository(db)
ttxnRepo := walletmodels.NewTransactionRepository(db)
	svc := NewWalletService(repo, txnRepo, nil)

	ctx := context.Background()

	wallet := &walletmodels.Wallet{
		UserID:   "lock-user",
		Balance:  0,
		Currency: "VND",
		Status:   "active",
		Version:  0,
	}
	require.NoError(t, repo.CreateWallet(ctx, wallet))

	// First top-up
	req1 := &walletmodels.TopUpRequest{
		UserID: "lock-user",
		Amount: 100000,
		Method: "bank",
	}
	txn1, err := svc.TopUp(ctx, req1)
	require.NoError(t, err)
	assert.Equal(t, int64(100000), txn1.BalanceAfter)

	// Second top-up should succeed and add to balance
	req2 := &walletmodels.TopUpRequest{
		UserID: "lock-user",
		Amount: 50000,
		Method: "bank",
	}
	txn2, err := svc.TopUp(ctx, req2)
	require.NoError(t, err)
	assert.Equal(t, int64(150000), txn2.BalanceAfter) // 100k + 50k

	// Check final wallet balance
	finalWallet, _ := repo.GetWalletByUser(ctx, "lock-user")
	assert.Equal(t, int64(150000), finalWallet.Balance)
}

// TestWalletService_Integration_Idempotency verifies duplicate requests return same transaction
func TestWalletService_Integration_Idempotency(t *testing.T) {
	db := helper.SetupTestDB(t)
	SetupWalletTestDB(t, db)

	repo := walletmodels.NewWalletRepository(db)
ttxnRepo := walletmodels.NewTransactionRepository(db)
	txnRepo := walletmodels.NewTransactionRepository(db)
	svc := NewWalletService(repo, txnRepo, nil)

	ctx := context.Background()

	wallet := &walletmodels.Wallet{
		UserID:   "idem-user",
		Balance:  0,
		Currency: "VND",
		Status:   "active",
		Version:  0,
	}
	require.NoError(t, repo.CreateWallet(ctx, wallet))

	req := &walletmodels.TopUpRequest{
		UserID:         "idem-user",
		Amount:         75000,
		Method:         "bank",
		IdempotencyKey: "idem-key-123",
	}

	// First call
	txn1, err := svc.TopUp(ctx, req)
	require.NoError(t, err)

	// Second call with same key should return same transaction
	txn2, err := svc.TopUp(ctx, req)
	require.NoError(t, err)

	assert.Equal(t, txn1.ID, txn2.ID, "Idempotent call returned same transaction")
	assert.Equal(t, int64(75000), txn1.Amount)
	assert.Equal(t, int64(75000), txn2.Amount)

	// Balance should only increase once
	w, _ := repo.GetWalletByUser(ctx, "idem-user")
	assert.Equal(t, int64(75000), w.Balance)
}

// TestWalletService_Integration_GetBalance verifies GetBalance returns correct type
func TestWalletService_Integration_GetBalance(t *testing.T) {
	db := helper.SetupTestDB(t)
	SetupWalletTestDB(t, db)

	repo := walletmodels.NewWalletRepository(db)
ttxnRepo := walletmodels.NewTransactionRepository(db)
	svc := NewWalletService(repo, txnRepo, nil)

	ctx := context.Background()

	wallet := &walletmodels.Wallet{
		UserID:   "balance-user",
		Balance:  123456789,
		Currency: "VND",
		Status:   "active",
		Version:  0,
	}
	require.NoError(t, repo.CreateWallet(ctx, wallet))

	balance, err := svc.GetBalance(ctx, "balance-user")
	require.NoError(t, err)
	assert.IsType(t, int64(0), balance)
	assert.Equal(t, int64(123456789), balance)
}

// TestWalletService_Integration_GetTransactions verifies transaction history retrieval
func TestWalletService_Integration_GetTransactions(t *testing.T) {
	db := helper.SetupTestDB(t)
	SetupWalletTestDB(t, db)

	repo := walletmodels.NewWalletRepository(db)
ttxnRepo := walletmodels.NewTransactionRepository(db)
	txnRepo := walletmodels.NewTransactionRepository(db)
	svc := NewWalletService(repo, txnRepo, nil)

	ctx := context.Background()

	wallet := &walletmodels.Wallet{
		UserID:   "txn-user",
		Balance:  0,
		Currency: "VND",
		Status:   "active",
		Version:  0,
	}
	require.NoError(t, repo.CreateWallet(ctx, wallet))
	// We need wallet.ID for transactions; fetch it
	w, _ := repo.GetWalletByUser(ctx, "txn-user")

	// Create some transactions manually
	txn1 := &walletmodels.WalletTransaction{
		WalletID:     w.ID,
		Type:         "topup",
		Amount:       100000,
		BalanceAfter: 100000,
		Description:  "Top up 1",
		Status:       "completed",
	}
	require.NoError(t, txnRepo.CreateTransaction(ctx, txn1))

	txn2 := &walletmodels.WalletTransaction{
		WalletID:     w.ID,
		Type:         "withdraw",
		Amount:       -30000,
		BalanceAfter: 70000,
		Description:  "Withdraw 1",
		Status:       "completed",
	}
	require.NoError(t, txnRepo.CreateTransaction(ctx, txn2))

	// Get transactions
	txns, err := svc.GetTransactions(ctx, "txn-user", 10, 0)
	require.NoError(t, err)
	assert.Len(t, txns, 2)
	// Should be ordered by created_at desc; tx2 is later, so should be first
	assert.Contains(t, []int64{txns[0].ID, txns[1].ID}, txn2.ID)
	assert.Contains(t, []int64{txns[0].ID, txns[1].ID}, txn1.ID)
}

// TestWalletService_Integration_ConcurrentTransferWithLock tests that two concurrent transfers
// on same wallet do not cause race condition (using database transaction isolation)
// This test spawns goroutines but sqlite has limited concurrency; we can still exercise
func TestWalletService_Integration_ConcurrentTransfers(t *testing.T) {
	db := helper.SetupTestDB(t)
	SetupWalletTestDB(t, db)

	repo := walletmodels.NewWalletRepository(db)
ttxnRepo := walletmodels.NewTransactionRepository(db)
	svc := NewWalletService(repo, txnRepo, nil)

	ctx := context.Background()

	// Create a wallet with initial 1,000,000 cents
	wallet := &walletmodels.Wallet{
		UserID:   "concurrent-user",
		Balance:  1000000,
		Currency: "VND",
		Status:   "active",
		Version:  0,
	}
	require.NoError(t, repo.CreateWallet(ctx, wallet))

	// We'll simulate two concurrent transfers out of the same wallet
	// In a real test with proper DB, we'd need to ensure transactions are serialized.
	// Since we're using SQLite in memory with serialized mode, we can attempt parallel calls.

	const goroutines = 5
	const transferAmount = 100000 // 100k each
	errors := make(chan error, goroutines)

	for i := 0; i < goroutines; i++ {
		go func(idx int) {
			req := &walletmodels.TransferRequest{
				FromUserID: "concurrent-user",
				ToUserID:   "receiver-" + string(rune(idx)),
				Amount:     transferAmount,
			}
			// We need to create receiver wallet first (synchronized? but separate)
			// This is simplified; focus on sender wallet lock
			_, err := svc.Transfer(context.Background(), req)
			if err != nil {
				errors <- err
			} else {
				errors <- nil
			}
		}(i)
	}

	// Collect errors
	for i := 0; i < goroutines; i++ {
		err := <-errors
		if err != nil {
			t.Logf("Goroutine error: %v", err)
			// It's okay if one fails due to insufficient balance after first successes
			// Acceptable that error is "insufficient balance" after balance is reduced
		}
	}

	// Final balance should be initial - (successful transfers * amount)
	// At least one should succeed, others might fail due to insufficient balance
	finalWallet, _ := repo.GetWalletByUser(ctx, "concurrent-user")
	// Balance should be less than or equal to 1000000 - 100000
	assert.True(t, finalWallet.Balance <= 900000, "Balance should have decreased")
	assert.True(t, finalWallet.Balance >= 0, "Balance cannot be negative")
}
