package banking

import (
	"context"
	"database/sql"
	"errors"
	"fmt"
	"os"
	bm "community.local/modules/Banking/internal/models"
)

// SQLiteBankingRepository wraps the original repository but uses SQLite-compatible placeholders
type SQLiteBankingRepository struct {
	db *sql.DB
}

func NewSQLiteBankingRepository(db *sql.DB) *SQLiteBankingRepository {
	return &SQLiteBankingRepository{db: db}
}

func (r *SQLiteBankingRepository) CreateAccount(ctx context.Context, account *bm.Account) error {
	if r.db == nil {
		return errors.New("database connection is nil")
	}
	// Let DB set created_at and updated_at via DEFAULT
	query := `
	INSERT INTO banking_accounts (user_id, account_number, account_type, balance, currency, status)
	VALUES (?, ?, ?, ?, ?, ?)
	RETURNING id`
	fmt.Fprintf(os.Stderr, "DEBUG Repo: CreateAccount user=%s number=%s type=%s bal=%.2f cur=%s status=%s\n", account.UserID, account.AccountNumber, account.AccountType, account.Balance, account.Currency, account.Status)
	err := r.db.QueryRowContext(ctx, query, account.UserID, account.AccountNumber, account.AccountType, account.Balance, account.Currency, account.Status).Scan(&account.ID)
	if err != nil {
		fmt.Fprintf(os.Stderr, "ERROR Repo: CreateAccount failed: %v\n", err)
	}
	return err
}

func (r *SQLiteBankingRepository) GetAccount(ctx context.Context, id int64) (*bm.Account, error) {
	if r.db == nil {
		return nil, errors.New("database connection is nil")
	}
	query := `
	SELECT id, user_id, account_number, account_type, balance, currency, status, created_at, updated_at
	FROM banking_accounts
	WHERE id = ?`
	account := &bm.Account{}
	err := r.db.QueryRowContext(ctx, query, id).Scan(
		&account.ID, &account.UserID, &account.AccountNumber, &account.AccountType,
		&account.Balance, &account.Currency, &account.Status, &account.CreatedAt, &account.UpdatedAt,
	)
	if err != nil {
		return nil, err
	}
	return account, nil
}

func (r *SQLiteBankingRepository) GetAccountByNumber(ctx context.Context, accountNumber string) (*bm.Account, error) {
	if r.db == nil {
		return nil, errors.New("database connection is nil")
	}
	query := `
	SELECT id, user_id, account_number, account_type, balance, currency, status, created_at, updated_at
	FROM banking_accounts
	WHERE account_number = ?`
	account := &bm.Account{}
	err := r.db.QueryRowContext(ctx, query, accountNumber).Scan(
		&account.ID, &account.UserID, &account.AccountNumber, &account.AccountType,
		&account.Balance, &account.Currency, &account.Status, &account.CreatedAt, &account.UpdatedAt,
	)
	if err != nil {
		return nil, err
	}
	return account, nil
}

func (r *SQLiteBankingRepository) GetUserAccounts(ctx context.Context, userID string) ([]*bm.Account, error) {
	if r.db == nil {
		return nil, errors.New("database connection is nil")
	}
	query := `
	SELECT id, user_id, account_number, account_type, balance, currency, status, created_at, updated_at
	FROM banking_accounts
	WHERE user_id = ?`
	rows, err := r.db.QueryContext(ctx, query, userID)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var accounts []*bm.Account
	for rows.Next() {
		account := &bm.Account{}
		err := rows.Scan(
			&account.ID, &account.UserID, &account.AccountNumber, &account.AccountType,
			&account.Balance, &account.Currency, &account.Status, &account.CreatedAt, &account.UpdatedAt,
		)
		if err != nil {
			return nil, err
		}
		accounts = append(accounts, account)
	}
	return accounts, nil
}

func (r *SQLiteBankingRepository) UpdateAccount(ctx context.Context, account *bm.Account) error {
	if r.db == nil {
		return errors.New("database connection is nil")
	}
	query := `
	UPDATE banking_accounts
	SET balance = ?, status = ?, updated_at = ?
	WHERE id = ?`
	_, err := r.db.ExecContext(ctx, query, account.Balance, account.Status, account.UpdatedAt, account.ID)
	return err
}

func (r *SQLiteBankingRepository) CreateTransaction(ctx context.Context, txn *bm.Transaction) error {
	if r.db == nil {
		return errors.New("database connection is nil")
	}
	query := `
	INSERT INTO banking_transactions (account_id, type, amount, balance_after, description, reference_id, status, created_at)
	VALUES (?, ?, ?, ?, ?, ?, ?, ?)
	RETURNING id`
	return r.db.QueryRowContext(ctx, query, txn.AccountID, txn.Type, txn.Amount, txn.BalanceAfter, txn.Description, txn.ReferenceID, txn.Status, txn.CreatedAt).Scan(&txn.ID)
}

func (r *SQLiteBankingRepository) GetTransactions(ctx context.Context, accountID int64, limit, offset int) ([]*bm.Transaction, error) {
	if r.db == nil {
		return nil, errors.New("database connection is nil")
	}
	query := `
	SELECT id, account_id, type, amount, balance_after, description, reference_id, status, created_at
	FROM banking_transactions
	WHERE account_id = ?
	ORDER BY created_at DESC
	LIMIT ? OFFSET ?`
	rows, err := r.db.QueryContext(ctx, query, accountID, limit, offset)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var transactions []*bm.Transaction
	for rows.Next() {
		txn := &bm.Transaction{}
		err := rows.Scan(
			&txn.ID, &txn.AccountID, &txn.Type, &txn.Amount, &txn.BalanceAfter,
			&txn.Description, &txn.ReferenceID, &txn.Status, &txn.CreatedAt,
		)
		if err != nil {
			return nil, err
		}
		transactions = append(transactions, txn)
	}
	return transactions, nil
}
