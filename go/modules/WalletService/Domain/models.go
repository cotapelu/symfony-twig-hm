package Domain

import (
	"errors"
	"time"

	_ "github.com/lib/pq"
)

var (
	ErrInvalidAmount       = errors.New("amount must be positive and not exceed limit")
	ErrSelfTransfer        = errors.New("cannot transfer to yourself")
	ErrInsufficientBalance = errors.New("insufficient balance")
	ErrWalletLocked        = errors.New("wallet is locked")
	ErrInvalidUserID       = errors.New("invalid user ID")
)

// Wallet represents a user's wallet
type Wallet struct {
	ID        int64     `json:"id"`
	UserID    string    `json:"user_id"`
	Balance   int64     `json:"balance"` // balance in smallest currency unit (e.g., VND)
	Currency  string    `json:"currency"`
	Status    string    `json:"status"` // active, locked
	IsDefault bool      `json:"is_default"`
	Version   int64     `json:"version"` // optimistic locking
	CreatedAt time.Time `json:"created_at"`
	UpdatedAt time.Time `json:"updated_at"`
}

// WalletTransaction represents a wallet transaction
type WalletTransaction struct {
	ID           int64     `json:"id"`
	WalletID     int64     `json:"wallet_id"`
	Type         string    `json:"type"` // topup, withdraw, transfer_out, transfer_in
	Amount       int64     `json:"amount"`        // signed amount (negative for debit)
	BalanceAfter int64     `json:"balance_after"` // balance after this transaction
	Description  string    `json:"description"`
	ReferenceID  string    `json:"reference_id"` // idempotency key or external ref
	Metadata     string    `json:"metadata"`     // JSON metadata
	Status       string    `json:"status"`       // completed, pending, failed
	CreatedAt    time.Time `json:"created_at"`
}

// WalletBalance is a read model for balance queries
type WalletBalance struct {
	ID          int64     `json:"id"`
	WalletID    int64     `json:"wallet_id"`
	Currency    string    `json:"currency"`
	Balance     int64     `json:"balance"`
	LastUpdated time.Time `json:"last_updated"`
}

// TopUpRequest defines the input for topping up a wallet
type TopUpRequest struct {
	UserID         string `json:"user_id"`
	Amount         int64  `json:"amount"` // in smallest currency unit
	Method         string `json:"method"`   // bank_transfer, card, etc.
	BankCode       string `json:"bank_code"`
	CardToken      string `json:"card_token"` // if payment method uses token
	IdempotencyKey string `json:"idempotency_key"`
}

// TransferRequest defines the input for transferring funds
type TransferRequest struct {
	FromUserID     string `json:"from_user_id"`
	ToUserID       string `json:"to_user_id"`
	Amount         int64  `json:"amount"`
	Description    string `json:"description"`
	IdempotencyKey string `json:"idempotency_key"`
}

// WithdrawalRequest defines the input for withdrawing funds
type WithdrawalRequest struct {
	UserID         string `json:"user_id"`
	Amount         int64  `json:"amount"`
	BankCode       string `json:"bank_code"`
	AccountNo      string `json:"account_no"`
	HolderName     string `json:"holder_name"`
	IdempotencyKey string `json:"idempotency_key"`
}

// TransactionEvent is emitted when a wallet transaction occurs
type TransactionEvent struct {
	EventType     string    `json:"event_type"`
	TransactionID int64     `json:"transaction_id"`
	WalletID      int64     `json:"wallet_id"`
	UserID        string    `json:"user_id"`
	Type          string    `json:"type"`
	Amount        int64     `json:"amount"`
	Status        string    `json:"status"`
	Timestamp     time.Time `json:"timestamp"`
}

// Validate performs basic validation on TopUpRequest
func (r *TopUpRequest) Validate() error {
	if r.UserID == "" {
		return ErrInvalidUserID
	}
	if r.Amount <= 0 || r.Amount > 1000000000 {
		return ErrInvalidAmount
	}
	return nil
}

// Validate performs basic validation on TransferRequest
func (r *TransferRequest) Validate() error {
	if r.FromUserID == "" || r.ToUserID == "" {
		return ErrInvalidUserID
	}
	if r.FromUserID == r.ToUserID {
		return ErrSelfTransfer
	}
	if r.Amount <= 0 || r.Amount > 1000000000 {
		return ErrInvalidAmount
	}
	return nil
}

// Validate performs basic validation on WithdrawalRequest
func (r *WithdrawalRequest) Validate() error {
	if r.UserID == "" {
		return ErrInvalidUserID
	}
	if r.Amount <= 0 || r.Amount > 1000000000 {
		return ErrInvalidAmount
	}
	if r.BankCode == "" || r.AccountNo == "" || r.HolderName == "" {
		return errors.New("bank code, account number, and holder name are required")
	}
	return nil
}
