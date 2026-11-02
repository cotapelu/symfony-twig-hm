package walletservice

import (
	"context"
	"errors"
	"testing"

	walletmodels "community.local/modules/WalletService/internal/models"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/mock"
)

// Additional comprehensive tests for WalletService to increase coverage

func TestCreateWallet_Error(t *testing.T) {
	walletRepo := new(MockWalletRepository)
	txnRepo := new(MockTransactionRepository)
	svc := NewWalletService(walletRepo, txnRepo, nil)

	walletRepo.On("CreateWallet", mock.Anything, mock.Anything).Return(errors.New("db error"))

	result, err := svc.CreateWallet(context.Background(), "user123", "VND")

	assert.Error(t, err)
	assert.Nil(t, result)
}

func TestGetWallet_Error(t *testing.T) {
	walletRepo := new(MockWalletRepository)
	txnRepo := new(MockTransactionRepository)
	svc := NewWalletService(walletRepo, txnRepo, nil)

	walletRepo.On("GetWalletByUser", mock.Anything, "user123").Return(nil, errors.New("db error"))

	result, err := svc.GetWallet(context.Background(), "user123")

	assert.Error(t, err)
	assert.Nil(t, result)
}

func TestGetWallet_NotFound(t *testing.T) {
	walletRepo := new(MockWalletRepository)
	txnRepo := new(MockTransactionRepository)
	svc := NewWalletService(walletRepo, txnRepo, nil)

	walletRepo.On("GetWalletByUser", mock.Anything, "user123").Return(nil, errors.New("not found"))

	result, err := svc.GetWallet(context.Background(), "user123")

	assert.Error(t, err)
	assert.Nil(t, result)
}

func TestTopUp_Idempotency(t *testing.T) {
	walletRepo := new(MockWalletRepository)
	txnRepo := new(MockTransactionRepository)
	svc := NewWalletService(walletRepo, txnRepo, nil)

	existingTxn := &walletmodels.WalletTransaction{ID: 1}
	walletRepo.On("GetWalletForUpdate", mock.Anything, "user123").Return(&walletmodels.Wallet{ID: 1, Balance: 0, Status: "active"}, nil)
	txnRepo.On("GetByReference", mock.Anything, "key123").Return(existingTxn, nil)

	result, err := svc.TopUp(context.Background(), &walletmodels.TopUpRequest{
		UserID:         "user123",
		Amount:         100,
		Method:         "bank",
		IdempotencyKey: "key123",
	})

	assert.NoError(t, err)
	assert.Equal(t, existingTxn, result)
}

func TestTopUp_WalletLocked(t *testing.T) {
	walletRepo := new(MockWalletRepository)
	txnRepo := new(MockTransactionRepository)
	svc := NewWalletService(walletRepo, txnRepo, nil)

	walletRepo.On("GetWalletForUpdate", mock.Anything, "user123").Return(&walletmodels.Wallet{ID: 1, Balance: 100, Status: "locked"}, nil)

	_, err := svc.TopUp(context.Background(), &walletmodels.TopUpRequest{
		UserID:  "user123",
		Amount:  100,
		Method: "bank",
	})

	assert.Error(t, err)
	assert.Equal(t, ErrWalletLocked, err)
}

func TestTopUp_CreateWalletFails(t *testing.T) {
	walletRepo := new(MockWalletRepository)
	txnRepo := new(MockTransactionRepository)
	svc := NewWalletService(walletRepo, txnRepo, nil)

	walletRepo.On("GetWalletForUpdate", mock.Anything, "user123").Return(nil, errors.New("not found"))
	walletRepo.On("CreateWallet", mock.Anything, mock.Anything).Return(errors.New("create failed"))

	_, err := svc.TopUp(context.Background(), &walletmodels.TopUpRequest{
		UserID: "user123",
		Amount: 100,
		Method: "bank",
	})

	assert.Error(t, err)
}

func TestTopUp_UpdateBalanceFails(t *testing.T) {
	walletRepo := new(MockWalletRepository)
	txnRepo := new(MockTransactionRepository)
	svc := NewWalletService(walletRepo, txnRepo, nil)

	wallet := &walletmodels.Wallet{ID: 1, Balance: 0, Status: "active"}
	walletRepo.On("GetWalletForUpdate", mock.Anything, "user123").Return(wallet, nil)
	walletRepo.On("UpdateBalanceWithVersion", mock.Anything, mock.Anything, int64(100), int64(0)).Return(errors.New("update failed"))

	_, err := svc.TopUp(context.Background(), &walletmodels.TopUpRequest{
		UserID: "user123",
		Amount: 100,
		Method: "bank",
	})

	assert.Error(t, err)
}

func TestTopUp_CreateTransactionFails(t *testing.T) {
	walletRepo := new(MockWalletRepository)
	txnRepo := new(MockTransactionRepository)
	svc := NewWalletService(walletRepo, txnRepo, nil)

	wallet := &walletmodels.Wallet{ID: 1, Balance: 0, Status: "active"}
	walletRepo.On("GetWalletForUpdate", mock.Anything, "user123").Return(wallet, nil)
	walletRepo.On("UpdateBalanceWithVersion", mock.Anything, mock.Anything, int64(100), int64(0)).Return(nil)
	txnRepo.On("CreateTransaction", mock.Anything, mock.Anything).Return(errors.New("txn failed"))

	_, err := svc.TopUp(context.Background(), &walletmodels.TopUpRequest{
		UserID: "user123",
		Amount: 100,
		Method: "bank",
	})

	assert.Error(t, err)
}

func TestTransfer_Success(t *testing.T) {
	walletRepo := new(MockWalletRepository)
	txnRepo := new(MockTransactionRepository)
	eventHandler := new(MockEventHandler)
	svc := NewWalletService(walletRepo, txnRepo, eventHandler)

	fromWallet := &walletmodels.Wallet{ID: 1, Balance: 1000, Status: "active", Version: 1}
	toWallet := &walletmodels.Wallet{ID: 2, Balance: 500, Status: "active", Version: 1}

	walletRepo.On("GetWalletForUpdate", mock.Anything, "user1").Return(fromWallet, nil)
	walletRepo.On("GetWalletForUpdate", mock.Anything, "user2").Return(toWallet, nil)
	walletRepo.On("UpdateBalanceWithVersion", mock.Anything, int64(1), int64(-100), int64(1)).Return(nil)
	walletRepo.On("UpdateBalanceWithVersion", mock.Anything, int64(2), int64(100), int64(1)).Return(nil)
	txnRepo.On("CreateTransaction", mock.Anything, mock.Anything).Return(nil).Times(2)
	eventHandler.On("EmitEvent", mock.Anything, mock.Anything, mock.Anything).Return(nil)

	result, err := svc.Transfer(context.Background(), &walletmodels.TransferRequest{
		FromUserID: "user1",
		ToUserID:   "user2",
		Amount:     100,
		Description: "test transfer",
	})

	assert.NoError(t, err)
	assert.NotNil(t, result)
	assert.Equal(t, "transfer_out", result.Type)
	assert.Equal(t, int64(-100), result.Amount)
}

func TestTransfer_Idempotency(t *testing.T) {
	walletRepo := new(MockWalletRepository)
	txnRepo := new(MockTransactionRepository)
	svc := NewWalletService(walletRepo, txnRepo, nil)

	existingTxn := &walletmodels.WalletTransaction{ID: 1}
	txnRepo.On("GetByReference", mock.Anything, "trf123").Return(existingTxn, nil)

	result, err := svc.Transfer(context.Background(), &walletmodels.TransferRequest{
		FromUserID:     "user1",
		ToUserID:       "user2",
		Amount:         100,
		IdempotencyKey: "trf123",
	})

	assert.NoError(t, err)
	assert.Equal(t, existingTxn, result)
}

func TestTransfer_SenderWalletNotFound(t *testing.T) {
	walletRepo := new(MockWalletRepository)
	txnRepo := new(MockTransactionRepository)
	svc := NewWalletService(walletRepo, txnRepo, nil)

	walletRepo.On("GetWalletForUpdate", mock.Anything, "user1").Return(nil, nil)

	_, err := svc.Transfer(context.Background(), &walletmodels.TransferRequest{
		FromUserID: "user1",
		ToUserID:   "user2",
		Amount:     100,
	})

	assert.Error(t, err)
	assert.Contains(t, err.Error(), "sender wallet not found")
}

func TestTransfer_SenderWalletLocked(t *testing.T) {
	walletRepo := new(MockWalletRepository)
	txnRepo := new(MockTransactionRepository)
	svc := NewWalletService(walletRepo, txnRepo, nil)

	fromWallet := &walletmodels.Wallet{ID: 1, Balance: 1000, Status: "locked"}
	walletRepo.On("GetWalletForUpdate", mock.Anything, "user1").Return(fromWallet, nil)

	_, err := svc.Transfer(context.Background(), &walletmodels.TransferRequest{
		FromUserID: "user1",
		ToUserID:   "user2",
		Amount:     100,
	})

	assert.Error(t, err)
	assert.Equal(t, ErrWalletLocked, err)
}

func TestTransfer_ReceiverWalletLocked(t *testing.T) {
	walletRepo := new(MockWalletRepository)
	txnRepo := new(MockTransactionRepository)
	svc := NewWalletService(walletRepo, txnRepo, nil)

	fromWallet := &walletmodels.Wallet{ID: 1, Balance: 1000, Status: "active"}
	toWallet := &walletmodels.Wallet{ID: 2, Balance: 500, Status: "locked"}

	walletRepo.On("GetWalletForUpdate", mock.Anything, "user1").Return(fromWallet, nil)
	walletRepo.On("GetWalletForUpdate", mock.Anything, "user2").Return(toWallet, nil)

	_, err := svc.Transfer(context.Background(), &walletmodels.TransferRequest{
		FromUserID: "user1",
		ToUserID:   "user2",
		Amount:     100,
	})

	assert.Error(t, err)
	assert.Equal(t, ErrWalletLocked, err)
}

func TestTransfer_CreateReceiverWalletFails(t *testing.T) {
	walletRepo := new(MockWalletRepository)
	txnRepo := new(MockTransactionRepository)
	svc := NewWalletService(walletRepo, txnRepo, nil)

	fromWallet := &walletmodels.Wallet{ID: 1, Balance: 1000, Status: "active"}
	walletRepo.On("GetWalletForUpdate", mock.Anything, "user1").Return(fromWallet, nil)
	walletRepo.On("GetWalletForUpdate", mock.Anything, "user2").Return(nil, errors.New("not found"))
	walletRepo.On("CreateWallet", mock.Anything, mock.Anything).Return(errors.New("create failed"))

	_, err := svc.Transfer(context.Background(), &walletmodels.TransferRequest{
		FromUserID: "user1",
		ToUserID:   "user2",
		Amount:     100,
	})

	assert.Error(t, err)
}

func TestTransfer_DebitFails(t *testing.T) {
	walletRepo := new(MockWalletRepository)
	txnRepo := new(MockTransactionRepository)
	svc := NewWalletService(walletRepo, txnRepo, nil)

	fromWallet := &walletmodels.Wallet{ID: 1, Balance: 1000, Status: "active", Version: 1}
	toWallet := &walletmodels.Wallet{ID: 2, Balance: 500, Status: "active", Version: 1}

	walletRepo.On("GetWalletForUpdate", mock.Anything, "user1").Return(fromWallet, nil)
	walletRepo.On("GetWalletForUpdate", mock.Anything, "user2").Return(toWallet, nil)
	walletRepo.On("UpdateBalanceWithVersion", mock.Anything, int64(1), int64(-100), int64(1)).Return(errors.New("debit failed"))

	_, err := svc.Transfer(context.Background(), &walletmodels.TransferRequest{
		FromUserID: "user1",
		ToUserID:   "user2",
		Amount:     100,
	})

	assert.Error(t, err)
}

func TestTransfer_CreditFails(t *testing.T) {
	walletRepo := new(MockWalletRepository)
	txnRepo := new(MockTransactionRepository)
	svc := NewWalletService(walletRepo, txnRepo, nil)

	fromWallet := &walletmodels.Wallet{ID: 1, Balance: 1000, Status: "active", Version: 1}
	toWallet := &walletmodels.Wallet{ID: 2, Balance: 500, Status: "active", Version: 1}

	walletRepo.On("GetWalletForUpdate", mock.Anything, "user1").Return(fromWallet, nil)
	walletRepo.On("GetWalletForUpdate", mock.Anything, "user2").Return(toWallet, nil)
	walletRepo.On("UpdateBalanceWithVersion", mock.Anything, int64(1), int64(-100), int64(1)).Return(nil)
	walletRepo.On("UpdateBalanceWithVersion", mock.Anything, int64(2), int64(100), int64(1)).Return(errors.New("credit failed"))

	_, err := svc.Transfer(context.Background(), &walletmodels.TransferRequest{
		FromUserID: "user1",
		ToUserID:   "user2",
		Amount:     100,
	})

	assert.Error(t, err)
}

func TestTransfer_CreateTxnFails(t *testing.T) {
	walletRepo := new(MockWalletRepository)
	txnRepo := new(MockTransactionRepository)
	svc := NewWalletService(walletRepo, txnRepo, nil)

	fromWallet := &walletmodels.Wallet{ID: 1, Balance: 1000, Status: "active", Version: 1}
	toWallet := &walletmodels.Wallet{ID: 2, Balance: 500, Status: "active", Version: 1}

	walletRepo.On("GetWalletForUpdate", mock.Anything, "user1").Return(fromWallet, nil)
	walletRepo.On("GetWalletForUpdate", mock.Anything, "user2").Return(toWallet, nil)
	walletRepo.On("UpdateBalanceWithVersion", mock.Anything, int64(1), int64(-100), int64(1)).Return(nil)
	walletRepo.On("UpdateBalanceWithVersion", mock.Anything, int64(2), int64(100), int64(1)).Return(nil)
	txnRepo.On("CreateTransaction", mock.Anything, mock.Anything).Return(errors.New("txn failed"))

	_, err := svc.Transfer(context.Background(), &walletmodels.TransferRequest{
		FromUserID: "user1",
		ToUserID:   "user2",
		Amount:     100,
	})

	assert.Error(t, err)
}

func TestWithdraw_MissingBankCode(t *testing.T) {
	walletRepo := new(MockWalletRepository)
	txnRepo := new(MockTransactionRepository)
	svc := NewWalletService(walletRepo, txnRepo, nil)

	_, err := svc.Withdraw(context.Background(), &walletmodels.WithdrawalRequest{
		UserID:     "user123",
		Amount:     100,
		AccountNo:  "123456",
		HolderName: "Test",
	})

	assert.Error(t, err)
	assert.Contains(t, err.Error(), "bank code is required")
}

func TestWithdraw_MissingAccountNo(t *testing.T) {
	walletRepo := new(MockWalletRepository)
	txnRepo := new(MockTransactionRepository)
	svc := NewWalletService(walletRepo, txnRepo, nil)

	_, err := svc.Withdraw(context.Background(), &walletmodels.WithdrawalRequest{
		UserID:     "user123",
		Amount:     100,
		BankCode:   "VCB",
		HolderName: "Test",
	})

	assert.Error(t, err)
	assert.Contains(t, err.Error(), "account number is required")
}

func TestWithdraw_MissingHolderName(t *testing.T) {
	walletRepo := new(MockWalletRepository)
	txnRepo := new(MockTransactionRepository)
	svc := NewWalletService(walletRepo, txnRepo, nil)

	_, err := svc.Withdraw(context.Background(), &walletmodels.WithdrawalRequest{
		UserID:     "user123",
		Amount:     100,
		BankCode:   "VCB",
		AccountNo:  "123456",
	})

	assert.Error(t, err)
	assert.Contains(t, err.Error(), "holder name is required")
}

func TestWithdraw_WalletNotFound(t *testing.T) {
	walletRepo := new(MockWalletRepository)
	txnRepo := new(MockTransactionRepository)
	svc := NewWalletService(walletRepo, txnRepo, nil)

	walletRepo.On("GetWalletForUpdate", mock.Anything, "user123").Return(nil, errors.New("not found"))

	_, err := svc.Withdraw(context.Background(), &walletmodels.WithdrawalRequest{
		UserID:     "user123",
		Amount:     100,
		BankCode:   "VCB",
		AccountNo:  "123456",
		HolderName: "Test",
	})

	assert.Error(t, err)
}

func TestWithdraw_WalletLocked(t *testing.T) {
	walletRepo := new(MockWalletRepository)
	txnRepo := new(MockTransactionRepository)
	svc := NewWalletService(walletRepo, txnRepo, nil)

	wallet := &walletmodels.Wallet{ID: 1, Balance: 1000, Status: "locked"}
	walletRepo.On("GetWalletForUpdate", mock.Anything, "user123").Return(wallet, nil)

	_, err := svc.Withdraw(context.Background(), &walletmodels.WithdrawalRequest{
		UserID:     "user123",
		Amount:     100,
		BankCode:   "VCB",
		AccountNo:  "123456",
		HolderName: "Test",
	})

	assert.Error(t, err)
	assert.Equal(t, ErrWalletLocked, err)
}

func TestWithdraw_InsufficientBalance(t *testing.T) {
	walletRepo := new(MockWalletRepository)
	txnRepo := new(MockTransactionRepository)
	svc := NewWalletService(walletRepo, txnRepo, nil)

	wallet := &walletmodels.Wallet{ID: 1, Balance: 50, Status: "active"}
	walletRepo.On("GetWalletForUpdate", mock.Anything, "user123").Return(wallet, nil)

	_, err := svc.Withdraw(context.Background(), &walletmodels.WithdrawalRequest{
		UserID:     "user123",
		Amount:     100,
		BankCode:   "VCB",
		AccountNo:  "123456",
		HolderName: "Test",
	})

	assert.Error(t, err)
	assert.Equal(t, ErrInsufficientBalance, err)
}

func TestWithdraw_UpdateBalanceFails(t *testing.T) {
	walletRepo := new(MockWalletRepository)
	txnRepo := new(MockTransactionRepository)
	svc := NewWalletService(walletRepo, txnRepo, nil)

	wallet := &walletmodels.Wallet{ID: 1, Balance: 1000, Status: "active", Version: 1}
	walletRepo.On("GetWalletForUpdate", mock.Anything, "user123").Return(wallet, nil)
	walletRepo.On("UpdateBalanceWithVersion", mock.Anything, mock.Anything, int64(-100), int64(1)).Return(errors.New("update failed"))

	_, err := svc.Withdraw(context.Background(), &walletmodels.WithdrawalRequest{
		UserID:     "user123",
		Amount:     100,
		BankCode:   "VCB",
		AccountNo:  "123456",
		HolderName: "Test",
	})

	assert.Error(t, err)
}

func TestWithdraw_CreateTransactionFails(t *testing.T) {
	walletRepo := new(MockWalletRepository)
	txnRepo := new(MockTransactionRepository)
	svc := NewWalletService(walletRepo, txnRepo, nil)

	wallet := &walletmodels.Wallet{ID: 1, Balance: 1000, Status: "active", Version: 1}
	walletRepo.On("GetWalletForUpdate", mock.Anything, "user123").Return(wallet, nil)
	walletRepo.On("UpdateBalanceWithVersion", mock.Anything, mock.Anything, int64(-100), int64(1)).Return(nil)
	txnRepo.On("CreateTransaction", mock.Anything, mock.Anything).Return(errors.New("txn failed"))

	_, err := svc.Withdraw(context.Background(), &walletmodels.WithdrawalRequest{
		UserID:     "user123",
		Amount:     100,
		BankCode:   "VCB",
		AccountNo:  "123456",
		HolderName: "Test",
	})

	assert.Error(t, err)
}

func TestWithdraw_Idempotency(t *testing.T) {
	walletRepo := new(MockWalletRepository)
	txnRepo := new(MockTransactionRepository)
	svc := NewWalletService(walletRepo, txnRepo, nil)

	existingTxn := &walletmodels.WalletTransaction{ID: 1}
	wallet := &walletmodels.Wallet{ID: 1, Balance: 1000, Status: "active"}
	walletRepo.On("GetWalletForUpdate", mock.Anything, "user123").Return(wallet, nil)
	txnRepo.On("GetByReference", mock.Anything, "withdraw123").Return(existingTxn, nil)

	result, err := svc.Withdraw(context.Background(), &walletmodels.WithdrawalRequest{
		UserID:         "user123",
		Amount:         100,
		BankCode:       "VCB",
		AccountNo:      "123456",
		HolderName:     "Test",
		IdempotencyKey: "withdraw123",
	})

	assert.NoError(t, err)
	assert.Equal(t, existingTxn, result)
}

func TestGetTransactions_WalletNotFound(t *testing.T) {
	walletRepo := new(MockWalletRepository)
	txnRepo := new(MockTransactionRepository)
	svc := NewWalletService(walletRepo, txnRepo, nil)

	walletRepo.On("GetWalletByUser", mock.Anything, "user123").Return(nil, errors.New("not found"))

	_, err := svc.GetTransactions(context.Background(), "user123", 20, 0)

	assert.Error(t, err)
}

func TestGetTransactions_Success(t *testing.T) {
	walletRepo := new(MockWalletRepository)
	txnRepo := new(MockTransactionRepository)
	svc := NewWalletService(walletRepo, txnRepo, nil)

	wallet := &walletmodels.Wallet{ID: 1, UserID: "user123"}
	txns := []*walletmodels.WalletTransaction{{ID: 1}}

	walletRepo.On("GetWalletByUser", mock.Anything, "user123").Return(wallet, nil)
	txnRepo.On("GetByWallet", mock.Anything, int64(1), 20, 0).Return(txns, nil)

	result, err := svc.GetTransactions(context.Background(), "user123", 20, 0)

	assert.NoError(t, err)
	assert.Equal(t, txns, result)
}

func TestGetTransactions_EmptyList(t *testing.T) {
	walletRepo := new(MockWalletRepository)
	txnRepo := new(MockTransactionRepository)
	svc := NewWalletService(walletRepo, txnRepo, nil)

	wallet := &walletmodels.Wallet{ID: 1, UserID: "user123"}
	walletRepo.On("GetWalletByUser", mock.Anything, "user123").Return(wallet, nil)
	txnRepo.On("GetByWallet", mock.Anything, int64(1), 20, 0).Return([]*walletmodels.WalletTransaction{}, nil)

	result, err := svc.GetTransactions(context.Background(), "user123", 20, 0)

	assert.NoError(t, err)
	assert.Empty(t, result)
}

func TestDeleteWallet_NotFound(t *testing.T) {
	walletRepo := new(MockWalletRepository)
	txnRepo := new(MockTransactionRepository)
	svc := NewWalletService(walletRepo, txnRepo, nil)

	walletRepo.On("GetWalletByUser", mock.Anything, "user123").Return(nil, errors.New("not found"))

	err := svc.DeleteWallet(context.Background(), "user123")

	assert.Error(t, err)
}

func TestDeleteWallet_NonZeroBalance(t *testing.T) {
	walletRepo := new(MockWalletRepository)
	txnRepo := new(MockTransactionRepository)
	svc := NewWalletService(walletRepo, txnRepo, nil)

	wallet := &walletmodels.Wallet{ID: 1, Balance: 100}
	walletRepo.On("GetWalletByUser", mock.Anything, "user123").Return(wallet, nil)

	err := svc.DeleteWallet(context.Background(), "user123")

	assert.Error(t, err)
	assert.Contains(t, err.Error(), "cannot delete wallet with non-zero balance")
}

func TestDeleteWallet_Success(t *testing.T) {
	walletRepo := new(MockWalletRepository)
	txnRepo := new(MockTransactionRepository)
	svc := NewWalletService(walletRepo, txnRepo, nil)

	wallet := &walletmodels.Wallet{ID: 1, Balance: 0}
	walletRepo.On("GetWalletByUser", mock.Anything, "user123").Return(wallet, nil)
	walletRepo.On("DeleteWallet", mock.Anything, int64(1)).Return(nil)

	err := svc.DeleteWallet(context.Background(), "user123")

	assert.NoError(t, err)
}

func TestLockWallet_NotFound(t *testing.T) {
	walletRepo := new(MockWalletRepository)
	txnRepo := new(MockTransactionRepository)
	svc := NewWalletService(walletRepo, txnRepo, nil)

	walletRepo.On("GetWalletByUser", mock.Anything, "user123").Return(nil, errors.New("not found"))

	err := svc.LockWallet(context.Background(), "user123", "test")

	assert.Error(t, err)
}

func TestUnlockWallet_NotFound(t *testing.T) {
	walletRepo := new(MockWalletRepository)
	txnRepo := new(MockTransactionRepository)
	svc := NewWalletService(walletRepo, txnRepo, nil)

	walletRepo.On("GetWalletByUser", mock.Anything, "user123").Return(nil, errors.New("not found"))

	err := svc.UnlockWallet(context.Background(), "user123")

	assert.Error(t, err)
}

func TestGetBalance_Error(t *testing.T) {
	walletRepo := new(MockWalletRepository)
	txnRepo := new(MockTransactionRepository)
	svc := NewWalletService(walletRepo, txnRepo, nil)

	walletRepo.On("GetWalletByUser", mock.Anything, "user123").Return(nil, errors.New("db error"))

	balance, err := svc.GetBalance(context.Background(), "user123")

	assert.Error(t, err)
	assert.Equal(t, int64(0), balance)
}

func TestExportTransactions_Success(t *testing.T) {
	walletRepo := new(MockWalletRepository)
	txnRepo := new(MockTransactionRepository)
	svc := NewWalletService(walletRepo, txnRepo, nil)

	wallet := &walletmodels.Wallet{ID: 1, UserID: "user123"}
	txns := []*walletmodels.WalletTransaction{{ID: 1}}

	walletRepo.On("GetWalletByUser", mock.Anything, "user123").Return(wallet, nil)
	txnRepo.On("GetByWallet", mock.Anything, int64(1), 100, 0).Return(txns, nil)

	result, err := svc.ExportTransactions(context.Background(), "user123", 100, 0)

	assert.NoError(t, err)
	assert.Equal(t, txns, result)
}

func TestExportTransactions_WalletNotFound(t *testing.T) {
	walletRepo := new(MockWalletRepository)
	txnRepo := new(MockTransactionRepository)
	svc := NewWalletService(walletRepo, txnRepo, nil)

	walletRepo.On("GetWalletByUser", mock.Anything, "user123").Return(nil, errors.New("not found"))

	_, err := svc.ExportTransactions(context.Background(), "user123", 100, 0)

	assert.Error(t, err)
}
