package banking

import (
	"context"
	"database/sql"
	"testing"
	"time"

	"github.com/DATA-DOG/go-sqlmock"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"

	bm "community.local/modules/Banking/internal/models"
)

func newMockDB() (*sql.DB, sqlmock.Sqlmock) {
	db, mock, err := sqlmock.New()
	if err != nil {
		panic(err)
	}
	return db, mock
}

func TestSQLiteBankingRepository_CreateAccount(t *testing.T) {
	db, mock := newMockDB()
	repo := NewSQLiteBankingRepository(db)

	account := &bm.Account{
		UserID:        "user-1",
		AccountNumber: "ACC-001",
		AccountType:   "CHECKING",
		Balance:       0,
		Currency:      "VND",
		Status:        "active",
	}
	query := `INSERT INTO banking_accounts \(user_id, account_number, account_type, balance, currency, status\) VALUES \(\?, \?, \?, \?, \?, \?\) RETURNING id`
	mock.ExpectQuery(query).WithArgs(
		account.UserID, account.AccountNumber, account.AccountType,
		account.Balance, account.Currency, account.Status,
	).WillReturnRows(sqlmock.NewRows([]string{"id"}).AddRow(123))

	err := repo.CreateAccount(context.Background(), account)
	require.NoError(t, err)
	assert.Equal(t, int64(123), account.ID)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestSQLiteBankingRepository_CreateAccount_NilDB(t *testing.T) {
	repo := NewSQLiteBankingRepository(nil)
	account := &bm.Account{}
	err := repo.CreateAccount(context.Background(), account)
	assert.Error(t, err)
	assert.Equal(t, "database connection is nil", err.Error())
}

func TestSQLiteBankingRepository_GetAccount_Found(t *testing.T) {
	db, mock := newMockDB()
	repo := NewSQLiteBankingRepository(db)

	now := time.Now()
	account := &bm.Account{
		ID:            123,
		UserID:        "user-1",
		AccountNumber: "ACC-001",
		AccountType:   "CHECKING",
		Balance:       5000,
		Currency:      "VND",
		Status:        "active",
		CreatedAt:     now,
		UpdatedAt:     now,
	}
	query := `SELECT id, user_id, account_number, account_type, balance, currency, status, created_at, updated_at FROM banking_accounts WHERE id = \?`
	rows := sqlmock.NewRows([]string{"id", "user_id", "account_number", "account_type", "balance", "currency", "status", "created_at", "updated_at"}).
		AddRow(account.ID, account.UserID, account.AccountNumber, account.AccountType, account.Balance, account.Currency, account.Status, account.CreatedAt, account.UpdatedAt)
	mock.ExpectQuery(query).WithArgs(account.ID).WillReturnRows(rows)

	result, err := repo.GetAccount(context.Background(), account.ID)
	require.NoError(t, err)
	assert.Equal(t, account, result)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestSQLiteBankingRepository_GetAccount_NotFound(t *testing.T) {
	db, mock := newMockDB()
	repo := NewSQLiteBankingRepository(db)

	query := `SELECT id, user_id, account_number, account_type, balance, currency, status, created_at, updated_at FROM banking_accounts WHERE id = \?`
	mock.ExpectQuery(query).WithArgs(999).WillReturnError(sql.ErrNoRows)

	result, err := repo.GetAccount(context.Background(), 999)
	assert.ErrorIs(t, err, sql.ErrNoRows)
	assert.Nil(t, result)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestSQLiteBankingRepository_GetAccount_NilDB(t *testing.T) {
	repo := NewSQLiteBankingRepository(nil)
	result, err := repo.GetAccount(context.Background(), 1)
	assert.Error(t, err)
	assert.Equal(t, "database connection is nil", err.Error())
	assert.Nil(t, result)
}

func TestSQLiteBankingRepository_GetAccountByNumber_Found(t *testing.T) {
	db, mock := newMockDB()
	repo := NewSQLiteBankingRepository(db)

	now := time.Now()
	account := &bm.Account{
		ID:            123,
		UserID:        "user-1",
		AccountNumber: "ACC-001",
		AccountType:   "CHECKING",
		Balance:       5000,
		Currency:      "VND",
		Status:        "active",
		CreatedAt:     now,
		UpdatedAt:     now,
	}
	query := `SELECT id, user_id, account_number, account_type, balance, currency, status, created_at, updated_at FROM banking_accounts WHERE account_number = \?`
	rows := sqlmock.NewRows([]string{"id", "user_id", "account_number", "account_type", "balance", "currency", "status", "created_at", "updated_at"}).
		AddRow(account.ID, account.UserID, account.AccountNumber, account.AccountType, account.Balance, account.Currency, account.Status, account.CreatedAt, account.UpdatedAt)
	mock.ExpectQuery(query).WithArgs(account.AccountNumber).WillReturnRows(rows)

	result, err := repo.GetAccountByNumber(context.Background(), account.AccountNumber)
	require.NoError(t, err)
	assert.Equal(t, account, result)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestSQLiteBankingRepository_GetAccountByNumber_NotFound(t *testing.T) {
	db, mock := newMockDB()
	repo := NewSQLiteBankingRepository(db)

	query := `SELECT id, user_id, account_number, account_type, balance, currency, status, created_at, updated_at FROM banking_accounts WHERE account_number = \?`
	mock.ExpectQuery(query).WithArgs("UNKNOWN").WillReturnError(sql.ErrNoRows)

	result, err := repo.GetAccountByNumber(context.Background(), "UNKNOWN")
	assert.ErrorIs(t, err, sql.ErrNoRows)
	assert.Nil(t, result)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestSQLiteBankingRepository_GetAccountByNumber_NilDB(t *testing.T) {
	repo := NewSQLiteBankingRepository(nil)
	result, err := repo.GetAccountByNumber(context.Background(), "test")
	assert.Error(t, err)
	assert.Equal(t, "database connection is nil", err.Error())
	assert.Nil(t, result)
}

func TestSQLiteBankingRepository_GetUserAccounts_Success(t *testing.T) {
	db, mock := newMockDB()
	repo := NewSQLiteBankingRepository(db)

	now := time.Now()
	accounts := []*bm.Account{
		{ID: 1, UserID: "user-1", AccountNumber: "ACC-001", AccountType: "CHECKING", Balance: 100, Currency: "VND", Status: "active", CreatedAt: now, UpdatedAt: now},
		{ID: 2, UserID: "user-1", AccountNumber: "ACC-002", AccountType: "SAVINGS", Balance: 200, Currency: "VND", Status: "active", CreatedAt: now, UpdatedAt: now},
	}
	query := `SELECT id, user_id, account_number, account_type, balance, currency, status, created_at, updated_at FROM banking_accounts WHERE user_id = \?`
	rows := sqlmock.NewRows([]string{"id", "user_id", "account_number", "account_type", "balance", "currency", "status", "created_at", "updated_at"}).
		AddRow(accounts[0].ID, accounts[0].UserID, accounts[0].AccountNumber, accounts[0].AccountType, accounts[0].Balance, accounts[0].Currency, accounts[0].Status, accounts[0].CreatedAt, accounts[0].UpdatedAt).
		AddRow(accounts[1].ID, accounts[1].UserID, accounts[1].AccountNumber, accounts[1].AccountType, accounts[1].Balance, accounts[1].Currency, accounts[1].Status, accounts[1].CreatedAt, accounts[1].UpdatedAt)
	mock.ExpectQuery(query).WithArgs("user-1").WillReturnRows(rows)

	result, err := repo.GetUserAccounts(context.Background(), "user-1")
	require.NoError(t, err)
	assert.Equal(t, accounts, result)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestSQLiteBankingRepository_GetUserAccounts_NilDB(t *testing.T) {
	repo := NewSQLiteBankingRepository(nil)
	result, err := repo.GetUserAccounts(context.Background(), "user-1")
	assert.Error(t, err)
	assert.Equal(t, "database connection is nil", err.Error())
	assert.Nil(t, result)
}

func TestSQLiteBankingRepository_UpdateAccount(t *testing.T) {
	db, mock := newMockDB()
	repo := NewSQLiteBankingRepository(db)

	account := &bm.Account{
		ID:        123,
		Balance:   5000,
		Status:    "active",
		UpdatedAt: time.Now(),
	}
	query := `UPDATE banking_accounts SET balance = \?, status = \?, updated_at = \? WHERE id = \?`
	mock.ExpectExec(query).
		WithArgs(account.Balance, account.Status, account.UpdatedAt, account.ID).
		WillReturnResult(sqlmock.NewResult(0, 1))

	err := repo.UpdateAccount(context.Background(), account)
	require.NoError(t, err)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestSQLiteBankingRepository_UpdateAccount_NilDB(t *testing.T) {
	repo := NewSQLiteBankingRepository(nil)
	account := &bm.Account{}
	err := repo.UpdateAccount(context.Background(), account)
	assert.Error(t, err)
	assert.Equal(t, "database connection is nil", err.Error())
}

func TestSQLiteBankingRepository_CreateTransaction(t *testing.T) {
	db, mock := newMockDB()
	repo := NewSQLiteBankingRepository(db)

	now := time.Now()
	txn := &bm.Transaction{
		AccountID:    123,
		Type:         "DEPOSIT",
		Amount:       100,
		BalanceAfter: 1100,
		Description:  "Test deposit",
		ReferenceID:  "ref-123",
		Status:       "COMPLETED",
		CreatedAt:    now,
	}
	query := `INSERT INTO banking_transactions \(account_id, type, amount, balance_after, description, reference_id, status, created_at\) VALUES \(\?, \?, \?, \?, \?, \?, \?, \?\) RETURNING id`
	mock.ExpectQuery(query).WithArgs(
		txn.AccountID, txn.Type, txn.Amount, txn.BalanceAfter,
		txn.Description, txn.ReferenceID, txn.Status, txn.CreatedAt,
	).WillReturnRows(sqlmock.NewRows([]string{"id"}).AddRow(456))

	err := repo.CreateTransaction(context.Background(), txn)
	require.NoError(t, err)
	assert.Equal(t, int64(456), txn.ID)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestSQLiteBankingRepository_CreateTransaction_NilDB(t *testing.T) {
	repo := NewSQLiteBankingRepository(nil)
	txn := &bm.Transaction{}
	err := repo.CreateTransaction(context.Background(), txn)
	assert.Error(t, err)
	assert.Equal(t, "database connection is nil", err.Error())
}

func TestSQLiteBankingRepository_GetTransactions_Success(t *testing.T) {
	db, mock := newMockDB()
	repo := NewSQLiteBankingRepository(db)

	now := time.Now()
	txns := []*bm.Transaction{
		{ID: 1, AccountID: 123, Type: "DEPOSIT", Amount: 100, BalanceAfter: 1100, Description: "desc1", ReferenceID: "ref1", Status: "COMPLETED", CreatedAt: now},
		{ID: 2, AccountID: 123, Type: "WITHDRAW", Amount: 50, BalanceAfter: 1050, Description: "desc2", ReferenceID: "ref2", Status: "COMPLETED", CreatedAt: now},
	}
	query := `SELECT id, account_id, type, amount, balance_after, description, reference_id, status, created_at FROM banking_transactions WHERE account_id = \? ORDER BY created_at DESC LIMIT \? OFFSET \?`
	rows := sqlmock.NewRows([]string{"id", "account_id", "type", "amount", "balance_after", "description", "reference_id", "status", "created_at"}).
		AddRow(txns[0].ID, txns[0].AccountID, txns[0].Type, txns[0].Amount, txns[0].BalanceAfter, txns[0].Description, txns[0].ReferenceID, txns[0].Status, txns[0].CreatedAt).
		AddRow(txns[1].ID, txns[1].AccountID, txns[1].Type, txns[1].Amount, txns[1].BalanceAfter, txns[1].Description, txns[1].ReferenceID, txns[1].Status, txns[1].CreatedAt)
	mock.ExpectQuery(query).WithArgs(123, 10, 0).WillReturnRows(rows)

	result, err := repo.GetTransactions(context.Background(), 123, 10, 0)
	require.NoError(t, err)
	assert.Equal(t, txns, result)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestSQLiteBankingRepository_GetTransactions_NilDB(t *testing.T) {
	repo := NewSQLiteBankingRepository(nil)
	result, err := repo.GetTransactions(context.Background(), 123, 10, 0)
	assert.Error(t, err)
	assert.Equal(t, "database connection is nil", err.Error())
	assert.Nil(t, result)
}

func TestSQLiteBankingRepository_NilDB_AllMethods(t *testing.T) {
	repo := NewSQLiteBankingRepository(nil)

	err := repo.CreateAccount(context.Background(), nil)
	assert.Error(t, err)
	assert.Equal(t, "database connection is nil", err.Error())

	_, err = repo.GetAccount(context.Background(), 1)
	assert.Error(t, err)
	assert.Equal(t, "database connection is nil", err.Error())

	_, err = repo.GetAccountByNumber(context.Background(), "test")
	assert.Error(t, err)
	assert.Equal(t, "database connection is nil", err.Error())

	_, err = repo.GetUserAccounts(context.Background(), "user1")
	assert.Error(t, err)
	assert.Equal(t, "database connection is nil", err.Error())

	err = repo.UpdateAccount(context.Background(), nil)
	assert.Error(t, err)
	assert.Equal(t, "database connection is nil", err.Error())

	err = repo.CreateTransaction(context.Background(), nil)
	assert.Error(t, err)
	assert.Equal(t, "database connection is nil", err.Error())

	_, err = repo.GetTransactions(context.Background(), 1, 10, 0)
	assert.Error(t, err)
	assert.Equal(t, "database connection is nil", err.Error())
}