package banking

import (
	"context"
	"database/sql"
	"testing"

	bankingmodels "community.local/modules/Banking/internal/models"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/mock"
)

// Additional tests to increase coverage for BankingService

func TestGetAccountByNumberSuccess(t *testing.T) {
	repo := new(MockRepository)
	svc := NewBankingService(repo)

	account := &bankingmodels.Account{
		ID:            1,
		UserID:        "user123",
		AccountNumber: "ACC-123",
		Balance:       500.0,
	}

	repo.On("GetAccountByNumber", mock.Anything, "ACC-123").Return(account, nil)

	result, err := svc.GetAccountByNumber(context.Background(), "ACC-123")

	assert.NoError(t, err)
	assert.Equal(t, account, result)
}

func TestGetAccountByNumberNotFound(t *testing.T) {
	repo := new(MockRepository)
	svc := NewBankingService(repo)

	repo.On("GetAccountByNumber", mock.Anything, "UNKNOWN").Return(nil, sql.ErrNoRows)

	result, err := svc.GetAccountByNumber(context.Background(), "UNKNOWN")

	assert.Error(t, err)
	assert.Nil(t, result)
	assert.Equal(t, ErrAccountNotFound, err)
}

func TestGetUserAccountsSuccess(t *testing.T) {
	repo := new(MockRepository)
	svc := NewBankingService(repo)

	accounts := []*bankingmodels.Account{
		{ID: 1, UserID: "user123", AccountNumber: "A1", Balance: 100},
		{ID: 2, UserID: "user123", AccountNumber: "A2", Balance: 200},
	}

	repo.On("GetUserAccounts", mock.Anything, "user123").Return(accounts, nil)

	result, err := svc.GetUserAccounts(context.Background(), "user123")

	assert.NoError(t, err)
	assert.Equal(t, 2, len(result))
	assert.Equal(t, accounts, result)
}

func TestGetBalanceSuccess(t *testing.T) {
	repo := new(MockRepository)
	svc := NewBankingService(repo)

	account := &bankingmodels.Account{
		ID:     1,
		Balance: 1234.56,
	}

	repo.On("GetAccount", mock.Anything, int64(1)).Return(account, nil)

	balance, err := svc.GetBalance(context.Background(), 1)

	assert.NoError(t, err)
	assert.Equal(t, 1234.56, balance)
}

func TestTransferInvalidAmount(t *testing.T) {
	repo := new(MockRepository)
	svc := NewBankingService(repo)

	_, err := svc.Transfer(context.Background(), &bankingmodels.TransferRequest{
		FromAccount: "A",
		ToAccount:   "B",
		Amount:      -50,
		Description: "test",
	})

	assert.Error(t, err)
	assert.Equal(t, ErrInvalidAmount, err)
}

func TestTransferSuccess(t *testing.T) {
	repo := new(MockRepository)
	svc := NewBankingService(repo)

	fromAccount := &bankingmodels.Account{
		ID:            1,
		AccountNumber: "FROM",
		Balance:       200.0,
		Status:        "active",
	}
	toAccount := &bankingmodels.Account{
		ID:            2,
		AccountNumber: "TO",
		Balance:       100.0,
		Status:        "active",
	}

	// Expectations: GetAccountByNumber for from and to, then UpdateAccount twice, then CreateTransaction
	repo.On("GetAccountByNumber", mock.Anything, "FROM").Return(fromAccount, nil)
	repo.On("GetAccountByNumber", mock.Anything, "TO").Return(toAccount, nil)

	repo.On("UpdateAccount", mock.Anything, mock.Anything).Return(nil).Once()
	repo.On("UpdateAccount", mock.Anything, mock.Anything).Return(nil).Once()

	repo.On("CreateTransaction", mock.Anything, mock.Anything).Return(nil)

	req := &bankingmodels.TransferRequest{
		FromAccount: "FROM",
		ToAccount:   "TO",
		Amount:      100.0,
		Description: "transfer test",
	}
	txn, err := svc.Transfer(context.Background(), req)

	assert.NoError(t, err)
	assert.NotNil(t, txn)
	assert.Equal(t, "transfer", txn.Type)
	assert.Equal(t, fromAccount.ID, txn.AccountID)
	assert.Equal(t, 100.0, txn.BalanceAfter)
	assert.Equal(t, "TO", txn.ReferenceID)
}

func TestTransferInsufficientBalance(t *testing.T) {
	repo := new(MockRepository)
	svc := NewBankingService(repo)

	fromAccount := &bankingmodels.Account{
		ID:     1,
		Balance: 50.0,
		Status:  "active",
	}

	repo.On("GetAccountByNumber", mock.Anything, "FROM").Return(fromAccount, nil)

	req := &bankingmodels.TransferRequest{
		FromAccount: "FROM",
		ToAccount:   "TO",
		Amount:      100.0,
		Description: "test",
	}

	_, err := svc.Transfer(context.Background(), req)

	assert.Error(t, err)
	assert.Equal(t, ErrInsufficientBalance, err)
}

func TestTransferToAccountNotFound(t *testing.T) {
	repo := new(MockRepository)
	svc := NewBankingService(repo)

	fromAccount := &bankingmodels.Account{
		ID:     1,
		Balance: 200.0,
		Status:  "active",
	}

	repo.On("GetAccountByNumber", mock.Anything, "FROM").Return(fromAccount, nil)
	repo.On("GetAccountByNumber", mock.Anything, "TO").Return(nil, sql.ErrNoRows)

	req := &bankingmodels.TransferRequest{
		FromAccount: "FROM",
		ToAccount:   "TO",
		Amount:      50.0,
		Description: "test",
	}

	_, err := svc.Transfer(context.Background(), req)

	assert.Error(t, err)
	assert.Equal(t, ErrAccountNotFound, err)
}

func TestTransferToAccountNotActive(t *testing.T) {
	repo := new(MockRepository)
	svc := NewBankingService(repo)

	fromAccount := &bankingmodels.Account{
		ID:     1,
		Balance: 200.0,
		Status:  "active",
	}
	toAccount := &bankingmodels.Account{
		ID:     2,
		Status: "frozen",
	}

	repo.On("GetAccountByNumber", mock.Anything, "FROM").Return(fromAccount, nil)
	repo.On("GetAccountByNumber", mock.Anything, "TO").Return(toAccount, nil)

	req := &bankingmodels.TransferRequest{
		FromAccount: "FROM",
		ToAccount:   "TO",
		Amount:      50.0,
		Description: "test",
	}

	_, err := svc.Transfer(context.Background(), req)

	assert.Error(t, err)
	assert.Equal(t, ErrAccountNotActive, err)
}

func TestWithdrawSuccess(t *testing.T) {
	repo := new(MockRepository)
	svc := NewBankingService(repo)

	account := &bankingmodels.Account{
		ID:     1,
		Balance: 500.0,
		Status: "active",
	}

	repo.On("GetAccount", mock.Anything, int64(1)).Return(account, nil)
	repo.On("UpdateAccountBalance", mock.Anything, int64(1), 100.0, false).Return(400.0, nil)
	repo.On("CreateTransaction", mock.Anything, mock.Anything).Return(nil)

	txn, err := svc.Withdraw(context.Background(), 1, 100.0, "withdraw test")

	assert.NoError(t, err)
	assert.NotNil(t, txn)
	assert.Equal(t, "withdrawal", txn.Type)
	assert.Equal(t, 400.0, txn.BalanceAfter)
}
