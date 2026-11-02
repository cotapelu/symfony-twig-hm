package banking

import (
	"context"
	"crypto/rand"
	"database/sql"
	"encoding/hex"
	"errors"
	"fmt"
	"time"

	bankingmodels "community.local/modules/Banking/internal/models"
)

var (
	ErrAccountNotFound     = errors.New("account not found")
	ErrInsufficientBalance = errors.New("insufficient balance")
	ErrInvalidAmount       = errors.New("invalid amount")
	ErrAccountNotActive    = errors.New("account is not active")
	ErrDuplicateAccount    = errors.New("account number already exists")
	ErrIdempotencyKeyUsed  = errors.New("idempotency key already used")
)

type BankingRepository interface {
	CreateAccount(ctx context.Context, account *bankingmodels.Account) error
	GetAccount(ctx context.Context, id int64) (*bankingmodels.Account, error)
	GetAccountByNumber(ctx context.Context, accountNumber string) (*bankingmodels.Account, error)
	GetUserAccounts(ctx context.Context, userID string) ([]*bankingmodels.Account, error)
	UpdateAccount(ctx context.Context, account *bankingmodels.Account) error
	UpdateAccountBalance(ctx context.Context, accountID int64, amount float64, isDeposit bool) (float64, error)
	CreateTransaction(ctx context.Context, txn *bankingmodels.Transaction) error
	GetTransactionByIdempotencyKey(ctx context.Context, key string) (*bankingmodels.Transaction, error)
	GetTransactions(ctx context.Context, accountID int64, limit, offset int) ([]*bankingmodels.Transaction, error)
}

type BankingService struct {
	repo BankingRepository
}

func NewBankingService(repo BankingRepository) *BankingService {
	return &BankingService{repo: repo}
}

func (s *BankingService) CreateAccount(ctx context.Context, userID, accountType, currency string) (*bankingmodels.Account, error) {
	account := &bankingmodels.Account{
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

func (s *BankingService) GetAccount(ctx context.Context, id int64) (*bankingmodels.Account, error) {
	account, err := s.repo.GetAccount(ctx, id)
	if err != nil {
		if errors.Is(err, sql.ErrNoRows) {
			return nil, ErrAccountNotFound
		}
		return nil, err
	}
	return account, nil
}

func (s *BankingService) GetAccountByNumber(ctx context.Context, accountNumber string) (*bankingmodels.Account, error) {
	account, err := s.repo.GetAccountByNumber(ctx, accountNumber)
	if err != nil {
		if errors.Is(err, sql.ErrNoRows) {
			return nil, ErrAccountNotFound
		}
		return nil, err
	}
	return account, nil
}

func (s *BankingService) GetUserAccounts(ctx context.Context, userID string) ([]*bankingmodels.Account, error) {
	return s.repo.GetUserAccounts(ctx, userID)
}

func (s *BankingService) GetBalance(ctx context.Context, accountID int64) (float64, error) {
	account, err := s.GetAccount(ctx, accountID)
	if err != nil {
		return 0, err
	}
	return account.Balance, nil
}

func (s *BankingService) GetTransactions(ctx context.Context, accountID int64, limit, offset int) ([]*bankingmodels.Transaction, error) {
	if limit <= 0 {
		limit = 20
	}
	if limit > 100 {
		limit = 100
	}
	return s.repo.GetTransactions(ctx, accountID, limit, offset)
}

func (s *BankingService) Deposit(ctx context.Context, accountID int64, amount float64, description string) (*bankingmodels.Transaction, error) {
	// Input validation
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

	// Use atomic balance update to prevent race conditions
	newBalance, err := s.repo.UpdateAccountBalance(ctx, accountID, amount, true)
	if err != nil {
		return nil, err
	}

	txn := &bankingmodels.Transaction{
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

func (s *BankingService) Withdraw(ctx context.Context, accountID int64, amount float64, description string) (*bankingmodels.Transaction, error) {
	// Input validation
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

	// Use atomic balance update to prevent race conditions
	newBalance, err := s.repo.UpdateAccountBalance(ctx, accountID, amount, false)
	if err != nil {
		return nil, err
	}

	txn := &bankingmodels.Transaction{
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

func (s *BankingService) Transfer(ctx context.Context, req *bankingmodels.TransferRequest) (*bankingmodels.Transaction, error) {
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

	txn := &bankingmodels.Transaction{
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

func generateAccountNumber() string {
	bytes := make([]byte, 4)
	rand.Read(bytes)
	return fmt.Sprintf("%s-%d", hex.EncodeToString(bytes), time.Now().UnixNano())
}
