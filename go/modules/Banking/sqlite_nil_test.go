package banking

import (
	"context"
	"testing"

	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"
	bm "community.local/modules/Banking/internal/models"
)

func TestSQLiteBankingRepository_NilDB(t *testing.T) {
	repo := NewSQLiteBankingRepository(nil)

	_, err := repo.GetAccount(context.Background(), 1)
	require.Error(t, err)

	_, err = repo.GetAccountByNumber(context.Background(), "123")
	require.Error(t, err)

	_, err = repo.GetUserAccounts(context.Background(), "user-1")
	require.Error(t, err)

	err = repo.UpdateAccount(context.Background(), &bm.Account{})
	require.Error(t, err)

	err = repo.CreateAccount(context.Background(), &bm.Account{})
	require.Error(t, err)

	_, err = repo.GetTransactions(context.Background(), 1, 10, 0)
	require.Error(t, err)

	err = repo.CreateTransaction(context.Background(), &bm.Transaction{})
	require.Error(t, err)
}

func TestSQLiteBankingRepository_NilDB_NoPanic(t *testing.T) {
	assert.NotPanics(t, func() {
		_ = NewSQLiteBankingRepository(nil)
	})
}
