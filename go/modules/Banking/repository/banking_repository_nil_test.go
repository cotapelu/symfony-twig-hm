package banking

import (
	"context"
	"testing"

	"github.com/stretchr/testify/assert"
)

func TestBankingRepository_NilDB(t *testing.T) {
	repo := &BankingRepositoryImpl{db: nil}

	err := repo.CreateAccount(context.Background(), nil)
	assert.Error(t, err)
	assert.Equal(t, "database connection is nil", err.Error())

	_, err = repo.GetAccount(context.Background(), 1)
	assert.Error(t, err)
	assert.Equal(t, "database connection is nil", err.Error())

	_, err = repo.GetAccountByNumber(context.Background(), "test")
	assert.Error(t, err)
	assert.Equal(t, "database connection is nil", err.Error())

	_, err = repo.GetUserAccounts(context.Background(), "user1")
	assert.Error(t, err)
	assert.Equal(t, "database connection is nil", err.Error())

	err = repo.UpdateAccount(context.Background(), nil)
	assert.Error(t, err)
	assert.Equal(t, "database connection is nil", err.Error())

	_, err = repo.UpdateAccountBalance(context.Background(), 1, 100, true)
	assert.Error(t, err)
	assert.Equal(t, "database connection is nil", err.Error())

	err = repo.CreateTransaction(context.Background(), nil)
	assert.Error(t, err)
	assert.Equal(t, "database connection is nil", err.Error())

	_, err = repo.GetTransactions(context.Background(), 1, 10, 0)
	assert.Error(t, err)
	assert.Equal(t, "database connection is nil", err.Error())

	_, err = repo.GetTransactionByIdempotencyKey(context.Background(), "key")
	assert.Error(t, err)
	assert.Equal(t, "database connection is nil", err.Error())
}