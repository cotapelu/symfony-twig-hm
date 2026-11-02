package banking

import (
	"context"
	"database/sql"
	"testing"
	"time"

	"github.com/DATA-DOG/go-sqlmock"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"

	bankingmodels "community.local/modules/Banking/internal/models"
)

func newMockDB() (*sql.DB, sqlmock.Sqlmock) {
	db, mock, err := sqlmock.New()
	if err != nil {
		panic(err)
	}
	return db, mock
}

func TestBankingRepositoryImpl_CreateAccount(t *testing.T) {
	db, mock := newMockDB()
	repo := NewBankingRepository(db)

	now := time.Now()
	account := &bankingmodels.Account{
		UserID:        "user-1",
		AccountNumber: "ACC-001",
		AccountType:   "CHECKING",
		Balance:       0,
		Currency:      "VND",
		Status:        "ACTIVE",
		CreatedAt:     now,
		UpdatedAt:     now,
	}
	returnedID := int64(123)
	query := `INSERT INTO banking_accounts \(user_id, account_number, account_type, balance, currency, status, created_at, updated_at\) VALUES \(\$1, \$2, \$3, \$4, \$5, \$6, \$7, \$8\) RETURNING id`
	mock.ExpectQuery(query).WithArgs(
		account.UserID, account.AccountNumber, account.AccountType,
		account.Balance, account.Currency, account.Status,
		account.CreatedAt, account.UpdatedAt,
	).WillReturnRows(sqlmock.NewRows([]string{"id"}).AddRow(returnedID))

	err := repo.CreateAccount(context.Background(), account)
	require.NoError(t, err)
	assert.Equal(t, returnedID, account.ID)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestBankingRepositoryImpl_GetAccount_Found(t *testing.T) {
	db, mock := newMockDB()
	repo := NewBankingRepository(db)

	now := time.Now()
	account := &bankingmodels.Account{
		ID:            123,
		UserID:        "user-1",
		AccountNumber: "ACC-001",
		AccountType:   "CHECKING",
		Balance:       5000,
		Currency:      "VND",
		Status:        "ACTIVE",
		CreatedAt:     now,
		UpdatedAt:     now,
	}
	query := `SELECT id, user_id, account_number, account_type, balance, currency, status, created_at, updated_at FROM banking_accounts WHERE id = \$1`
	rows := sqlmock.NewRows([]string{"id", "user_id", "account_number", "account_type", "balance", "currency", "status", "created_at", "updated_at"}).
		AddRow(account.ID, account.UserID, account.AccountNumber, account.AccountType, account.Balance, account.Currency, account.Status, account.CreatedAt, account.UpdatedAt)
	mock.ExpectQuery(query).WithArgs(account.ID).WillReturnRows(rows)

	result, err := repo.GetAccount(context.Background(), account.ID)
	require.NoError(t, err)
	assert.Equal(t, account, result)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestBankingRepositoryImpl_GetAccount_NotFound(t *testing.T) {
	db, mock := newMockDB()
	repo := NewBankingRepository(db)

	query := `SELECT id, user_id, account_number, account_type, balance, currency, status, created_at, updated_at FROM banking_accounts WHERE id = \$1`
	mock.ExpectQuery(query).WithArgs(999).WillReturnError(sql.ErrNoRows)

	result, err := repo.GetAccount(context.Background(), 999)
	assert.ErrorIs(t, err, sql.ErrNoRows)
	assert.Nil(t, result)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestBankingRepositoryImpl_GetAccountByNumber_Found(t *testing.T) {
	db, mock := newMockDB()
	repo := NewBankingRepository(db)

	now := time.Now()
	account := &bankingmodels.Account{
		ID:            123,
		UserID:        "user-1",
		AccountNumber: "ACC-001",
		AccountType:   "CHECKING",
		Balance:       5000,
		Currency:      "VND",
		Status:        "ACTIVE",
		CreatedAt:     now,
		UpdatedAt:     now,
	}
	query := `SELECT id, user_id, account_number, account_type, balance, currency, status, created_at, updated_at FROM banking_accounts WHERE account_number = \$1`
	rows := sqlmock.NewRows([]string{"id", "user_id", "account_number", "account_type", "balance", "currency", "status", "created_at", "updated_at"}).
		AddRow(account.ID, account.UserID, account.AccountNumber, account.AccountType, account.Balance, account.Currency, account.Status, account.CreatedAt, account.UpdatedAt)
	mock.ExpectQuery(query).WithArgs(account.AccountNumber).WillReturnRows(rows)

	result, err := repo.GetAccountByNumber(context.Background(), account.AccountNumber)
	require.NoError(t, err)
	assert.Equal(t, account, result)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestBankingRepositoryImpl_GetAccountByNumber_NotFound(t *testing.T) {
	db, mock := newMockDB()
	repo := NewBankingRepository(db)

	query := `SELECT id, user_id, account_number, account_type, balance, currency, status, created_at, updated_at FROM banking_accounts WHERE account_number = \$1`
	mock.ExpectQuery(query).WithArgs("UNKNOWN").WillReturnError(sql.ErrNoRows)

	result, err := repo.GetAccountByNumber(context.Background(), "UNKNOWN")
	assert.ErrorIs(t, err, sql.ErrNoRows)
	assert.Nil(t, result)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestBankingRepositoryImpl_GetUserAccounts_Success(t *testing.T) {
	db, mock := newMockDB()
	repo := NewBankingRepository(db)

	now := time.Now()
	accounts := []*bankingmodels.Account{
		{ID: 1, UserID: "user-1", AccountNumber: "ACC-001", AccountType: "CHECKING", Balance: 100, Currency: "VND", Status: "ACTIVE", CreatedAt: now, UpdatedAt: now},
		{ID: 2, UserID: "user-1", AccountNumber: "ACC-002", AccountType: "SAVINGS", Balance: 200, Currency: "VND", Status: "ACTIVE", CreatedAt: now, UpdatedAt: now},
	}
	query := `SELECT id, user_id, account_number, account_type, balance, currency, status, created_at, updated_at FROM banking_accounts WHERE user_id = \$1`
	rows := sqlmock.NewRows([]string{"id", "user_id", "account_number", "account_type", "balance", "currency", "status", "created_at", "updated_at"}).
		AddRow(accounts[0].ID, accounts[0].UserID, accounts[0].AccountNumber, accounts[0].AccountType, accounts[0].Balance, accounts[0].Currency, accounts[0].Status, accounts[0].CreatedAt, accounts[0].UpdatedAt).
		AddRow(accounts[1].ID, accounts[1].UserID, accounts[1].AccountNumber, accounts[1].AccountType, accounts[1].Balance, accounts[1].Currency, accounts[1].Status, accounts[1].CreatedAt, accounts[1].UpdatedAt)
	mock.ExpectQuery(query).WithArgs("user-1").WillReturnRows(rows)

	result, err := repo.GetUserAccounts(context.Background(), "user-1")
	require.NoError(t, err)
	assert.Equal(t, accounts, result)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestBankingRepositoryImpl_UpdateAccount(t *testing.T) {
	db, mock := newMockDB()
	repo := NewBankingRepository(db)

	account := &bankingmodels.Account{
		ID:        123,
		Balance:   5000,
		Status:    "ACTIVE",
		UpdatedAt: time.Now(),
	}
	query := `UPDATE banking_accounts SET balance = \$1, status = \$2, updated_at = \$3 WHERE id = \$4`
	mock.ExpectExec(query).
		WithArgs(account.Balance, account.Status, account.UpdatedAt, account.ID).
		WillReturnResult(sqlmock.NewResult(0, 1))

	err := repo.UpdateAccount(context.Background(), account)
	require.NoError(t, err)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestBankingRepositoryImpl_UpdateAccountBalance_Deposit(t *testing.T) {
	db, mock := newMockDB()
	repo := NewBankingRepository(db)

	accountID := int64(123)
	amount := 1500.0
	newBalance := 6500.0

	updateQuery := `UPDATE banking_accounts SET balance = balance \+ \$1, updated_at = \$2 WHERE id = \$3 RETURNING balance`
	mock.ExpectQuery(updateQuery).WithArgs(amount, sqlmock.AnyArg(), accountID).WillReturnRows(sqlmock.NewRows([]string{"balance"}).AddRow(newBalance))

	result, err := repo.UpdateAccountBalance(context.Background(), accountID, amount, true)
	require.NoError(t, err)
	assert.Equal(t, newBalance, result)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestBankingRepositoryImpl_UpdateAccountBalance_Withdraw(t *testing.T) {
	db, mock := newMockDB()
	repo := NewBankingRepository(db)

	accountID := int64(123)
	amount := 500.0
	newBalance := 4500.0

	updateQuery := `UPDATE banking_accounts SET balance = balance \- \$1, updated_at = \$2 WHERE id = \$3 RETURNING balance`
	mock.ExpectQuery(updateQuery).WithArgs(amount, sqlmock.AnyArg(), accountID).WillReturnRows(sqlmock.NewRows([]string{"balance"}).AddRow(newBalance))

	result, err := repo.UpdateAccountBalance(context.Background(), accountID, amount, false)
	require.NoError(t, err)
	assert.Equal(t, newBalance, result)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestBankingRepositoryImpl_CreateTransaction(t *testing.T) {
	db, mock := newMockDB()
	repo := NewBankingRepository(db)

	now := time.Now()
	txn := &bankingmodels.Transaction{
		AccountID:    123,
		Type:         "DEPOSIT",
		Amount:       100,
		BalanceAfter: 1100,
		Description:  "Test deposit",
		ReferenceID:  "ref-123",
		Status:       "COMPLETED",
		CreatedAt:    now,
	}
	returnedID := int64(456)
	query := `INSERT INTO banking_transactions \(account_id, type, amount, balance_after, description, reference_id, status, created_at\) VALUES \(\$1, \$2, \$3, \$4, \$5, \$6, \$7, \$8\) RETURNING id`
	mock.ExpectQuery(query).WithArgs(
		txn.AccountID, txn.Type, txn.Amount, txn.BalanceAfter,
		txn.Description, txn.ReferenceID, txn.Status, txn.CreatedAt,
	).WillReturnRows(sqlmock.NewRows([]string{"id"}).AddRow(returnedID))

	err := repo.CreateTransaction(context.Background(), txn)
	require.NoError(t, err)
	assert.Equal(t, returnedID, txn.ID)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestBankingRepositoryImpl_GetTransactions_Success(t *testing.T) {
	db, mock := newMockDB()
	repo := NewBankingRepository(db)

	now := time.Now()
	txns := []*bankingmodels.Transaction{
		{ID: 1, AccountID: 123, Type: "DEPOSIT", Amount: 100, BalanceAfter: 1100, Description: "desc1", ReferenceID: "ref1", Status: "COMPLETED", CreatedAt: now},
		{ID: 2, AccountID: 123, Type: "WITHDRAW", Amount: 50, BalanceAfter: 1050, Description: "desc2", ReferenceID: "ref2", Status: "COMPLETED", CreatedAt: now},
	}
	query := `SELECT id, account_id, type, amount, balance_after, description, reference_id, status, created_at FROM banking_transactions WHERE account_id = \$1 ORDER BY created_at DESC LIMIT \$2 OFFSET \$3`
	rows := sqlmock.NewRows([]string{"id", "account_id", "type", "amount", "balance_after", "description", "reference_id", "status", "created_at"}).
		AddRow(txns[0].ID, txns[0].AccountID, txns[0].Type, txns[0].Amount, txns[0].BalanceAfter, txns[0].Description, txns[0].ReferenceID, txns[0].Status, txns[0].CreatedAt).
		AddRow(txns[1].ID, txns[1].AccountID, txns[1].Type, txns[1].Amount, txns[1].BalanceAfter, txns[1].Description, txns[1].ReferenceID, txns[1].Status, txns[1].CreatedAt)
	mock.ExpectQuery(query).WithArgs(123, 10, 0).WillReturnRows(rows)

	result, err := repo.GetTransactions(context.Background(), 123, 10, 0)
	require.NoError(t, err)
	assert.Equal(t, txns, result)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestBankingRepositoryImpl_GetTransactionByIdempotencyKey_Found(t *testing.T) {
	db, mock := newMockDB()
	repo := NewBankingRepository(db)

	now := time.Now()
	txn := &bankingmodels.Transaction{
		ID:           1,
		AccountID:    123,
		Type:         "DEPOSIT",
		Amount:       100,
		BalanceAfter: 1100,
		Description:  "desc",
		ReferenceID:  "ref-123",
		Status:       "COMPLETED",
		CreatedAt:    now,
	}
	query := `SELECT id, account_id, type, amount, balance_after, description, reference_id, status, created_at FROM banking_transactions WHERE reference_id = \$1 ORDER BY created_at DESC LIMIT 1`
	rows := sqlmock.NewRows([]string{"id", "account_id", "type", "amount", "balance_after", "description", "reference_id", "status", "created_at"}).
		AddRow(txn.ID, txn.AccountID, txn.Type, txn.Amount, txn.BalanceAfter, txn.Description, txn.ReferenceID, txn.Status, txn.CreatedAt)
	mock.ExpectQuery(query).WithArgs("ref-123").WillReturnRows(rows)

	result, err := repo.GetTransactionByIdempotencyKey(context.Background(), "ref-123")
	require.NoError(t, err)
	assert.Equal(t, txn, result)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestBankingRepositoryImpl_GetTransactionByIdempotencyKey_NotFound(t *testing.T) {
	db, mock := newMockDB()
	repo := NewBankingRepository(db)

	query := `SELECT id, account_id, type, amount, balance_after, description, reference_id, status, created_at FROM banking_transactions WHERE reference_id = \$1 ORDER BY created_at DESC LIMIT 1`
	mock.ExpectQuery(query).WithArgs("nonexistent").WillReturnError(sql.ErrNoRows)

	result, err := repo.GetTransactionByIdempotencyKey(context.Background(), "nonexistent")
	require.NoError(t, err)
	assert.Nil(t, result)
	require.NoError(t, mock.ExpectationsWereMet())
}
