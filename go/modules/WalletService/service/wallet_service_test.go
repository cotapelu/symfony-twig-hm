package walletservice

import (
	"context"
	"database/sql"
	"errors"
	"testing"

	walletmodels "community.local/modules/WalletService/internal/models"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/mock"
)

// MockWalletRepository implements WalletRepositoryInterface
type MockWalletRepository struct {
	mock.Mock
}

func (m *MockWalletRepository) CreateWallet(ctx context.Context, wallet *walletmodels.Wallet) error {
	args := m.Called(ctx, wallet)
	return args.Error(0)
}

func (m *MockWalletRepository) GetWalletByUser(ctx context.Context, userID string) (*walletmodels.Wallet, error) {
	args := m.Called(ctx, userID)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).(*walletmodels.Wallet), args.Error(1)
}

func (m *MockWalletRepository) GetWalletForUpdate(ctx context.Context, userID string) (*walletmodels.Wallet, error) {
	args := m.Called(ctx, userID)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).(*walletmodels.Wallet), args.Error(1)
}

func (m *MockWalletRepository) UpdateBalance(ctx context.Context, walletID int64, amount int64) error {
	args := m.Called(ctx, walletID, amount)
	return args.Error(0)
}

func (m *MockWalletRepository) UpdateStatus(ctx context.Context, walletID int64, status string) error {
	args := m.Called(ctx, walletID, status)
	return args.Error(0)
}

func (m *MockWalletRepository) DeleteWallet(ctx context.Context, walletID int64) error {
	args := m.Called(ctx, walletID)
	return args.Error(0)
}

// Additional methods to satisfy WalletRepositoryInterface
func (m *MockWalletRepository) GetWallet(ctx context.Context, id int64) (*walletmodels.Wallet, error) {
	args := m.Called(ctx, id)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).(*walletmodels.Wallet), args.Error(1)
}

func (m *MockWalletRepository) UpdateBalanceWithVersion(ctx context.Context, id int64, amount int64, expectedVersion int64) error {
	args := m.Called(ctx, id, amount, expectedVersion)
	return args.Error(0)
}

func (m *MockWalletRepository) HasSufficientBalance(ctx context.Context, userID string, amount int64) (bool, error) {
	args := m.Called(ctx, userID, amount)
	return args.Bool(0), args.Error(1)
}

func (m *MockWalletRepository) DB() *sql.DB {
	args := m.Called()
	if args.Get(0) == nil {
		return nil
	}
	return args.Get(0).(*sql.DB)
}

// MockTransactionRepository implements TransactionRepositoryInterface
type MockTransactionRepository struct {
	mock.Mock
}

func (m *MockTransactionRepository) CreateTransaction(ctx context.Context, txn *walletmodels.WalletTransaction) error {
	args := m.Called(ctx, txn)
	return args.Error(0)
}

func (m *MockTransactionRepository) GetByReference(ctx context.Context, ref string) (*walletmodels.WalletTransaction, error) {
	args := m.Called(ctx, ref)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).(*walletmodels.WalletTransaction), args.Error(1)
}

func (m *MockTransactionRepository) GetByWallet(ctx context.Context, walletID int64, limit, offset int) ([]*walletmodels.WalletTransaction, error) {
	args := m.Called(ctx, walletID, limit, offset)
	return args.Get(0).([]*walletmodels.WalletTransaction), args.Error(1)
}

// MockEventHandler implements EventHandler
type MockEventHandler struct {
	mock.Mock
}

func (m *MockEventHandler) EmitEvent(ctx context.Context, eventType string, data interface{}) error {
	args := m.Called(ctx, eventType, data)
	return args.Error(0)
}

func TestNewWalletService(t *testing.T) {
	walletRepo := new(MockWalletRepository)
	txnRepo := new(MockTransactionRepository)
	eventHandler := new(MockEventHandler)
	
	svc := NewWalletService(walletRepo, txnRepo, eventHandler)
	assert.NotNil(t, svc)
}

func TestCreateWallet(t *testing.T) {
	walletRepo := new(MockWalletRepository)
	txnRepo := new(MockTransactionRepository)
	
	svc := NewWalletService(walletRepo, txnRepo, nil)
	
	
	walletRepo.On("CreateWallet", mock.Anything, mock.Anything).Return(nil)
	
	result, err := svc.CreateWallet(context.Background(), "user123", "VND")
	
	assert.NoError(t, err)
	assert.NotNil(t, result)
	assert.Equal(t, "user123", result.UserID)
	assert.Equal(t, "VND", result.Currency)
}

func TestGetWallet(t *testing.T) {
	walletRepo := new(MockWalletRepository)
	txnRepo := new(MockTransactionRepository)
	
	svc := NewWalletService(walletRepo, txnRepo, nil)
	
	wallet := &walletmodels.Wallet{
		ID:      1,
		UserID:  "user123",
		Balance: 1000,
	}
	
	walletRepo.On("GetWalletByUser", mock.Anything, "user123").Return(wallet, nil)
	
	result, err := svc.GetWallet(context.Background(), "user123")
	
	assert.NoError(t, err)
	assert.Equal(t, wallet, result)
}

func TestTopUp_InvalidAmount(t *testing.T) {
	walletRepo := new(MockWalletRepository)
	txnRepo := new(MockTransactionRepository)
	
	svc := NewWalletService(walletRepo, txnRepo, nil)
	
	_, err := svc.TopUp(context.Background(), &walletmodels.TopUpRequest{
		UserID:  "user123",
		Amount:  -100,
	})
	
	assert.Error(t, err)
	assert.Equal(t, ErrInvalidAmount, err)
}

func TestTopUp_Success(t *testing.T) {
	walletRepo := new(MockWalletRepository)
	txnRepo := new(MockTransactionRepository)
	eventHandler := new(MockEventHandler)
	
	svc := NewWalletService(walletRepo, txnRepo, eventHandler)
	
	
	walletRepo.On("GetWalletForUpdate", mock.Anything, "user123").Return(nil, errors.New("not found"))
	walletRepo.On("CreateWallet", mock.Anything, mock.Anything).Return(nil)
	walletRepo.On("UpdateBalanceWithVersion", mock.Anything, mock.Anything, int64(100), int64(0)).Return(nil)
	txnRepo.On("CreateTransaction", mock.Anything, mock.Anything).Return(nil)
	eventHandler.On("EmitEvent", mock.Anything, mock.Anything, mock.Anything).Return(nil)
	
	result, err := svc.TopUp(context.Background(), &walletmodels.TopUpRequest{
		UserID:  "user123",
		Amount:  100,
		Method:  "bank",
	})
	
	assert.NoError(t, err)
	assert.NotNil(t, result)
}

func TestTransfer_InvalidAmount(t *testing.T) {
	walletRepo := new(MockWalletRepository)
	txnRepo := new(MockTransactionRepository)
	
	svc := NewWalletService(walletRepo, txnRepo, nil)
	
	_, err := svc.Transfer(context.Background(), &walletmodels.TransferRequest{
		FromUserID: "user1",
		ToUserID:   "user2",
		Amount:     -100,
	})
	
	assert.Error(t, err)
	assert.Equal(t, ErrInvalidAmount, err)
}

func TestTransfer_SelfTransfer(t *testing.T) {
	walletRepo := new(MockWalletRepository)
	txnRepo := new(MockTransactionRepository)
	
	svc := NewWalletService(walletRepo, txnRepo, nil)
	
	_, err := svc.Transfer(context.Background(), &walletmodels.TransferRequest{
		FromUserID: "user1",
		ToUserID:   "user1",
		Amount:     100,
	})
	
	assert.Error(t, err)
	assert.Equal(t, ErrSelfTransfer, err)
}

func TestTransfer_InsufficientBalance(t *testing.T) {
	walletRepo := new(MockWalletRepository)
	txnRepo := new(MockTransactionRepository)
	
	svc := NewWalletService(walletRepo, txnRepo, nil)
	
	fromWallet := &walletmodels.Wallet{
		ID:      1,
		UserID:  "user1",
		Balance: 50,
		Status:  "active",
	}
	
	walletRepo.On("GetWalletForUpdate", mock.Anything, "user1").Return(fromWallet, nil)
	
	_, err := svc.Transfer(context.Background(), &walletmodels.TransferRequest{
		FromUserID: "user1",
		ToUserID:   "user2",
		Amount:     100,
	})
	
	assert.Error(t, err)
	assert.Equal(t, ErrInsufficientBalance, err)
}

func TestWithdraw_InvalidAmount(t *testing.T) {
	walletRepo := new(MockWalletRepository)
	txnRepo := new(MockTransactionRepository)
	
	svc := NewWalletService(walletRepo, txnRepo, nil)
	
	_, err := svc.Withdraw(context.Background(), &walletmodels.WithdrawalRequest{
		UserID:     "user123",
		Amount:     -100,
		BankCode:   "VCB",
		AccountNo:  "123456",
		HolderName: "Test",
	})
	
	assert.Error(t, err)
	assert.Equal(t, ErrInvalidAmount, err)
}

func TestWithdraw_Success(t *testing.T) {
	walletRepo := new(MockWalletRepository)
	txnRepo := new(MockTransactionRepository)
	eventHandler := new(MockEventHandler)
	
	svc := NewWalletService(walletRepo, txnRepo, eventHandler)
	
	wallet := &walletmodels.Wallet{
		ID:      1,
		UserID:  "user123",
		Balance: 1000,
		Status:  "active",
	}
	
	walletRepo.On("GetWalletForUpdate", mock.Anything, "user123").Return(wallet, nil)
	walletRepo.On("UpdateBalanceWithVersion", mock.Anything, mock.Anything, int64(-100), int64(0)).Return(nil)
	txnRepo.On("CreateTransaction", mock.Anything, mock.Anything).Return(nil)
	eventHandler.On("EmitEvent", mock.Anything, mock.Anything, mock.Anything).Return(nil)
	
	result, err := svc.Withdraw(context.Background(), &walletmodels.WithdrawalRequest{
		UserID:     "user123",
		Amount:     100,
		BankCode:   "VCB",
		AccountNo:  "123456",
		HolderName: "Test User",
	})
	
	assert.NoError(t, err)
	assert.NotNil(t, result)
}

func TestGetBalance(t *testing.T) {
	walletRepo := new(MockWalletRepository)
	txnRepo := new(MockTransactionRepository)
	
	svc := NewWalletService(walletRepo, txnRepo, nil)
	
	wallet := &walletmodels.Wallet{
		ID:      1,
		UserID:  "user123",
		Balance: 1000,
	}
	
	walletRepo.On("GetWalletByUser", mock.Anything, "user123").Return(wallet, nil)
	
	balance, err := svc.GetBalance(context.Background(), "user123")
	
	assert.NoError(t, err)
	assert.Equal(t, int64(1000), balance)
}

func TestLockUnlockWallet(t *testing.T) {
	walletRepo := new(MockWalletRepository)
	txnRepo := new(MockTransactionRepository)
	
	svc := NewWalletService(walletRepo, txnRepo, nil)
	
	wallet := &walletmodels.Wallet{
		ID:     1,
		UserID: "user123",
	}
	
	walletRepo.On("GetWalletByUser", mock.Anything, "user123").Return(wallet, nil)
	walletRepo.On("UpdateStatus", mock.Anything, int64(1), "locked").Return(nil)
	
	err := svc.LockWallet(context.Background(), "user123", "test")
	assert.NoError(t, err)
	
	walletRepo.On("UpdateStatus", mock.Anything, int64(1), "active").Return(nil)
	
	err = svc.UnlockWallet(context.Background(), "user123")
	assert.NoError(t, err)
}