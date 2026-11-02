package handler

import (
	"bytes"
	"context"
	"encoding/json"
	"errors"
	"net/http"
	"net/http/httptest"
	"strings"
	"testing"
	"time"

	bankingmodels "community.local/modules/Banking/internal/models"

	"github.com/gin-gonic/gin"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/mock"
	"github.com/stretchr/testify/require"
)

// MockBankingServiceEvo mocks BankingService
type MockBankingServiceEvo struct {
	mock.Mock
}

func (m *MockBankingServiceEvo) CreateAccount(ctx context.Context, userID, accountType, currency string) (*bankingmodels.Account, error) {
	args := m.Called(ctx, userID, accountType, currency)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).(*bankingmodels.Account), args.Error(1)
}

func (m *MockBankingServiceEvo) GetAccount(ctx context.Context, id int64) (*bankingmodels.Account, error) {
	args := m.Called(ctx, id)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).(*bankingmodels.Account), args.Error(1)
}

func (m *MockBankingServiceEvo) GetAccountByNumber(ctx context.Context, accountNumber string) (*bankingmodels.Account, error) {
	args := m.Called(ctx, accountNumber)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).(*bankingmodels.Account), args.Error(1)
}

func (m *MockBankingServiceEvo) GetUserAccounts(ctx context.Context, userID string) ([]*bankingmodels.Account, error) {
	args := m.Called(ctx, userID)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).([]*bankingmodels.Account), args.Error(1)
}

func (m *MockBankingServiceEvo) GetBalance(ctx context.Context, accountID int64) (float64, error) {
	args := m.Called(ctx, accountID)
	if args.Error(1) != nil {
		return 0, args.Error(1)
	}
	return args.Get(0).(float64), args.Error(1)
}

func (m *MockBankingServiceEvo) GetTransactions(ctx context.Context, accountID int64, limit, offset int) ([]*bankingmodels.Transaction, error) {
	args := m.Called(ctx, accountID, limit, offset)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).([]*bankingmodels.Transaction), args.Error(1)
}

func (m *MockBankingServiceEvo) Deposit(ctx context.Context, accountID int64, amount float64, description string) (*bankingmodels.Transaction, error) {
	args := m.Called(ctx, accountID, amount, description)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).(*bankingmodels.Transaction), args.Error(1)
}

func (m *MockBankingServiceEvo) Withdraw(ctx context.Context, accountID int64, amount float64, description string) (*bankingmodels.Transaction, error) {
	args := m.Called(ctx, accountID, amount, description)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).(*bankingmodels.Transaction), args.Error(1)
}

func (m *MockBankingServiceEvo) Transfer(ctx context.Context, req *bankingmodels.TransferRequest) (*bankingmodels.Transaction, error) {
	args := m.Called(ctx, req)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).(*bankingmodels.Transaction), args.Error(1)
}

// authMiddlewareEvo sets user_id from Test-User-ID header
func authMiddlewareEvo() gin.HandlerFunc {
	return func(c *gin.Context) {
		if uid := c.GetHeader("Test-User-ID"); uid != "" {
			c.Set("user_id", uid)
			if roles := c.GetHeader("Test-User-Roles"); roles != "" {
				c.Set("user_roles", strings.Split(roles, ","))
			}
		}
	}
}

func setupRouterEvo(handler *BankingHandler) *gin.Engine {
	r := gin.New()
	r.Use(authMiddlewareEvo())
	// Register routes at root
	r.POST("/accounts", handler.CreateAccount)
	r.GET("/accounts/:id", handler.GetAccount)
	r.GET("/accounts/number/:number", handler.GetAccountByNumber)
	r.GET("/accounts/user/:user_id", handler.GetUserAccounts)
	r.GET("/accounts/:id/balance", handler.GetBalance)
	r.GET("/accounts/:id/transactions", handler.GetTransactions)
	r.POST("/deposit", handler.Deposit)
	r.POST("/withdraw", handler.Withdraw)
	r.POST("/transfer", handler.Transfer)
	return r
}

// ==================== Edge Case Tests ====================

// TestGetUserAccounts_AdminAllowed tests that a user with ROLE_ADMIN can view any user's accounts
func TestGetUserAccounts_AdminAllowed(t *testing.T) {
	svc := &MockBankingServiceEvo{}
	handler := NewBankingHandler(svc)
	r := setupRouterEvo(handler)

	accounts := []*bankingmodels.Account{{ID: 1, UserID: "otheruser"}}
	svc.On("GetUserAccounts", mock.Anything, "otheruser").Return(accounts, nil)

	req := httptest.NewRequest(http.MethodGet, "/accounts/user/otheruser", nil)
	req.Header.Set("Test-User-ID", "admin")
	req.Header.Set("Test-User-Roles", "ROLE_ADMIN")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
}

// TestGetBalance_LargeAmount ensures very large balances are handled correctly
func TestGetBalance_LargeAmount(t *testing.T) {
	svc := &MockBankingServiceEvo{}
	handler := NewBankingHandler(svc)
	r := setupRouterEvo(handler)

	account := &bankingmodels.Account{ID: 1, UserID: "user123"}
	svc.On("GetAccount", mock.Anything, int64(1)).Return(account, nil)
	svc.On("GetBalance", mock.Anything, int64(1)).Return(1e15, nil)

	req := httptest.NewRequest(http.MethodGet, "/accounts/1/balance", nil)
	req.Header.Set("Test-User-ID", "user123")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
	var resp map[string]interface{}
	require.NoError(t, json.Unmarshal(w.Body.Bytes(), &resp))
	assert.Equal(t, 1e15, resp["balance"])
}

// TestDeposit_LargeAmount tests depositing an extremely large amount
func TestDeposit_LargeAmount(t *testing.T) {
	svc := &MockBankingServiceEvo{}
	handler := NewBankingHandler(svc)
	r := setupRouterEvo(handler)

	account := &bankingmodels.Account{ID: 1, UserID: "user123"}
	svc.On("GetAccount", mock.Anything, int64(1)).Return(account, nil)

	now := time.Now()
	txn := &bankingmodels.Transaction{
		ID:         1,
		AccountID:  1,
		Amount:      1e9,
		Type:        "deposit",
		Description: "max deposit",
		CreatedAt:   now,
	}
	svc.On("Deposit", mock.Anything, int64(1), 1e9, "max deposit").Return(txn, nil)

	body := map[string]interface{}{
		"account_id": 1,
		"amount":     1e9,
		"description": "max deposit",
	}
	jsonBody, _ := json.Marshal(body)

	req := httptest.NewRequest(http.MethodPost, "/deposit", bytes.NewReader(jsonBody))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Test-User-ID", "user123")
	req.Header.Set("Idempotency-Key", "key-dep-max")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
}

// TestWithdraw_AllBalance tests withdrawing the entire balance
func TestWithdraw_AllBalance(t *testing.T) {
	svc := &MockBankingServiceEvo{}
	handler := NewBankingHandler(svc)
	r := setupRouterEvo(handler)

	account := &bankingmodels.Account{ID: 1, UserID: "user123"}
	svc.On("GetAccount", mock.Anything, int64(1)).Return(account, nil)

	now := time.Now()
	txn := &bankingmodels.Transaction{
		ID:         1,
		AccountID:  1,
		Amount:     1000,
		Type:       "withdraw",
		Description: "withdraw all",
		CreatedAt:  now,
	}
	svc.On("Withdraw", mock.Anything, int64(1), 1000.0, "withdraw all").Return(txn, nil)

	body := map[string]interface{}{
		"account_id": 1,
		"amount":     1000,
		"description": "withdraw all",
	}
	jsonBody, _ := json.Marshal(body)

	req := httptest.NewRequest(http.MethodPost, "/withdraw", bytes.NewReader(jsonBody))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Test-User-ID", "user123")
	req.Header.Set("Idempotency-Key", "key-wdr-all")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
}

// TestTransfer_ServiceError tests transfer when the service returns an error
func TestTransfer_ServiceError(t *testing.T) {
	svc := &MockBankingServiceEvo{}
	handler := NewBankingHandler(svc)
	r := setupRouterEvo(handler)

	fromAcc := &bankingmodels.Account{ID: 1, UserID: "user123", AccountNumber: "ACC001"}
	svc.On("GetAccountByNumber", mock.Anything, "ACC001").Return(fromAcc, nil)

	svc.On("Transfer", mock.Anything, &bankingmodels.TransferRequest{
		FromAccount: "ACC001",
		ToAccount:   "ACC002",
		Amount:      200,
	}).Return(nil, errors.New("transfer failed")).Once()

	body := map[string]interface{}{
		"from_account": "ACC001",
		"to_account":   "ACC002",
		"amount":       200,
	}
	jsonBody, _ := json.Marshal(body)

	req := httptest.NewRequest(http.MethodPost, "/transfer", bytes.NewReader(jsonBody))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Test-User-ID", "user123")
	req.Header.Set("Idempotency-Key", "key-456")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusBadRequest, w.Code)
}
