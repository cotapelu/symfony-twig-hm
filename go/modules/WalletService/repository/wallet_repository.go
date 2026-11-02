package repository

import (
	"context"
	"database/sql"
	"fmt"
	"time"
	walletservice "community.local/modules/WalletService/internal/models"
)

// Type alias for Wallet
type Wallet = walletservice.Wallet

type WalletRepositoryImpl struct {
	db *sql.DB
}

func NewWalletRepository(db *sql.DB) *WalletRepositoryImpl {
	return &WalletRepositoryImpl{db: db}
}

func (r *WalletRepositoryImpl) CreateWallet(ctx context.Context, wallet *Wallet) error {
	if r.db == nil {
		return fmt.Errorf("database connection is nil")
	}
	query := `
	INSERT INTO wallets (user_id, balance, currency, status, created_at, updated_at)
	VALUES ($1, $2, $3, $4, $5, $6)
	RETURNING id`
	return r.db.QueryRowContext(ctx, query, wallet.UserID, wallet.Balance, wallet.Currency, wallet.Status, wallet.CreatedAt, wallet.UpdatedAt).Scan(&wallet.ID)
}

func (r *WalletRepositoryImpl) GetWallet(ctx context.Context, id int64) (*Wallet, error) {
	if r.db == nil {
		return nil, fmt.Errorf("database connection is nil")
	}
	query := `
	SELECT id, user_id, balance, currency, status, version, created_at, updated_at
	FROM wallets
	WHERE id = $1`
	wallet := &Wallet{}
	err := r.db.QueryRowContext(ctx, query, id).Scan(
		&wallet.ID, &wallet.UserID, &wallet.Balance, &wallet.Currency,
		&wallet.Status, &wallet.Version, &wallet.CreatedAt, &wallet.UpdatedAt,
	)
	if err != nil {
		return nil, err
	}
	return wallet, nil
}

func (r *WalletRepositoryImpl) GetWalletByUserID(ctx context.Context, userID string) (*Wallet, error) {
	if r.db == nil {
		return nil, fmt.Errorf("database connection is nil")
	}
	query := `
	SELECT id, user_id, balance, currency, status, version, created_at, updated_at
	FROM wallets
	WHERE user_id = $1`
	wallet := &Wallet{}
	err := r.db.QueryRowContext(ctx, query, userID).Scan(
		&wallet.ID, &wallet.UserID, &wallet.Balance, &wallet.Currency,
		&wallet.Status, &wallet.Version, &wallet.CreatedAt, &wallet.UpdatedAt,
	)
	if err != nil {
		return nil, err
	}
	return wallet, nil
}

func (r *WalletRepositoryImpl) UpdateWalletBalance(ctx context.Context, walletID int64, amount int64) error {
	if r.db == nil {
		return fmt.Errorf("database connection is nil")
	}
	query := `UPDATE wallets SET balance = balance + $1, updated_at = $2 WHERE id = $3`
	_, err := r.db.ExecContext(ctx, query, amount, time.Now(), walletID)
	return err
}

func (r *WalletRepositoryImpl) UpdateBalanceWithVersion(ctx context.Context, id int64, amount int64, expectedVersion int64) error {
	if r.db == nil {
		return fmt.Errorf("database connection is nil")
	}
	query := `UPDATE wallets SET balance = balance + $1, updated_at = NOW(), version = version + 1 WHERE id = $2 AND version = $3`
	result, err := r.db.ExecContext(ctx, query, amount, id, expectedVersion)
	if err != nil {
		return err
	}
	rowsAffected, err := result.RowsAffected()
	if err != nil {
		return err
	}
	if rowsAffected == 0 {
		return fmt.Errorf("optimistic lock failed: wallet version mismatch")
	}
	return nil
}

func (r *WalletRepositoryImpl) UpdateWalletStatus(ctx context.Context, walletID int64, status string) error {
	if r.db == nil {
		return fmt.Errorf("database connection is nil")
	}
	query := `UPDATE wallets SET status = $1, updated_at = $2 WHERE id = $3`
	_, err := r.db.ExecContext(ctx, query, status, time.Now(), walletID)
	return err
}
