package walletservice

import (
    "context"
    "database/sql"
    "errors"
    "testing"

    "github.com/stretchr/testify/assert"
    "github.com/stretchr/testify/mock"
)

func TestWalletService_ProcessWithdrawCallback_Success(t *testing.T) {
    ctx := context.Background()
    txn := &WalletTransaction{
        ID:       1,
        WalletID: 1,
        Type:     "withdraw",
        Amount:   int64(-500000),
        Status:   "pending",
    }

    txnRepo := &MockTransactionRepository{}
    txnRepo.On("GetByReference", ctx, "REF123").Return(txn, nil)

    walletRepo := &MockWalletRepository{}
    walletRepo.On("GetWallet", ctx, int64(1)).Return(&Wallet{ID: 1}, nil)
    walletRepo.On("UpdateBalance", ctx, int64(1), int64(500000)).Return(nil)

    events := &MockEventHandler{}

    svc := NewWalletService(&sql.DB{}, events)
    svc.walletRepo = walletRepo
    svc.txnRepo = txnRepo
    svc.beginTx = func(ctx context.Context, opts *sql.TxOptions) (Tx, error) { return &mockTx{}, nil }

    err := svc.ProcessWithdrawCallback(ctx, "REF123", "success")
    assert.NoError(t, err)
}

func TestWalletService_ProcessWithdrawCallback_Failed(t *testing.T) {
    ctx := context.Background()
    txn := &WalletTransaction{
        ID:       2,
        WalletID: 2,
        Type:     "withdraw",
        Amount:   int64(-300000),
        Status:   "pending",
    }

    txnRepo := &MockTransactionRepository{}
    txnRepo.On("GetByReference", ctx, "REF456").Return(txn, nil)

    walletRepo := &MockWalletRepository{}
    walletRepo.On("GetWallet", ctx, int64(2)).Return(&Wallet{ID: 2, Balance: 1000000}, nil)
    walletRepo.On("UpdateBalance", ctx, int64(2), int64(300000)).Return(nil)

    svc := NewWalletService(&sql.DB{}, nil)
    svc.walletRepo = walletRepo
    svc.txnRepo = txnRepo
    svc.beginTx = func(ctx context.Context, opts *sql.TxOptions) (Tx, error) { return &mockTx{}, nil }

    err := svc.ProcessWithdrawCallback(ctx, "REF456", "failed")
    assert.NoError(t, err)
}

func TestWalletService_DeleteWallet_Success(t *testing.T) {
    ctx := context.Background()
    walletRepo := &MockWalletRepository{}
    walletRepo.On("GetWalletByUser", ctx, "user123").Return(&Wallet{ID: 1}, nil)
    walletRepo.On("DeleteWallet", ctx, int64(1)).Return(nil)

    svc := &WalletService{
        walletRepo: walletRepo,
    }
    err := svc.DeleteWallet(ctx, "user123")
    assert.NoError(t, err)
}

func TestWalletService_LockWallet_Success(t *testing.T) {
    ctx := context.Background()
    walletRepo := &MockWalletRepository{}
    walletRepo.On("GetWalletByUser", ctx, "user123").Return(&Wallet{ID: 1, Status: "active"}, nil)
    walletRepo.On("UpdateStatus", ctx, int64(1), "locked").Return(nil)

    svc := &WalletService{
        walletRepo: walletRepo,
    }
    err := svc.LockWallet(ctx, "user123", "test reason")
    assert.NoError(t, err)
}

func TestWalletService_UnlockWallet_Success(t *testing.T) {
    ctx := context.Background()
    walletRepo := &MockWalletRepository{}
    walletRepo.On("GetWalletByUser", ctx, "user123").Return(&Wallet{ID: 1, Status: "locked"}, nil)
    walletRepo.On("UpdateStatus", ctx, int64(1), "active").Return(nil)

    svc := &WalletService{
        walletRepo: walletRepo,
    }
    err := svc.UnlockWallet(ctx, "user123")
    assert.NoError(t, err)
}

func TestWalletService_ExportTransactions_Success(t *testing.T) {
    ctx := context.Background()
    walletRepo := &MockWalletRepository{}
    walletRepo.On("GetWalletByUser", ctx, "user123").Return(&Wallet{ID: 1}, nil)
    txnRepo := &MockTransactionRepository{}
    txnRepo.On("GetByWallet", ctx, int64(1), 100, 0).Return([]*WalletTransaction{}, nil)

    svc := &WalletService{
        walletRepo: walletRepo,
        txnRepo:    txnRepo,
    }
    result, err := svc.ExportTransactions(ctx, "user123", 100, 0)
    assert.NoError(t, err)
    assert.NotNil(t, result)
}

func TestWalletService_TopUp_GetWalletError(t *testing.T) {
    ctx := context.Background()
    walletRepo := &MockWalletRepository{}
    walletRepo.On("GetWalletByUser", ctx, "user123").Return((*Wallet)(nil), errors.New("db error"))
    walletRepo.On("CreateWallet", mock.Anything, mock.Anything).Return(errors.New("create failed"))

    svc := &WalletService{
        walletRepo: walletRepo,
    }
    svc.beginTx = func(ctx context.Context, opts *sql.TxOptions) (Tx, error) { return &mockTx{}, nil }

    req := &TopUpRequest{UserID: "user123", Amount: 100000, Method: "card"}
    _, err := svc.TopUp(ctx, req)
    assert.Error(t, err)
}

func TestWalletService_TopUp_UpdateBalanceError(t *testing.T) {
    ctx := context.Background()
    wallet := &Wallet{ID: 1, UserID: "user123", Balance: 0}
    walletRepo := &MockWalletRepository{}
    walletRepo.On("GetWalletByUser", ctx, "user123").Return(wallet, nil)
    walletRepo.On("UpdateBalance", ctx, int64(1), int64(100000)).Return(errors.New("update failed"))

    svc := &WalletService{
        walletRepo: walletRepo,
    }
    svc.beginTx = func(ctx context.Context, opts *sql.TxOptions) (Tx, error) { return &mockTx{}, nil }

    req := &TopUpRequest{UserID: "user123", Amount: 100000, Method: "card"}
    _, err := svc.TopUp(ctx, req)
    assert.Error(t, err)
}

func TestWalletService_Transfer_GetWalletError(t *testing.T) {
    ctx := context.Background()
    walletRepo := &MockWalletRepository{}
    walletRepo.On("HasSufficientBalance", ctx, "userA", int64(500000)).Return(true, nil)
    walletRepo.On("GetWalletByUser", ctx, "userA").Return(&Wallet{ID: 1, UserID: "userA", Balance: 1000000}, nil)
    walletRepo.On("GetWalletByUser", ctx, "userB").Return((*Wallet)(nil), nil)
    walletRepo.On("CreateWallet", mock.Anything, mock.Anything).Return(errors.New("create failed"))

    svc := &WalletService{
        walletRepo: walletRepo,
    }
    svc.beginTx = func(ctx context.Context, opts *sql.TxOptions) (Tx, error) { return &mockTx{}, nil }

    req := &TransferRequest{FromUserID: "userA", ToUserID: "userB", Amount: 500000}
    _, err := svc.Transfer(ctx, req)
    assert.Error(t, err)
}

func TestWalletService_Withdraw_InsufficientBalance(t *testing.T) {
    ctx := context.Background()
    walletRepo := &MockWalletRepository{}
    walletRepo.On("HasSufficientBalance", ctx, "user123", int64(500000)).Return(false, nil)

    svc := &WalletService{
        walletRepo: walletRepo,
    }
    svc.beginTx = func(ctx context.Context, opts *sql.TxOptions) (Tx, error) { return &mockTx{}, nil }

    req := &WithdrawalRequest{UserID: "user123", Amount: 500000, BankCode: "VCB", AccountNo: "1234567890", HolderName: "Nguyen Van A"}
    _, err := svc.Withdraw(ctx, req)
    assert.Error(t, err)
    assert.Contains(t, err.Error(), "insufficient balance")
}

func TestWalletService_GetBalance_Error(t *testing.T) {
    ctx := context.Background()
    walletRepo := &MockWalletRepository{}
    walletRepo.On("GetWalletByUser", ctx, "user123").Return((*Wallet)(nil), errors.New("not found"))

    svc := &WalletService{
        walletRepo: walletRepo,
    }
    balance, err := svc.GetBalance(ctx, "user123")
    assert.Error(t, err)
    assert.Zero(t, balance)
}
