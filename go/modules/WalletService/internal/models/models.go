package walletservice

import (
	"context"
	"database/sql"
	"fmt"
	"time"

	_ "github.com/lib/pq"
)

// Tx interface abstracts transaction commit/rollback for testability.
type Tx interface {
	Commit() error
	Rollback() error
}

// sqlTx adapts *sql.Tx to Tx.
type sqlTx struct{ *sql.Tx }
func (st *sqlTx) Commit() error { return st.Tx.Commit() }
func (st *sqlTx) Rollback() error { return st.Tx.Rollback() }

type Wallet struct {
	ID        int64     `json:"id"`
	UserID    string    `json:"user_id"`
	Balance   int64     `json:"balance"` // balance in cents
	Currency  string    `json:"currency"`
	Status    string    `json:"status"`
	IsDefault bool      `json:"is_default"`
	Version   int64     `json:"version"` // optimistic locking version
	CreatedAt time.Time `json:"created_at"`
	UpdatedAt time.Time `json:"updated_at"`
}

type WalletTransaction struct {
	ID           int64     `json:"id"`
	WalletID     int64     `json:"wallet_id"`
	Type         string    `json:"type"`
	Amount       int64     `json:"amount"`        // amount in cents
	BalanceAfter int64     `json:"balance_after"` // balance after transaction in cents
	Description  string    `json:"description"`
	ReferenceID  string    `json:"reference_id"`
	Metadata     string    `json:"metadata"`
	Status       string    `json:"status"`
	CreatedAt    time.Time `json:"created_at"`
}

// WalletBalance represents a balance snapshot (may be used for reporting)
type WalletBalance struct {
	ID          int64     `json:"id"`
	WalletID    int64     `json:"wallet_id"`
	Currency    string    `json:"currency"`
	Balance     int64     `json:"balance"` // balance in cents
	LastUpdated time.Time `json:"last_updated"`
}

type TopUpRequest struct {
	UserID         string `json:"user_id"`
	Amount         int64  `json:"amount"` // amount in cents
	Method         string `json:"method"`
	BankCode       string `json:"bank_code"`
	CardToken      string `json:"card_token"`
	IdempotencyKey string `json:"idempotency_key"`
}

type TransferRequest struct {
	FromUserID     string `json:"from_user_id"`
	ToUserID       string `json:"to_user_id"`
	Amount         int64  `json:"amount"` // amount in cents
	Description    string `json:"description"`
	IdempotencyKey string `json:"idempotency_key"`
}

type WithdrawalRequest struct {
	UserID         string `json:"user_id"`
	Amount         int64  `json:"amount"` // amount in cents
	BankCode       string `json:"bank_code"`
	AccountNo      string `json:"account_no"`
	HolderName     string `json:"holder_name"`
	IdempotencyKey string `json:"idempotency_key"`
}

// TransactionEvent represents an event emitted when a wallet transaction occurs
type TransactionEvent struct {
	EventType     string    `json:"event_type"`
	TransactionID int64     `json:"transaction_id"`
	WalletID      int64     `json:"wallet_id"`
	UserID        string    `json:"user_id"`
	Type          string    `json:"type"`
	Amount        int64     `json:"amount"` // amount in cents
	Status        string    `json:"status"`
	Timestamp     time.Time `json:"timestamp"`
}

type WalletRepository struct {
	db *sql.DB
}

func NewWalletRepository(db *sql.DB) *WalletRepository {
	return &WalletRepository{db: db}
}

func (r *WalletRepository) CreateWallet(ctx context.Context, w *Wallet) error {
	query := `INSERT INTO wallets (user_id, balance, currency, status, is_default, version) VALUES ($1, $2, $3, $4, $5, $6) RETURNING id, created_at, version`
	return r.db.QueryRowContext(ctx, query, w.UserID, w.Balance, w.Currency, w.Status, w.IsDefault, w.Version).Scan(&w.ID, &w.CreatedAt, &w.Version)
}

func (r *WalletRepository) GetWallet(ctx context.Context, id int64) (*Wallet, error) {
	w := &Wallet{}
	query := `SELECT id, user_id, balance, currency, status, is_default, version, created_at, updated_at FROM wallets WHERE id = $1`
	err := r.db.QueryRowContext(ctx, query, id).Scan(&w.ID, &w.UserID, &w.Balance, &w.Currency, &w.Status, &w.IsDefault, &w.Version, &w.CreatedAt, &w.UpdatedAt)
	if err != nil {
		return nil, err
	}
	return w, nil
}

func (r *WalletRepository) GetWalletByUser(ctx context.Context, userID string) (*Wallet, error) {
	w := &Wallet{}
	query := `SELECT id, user_id, balance, currency, status, is_default, version, created_at, updated_at FROM wallets WHERE user_id = $1 AND status = 'active' LIMIT 1`
	err := r.db.QueryRowContext(ctx, query, userID).Scan(&w.ID, &w.UserID, &w.Balance, &w.Currency, &w.Status, &w.IsDefault, &w.Version, &w.CreatedAt, &w.UpdatedAt)
	if err != nil {
		return nil, err
	}
	return w, nil
}

func (r *WalletRepository) GetWalletForUpdate(ctx context.Context, userID string) (*Wallet, error) {
	w := &Wallet{}
	query := `SELECT id, user_id, balance, currency, status, is_default, version, created_at, updated_at FROM wallets WHERE user_id = $1 AND status = 'active' LIMIT 1`
	err := r.db.QueryRowContext(ctx, query, userID).Scan(&w.ID, &w.UserID, &w.Balance, &w.Currency, &w.Status, &w.IsDefault, &w.Version, &w.CreatedAt, &w.UpdatedAt)
	if err != nil {
		return nil, err
	}
	return w, nil
}

func (r *WalletRepository) UpdateBalance(ctx context.Context, id int64, amount int64) error {
	query := `UPDATE wallets SET balance = balance + $1, updated_at = CURRENT_TIMESTAMP WHERE id = $2`
	_, err := r.db.ExecContext(ctx, query, amount, id)
	return err
}

// UpdateBalanceWithVersion updates balance with optimistic locking
func (r *WalletRepository) UpdateBalanceWithVersion(ctx context.Context, id int64, amount int64, expectedVersion int64) error {
	query := `UPDATE wallets SET balance = balance + $1, updated_at = CURRENT_TIMESTAMP, version = version + 1 WHERE id = $2 AND version = $3`
	result, err := r.db.ExecContext(ctx, query, amount, id, expectedVersion)
	if err != nil {
		return err
	}
	rowsAffected, err := result.RowsAffected()
	if err != nil {
		return err
	}
	if rowsAffected == 0 {
		return fmt.Errorf("optimistic lock failed: wallet version mismatch")
	}
	return nil
}

func (r *WalletRepository) HasSufficientBalance(ctx context.Context, userID string, amount int64) (bool, error) {
	w, err := r.GetWalletByUser(ctx, userID)
	if err != nil {
		return false, err
	}
	return w != nil && w.Balance >= amount, nil
}

// DB returns the underlying database connection
func (r *WalletRepository) DB() *sql.DB {
	return r.db
}

func (r *WalletRepository) UpdateStatus(ctx context.Context, id int64, status string) error {
	query := `UPDATE wallets SET status = $1, updated_at = CURRENT_TIMESTAMP WHERE id = $2`
	_, err := r.db.ExecContext(ctx, query, status, id)
	return err
}

func (r *WalletRepository) DeleteWallet(ctx context.Context, id int64) error {
	query := `DELETE FROM wallets WHERE id = $1`
	_, err := r.db.ExecContext(ctx, query, id)
	return err
}

type TransactionRepository struct {
	db *sql.DB
}

func NewTransactionRepository(db *sql.DB) *TransactionRepository {
	return &TransactionRepository{db: db}
}

func (r *TransactionRepository) CreateTransaction(ctx context.Context, t *WalletTransaction) error {
	query := `INSERT INTO wallet_transactions (wallet_id, type, amount, balance_after, description, reference_id, metadata, status)
	          VALUES ($1, $2, $3, $4, $5, $6, $7, $8) RETURNING id, created_at`
	return r.db.QueryRowContext(ctx, query, t.WalletID, t.Type, t.Amount, t.BalanceAfter, t.Description, t.ReferenceID, t.Metadata, t.Status).Scan(&t.ID, &t.CreatedAt)
}

func (r *TransactionRepository) GetByWallet(ctx context.Context, walletID int64, limit, offset int) ([]*WalletTransaction, error) {
	rows, err := r.db.QueryContext(ctx, `SELECT id, wallet_id, type, amount, balance_after, description, reference_id, metadata, status, created_at
	                         FROM wallet_transactions WHERE wallet_id = $1 ORDER BY created_at DESC LIMIT $2 OFFSET $3`,
		walletID, limit, offset)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var txns []*WalletTransaction
	for rows.Next() {
		t := &WalletTransaction{}
		if err := rows.Scan(&t.ID, &t.WalletID, &t.Type, &t.Amount, &t.BalanceAfter, &t.Description, &t.ReferenceID, &t.Metadata, &t.Status, &t.CreatedAt); err != nil {
			return nil, err
		}
		txns = append(txns, t)
	}
	return txns, nil
}

func (r *TransactionRepository) GetByReference(ctx context.Context, refID string) (*WalletTransaction, error) {
	t := &WalletTransaction{}
	query := `SELECT id, wallet_id, type, amount, balance_after, description, reference_id, metadata, status, created_at
	          FROM wallet_transactions WHERE reference_id = $1`
	err := r.db.QueryRowContext(ctx, query, refID).Scan(&t.ID, &t.WalletID, &t.Type, &t.Amount, &t.BalanceAfter, &t.Description, &t.ReferenceID, &t.Metadata, &t.Status, &t.CreatedAt)
	if err != nil {
		return nil, err
	}
	return t, nil
}

// EventHandler is interface for emitting events
type EventHandler interface {
	EmitEvent(ctx context.Context, eventType string, data interface{}) error
}

// WalletRepositoryInterface defines wallet repo contract
type WalletRepositoryInterface interface {
	CreateWallet(ctx context.Context, w *Wallet) error
	GetWallet(ctx context.Context, id int64) (*Wallet, error)
	GetWalletByUser(ctx context.Context, userID string) (*Wallet, error)
	GetWalletForUpdate(ctx context.Context, userID string) (*Wallet, error) // FOR UPDATE locking
	UpdateBalance(ctx context.Context, id int64, amount int64) error
	UpdateBalanceWithVersion(ctx context.Context, id int64, amount int64, expectedVersion int64) error
	HasSufficientBalance(ctx context.Context, userID string, amount int64) (bool, error)
	DeleteWallet(ctx context.Context, id int64) error
	UpdateStatus(ctx context.Context, id int64, status string) error
	DB() *sql.DB
}

// TransactionRepositoryInterface defines transaction repo contract
type TransactionRepositoryInterface interface {
	CreateTransaction(ctx context.Context, t *WalletTransaction) error
	GetByWallet(ctx context.Context, walletID int64, limit, offset int) ([]*WalletTransaction, error)
	GetByReference(ctx context.Context, refID string) (*WalletTransaction, error)
}

type WalletService struct {
	walletRepo   WalletRepositoryInterface
	txnRepo      TransactionRepositoryInterface
	eventHandler EventHandler
	beginTx      func(context.Context, *sql.TxOptions) (Tx, error) // test hook
}

func NewWalletService(db *sql.DB, eventHandler EventHandler) *WalletService {
	svc := &WalletService{
		walletRepo:   NewWalletRepository(db),
		txnRepo:      NewTransactionRepository(db),
		eventHandler: eventHandler,
	}
	svc.beginTx = func(ctx context.Context, opts *sql.TxOptions) (Tx, error) {
		tx, err := db.BeginTx(ctx, opts)
		if err != nil {
			return nil, err
		}
		return &sqlTx{Tx: tx}, nil
	}
	return svc
}

func (s *WalletService) CreateWallet(ctx context.Context, userID, currency string) (*Wallet, error) {
	wallet := &Wallet{
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

func (s *WalletService) GetWallet(ctx context.Context, userID string) (*Wallet, error) {
	return s.walletRepo.GetWalletByUser(ctx, userID)
}

func (s *WalletService) TopUp(ctx context.Context, req *TopUpRequest) (*WalletTransaction, error) {
	tx, err := s.beginTx(ctx, nil)
	if err != nil {
		return nil, fmt.Errorf("failed to begin transaction: %w", err)
	}
	defer func() {
		if p := recover(); p != nil {
			tx.Rollback()
		}
	}()

	wallet, err := s.walletRepo.GetWalletByUser(ctx, req.UserID)
	if err != nil {
		tx.Rollback()
		wallet, err = s.CreateWallet(ctx, req.UserID, "VND")
		if err != nil {
			tx.Rollback()
			return nil, fmt.Errorf("failed to create wallet: %w", err)
		}
	}

	if err := s.walletRepo.UpdateBalance(ctx, wallet.ID, req.Amount); err != nil {
		tx.Rollback()
		return nil, fmt.Errorf("failed to update balance: %w", err)
	}

	txn := &WalletTransaction{
		WalletID:     wallet.ID,
		Type:         "topup",
		Amount:       req.Amount,
		BalanceAfter: wallet.Balance + req.Amount,
		Description:  fmt.Sprintf("Top up via %s", req.Method),
		ReferenceID:  fmt.Sprintf("TOPUP-%d", time.Now().UnixNano()),
		Metadata:     fmt.Sprintf(`{"method": "%s", "bank_code": "%s"}`, req.Method, req.BankCode),
		Status:       "completed",
	}
	if err := s.txnRepo.CreateTransaction(ctx, txn); err != nil {
		tx.Rollback()
		return nil, fmt.Errorf("failed to create transaction: %w", err)
	}

	if err := tx.Commit(); err != nil {
		return nil, fmt.Errorf("failed to commit transaction: %w", err)
	}

	if s.eventHandler != nil {
		event := TransactionEvent{
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

func (s *WalletService) Transfer(ctx context.Context, req *TransferRequest) (*WalletTransaction, error) {
	tx, err := s.beginTx(ctx, nil)
	if err != nil {
		return nil, fmt.Errorf("failed to begin transaction: %w", err)
	}
	defer func() {
		if p := recover(); p != nil {
			tx.Rollback()
		}
	}()

	hasBalance, err := s.walletRepo.HasSufficientBalance(ctx, req.FromUserID, req.Amount)
	if err != nil {
		tx.Rollback()
		return nil, fmt.Errorf("failed to check balance: %w", err)
	}
	if !hasBalance {
		tx.Rollback()
		return nil, fmt.Errorf("insufficient balance")
	}

	fromWallet, _ := s.walletRepo.GetWalletByUser(ctx, req.FromUserID)
	toWallet, _ := s.walletRepo.GetWalletByUser(ctx, req.ToUserID)
	if toWallet == nil {
		toWallet, err = s.CreateWallet(ctx, req.ToUserID, "VND")
		if err != nil {
			tx.Rollback()
			return nil, fmt.Errorf("failed to create destination wallet: %w", err)
		}
	}

	if err := s.walletRepo.UpdateBalance(ctx, fromWallet.ID, -req.Amount); err != nil {
		tx.Rollback()
		return nil, fmt.Errorf("failed to debit source wallet: %w", err)
	}

	if err := s.walletRepo.UpdateBalance(ctx, toWallet.ID, req.Amount); err != nil {
		tx.Rollback()
		return nil, fmt.Errorf("failed to credit destination wallet: %w", err)
	}

	txn := &WalletTransaction{
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
		tx.Rollback()
		return nil, fmt.Errorf("failed to create source transaction: %w", err)
	}

	inTxn := &WalletTransaction{
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
		tx.Rollback()
		return nil, fmt.Errorf("failed to create destination transaction: %w", err)
	}

	if err := tx.Commit(); err != nil {
		return nil, fmt.Errorf("failed to commit transaction: %w", err)
	}

	if s.eventHandler != nil {
		event := TransactionEvent{
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

func (s *WalletService) Withdraw(ctx context.Context, req *WithdrawalRequest) (*WalletTransaction, error) {
	tx, err := s.beginTx(ctx, nil)
	if err != nil {
		return nil, fmt.Errorf("failed to begin transaction: %w", err)
	}
	defer func() {
		if p := recover(); p != nil {
			tx.Rollback()
		}
	}()

	hasBalance, err := s.walletRepo.HasSufficientBalance(ctx, req.UserID, req.Amount)
	if err != nil {
		tx.Rollback()
		return nil, fmt.Errorf("failed to check balance: %w", err)
	}
	if !hasBalance {
		tx.Rollback()
		return nil, fmt.Errorf("insufficient balance")
	}

	wallet, _ := s.walletRepo.GetWalletByUser(ctx, req.UserID)
	if err := s.walletRepo.UpdateBalance(ctx, wallet.ID, -req.Amount); err != nil {
		tx.Rollback()
		return nil, fmt.Errorf("failed to update balance: %w", err)
	}

	txn := &WalletTransaction{
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
		tx.Rollback()
		return nil, fmt.Errorf("failed to create transaction: %w", err)
	}

	if err := tx.Commit(); err != nil {
		return nil, fmt.Errorf("failed to commit transaction: %w", err)
	}

	if s.eventHandler != nil {
		event := TransactionEvent{
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

func (s *WalletService) GetBalance(ctx context.Context, userID string) (int64, error) {
	wallet, err := s.walletRepo.GetWalletByUser(ctx, userID)
	if err != nil {
		return 0, err
	}
	return wallet.Balance, nil
}

func (s *WalletService) GetTransactions(ctx context.Context, userID string, limit, offset int) ([]*WalletTransaction, error) {
	wallet, err := s.walletRepo.GetWalletByUser(ctx, userID)
	if err != nil {
		return nil, err
	}
	return s.txnRepo.GetByWallet(ctx, wallet.ID, limit, offset)
}

func (s *WalletService) ProcessWithdrawCallback(ctx context.Context, refID string, status string) error {
	tx, err := s.beginTx(ctx, nil)
	if err != nil {
		return fmt.Errorf("failed to begin transaction: %w", err)
	}
	defer func() {
		if p := recover(); p != nil {
			tx.Rollback()
		}
	}()

	txn, err := s.txnRepo.GetByReference(ctx, refID)
	if err != nil {
		tx.Rollback()
		return fmt.Errorf("transaction not found: %w", err)
	}

	if status == "success" {
		txn.Status = "completed"
	} else if status == "failed" {
		txn.Status = "failed"
		wallet, err := s.walletRepo.GetWallet(ctx, txn.WalletID)
		if err != nil {
			tx.Rollback()
			return fmt.Errorf("failed to get wallet: %w", err)
		}

		if err := s.walletRepo.UpdateBalance(ctx, wallet.ID, -txn.Amount); err != nil {
			tx.Rollback()
			return fmt.Errorf("failed to rollback balance: %w", err)
		}
	}

	if err := tx.Commit(); err != nil {
		return fmt.Errorf("failed to commit transaction: %w", err)
	}

	if s.eventHandler != nil {
		event := TransactionEvent{
			EventType:     "wallet.withdrawal_completed",
			TransactionID: txn.ID,
			WalletID:      txn.WalletID,
			Type:          "withdraw",
			Amount:        txn.Amount,
			Status:        status,
			Timestamp:     time.Now(),
		}
		_ = s.eventHandler.EmitEvent(ctx, "wallet.transaction", event)
	}

	return nil
}

// DeleteWallet removes a wallet by user ID
func (s *WalletService) DeleteWallet(ctx context.Context, userID string) error {
	wallet, err := s.walletRepo.GetWalletByUser(ctx, userID)
	if err != nil {
		return err
	}
	return s.walletRepo.DeleteWallet(ctx, wallet.ID)
}

// LockWallet sets wallet status to 'locked'
func (s *WalletService) LockWallet(ctx context.Context, userID, reason string) error {
	wallet, err := s.walletRepo.GetWalletByUser(ctx, userID)
	if err != nil {
		return err
	}
	// TODO: Store reason in audit log or separate table if required
	return s.walletRepo.UpdateStatus(ctx, wallet.ID, "locked")
}

// UnlockWallet sets wallet status back to 'active'
func (s *WalletService) UnlockWallet(ctx context.Context, userID string) error {
	wallet, err := s.walletRepo.GetWalletByUser(ctx, userID)
	if err != nil {
		return err
	}
	return s.walletRepo.UpdateStatus(ctx, wallet.ID, "active")
}

// ExportTransactions is an alias to GetTransactions for now; can be extended for CSV export
func (s *WalletService) ExportTransactions(ctx context.Context, userID string, limit, offset int) ([]*WalletTransaction, error) {
	return s.GetTransactions(ctx, userID, limit, offset)
}
