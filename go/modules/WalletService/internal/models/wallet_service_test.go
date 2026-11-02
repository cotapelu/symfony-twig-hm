package walletservice

import (
	"context"
	"database/sql"
	"errors"
	"testing"
	"time"

	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/mock"
)

// MockWalletRepository mocks WalletRepository
type MockWalletRepository struct {
	mock.Mock
}

func (m *MockWalletRepository) CreateWallet(ctx context.Context, w *Wallet) error {
	args := m.Called(ctx, w)
	return args.Error(0)
}

func (m *MockWalletRepository) GetWallet(ctx context.Context, id int64) (*Wallet, error) {
	args := m.Called(ctx, id)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).(*Wallet), args.Error(1)
}

func (m *MockWalletRepository) GetWalletByUser(ctx context.Context, userID string) (*Wallet, error) {
	args := m.Called(ctx, userID)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).(*Wallet), args.Error(1)
}

func (m *MockWalletRepository) UpdateBalance(ctx context.Context, id int64, amount int64) error {
	args := m.Called(ctx, id, amount)
	return args.Error(0)
}

func (m *MockWalletRepository) HasSufficientBalance(ctx context.Context, userID string, amount int64) (bool, error) {
	args := m.Called(ctx, userID, amount)
	return args.Bool(0), args.Error(1)
}

// DB satisfies WalletRepositoryInterface (not used in tests)
func (m *MockWalletRepository) DB() *sql.DB {
	return nil
}

func (m *MockWalletRepository) GetWalletForUpdate(ctx context.Context, userID string) (*Wallet, error) {
	args := m.Called(ctx, userID)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).(*Wallet), args.Error(1)
}

func (m *MockWalletRepository) UpdateBalanceWithVersion(ctx context.Context, id int64, amount int64, expectedVersion int64) error {
	args := m.Called(ctx, id, amount, expectedVersion)
	return args.Error(0)
}

func (m *MockWalletRepository) DeleteWallet(ctx context.Context, id int64) error {
	args := m.Called(ctx, id)
	return args.Error(0)
}

func (m *MockWalletRepository) UpdateStatus(ctx context.Context, id int64, status string) error {
	args := m.Called(ctx, id, status)
	return args.Error(0)
}

// MockTransactionRepository mocks WalletTransactionRepository
type MockTransactionRepository struct {
	mock.Mock
}

func (m *MockTransactionRepository) CreateTransaction(ctx context.Context, t *WalletTransaction) error {
	args := m.Called(ctx, t)
	return args.Error(0)
}

func (m *MockTransactionRepository) GetByWallet(ctx context.Context, walletID int64, limit, offset int) ([]*WalletTransaction, error) {
	args := m.Called(ctx, walletID, limit, offset)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).([]*WalletTransaction), args.Error(1)
}

func (m *MockTransactionRepository) GetByReference(ctx context.Context, refID string) (*WalletTransaction, error) {
	args := m.Called(ctx, refID)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).(*WalletTransaction), args.Error(1)
}

// MockEventHandler mocks EventHandler
type MockEventHandler struct {
	mock.Mock
}

func (m *MockEventHandler) EmitEvent(ctx context.Context, eventType string, data interface{}) error {
	args := m.Called(ctx, eventType, data)
	return args.Error(0)
}

func TestWalletService_CreateWallet(t *testing.T) {
	ctx := context.Background()
	now := time.Now()

	svc := &WalletService{
		walletRepo: &MockWalletRepository{},
	}

	svc.walletRepo.(*MockWalletRepository).On("CreateWallet", ctx, mock.AnythingOfType("*walletservice.Wallet")).Run(func(args mock.Arguments) {
		w := args.Get(1).(*Wallet)
		w.ID = 1
		w.CreatedAt = now
	}).Return(nil)

	wallet, err := svc.CreateWallet(ctx, "user123", "VND")

	assert.NoError(t, err)
	assert.NotNil(t, wallet)
	assert.Equal(t, "user123", wallet.UserID)
	assert.Equal(t, "VND", wallet.Currency)
	assert.Equal(t, int64(0), wallet.Balance)
	assert.Equal(t, true, wallet.IsDefault)
	assert.Equal(t, "active", wallet.Status)
	assert.Equal(t, int64(1), wallet.ID)
	assert.WithinDuration(t, now, wallet.CreatedAt, 1*time.Second)
}

func TestWalletService_GetWallet(t *testing.T) {
	ctx := context.Background()
	svc := &WalletService{
		walletRepo: &MockWalletRepository{},
	}
	svc.walletRepo.(*MockWalletRepository).On("GetWalletByUser", ctx, "user123").Return(&Wallet{
		ID: 1, UserID: "user123", Balance: 1000000, Currency: "VND", Status: "active",
	}, nil)

	wallet, err := svc.GetWallet(ctx, "user123")

	assert.NoError(t, err)
	assert.Equal(t, int64(1), wallet.ID)
	assert.Equal(t, int64(1000000), wallet.Balance)
}

func TestWalletService_TopUp_SKIPPED(t *testing.T) {
	t.Skip("requires real DB transaction")
	ctx := context.Background()
	now := time.Now()

	// Wallet exists
	wallet := &Wallet{
		ID:     1,
		UserID: "user123",
		Balance: 500000,
	}

	svc := &WalletService{
		walletRepo: &MockWalletRepository{},
		txnRepo:    &MockTransactionRepository{},
	}

	walletRepo := svc.walletRepo.(*MockWalletRepository)
	txnRepo := svc.txnRepo.(*MockTransactionRepository)

	walletRepo.On("GetWalletByUser", ctx, "user123").Return(wallet, nil).Once()
	walletRepo.On("UpdateBalance", ctx, int64(1), int64(500000)).Return(nil).Once()

	txnRepo.On("CreateTransaction", ctx, mock.AnythingOfType("*walletservice.WalletTransaction")).Run(func(args mock.Arguments) {
		txn := args.Get(1).(*WalletTransaction)
		txn.ID = 1
		txn.CreatedAt = now
	}).Return(nil)

	walletRepo.On("GetWalletByUser", ctx, "user123").Return(&Wallet{ID: 1, Balance: 1000000}, nil).Once()

	req := &TopUpRequest{
		UserID:   "user123",
		Amount:   int64(500000),
		Method:   "card",
		BankCode: "VCB",
	}

	result, err := svc.TopUp(ctx, req)

	assert.NoError(t, err)
	assert.NotNil(t, result)
	assert.Equal(t, "topup", result.Type)
	assert.Equal(t, int64(500000), result.Amount)
	assert.Equal(t, int64(1000000), result.BalanceAfter)
}

func TestWalletService_TopUp_CreatesWalletIfNotExists_SKIPPED(t *testing.T) {
	t.Skip("requires real DB transaction")
	ctx := context.Background()

	svc := &WalletService{
		walletRepo: &MockWalletRepository{},
		txnRepo:    &MockTransactionRepository{},
	}

	walletRepo := svc.walletRepo.(*MockWalletRepository)
	txnRepo := svc.txnRepo.(*MockTransactionRepository)

	// GetWalletByUser returns nil first, then second call creates wallet
	walletRepo.On("GetWalletByUser", ctx, "newuser").Return((*Wallet)(nil), errors.New("not found")).Once()
	walletRepo.On("CreateWallet", ctx, "newuser", "VND").Return(nil).Once()
	// After creation, wallet is available
	createdWallet := &Wallet{ID: 1, UserID: "newuser", Balance: 0}
	walletRepo.On("GetWalletByUser", ctx, "newuser").Return(createdWallet, nil).Once()
	walletRepo.On("UpdateBalance", ctx, int64(1), int64(100000)).Return(nil).Once()

	txnRepo.On("CreateTransaction", ctx, mock.Anything).Return(nil)

	req := &TopUpRequest{
		UserID: "newuser",
		Amount: int64(100000),
		Method: "wallet",
	}

	result, err := svc.TopUp(ctx, req)

	assert.NoError(t, err)
	assert.NotNil(t, result)
	assert.Equal(t, "topup", result.Type)
}

func TestWalletService_Transfer_SKIPPED(t *testing.T) {
	t.Skip("requires real DB transaction")
	ctx := context.Background()

	fromWallet := &Wallet{ID: 1, UserID: "userA", Balance: 2000000}
	toWallet := &Wallet{ID: 2, UserID: "userB", Balance: 1000000}

	svc := &WalletService{
		walletRepo: &MockWalletRepository{},
		txnRepo:    &MockTransactionRepository{},
	}

	walletRepo := svc.walletRepo.(*MockWalletRepository)
	txnRepo := svc.txnRepo.(*MockTransactionRepository)

	// Check balance
	walletRepo.On("HasSufficientBalance", ctx, "userA", int64(500000)).Return(true, nil).Once()
	// Get wallets
	walletRepo.On("GetWalletByUser", ctx, "userA").Return(fromWallet, nil).Once()
	walletRepo.On("GetWalletByUser", ctx, "userB").Return(toWallet, nil).Once()
	// Update balances
	walletRepo.On("UpdateBalance", ctx, int64(1), int64(-500000)).Return(nil).Once()
	walletRepo.On("UpdateBalance", ctx, int64(2), int64(500000)).Return(nil).Once()
	// Transactions
	txnRepo.On("CreateTransaction", ctx, mock.AnythingOfType("*walletservice.WalletTransaction")).Return(nil).Twice()

	// After debit, from wallet balance decreased
	walletRepo.On("GetWalletByUser", ctx, "userA").Return(&Wallet{ID: 1, Balance: 1500000}, nil).Once()

	req := &TransferRequest{
		FromUserID: "userA",
		ToUserID:   "userB",
		Amount:     500000,
		Description: "Test transfer",
	}

	result, err := svc.Transfer(ctx, req)

	assert.NoError(t, err)
	assert.NotNil(t, result)
	assert.Equal(t, "transfer_out", result.Type)
	assert.Equal(t, int64(500000), -result.Amount)
	assert.Equal(t, int64(1500000), result.BalanceAfter)
}

func TestWalletService_Transfer_InsufficientBalance_SKIPPED(t *testing.T) {
	t.Skip("requires real DB transaction")
	ctx := context.Background()
	svc := &WalletService{
		walletRepo: &MockWalletRepository{},
	}
	walletRepo := svc.walletRepo.(*MockWalletRepository)
	walletRepo.On("HasSufficientBalance", ctx, "userA", int64(500000)).Return(false, nil)

	req := &TransferRequest{
		FromUserID: "userA",
		ToUserID:   "userB",
		Amount:     500000,
	}
	_, err := svc.Transfer(ctx, req)
	assert.Error(t, err)
	assert.Contains(t, err.Error(), "insufficient balance")
}

func TestWalletService_Withdraw_SKIPPED(t *testing.T) {
	t.Skip("requires real DB transaction")
	ctx := context.Background()
	wallet := &Wallet{ID: 1, UserID: "user123", Balance: 2000000}
	svc := &WalletService{
		walletRepo: &MockWalletRepository{},
		txnRepo:    &MockTransactionRepository{},
	}

	walletRepo := svc.walletRepo.(*MockWalletRepository)
	txnRepo := svc.txnRepo.(*MockTransactionRepository)

	walletRepo.On("HasSufficientBalance", ctx, "user123", int64(500000)).Return(true, nil).Once()
	walletRepo.On("GetWalletByUser", ctx, "user123").Return(wallet, nil).Once()
	walletRepo.On("UpdateBalance", ctx, int64(1), int64(-500000)).Return(nil).Once()

	txnRepo.On("CreateTransaction", ctx, mock.AnythingOfType("*walletservice.WalletTransaction")).Return(nil).Once()

	req := &WithdrawalRequest{
		UserID:    "user123",
		Amount:    int64(500000),
		BankCode:  "VCB",
		AccountNo: "1234567890",
		HolderName: "Nguyen Van A",
	}

	result, err := svc.Withdraw(ctx, req)

	assert.NoError(t, err)
	assert.NotNil(t, result)
	assert.Equal(t, "withdraw", result.Type)
	assert.Equal(t, int64(-500000), result.Amount)
	assert.Equal(t, "pending", result.Status)
}

func TestWalletService_GetBalance(t *testing.T) {
	ctx := context.Background()
	svc := &WalletService{
		walletRepo: &MockWalletRepository{},
	}
	svc.walletRepo.(*MockWalletRepository).On("GetWalletByUser", ctx, "user123").Return(&Wallet{
		ID: 1, Balance: 2500000,
	}, nil)

	balance, err := svc.GetBalance(ctx, "user123")
	assert.NoError(t, err)
	assert.Equal(t, int64(2500000), balance)
}

func TestWalletService_GetTransactions(t *testing.T) {
	ctx := context.Background()
	wallet := &Wallet{ID: 1, UserID: "user123"}
	txns := []*WalletTransaction{
		{ID: 1, WalletID: 1, Type: "topup", Amount: int64(500000), CreatedAt: time.Now()},
	}

	svc := &WalletService{
		walletRepo: &MockWalletRepository{},
		txnRepo:    &MockTransactionRepository{},
	}

	walletRepo := svc.walletRepo.(*MockWalletRepository)
	txnRepo := svc.txnRepo.(*MockTransactionRepository)

	walletRepo.On("GetWalletByUser", ctx, "user123").Return(wallet, nil).Once()
	txnRepo.On("GetByWallet", ctx, int64(1), 10, 0).Return(txns, nil).Once()

	result, err := svc.GetTransactions(ctx, "user123", 10, 0)

	assert.NoError(t, err)
	assert.Len(t, result, 1)
}

// mockTx for testing transaction injection
type mockTx struct{}

func (mt *mockTx) Commit() error  { return nil }
func (mt *mockTx) Rollback() error { return nil }

func TestWalletService_CreateWallet_Error(t *testing.T) {
	svc := &WalletService{
		walletRepo: &MockWalletRepository{},
	}
	svc.walletRepo.(*MockWalletRepository).On("CreateWallet", mock.Anything, mock.Anything).Return(errors.New("db error")).Once()

	_, err := svc.CreateWallet(context.Background(), "user123", "VND")
	assert.Error(t, err)
	assert.Contains(t, err.Error(), "db error")
}

func TestWalletService_TopUp_Success(t *testing.T) {
	ctx := context.Background()
	now := time.Now()
	wallet := &Wallet{ID: 1, UserID: "user123", Balance: 500000}

	billRepo := &MockWalletRepository{}
	billRepo.On("GetWalletByUser", ctx, "user123").Return(wallet, nil)
	billRepo.On("UpdateBalance", ctx, int64(1), int64(300000)).Return(nil)

	txnRepo := &MockTransactionRepository{}
	txnRepo.On("CreateTransaction", ctx, mock.AnythingOfType("*walletservice.WalletTransaction")).Run(func(args mock.Arguments) {
		txn := args.Get(1).(*WalletTransaction)
		txn.ID = 1
		txn.CreatedAt = now
	}).Return(nil)

	svc := &WalletService{
		walletRepo: billRepo,
		txnRepo:    txnRepo,
	}
	svc.beginTx = func(ctx context.Context, opts *sql.TxOptions) (Tx, error) { return &mockTx{}, nil }

	req := &TopUpRequest{UserID: "user123", Amount: 300000, Method: "card", BankCode: "VCB"}
	result, err := svc.TopUp(ctx, req)

	assert.NoError(t, err)
	assert.NotNil(t, result)
	assert.Equal(t, "topup", result.Type)
	assert.Equal(t, int64(300000), result.Amount)
	assert.Equal(t, int64(800000), result.BalanceAfter)
}

func TestWalletService_Transfer_Success(t *testing.T) {
	ctx := context.Background()
	fromWallet := &Wallet{ID: 1, UserID: "userA", Balance: 2000000}
	toWallet := &Wallet{ID: 2, UserID: "userB", Balance: 1000000}

	walletRepo := &MockWalletRepository{}
	walletRepo.On("HasSufficientBalance", ctx, "userA", int64(500000)).Return(true, nil)
	walletRepo.On("GetWalletByUser", ctx, "userA").Return(fromWallet, nil)
	walletRepo.On("GetWalletByUser", ctx, "userB").Return(toWallet, nil)
	walletRepo.On("UpdateBalance", ctx, int64(1), int64(-500000)).Return(nil)
	walletRepo.On("UpdateBalance", ctx, int64(2), int64(500000)).Return(nil)

	txnRepo := &MockTransactionRepository{}
	txnRepo.On("CreateTransaction", ctx, mock.AnythingOfType("*walletservice.WalletTransaction")).Return(nil).Times(2)

	svc := &WalletService{
		walletRepo: walletRepo,
		txnRepo:    txnRepo,
	}
	svc.beginTx = func(ctx context.Context, opts *sql.TxOptions) (Tx, error) { return &mockTx{}, nil }

	req := &TransferRequest{FromUserID: "userA", ToUserID: "userB", Amount: 500000}
	result, err := svc.Transfer(ctx, req)

	assert.NoError(t, err)
	assert.NotNil(t, result)
	assert.Equal(t, "transfer_out", result.Type)
	assert.Equal(t, int64(-500000), result.Amount)
	assert.Equal(t, int64(1500000), result.BalanceAfter)
}

func TestWalletService_Withdraw_Success(t *testing.T) {
	ctx := context.Background()
	wallet := &Wallet{ID: 1, UserID: "user123", Balance: 2000000}

	walletRepo := &MockWalletRepository{}
	walletRepo.On("GetWalletByUser", ctx, "user123").Return(wallet, nil)
	walletRepo.On("HasSufficientBalance", ctx, "user123", int64(500000)).Return(true, nil)
	walletRepo.On("UpdateBalance", ctx, int64(1), int64(-500000)).Return(nil)

	txnRepo := &MockTransactionRepository{}
	txnRepo.On("CreateTransaction", ctx, mock.AnythingOfType("*walletservice.WalletTransaction")).Run(func(args mock.Arguments) {
		txn := args.Get(1).(*WalletTransaction)
		txn.ID = 1
	}).Return(nil)

	svc := &WalletService{
		walletRepo: walletRepo,
		txnRepo:    txnRepo,
	}
	svc.beginTx = func(ctx context.Context, opts *sql.TxOptions) (Tx, error) { return &mockTx{}, nil }

	req := &WithdrawalRequest{UserID: "user123", Amount: 500000, BankCode: "VCB", AccountNo: "1234567890", HolderName: "Nguyen Van A"}
	result, err := svc.Withdraw(ctx, req)

	assert.NoError(t, err)
	assert.NotNil(t, result)
	assert.Equal(t, "withdraw", result.Type)
	assert.Equal(t, int64(-500000), result.Amount)
	assert.Equal(t, "pending", result.Status)
}
