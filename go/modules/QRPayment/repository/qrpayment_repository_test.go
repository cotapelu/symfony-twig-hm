package repository

import (
	"context"
	"testing"
	"time"

	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"

	"community.local/modules/QRPayment/internal/models"
)

func newNow() time.Time {
	return time.Now().Truncate(time.Microsecond)
}

func newQRCRepo() *InMemoryQRCRepository {
	return NewInMemoryQRCRepository()
}

func newPaymentRepo() *InMemoryPaymentRepository {
	return NewInMemoryPaymentRepository()
}

// QR Code repository tests

func TestInMemoryQRCRepository_CreateQRCode(t *testing.T) {
	repo := newQRCRepo()
	now := newNow()
	qr := &models.QRCode{
		Code:         "QR123",
		CodeType:     "static",
		MerchantID:   "merchant-1",
		MerchantName: "Test Merchant",
		Amount:       100.0,
		Currency:     "USD",
		Description:  "Test",
		ExpiresAt:    now.Add(24 * time.Hour),
	}
	// Set CreatedAt? repository sets it automatically.
	err := repo.CreateQRCode(context.Background(), qr)
	require.NoError(t, err)

	// Check ID was assigned
	assert.Equal(t, int64(1), qr.ID)
	assert.WithinDuration(t, now, qr.CreatedAt, time.Second)

	// Retrieve by ID
	retrieved, err := repo.GetQRCodeByID(context.Background(), qr.ID)
	require.NoError(t, err)
	assert.Equal(t, qr, retrieved)

	// Retrieve by code
	byCode, err := repo.GetQRCodeByCode(context.Background(), "QR123")
	require.NoError(t, err)
	assert.Equal(t, qr, byCode)
}

func TestInMemoryQRCRepository_GetQRCodeByCode_NotFound(t *testing.T) {
	repo := newQRCRepo()
	retrieved, err := repo.GetQRCodeByCode(context.Background(), "unknown")
	require.NoError(t, err)
	assert.Nil(t, retrieved)
}

func TestInMemoryQRCRepository_GetQRCodeByID_NotFound(t *testing.T) {
	repo := newQRCRepo()
	retrieved, err := repo.GetQRCodeByID(context.Background(), 999)
	require.NoError(t, err)
	assert.Nil(t, retrieved)
}

func TestInMemoryQRCRepository_MarkQRCodeAsUsed(t *testing.T) {
	repo := newQRCRepo()
	now := newNow()
	qr := &models.QRCode{
		Code:       "QR123",
		CodeType:   "static",
		MerchantID: "merchant-1",
		Status:     "active",
		ExpiresAt:  now.Add(24 * time.Hour),
	}
	require.NoError(t, repo.CreateQRCode(context.Background(), qr))

	// Mark as used
	err := repo.MarkQRCodeAsUsed(context.Background(), qr.ID)
	require.NoError(t, err)

	retrieved, err := repo.GetQRCodeByID(context.Background(), qr.ID)
	require.NoError(t, err)
	assert.Equal(t, "used", retrieved.Status)
	assert.NotNil(t, retrieved.UsedAt)
	assert.WithinDuration(t, time.Now(), *retrieved.UsedAt, time.Second)
}

func TestInMemoryQRCRepository_GetActiveQRCodeForMerchant(t *testing.T) {
	repo := newQRCRepo()
	now := newNow()
	// Create several QR codes
	qrs := []*models.QRCode{
		{Code: "QRA", CodeType: "static", MerchantID: "m1", Status: "active", ExpiresAt: now.Add(1 * time.Hour)},
		{Code: "QRB", CodeType: "dynamic", MerchantID: "m1", Status: "active", ExpiresAt: now.Add(1 * time.Hour)},
		{Code: "QRC", CodeType: "static", MerchantID: "m2", Status: "active", ExpiresAt: now.Add(1 * time.Hour)},
		{Code: "QRD", CodeType: "static", MerchantID: "m1", Status: "used", ExpiresAt: now.Add(1 * time.Hour)},
	}
	for _, qr := range qrs {
		require.NoError(t, repo.CreateQRCode(context.Background(), qr))
	}

	// Active static for m1 should return A only
	result, err := repo.GetActiveQRCodeForMerchant(context.Background(), "m1")
	require.NoError(t, err)
	assert.Equal(t, qrs[0], result)

	// m2 returns C
	result2, err := repo.GetActiveQRCodeForMerchant(context.Background(), "m2")
	require.NoError(t, err)
	assert.Equal(t, qrs[2], result2)

	// m3 none
	none, err := repo.GetActiveQRCodeForMerchant(context.Background(), "m3")
	require.NoError(t, err)
	assert.Nil(t, none)
}

func TestInMemoryQRCRepository_GetActiveQRCodeForMerchant_NoMatch(t *testing.T) {
	repo := newQRCRepo()
	now := newNow()
	qr := &models.QRCode{
		Code:       "QRA",
		CodeType:   "dynamic",
		MerchantID: "m1",
		Status:     "active",
		ExpiresAt:  now.Add(1 * time.Hour),
	}
	require.NoError(t, repo.CreateQRCode(context.Background(), qr))
	// Look for static when only dynamic exists
	result, err := repo.GetActiveQRCodeForMerchant(context.Background(), "m1")
	require.NoError(t, err)
	assert.Nil(t, result)
}

// Payment repository tests

func TestInMemoryPaymentRepository_CreatePayment(t *testing.T) {
	repo := newPaymentRepo()
	now := newNow()
	payment := &models.QRCPayment{
		QRCodeID:      1,
		PayerID:       "user-1",
		Amount:        100.0,
		Fee:           2.0,
		NetAmount:     98.0,
		Currency:      "USD",
		PaymentMethod: "card",
		ReferenceID:   "ref-123",
		BankCode:      "XYZ",
		Status:        "pending",
	}
	err := repo.CreatePayment(context.Background(), payment)
	require.NoError(t, err)

	// ID assigned
	assert.Equal(t, int64(1), payment.ID)
	assert.WithinDuration(t, now, payment.CreatedAt, time.Second)

	// Retrieve by ID
	retrieved, err := repo.GetPaymentByID(context.Background(), payment.ID)
	require.NoError(t, err)
	assert.Equal(t, payment, retrieved)
}

func TestInMemoryPaymentRepository_GetPaymentByID_NotFound(t *testing.T) {
	repo := newPaymentRepo()
	retrieved, err := repo.GetPaymentByID(context.Background(), 999)
	require.NoError(t, err)
	assert.Nil(t, retrieved)
}

func TestInMemoryPaymentRepository_GetPaymentByReference(t *testing.T) {
	repo := newPaymentRepo()
	now := newNow()
	payment := &models.QRCPayment{
		QRCodeID:      1,
		PayerID:       "user-1",
		Amount:        100.0,
		Fee:           2.0,
		NetAmount:     98.0,
		Currency:      "USD",
		PaymentMethod: "card",
		ReferenceID:   "ref-123",
		Status:        "completed",
		CreatedAt:     now,
	}
	require.NoError(t, repo.CreatePayment(context.Background(), payment))

	retrieved, err := repo.GetPaymentByReference(context.Background(), "ref-123")
	require.NoError(t, err)
	assert.Equal(t, payment, retrieved)

	// Not found
	none, err := repo.GetPaymentByReference(context.Background(), "ref-unknown")
	require.NoError(t, err)
	assert.Nil(t, none)
}

func TestInMemoryPaymentRepository_GetPaymentsByUser(t *testing.T) {
	repo := newPaymentRepo()
	now := newNow()
	payments := []*models.QRCPayment{
		{PayerID: "user1", Amount: 100, Currency: "USD", Status: "completed", CreatedAt: now},
		{PayerID: "user2", Amount: 200, Currency: "USD", Status: "pending", CreatedAt: now},
		{PayerID: "user1", Amount: 150, Currency: "USD", Status: "failed", CreatedAt: now},
	}
	for _, p := range payments {
		require.NoError(t, repo.CreatePayment(context.Background(), p))
	}

	// Get all for user1 (no pagination)
	results, err := repo.GetPaymentsByUser(context.Background(), "user1", 10, 0)
	require.NoError(t, err)
	assert.ElementsMatch(t, []*models.QRCPayment{payments[0], payments[2]}, results)

	// Get for user2
	results2, err := repo.GetPaymentsByUser(context.Background(), "user2", 10, 0)
	require.NoError(t, err)
	assert.ElementsMatch(t, []*models.QRCPayment{payments[1]}, results2)

	// Empty for unknown user
	empty, err := repo.GetPaymentsByUser(context.Background(), "unknown", 10, 0)
	require.NoError(t, err)
	assert.Empty(t, empty)

	// Pagination offset beyond range should return nil
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

func TestInMemoryPaymentRepository_UpdatePaymentStatus(t *testing.T) {
	repo := newPaymentRepo()
	now := newNow()
	payment := &models.QRCPayment{
		QRCodeID:   1,
		PayerID:    "user-1",
		Amount:     100,
		Currency:   "USD",
		Status:     "pending",
		CreatedAt:  now,
	}
	require.NoError(t, repo.CreatePayment(context.Background(), payment))

	completedAt := now.Add(5 * time.Minute)
	err := repo.UpdatePaymentStatus(context.Background(), payment.ID, "completed", &completedAt)
	require.NoError(t, err)

	retrieved, err := repo.GetPaymentByID(context.Background(), payment.ID)
	require.NoError(t, err)
	assert.Equal(t, "completed", retrieved.Status)
	assert.Equal(t, &completedAt, retrieved.CompletedAt)
}
