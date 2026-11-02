package models

import (
	"time"
)

// QRCode represents a payment QR code
type QRCode struct {
	ID           int64      `json:"id"`
	Code         string     `json:"code"`
	CodeType     string     `json:"code_type"` // static, dynamic, emv_qr
	MerchantID   string     `json:"merchant_id"`
	MerchantName string     `json:"merchant_name"`
	Amount       float64    `json:"amount"`
	Currency     string     `json:"currency"`
	Description  string     `json:"description"`
	Status       string     `json:"status"` // active, used, expired
	ExpiresAt    time.Time  `json:"expires_at"`
	UsedAt       *time.Time `json:"used_at"`
	CreatedAt    time.Time  `json:"created_at"`
}

// QRCPayment represents a QR code payment transaction
type QRCPayment struct {
	ID            int64      `json:"id"`
	QRCodeID      int64      `json:"qr_code_id"`
	PayerID       string     `json:"payer_id"`
	Amount        float64    `json:"amount"`
	Fee           float64    `json:"fee"`
	NetAmount     float64    `json:"net_amount"`
	Currency      string     `json:"currency"`
	PaymentMethod string     `json:"payment_method"`
	ReferenceID   string     `json:"reference_id"`
	BankCode      string     `json:"bank_code"`
	Status        string     `json:"status"` // pending, completed, failed, refunded
	CreatedAt     time.Time  `json:"created_at"`
	CompletedAt   *time.Time `json:"completed_at"`
}

// GenerateQRRequest represents a request to generate a QR code
type GenerateQRRequest struct {
	MerchantID     string  `json:"merchant_id"`
	MerchantName   string  `json:"merchant_name"`
	Amount         float64 `json:"amount"`
	Currency       string  `json:"currency"`
	Description    string  `json:"description"`
	ExpiryMinutes  int     `json:"expiry_minutes"`
	IdempotencyKey string  `json:"idempotency_key"`
}

// PaymentRequest represents a request to pay via QR code
type PaymentRequest struct {
	QRCode         string  `json:"qr_code"`
	PayerID        string  `json:"payer_id"`
	Amount         float64 `json:"amount"`
	PaymentMethod  string  `json:"payment_method"`
	BankCode       string  `json:"bank_code"`
	IdempotencyKey string  `json:"idempotency_key"`
}

// PaymentProcessedEvent represents an event emitted when a payment is processed
type PaymentProcessedEvent struct {
	EventType string    `json:"event_type"`
	PaymentID int64     `json:"payment_id"`
	QRCodeID  int64     `json:"qr_code_id"`
	PayerID   string    `json:"payer_id"`
	Amount    float64   `json:"amount"`
	Currency  string    `json:"currency"`
	Status    string    `json:"status"`
	Timestamp time.Time `json:"timestamp"`
}
