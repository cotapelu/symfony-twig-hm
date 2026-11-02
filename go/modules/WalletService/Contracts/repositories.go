package Contracts

import (
	"context"
	"database/sql"

	"community.local/modules/WalletService/Domain"
)

// WalletRepositoryInterface defines the contract for wallet persistence
type WalletRepositoryInterface interface {
	CreateWallet(ctx context.Context, w *Domain.Wallet) error
	GetWallet(ctx context.Context, id int64) (*Domain.Wallet, error)
	GetWalletByUser(ctx context.Context, userID string) (*Domain.Wallet, error)
	GetWalletForUpdate(ctx context.Context, userID string) (*Domain.Wallet, error) // FOR UPDATE locking
	UpdateBalance(ctx context.Context, id int64, amount int64) error
	UpdateBalanceWithVersion(ctx context.Context, id int64, amount int64, expectedVersion int64) error
	HasSufficientBalance(ctx context.Context, userID string, amount int64) (bool, error)
	DeleteWallet(ctx context.Context, id int64) error
	UpdateStatus(ctx context.Context, id int64, status string) error
	DB() *sql.DB // expose DB for transaction management (if needed)
}

// TransactionRepositoryInterface defines the contract for transaction persistence
type TransactionRepositoryInterface interface {
	CreateTransaction(ctx context.Context, t *Domain.WalletTransaction) error
	GetByWallet(ctx context.Context, walletID int64, limit, offset int) ([]*Domain.WalletTransaction, error)
	GetByReference(ctx context.Context, refID string) (*Domain.WalletTransaction, error)
}

// EventHandler interface for emitting domain events
type EventHandler interface {
	EmitEvent(ctx context.Context, eventType string, data interface{}) error
}

// WalletServiceInterface defines the application service contract
type WalletServiceInterface interface {
	CreateWallet(ctx context.Context, userID, currency string) (*Domain.Wallet, error)
	GetWallet(ctx context.Context, userID string) (*Domain.Wallet, error)
	GetBalance(ctx context.Context, userID string) (int64, error)
	GetTransactions(ctx context.Context, userID string, limit, offset int) ([]*Domain.WalletTransaction, error)
	TopUp(ctx context.Context, req *Domain.TopUpRequest) (*Domain.WalletTransaction, error)
	Transfer(ctx context.Context, req *Domain.TransferRequest) (*Domain.WalletTransaction, error)
	Withdraw(ctx context.Context, req *Domain.WithdrawalRequest) (*Domain.WalletTransaction, error)
	DeleteWallet(ctx context.Context, userID string) error
	LockWallet(ctx context.Context, userID, reason string) error
	UnlockWallet(ctx context.Context, userID string) error
	ExportTransactions(ctx context.Context, userID string, limit, offset int) ([]*Domain.WalletTransaction, error)
}
