package banking

import (
	"context"
	"database/sql"
	"errors"
	"fmt"
	"time"

	bm "community.local/modules/Banking/internal/models"
)

// SQLiteBankingService implements BankingService interface for SQLite
type SQLiteBankingService struct {
	repo *SQLiteBankingRepository
}

func NewSQLiteBankingService(db *sql.DB) *SQLiteBankingService {
	return &SQLiteBankingService{
		repo: NewSQLiteBankingRepository(db),
	}
}

func (s *SQLiteBankingService) CreateAccount(ctx context.Context, userID, accountType, currency string) (*bm.Account, error) {
	account := &bm.Account{
		UserID:        userID,
		AccountNumber: generateAccountNumber(),
		AccountType:   accountType,
		Balance:       0,
		Currency:      currency,
		Status:        "active",
		CreatedAt:     time.Now(),
		UpdatedAt:     time.Now(),
	}

	if err := s.repo.CreateAccount(ctx, account); err != nil {
		return nil, err
	}

	return account, nil
}

func (s *SQLiteBankingService) GetAccount(ctx context.Context, id int64) (*bm.Account, error) {
	account, err := s.repo.GetAccount(ctx, id)
	if err != nil {
		if errors.Is(err, sql.ErrNoRows) {
			return nil, ErrAccountNotFound
		}
		return nil, err
	}
	return account, nil
}

func (s *SQLiteBankingService) GetAccountByNumber(ctx context.Context, accountNumber string) (*bm.Account, error) {
	account, err := s.repo.GetAccountByNumber(ctx, accountNumber)
	if err != nil {
		if errors.Is(err, sql.ErrNoRows) {
			return nil, ErrAccountNotFound
		}
		return nil, err
	}
	return account, nil
}

func (s *SQLiteBankingService) GetUserAccounts(ctx context.Context, userID string) ([]*bm.Account, error) {
	return s.repo.GetUserAccounts(ctx, userID)
}

func (s *SQLiteBankingService) GetBalance(ctx context.Context, accountID int64) (float64, error) {
	account, err := s.GetAccount(ctx, accountID)
	if err != nil {
		return 0, err
	}
	return account.Balance, nil
}

func (s *SQLiteBankingService) GetTransactions(ctx context.Context, accountID int64, limit, offset int) ([]*bm.Transaction, error) {
	if limit <= 0 {
		limit = 20
	}
	if limit > 100 {
		limit = 100
	}
	return s.repo.GetTransactions(ctx, accountID, limit, offset)
}

func (s *SQLiteBankingService) Deposit(ctx context.Context, accountID int64, amount float64, description string) (*bm.Transaction, error) {
	if amount <= 0 {
		return nil, ErrInvalidAmount
	}

	account, err := s.GetAccount(ctx, accountID)
	if err != nil {
		return nil, err
	}

	if account.Status != "active" {
		return nil, ErrAccountNotActive
	}

	newBalance := account.Balance + amount
	account.Balance = newBalance
	account.UpdatedAt = time.Now()

	if err := s.repo.UpdateAccount(ctx, account); err != nil {
		return nil, err
	}

	txn := &bm.Transaction{
		AccountID:    accountID,
		Type:         "deposit",
		Amount:       amount,
		BalanceAfter: newBalance,
		Description:  description,
		Status:       "completed",
		CreatedAt:    time.Now(),
	}

	if err := s.repo.CreateTransaction(ctx, txn); err != nil {
		return nil, err
	}

	return txn, nil
}

func (s *SQLiteBankingService) Withdraw(ctx context.Context, accountID int64, amount float64, description string) (*bm.Transaction, error) {
	if amount <= 0 {
		return nil, ErrInvalidAmount
	}

	account, err := s.GetAccount(ctx, accountID)
	if err != nil {
		return nil, err
	}

	if account.Status != "active" {
		return nil, ErrAccountNotActive
	}

	if account.Balance < amount {
		return nil, ErrInsufficientBalance
	}

	newBalance := account.Balance - amount
	account.Balance = newBalance
	account.UpdatedAt = time.Now()

	if err := s.repo.UpdateAccount(ctx, account); err != nil {
		return nil, err
	}

	txn := &bm.Transaction{
		AccountID:    accountID,
		Type:         "withdrawal",
		Amount:       amount,
		BalanceAfter: newBalance,
		Description:  description,
		Status:       "completed",
		CreatedAt:    time.Now(),
	}

	if err := s.repo.CreateTransaction(ctx, txn); err != nil {
		return nil, err
	}

	return txn, nil
}

func (s *SQLiteBankingService) Transfer(ctx context.Context, req *bm.TransferRequest) (*bm.Transaction, error) {
	if req.Amount <= 0 {
		return nil, ErrInvalidAmount
	}

	fromAccount, err := s.GetAccountByNumber(ctx, req.FromAccount)
	if err != nil {
		return nil, err
	}

	if fromAccount.Status != "active" {
		return nil, ErrAccountNotActive
	}

	if fromAccount.Balance < req.Amount {
		return nil, ErrInsufficientBalance
	}

	toAccount, err := s.GetAccountByNumber(ctx, req.ToAccount)
	if err != nil {
		return nil, err
	}

	if toAccount.Status != "active" {
		return nil, ErrAccountNotActive
	}

	fromBalance := fromAccount.Balance - req.Amount
	toBalance := toAccount.Balance + req.Amount

	fromAccount.Balance = fromBalance
	fromAccount.UpdatedAt = time.Now()
	toAccount.Balance = toBalance
	toAccount.UpdatedAt = time.Now()

	if err := s.repo.UpdateAccount(ctx, fromAccount); err != nil {
		return nil, err
	}

	if err := s.repo.UpdateAccount(ctx, toAccount); err != nil {
		return nil, err
	}

	txn := &bm.Transaction{
		AccountID:    fromAccount.ID,
		Type:         "transfer",
		Amount:       req.Amount,
		BalanceAfter: fromBalance,
		Description:  req.Description,
		ReferenceID:  toAccount.AccountNumber,
		Status:       "completed",
		CreatedAt:    time.Now(),
	}

	if err := s.repo.CreateTransaction(ctx, txn); err != nil {
		return nil, err
	}

	return txn, nil
}

var (
	ErrAccountNotFound     = errors.New("account not found")
	ErrInsufficientBalance = errors.New("insufficient balance")
	ErrInvalidAmount       = errors.New("invalid amount")
	ErrAccountNotActive    = errors.New("account is not active")
	ErrDuplicateAccount    = errors.New("account number already exists")
)

func generateAccountNumber() string {
	return fmt.Sprintf("%d%d", time.Now().Unix(), time.Now().Nanosecond())
}
