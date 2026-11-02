package repository

import (
	"context"
	"database/sql"
	"testing"
	"time"

	_ "github.com/mattn/go-sqlite3"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"

	"community.local/modules/QRPayment/internal/models"
)

// setupTestDB creates an in-memory SQLite database with the required schema
func setupTestDB(t *testing.T) *sql.DB {
	t.Helper()
	db, err := sql.Open("sqlite3", ":memory:")
	require.NoError(t, err)

	// Create tables
	_, err = db.Exec(`
		CREATE TABLE qr_codes (
			id INTEGER PRIMARY KEY AUTOINCREMENT,
			code TEXT UNIQUE NOT NULL,
			code_type TEXT NOT NULL,
			merchant_id TEXT NOT NULL,
			merchant_name TEXT NOT NULL,
			amount REAL NOT NULL,
			currency TEXT NOT NULL DEFAULT 'VND',
			description TEXT,
			status TEXT NOT NULL DEFAULT 'active',
			expires_at DATETIME NOT NULL,
			used_at DATETIME,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
		);
	`)
	require.NoError(t, err)

	_, err = db.Exec(`
		CREATE TABLE qr_payments (
			id INTEGER PRIMARY KEY AUTOINCREMENT,
			qr_code_id INTEGER NOT NULL,
			payer_id TEXT NOT NULL,
			amount REAL NOT NULL,
			fee REAL NOT NULL,
			net_amount REAL NOT NULL,
			currency TEXT NOT NULL,
			payment_method TEXT NOT NULL,
			reference_id TEXT UNIQUE NOT NULL,
			bank_code TEXT,
			status TEXT NOT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			completed_at DATETIME
		);
	`)
	require.NoError(t, err)

	return db
}

func TestQRCPostgresRepository_CreateQRCode(t *testing.T) {
	db := setupTestDB(t)
	defer db.Close()

	repo := NewQRCPostgresRepository(db)
	qr := &models.QRCode{
		Code:         "QR123",
		CodeType:     "static",
		MerchantID:   "merchant-1",
		MerchantName: "Test Merchant",
		Amount:       100.0,
		Currency:     "VND",
		Description:  "Test QR",
		ExpiresAt:    time.Now().Add(24 * time.Hour),
	}

	err := repo.CreateQRCode(context.Background(), qr)
	require.NoError(t, err)
	assert.Greater(t, qr.ID, int64(0))

	// Verify it was inserted
	var count int
	err = db.QueryRowContext(context.Background(), "SELECT COUNT(*) FROM qr_codes WHERE code = ?", "QR123").Scan(&count)
	require.NoError(t, err)
	assert.Equal(t, 1, count)
}

func TestQRCPostgresRepository_CreateQRCode_NilDB(t *testing.T) {
	repo := NewQRCPostgresRepository(nil)
	qr := &models.QRCode{}
	err := repo.CreateQRCode(context.Background(), qr)
	assert.Error(t, err)
	assert.Equal(t, "database connection is nil", err.Error())
}

func TestQRCPostgresRepository_GetQRCodeByCode(t *testing.T) {
	db := setupTestDB(t)
	defer db.Close()

	repo := NewQRCPostgresRepository(db)
	qr := &models.QRCode{
		Code:         "QR123",
		CodeType:     "static",
		MerchantID:   "merchant-1",
		MerchantName: "Test Merchant",
		Amount:       100.0,
		Currency:     "VND",
		Description:  "Test QR",
		ExpiresAt:    time.Now().Add(24 * time.Hour),
	}
	require.NoError(t, repo.CreateQRCode(context.Background(), qr))

	result, err := repo.GetQRCodeByCode(context.Background(), "QR123")
	require.NoError(t, err)
	require.NotNil(t, result)
	assert.Equal(t, "QR123", result.Code)
	assert.Equal(t, "static", result.CodeType)
	assert.Equal(t, "merchant-1", result.MerchantID)
}

func TestQRCPostgresRepository_GetQRCodeByCode_NotFound(t *testing.T) {
	db := setupTestDB(t)
	defer db.Close()

	repo := NewQRCPostgresRepository(db)
	result, err := repo.GetQRCodeByCode(context.Background(), "nonexistent")
	require.NoError(t, err)
	assert.Nil(t, result)
}

func TestQRCPostgresRepository_GetQRCodeByID(t *testing.T) {
	db := setupTestDB(t)
	defer db.Close()

	repo := NewQRCPostgresRepository(db)
	qr := &models.QRCode{
		Code:         "QR123",
		CodeType:     "static",
		MerchantID:   "merchant-1",
		MerchantName: "Test Merchant",
		Amount:       100.0,
		Currency:     "VND",
		Description:  "Test QR",
		ExpiresAt:    time.Now().Add(24 * time.Hour),
	}
	require.NoError(t, repo.CreateQRCode(context.Background(), qr))

	result, err := repo.GetQRCodeByID(context.Background(), qr.ID)
	require.NoError(t, err)
	require.NotNil(t, result)
	assert.Equal(t, qr.ID, result.ID)
	assert.Equal(t, "QR123", result.Code)
}

func TestQRCPostgresRepository_MarkQRCodeAsUsed(t *testing.T) {
	db := setupTestDB(t)
	defer db.Close()

	repo := NewQRCPostgresRepository(db)
	qr := &models.QRCode{
		Code:       "QR123",
		CodeType:   "static",
		MerchantID: "merchant-1",
		Status:     "active",
		ExpiresAt:  time.Now().Add(24 * time.Hour),
	}
	require.NoError(t, repo.CreateQRCode(context.Background(), qr))

	err := repo.MarkQRCodeAsUsed(context.Background(), qr.ID)
	require.NoError(t, err)

	// Verify it was marked as used
	var status string
	var usedAt sql.NullTime
	err = db.QueryRowContext(context.Background(), "SELECT status, used_at FROM qr_codes WHERE id = ?", qr.ID).Scan(&status, &usedAt)
	require.NoError(t, err)
	assert.Equal(t, "used", status)
	assert.True(t, usedAt.Valid)
}

func TestPaymentPostgresRepository_CreatePayment(t *testing.T) {
	db := setupTestDB(t)
	defer db.Close()

	repo := NewPaymentPostgresRepository(db)
	payment := &models.QRCPayment{
		QRCodeID:      1,
		PayerID:       "user-1",
		Amount:        100.0,
		Fee:           2.0,
		NetAmount:     98.0,
		Currency:      "VND",
		PaymentMethod: "bank_transfer",
		ReferenceID:   "ref-123",
		BankCode:      "BANK001",
		Status:        "pending",
	}

	err := repo.CreatePayment(context.Background(), payment)
	require.NoError(t, err)
	assert.Greater(t, payment.ID, int64(0))

	// Verify it was inserted
	var count int
	err = db.QueryRowContext(context.Background(), "SELECT COUNT(*) FROM qr_payments WHERE reference_id = ?", "ref-123").Scan(&count)
	require.NoError(t, err)
	assert.Equal(t, 1, count)
}

func TestPaymentPostgresRepository_CreatePayment_NilDB(t *testing.T) {
	repo := NewPaymentPostgresRepository(nil)
	payment := &models.QRCPayment{}
	err := repo.CreatePayment(context.Background(), payment)
	assert.Error(t, err)
	assert.Equal(t, "database connection is nil", err.Error())
}

func TestPaymentPostgresRepository_GetPaymentByID(t *testing.T) {
	db := setupTestDB(t)
	defer db.Close()

	repo := NewPaymentPostgresRepository(db)
	payment := &models.QRCPayment{
		QRCodeID:      1,
		PayerID:       "user-1",
		Amount:        100.0,
		Fee:           2.0,
		NetAmount:     98.0,
		Currency:      "VND",
		PaymentMethod: "bank_transfer",
		ReferenceID:   "ref-123",
		BankCode:      "BANK001",
		Status:        "pending",
	}
	require.NoError(t, repo.CreatePayment(context.Background(), payment))

	result, err := repo.GetPaymentByID(context.Background(), payment.ID)
	require.NoError(t, err)
	require.NotNil(t, result)
	assert.Equal(t, payment.ID, result.ID)
	assert.Equal(t, "user-1", result.PayerID)
	assert.Equal(t, 100.0, result.Amount)
}

func TestPaymentPostgresRepository_GetPaymentByID_NotFound(t *testing.T) {
	db := setupTestDB(t)
	defer db.Close()

	repo := NewPaymentPostgresRepository(db)
	result, err := repo.GetPaymentByID(context.Background(), 999)
	require.NoError(t, err)
	assert.Nil(t, result)
}

func TestPaymentPostgresRepository_GetPaymentByReference(t *testing.T) {
	db := setupTestDB(t)
	defer db.Close()

	repo := NewPaymentPostgresRepository(db)
	payment := &models.QRCPayment{
		QRCodeID:      1,
		PayerID:       "user-1",
		Amount:        100.0,
		Fee:           2.0,
		NetAmount:     98.0,
		Currency:      "VND",
		PaymentMethod: "bank_transfer",
		ReferenceID:   "ref-123",
		BankCode:      "BANK001",
		Status:        "pending",
	}
	require.NoError(t, repo.CreatePayment(context.Background(), payment))

	result, err := repo.GetPaymentByReference(context.Background(), "ref-123")
	require.NoError(t, err)
	require.NotNil(t, result)
	assert.Equal(t, "ref-123", result.ReferenceID)
	assert.Equal(t, "user-1", result.PayerID)
}

func TestPaymentPostgresRepository_GetPaymentByReference_NotFound(t *testing.T) {
	db := setupTestDB(t)
	defer db.Close()

	repo := NewPaymentPostgresRepository(db)
	result, err := repo.GetPaymentByReference(context.Background(), "nonexistent")
	require.NoError(t, err)
	assert.Nil(t, result)
}

func TestPaymentPostgresRepository_GetPaymentsByUser(t *testing.T) {
	db := setupTestDB(t)
	defer db.Close()

	repo := NewPaymentPostgresRepository(db)
	payments := []*models.QRCPayment{
		{PayerID: "user1", Amount: 100, Currency: "VND", Status: "completed", ReferenceID: "ref-1"},
		{PayerID: "user2", Amount: 200, Currency: "VND", Status: "pending", ReferenceID: "ref-2"},
		{PayerID: "user1", Amount: 150, Currency: "VND", Status: "failed", ReferenceID: "ref-3"},
	}
	for _, p := range payments {
		require.NoError(t, repo.CreatePayment(context.Background(), p))
	}

	// Get all for user1 (no pagination)
	results, err := repo.GetPaymentsByUser(context.Background(), "user1", 10, 0)
	require.NoError(t, err)
	assert.Len(t, results, 2)
	for _, r := range results {
		assert.Equal(t, "user1", r.PayerID)
	}

	// Get for user2
	results2, err := repo.GetPaymentsByUser(context.Background(), "user2", 10, 0)
	require.NoError(t, err)
	assert.Len(t, results2, 1)
	assert.Equal(t, "user2", results2[0].PayerID)

	// Empty for unknown user
	empty, err := repo.GetPaymentsByUser(context.Background(), "unknown", 10, 0)
	require.NoError(t, err)
	assert.Empty(t, empty)

	// Pagination offset beyond range should return empty
	offsetResults, err := repo.GetPaymentsByUser(context.Background(), "user1", 2, 10)
	require.NoError(t, err)
	assert.Empty(t, offsetResults)

	// Pagination limit partial
	page1, err := repo.GetPaymentsByUser(context.Background(), "user1", 1, 0)
	require.NoError(t, err)
	assert.Len(t, page1, 1)
	page2, err := repo.GetPaymentsByUser(context.Background(), "user1", 1, 1)
	require.NoError(t, err)
	assert.Len(t, page2, 1)
	assert.NotEqual(t, page1[0].ID, page2[0].ID)
}