package repository

import (
	"context"
	"database/sql"
	"fmt"
	"time"

	qrpayment "community.local/modules/QRPayment/internal/models"
)

// QRCPostgresRepository implements QRCRepositoryInterface for PostgreSQL.
type QRCPostgresRepository struct {
	db *sql.DB
}

func NewQRCPostgresRepository(db *sql.DB) *QRCPostgresRepository {
	return &QRCPostgresRepository{db: db}
}

func (r *QRCPostgresRepository) CreateQRCode(ctx context.Context, qr *qrpayment.QRCode) error {
	if r.db == nil {
		return fmt.Errorf("database connection is nil")
	}
	query := `
		INSERT INTO qr_codes (code, code_type, merchant_id, merchant_name, amount, currency, description, status, expires_at, created_at)
		VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10)
		RETURNING id
	`
	createdAt := time.Now()
	if qr.CreatedAt.IsZero() {
		qr.CreatedAt = createdAt
	}
	err := r.db.QueryRowContext(ctx, query,
		qr.Code, qr.CodeType, qr.MerchantID, qr.MerchantName,
		qr.Amount, qr.Currency, qr.Description, qr.Status,
		qr.ExpiresAt, qr.CreatedAt,
	).Scan(&qr.ID)
	if err != nil {
		return fmt.Errorf("failed to insert qr_code: %w", err)
	}
	return nil
}

func (r *QRCPostgresRepository) GetQRCodeByCode(ctx context.Context, code string) (*qrpayment.QRCode, error) {
	if r.db == nil {
		return nil, fmt.Errorf("database connection is nil")
	}
	query := `
		SELECT id, code, code_type, merchant_id, merchant_name, amount, currency, description, status, expires_at, used_at, created_at
		FROM qr_codes WHERE code = $1
	`
	qr := &qrpayment.QRCode{}
	err := r.db.QueryRowContext(ctx, query, code).Scan(
		&qr.ID, &qr.Code, &qr.CodeType, &qr.MerchantID, &qr.MerchantName,
		&qr.Amount, &qr.Currency, &qr.Description, &qr.Status,
		&qr.ExpiresAt, &qr.UsedAt, &qr.CreatedAt,
	)
	if err != nil {
		if err == sql.ErrNoRows {
			return nil, nil
		}
		return nil, fmt.Errorf("failed to get qr_code by code: %w", err)
	}
	return qr, nil
}

func (r *QRCPostgresRepository) GetQRCodeByID(ctx context.Context, id int64) (*qrpayment.QRCode, error) {
	if r.db == nil {
		return nil, fmt.Errorf("database connection is nil")
	}
	query := `
		SELECT id, code, code_type, merchant_id, merchant_name, amount, currency, description, status, expires_at, used_at, created_at
		FROM qr_codes WHERE id = $1
	`
	qr := &qrpayment.QRCode{}
	err := r.db.QueryRowContext(ctx, query, id).Scan(
		&qr.ID, &qr.Code, &qr.CodeType, &qr.MerchantID, &qr.MerchantName,
		&qr.Amount, &qr.Currency, &qr.Description, &qr.Status,
		&qr.ExpiresAt, &qr.UsedAt, &qr.CreatedAt,
	)
	if err != nil {
		if err == sql.ErrNoRows {
			return nil, nil
		}
		return nil, fmt.Errorf("failed to get qr_code by id: %w", err)
	}
	return qr, nil
}

func (r *QRCPostgresRepository) MarkQRCodeAsUsed(ctx context.Context, id int64) error {
	if r.db == nil {
		return fmt.Errorf("database connection is nil")
	}
	query := `UPDATE qr_codes SET status = 'used', used_at = $1 WHERE id = $2`
	now := time.Now()
	_, err := r.db.ExecContext(ctx, query, now, id)
	if err != nil {
		return fmt.Errorf("failed to mark qr_code as used: %w", err)
	}
	return nil
}

// PaymentPostgresRepository implements PaymentRepositoryInterface for PostgreSQL.
type PaymentPostgresRepository struct {
	db *sql.DB
}

func NewPaymentPostgresRepository(db *sql.DB) *PaymentPostgresRepository {
	return &PaymentPostgresRepository{db: db}
}

func (r *PaymentPostgresRepository) CreatePayment(ctx context.Context, p *qrpayment.QRCPayment) error {
	if r.db == nil {
		return fmt.Errorf("database connection is nil")
	}
	query := `
		INSERT INTO qr_payments (qr_code_id, payer_id, amount, fee, net_amount, currency, payment_method, reference_id, bank_code, status, created_at, completed_at)
		VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12)
		RETURNING id
	`
	createdAt := time.Now()
	if p.CreatedAt.IsZero() {
		p.CreatedAt = createdAt
	}
	err := r.db.QueryRowContext(ctx, query,
		p.QRCodeID, p.PayerID, p.Amount, p.Fee, p.NetAmount,
		p.Currency, p.PaymentMethod, p.ReferenceID, p.BankCode,
		p.Status, p.CreatedAt, p.CompletedAt,
	).Scan(&p.ID)
	if err != nil {
		return fmt.Errorf("failed to insert qr_payment: %w", err)
	}
	return nil
}

func (r *PaymentPostgresRepository) GetPaymentByID(ctx context.Context, id int64) (*qrpayment.QRCPayment, error) {
	if r.db == nil {
		return nil, fmt.Errorf("database connection is nil")
	}
	query := `
		SELECT id, qr_code_id, payer_id, amount, fee, net_amount, currency, payment_method, reference_id, bank_code, status, created_at, completed_at
		FROM qr_payments WHERE id = $1
	`
	p := &qrpayment.QRCPayment{}
	err := r.db.QueryRowContext(ctx, query, id).Scan(
		&p.ID, &p.QRCodeID, &p.PayerID, &p.Amount, &p.Fee, &p.NetAmount,
		&p.Currency, &p.PaymentMethod, &p.ReferenceID, &p.BankCode,
		&p.Status, &p.CreatedAt, &p.CompletedAt,
	)
	if err != nil {
		if err == sql.ErrNoRows {
			return nil, nil
		}
		return nil, fmt.Errorf("failed to get payment by id: %w", err)
	}
	return p, nil
}

func (r *PaymentPostgresRepository) GetPaymentByReference(ctx context.Context, refID string) (*qrpayment.QRCPayment, error) {
	if r.db == nil {
		return nil, fmt.Errorf("database connection is nil")
	}
	query := `
		SELECT id, qr_code_id, payer_id, amount, fee, net_amount, currency, payment_method, reference_id, bank_code, status, created_at, completed_at
		FROM qr_payments WHERE reference_id = $1
	`
	p := &qrpayment.QRCPayment{}
	err := r.db.QueryRowContext(ctx, query, refID).Scan(
		&p.ID, &p.QRCodeID, &p.PayerID, &p.Amount, &p.Fee, &p.NetAmount,
		&p.Currency, &p.PaymentMethod, &p.ReferenceID, &p.BankCode,
		&p.Status, &p.CreatedAt, &p.CompletedAt,
	)
	if err != nil {
		if err == sql.ErrNoRows {
			return nil, nil
		}
		return nil, fmt.Errorf("failed to get payment by reference: %w", err)
	}
	return p, nil
}

func (r *PaymentPostgresRepository) GetPaymentsByUser(ctx context.Context, userID string, limit, offset int) ([]*qrpayment.QRCPayment, error) {
	if r.db == nil {
		return nil, fmt.Errorf("database connection is nil")
	}
	if limit <= 0 {
		limit = 20
	}
	if limit > 100 {
		limit = 100
	}
	query := `
		SELECT id, qr_code_id, payer_id, amount, fee, net_amount, currency, payment_method, reference_id, bank_code, status, created_at, completed_at
		FROM qr_payments
		WHERE payer_id = $1
		ORDER BY created_at DESC
		LIMIT $2 OFFSET $3
	`
	rows, err := r.db.QueryContext(ctx, query, userID, limit, offset)
	if err != nil {
		return nil, fmt.Errorf("failed to query payments by user: %w", err)
	}
	defer rows.Close()

	var payments []*qrpayment.QRCPayment
	for rows.Next() {
		p := &qrpayment.QRCPayment{}
		err := rows.Scan(
			&p.ID, &p.QRCodeID, &p.PayerID, &p.Amount, &p.Fee, &p.NetAmount,
			&p.Currency, &p.PaymentMethod, &p.ReferenceID, &p.BankCode,
			&p.Status, &p.CreatedAt, &p.CompletedAt,
		)
		if err != nil {
			return nil, fmt.Errorf("failed to scan payment row: %w", err)
		}
		payments = append(payments, p)
	}
	if err := rows.Err(); err != nil {
		return nil, fmt.Errorf("row iteration error: %w", err)
	}
	return payments, nil
}
