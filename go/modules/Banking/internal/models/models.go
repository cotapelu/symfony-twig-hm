package banking

import (
	"context"
	"database/sql"
	"fmt"
	"time"

	_ "github.com/lib/pq"
)

type Account struct {
	ID            int64     `json:"id"`
	UserID        string    `json:"user_id"`
	AccountNumber string    `json:"account_number"`
	AccountType   string    `json:"account_type"`
	Balance       float64   `json:"balance"`
	Currency      string    `json:"currency"`
	Status        string    `json:"status"`
	CreatedAt     time.Time `json:"created_at"`
	UpdatedAt     time.Time `json:"updated_at"`
}

type Transaction struct {
	ID           int64     `json:"id"`
	AccountID    int64     `json:"account_id"`
	Type         string    `json:"type"`
	Amount       float64   `json:"amount"`
	BalanceAfter float64   `json:"balance_after"`
	Description  string    `json:"description"`
	ReferenceID  string    `json:"reference_id"`
	Status       string    `json:"status"`
	CreatedAt    time.Time `json:"created_at"`
}

type TransferRequest struct {
	FromAccount string  `json:"from_account"`
	ToAccount   string  `json:"to_account"`
	Amount      float64 `json:"amount"`
	Description string  `json:"description"`
}

// TransactionEvent represents an event emitted when a banking transaction occurs
type TransactionEvent struct {
	EventType     string    `json:"event_type"`
	TransactionID int64     `json:"transaction_id"`
	AccountID     int64     `json:"account_id"`
	UserID        string    `json:"user_id"`
	Type          string    `json:"type"`
	Amount        float64   `json:"amount"`
	Status        string    `json:"status"`
	Timestamp     time.Time `json:"timestamp"`
}

type AccountRepository struct {
	db *sql.DB
}

func NewAccountRepository(db *sql.DB) *AccountRepository {
	return &AccountRepository{db: db}
}

func (r *AccountRepository) CreateAccount(ctx context.Context, a *Account) error {
	query := `INSERT INTO banking_accounts (user_id, account_number, account_type, balance, currency, status)
	          VALUES ($1, $2, $3, $4, $5, $6) RETURNING id, created_at`
	return r.db.QueryRowContext(ctx, query, a.UserID, a.AccountNumber, a.AccountType, a.Balance, a.Currency, a.Status).Scan(&a.ID, &a.CreatedAt)
}

func (r *AccountRepository) GetAccountByID(ctx context.Context, id int64) (*Account, error) {
	a := &Account{}
	query := `SELECT id, user_id, account_number, account_type, balance, currency, status, created_at, updated_at
	          FROM banking_accounts WHERE id = $1`
	err := r.db.QueryRowContext(ctx, query, id).Scan(&a.ID, &a.UserID, &a.AccountNumber, &a.AccountType, &a.Balance, &a.Currency, &a.Status, &a.CreatedAt, &a.UpdatedAt)
	if err != nil {
		return nil, err
	}
	return a, nil
}

func (r *AccountRepository) GetAccountByNumber(ctx context.Context, accNum string) (*Account, error) {
	a := &Account{}
	query := `SELECT id, user_id, account_number, account_type, balance, currency, status, created_at, updated_at
	          FROM banking_accounts WHERE account_number = $1`
	err := r.db.QueryRowContext(ctx, query, accNum).Scan(&a.ID, &a.UserID, &a.AccountNumber, &a.AccountType, &a.Balance, &a.Currency, &a.Status, &a.CreatedAt, &a.UpdatedAt)
	if err != nil {
		return nil, err
	}
	return a, nil
}

func (r *AccountRepository) GetAccountsByUser(ctx context.Context, userID string) ([]*Account, error) {
	rows, err := r.db.QueryContext(ctx, `SELECT id, user_id, account_number, account_type, balance, currency, status, created_at, updated_at
	                         FROM banking_accounts WHERE user_id = $1 ORDER BY created_at DESC`, userID)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var accounts []*Account
	for rows.Next() {
		a := &Account{}
		if err := rows.Scan(&a.ID, &a.UserID, &a.AccountNumber, &a.AccountType, &a.Balance, &a.Currency, &a.Status, &a.CreatedAt, &a.UpdatedAt); err != nil {
			return nil, err
		}
		accounts = append(accounts, a)
	}
	return accounts, nil
}

func (r *AccountRepository) UpdateBalance(ctx context.Context, id int64, amount float64) error {
	query := `UPDATE banking_accounts SET balance = balance + $1, updated_at = CURRENT_TIMESTAMP WHERE id = $2`
	_, err := r.db.ExecContext(ctx, query, amount, id)
	return err
}

// DB returns the underlying database connection
func (r *AccountRepository) DB() *sql.DB {
	return r.db
}

type TransactionRepository struct {
	db *sql.DB
}

func NewTransactionRepository(db *sql.DB) *TransactionRepository {
	return &TransactionRepository{db: db}
}

func (r *TransactionRepository) CreateTransaction(ctx context.Context, t *Transaction) error {
	query := `INSERT INTO banking_transactions (account_id, type, amount, balance_after, description, reference_id, status)
	          VALUES ($1, $2, $3, $4, $5, $6, $7) RETURNING id, created_at`
	return r.db.QueryRowContext(ctx, query, t.AccountID, t.Type, t.Amount, t.BalanceAfter, t.Description, t.ReferenceID, t.Status).Scan(&t.ID, &t.CreatedAt)
}

func (r *TransactionRepository) GetTransactionsByAccount(ctx context.Context, accID int64, limit, offset int) ([]*Transaction, error) {
	rows, err := r.db.QueryContext(ctx, `SELECT id, account_id, type, amount, balance_after, description, reference_id, status, created_at
	                         FROM banking_transactions WHERE account_id = $1 ORDER BY created_at DESC LIMIT $2 OFFSET $3`,
		accID, limit, offset)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var txns []*Transaction
	for rows.Next() {
		t := &Transaction{}
		if err := rows.Scan(&t.ID, &t.AccountID, &t.Type, &t.Amount, &t.BalanceAfter, &t.Description, &t.ReferenceID, &t.Status, &t.CreatedAt); err != nil {
			return nil, err
		}
		txns = append(txns, t)
	}
	return txns, nil
}

func (r *TransactionRepository) GetTransactionByReference(ctx context.Context, refID string) (*Transaction, error) {
	t := &Transaction{}
	query := `SELECT id, account_id, type, amount, balance_after, description, reference_id, status, created_at
	          FROM banking_transactions WHERE reference_id = $1`
	err := r.db.QueryRowContext(ctx, query, refID).Scan(&t.ID, &t.AccountID, &t.Type, &t.Amount, &t.BalanceAfter, &t.Description, &t.ReferenceID, &t.Status, &t.CreatedAt)
	if err != nil {
		return nil, err
	}
	return t, nil
}

// EventHandler is interface for emitting events
type EventHandler interface {
	EmitEvent(ctx context.Context, eventType string, data interface{}) error
}

// AccountRepositoryInterface defines account repo contract
type AccountRepositoryInterface interface {
	CreateAccount(ctx context.Context, a *Account) error
	GetAccountByID(ctx context.Context, id int64) (*Account, error)
	GetAccountByNumber(ctx context.Context, accNum string) (*Account, error)
	GetAccountsByUser(ctx context.Context, userID string) ([]*Account, error)
	UpdateBalance(ctx context.Context, id int64, amount float64) error
	DB() *sql.DB
}

// TransactionRepositoryInterface defines transaction repo contract
type TransactionRepositoryInterface interface {
	CreateTransaction(ctx context.Context, t *Transaction) error
	GetTransactionsByAccount(ctx context.Context, accID int64, limit, offset int) ([]*Transaction, error)
	GetTransactionByReference(ctx context.Context, refID string) (*Transaction, error)
}

type BankingService struct {
	accRepo      AccountRepositoryInterface
	txnRepo      TransactionRepositoryInterface
	eventHandler EventHandler
}

func NewBankingService(db *sql.DB, eventHandler EventHandler) *BankingService {
	return &BankingService{
		accRepo:      NewAccountRepository(db),
		txnRepo:      NewTransactionRepository(db),
		eventHandler: eventHandler,
	}
}

func (s *BankingService) CreateAccount(ctx context.Context, userID, accType, currency string) (*Account, error) {
	acc := &Account{
		UserID:        userID,
		AccountNumber: fmt.Sprintf("BK-%s-%d", time.Now().Format("060102"), time.Now().UnixNano()%1000000),
		AccountType:   accType,
		Balance:       0,
		Currency:      currency,
		Status:        "active",
	}
	if err := s.accRepo.CreateAccount(ctx, acc); err != nil {
		return nil, err
	}
	return acc, nil
}

func (s *BankingService) GetAccount(ctx context.Context, id int64) (*Account, error) {
	return s.accRepo.GetAccountByID(ctx, id)
}

func (s *BankingService) GetAccountByNumber(ctx context.Context, accNum string) (*Account, error) {
	return s.accRepo.GetAccountByNumber(ctx, accNum)
}

func (s *BankingService) GetUserAccounts(ctx context.Context, userID string) ([]*Account, error) {
	return s.accRepo.GetAccountsByUser(ctx, userID)
}

func (s *BankingService) Deposit(ctx context.Context, accID int64, amount float64, description string) (*Transaction, error) {
	if amount <= 0 {
		return nil, fmt.Errorf("deposit amount must be positive")
	}

	tx, err := s.accRepo.DB().BeginTx(ctx, nil)
	if err != nil {
		return nil, fmt.Errorf("failed to begin transaction: %w", err)
	}
	defer func() {
		if p := recover(); p != nil {
			tx.Rollback()
		}
	}()

	if err := s.accRepo.UpdateBalance(ctx, accID, amount); err != nil {
		tx.Rollback()
		return nil, fmt.Errorf("failed to update balance: %w", err)
	}

	acc, err := s.accRepo.GetAccountByID(ctx, accID)
	if err != nil {
		tx.Rollback()
		return nil, fmt.Errorf("failed to get account: %w", err)
	}

	txn := &Transaction{
		AccountID:    accID,
		Type:         "deposit",
		Amount:       amount,
		BalanceAfter: acc.Balance + amount,
		Description:  description,
		ReferenceID:  fmt.Sprintf("DEP-%d", time.Now().UnixNano()),
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
			EventType:     "banking.deposited",
			TransactionID: txn.ID,
			AccountID:     accID,
			UserID:        acc.UserID,
			Type:          "deposit",
			Amount:        amount,
			Status:        "completed",
			Timestamp:     time.Now(),
		}
		_ = s.eventHandler.EmitEvent(ctx, "banking.transaction", event)
	}

	return txn, nil
}

func (s *BankingService) Withdraw(ctx context.Context, accID int64, amount float64, description string) (*Transaction, error) {
	if amount <= 0 {
		return nil, fmt.Errorf("withdrawal amount must be positive")
	}

	tx, err := s.accRepo.DB().BeginTx(ctx, nil)
	if err != nil {
		return nil, fmt.Errorf("failed to begin transaction: %w", err)
	}
	defer func() {
		if p := recover(); p != nil {
			tx.Rollback()
		}
	}()

	acc, err := s.accRepo.GetAccountByID(ctx, accID)
	if err != nil {
		tx.Rollback()
		return nil, fmt.Errorf("failed to get account: %w", err)
	}

	if acc.Balance < amount {
		tx.Rollback()
		return nil, fmt.Errorf("insufficient balance")
	}

	if err := s.accRepo.UpdateBalance(ctx, accID, -amount); err != nil {
		tx.Rollback()
		return nil, fmt.Errorf("failed to update balance: %w", err)
	}

	acc, err = s.accRepo.GetAccountByID(ctx, accID)
	if err != nil {
		tx.Rollback()
		return nil, fmt.Errorf("failed to get updated account: %w", err)
	}

	txn := &Transaction{
		AccountID:    accID,
		Type:         "withdraw",
		Amount:       -amount,
		BalanceAfter: acc.Balance,
		Description:  description,
		ReferenceID:  fmt.Sprintf("WTH-%d", time.Now().UnixNano()),
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
			EventType:     "banking.withdrawn",
			TransactionID: txn.ID,
			AccountID:     accID,
			UserID:        acc.UserID,
			Type:          "withdraw",
			Amount:        amount,
			Status:        "completed",
			Timestamp:     time.Now(),
		}
		_ = s.eventHandler.EmitEvent(ctx, "banking.transaction", event)
	}

	return txn, nil
}

func (s *BankingService) Transfer(ctx context.Context, req *TransferRequest) (*Transaction, error) {
	if req.Amount <= 0 {
		return nil, fmt.Errorf("transfer amount must be positive")
	}
	if req.FromAccount == req.ToAccount {
		return nil, fmt.Errorf("cannot transfer to the same account")
	}

	tx, err := s.accRepo.DB().BeginTx(ctx, nil)
	if err != nil {
		return nil, fmt.Errorf("failed to begin transaction: %w", err)
	}
	defer func() {
		if p := recover(); p != nil {
			tx.Rollback()
		}
	}()

	fromAcc, err := s.accRepo.GetAccountByNumber(ctx, req.FromAccount)
	if err != nil {
		tx.Rollback()
		return nil, fmt.Errorf("source account not found: %w", err)
	}

	toAcc, err := s.accRepo.GetAccountByNumber(ctx, req.ToAccount)
	if err != nil {
		tx.Rollback()
		return nil, fmt.Errorf("destination account not found: %w", err)
	}

	if fromAcc.Balance < req.Amount {
		tx.Rollback()
		return nil, fmt.Errorf("insufficient balance")
	}

	if err := s.accRepo.UpdateBalance(ctx, fromAcc.ID, -req.Amount); err != nil {
		tx.Rollback()
		return nil, fmt.Errorf("failed to debit source account: %w", err)
	}

	if err := s.accRepo.UpdateBalance(ctx, toAcc.ID, req.Amount); err != nil {
		tx.Rollback()
		if rollbackErr := s.accRepo.UpdateBalance(ctx, fromAcc.ID, req.Amount); rollbackErr != nil {
			return nil, fmt.Errorf("transfer failed and rollback also failed: %v (rollback error: %w)", err, rollbackErr)
		}
		return nil, fmt.Errorf("transfer failed, source account has been credited back: %w", err)
	}

	fromAcc, err = s.accRepo.GetAccountByID(ctx, fromAcc.ID)
	if err != nil {
		tx.Rollback()
		return nil, fmt.Errorf("failed to get updated source account: %w", err)
	}

	txn := &Transaction{
		AccountID:    fromAcc.ID,
		Type:         "transfer",
		Amount:       -req.Amount,
		BalanceAfter: fromAcc.Balance,
		Description:  req.Description,
		ReferenceID:  fmt.Sprintf("TRF-%d", time.Now().UnixNano()),
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
			EventType:     "banking.transferred",
			TransactionID: txn.ID,
			AccountID:     fromAcc.ID,
			UserID:        fromAcc.UserID,
			Type:          "transfer",
			Amount:        req.Amount,
			Status:        "completed",
			Timestamp:     time.Now(),
		}
		_ = s.eventHandler.EmitEvent(ctx, "banking.transaction", event)
	}

	return txn, nil
}

func (s *BankingService) GetTransactions(ctx context.Context, accID int64, limit, offset int) ([]*Transaction, error) {
	return s.txnRepo.GetTransactionsByAccount(ctx, accID, limit, offset)
}

func (s *BankingService) GetBalance(ctx context.Context, accID int64) (float64, error) {
	acc, err := s.accRepo.GetAccountByID(ctx, accID)
	if err != nil {
		return 0, err
	}
	return acc.Balance, nil
}
