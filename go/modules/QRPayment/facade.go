package QRPayment

import (
	"context"
	"database/sql"

	"community.local/modules/QRPayment/handler"
	qr "community.local/modules/QRPayment/service"
)

type nopEventHandler struct{}

func (n *nopEventHandler) EmitEvent(ctx context.Context, eventType string, data interface{}) error {
	return nil
}

// NewHandler creates a new QRPaymentHandler with database connection
func NewHandler(db *sql.DB) *handler.QRPaymentHandler {
	nop := &nopEventHandler{}
	svc := qr.NewQRPaymentService(db, nop)
	return handler.NewQRPaymentHandler(svc)
}

