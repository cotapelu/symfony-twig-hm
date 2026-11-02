package banking

import (
	"database/sql"

	"community.local/modules/Banking/handler"
	bm "community.local/modules/Banking/internal/models"
)

// NewHandler creates a new BankingHandler with database connection
func NewHandler(db *sql.DB) *handler.BankingHandler {
	svc := bm.NewBankingService(db, nil) // nil event handler
	return handler.NewBankingHandler(svc)
}
