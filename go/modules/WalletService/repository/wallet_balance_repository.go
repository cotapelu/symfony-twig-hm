package repository

import (
	"context"
	"database/sql"
	"errors"
	walletservice "community.local/modules/WalletService/internal/models"
)

type WalletBalanceRepositoryImpl struct {
	db *sql.DB
}

func NewWalletBalanceRepository(db *sql.DB) *WalletBalanceRepositoryImpl {
	return &WalletBalanceRepositoryImpl{db: db}
}

func (r *WalletBalanceRepositoryImpl) GetBalance(ctx context.Context, walletID int64) (*walletservice.WalletBalance, error) {
	if r.db == nil {
		return nil, errors.New("database connection is nil")
	}
	query := `
	SELECT id, wallet_id, currency, balance, last_updated
	FROM wallet_balances
	WHERE wallet_id = $1`
	balance := &walletservice.WalletBalance{}
	err := r.db.QueryRowContext(ctx, query, walletID).Scan(
		&balance.ID, &balance.WalletID, &balance.Currency, &balance.Balance, &balance.LastUpdated,
	)
	if err != nil {
		return nil, err
	}
	return balance, nil
}

func (r *WalletBalanceRepositoryImpl) UpdateBalance(ctx context.Context, walletID int64, amount int64) error {
	if r.db == nil {
		return errors.New("database connection is nil")
	}
	query := `UPDATE wallet_balances SET balance = balance + $1, last_updated = NOW() WHERE wallet_id = $2`
	_, err := r.db.ExecContext(ctx, query, amount, walletID)
	return err
}

func (r *WalletBalanceRepositoryImpl) CreateBalance(ctx context.Context, balance *walletservice.WalletBalance) error {
	if r.db == nil {
		return errors.New("database connection is nil")
	}
	query := `
	INSERT INTO wallet_balances (wallet_id, currency, balance, last_updated)
	VALUES ($1, $2, $3, $4)
	RETURNING id`
	return r.db.QueryRowContext(ctx, query, balance.WalletID, balance.Currency, balance.Balance, balance.LastUpdated).Scan(&balance.ID)
}
