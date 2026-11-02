package billspayment

import (
	"context"
	"database/sql"
	"testing"

	_ "github.com/mattn/go-sqlite3"
	"github.com/stretchr/testify/assert"

	"community.local/testing/helper"
)

func setupBillsFullSchema(t *testing.T, db *sql.DB) {
	t.Helper()
	_, _ = db.Exec("PRAGMA foreign_keys = ON")

	// providers
	_, err := db.Exec(`
		CREATE TABLE bill_providers (
			id INTEGER PRIMARY KEY AUTOINCREMENT,
			name TEXT NOT NULL,
			code TEXT UNIQUE NOT NULL,
			category TEXT NOT NULL,
			processing_fee REAL NOT NULL,
			min_amount REAL NOT NULL,
			max_amount REAL NOT NULL,
			is_active BOOLEAN NOT NULL DEFAULT true
		);
	`)
	if err != nil {
		t.Fatalf("create providers failed: %v", err)
	}

	// bills
	_, err = db.Exec(`
		CREATE TABLE bills (
			id INTEGER PRIMARY KEY AUTOINCREMENT,
			bill_type TEXT NOT NULL,
			provider TEXT NOT NULL,
			account_number TEXT NOT NULL,
			customer_name TEXT NOT NULL,
			amount REAL NOT NULL,
			period TEXT,
			status TEXT NOT NULL DEFAULT 'unpaid',
			due_date DATETIME,
			paid_at DATETIME,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
		);
	`)
	if err != nil {
		t.Fatalf("create bills failed: %v", err)
	}

	// bill_payments
	_, err = db.Exec(`
		CREATE TABLE bill_payments (
			id INTEGER PRIMARY KEY AUTOINCREMENT,
			bill_id INTEGER NOT NULL REFERENCES bills(id),
			user_id TEXT NOT NULL,
			amount REAL NOT NULL,
			payment_method TEXT NOT NULL,
			transaction_id TEXT,
			status TEXT NOT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
		);
	`)
	if err != nil {
		t.Fatalf("create payments failed: %v", err)
	}
}

func TestPaymentRepository_CreateAndGet(t *testing.T) {
	db := helper.SetupTestDB(t)
	defer db.Close()
	setupBillsFullSchema(t, db)

	// Insert a bill first (direct SQL for simplicity)
	_, err := db.Exec(`INSERT INTO bills (bill_type, provider, account_number, customer_name, amount, period, status, due_date) VALUES ($1, $2, $3, $4, $5, $6, $7, $8)`,
		"electricity", "ElecCo", "ACC-001", "John", 200000, "2025-01", "unpaid", "2025-01-31")
	if err != nil {
		t.Fatalf("insert bill failed: %v", err)
	}
	// Get last inserted bill id
	var billID int64
	err = db.QueryRowContext(context.Background(), `SELECT last_insert_rowid()`).Scan(&billID)
	if err != nil {
		t.Fatalf("get last bill id failed: %v", err)
	}

	paymentRepo := NewPaymentRepository(db)
	ctx := context.Background()

	payment := &BillPayment{
		BillID:         billID,
		UserID:         "user123",
		Amount:         200000,
		PaymentMethod:  "bank_transfer",
		TransactionID:  "TX-001",
		Status:         "completed",
	}
	err = paymentRepo.CreatePayment(ctx, payment)
	assert.NoError(t, err)
	assert.NotZero(t, payment.ID)
	assert.NotZero(t, payment.CreatedAt)

	// GetPayment
	fetched, err := paymentRepo.GetPayment(ctx, payment.ID)
	assert.NoError(t, err)
	assert.Equal(t, payment.ID, fetched.ID)
	assert.Equal(t, "bank_transfer", fetched.PaymentMethod)

	// GetPaymentsByUser
	payments, err := paymentRepo.GetPaymentsByUser(ctx, "user123")
	assert.NoError(t, err)
	assert.Len(t, payments, 1)
	assert.Equal(t, payment.ID, payments[0].ID)

	// GetPaymentByTransaction
	byTx, err := paymentRepo.GetPaymentByTransaction(ctx, "TX-001")
	assert.NoError(t, err)
	assert.Equal(t, payment.ID, byTx.ID)
}
