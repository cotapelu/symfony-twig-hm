package CreditLending

import (
	"database/sql"

	"community.local/modules/CreditLending/handler"
)

// NewHandler creates a new CreditLendingHandler
func NewHandler(db *sql.DB) *handler.CreditLendingHandler {
	return handler.NewCreditLendingHandler(db)
}
