package WalletService

import (
	"context"
	"database/sql"

	"community.local/modules/WalletService/handler"
	walletmodels "community.local/modules/WalletService/internal/models"
	service "community.local/modules/WalletService/service"
)

type nopEventHandler struct{}

func (n *nopEventHandler) EmitEvent(ctx context.Context, eventType string, data interface{}) error {
	return nil
}

// NewHandler creates a new WalletHandler with database connection
func NewHandler(db *sql.DB) *handler.WalletHandler {
	nop := &nopEventHandler{}
	walletRepo := walletmodels.NewWalletRepository(db)
	txnRepo := walletmodels.NewTransactionRepository(db)
	svc := service.NewWalletService(walletRepo, txnRepo, nop)
	return handler.NewWalletHandler(svc)
}
