package billspayment

import (
	"database/sql"

	"community.local/modules/BillsPayment/handler"
	bp "community.local/modules/BillsPayment/internal/models"
)

// NewHandler creates a new BillsPaymentHandler with database connection
func NewHandler(db *sql.DB) *handler.BillsPaymentHandler {
	svc := bp.NewBillsPaymentService(db, nil) // no event handler for now
	return handler.NewBillsPaymentHandler(svc)
}
