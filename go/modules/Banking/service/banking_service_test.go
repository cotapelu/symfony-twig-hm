package banking

import (
	"context"
	"database/sql"
	"testing"

	bankingmodels "community.local/modules/Banking/internal/models"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/mock"
)

// MockRepository implements BankingRepository for testing
type MockRepository struct {
	mock.Mock
}

func (m *MockRepository) CreateAccount(ctx context.Context, account *bankingmodels.Account) error {
	args := m.Called(ctx, account)
	return args.Error(0)
}

func (m *MockRepository) GetAccount(ctx context.Context, id int64) (*bankingmodels.Account, error) {
	args := m.Called(ctx, id)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).(*bankingmodels.Account), args.Error(1)
}

func (m *MockRepository) GetAccountByNumber(ctx context.Context, accountNumber string) (*bankingmodels.Account, error) {
	args := m.Called(ctx, accountNumber)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).(*bankingmodels.Account), args.Error(1)
}

func (m *MockRepository) GetUserAccounts(ctx context.Context, userID string) ([]*bankingmodels.Account, error) {
	args := m.Called(ctx, userID)
	return args.Get(0).([]*bankingmodels.Account), args.Error(1)
}

func (m *MockRepository) UpdateAccount(ctx context.Context, account *bankingmodels.Account) error {
	args := m.Called(ctx, account)
	return args.Error(0)
}

func (m *MockRepository) UpdateAccountBalance(ctx context.Context, accountID int64, amount float64, isDeposit bool) (float64, error) {
	args := m.Called(ctx, accountID, amount, isDeposit)
	return args.Get(0).(float64), args.Error(1)
}

func (m *MockRepository) CreateTransaction(ctx context.Context, txn *bankingmodels.Transaction) error {
	args := m.Called(ctx, txn)
	return args.Error(0)
}

func (m *MockRepository) GetTransactionByIdempotencyKey(ctx context.Context, key string) (*bankingmodels.Transaction, error) {
	args := m.Called(ctx, key)
	return args.Get(0).(*bankingmodels.Transaction), args.Error(1)
}

func (m *MockRepository) GetTransactions(ctx context.Context, accountID int64, limit, offset int) ([]*bankingmodels.Transaction, error) {
	args := m.Called(ctx, accountID, limit, offset)
	return args.Get(0).([]*bankingmodels.Transaction), args.Error(1)
}

func (m *MockRepository) WithTransaction(ctx context.Context, fn func(repo BankingRepository) error) error {
	args := m.Called(ctx, fn)
	return args.Error(0)
}

func TestNewBankingService(t *testing.T) {
	repo := new(MockRepository)
	svc := NewBankingService(repo)
	assert.NotNil(t, svc)
}

func TestCreateAccount(t *testing.T) {
	repo := new(MockRepository)
	svc := NewBankingService(repo)
	
	repo.On("CreateAccount", mock.Anything, mock.Anything).Return(nil)
	
	account, err := svc.CreateAccount(context.Background(), "user123", "checking", "USD")
	
	assert.NoError(t, err)
	assert.NotNil(t, account)
	assert.Equal(t, "user123", account.UserID)
	assert.Equal(t, "checking", account.AccountType)
	assert.Equal(t, "USD", account.Currency)
	assert.Equal(t, "active", account.Status)
	
	repo.AssertExpectations(t)
}

func TestGetAccountNotFound(t *testing.T) {
	repo := new(MockRepository)
	svc := NewBankingService(repo)
	
	repo.On("GetAccount", mock.Anything, int64(999)).Return(nil, sql.ErrNoRows)
	
	_, err := svc.GetAccount(context.Background(), 999)
	
	assert.Error(t, err)
	assert.Equal(t, ErrAccountNotFound, err)
}

func TestGetAccountSuccess(t *testing.T) {
	repo := new(MockRepository)
	svc := NewBankingService(repo)
	
	account := &bankingmodels.Account{
		ID:            1,
		UserID:        "user123",
		AccountNumber: "TEST-123",
		Balance:       100.0,
	}
	
	repo.On("GetAccount", mock.Anything, int64(1)).Return(account, nil)
	
	result, err := svc.GetAccount(context.Background(), 1)
	
	assert.NoError(t, err)
	assert.Equal(t, account, result)
}

func TestDepositInvalidAmount(t *testing.T) {
	repo := new(MockRepository)
	svc := NewBankingService(repo)
	
	_, err := svc.Deposit(context.Background(), 1, -10, "test")
	
	assert.Error(t, err)
	assert.Equal(t, ErrInvalidAmount, err)
}

func TestDepositSuccess(t *testing.T) {
	repo := new(MockRepository)
	svc := NewBankingService(repo)
	
	account := &bankingmodels.Account{
		ID:     1,
		Status: "active",
	}
	
	repo.On("GetAccount", mock.Anything, int64(1)).Return(account, nil)
	repo.On("UpdateAccountBalance", mock.Anything, int64(1), 100.0, true).Return(100.0, nil)
	repo.On("CreateTransaction", mock.Anything, mock.Anything).Return(nil)
	
	txn, err := svc.Deposit(context.Background(), 1, 100.0, "test deposit")
	
	assert.NoError(t, err)
	assert.NotNil(t, txn)
	assert.Equal(t, "deposit", txn.Type)
	assert.Equal(t, 100.0, txn.Amount)
}

func TestWithdrawInvalidAmount(t *testing.T) {
	repo := new(MockRepository)
	svc := NewBankingService(repo)
	
	_, err := svc.Withdraw(context.Background(), 1, -10, "test")
	
	assert.Error(t, err)
	assert.Equal(t, ErrInvalidAmount, err)
}

func TestWithdrawInsufficientBalance(t *testing.T) {
	repo := new(MockRepository)
	svc := NewBankingService(repo)
	
	account := &bankingmodels.Account{
		ID:      1,
		Status:  "active",
		Balance: 50.0,
	}
	
	repo.On("GetAccount", mock.Anything, int64(1)).Return(account, nil)
	
	_, err := svc.Withdraw(context.Background(), 1, 100.0, "test")
	
	assert.Error(t, err)
	assert.Equal(t, ErrInsufficientBalance, err)
}

func TestWithdrawAccountNotActive(t *testing.T) {
	repo := new(MockRepository)
	svc := NewBankingService(repo)
	
	account := &bankingmodels.Account{
		ID:     1,
		Status: "frozen",
	}
	
	repo.On("GetAccount", mock.Anything, int64(1)).Return(account, nil)
	
	_, err := svc.Withdraw(context.Background(), 1, 50.0, "test")
	
	assert.Error(t, err)
	assert.Equal(t, ErrAccountNotActive, err)
}

func TestGetTransactions(t *testing.T) {
	repo := new(MockRepository)
	svc := NewBankingService(repo)
	
	// Test default limit
	repo.On("GetTransactions", mock.Anything, int64(1), 20, 0).Return([]*bankingmodels.Transaction{}, nil)
	
	_, err := svc.GetTransactions(context.Background(), 1, 0, 0)
	assert.NoError(t, err)
	
	// Test limit above max
	repo2 := new(MockRepository)
	svc2 := NewBankingService(repo2)
	repo2.On("GetTransactions", mock.Anything, int64(1), 100, 0).Return([]*bankingmodels.Transaction{}, nil)
	
	_, err = svc2.GetTransactions(context.Background(), 1, 200, 0)
	assert.NoError(t, err)
}