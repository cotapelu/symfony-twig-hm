package repository

import (
    "context"
    "testing"
    "time"

    "community.local/modules/QRPayment/internal/models"

    "github.com/stretchr/testify/assert"
    "github.com/stretchr/testify/require"
)

// ==================== QR Code Repository Additional Tests ====================

func TestInMemoryQRCRepository_MarkQRCodeAsUsed_NotFound(t *testing.T) {
    repo := newQRCRepo()
    // Try to mark non-existent QR code - should not error
    err := repo.MarkQRCodeAsUsed(context.Background(), 999)
    require.NoError(t, err)
}

// ==================== Payment Repository Additional Tests ====================

func TestInMemoryPaymentRepository_GetPaymentsByUser_Empty(t *testing.T) {
    repo := newPaymentRepo()
    // No payments for user
    result, err := repo.GetPaymentsByUser(context.Background(), "nonexistent", 20, 0)
    require.NoError(t, err)
    assert.Nil(t, result)
}

func TestInMemoryPaymentRepository_GetPaymentsByUser_OffsetBeyond(t *testing.T) {
    repo := newPaymentRepo()
    now := time.Now()
    // Create 3 payments for user1
    for i := 0; i < 3; i++ {
        p := &models.QRCPayment{
            QRCodeID:      int64(i + 1),
            PayerID:       "user1",
            Amount:        float64(i + 1) * 100,
            Fee:           2.0,
            NetAmount:     float64(i+1)*100 - 2.0,
            Currency:      "USD",
            PaymentMethod: "card",
            ReferenceID:   "ref-" + string(rune(i+'0')),
            Status:        "completed",
            CreatedAt:     now,
        }
        require.NoError(t, repo.CreatePayment(context.Background(), p))
    }

    // Offset beyond length should return nil (no error)
    result, err := repo.GetPaymentsByUser(context.Background(), "user1", 20, 10)
    require.NoError(t, err)
    assert.Nil(t, result)
}

func TestInMemoryPaymentRepository_GetPaymentsByUser_LimitLessThanZero(t *testing.T) {
    repo := newPaymentRepo()
    now := time.Now()
    // Create 3 payments for user1
    for i := 0; i < 3; i++ {
        p := &models.QRCPayment{
            QRCodeID:      int64(i + 1),
            PayerID:       "user1",
            Amount:        float64(i+1) * 100,
            Status:        "completed",
            CreatedAt:     now,
        }
        require.NoError(t, repo.CreatePayment(context.Background(), p))
    }

    // Limit = 0 means zero items requested (no pagination default in this repo)
    result, err := repo.GetPaymentsByUser(context.Background(), "user1", 0, 0)
    require.NoError(t, err)
    // With limit 0, end = offset + 0 = 0, so no items returned
    assert.Len(t, result, 0)
}

func TestInMemoryPaymentRepository_GetPaymentsByUser_LimitGreaterThanAvailable(t *testing.T) {
    repo := newPaymentRepo()
    now := time.Now()
    // Create 2 payments
    for i := 0; i < 2; i++ {
        p := &models.QRCPayment{
            QRCodeID:      int64(i + 1),
            PayerID:       "user1",
            Amount:        100,
            Status:        "completed",
            CreatedAt:     now,
        }
        require.NoError(t, repo.CreatePayment(context.Background(), p))
    }

    // Request 5 but only 2 exist
    result, err := repo.GetPaymentsByUser(context.Background(), "user1", 5, 0)
    require.NoError(t, err)
    assert.Len(t, result, 2)
}
