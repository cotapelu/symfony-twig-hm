package repository

import (
	"context"
	"testing"

	qrpayment "community.local/modules/QRPayment/internal/models"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"
)

// NilDB tests ensure repository methods handle nil DB without panic.
// This keeps abnormal-path evidence measurable in CI.
func TestQRCPostgresRepository_NilDB(t *testing.T) {
	repo := NewQRCPostgresRepository(nil)

	err := repo.CreateQRCode(context.Background(), &qrpayment.QRCode{})
	require.Error(t, err)

	_, err = repo.GetQRCodeByCode(context.Background(), "QR-ABC")
	require.Error(t, err)

	_, err = repo.GetQRCodeByID(context.Background(), 1)
	require.Error(t, err)

	err = repo.MarkQRCodeAsUsed(context.Background(), 1)
	require.Error(t, err)
}

func TestPaymentPostgresRepository_NilDB(t *testing.T) {
	repo := NewPaymentPostgresRepository(nil)

	_, err := repo.GetPaymentByID(context.Background(), 1)
	require.Error(t, err)

	_, err = repo.GetPaymentByReference(context.Background(), "ref-abc")
	require.Error(t, err)

	_, err = repo.GetPaymentsByUser(context.Background(), "user-1", 10, 0)
	require.Error(t, err)
}

func TestPostgresRepositories_NilDB_NoPanic(t *testing.T) {
	// Smoke check: nil DB constructors should not panic.
	assert.NotPanics(t, func() {
		_ = NewQRCPostgresRepository(nil)
		_ = NewPaymentPostgresRepository(nil)
	})
}
