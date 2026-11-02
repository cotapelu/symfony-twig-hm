package repository

import (
	"context"
	"database/sql"
	"errors"
	walletservice "community.local/modules/WalletService/internal/models"
)

type WalletTransaction = walletservice.WalletTransaction

type WalletTransactionRepositoryImpl struct {
	db *sql.DB
}

func NewWalletTransactionRepository(db *sql.DB) *WalletTransactionRepositoryImpl {
	return &WalletTransactionRepositoryImpl{db: db}
}

func (r *WalletTransactionRepositoryImpl) CreateTransaction(ctx context.Context, txn *walletservice.WalletTransaction) error {
	if r.db == nil {
		return errors.New("database connection is nil")
	}
	query := `
	INSERT INTO wallet_transactions (wallet_id, type, amount, balance_after, description, reference_id, status, created_at)
	VALUES ($1, $2, $3, $4, $5, $6, $7, $8)
	RETURNING id`
	return r.db.QueryRowContext(ctx, query, txn.WalletID, txn.Type, txn.Amount, txn.BalanceAfter, txn.Description, txn.ReferenceID, txn.Status, txn.CreatedAt).Scan(&txn.ID)
}

func (r *WalletTransactionRepositoryImpl) GetTransaction(ctx context.Context, id int64) (*walletservice.WalletTransaction, error) {
	if r.db == nil {
		return nil, errors.New("database connection is nil")
	}
	query := `
	SELECT id, wallet_id, type, amount, balance_after, description, reference_id, status, created_at
	FROM wallet_transactions
	WHERE id = $1`
	txn := &walletservice.WalletTransaction{}
	err := r.db.QueryRowContext(ctx, query, id).Scan(
		&txn.ID, &txn.WalletID, &txn.Type, &txn.Amount, &txn.BalanceAfter, &txn.Description, &txn.ReferenceID, &txn.Status, &txn.CreatedAt,
	)
	if err != nil {
		return nil, err
	}
	return txn, nil
}

func (r *WalletTransactionRepositoryImpl) GetTransactionsByWallet(ctx context.Context, walletID int64, limit, offset int) ([]*walletservice.WalletTransaction, error) {
	if r.db == nil {
		return nil, errors.New("database connection is nil")
	}
	query := `
	SELECT id, wallet_id, type, amount, balance_after, description, reference_id, status, created_at
	FROM wallet_transactions
	WHERE wallet_id = $1
	ORDER BY created_at DESC
	LIMIT $2 OFFSET $3`
	rows, err := r.db.QueryContext(ctx, query, walletID, limit, offset)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var transactions []*walletservice.WalletTransaction
	for rows.Next() {
		txn := &walletservice.WalletTransaction{}
		err := rows.Scan(
			&txn.ID, &txn.WalletID, &txn.Type, &txn.Amount, &txn.BalanceAfter, &txn.Description, &txn.ReferenceID, &txn.Status, &txn.CreatedAt,
		)
		if err != nil {
			return nil, err
		}
		transactions = append(transactions, txn)
	}
	return transactions, nil
}

func (r *WalletTransactionRepositoryImpl) UpdateTransactionStatus(ctx context.Context, transactionID int64, status string) error {
	if r.db == nil {
		return errors.New("database connection is nil")
	}
	query := `UPDATE wallet_transactions SET status = $1 WHERE id = $2`
	_, err := r.db.ExecContext(ctx, query, status, transactionID)
	return err
}
