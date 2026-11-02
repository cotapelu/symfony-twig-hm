package repository

import (
	"context"
	"database/sql"
	"errors"
	"community.local/modules/Escrow/models"
)

type EscrowRepository struct {
	db *sql.DB
}

func NewEscrowRepository(db *sql.DB) *EscrowRepository {
	return &EscrowRepository{db: db}
}

func (r *EscrowRepository) Create(ctx context.Context, e *models.Escrow) error {
	if r.db == nil {
		return errors.New("database connection is nil")
	}
	query := `
	INSERT INTO escrow (id, buyer_id, seller_id, amount, currency, status, contract_address, tx_hash, created_at, updated_at)
	VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10)`
	_, err := r.db.ExecContext(ctx, query, e.ID, e.BuyerID, e.SellerID, e.Amount, e.Currency, e.Status, e.Contract, e.TxHash, e.CreatedAt, e.UpdatedAt)
	return err
}

func (r *EscrowRepository) GetByID(ctx context.Context, id string) (*models.Escrow, error) {
	if r.db == nil {
		return nil, errors.New("database connection is nil")
	}
	query := `
	SELECT id, buyer_id, seller_id, amount, currency, status, contract_address, tx_hash, created_at, updated_at
	FROM escrow
	WHERE id = $1`
	e := &models.Escrow{}
	err := r.db.QueryRowContext(ctx, query, id).Scan(
		&e.ID, &e.BuyerID, &e.SellerID, &e.Amount, &e.Currency, &e.Status,
		&e.Contract, &e.TxHash, &e.CreatedAt, &e.UpdatedAt,
	)
	if err != nil {
		return nil, err
	}
	return e, nil
}

func (r *EscrowRepository) Update(ctx context.Context, e *models.Escrow) error {
	if r.db == nil {
		return errors.New("database connection is nil")
	}
	query := `
	UPDATE escrow
	SET status = $1, contract_address = $2, tx_hash = $3, updated_at = $4
	WHERE id = $5`
	_, err := r.db.ExecContext(ctx, query, e.Status, e.Contract, e.TxHash, e.UpdatedAt, e.ID)
	return err
}
