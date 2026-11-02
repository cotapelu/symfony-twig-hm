package repository

import (
	"testing"

	"github.com/stretchr/testify/assert"
)

func TestWalletRepository_NilDB_NoPanic(t *testing.T) {
	assert.NotPanics(t, func() {
		_ = NewWalletRepository(nil)
		_ = NewWalletTransactionRepository(nil)
		_ = NewWalletBalanceRepository(nil)
	})
}
