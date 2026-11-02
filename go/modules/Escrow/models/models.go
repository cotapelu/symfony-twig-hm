package models

import (
	"time"

	"github.com/google/uuid"
)

type EscrowStatus string

const (
	EscrowStatusPending   = "pending"
	EscrowStatusFunded    = "funded"
	EscrowStatusReleased  = "released"
	EscrowStatusRefunded  = "refunded"
	EscrowStatusExpired   = "expired"
)

type Escrow struct {
	ID        string    `json:"id" db:"id"`
	BuyerID   string    `json:"buyer_id" db:"buyer_id"`
	SellerID  string    `json:"seller_id" db:"seller_id"`
	Amount    float64   `json:"amount" db:"amount"`
	Currency  string    `json:"currency" db:"currency"`
	Status    string    `json:"status" db:"status"`
	Contract  string    `json:"contract_address,omitempty" db:"contract_address"` // if on-chain
	TxHash    string    `json:"tx_hash,omitempty" db:"tx_hash"`                 // on-chain tx
	CreatedAt time.Time `json:"created_at" db:"created_at"`
	UpdatedAt time.Time `json:"updated_at" db:"updated_at"`
}

func NewEscrow(buyerID, sellerID string, amount float64, currency string) *Escrow {
	now := time.Now()
	return &Escrow{
		ID:        uuid.NewString(),
		BuyerID:   buyerID,
		SellerID:  sellerID,
		Amount:    amount,
		Currency:  currency,
		Status:    EscrowStatusPending,
		CreatedAt: now,
		UpdatedAt: now,
	}
}
