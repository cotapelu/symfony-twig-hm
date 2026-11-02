package domain

import (
	"time"

	"app/internal/appcore"
)

// PaymentProcessed represents a payment completion event
type PaymentProcessed struct {
	*appcore.Event
	paymentID    string
	orderID      string
	userID       string
	amount       float64
	currency     string
	status       string
	processedAt  time.Time
}

// NewPaymentProcessed creates a new PaymentProcessed event
func NewPaymentProcessed(paymentID, orderID, userID string, amount float64, currency, status string) *PaymentProcessed {
	event := &PaymentProcessed{
		paymentID:   paymentID,
		orderID:     orderID,
		userID:      userID,
		amount:      amount,
		currency:    currency,
		status:      status,
		processedAt: time.Now().UTC(),
	}

	event.Event = appcore.NewEvent("PaymentProcessed", map[string]interface{}{
		"payment_id":   paymentID,
		"order_id":     orderID,
		"user_id":      userID,
		"amount":       amount,
		"currency":     currency,
		"status":       status,
		"processed_at": event.processedAt.Format(time.RFC3339),
	})

	return event
}

// Getters
func (e *PaymentProcessed) PaymentID() string {
	return e.paymentID
}

func (e *PaymentProcessed) OrderID() string {
	return e.orderID
}

func (e *PaymentProcessed) UserID() string {
	return e.userID
}

func (e *PaymentProcessed) Amount() float64 {
	return e.amount
}

func (e *PaymentProcessed) Currency() string {
	return e.currency
}

func (e *PaymentProcessed) Status() string {
	return e.status
}

func (e *PaymentProcessed) ProcessedAt() time.Time {
	return e.processedAt
}
