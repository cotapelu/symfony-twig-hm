package handler

import (
	"bytes"
	"context"
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"strings"
	"testing"
	"time"

	walletmodels "community.local/modules/WalletService/internal/models"

	"github.com/gin-gonic/gin"
	"github.com/golang-jwt/jwt/v5"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/mock"
	"github.com/stretchr/testify/require"
)

// MockWalletService mocks WalletService
type MockWalletService struct {
	mock.Mock
}

func (m *MockWalletService) CreateWallet(ctx context.Context, userID, currency string) (*walletmodels.Wallet, error) {
	args := m.Called(ctx, userID, currency)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).(*walletmodels.Wallet), args.Error(1)
}

func (m *MockWalletService) GetWallet(ctx context.Context, userID string) (*walletmodels.Wallet, error) {
	args := m.Called(ctx, userID)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).(*walletmodels.Wallet), args.Error(1)
}

func (m *MockWalletService) GetBalance(ctx context.Context, userID string) (int64, error) {
	args := m.Called(ctx, userID)
	if args.Error(1) != nil {
		return 0, args.Error(1)
	}
	return args.Get(0).(int64), args.Error(1)
}

func (m *MockWalletService) GetTransactions(ctx context.Context, userID string, limit, offset int) ([]*walletmodels.WalletTransaction, error) {
	args := m.Called(ctx, userID, limit, offset)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).([]*walletmodels.WalletTransaction), args.Error(1)
}

func (m *MockWalletService) TopUp(ctx context.Context, req *walletmodels.TopUpRequest) (*walletmodels.WalletTransaction, error) {
	args := m.Called(ctx, req)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).(*walletmodels.WalletTransaction), args.Error(1)
}

func (m *MockWalletService) Withdraw(ctx context.Context, req *walletmodels.WithdrawalRequest) (*walletmodels.WalletTransaction, error) {
	args := m.Called(ctx, req)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).(*walletmodels.WalletTransaction), args.Error(1)
}

func (m *MockWalletService) Transfer(ctx context.Context, req *walletmodels.TransferRequest) (*walletmodels.WalletTransaction, error) {
	args := m.Called(ctx, req)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).(*walletmodels.WalletTransaction), args.Error(1)
}

func (m *MockWalletService) DeleteWallet(ctx context.Context, userID string) error {
	args := m.Called(ctx, userID)
	return args.Error(0)
}

func (m *MockWalletService) LockWallet(ctx context.Context, userID, reason string) error {
	args := m.Called(ctx, userID, reason)
	return args.Error(0)
}

func (m *MockWalletService) UnlockWallet(ctx context.Context, userID string) error {
	args := m.Called(ctx, userID)
	return args.Error(0)
}

func (m *MockWalletService) ExportTransactions(ctx context.Context, userID string, limit, offset int) ([]*walletmodels.WalletTransaction, error) {
	args := m.Called(ctx, userID, limit, offset)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).([]*walletmodels.WalletTransaction), args.Error(1)
}

// createTestToken creates a JWT token for testing
func createTestToken(userID string, roles []string) string {
	claims := jwt.MapClaims{
		"userId": userID,
		"roles":  roles,
	}
	token := jwt.NewWithClaims(jwt.SigningMethodHS256, claims)
	tokenString, _ := token.SignedString([]byte("dev-secret-change-in-production"))
	return tokenString
}

// authMiddlewareEvo creates a test middleware that sets up JWT authentication
func authMiddlewareEvo() gin.HandlerFunc {
	return func(c *gin.Context) {
		// Check for Test-User-ID header and create JWT token if present
		if uid := c.GetHeader("Test-User-ID"); uid != "" {
			roles := []string{}
			if roleHeader := c.GetHeader("Test-User-Roles"); roleHeader != "" {
				roles = strings.Split(roleHeader, ",")
			}
			token := createTestToken(uid, roles)
			c.Request.Header.Set("Authorization", "Bearer "+token)
		}
	}
}

func setupRouterEvo(handler *WalletHandler) *gin.Engine {
	r := gin.New()
	r.Use(authMiddlewareEvo())
	handler.RegisterRoutes(r)
	return r
}

// ==================== CreateWallet Tests ====================

func TestCreateWallet_Success(t *testing.T) {
	svc := &MockWalletService{}
	handler := NewWalletHandler(svc)
	r := setupRouterEvo(handler)

	wallet := &walletmodels.Wallet{
		UserID:   "user123",
		Currency: "USD",
		Balance:  0,
	}
	svc.On("CreateWallet", mock.Anything, "user123", "USD").Return(wallet, nil)

	body := map[string]string{
		"user_id":  "user123",
		"currency": "USD",
	}
	jsonBody, _ := json.Marshal(body)

	req := httptest.NewRequest(http.MethodPost, "/api/wallet/create", bytes.NewReader(jsonBody))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Test-User-ID", "user123")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusCreated, w.Code)
}

func TestCreateWallet_NoAuth(t *testing.T) {
	svc := &MockWalletService{}
	handler := NewWalletHandler(svc)
	r := setupRouterEvo(handler)

	body := map[string]string{
		"user_id":  "user123",
		"currency": "USD",
	}
	jsonBody, _ := json.Marshal(body)

	req := httptest.NewRequest(http.MethodPost, "/api/wallet/create", bytes.NewReader(jsonBody))
	req.Header.Set("Content-Type", "application/json")
	// no Test-User-ID
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusUnauthorized, w.Code)
}

func TestCreateWallet_InvalidUserID(t *testing.T) {
	svc := &MockWalletService{}
	handler := NewWalletHandler(svc)
	r := setupRouterEvo(handler)

	body := map[string]string{
		"user_id":  "", // invalid
		"currency": "USD",
	}
	jsonBody, _ := json.Marshal(body)

	req := httptest.NewRequest(http.MethodPost, "/api/wallet/create", bytes.NewReader(jsonBody))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Test-User-ID", "user123")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusBadRequest, w.Code)
}

func TestCreateWallet_UnauthorizedDifferentUser(t *testing.T) {
	svc := &MockWalletService{}
	handler := NewWalletHandler(svc)
	r := setupRouterEvo(handler)

	svc.On("CreateWallet", mock.Anything, "otheruser", "USD").Return(&walletmodels.Wallet{UserID: "otheruser"}, nil)

	body := map[string]string{
		"user_id":  "otheruser",
		"currency": "USD",
	}
	jsonBody, _ := json.Marshal(body)

	req := httptest.NewRequest(http.MethodPost, "/api/wallet/create", bytes.NewReader(jsonBody))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Test-User-ID", "user123")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	// Should fail due to auth user mismatch (non-admin)
	assert.Equal(t, http.StatusForbidden, w.Code)
}

// ==================== GetMyWallet, GetMyBalance, GetMyTransactions ====================

// These endpoints use the authenticated user's wallet. We'll test success.
func TestGetMyWallet_Success(t *testing.T) {
	svc := &MockWalletService{}
	handler := NewWalletHandler(svc)
	r := setupRouterEvo(handler)

	wallet := &walletmodels.Wallet{UserID: "user123", Balance: 1000, Currency: "USD"}
	svc.On("GetWallet", mock.Anything, "user123").Return(wallet, nil)

	req := httptest.NewRequest(http.MethodGet, "/api/wallet/me", nil)
	req.Header.Set("Test-User-ID", "user123")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
}

func TestGetMyWallet_NoAuth(t *testing.T) {
	svc := &MockWalletService{}
	handler := NewWalletHandler(svc)
	r := setupRouterEvo(handler)

	req := httptest.NewRequest(http.MethodGet, "/api/wallet/me", nil)
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusUnauthorized, w.Code)
}

func TestGetMyBalance_Success(t *testing.T) {
	svc := &MockWalletService{}
	handler := NewWalletHandler(svc)
	r := setupRouterEvo(handler)

	svc.On("GetBalance", mock.Anything, "user123").Return(int64(5000), nil)

	req := httptest.NewRequest(http.MethodGet, "/api/wallet/me/balance", nil)
	req.Header.Set("Test-User-ID", "user123")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
	var resp map[string]interface{}
	require.NoError(t, json.Unmarshal(w.Body.Bytes(), &resp))
	assert.InDelta(t, 5000, resp["balance"], 0.001)
}

func TestGetMyTransactions_Success(t *testing.T) {
	svc := &MockWalletService{}
	handler := NewWalletHandler(svc)
	r := setupRouterEvo(handler)

	now := time.Now()
	txns := []*walletmodels.WalletTransaction{
		{ID: 1, WalletID: 1, Amount: 100, Type: "topup", CreatedAt: now},
	}
	svc.On("GetTransactions", mock.Anything, "user123", 20, 0).Return(txns, nil)

	req := httptest.NewRequest(http.MethodGet, "/api/wallet/me/transactions", nil)
	req.Header.Set("Test-User-ID", "user123")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
}

// ==================== TopUp Tests ====================

func TestTopUp_Success(t *testing.T) {
	svc := &MockWalletService{}
	handler := NewWalletHandler(svc)
	r := setupRouterEvo(handler)

	now := time.Now()
	txn := &walletmodels.WalletTransaction{
		ID:       1,
		WalletID: 1,
		Amount:   200,
		Type:     "topup",
		CreatedAt: now,
	}
	reqObj := &walletmodels.TopUpRequest{
		UserID: "user123",
		Amount: 200,
		Method: "bank_transfer",
	}
	svc.On("TopUp", mock.Anything, reqObj).Return(txn, nil)

	body := map[string]interface{}{
		"user_id": "user123",
		"amount":  200,
	}
	jsonBody, _ := json.Marshal(body)

	req := httptest.NewRequest(http.MethodPost, "/api/wallet/topup", bytes.NewReader(jsonBody))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Test-User-ID", "user123")
	req.Header.Set("Idempotency-Key", "key-topup-1")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
}

func TestTopUp_NoAuth(t *testing.T) {
	svc := &MockWalletService{}
	handler := NewWalletHandler(svc)
	r := setupRouterEvo(handler)

	body := map[string]interface{}{
		"amount": 200,
	}
	jsonBody, _ := json.Marshal(body)

	req := httptest.NewRequest(http.MethodPost, "/api/wallet/topup", bytes.NewReader(jsonBody))
	req.Header.Set("Content-Type", "application/json")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusUnauthorized, w.Code)
}

func TestTopUp_InvalidAmount(t *testing.T) {
	svc := &MockWalletService{}
	handler := NewWalletHandler(svc)
	r := setupRouterEvo(handler)

	body := map[string]interface{}{
		"user_id": "user123",
		"amount": -100,
	}
	jsonBody, _ := json.Marshal(body)

	req := httptest.NewRequest(http.MethodPost, "/api/wallet/topup", bytes.NewReader(jsonBody))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Test-User-ID", "user123")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusBadRequest, w.Code)
}

// ==================== Withdraw Tests ====================

func TestWithdraw_Success(t *testing.T) {
	svc := &MockWalletService{}
	handler := NewWalletHandler(svc)
	r := setupRouterEvo(handler)

	now := time.Now()
	txn := &walletmodels.WalletTransaction{
		ID:       1,
		WalletID: 1,
		Amount:   100,
		Type:     "withdraw",
		CreatedAt: now,
	}
	reqObj := &walletmodels.WithdrawalRequest{
		UserID:    "user123",
		Amount:    100,
		BankCode:  "VCB",
		AccountNo: "1234567890",
		HolderName: "John Doe",
	}
	svc.On("Withdraw", mock.Anything, reqObj).Return(txn, nil)

	body := map[string]interface{}{
		"user_id":    "user123",
		"amount":     100,
		"bank_code":  "VCB",
		"account_no": "1234567890",
		"holder_name":"John Doe",
	}
	jsonBody, _ := json.Marshal(body)

	req := httptest.NewRequest(http.MethodPost, "/api/wallet/withdraw", bytes.NewReader(jsonBody))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Test-User-ID", "user123")
	req.Header.Set("Idempotency-Key", "key-wdr-1")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
}

func TestWithdraw_NoAuth(t *testing.T) {
	svc := &MockWalletService{}
	handler := NewWalletHandler(svc)
	r := setupRouterEvo(handler)

	body := map[string]interface{}{"amount": 100}
	jsonBody, _ := json.Marshal(body)

	req := httptest.NewRequest(http.MethodPost, "/api/wallet/withdraw", bytes.NewReader(jsonBody))
	req.Header.Set("Content-Type", "application/json")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusUnauthorized, w.Code)
}

// ==================== Transfer Tests ====================

func TestTransfer_Success(t *testing.T) {
	svc := &MockWalletService{}
	handler := NewWalletHandler(svc)
	r := setupRouterEvo(handler)

	now := time.Now()
	txn := &walletmodels.WalletTransaction{
		ID:        1,
		WalletID: 1,
		Amount:    200,
		Type:      "transfer",
		CreatedAt: now,
	}
	reqObj := &walletmodels.TransferRequest{
		FromUserID: "user123",
		ToUserID:   "user456",
		Amount:     200,
	}
	svc.On("Transfer", mock.Anything, reqObj).Return(txn, nil)

	body := map[string]interface{}{
		"from_user_id": "user123",
		"to_user_id":   "user456",
		"amount":       200,
	}
	jsonBody, _ := json.Marshal(body)

	req := httptest.NewRequest(http.MethodPost, "/api/wallet/transfer", bytes.NewReader(jsonBody))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Test-User-ID", "user123")
	req.Header.Set("Idempotency-Key", "key-xfer-1")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
}

func TestTransfer_NoAuth(t *testing.T) {
	svc := &MockWalletService{}
	handler := NewWalletHandler(svc)
	r := setupRouterEvo(handler)

	body := map[string]interface{}{
		"from_user_id": "user123",
		"to_user_id":   "user456",
		"amount":       200,
	}
	jsonBody, _ := json.Marshal(body)

	req := httptest.NewRequest(http.MethodPost, "/api/wallet/transfer", bytes.NewReader(jsonBody))
	req.Header.Set("Content-Type", "application/json")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusUnauthorized, w.Code)
}

// ==================== DeleteWallet Tests ====================

func TestDeleteMyWallet_Success(t *testing.T) {
	svc := &MockWalletService{}
	handler := NewWalletHandler(svc)
	r := setupRouterEvo(handler)

	svc.On("DeleteWallet", mock.Anything, "user123").Return(nil)

	req := httptest.NewRequest(http.MethodDelete, "/api/wallet/me", nil)
	req.Header.Set("Test-User-ID", "user123")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
}

func TestDeleteMyWallet_NoAuth(t *testing.T) {
	svc := &MockWalletService{}
	handler := NewWalletHandler(svc)
	r := setupRouterEvo(handler)

	req := httptest.NewRequest(http.MethodDelete, "/api/wallet/me", nil)
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusUnauthorized, w.Code)
}

// ==================== Lock/Unlock Wallet Tests ====================

func TestLockWallet_Success(t *testing.T) {
	svc := &MockWalletService{}
	handler := NewWalletHandler(svc)
	r := setupRouterEvo(handler)

	svc.On("LockWallet", mock.Anything, "user123", mock.Anything).Return(nil)

	req := httptest.NewRequest(http.MethodPatch, "/api/wallet/user123/lock", strings.NewReader(`{"reason":"test lock"}`))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Test-User-ID", "admin")
	req.Header.Set("Test-User-Roles", "admin")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
}

func TestUnlockWallet_Success(t *testing.T) {
	svc := &MockWalletService{}
	handler := NewWalletHandler(svc)
	r := setupRouterEvo(handler)

	svc.On("UnlockWallet", mock.Anything, "user123").Return(nil)

	req := httptest.NewRequest(http.MethodPatch, "/api/wallet/user123/unlock", nil)
	req.Header.Set("Test-User-ID", "admin")
	req.Header.Set("Test-User-Roles", "admin")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
}