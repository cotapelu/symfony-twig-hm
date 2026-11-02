package billspayment

import (
	"context"
	"crypto/rand"
	"database/sql"
	"encoding/hex"
	"errors"
	"fmt"
	"time"

	_ "github.com/lib/pq"
)

// Tx interface abstracts database transaction operations for testability.
type Tx interface {
	Commit() error
	Rollback() error
}

// sqlTx adapts *sql.Tx to implement Tx.
type sqlTx struct{ *sql.Tx }

func (st *sqlTx) Commit() error { return st.Tx.Commit() }
func (st *sqlTx) Rollback() error { return st.Tx.Rollback() }

type Bill struct {
	ID            int64      `json:"id"`
	BillType      string     `json:"bill_type"`
	Provider      string     `json:"provider"`
	AccountNumber string     `json:"account_number"`
	CustomerName  string     `json:"customer_name"`
	Amount        float64    `json:"amount"`
	Period        string     `json:"period"`
	Status        string     `json:"status"`
	DueDate       time.Time  `json:"due_date"`
	PaidAt        *time.Time `json:"paid_at"`
	CreatedAt     time.Time  `json:"created_at"`
}

type BillPayment struct {
	ID            int64     `json:"id"`
	BillID        int64     `json:"bill_id"`
	UserID        string    `json:"user_id"`
	Amount        float64   `json:"amount"`
	PaymentMethod string    `json:"payment_method"`
	TransactionID string    `json:"transaction_id"`
	Status        string    `json:"status"`
	CreatedAt     time.Time `json:"created_at"`
}

type BillProvider struct {
	ID            int64   `json:"id"`
	Name          string  `json:"name"`
	Code          string  `json:"code"`
	Category      string  `json:"category"`
	ProcessingFee float64 `json:"processing_fee"`
	MinAmount     float64 `json:"min_amount"`
	MaxAmount     float64 `json:"max_amount"`
	IsActive      bool    `json:"is_active"`
}

// BillPaidEvent represents an event emitted when a bill is paid
type BillPaidEvent struct {
	EventType string    `json:"event_type"`
	BillID    int64     `json:"bill_id"`
	PaymentID int64     `json:"payment_id"`
	UserID    string    `json:"user_id"`
	Amount    float64   `json:"amount"`
	Status    string    `json:"status"`
	Timestamp time.Time `json:"timestamp"`
}

// BillRepository handles database operations for bills
type BillRepository struct {
	db *sql.DB
}

// NewBillRepository creates a new bill repository
func NewBillRepository(db *sql.DB) *BillRepository {
	return &BillRepository{db: db}
}

// DB returns the underlying database connection
func (r *BillRepository) DB() *sql.DB {
	return r.db
}

func (r *BillRepository) CreateBill(ctx context.Context, b *Bill) error {
	if r.db == nil {
		return fmt.Errorf("database connection is nil")
	}
	query := `INSERT INTO bills (bill_type, provider, account_number, customer_name, amount, period, status, due_date)
	          VALUES ($1, $2, $3, $4, $5, $6, $7, $8) RETURNING id, created_at`
	return r.db.QueryRowContext(ctx, query, b.BillType, b.Provider, b.AccountNumber, b.CustomerName, b.Amount, b.Period, b.Status, b.DueDate).Scan(&b.ID, &b.CreatedAt)
}

func (r *BillRepository) GetBill(ctx context.Context, id int64) (*Bill, error) {
	if r.db == nil {
		return nil, fmt.Errorf("database connection is nil")
	}
	b := &Bill{}
	query := `SELECT id, bill_type, provider, account_number, customer_name, amount, period, status, due_date, paid_at, created_at
	          FROM bills WHERE id = $1`
	err := r.db.QueryRowContext(ctx, query, id).Scan(&b.ID, &b.BillType, &b.Provider, &b.AccountNumber, &b.CustomerName, &b.Amount, &b.Period, &b.Status, &b.DueDate, &b.PaidAt, &b.CreatedAt)
	if err != nil {
		return nil, err
	}
	return b, nil
}

func (r *BillRepository) GetBillsByAccount(ctx context.Context, accNum string) ([]*Bill, error) {
	if r.db == nil {
		return nil, fmt.Errorf("database connection is nil")
	}
	rows, err := r.db.QueryContext(ctx, `SELECT id, bill_type, provider, account_number, customer_name, amount, period, status, due_date, paid_at, created_at
	                        FROM bills WHERE account_number = $1 ORDER BY created_at DESC`, accNum)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var bills []*Bill
	for rows.Next() {
		b := &Bill{}
		if err := rows.Scan(&b.ID, &b.BillType, &b.Provider, &b.AccountNumber, &b.CustomerName, &b.Amount, &b.Period, &b.Status, &b.DueDate, &b.PaidAt, &b.CreatedAt); err != nil {
			return nil, err
		}
		bills = append(bills, b)
	}
	if err := rows.Err(); err != nil {
		return nil, err
	}
	return bills, nil
}

func (r *BillRepository) GetUnpaidBills(ctx context.Context) ([]*Bill, error) {
	if r.db == nil {
		return nil, fmt.Errorf("database connection is nil")
	}
	rows, err := r.db.QueryContext(ctx, `SELECT id, bill_type, provider, account_number, customer_name, amount, period, status, due_date, paid_at, created_at
	                        FROM bills WHERE status = 'unpaid' AND due_date >= NOW() ORDER BY due_date ASC`)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var bills []*Bill
	for rows.Next() {
		b := &Bill{}
		if err := rows.Scan(&b.ID, &b.BillType, &b.Provider, &b.AccountNumber, &b.CustomerName, &b.Amount, &b.Period, &b.Status, &b.DueDate, &b.PaidAt, &b.CreatedAt); err != nil {
			return nil, err
		}
		bills = append(bills, b)
	}
	return bills, nil
}

func (r *BillRepository) MarkAsPaid(ctx context.Context, id int64) error {
	if r.db == nil {
		return fmt.Errorf("database connection is nil")
	}
	query := `UPDATE bills SET status = 'paid', paid_at = CURRENT_TIMESTAMP WHERE id = $1`
	res, err := r.db.ExecContext(ctx, query, id)
	if err != nil {
		return err
	}
	rowsAffected, _ := res.RowsAffected()
	if rowsAffected == 0 {
		return errors.New("bill not found")
	}
	return nil
}

func (r *BillRepository) GetUpcomingBills(ctx context.Context, days int) ([]*Bill, error) {
	if r.db == nil {
		return nil, fmt.Errorf("database connection is nil")
	}
	rows, err := r.db.QueryContext(ctx, `SELECT id, bill_type, provider, account_number, customer_name, amount, period, status, due_date, paid_at, created_at
	                                 FROM bills WHERE status = 'unpaid' AND due_date BETWEEN NOW() AND NOW() + INTERVAL '1 day' * $1 ORDER BY due_date ASC`, days)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var bills []*Bill
	for rows.Next() {
		b := &Bill{}
		if err := rows.Scan(&b.ID, &b.BillType, &b.Provider, &b.AccountNumber, &b.CustomerName, &b.Amount, &b.Period, &b.Status, &b.DueDate, &b.PaidAt, &b.CreatedAt); err != nil {
			return nil, err
		}
		bills = append(bills, b)
	}
	return bills, nil
}

type PaymentRepository struct {
	db *sql.DB
}

func NewPaymentRepository(db *sql.DB) *PaymentRepository {
	return &PaymentRepository{db: db}
}

func (r *PaymentRepository) CreatePayment(ctx context.Context, p *BillPayment) error {
	if r.db == nil {
		return fmt.Errorf("database connection is nil")
	}
	query := `INSERT INTO bill_payments (bill_id, user_id, amount, payment_method, transaction_id, status)
	          VALUES ($1, $2, $3, $4, $5, $6) RETURNING id, created_at`
	return r.db.QueryRowContext(ctx, query, p.BillID, p.UserID, p.Amount, p.PaymentMethod, p.TransactionID, p.Status).Scan(&p.ID, &p.CreatedAt)
}

func (r *PaymentRepository) GetPayment(ctx context.Context, id int64) (*BillPayment, error) {
	if r.db == nil {
		return nil, fmt.Errorf("database connection is nil")
	}
	p := &BillPayment{}
	query := `SELECT id, bill_id, user_id, amount, payment_method, transaction_id, status, created_at
	          FROM bill_payments WHERE id = $1`
	err := r.db.QueryRowContext(ctx, query, id).Scan(&p.ID, &p.BillID, &p.UserID, &p.Amount, &p.PaymentMethod, &p.TransactionID, &p.Status, &p.CreatedAt)
	if err != nil {
		return nil, err
	}
	return p, nil
}

func (r *PaymentRepository) GetPaymentsByUser(ctx context.Context, userID string) ([]*BillPayment, error) {
	if r.db == nil {
		return nil, fmt.Errorf("database connection is nil")
	}
	rows, err := r.db.QueryContext(ctx, `SELECT id, bill_id, user_id, amount, payment_method, transaction_id, status, created_at
	                        FROM bill_payments WHERE user_id = $1 ORDER BY created_at DESC`, userID)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var payments []*BillPayment
	for rows.Next() {
		p := &BillPayment{}
		if err := rows.Scan(&p.ID, &p.BillID, &p.UserID, &p.Amount, &p.PaymentMethod, &p.TransactionID, &p.Status, &p.CreatedAt); err != nil {
			return nil, err
		}
		payments = append(payments, p)
	}
	return payments, nil
}

func (r *PaymentRepository) GetPaymentByTransaction(ctx context.Context, txID string) (*BillPayment, error) {
	if r.db == nil {
		return nil, fmt.Errorf("database connection is nil")
	}
	p := &BillPayment{}
	query := `SELECT id, bill_id, user_id, amount, payment_method, transaction_id, status, created_at
	          FROM bill_payments WHERE transaction_id = $1`
	err := r.db.QueryRowContext(ctx, query, txID).Scan(&p.ID, &p.BillID, &p.UserID, &p.Amount, &p.PaymentMethod, &p.TransactionID, &p.Status, &p.CreatedAt)
	if err != nil {
		return nil, err
	}
	return p, nil
}

type ProviderRepository struct {
	db *sql.DB
}

func NewProviderRepository(db *sql.DB) *ProviderRepository {
	return &ProviderRepository{db: db}
}

func (r *ProviderRepository) GetAll(ctx context.Context) ([]*BillProvider, error) {
	if r.db == nil {
		return nil, fmt.Errorf("database connection is nil")
	}
	rows, err := r.db.QueryContext(ctx, `SELECT id, name, code, category, processing_fee, min_amount, max_amount, is_active
	                        FROM bill_providers WHERE is_active = true`)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var providers []*BillProvider
	for rows.Next() {
		p := &BillProvider{}
		if err := rows.Scan(&p.ID, &p.Name, &p.Code, &p.Category, &p.ProcessingFee, &p.MinAmount, &p.MaxAmount, &p.IsActive); err != nil {
			return nil, err
		}
		providers = append(providers, p)
	}
	return providers, nil
}

func (r *ProviderRepository) GetByCategory(ctx context.Context, category string) ([]*BillProvider, error) {
	if r.db == nil {
		return nil, fmt.Errorf("database connection is nil")
	}
	rows, err := r.db.QueryContext(ctx, `SELECT id, name, code, category, processing_fee, min_amount, max_amount, is_active
	                        FROM bill_providers WHERE category = $1 AND is_active = true`, category)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var providers []*BillProvider
	for rows.Next() {
		p := &BillProvider{}
		if err := rows.Scan(&p.ID, &p.Name, &p.Code, &p.Category, &p.ProcessingFee, &p.MinAmount, &p.MaxAmount, &p.IsActive); err != nil {
			return nil, err
		}
		providers = append(providers, p)
	}
	return providers, nil
}

// EventHandler is interface for emitting events
type EventHandler interface {
	EmitEvent(ctx context.Context, eventType string, data interface{}) error
}

// BillRepositoryInterface defines repository contract for bills
type BillRepositoryInterface interface {
	CreateBill(ctx context.Context, b *Bill) error
	GetBill(ctx context.Context, id int64) (*Bill, error)
	GetBillsByAccount(ctx context.Context, accNum string) ([]*Bill, error)
	GetUpcomingBills(ctx context.Context, days int) ([]*Bill, error)
	MarkAsPaid(ctx context.Context, id int64) error
	DB() *sql.DB
}

// PaymentRepositoryInterface defines repository contract for payments
type PaymentRepositoryInterface interface {
	CreatePayment(ctx context.Context, p *BillPayment) error
	GetPayment(ctx context.Context, id int64) (*BillPayment, error)
	GetPaymentsByUser(ctx context.Context, userID string) ([]*BillPayment, error)
	GetPaymentByTransaction(ctx context.Context, txID string) (*BillPayment, error)
}

// ProviderRepositoryInterface defines repository contract for providers
type ProviderRepositoryInterface interface {
	GetAll(ctx context.Context) ([]*BillProvider, error)
	GetByCategory(ctx context.Context, category string) ([]*BillProvider, error)
}

type BillsPaymentService struct {
	billRepo     BillRepositoryInterface
	paymentRepo  PaymentRepositoryInterface
	providerRepo ProviderRepositoryInterface
	eventHandler EventHandler
	beginTx      func(context.Context, *sql.TxOptions) (Tx, error) // for testability
}

func NewBillsPaymentService(db *sql.DB, eventHandler EventHandler) *BillsPaymentService {
	svc := &BillsPaymentService{
		billRepo:     NewBillRepository(db),
		paymentRepo:  NewPaymentRepository(db),
		providerRepo: NewProviderRepository(db),
		eventHandler: eventHandler,
	}
	svc.beginTx = func(ctx context.Context, opts *sql.TxOptions) (Tx, error) {
		tx, err := db.BeginTx(ctx, opts)
		if err != nil {
			return nil, err
		}
		return &sqlTx{Tx: tx}, nil
	}
	return svc
}

func (s *BillsPaymentService) CreateBill(ctx context.Context, billType, provider, accNum, custName string, amount float64, period string, dueDate time.Time) (*Bill, error) {
	if amount <= 0 {
		return nil, fmt.Errorf("amount must be greater than zero")
	}

	bill := &Bill{
		BillType:      billType,
		Provider:      provider,
		AccountNumber: accNum,
		CustomerName:  custName,
		Amount:        amount,
		Period:        period,
		Status:        "unpaid",
		DueDate:       dueDate,
	}
	if err := s.billRepo.CreateBill(ctx, bill); err != nil {
		return nil, err
	}
	return bill, nil
}

func (s *BillsPaymentService) GetBill(ctx context.Context, id int64) (*Bill, error) {
	return s.billRepo.GetBill(ctx, id)
}

func (s *BillsPaymentService) GetBillsByAccount(ctx context.Context, accNum string) ([]*Bill, error) {
	return s.billRepo.GetBillsByAccount(ctx, accNum)
}

func (s *BillsPaymentService) GetProviders(ctx context.Context) ([]*BillProvider, error) {
	return s.providerRepo.GetAll(ctx)
}

func (s *BillsPaymentService) GetProvidersByCategory(ctx context.Context, category string) ([]*BillProvider, error) {
	return s.providerRepo.GetByCategory(ctx, category)
}

func (s *BillsPaymentService) PayBill(ctx context.Context, billID int64, userID, method string) (*BillPayment, error) {
	tx, err := s.beginTx(ctx, nil)
	if err != nil {
		return nil, fmt.Errorf("failed to begin transaction: %w", err)
	}
	defer func() {
		if p := recover(); p != nil {
			tx.Rollback()
		}
	}()

	bill, err := s.billRepo.GetBill(ctx, billID)
	if err != nil {
		tx.Rollback()
		return nil, fmt.Errorf("bill not found: %w", err)
	}

	if bill.Status == "paid" {
		tx.Rollback()
		return nil, fmt.Errorf("bill already paid")
	}

	payment := &BillPayment{
		BillID:        billID,
		UserID:        userID,
		Amount:        bill.Amount,
		PaymentMethod: method,
		TransactionID: generateTransactionID(billID),
		Status:        "completed",
	}

	if err := s.paymentRepo.CreatePayment(ctx, payment); err != nil {
		tx.Rollback()
		return nil, fmt.Errorf("failed to create payment: %w", err)
	}

	if err := s.billRepo.MarkAsPaid(ctx, billID); err != nil {
		tx.Rollback()
		return nil, fmt.Errorf("failed to mark bill as paid: %w", err)
	}

	if err := tx.Commit(); err != nil {
		return nil, fmt.Errorf("failed to commit transaction: %w", err)
	}

	if s.eventHandler != nil {
		event := BillPaidEvent{
			EventType: "bill.paid",
			BillID:    billID,
			PaymentID: payment.ID,
			UserID:    userID,
			Amount:    bill.Amount,
			Status:    "completed",
			Timestamp: time.Now(),
		}
		_ = s.eventHandler.EmitEvent(ctx, "bill.payment", event)
	}

	return payment, nil
}

func (s *BillsPaymentService) GetPayments(ctx context.Context, userID string) ([]*BillPayment, error) {
	return s.paymentRepo.GetPaymentsByUser(ctx, userID)
}

func (s *BillsPaymentService) GetPaymentByTransaction(ctx context.Context, txID string) (*BillPayment, error) {
	return s.paymentRepo.GetPaymentByTransaction(ctx, txID)
}

func (s *BillsPaymentService) GetUpcomingBills(ctx context.Context, days int) ([]*Bill, error) {
	return s.billRepo.GetUpcomingBills(ctx, days)
}

func generateTransactionID(billID int64) string {
	bytes := make([]byte, 4)
	rand.Read(bytes)
	return fmt.Sprintf("BILLPAY-%d-%s-%d", billID, hex.EncodeToString(bytes), time.Now().UnixNano())
}
