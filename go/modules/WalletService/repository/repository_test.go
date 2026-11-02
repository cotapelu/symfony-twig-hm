package repository

import (
	"context"
	"database/sql"
	"testing"
	"time"

	"github.com/DATA-DOG/go-sqlmock"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"

	walletservice "community.local/modules/WalletService/internal/models"
)

func newNow() time.Time {
	return time.Now().Truncate(time.Microsecond)
}

// ==================== WalletRepositoryImpl Tests ====================

func TestWalletRepositoryImpl_CreateWallet(t *testing.T) {
	db, mock, err := sqlmock.New()
	require.NoError(t, err)
	defer db.Close()

	repo := NewWalletRepository(db)
	now := newNow()

	wallet := &walletservice.Wallet{
		UserID:    "user123",
		Balance:   0,
		Currency:  "VND",
		Status:    "active",
		CreatedAt: now,
		UpdatedAt: now,
	}

	mock.ExpectQuery(`INSERT INTO wallets \(user_id, balance, currency, status, created_at, updated_at\) VALUES \(\$1, \$2, \$3, \$4, \$5, \$6\) RETURNING id`).
		WithArgs("user123", int64(0), "VND", "active", now, now).
		WillReturnRows(sqlmock.NewRows([]string{"id"}).AddRow(int64(42)))

	err = repo.CreateWallet(context.Background(), wallet)
	require.NoError(t, err)
	assert.Equal(t, int64(42), wallet.ID)
}

func TestWalletRepositoryImpl_GetWallet(t *testing.T) {
	db, mock, err := sqlmock.New()
	require.NoError(t, err)
	defer db.Close()

	repo := NewWalletRepository(db)
	now := newNow()

	rows := mock.NewRows([]string{"id", "user_id", "balance", "currency", "status", "version", "created_at", "updated_at"}).
		AddRow(int64(42), "user123", int64(1000), "VND", "active", int64(1), now, now)
	mock.ExpectQuery(`SELECT id, user_id, balance, currency, status, version, created_at, updated_at FROM wallets WHERE id = \$1`).
		WithArgs(int64(42)).
		WillReturnRows(rows)

	w, err := repo.GetWallet(context.Background(), 42)
	require.NoError(t, err)
	assert.Equal(t, int64(42), w.ID)
	assert.Equal(t, "user123", w.UserID)
	assert.Equal(t, int64(1000), w.Balance)

	// Not found
	mock.ExpectQuery(`SELECT`).WithArgs(int64(99)).WillReturnError(sql.ErrNoRows)
	_, err = repo.GetWallet(context.Background(), 99)
	assert.ErrorIs(t, err, sql.ErrNoRows)
}

func TestWalletRepositoryImpl_GetWalletByUserID(t *testing.T) {
	db, mock, err := sqlmock.New()
	require.NoError(t, err)
	defer db.Close()

	repo := NewWalletRepository(db)
	now := newNow()

	rows := mock.NewRows([]string{"id", "user_id", "balance", "currency", "status", "version", "created_at", "updated_at"}).
		AddRow(int64(42), "user123", int64(2000), "VND", "active", int64(2), now, now)
	mock.ExpectQuery(`SELECT id, user_id, balance, currency, status, version, created_at, updated_at FROM wallets WHERE user_id = \$1`).
		WithArgs("user123").
		WillReturnRows(rows)

	w, err := repo.GetWalletByUserID(context.Background(), "user123")
	require.NoError(t, err)
	assert.Equal(t, int64(42), w.ID)
	assert.Equal(t, int64(2000), w.Balance)
}

func TestWalletRepositoryImpl_UpdateWalletBalance(t *testing.T) {
	db, mock, err := sqlmock.New()
	require.NoError(t, err)
	defer db.Close()

	repo := NewWalletRepository(db)
	mock.ExpectExec(`UPDATE wallets SET balance = balance \+ \$1, updated_at = \$2 WHERE id = \$3`).
		WithArgs(int64(500), sqlmock.AnyArg(), int64(42)).
		WillReturnResult(sqlmock.NewResult(1, 1))

	err = repo.UpdateWalletBalance(context.Background(), 42, 500)
	require.NoError(t, err)
}

func TestWalletRepositoryImpl_UpdateBalanceWithVersion(t *testing.T) {
	db, mock, err := sqlmock.New()
	require.NoError(t, err)
	defer db.Close()

	repo := NewWalletRepository(db)

	// Success
	mock.ExpectExec(`UPDATE wallets SET balance = balance \+ \$1, updated_at = NOW\(\), version = version \+ 1 WHERE id = \$2 AND version = \$3`).
		WithArgs(int64(500), int64(42), int64(2)).
		WillReturnResult(sqlmock.NewResult(1, 1))

	err = repo.UpdateBalanceWithVersion(context.Background(), 42, 500, 2)
	require.NoError(t, err)

	// Failure: version mismatch
	mock.ExpectExec(`UPDATE wallets SET balance = balance \+ \$1, updated_at = NOW\(\), version = version \+ 1 WHERE id = \$2 AND version = \$3`).
		WithArgs(int64(500), int64(42), int64(3)).
		WillReturnResult(sqlmock.NewResult(0, 0))

	err = repo.UpdateBalanceWithVersion(context.Background(), 42, 500, 3)
	require.Error(t, err)
	assert.Contains(t, err.Error(), "optimistic lock failed")
}

func TestWalletRepositoryImpl_UpdateWalletStatus(t *testing.T) {
	db, mock, err := sqlmock.New()
	require.NoError(t, err)
	defer db.Close()

	repo := NewWalletRepository(db)
	mock.ExpectExec(`UPDATE wallets SET status = \$1, updated_at = \$2 WHERE id = \$3`).
		WithArgs("locked", sqlmock.AnyArg(), int64(42)).
		WillReturnResult(sqlmock.NewResult(1, 1))

	err = repo.UpdateWalletStatus(context.Background(), 42, "locked")
	require.NoError(t, err)
}

// ==================== WalletBalanceRepositoryImpl Tests ====================

func TestWalletBalanceRepositoryImpl_GetBalance(t *testing.T) {
	db, mock, err := sqlmock.New()
	require.NoError(t, err)
	defer db.Close()

	repo := NewWalletBalanceRepository(db)
	now := newNow()

	rows := mock.NewRows([]string{"id", "wallet_id", "currency", "balance", "last_updated"}).
		AddRow(int64(1), int64(42), "VND", int64(3000), now)
	mock.ExpectQuery(`SELECT id, wallet_id, currency, balance, last_updated FROM wallet_balances WHERE wallet_id = \$1`).
		WithArgs(int64(42)).
		WillReturnRows(rows)

	bal, err := repo.GetBalance(context.Background(), 42)
	require.NoError(t, err)
	assert.Equal(t, int64(1), bal.ID)
	assert.Equal(t, int64(42), bal.WalletID)
	assert.Equal(t, int64(3000), bal.Balance)

	// Not found
	mock.ExpectQuery(`SELECT`).WithArgs(int64(99)).WillReturnError(sql.ErrNoRows)
	_, err = repo.GetBalance(context.Background(), 99)
	assert.ErrorIs(t, err, sql.ErrNoRows)
}

func TestWalletBalanceRepositoryImpl_UpdateBalance(t *testing.T) {
	db, mock, err := sqlmock.New()
	require.NoError(t, err)
	defer db.Close()

	repo := NewWalletBalanceRepository(db)
	mock.ExpectExec(`UPDATE wallet_balances SET balance = balance \+ \$1, last_updated = NOW\(\) WHERE wallet_id = \$2`).
		WithArgs(int64(200), int64(42)).
		WillReturnResult(sqlmock.NewResult(1, 1))

	err = repo.UpdateBalance(context.Background(), 42, 200)
	require.NoError(t, err)
}

func TestWalletBalanceRepositoryImpl_CreateBalance(t *testing.T) {
	db, mock, err := sqlmock.New()
	require.NoError(t, err)
	defer db.Close()

	repo := NewWalletBalanceRepository(db)
	now := newNow()
	bal := &walletservice.WalletBalance{
		WalletID:    42,
		Currency:    "VND",
		Balance:     5000,
		LastUpdated: now,
	}

	mock.ExpectQuery(`INSERT INTO wallet_balances \(wallet_id, currency, balance, last_updated\) VALUES \(\$1, \$2, \$3, \$4\) RETURNING id`).
		WithArgs(int64(42), "VND", int64(5000), now).
		WillReturnRows(sqlmock.NewRows([]string{"id"}).AddRow(int64(7)))

	err = repo.CreateBalance(context.Background(), bal)
	require.NoError(t, err)
	assert.Equal(t, int64(7), bal.ID)
}

// ==================== WalletTransactionRepositoryImpl Tests ====================

func TestWalletTransactionRepositoryImpl_CreateTransaction(t *testing.T) {
	db, mock, err := sqlmock.New()
	require.NoError(t, err)
	defer db.Close()

	repo := NewWalletTransactionRepository(db)
	now := newNow()
	txn := &walletservice.WalletTransaction{
		WalletID:     42,
		Type:         "topup",
		Amount:       1000,
		BalanceAfter: 2000,
		Description:  "Test top-up",
		ReferenceID:  "ref123",
		Status:       "completed",
		CreatedAt:    now,
	}

	mock.ExpectQuery(`INSERT INTO wallet_transactions \(wallet_id, type, amount, balance_after, description, reference_id, status, created_at\) VALUES \(\$1, \$2, \$3, \$4, \$5, \$6, \$7, \$8\) RETURNING id`).
		WithArgs(int64(42), "topup", int64(1000), int64(2000), "Test top-up", "ref123", "completed", now).
		WillReturnRows(sqlmock.NewRows([]string{"id"}).AddRow(int64(99)))

	err = repo.CreateTransaction(context.Background(), txn)
	require.NoError(t, err)
	assert.Equal(t, int64(99), txn.ID)
}

func TestWalletTransactionRepositoryImpl_GetTransaction(t *testing.T) {
	db, mock, err := sqlmock.New()
	require.NoError(t, err)
	defer db.Close()

	repo := NewWalletTransactionRepository(db)
	now := newNow()

	rows := mock.NewRows([]string{"id", "wallet_id", "type", "amount", "balance_after", "description", "reference_id", "status", "created_at"}).
		AddRow(int64(99), int64(42), "topup", int64(1000), int64(2000), "Test", "ref123", "completed", now)
	mock.ExpectQuery(`SELECT id, wallet_id, type, amount, balance_after, description, reference_id, status, created_at FROM wallet_transactions WHERE id = \$1`).
		WithArgs(int64(99)).
		WillReturnRows(rows)

	txn, err := repo.GetTransaction(context.Background(), 99)
	require.NoError(t, err)
	assert.Equal(t, int64(99), txn.ID)
	assert.Equal(t, "topup", txn.Type)
}

func TestWalletTransactionRepositoryImpl_GetTransactionsByWallet(t *testing.T) {
	db, mock, err := sqlmock.New()
	require.NoError(t, err)
	defer db.Close()

	repo := NewWalletTransactionRepository(db)
	now := newNow()

	rows := mock.NewRows([]string{"id", "wallet_id", "type", "amount", "balance_after", "description", "reference_id", "status", "created_at"}).
		AddRow(int64(1), int64(42), "topup", int64(1000), int64(2000), "First", "ref1", "completed", now).
		AddRow(int64(2), int64(42), "transfer_out", int64(-500), int64(1500), "Transfer", "ref2", "completed", now).
		AddRow(int64(3), int64(42), "transfer_in", int64(300), int64(1800), "Third", "ref3", "completed", now)
	mock.ExpectQuery(`SELECT id, wallet_id, type, amount, balance_after, description, reference_id, status, created_at FROM wallet_transactions WHERE wallet_id = \$1 ORDER BY created_at DESC LIMIT \$2 OFFSET \$3`).
		WithArgs(int64(42), 10, 0).
		WillReturnRows(rows)

	txns, err := repo.GetTransactionsByWallet(context.Background(), 42, 10, 0)
	require.NoError(t, err)
	assert.Len(t, txns, 3)
	assert.Equal(t, int64(1), txns[0].ID)
	assert.Equal(t, int64(2), txns[1].ID)
	assert.Equal(t, int64(3), txns[2].ID)
}

func TestWalletTransactionRepositoryImpl_UpdateTransactionStatus(t *testing.T) {
	db, mock, err := sqlmock.New()
	require.NoError(t, err)
	defer db.Close()

	repo := NewWalletTransactionRepository(db)
	mock.ExpectExec(`UPDATE wallet_transactions SET status = \$1 WHERE id = \$2`).
		WithArgs("failed", int64(99)).
		WillReturnResult(sqlmock.NewResult(1, 1))

	err = repo.UpdateTransactionStatus(context.Background(), 99, "failed")
	require.NoError(t, err)
}

// ==================== InMemoryWalletRepository Tests ====================

func TestInMemoryWalletRepository_CreateWallet(t *testing.T) {
	repo := NewInMemoryWalletRepository()

	w := &walletservice.Wallet{
		UserID:  "user123",
		Balance: 0,
		Currency: "VND",
		Status: "active",
	}

	err := repo.CreateWallet(context.Background(), w)
	require.NoError(t, err)
	assert.Equal(t, int64(1), w.ID)
	assert.NotEqual(t, time.Time{}, w.CreatedAt)
	assert.NotEqual(t, time.Time{}, w.UpdatedAt)

	// Second wallet gets ID 2
	w2 := &walletservice.Wallet{UserID: "user456", Balance: 0, Currency: "USD", Status: "active"}
	repo.CreateWallet(context.Background(), w2)
	assert.Equal(t, int64(2), w2.ID)
}

func TestInMemoryWalletRepository_GetWallet(t *testing.T) {
	repo := NewInMemoryWalletRepository()

	w := &walletservice.Wallet{
		UserID:   "user123",
		Balance:  1000,
		Currency: "VND",
		Status:   "active",
	}
	repo.CreateWallet(context.Background(), w)

	fetched, err := repo.GetWallet(context.Background(), w.ID)
	require.NoError(t, err)
	assert.Equal(t, w, fetched)

	// Non-existent
	fetched2, err := repo.GetWallet(context.Background(), 999)
	assert.NoError(t, err)
	assert.Nil(t, fetched2)
}

func TestInMemoryWalletRepository_GetWalletByUser(t *testing.T) {
	repo := NewInMemoryWalletRepository()

	// Active wallet
	active := &walletservice.Wallet{
		UserID:  "user1",
		Balance: 500,
		Status:  "active",
	}
	// Locked wallet (same user)
	locked := &walletservice.Wallet{
		UserID:  "user1",
		Balance: 200,
		Status:  "locked",
	}
	repo.CreateWallet(context.Background(), active)
	repo.CreateWallet(context.Background(), locked)

	w, err := repo.GetWalletByUser(context.Background(), "user1")
	require.NoError(t, err)
	assert.Equal(t, active.ID, w.ID)
	assert.Equal(t, "active", w.Status)

	// No active wallet
	w2, err := repo.GetWalletByUser(context.Background(), "user2")
	assert.NoError(t, err)
	assert.Nil(t, w2)
}

func TestInMemoryWalletRepository_GetWalletForUpdate(t *testing.T) {
	repo := NewInMemoryWalletRepository()

	w := &walletservice.Wallet{
		UserID: "userX",
		Balance: 1000,
		Status: "active",
	}
	err := repo.CreateWallet(context.Background(), w)
	require.NoError(t, err)

	fetched, err := repo.GetWalletForUpdate(context.Background(), "userX")
	require.NoError(t, err)
	assert.Equal(t, w.ID, fetched.ID)
	// Ensure it's the same pointer (locked access)
	assert.Equal(t, w, fetched)
}

func TestInMemoryWalletRepository_UpdateBalance(t *testing.T) {
	repo := NewInMemoryWalletRepository()

	w := &walletservice.Wallet{
		UserID: "user5",
		Balance: 100,
		Status: "active",
	}
	require.NoError(t, repo.CreateWallet(context.Background(), w))

	err := repo.UpdateBalance(context.Background(), w.ID, 50)
	require.NoError(t, err)
	assert.Equal(t, int64(150), w.Balance)
	assert.NotEqual(t, time.Time{}, w.UpdatedAt)

	// Non-existent wallet
	err = repo.UpdateBalance(context.Background(), 999, 50)
	assert.Error(t, err)
	assert.Contains(t, err.Error(), "wallet not found")
}

func TestInMemoryWalletRepository_HasSufficientBalance(t *testing.T) {
	repo := NewInMemoryWalletRepository()

	w := &walletservice.Wallet{
		UserID: "user7",
		Balance: 1000,
		Status: "active",
	}
	require.NoError(t, repo.CreateWallet(context.Background(), w))

	has, err := repo.HasSufficientBalance(context.Background(), "user7", 500)
	require.NoError(t, err)
	assert.True(t, has)

	has, err = repo.HasSufficientBalance(context.Background(), "user7", 1500)
	require.NoError(t, err)
	assert.False(t, has)
}

// ==================== InMemoryTransactionRepository Tests ====================

func TestInMemoryTransactionRepository_CreateTransaction(t *testing.T) {
	repo := NewInMemoryTransactionRepository()

	txn := &walletservice.WalletTransaction{
		WalletID:     42,
		Type:         "topup",
		Amount:       1000,
		BalanceAfter: 2000,
		Description:  "Top up",
		ReferenceID:  "refABC",
		Status:       "completed",
	}
	// Set CreatedAt to zero to verify repo sets it
	txn.CreatedAt = time.Time{}

	err := repo.CreateTransaction(context.Background(), txn)
	require.NoError(t, err)
	assert.Equal(t, int64(1), txn.ID)
	assert.NotEmpty(t, txn.CreatedAt)

	// Check index
	ids, ok := repo.walletIdx[42]
	require.True(t, ok)
	assert.Len(t, ids, 1)
	assert.Equal(t, int64(1), ids[0])
}

func TestInMemoryTransactionRepository_GetByWallet(t *testing.T) {
	repo := NewInMemoryTransactionRepository()
	now := newNow()

	// Create three transactions for wallet 1
	txns := []*walletservice.WalletTransaction{
		{WalletID: 1, Type: "topup", Amount: 100, BalanceAfter: 200, Description: "T1", ReferenceID: "r1", Metadata: "{}", Status: "completed", CreatedAt: now.Add(-2 * time.Hour)},
		{WalletID: 1, Type: "transfer_out", Amount: -50, BalanceAfter: 150, Description: "T2", ReferenceID: "r2", Metadata: "{}", Status: "completed", CreatedAt: now.Add(-1 * time.Hour)},
		{WalletID: 1, Type: "transfer_in", Amount: 30, BalanceAfter: 180, Description: "T3", ReferenceID: "r3", Metadata: "{}", Status: "completed", CreatedAt: now},
	}
	for _, txn := range txns {
		require.NoError(t, repo.CreateTransaction(context.Background(), txn))
	}

	// Get all (limit=10, offset=0)
	result, err := repo.GetByWallet(context.Background(), 1, 10, 0)
	require.NoError(t, err)
	assert.Len(t, result, 3)
	// Repository returns in creation order (oldest first)
	assert.Equal(t, txns[0].ID, result[0].ID)
	assert.Equal(t, txns[1].ID, result[1].ID)
	assert.Equal(t, txns[2].ID, result[2].ID)
}

func TestInMemoryTransactionRepository_GetByWallet_Pagination(t *testing.T) {
	repo := NewInMemoryTransactionRepository()
	now := newNow()

	for i := int64(1); i <= 5; i++ {
		txn := &walletservice.WalletTransaction{
			WalletID:    100,
			Type:        "topup",
			Amount:      i * 100,
			Description: "Test",
			ReferenceID: "ref" + string(rune(i+'0')),
			Metadata:    "{}",
			Status:      "completed",
			CreatedAt:   now,
		}
		_ = repo.CreateTransaction(context.Background(), txn)
	}

	// offset=2, limit=2 -> should get 3rd and 4th (IDs 3,4)
	result, err := repo.GetByWallet(context.Background(), 100, 2, 2)
	require.NoError(t, err)
	assert.Len(t, result, 2)
	assert.Equal(t, int64(3), result[0].ID)
	assert.Equal(t, int64(4), result[1].ID)

	// offset beyond length -> empty
	result, err = repo.GetByWallet(context.Background(), 100, 10, 100)
	require.NoError(t, err)
	assert.Len(t, result, 0)
}

func TestInMemoryTransactionRepository_GetByReference(t *testing.T) {
	repo := NewInMemoryTransactionRepository()
	now := newNow()

	txn := &walletservice.WalletTransaction{
		WalletID:    42,
		Type:        "topup",
		Amount:      1000,
		BalanceAfter: 2000,
		ReferenceID: "unique_ref_123",
		Metadata:    "{}",
		Status:      "completed",
		CreatedAt:   now,
	}
	_ = repo.CreateTransaction(context.Background(), txn)

	found, err := repo.GetByReference(context.Background(), "unique_ref_123")
	require.NoError(t, err)
	assert.Equal(t, int64(1), found.ID)
	assert.Equal(t, int64(42), found.WalletID)

	// Not found
	found2, err := repo.GetByReference(context.Background(), "missing_ref")
	require.NoError(t, err)
	assert.Nil(t, found2)
}
