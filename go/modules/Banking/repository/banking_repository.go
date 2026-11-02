package banking

import (
	"context"
	"database/sql"
	"errors"
	"time"

	bankingmodels "community.local/modules/Banking/internal/models"
)

type BankingRepositoryImpl struct {
	db *sql.DB
}

func NewBankingRepository(db *sql.DB) *BankingRepositoryImpl {
	return &BankingRepositoryImpl{db: db}
}

func (r *BankingRepositoryImpl) CreateAccount(ctx context.Context, account *bankingmodels.Account) error {
	if r.db == nil {
		return errors.New("database connection is nil")
	}
	query := `
		INSERT INTO banking_accounts (user_id, account_number, account_type, balance, currency, status, created_at, updated_at)
		VALUES ($1, $2, $3, $4, $5, $6, $7, $8)
		RETURNING id
	`
	return r.db.QueryRowContext(ctx, query,
		account.UserID, account.AccountNumber, account.AccountType,
		account.Balance, account.Currency, account.Status,
		account.CreatedAt, account.UpdatedAt,
	).Scan(&account.ID)
}

func (r *BankingRepositoryImpl) GetAccount(ctx context.Context, id int64) (*bankingmodels.Account, error) {
	if r.db == nil {
		return nil, errors.New("database connection is nil")
	}
	query := `
		SELECT id, user_id, account_number, account_type, balance, currency, status, created_at, updated_at
		FROM banking_accounts WHERE id = $1
	`
	account := &bankingmodels.Account{}
	err := r.db.QueryRowContext(ctx, query, id).Scan(
		&account.ID, &account.UserID, &account.AccountNumber, &account.AccountType,
		&account.Balance, &account.Currency, &account.Status,
		&account.CreatedAt, &account.UpdatedAt,
	)
	if err != nil {
		return nil, err
	}
	return account, nil
}

func (r *BankingRepositoryImpl) GetAccountByNumber(ctx context.Context, accountNumber string) (*bankingmodels.Account, error) {
	if r.db == nil {
		return nil, errors.New("database connection is nil")
	}
	query := `
		SELECT id, user_id, account_number, account_type, balance, currency, status, created_at, updated_at
		FROM banking_accounts WHERE account_number = $1
	`
	account := &bankingmodels.Account{}
	err := r.db.QueryRowContext(ctx, query, accountNumber).Scan(
		&account.ID, &account.UserID, &account.AccountNumber, &account.AccountType,
		&account.Balance, &account.Currency, &account.Status,
		&account.CreatedAt, &account.UpdatedAt,
	)
	if err != nil {
		return nil, err
	}
	return account, nil
}

func (r *BankingRepositoryImpl) GetUserAccounts(ctx context.Context, userID string) ([]*bankingmodels.Account, error) {
	if r.db == nil {
		return nil, errors.New("database connection is nil")
	}
	query := `
		SELECT id, user_id, account_number, account_type, balance, currency, status, created_at, updated_at
		FROM banking_accounts WHERE user_id = $1
	`
	rows, err := r.db.QueryContext(ctx, query, userID)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var accounts []*bankingmodels.Account
	for rows.Next() {
		account := &bankingmodels.Account{}
		err := rows.Scan(
			&account.ID, &account.UserID, &account.AccountNumber, &account.AccountType,
			&account.Balance, &account.Currency, &account.Status,
			&account.CreatedAt, &account.UpdatedAt,
		)
		if err != nil {
			return nil, err
		}
		accounts = append(accounts, account)
	}
	return accounts, nil
}

func (r *BankingRepositoryImpl) UpdateAccount(ctx context.Context, account *bankingmodels.Account) error {
	if r.db == nil {
		return errors.New("database connection is nil")
	}
	query := `
	UPDATE banking_accounts
	SET balance = $1, status = $2, updated_at = $3
	WHERE id = $4
	`
	_, err := r.db.ExecContext(ctx, query, account.Balance, account.Status, account.UpdatedAt, account.ID)
	return err
}

func (r *BankingRepositoryImpl) UpdateAccountBalance(ctx context.Context, accountID int64, amount float64, isDeposit bool) (float64, error) {
	if r.db == nil {
		return 0, errors.New("database connection is nil")
	}
	var newBalance float64
	var query string

	if isDeposit {
		query = `UPDATE banking_accounts SET balance = balance + $1, updated_at = $2 WHERE id = $3 RETURNING balance`
	} else {
		query = `UPDATE banking_accounts SET balance = balance - $1, updated_at = $2 WHERE id = $3 RETURNING balance`
	}

	err := r.db.QueryRowContext(ctx, query, amount, time.Now(), accountID).Scan(&newBalance)
	if err != nil {
		return 0, err
	}
	return newBalance, nil
}

func (r *BankingRepositoryImpl) CreateTransaction(ctx context.Context, txn *bankingmodels.Transaction) error {
	if r.db == nil {
		return errors.New("database connection is nil")
	}
	query := `
		INSERT INTO banking_transactions (account_id, type, amount, balance_after, description, reference_id, status, created_at)
		VALUES ($1, $2, $3, $4, $5, $6, $7, $8)
		RETURNING id
	`
	return r.db.QueryRowContext(ctx, query,
		txn.AccountID, txn.Type, txn.Amount, txn.BalanceAfter,
		txn.Description, txn.ReferenceID, txn.Status, txn.CreatedAt,
	).Scan(&txn.ID)
}

func (r *BankingRepositoryImpl) GetTransactions(ctx context.Context, accountID int64, limit, offset int) ([]*bankingmodels.Transaction, error) {
	if r.db == nil {
		return nil, errors.New("database connection is nil")
	}
	query := `
		SELECT id, account_id, type, amount, balance_after, description, reference_id, status, created_at
		FROM banking_transactions 
		WHERE account_id = $1
		ORDER BY created_at DESC
		LIMIT $2 OFFSET $3
	`
	rows, err := r.db.QueryContext(ctx, query, accountID, limit, offset)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var transactions []*bankingmodels.Transaction
	for rows.Next() {
		txn := &bankingmodels.Transaction{}
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

func (r *BankingRepositoryImpl) GetTransactionByIdempotencyKey(ctx context.Context, key string) (*bankingmodels.Transaction, error) {
	if r.db == nil {
		return nil, errors.New("database connection is nil")
	}
	query := `
		SELECT id, account_id, type, amount, balance_after, description, reference_id, status, created_at
		FROM banking_transactions
		WHERE reference_id = $1
		ORDER BY created_at DESC
		LIMIT 1
	`
	txn := &bankingmodels.Transaction{}
	err := r.db.QueryRowContext(ctx, query, key).Scan(
		&txn.ID, &txn.AccountID, &txn.Type, &txn.Amount, &txn.BalanceAfter,
		&txn.Description, &txn.ReferenceID, &txn.Status, &txn.CreatedAt,
	)
	if err != nil {
		if errors.Is(err, sql.ErrNoRows) {
			return nil, nil // Not found is not an error, just nil result
		}
		return nil, err
	}
	return txn, nil
}

