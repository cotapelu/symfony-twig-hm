package walletservice

import (
	"context"
	"errors"
	"fmt"
	"time"

	walletmodels "community.local/modules/WalletService/internal/models"
)

var (
	ErrInvalidAmount       = errors.New("amount must be positive and not exceed limit")
	ErrSelfTransfer        = errors.New("cannot transfer to yourself")
	ErrInsufficientBalance = errors.New("insufficient balance")
	ErrWalletLocked        = errors.New("wallet is locked")
	ErrInvalidUserID       = errors.New("invalid user ID")
)

type EventHandler interface {
	EmitEvent(ctx context.Context, eventType string, data interface{}) error
}

// WalletService handles wallet business logic
type WalletService struct {
	walletRepo   walletmodels.WalletRepositoryInterface
	txnRepo      walletmodels.TransactionRepositoryInterface
	eventHandler EventHandler
}

// NewWalletService creates a new wallet service with repositories
func NewWalletService(walletRepo walletmodels.WalletRepositoryInterface, txnRepo walletmodels.TransactionRepositoryInterface, eventHandler EventHandler) *WalletService {
	return &WalletService{
		walletRepo:   walletRepo,
		txnRepo:      txnRepo,
		eventHandler: eventHandler,
	}
}

// CreateWallet creates a new wallet for a user
func (s *WalletService) CreateWallet(ctx context.Context, userID, currency string) (*walletmodels.Wallet, error) {
	wallet := &walletmodels.Wallet{
		UserID:    userID,
		Balance:   0,
		Currency:  currency,
		Status:    "active",
		IsDefault: true,
	}
	if err := s.walletRepo.CreateWallet(ctx, wallet); err != nil {
		return nil, err
	}
	return wallet, nil
}

// GetWallet retrieves a user's wallet
func (s *WalletService) GetWallet(ctx context.Context, userID string) (*walletmodels.Wallet, error) {
	return s.walletRepo.GetWalletByUser(ctx, userID)
}

// TopUp adds funds to a wallet
func (s *WalletService) TopUp(ctx context.Context, req *walletmodels.TopUpRequest) (*walletmodels.WalletTransaction, error) {
	// Input validation
	if req.Amount <= 0 {
		return nil, ErrInvalidAmount
	}
	if req.Amount > 1000000000 { // 1 billion max
		return nil, ErrInvalidAmount
	}

	// Idempotency check - return existing transaction if already processed
	if req.IdempotencyKey != "" {
		existing, err := s.txnRepo.GetByReference(ctx, req.IdempotencyKey)
		if err == nil && existing != nil {
			return existing, nil
		}
	}

	// Lock the wallet row to prevent concurrent modifications
	wallet, err := s.walletRepo.GetWalletForUpdate(ctx, req.UserID)
	if err != nil || wallet == nil {
		// Wallet doesn't exist, create it (no lock needed for new wallet)
		wallet, err = s.CreateWallet(ctx, req.UserID, "VND")
		if err != nil {
			return nil, fmt.Errorf("failed to create wallet: %w", err)
		}
		// For newly created wallet, no need to lock; proceed to top up
		// However, we need to also set the version appropriately
	} else {
		// Check if wallet is locked
		if wallet.Status == "locked" {
			return nil, ErrWalletLocked
		}
	}

	// Apply the top up (after ensuring wallet exists and is unlocked)
	if err := s.walletRepo.UpdateBalanceWithVersion(ctx, wallet.ID, req.Amount, wallet.Version); err != nil {
		return nil, fmt.Errorf("failed to update balance: %w", err)
	}

	referenceID := req.IdempotencyKey
	if referenceID == "" {
		referenceID = fmt.Sprintf("TOPUP-%d", time.Now().UnixNano())
	}

	txn := &walletmodels.WalletTransaction{
		WalletID:     wallet.ID,
		Type:         "topup",
		Amount:       req.Amount,
		BalanceAfter: wallet.Balance + req.Amount,
		Description:  fmt.Sprintf("Top up via %s", req.Method),
		ReferenceID:  referenceID,
		Metadata:     fmt.Sprintf(`{"method": "%s", "bank_code": "%s"}`, req.Method, req.BankCode),
		Status:       "completed",
	}
	if err := s.txnRepo.CreateTransaction(ctx, txn); err != nil {
		return nil, fmt.Errorf("failed to create transaction: %w", err)
	}

	if s.eventHandler != nil {
		event := walletmodels.TransactionEvent{
			EventType:     "wallet.topped_up",
			TransactionID: txn.ID,
			WalletID:      wallet.ID,
			UserID:        req.UserID,
			Type:          "topup",
			Amount:        req.Amount,
			Status:        "completed",
			Timestamp:     time.Now(),
		}
		_ = s.eventHandler.EmitEvent(ctx, "wallet.transaction", event)
	}

	return txn, nil
}

// Transfer transfers funds between users
func (s *WalletService) Transfer(ctx context.Context, req *walletmodels.TransferRequest) (*walletmodels.WalletTransaction, error) {
	// Input validation
	if req.Amount <= 0 {
		return nil, ErrInvalidAmount
	}
	if req.FromUserID == req.ToUserID {
		return nil, ErrSelfTransfer
	}

	// Idempotency check - return existing transaction if already processed
	if req.IdempotencyKey != "" {
		existing, err := s.txnRepo.GetByReference(ctx, req.IdempotencyKey)
		if err == nil && existing != nil {
			return existing, nil
		}
	}

	// Get sender wallet with row lock to prevent race conditions
	fromWallet, err := s.walletRepo.GetWalletForUpdate(ctx, req.FromUserID)
	if err != nil {
		return nil, fmt.Errorf("failed to get sender wallet: %w", err)
	}
	if fromWallet == nil {
		return nil, fmt.Errorf("sender wallet not found")
	}

	// Check if sender wallet is locked
	if fromWallet.Status == "locked" {
		return nil, ErrWalletLocked
	}

	// Check balance after acquiring lock
	if fromWallet.Balance < req.Amount {
		return nil, ErrInsufficientBalance
	}

	// Get or create receiver wallet
	toWallet, err := s.walletRepo.GetWalletForUpdate(ctx, req.ToUserID)
	if err != nil {
		return nil, fmt.Errorf("failed to get receiver wallet: %w", err)
	}
	if toWallet == nil {
		toWallet, err = s.CreateWallet(ctx, req.ToUserID, "VND")
		if err != nil {
			return nil, fmt.Errorf("failed to create destination wallet: %w", err)
		}
	}

	// Check if receiver wallet is locked
	if toWallet.Status == "locked" {
		return nil, ErrWalletLocked
	}

	// Debit sender wallet
	if err := s.walletRepo.UpdateBalanceWithVersion(ctx, fromWallet.ID, -req.Amount, fromWallet.Version); err != nil {
		return nil, fmt.Errorf("failed to debit source wallet: %w", err)
	}

	// Credit receiver wallet
	if err := s.walletRepo.UpdateBalanceWithVersion(ctx, toWallet.ID, req.Amount, toWallet.Version); err != nil {
		return nil, fmt.Errorf("failed to credit destination wallet: %w", err)
	}

	txn := &walletmodels.WalletTransaction{
		WalletID:     fromWallet.ID,
		Type:         "transfer_out",
		Amount:       -req.Amount,
		BalanceAfter: fromWallet.Balance - req.Amount,
		Description:  fmt.Sprintf("Transfer to %s: %s", req.ToUserID, req.Description),
		ReferenceID:  fmt.Sprintf("TRF-%d", time.Now().UnixNano()),
		Metadata:     fmt.Sprintf(`{"to_user": "%s"}`, req.ToUserID),
		Status:       "completed",
	}
	if err := s.txnRepo.CreateTransaction(ctx, txn); err != nil {
		return nil, fmt.Errorf("failed to create source transaction: %w", err)
	}

	inTxn := &walletmodels.WalletTransaction{
		WalletID:     toWallet.ID,
		Type:         "transfer_in",
		Amount:       req.Amount,
		BalanceAfter: toWallet.Balance + req.Amount,
		Description:  fmt.Sprintf("Transfer from %s", req.FromUserID),
		ReferenceID:  fmt.Sprintf("TRF-IN-%d", time.Now().UnixNano()),
		Metadata:     fmt.Sprintf(`{"from_user": "%s"}`, req.FromUserID),
		Status:       "completed",
	}
	if err := s.txnRepo.CreateTransaction(ctx, inTxn); err != nil {
		return nil, fmt.Errorf("failed to create destination transaction: %w", err)
	}

	if s.eventHandler != nil {
		event := walletmodels.TransactionEvent{
			EventType:     "wallet.transferred",
			TransactionID: txn.ID,
			WalletID:      fromWallet.ID,
			UserID:        req.FromUserID,
			Type:          "transfer_out",
			Amount:        req.Amount,
			Status:        "completed",
			Timestamp:     time.Now(),
		}
		_ = s.eventHandler.EmitEvent(ctx, "wallet.transaction", event)
	}

	return txn, nil
}

// Withdraw withdraws funds from a wallet
func (s *WalletService) Withdraw(ctx context.Context, req *walletmodels.WithdrawalRequest) (*walletmodels.WalletTransaction, error) {
	// Input validation
	if req.Amount <= 0 {
		return nil, ErrInvalidAmount
	}
	if req.BankCode == "" {
		return nil, fmt.Errorf("bank code is required")
	}
	if req.AccountNo == "" {
		return nil, fmt.Errorf("account number is required")
	}
	if req.HolderName == "" {
		return nil, fmt.Errorf("holder name is required")
	}

	// Idempotency check - return existing transaction if already processed
	if req.IdempotencyKey != "" {
		existing, err := s.txnRepo.GetByReference(ctx, req.IdempotencyKey)
		if err == nil && existing != nil {
			return existing, nil
		}
	}

	// Lock wallet row for update to prevent race conditions
	wallet, err := s.walletRepo.GetWalletForUpdate(ctx, req.UserID)
	if err != nil || wallet == nil {
		return nil, fmt.Errorf("wallet not found")
	}
	if wallet.Status == "locked" {
		return nil, ErrWalletLocked
	}
	if wallet.Balance < req.Amount {
		return nil, ErrInsufficientBalance
	}

	if err := s.walletRepo.UpdateBalanceWithVersion(ctx, wallet.ID, -req.Amount, wallet.Version); err != nil {
		return nil, fmt.Errorf("failed to update balance: %w", err)
	}

	txn := &walletmodels.WalletTransaction{
		WalletID:     wallet.ID,
		Type:         "withdraw",
		Amount:       -req.Amount,
		BalanceAfter: wallet.Balance - req.Amount,
		Description:  fmt.Sprintf("Withdraw to %s - %s", req.BankCode, req.AccountNo),
		ReferenceID:  fmt.Sprintf("WTH-%d", time.Now().UnixNano()),
		Metadata:     fmt.Sprintf(`{"bank_code": "%s", "account_no": "%s", "holder_name": "%s"}`, req.BankCode, req.AccountNo, req.HolderName),
		Status:       "pending",
	}
	if err := s.txnRepo.CreateTransaction(ctx, txn); err != nil {
		return nil, fmt.Errorf("failed to create transaction: %w", err)
	}

	if s.eventHandler != nil {
		event := walletmodels.TransactionEvent{
			EventType:     "wallet.withdrawn",
			TransactionID: txn.ID,
			WalletID:      wallet.ID,
			UserID:        req.UserID,
			Type:          "withdraw",
			Amount:        req.Amount,
			Status:        "pending",
			Timestamp:     time.Now(),
		}
		_ = s.eventHandler.EmitEvent(ctx, "wallet.transaction", event)
	}

	return txn, nil
}

// GetBalance retrieves a user's wallet balance
func (s *WalletService) GetBalance(ctx context.Context, userID string) (int64, error) {
	wallet, err := s.walletRepo.GetWalletByUser(ctx, userID)
	if err != nil {
		return 0, err
	}
	return wallet.Balance, nil
}

// GetTransactions retrieves a user's transaction history
func (s *WalletService) GetTransactions(ctx context.Context, userID string, limit, offset int) ([]*walletmodels.WalletTransaction, error) {
	wallet, err := s.walletRepo.GetWalletByUser(ctx, userID)
	if err != nil {
		return nil, err
	}
	return s.txnRepo.GetByWallet(ctx, wallet.ID, limit, offset)
}

func (s *WalletService) DeleteWallet(ctx context.Context, userID string) error {
	wallet, err := s.walletRepo.GetWalletByUser(ctx, userID)
	if err != nil {
		return fmt.Errorf("wallet not found: %w", err)
	}

	if wallet.Balance > 0 {
		return fmt.Errorf("cannot delete wallet with non-zero balance")
	}

	return s.walletRepo.DeleteWallet(ctx, wallet.ID)
}

func (s *WalletService) LockWallet(ctx context.Context, userID, reason string) error {
	wallet, err := s.walletRepo.GetWalletByUser(ctx, userID)
	if err != nil {
		return fmt.Errorf("wallet not found: %w", err)
	}

	return s.walletRepo.UpdateStatus(ctx, wallet.ID, "locked")
}

func (s *WalletService) UnlockWallet(ctx context.Context, userID string) error {
	wallet, err := s.walletRepo.GetWalletByUser(ctx, userID)
	if err != nil {
		return fmt.Errorf("wallet not found: %w", err)
	}

	return s.walletRepo.UpdateStatus(ctx, wallet.ID, "active")
}

func (s *WalletService) ExportTransactions(ctx context.Context, userID string, limit, offset int) ([]*walletmodels.WalletTransaction, error) {
	wallet, err := s.walletRepo.GetWalletByUser(ctx, userID)
	if err != nil {
		return nil, err
	}

	return s.txnRepo.GetByWallet(ctx, wallet.ID, limit, offset)
}
