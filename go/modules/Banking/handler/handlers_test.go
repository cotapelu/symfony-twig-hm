package handler

import (
	"bytes"
	"context"
	"encoding/json"
	"errors"
	"net/http"
	"net/http/httptest"
	"testing"
	"time"

	bm "community.local/modules/Banking/internal/models"

	"github.com/gin-gonic/gin"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/mock"
)

// MockBankingService mocks the BankingService interface
type MockBankingService struct {
	mock.Mock
}

func (m *MockBankingService) CreateAccount(ctx context.Context, userID, accountType, currency string) (*bm.Account, error) {
	args := m.Called(ctx, userID, accountType, currency)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).(*bm.Account), args.Error(1)
}

func (m *MockBankingService) GetAccount(ctx context.Context, id int64) (*bm.Account, error) {
	args := m.Called(ctx, id)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).(*bm.Account), args.Error(1)
}

func (m *MockBankingService) GetAccountByNumber(ctx context.Context, accountNumber string) (*bm.Account, error) {
	args := m.Called(ctx, accountNumber)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).(*bm.Account), args.Error(1)
}

func (m *MockBankingService) GetUserAccounts(ctx context.Context, userID string) ([]*bm.Account, error) {
	args := m.Called(ctx, userID)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).([]*bm.Account), args.Error(1)
}

func (m *MockBankingService) GetBalance(ctx context.Context, accountID int64) (float64, error) {
	args := m.Called(ctx, accountID)
	if args.Get(0) == nil {
		return 0, args.Error(1)
	}
	return args.Get(0).(float64), args.Error(1)
}

func (m *MockBankingService) GetTransactions(ctx context.Context, accountID int64, limit, offset int) ([]*bm.Transaction, error) {
	args := m.Called(ctx, accountID, limit, offset)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).([]*bm.Transaction), args.Error(1)
}

func (m *MockBankingService) Deposit(ctx context.Context, accountID int64, amount float64, description string) (*bm.Transaction, error) {
	args := m.Called(ctx, accountID, amount, description)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).(*bm.Transaction), args.Error(1)
}

func (m *MockBankingService) Withdraw(ctx context.Context, accountID int64, amount float64, description string) (*bm.Transaction, error) {
	args := m.Called(ctx, accountID, amount, description)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).(*bm.Transaction), args.Error(1)
}

func (m *MockBankingService) Transfer(ctx context.Context, req *bm.TransferRequest) (*bm.Transaction, error) {
	args := m.Called(ctx, req)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).(*bm.Transaction), args.Error(1)
}

func TestNewBankingHandler(t *testing.T) {
	svc := &MockBankingService{}
	handler := NewBankingHandler(svc)
	if handler == nil {
		t.Fatal("NewBankingHandler returned nil")
	}
}

// Test for RegisterRoutes
func TestRegisterRoutes(t *testing.T) {
	gin.SetMode(gin.TestMode)
	svc := &MockBankingService{}
	handler := NewBankingHandler(svc)

	router := gin.New()
	handler.RegisterRoutes(router)

	// Verify routes are registered by checking the routes
	routes := router.Routes()
	found := false
	for _, r := range routes {
		if r.Path == "/api/banking/accounts" && r.Method == "POST" {
			found = true
			break
		}
	}
	if !found {
		t.Fatal("RegisterRoutes did not register expected routes")
	}
}

// authTestMiddleware sets user_id in context for tests if Test-User-ID header is present
func authTestMiddleware() gin.HandlerFunc {
	return func(c *gin.Context) {
		if uid := c.GetHeader("Test-User-ID"); uid != "" {
			c.Set("user_id", uid)
		}
	}
}

func TestCreateAccount(t *testing.T) {
	gin.SetMode(gin.TestMode)
	svc := &MockBankingService{}
	handler := NewBankingHandler(svc)

	router := gin.New()
	router.Use(authTestMiddleware())
	router.POST("/accounts", handler.CreateAccount)

	now := time.Now()
	account := &bm.Account{
		ID:            1,
		UserID:        "user123",
		AccountNumber: "ACC001",
		AccountType:   "savings",
		Balance:       0,
		Currency:      "USD",
		Status:        "active",
		CreatedAt:     now,
		UpdatedAt:     now,
	}

	// Valid request
	svc.On("CreateAccount", mock.Anything, "user123", "savings", "USD").Return(account, nil)
	body := map[string]string{
		"user_id":      "user123",
		"account_type": "savings",
		"currency":     "USD",
	}
	jsonBody, _ := json.Marshal(body)

	req := httptest.NewRequest("POST", "/accounts", bytes.NewReader(jsonBody))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Test-User-ID", "user123")
	rec := httptest.NewRecorder()
	router.ServeHTTP(rec, req)

	assert.Equal(t, http.StatusCreated, rec.Code)
	var resp map[string]interface{}
	json.Unmarshal(rec.Body.Bytes(), &resp)
	assert.Contains(t, resp, "account")

	// Test binding error - no mock needed, should fail before service call
	badReq := bytes.NewReader([]byte(`{"invalid": "json"}`))
	req2 := httptest.NewRequest("POST", "/accounts", badReq)
	req2.Header.Set("Content-Type", "application/json")
	req2.Header.Set("Test-User-ID", "user123")
	rec2 := httptest.NewRecorder()
	router.ServeHTTP(rec2, req2)
	assert.Equal(t, http.StatusBadRequest, rec2.Code)
}

func TestGetAccount(t *testing.T) {
	gin.SetMode(gin.TestMode)
	svc := &MockBankingService{}
	handler := NewBankingHandler(svc)

	router := gin.New()
	router.Use(authTestMiddleware())
	router.GET("/accounts/:id", handler.GetAccount)

	now := time.Now()
	account := &bm.Account{
		ID:            1,
		UserID:        "user123",
		AccountNumber: "ACC001",
		AccountType:   "savings",
		Balance:       1000,
		Currency:      "USD",
		Status:        "active",
		CreatedAt:     now,
		UpdatedAt:     now,
	}

	// Successful retrieval: two GetAccount calls (check + fetch)
	svc.On("GetAccount", mock.Anything, int64(1)).Return(account, nil).Twice()
	req := httptest.NewRequest("GET", "/accounts/1", nil)
	req.Header.Set("Test-User-ID", "user123")
	rec := httptest.NewRecorder()
	router.ServeHTTP(rec, req)
	assert.Equal(t, http.StatusOK, rec.Code)

	// Account not found (ownership check fails)
	svc.On("GetAccount", mock.Anything, int64(999)).Return(nil, errors.New("not found")).Once()
	req2 := httptest.NewRequest("GET", "/accounts/999", nil)
	req2.Header.Set("Test-User-ID", "user123")
	rec2 := httptest.NewRecorder()
	router.ServeHTTP(rec2, req2)
	assert.Equal(t, http.StatusInternalServerError, rec2.Code)

	// Unauthorized (no user_id)
	req3 := httptest.NewRequest("GET", "/accounts/1", nil)
	rec3 := httptest.NewRecorder()
	router.ServeHTTP(rec3, req3)
	assert.Equal(t, http.StatusUnauthorized, rec3.Code)
}

func TestGetAccountByNumber(t *testing.T) {
	gin.SetMode(gin.TestMode)
	svc := &MockBankingService{}
	handler := NewBankingHandler(svc)

	router := gin.New()
	router.Use(authTestMiddleware())
	router.GET("/accounts/number/:number", handler.GetAccountByNumber)

	now := time.Now()
	account := &bm.Account{
		ID:            1,
		UserID:        "user123",
		AccountNumber: "ACC001",
		AccountType:   "savings",
		Balance:       1000,
		Currency:      "USD",
		Status:        "active",
		CreatedAt:     now,
		UpdatedAt:     now,
	}

	svc.On("GetAccountByNumber", mock.Anything, "ACC001").Return(account, nil)
	req := httptest.NewRequest("GET", "/accounts/number/ACC001", nil)
	req.Header.Set("Test-User-ID", "user123")
	rec := httptest.NewRecorder()
	router.ServeHTTP(rec, req)
	assert.Equal(t, http.StatusOK, rec.Code)

	// Not found
	svc.On("GetAccountByNumber", mock.Anything, "UNKNOWN").Return(nil, errors.New("not found"))
	req2 := httptest.NewRequest("GET", "/accounts/number/UNKNOWN", nil)
	req2.Header.Set("Test-User-ID", "user123")
	rec2 := httptest.NewRecorder()
	router.ServeHTTP(rec2, req2)
	assert.Equal(t, http.StatusNotFound, rec2.Code)

	// Forbidden - account exists but belongs to another user
	otherAccount := &bm.Account{
		ID:            2,
		UserID:        "other_user",
		AccountNumber: "ACC002",
		AccountType:   "savings",
		Balance:       500,
		Currency:      "USD",
		Status:        "active",
		CreatedAt:     now,
		UpdatedAt:     now,
	}
	svc.On("GetAccountByNumber", mock.Anything, "ACC002").Return(otherAccount, nil)
	req3 := httptest.NewRequest("GET", "/accounts/number/ACC002", nil)
	req3.Header.Set("Test-User-ID", "user123")
	rec3 := httptest.NewRecorder()
	router.ServeHTTP(rec3, req3)
	assert.Equal(t, http.StatusForbidden, rec3.Code)
}

func TestGetUserAccounts(t *testing.T) {
	gin.SetMode(gin.TestMode)
	svc := &MockBankingService{}
	handler := NewBankingHandler(svc)

	router := gin.New()
	router.Use(authTestMiddleware())
	router.GET("/accounts/user/:user_id", handler.GetUserAccounts)

	now := time.Now()
	accounts := []*bm.Account{
		{ID: 1, UserID: "user123", AccountNumber: "ACC001", AccountType: "savings", Balance: 1000, Currency: "USD", Status: "active", CreatedAt: now, UpdatedAt: now},
		{ID: 2, UserID: "user123", AccountNumber: "ACC002", AccountType: "checking", Balance: 2000, Currency: "USD", Status: "active", CreatedAt: now, UpdatedAt: now},
	}

	svc.On("GetUserAccounts", mock.Anything, "user123").Return(accounts, nil)
	req := httptest.NewRequest("GET", "/accounts/user/user123", nil)
	req.Header.Set("Test-User-ID", "user123")
	rec := httptest.NewRecorder()
	router.ServeHTTP(rec, req)
	assert.Equal(t, http.StatusOK, rec.Code)
	var resp map[string]interface{}
	json.Unmarshal(rec.Body.Bytes(), &resp)
	assert.Contains(t, resp, "accounts")
}

func TestGetBalance(t *testing.T) {
	gin.SetMode(gin.TestMode)
	svc := &MockBankingService{}
	handler := NewBankingHandler(svc)

	router := gin.New()
	router.Use(authTestMiddleware())
	router.GET("/accounts/:id/balance", handler.GetBalance)

	now := time.Now()
	account := &bm.Account{
		ID:            1,
		UserID:        "user123",
		AccountNumber: "ACC001",
		AccountType:   "savings",
		Balance:       2500.75,
		Currency:      "USD",
		Status:        "active",
		CreatedAt:     now,
		UpdatedAt:     now,
	}

	// Success: GetAccount (ownership check) once, then GetBalance
	svc.On("GetAccount", mock.Anything, int64(1)).Return(account, nil).Once()
	svc.On("GetBalance", mock.Anything, int64(1)).Return(2500.75, nil).Once()

	req := httptest.NewRequest("GET", "/accounts/1/balance", nil)
	req.Header.Set("Test-User-ID", "user123")
	rec := httptest.NewRecorder()
	router.ServeHTTP(rec, req)
	assert.Equal(t, http.StatusOK, rec.Code)
	var resp map[string]interface{}
	json.Unmarshal(rec.Body.Bytes(), &resp)
	assert.Contains(t, resp, "balance")
	assert.InDelta(t, 2500.75, resp["balance"], 0.001)

	// Account not found (ownership check fails) -> 500
	svc.On("GetAccount", mock.Anything, int64(999)).Return(nil, errors.New("not found")).Once()
	req2 := httptest.NewRequest("GET", "/accounts/999/balance", nil)
	req2.Header.Set("Test-User-ID", "user123")
	rec2 := httptest.NewRecorder()
	router.ServeHTTP(rec2, req2)
	assert.Equal(t, http.StatusInternalServerError, rec2.Code)
}

func TestGetTransactions(t *testing.T) {
	gin.SetMode(gin.TestMode)
	svc := &MockBankingService{}
	handler := NewBankingHandler(svc)

	router := gin.New()
	router.Use(authTestMiddleware())
	router.GET("/accounts/:id/transactions", handler.GetTransactions)

	now := time.Now()
	account := &bm.Account{
		ID:            1,
		UserID:        "user123",
		AccountNumber: "ACC001",
		AccountType:   "savings",
		Balance:       1000,
		Currency:      "USD",
		Status:        "active",
		CreatedAt:     now,
		UpdatedAt:     now,
	}
	txns := []*bm.Transaction{
		{ID: 1, AccountID: 1, Type: "deposit", Amount: 100, BalanceAfter: 1100, Description: "deposit 1", Status: "completed", CreatedAt: now},
		{ID: 2, AccountID: 1, Type: "withdraw", Amount: 50, BalanceAfter: 1050, Description: "withdraw 1", Status: "completed", CreatedAt: now},
	}

	// Ownership check GetAccount + GetTransactions
	svc.On("GetAccount", mock.Anything, int64(1)).Return(account, nil).Once()
	svc.On("GetTransactions", mock.Anything, int64(1), 20, 0).Return(txns, nil).Once()

	req := httptest.NewRequest("GET", "/accounts/1/transactions?limit=20&offset=0", nil)
	req.Header.Set("Test-User-ID", "user123")
	rec := httptest.NewRecorder()
	router.ServeHTTP(rec, req)
	assert.Equal(t, http.StatusOK, rec.Code)
	var resp map[string]interface{}
	json.Unmarshal(rec.Body.Bytes(), &resp)
	assert.Contains(t, resp, "transactions")
}

func TestDeposit(t *testing.T) {
	gin.SetMode(gin.TestMode)
	svc := &MockBankingService{}
	handler := NewBankingHandler(svc)

	router := gin.New()
	router.Use(authTestMiddleware())
	router.POST("/deposit", handler.Deposit)

	now := time.Now()
	account := &bm.Account{
		ID:            1,
		UserID:        "user123",
		AccountNumber: "ACC001",
		AccountType:   "savings",
		Balance:       1000,
		Currency:      "USD",
		Status:        "active",
		CreatedAt:     now,
		UpdatedAt:     now,
	}
	txn := &bm.Transaction{
		ID:           1,
		AccountID:    1,
		Type:         "deposit",
		Amount:       500,
		BalanceAfter: 1500,
		Description:  "test deposit",
		Status:       "completed",
		CreatedAt:    now,
	}

	// Two GetAccount calls: one in checkAccountOwnership (handler), one in service Deposit
	svc.On("GetAccount", mock.Anything, int64(1)).Return(account, nil).Twice()
	svc.On("Deposit", mock.Anything, int64(1), 500.0, "test deposit").Return(txn, nil).Once()

	body := map[string]interface{}{
		"account_id":  1,
		"amount":      500,
		"description": "test deposit",
	}
	jsonBody, _ := json.Marshal(body)
	req := httptest.NewRequest("POST", "/deposit", bytes.NewReader(jsonBody))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Test-User-ID", "user123")
	req.Header.Set("Idempotency-Key", "test-key-1")
	rec := httptest.NewRecorder()
	router.ServeHTTP(rec, req)
	assert.Equal(t, http.StatusOK, rec.Code)

	// Invalid amount (negative) - validation catches before service
	badBody := map[string]interface{}{
		"account_id": 1,
		"amount":     -100,
	}
	badJson, _ := json.Marshal(badBody)
	req2 := httptest.NewRequest("POST", "/deposit", bytes.NewReader(badJson))
	req2.Header.Set("Content-Type", "application/json")
	req2.Header.Set("Test-User-ID", "user123")
	req2.Header.Set("Idempotency-Key", "test-key-2")
	rec2 := httptest.NewRecorder()
	router.ServeHTTP(rec2, req2)
	assert.Equal(t, http.StatusBadRequest, rec2.Code)

	// Missing idempotency key
	body2 := map[string]interface{}{
		"account_id": 1,
		"amount":     100,
	}
	jsonBody2, _ := json.Marshal(body2)
	req3 := httptest.NewRequest("POST", "/deposit", bytes.NewReader(jsonBody2))
	req3.Header.Set("Content-Type", "application/json")
	req3.Header.Set("Test-User-ID", "user123")
	rec3 := httptest.NewRecorder()
	router.ServeHTTP(rec3, req3)
	assert.Equal(t, http.StatusBadRequest, rec3.Code)
}

func TestWithdraw(t *testing.T) {
	gin.SetMode(gin.TestMode)
	svc := &MockBankingService{}
	handler := NewBankingHandler(svc)

	router := gin.New()
	router.Use(authTestMiddleware())
	router.POST("/withdraw", handler.Withdraw)

	now := time.Now()
	account := &bm.Account{
		ID:            1,
		UserID:        "user123",
		AccountNumber: "ACC001",
		AccountType:   "savings",
		Balance:       1000,
		Currency:      "USD",
		Status:        "active",
		CreatedAt:     now,
		UpdatedAt:     now,
	}
	txn := &bm.Transaction{
		ID:           2,
		AccountID:    1,
		Type:         "withdraw",
		Amount:       300,
		BalanceAfter: 700,
		Description:  "test withdraw",
		Status:       "completed",
		CreatedAt:    now,
	}

	// Two GetAccount calls: checkAccountOwnership (handler) + service Withdraw
	svc.On("GetAccount", mock.Anything, int64(1)).Return(account, nil).Twice()
	svc.On("Withdraw", mock.Anything, int64(1), 300.0, "test withdraw").Return(txn, nil).Once()

	body := map[string]interface{}{
		"account_id":  1,
		"amount":      300,
		"description": "test withdraw",
	}
	jsonBody, _ := json.Marshal(body)
	req := httptest.NewRequest("POST", "/withdraw", bytes.NewReader(jsonBody))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Test-User-ID", "user123")
	req.Header.Set("Idempotency-Key", "test-key-3")
	rec := httptest.NewRecorder()
	router.ServeHTTP(rec, req)
	assert.Equal(t, http.StatusOK, rec.Code)
}

func TestTransfer(t *testing.T) {
	gin.SetMode(gin.TestMode)
	svc := &MockBankingService{}
	handler := NewBankingHandler(svc)

	router := gin.New()
	router.Use(authTestMiddleware())
	router.POST("/transfer", handler.Transfer)

	now := time.Now()
	srcAccount := &bm.Account{
		ID:            1,
		UserID:        "user123",
		AccountNumber: "ACC001",
		AccountType:   "savings",
		Balance:       1000,
		Currency:      "USD",
		Status:        "active",
		CreatedAt:     now,
		UpdatedAt:     now,
	}
	dstAccount := &bm.Account{
		ID:            2,
		UserID:        "user123", // destination can be any user
		AccountNumber: "ACC002",
		AccountType:   "savings",
		Balance:       500,
		Currency:      "USD",
		Status:        "active",
		CreatedAt:     now,
		UpdatedAt:     now,
	}
	otherSrc := &bm.Account{
		ID:            3,
		UserID:        "other_user",
		AccountNumber: "ACC003",
		AccountType:   "savings",
		Balance:       500,
		Currency:      "USD",
		Status:        "active",
		CreatedAt:     now,
		UpdatedAt:     now,
	}
	txn := &bm.Transaction{
		ID:           3,
		AccountID:    1,
		Type:         "transfer",
		Amount:       200,
		BalanceAfter: 800,
		Description:  "test transfer",
		Status:       "completed",
		CreatedAt:    now,
	}

	// 1. Success case
	// Expectations: GetAccountByNumber called thrice (handler ownership, service from, service to), Transfer once
	svc.On("GetAccountByNumber", mock.Anything, "ACC001").Return(srcAccount, nil).Once() // handler
	svc.On("GetAccountByNumber", mock.Anything, "ACC001").Return(srcAccount, nil).Once() // service from
	svc.On("GetAccountByNumber", mock.Anything, "ACC002").Return(dstAccount, nil).Once() // service to
	svc.On("Transfer", mock.Anything, mock.Anything).Return(txn, nil).Once()
	fullBody := map[string]interface{}{
		"from_account": "ACC001",
		"to_account":   "ACC002",
		"amount":       200,
		"description":  "test transfer",
	}
	fullJSON, _ := json.Marshal(fullBody)
	req := httptest.NewRequest("POST", "/transfer", bytes.NewReader(fullJSON))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Test-User-ID", "user123")
	req.Header.Set("Idempotency-Key", "test-key-success")
	rec := httptest.NewRecorder()
	router.ServeHTTP(rec, req)
	assert.Equal(t, http.StatusOK, rec.Code)

	// 2. Validation error: missing to_account (Transfer service will fail because to account not found)
	invalidBody := map[string]interface{}{
		"from_account": "ACC001",
		"amount":       200,
	}
	invalidJSON, _ := json.Marshal(invalidBody)
	// Expect GetAccountByNumber for source ownership to succeed, then Transfer returns error
	svc.On("GetAccountByNumber", mock.Anything, "ACC001").Return(srcAccount, nil).Once()
	svc.On("Transfer", mock.Anything, mock.Anything).Return(nil, errors.New("account not found")).Once()
	req2 := httptest.NewRequest("POST", "/transfer", bytes.NewReader(invalidJSON))
	req2.Header.Set("Content-Type", "application/json")
	req2.Header.Set("Test-User-ID", "user123")
	req2.Header.Set("Idempotency-Key", "test-key-invalid")
	rec2 := httptest.NewRecorder()
	router.ServeHTTP(rec2, req2)
	assert.Equal(t, http.StatusBadRequest, rec2.Code)

	// 3. Source account not found
	notFoundBody := map[string]interface{}{
		"from_account": "NONEXISTENT",
		"to_account":   "ACC002",
		"amount":       100,
	}
	notFoundJSON, _ := json.Marshal(notFoundBody)
	svc.On("GetAccountByNumber", mock.Anything, "NONEXISTENT").Return(nil, errors.New("not found")).Once()
	req3 := httptest.NewRequest("POST", "/transfer", bytes.NewReader(notFoundJSON))
	req3.Header.Set("Content-Type", "application/json")
	req3.Header.Set("Test-User-ID", "user123")
	req3.Header.Set("Idempotency-Key", "test-key-notfound")
	rec3 := httptest.NewRecorder()
	router.ServeHTTP(rec3, req3)
	assert.Equal(t, http.StatusBadRequest, rec3.Code)

	// 4. Unauthorized (source account belongs to another user)
	authBody := map[string]interface{}{
		"from_account": "ACC003",
		"to_account":   "ACC004",
		"amount":       100,
	}
	authJSON, _ := json.Marshal(authBody)
	svc.On("GetAccountByNumber", mock.Anything, "ACC003").Return(otherSrc, nil).Once()
	req4 := httptest.NewRequest("POST", "/transfer", bytes.NewReader(authJSON))
	req4.Header.Set("Content-Type", "application/json")
	req4.Header.Set("Test-User-ID", "user123")
	req4.Header.Set("Idempotency-Key", "test-key-unauth")
	rec4 := httptest.NewRecorder()
	router.ServeHTTP(rec4, req4)
	assert.Equal(t, http.StatusForbidden, rec4.Code)
}
