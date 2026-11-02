package billspayment

import (
	"context"
	"testing"

	"github.com/stretchr/testify/assert"
)

func TestBillRepository_NilDB(t *testing.T) {
	repo := &BillRepository{db: nil}

	err := repo.CreateBill(context.Background(), nil)
	assert.Error(t, err)
	assert.Equal(t, "database connection is nil", err.Error())

	_, err = repo.GetBill(context.Background(), 1)
	assert.Error(t, err)
	assert.Equal(t, "database connection is nil", err.Error())

	_, err = repo.GetBillsByAccount(context.Background(), "test")
	assert.Error(t, err)
	assert.Equal(t, "database connection is nil", err.Error())

	_, err = repo.GetUnpaidBills(context.Background())
	assert.Error(t, err)
	assert.Equal(t, "database connection is nil", err.Error())

	err = repo.MarkAsPaid(context.Background(), 1)
	assert.Error(t, err)
	assert.Equal(t, "database connection is nil", err.Error())

	_, err = repo.GetUpcomingBills(context.Background(), 7)
	assert.Error(t, err)
	assert.Equal(t, "database connection is nil", err.Error())
}

func TestPaymentRepository_NilDB(t *testing.T) {
	repo := &PaymentRepository{db: nil}

	err := repo.CreatePayment(context.Background(), nil)
	assert.Error(t, err)
	assert.Equal(t, "database connection is nil", err.Error())

	_, err = repo.GetPayment(context.Background(), 1)
	assert.Error(t, err)
	assert.Equal(t, "database connection is nil", err.Error())

	_, err = repo.GetPaymentsByUser(context.Background(), "user1")
	assert.Error(t, err)
	assert.Equal(t, "database connection is nil", err.Error())

	_, err = repo.GetPaymentByTransaction(context.Background(), "tx1")
	assert.Error(t, err)
	assert.Equal(t, "database connection is nil", err.Error())
}

func TestProviderRepository_NilDB(t *testing.T) {
	repo := &ProviderRepository{db: nil}

	_, err := repo.GetAll(context.Background())
	assert.Error(t, err)
	assert.Equal(t, "database connection is nil", err.Error())

	_, err = repo.GetByCategory(context.Background(), "utility")
	assert.Error(t, err)
	assert.Equal(t, "database connection is nil", err.Error())
}