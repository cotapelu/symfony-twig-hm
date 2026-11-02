package service

import (
	"context"
	"database/sql"
	"errors"
	"strings"
	"testing"
	"time"

	"community.local/internal/models"
)

// Mock BankingRepository để test
type mockBankingRepo struct {
	accounts map[string]*models.BankingAccount
	transactions []*models.BankingTransaction
}

func (m *mockBankingRepo) CreateAccount(ctx context.Context, account *models.BankingAccount) error {
	if m.accounts == nil {
		m.accounts = make(map[string]*models.BankingAccount)
	}
	account.ID = "acc-" + time.Now().Format("20060102150405")
	account.CreatedAt = time.Now()
	account.UpdatedAt = time.Now()
	m.accounts[account.ID] = account
	return nil
}

func (m *mockBankingRepo) GetAccount(ctx context.Context, id string) (*models.BankingAccount, error) {
	acc, ok := m.accounts[id]
	if !ok {
		return nil, sql.ErrNoRows
	}
	return acc, nil
}

func (m *mockBankingRepo) GetAccountByNumber(ctx context.Context, accountNumber string) (*models.BankingAccount, error) {
	for _, acc := range m.accounts {
		if acc.AccountNumber == accountNumber {
			return acc, nil
		}
	}
	return nil, sql.ErrNoRows
}

func (m *mockBankingRepo) GetAccountsByUser(ctx context.Context, userID string) ([]*models.BankingAccount, error) {
	var results []*models.BankingAccount
	for _, acc := range m.accounts {
		if acc.UserID == userID {
			results = append(results, acc)
		}
	}
	return results, nil
}

func (m *mockBankingRepo) UpdateBalance(ctx context.Context, id string, amount float64) error {
	acc, ok := m.accounts[id]
	if !ok {
		return errors.New("not found")
	}
	acc.Balance += amount
	acc.UpdatedAt = time.Now()
	return nil
}

func (m *mockBankingRepo) CreateTransaction(ctx context.Context, tx *models.BankingTransaction) error {
	if m.transactions == nil {
		m.transactions = make([]*models.BankingTransaction, 0)
	}
	tx.ID = "tx-" + time.Now().Format("20060102150405")
	tx.CreatedAt = time.Now()
	m.transactions = append(m.transactions, tx)
	return nil
}

func (m *mockBankingRepo) GetTransactions(ctx context.Context, accountID string, limit, offset int) ([]*models.BankingTransaction, error) {
	var results []*models.BankingTransaction
	for _, tx := range m.transactions {
		if tx.AccountID == accountID {
			results = append(results, tx)
		}
	}
	return results, nil
}

// BankingService tests
func TestCreateAccount(t *testing.T) {
	repo := &mockBankingRepo{accounts: make(map[string]*models.BankingAccount)}
	svc := NewBankingService(repo)

	ctx := context.Background()
	account, err := svc.CreateAccount(ctx, "user-1", "ACC-001", "CHECKING")
	if err != nil {
		t.Fatalf("CreateAccount failed: %v", err)
	}

	if account.UserID != "user-1" {
		t.Errorf("Expected UserID 'user-1', got %s", account.UserID)
	}
	if account.AccountNumber != "ACC-001" {
		t.Errorf("Expected AccountNumber 'ACC-001', got %s", account.AccountNumber)
	}
	if account.AccountType != "CHECKING" {
		t.Errorf("Expected AccountType 'CHECKING', got %s", account.AccountType)
	}
	if account.Status != "ACTIVE" {
		t.Errorf("Expected default status 'ACTIVE', got %s", account.Status)
	}
}

func TestCreateAccount_InvalidType(t *testing.T) {
	repo := &mockBankingRepo{accounts: make(map[string]*models.BankingAccount)}
	svc := NewBankingService(repo)

	ctx := context.Background()
	account, err := svc.CreateAccount(ctx, "user-1", "ACC-001", "INVALID")
	if err == nil {
		t.Fatal("Expected error for invalid account type, got none")
	}
	if account != nil {
		t.Errorf("Expected nil account, got %v", account)
	}
}

func TestGetAccount_NotFound(t *testing.T) {
	repo := &mockBankingRepo{accounts: make(map[string]*models.BankingAccount)}
	svc := NewBankingService(repo)

	ctx := context.Background()
	_, err := svc.GetAccount(ctx, "non-existent")
	if err == nil {
		t.Fatal("Expected error for non-existent account")
	}
}

func TestGetAccount_Success(t *testing.T) {
	repo := &mockBankingRepo{accounts: map[string]*models.BankingAccount{
		"acc-1": {ID: "acc-1", AccountNumber: "123456", UserID: "user-1", Balance: 100, Status: "ACTIVE"},
	}}
	svc := NewBankingService(repo)
	ctx := context.Background()

	acc, err := svc.GetAccount(ctx, "acc-1")
	if err != nil {
		t.Fatalf("GetAccount failed: %v", err)
	}
	if acc.ID != "acc-1" {
		t.Errorf("Expected acc-1, got %s", acc.ID)
	}
}

func TestGetAccount_Closed(t *testing.T) {
	repo := &mockBankingRepo{accounts: map[string]*models.BankingAccount{
		"acc-1": {ID: "acc-1", AccountNumber: "123456", UserID: "user-1", Balance: 100, Status: "CLOSED"},
	}}
	svc := NewBankingService(repo)
	ctx := context.Background()

	_, err := svc.GetAccount(ctx, "acc-1")
	if err == nil {
		t.Fatal("Expected error for closed account, got nil")
	}
	if !strings.Contains(err.Error(), "closed") {
		t.Errorf("Expected 'account is closed' error, got %s", err.Error())
	}
}

func TestDeposit_Success(t *testing.T) {
	// Setup account with balance 100
	acc := &models.BankingAccount{
		ID:       "acc-1",
		UserID:   "user-1",
		Balance:  100,
		Status:   "ACTIVE",
	}
	repo := &mockBankingRepo{accounts: map[string]*models.BankingAccount{"acc-1": acc}}
	svc := NewBankingService(repo)

	ctx := context.Background()
	err := svc.Deposit(ctx, "acc-1", 50)
	if err != nil {
		t.Fatalf("Deposit failed: %v", err)
	}

	if acc.Balance != 150 {
		t.Errorf("Expected balance 150, got %f", acc.Balance)
	}
	if len(repo.transactions) != 1 {
		t.Errorf("Expected 1 transaction, got %d", len(repo.transactions))
	}
}

func TestDeposit_InvalidAmount(t *testing.T) {
	repo := &mockBankingRepo{accounts: make(map[string]*models.BankingAccount)}
	svc := NewBankingService(repo)
	ctx := context.Background()

	// Zero amount
	err := svc.Deposit(ctx, "acc-1", 0)
	if err == nil {
		t.Fatal("Expected error for zero amount")
	}
	if err.Error() != "amount must be positive" {
		t.Errorf("Expected 'amount must be positive', got %v", err)
	}

	// Negative amount
	err = svc.Deposit(ctx, "acc-1", -10)
	if err == nil {
		t.Fatal("Expected error for negative amount")
	}
}

func TestWithdraw_SufficientBalance(t *testing.T) {
	acc := &models.BankingAccount{
		ID:         "acc-1",
		UserID:     "user-1",
		Balance:   100,
		AccountType: "CHECKING",
		Status:     "ACTIVE",
	}
	repo := &mockBankingRepo{accounts: map[string]*models.BankingAccount{"acc-1": acc}}
	svc := NewBankingService(repo)

	ctx := context.Background()
	err := svc.Withdraw(ctx, "acc-1", 30)
	if err != nil {
		t.Fatalf("Withdraw failed: %v", err)
	}

	if acc.Balance != 70 {
		t.Errorf("Expected balance 70, got %f", acc.Balance)
	}
}

func TestWithdraw_InsufficientBalance_ShouldFail(t *testing.T) {
	acc := &models.BankingAccount{
		ID:         "acc-1",
		UserID:     "user-1",
		Balance:   100,
		AccountType: "SAVINGS", // no overdraft
		Status:     "ACTIVE",
	}
	repo := &mockBankingRepo{accounts: map[string]*models.BankingAccount{"acc-1": acc}}
	svc := NewBankingService(repo)

	ctx := context.Background()
	err := svc.Withdraw(ctx, "acc-1", 150) // exceeds balance
	if err == nil {
		t.Fatal("Expected error for insufficient balance")
	}
	if acc.Balance != 100 {
		t.Errorf("Balance should be unchanged, got %f", acc.Balance)
	}
}

func TestWithdraw_OverdraftLimit_CheckingAccount(t *testing.T) {
	acc := &models.BankingAccount{
		ID:         "acc-1",
		UserID:     "user-1",
		Balance:   -50, // already negative
		AccountType: "CHECKING",
		Status:     "ACTIVE",
	}
	repo := &mockBankingRepo{accounts: map[string]*models.BankingAccount{"acc-1": acc}}
	svc := NewBankingService(repo)

	ctx := context.Background()
	err := svc.Withdraw(ctx, "acc-1", 400) // -50 - 400 = -450 > -500 limit?
	// Overdraft limit -500, so -450 is allowed, balance becomes -450
	if err != nil {
		t.Fatalf("Withdraw failed: %v", err)
	}
	if acc.Balance != -450 {
		t.Errorf("Expected balance -450, got %f", acc.Balance)
	}
}

func TestTransfer_Success(t *testing.T) {
	from := &models.BankingAccount{ID: "acc-1", UserID: "user-1", Balance: 100, AccountType: "CHECKING", Status: "ACTIVE"}
	to := &models.BankingAccount{ID: "acc-2", UserID: "user-2", Balance: 50, AccountType: "CHECKING", Status: "ACTIVE"}
	repo := &mockBankingRepo{accounts: map[string]*models.BankingAccount{
		"acc-1": from,
		"acc-2": to,
	}}
	svc := NewBankingService(repo)

	ctx := context.Background()
	err := svc.Transfer(ctx, "acc-1", "acc-2", 30)
	if err != nil {
		t.Fatalf("Transfer failed: %v", err)
	}

	if from.Balance != 70 {
		t.Errorf("From account balance: expected 70, got %f", from.Balance)
	}
	if to.Balance != 80 {
		t.Errorf("To account balance: expected 80, got %f", to.Balance)
	}
	if len(repo.transactions) != 2 {
		t.Errorf("Expected 2 transactions, got %d", len(repo.transactions))
	}
}

func TestTransfer_SameAccount(t *testing.T) {
	acc := &models.BankingAccount{
		ID: "acc-1", UserID: "user-1", Balance: 100, AccountType: "CHECKING", Status: "ACTIVE",
	}
	repo := &mockBankingRepo{accounts: map[string]*models.BankingAccount{"acc-1": acc}}
	svc := NewBankingService(repo)

	ctx := context.Background()
	err := svc.Transfer(ctx, "acc-1", "acc-1", 50) // same account
	if err == nil {
		t.Fatal("Expected error for same account")
	}
	if !strings.Contains(err.Error(), "cannot transfer to same account") {
		t.Errorf("Expected same account error, got %v", err)
	}
}

func TestTransfer_InsufficientFunds_Savings(t *testing.T) {
	acc := &models.BankingAccount{
		ID: "acc-1", UserID: "user-1", Balance: 50, AccountType: "SAVINGS", Status: "ACTIVE",
	}
	repo := &mockBankingRepo{accounts: map[string]*models.BankingAccount{"acc-1": acc}}
	svc := NewBankingService(repo)

	ctx := context.Background()
	err := svc.Transfer(ctx, "acc-1", "acc-2", 100) // exceeds balance
	if err == nil {
		t.Fatal("Expected insufficient funds error")
	}
	if acc.Balance != 50 {
		t.Errorf("Balance should unchanged, got %f", acc.Balance)
	}
}

func TestTransfer_OverdraftExceeded_Checking(t *testing.T) {
	acc := &models.BankingAccount{
		ID: "acc-1", UserID: "user-1", Balance: -400, AccountType: "CHECKING", Status: "ACTIVE",
	}
	repo := &mockBankingRepo{accounts: map[string]*models.BankingAccount{"acc-1": acc}}
	svc := NewBankingService(repo)

	ctx := context.Background()
	// Overdraft limit for CHECKING is -500. Current -400, trying to withdraw 200 would make -600 < -500 -> fail
	err := svc.Transfer(ctx, "acc-1", "acc-2", 200)
	if err == nil {
		t.Fatal("Expected overdraft exceeded error")
	}
}

func TestGetAccountByNumber_Success(t *testing.T) {
	repo := &mockBankingRepo{accounts: map[string]*models.BankingAccount{
		"acc-1": {ID: "acc-1", AccountNumber: "123456", UserID: "user-1", Balance: 100, Status: "ACTIVE"},
	}}
	svc := NewBankingService(repo)
	ctx := context.Background()

	acc, err := svc.GetAccountByNumber(ctx, "123456")
	if err != nil {
		t.Fatalf("GetAccountByNumber failed: %v", err)
	}
	if acc.AccountNumber != "123456" {
		t.Errorf("Expected account number 123456, got %s", acc.AccountNumber)
	}
}

func TestGetAccountByNumber_NotFound(t *testing.T) {
	repo := &mockBankingRepo{accounts: map[string]*models.BankingAccount{
		"acc-1": {ID: "acc-1", AccountNumber: "123456", Status: "ACTIVE"},
	}}
	svc := NewBankingService(repo)
	ctx := context.Background()

	_, err := svc.GetAccountByNumber(ctx, "999999")
	if err == nil {
		t.Fatal("Expected error, got nil")
	}
}

func TestGetAccountsByUser_Success(t *testing.T) {
	repo := &mockBankingRepo{accounts: map[string]*models.BankingAccount{
		"acc-1": {ID: "acc-1", UserID: "user-1", Balance: 100, Status: "ACTIVE"},
		"acc-2": {ID: "acc-2", UserID: "user-1", Balance: 200, Status: "ACTIVE"},
		"acc-3": {ID: "acc-3", UserID: "user-2", Balance: 300, Status: "ACTIVE"},
	}}
	svc := NewBankingService(repo)
	ctx := context.Background()

	accounts, err := svc.GetAccountsByUser(ctx, "user-1")
	if err != nil {
		t.Fatalf("GetAccountsByUser failed: %v", err)
	}
	if len(accounts) != 2 {
		t.Errorf("Expected 2 accounts, got %d", len(accounts))
	}
}

func TestGetAccountsByUser_WithClosed(t *testing.T) {
	repo := &mockBankingRepo{accounts: map[string]*models.BankingAccount{
		"acc-1": {ID: "acc-1", UserID: "user-1", Balance: 100, Status: "ACTIVE"},
		"acc-2": {ID: "acc-2", UserID: "user-1", Balance: 200, Status: "CLOSED"},
	}}
	svc := NewBankingService(repo)
	ctx := context.Background()

	accounts, err := svc.GetAccountsByUser(ctx, "user-1")
	if err != nil {
		t.Fatalf("GetAccountsByUser failed: %v", err)
	}
	if len(accounts) != 1 {
		t.Errorf("Expected 1 active account, got %d", len(accounts))
	}
	if accounts[0].ID != "acc-1" {
		t.Errorf("Expected active account acc-1, got %s", accounts[0].ID)
	}
}

func TestWithdraw_InvalidAmount(t *testing.T) {
	acc := &models.BankingAccount{
		ID:         "acc-1",
		UserID:     "user-1",
		Balance:    100,
		AccountType: "CHECKING",
		Status:     "ACTIVE",
	}
	repo := &mockBankingRepo{accounts: map[string]*models.BankingAccount{"acc-1": acc}}
	svc := NewBankingService(repo)

	ctx := context.Background()
	err := svc.Withdraw(ctx, "acc-1", -10)
	if err == nil {
		t.Fatal("Expected error for negative amount")
	}
	if !strings.Contains(err.Error(), "positive") {
		t.Errorf("Expected 'amount must be positive' error, got %s", err.Error())
	}
}

func TestTransfer_InvalidAmount(t *testing.T) {
	from := &models.BankingAccount{ID: "acc-1", UserID: "user-1", Balance: 100, AccountType: "CHECKING", Status: "ACTIVE"}
	to := &models.BankingAccount{ID: "acc-2", UserID: "user-2", Balance: 50, AccountType: "CHECKING", Status: "ACTIVE"}
	repo := &mockBankingRepo{accounts: map[string]*models.BankingAccount{
		"acc-1": from,
		"acc-2": to,
	}}
	svc := NewBankingService(repo)
	ctx := context.Background()

	err := svc.Transfer(ctx, "acc-1", "acc-2", -30)
	if err == nil {
		t.Fatal("Expected error for negative amount")
	}
	if !strings.Contains(err.Error(), "positive") {
		t.Errorf("Expected 'amount must be positive' error, got %s", err.Error())
	}
}

func TestTransfer_GetAccountError_From(t *testing.T) {
	// to account exists
	to := &models.BankingAccount{ID: "acc-2", UserID: "user-2", Balance: 50, AccountType: "CHECKING", Status: "ACTIVE"}
	repo := &mockBankingRepo{accounts: map[string]*models.BankingAccount{
		"acc-2": to,
	}}
	svc := NewBankingService(repo)
	ctx := context.Background()

	err := svc.Transfer(ctx, "non-existent-from", "acc-2", 30)
	if err == nil {
		t.Fatal("Expected error for non-existent from account")
	}
}

func TestTransfer_GetAccountError_To(t *testing.T) {
	from := &models.BankingAccount{ID: "acc-1", UserID: "user-1", Balance: 100, AccountType: "CHECKING", Status: "ACTIVE"}
	repo := &mockBankingRepo{accounts: map[string]*models.BankingAccount{
		"acc-1": from,
	}}
	svc := NewBankingService(repo)
	ctx := context.Background()

	err := svc.Transfer(ctx, "acc-1", "non-existent-to", 30)
	if err == nil {
		t.Fatal("Expected error for non-existent to account")
	}
}
