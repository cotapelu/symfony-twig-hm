package handler

import (
	"bytes"
	"encoding/json"
	"fmt"
	"net/http"
	"net/http/httptest"
	"testing"

	walletmodels "community.local/modules/WalletService/internal/models"

	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/mock"
	"github.com/stretchr/testify/require"
)

// Additional tests to increase coverage for WalletHandler

func TestGetBalance_NotOwner_Forbidden(t *testing.T) {
	svc := &MockWalletService{}
	handler := NewWalletHandler(svc)
	r := setupRouterEvo(handler)

	// Request as alice for bob's balance -> should be 403
	req := httptest.NewRequest(http.MethodGet, "/api/wallet/bob/balance", nil)
	req.Header.Set("Test-User-ID", "alice")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusForbidden, w.Code)
}

func TestGetTransactions_NotOwner_Forbidden(t *testing.T) {
	svc := &MockWalletService{}
	handler := NewWalletHandler(svc)
	r := setupRouterEvo(handler)

	req := httptest.NewRequest(http.MethodGet, "/api/wallet/bob/transactions", nil)
	req.Header.Set("Test-User-ID", "alice")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusForbidden, w.Code)
}

func TestExportMyTransactions_Success(t *testing.T) {
	svc := &MockWalletService{}
	handler := NewWalletHandler(svc)
	r := setupRouterEvo(handler)

	txns := []*walletmodels.WalletTransaction{
		{ID: 1, WalletID: 1, Amount: 100, Type: "topup"},
	}
	svc.On("ExportTransactions", mock.Anything, "user123", 20, 0).Return(txns, nil)

	req := httptest.NewRequest(http.MethodGet, "/api/wallet/me/export", nil)
	req.Header.Set("Test-User-ID", "user123")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
	var resp map[string]interface{}
	require.NoError(t, json.Unmarshal(w.Body.Bytes(), &resp))
	assert.Contains(t, resp, "transactions")
	assert.Len(t, resp["transactions"].([]interface{}), 1)
}

func TestExportTransactions_NotOwner_Forbidden(t *testing.T) {
	svc := &MockWalletService{}
	handler := NewWalletHandler(svc)
	r := setupRouterEvo(handler)

	req := httptest.NewRequest(http.MethodGet, "/api/wallet/bob/export", nil)
	req.Header.Set("Test-User-ID", "alice")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusForbidden, w.Code)
}

func TestTransfer_InvalidAmount(t *testing.T) {
	svc := &MockWalletService{}
	handler := NewWalletHandler(svc)
	r := setupRouterEvo(handler)

	// zero amount
	body := map[string]interface{}{
		"from_user_id": "user1",
		"to_user_id":   "user2",
		"amount":       0,
	}
	jsonBody, _ := json.Marshal(body)
	req := httptest.NewRequest(http.MethodPost, "/api/wallet/transfer", bytes.NewReader(jsonBody))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Test-User-ID", "user1")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)
	assert.Equal(t, http.StatusBadRequest, w.Code)

	// negative amount
	body["amount"] = -100
	jsonBody2, _ := json.Marshal(body)
	req2 := httptest.NewRequest(http.MethodPost, "/api/wallet/transfer", bytes.NewReader(jsonBody2))
	req2.Header.Set("Content-Type", "application/json")
	req2.Header.Set("Test-User-ID", "user1")
	w2 := httptest.NewRecorder()
	r.ServeHTTP(w2, req2)
	assert.Equal(t, http.StatusBadRequest, w2.Code)
}

func TestTransfer_SelfTransfer(t *testing.T) {
	svc := &MockWalletService{}
	handler := NewWalletHandler(svc)
	r := setupRouterEvo(handler)

	body := map[string]interface{}{
		"from_user_id": "user1",
		"to_user_id":   "user1",
		"amount":       100,
	}
	jsonBody, _ := json.Marshal(body)
	req := httptest.NewRequest(http.MethodPost, "/api/wallet/transfer", bytes.NewReader(jsonBody))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Test-User-ID", "user1")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)
	assert.Equal(t, http.StatusBadRequest, w.Code)
}

func TestWithdraw_MissingAccountNo(t *testing.T) {
	svc := &MockWalletService{}
	handler := NewWalletHandler(svc)
	r := setupRouterEvo(handler)

	body := map[string]interface{}{
		"user_id": "user1",
		"amount":  100,
		// account_no missing
	}
	jsonBody, _ := json.Marshal(body)
	req := httptest.NewRequest(http.MethodPost, "/api/wallet/withdraw", bytes.NewReader(jsonBody))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Test-User-ID", "user1")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)
	assert.Equal(t, http.StatusBadRequest, w.Code)
}

func TestDeleteWallet_AdminSuccess(t *testing.T) {
	svc := &MockWalletService{}
	handler := NewWalletHandler(svc)
	r := setupRouterEvo(handler)

	svc.On("DeleteWallet", mock.Anything, "otheruser").Return(nil)

	req := httptest.NewRequest(http.MethodDelete, "/api/wallet/otheruser", nil)
	req.Header.Set("Test-User-ID", "admin")
	req.Header.Set("Test-User-Roles", "admin")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
	var resp map[string]interface{}
	require.NoError(t, json.Unmarshal(w.Body.Bytes(), &resp))
	assert.Equal(t, "Wallet deleted successfully", resp["message"])
}

// ==================== Additional Tests Phase 2 ====================

func TestGetMyWallet_ServiceError(t *testing.T) {
	svc := &MockWalletService{}
	handler := NewWalletHandler(svc)
	r := setupRouterEvo(handler)

	svc.On("GetWallet", mock.Anything, "user123").Return(nil, fmt.Errorf("database error"))

	req := httptest.NewRequest(http.MethodGet, "/api/wallet/me", nil)
	req.Header.Set("Test-User-ID", "user123")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusNotFound, w.Code)
}

func TestGetMyBalance_ServiceError(t *testing.T) {
	svc := &MockWalletService{}
	handler := NewWalletHandler(svc)
	r := setupRouterEvo(handler)

	svc.On("GetBalance", mock.Anything, "user123").Return(int64(0), fmt.Errorf("database error"))

	req := httptest.NewRequest(http.MethodGet, "/api/wallet/me/balance", nil)
	req.Header.Set("Test-User-ID", "user123")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusNotFound, w.Code)
}

func TestGetMyTransactions_ServiceError(t *testing.T) {
	svc := &MockWalletService{}
	handler := NewWalletHandler(svc)
	r := setupRouterEvo(handler)

	svc.On("GetTransactions", mock.Anything, "user123", 20, 0).Return(nil, fmt.Errorf("database error"))

	req := httptest.NewRequest(http.MethodGet, "/api/wallet/me/transactions", nil)
	req.Header.Set("Test-User-ID", "user123")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusInternalServerError, w.Code)
}

func TestExportMyTransactions_ServiceError(t *testing.T) {
	svc := &MockWalletService{}
	handler := NewWalletHandler(svc)
	r := setupRouterEvo(handler)

	svc.On("ExportTransactions", mock.Anything, "user123", 20, 0).Return(nil, fmt.Errorf("database error"))

	req := httptest.NewRequest(http.MethodGet, "/api/wallet/me/export", nil)
	req.Header.Set("Test-User-ID", "user123")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusInternalServerError, w.Code)
}

func TestTopUp_ServiceError(t *testing.T) {
	svc := &MockWalletService{}
	handler := NewWalletHandler(svc)
	r := setupRouterEvo(handler)

	reqObj := &walletmodels.TopUpRequest{
		UserID: "user123",
		Amount: 200,
		Method: "bank_transfer",
	}
	svc.On("TopUp", mock.Anything, reqObj).Return(nil, fmt.Errorf("payment failed"))

	body := map[string]interface{}{
		"user_id": "user123",
		"amount":  200,
	}
	jsonBody, _ := json.Marshal(body)

	req := httptest.NewRequest(http.MethodPost, "/api/wallet/topup", bytes.NewReader(jsonBody))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Test-User-ID", "user123")
	req.Header.Set("Idempotency-Key", "key-topup-2")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusInternalServerError, w.Code)
}

func TestTransfer_ServiceError(t *testing.T) {
	svc := &MockWalletService{}
	handler := NewWalletHandler(svc)
	r := setupRouterEvo(handler)

	reqObj := &walletmodels.TransferRequest{
		FromUserID: "user1",
		ToUserID:   "user2",
		Amount:     100,
		Description: "test",
	}
	svc.On("Transfer", mock.Anything, reqObj).Return(nil, fmt.Errorf("transfer failed"))

	body := map[string]interface{}{
		"from_user_id": "user1",
		"to_user_id":   "user2",
		"amount":       100,
		"description":  "test",
	}
	jsonBody, _ := json.Marshal(body)

	req := httptest.NewRequest(http.MethodPost, "/api/wallet/transfer", bytes.NewReader(jsonBody))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Test-User-ID", "user1")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusBadRequest, w.Code)
}

func TestWithdraw_ServiceError(t *testing.T) {
	svc := &MockWalletService{}
	handler := NewWalletHandler(svc)
	r := setupRouterEvo(handler)

	reqObj := &walletmodels.WithdrawalRequest{
		UserID:     "user1",
		Amount:     100,
		AccountNo:  "123456",
		HolderName: "Test",
	}
	svc.On("Withdraw", mock.Anything, reqObj).Return(nil, fmt.Errorf("withdrawal failed"))

	body := map[string]interface{}{
		"user_id":    "user1",
		"amount":     100,
		"account_no": "123456",
		"holder_name":"Test",
	}
	jsonBody, _ := json.Marshal(body)

	req := httptest.NewRequest(http.MethodPost, "/api/wallet/withdraw", bytes.NewReader(jsonBody))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Test-User-ID", "user1")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusBadRequest, w.Code)
}

func TestDeleteMyWallet_ServiceError(t *testing.T) {
	svc := &MockWalletService{}
	handler := NewWalletHandler(svc)
	r := setupRouterEvo(handler)

	svc.On("DeleteWallet", mock.Anything, "user123").Return(fmt.Errorf("delete failed"))

	req := httptest.NewRequest(http.MethodDelete, "/api/wallet/me", nil)
	req.Header.Set("Test-User-ID", "user123")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusBadRequest, w.Code)
}

func TestLockWallet_Unauthorized(t *testing.T) {
	svc := &MockWalletService{}
	handler := NewWalletHandler(svc)
	r := setupRouterEvo(handler)

	body := `{"reason":"test lock"}`
	req := httptest.NewRequest(http.MethodPatch, "/api/wallet/user1/lock", bytes.NewReader([]byte(body)))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Test-User-ID", "user123") // not admin
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusForbidden, w.Code)
}

func TestUnlockWallet_Unauthorized(t *testing.T) {
	svc := &MockWalletService{}
	handler := NewWalletHandler(svc)
	r := setupRouterEvo(handler)

	req := httptest.NewRequest(http.MethodPatch, "/api/wallet/user1/unlock", nil)
	req.Header.Set("Test-User-ID", "user123") // not admin
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusForbidden, w.Code)
}

func TestTransfer_AmountTooLarge(t *testing.T) {
	svc := &MockWalletService{}
	handler := NewWalletHandler(svc)
	r := setupRouterEvo(handler)

	body := map[string]interface{}{
		"from_user_id": "user1",
		"to_user_id":   "user2",
		"amount":        maxAmount + 1,
	}
	jsonBody, _ := json.Marshal(body)

	req := httptest.NewRequest(http.MethodPost, "/api/wallet/transfer", bytes.NewReader(jsonBody))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Test-User-ID", "user1")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusBadRequest, w.Code)
}

func TestWithdraw_AmountTooLarge(t *testing.T) {
	svc := &MockWalletService{}
	handler := NewWalletHandler(svc)
	r := setupRouterEvo(handler)

	body := map[string]interface{}{
		"user_id":    "user1",
		"amount":     maxAmount + 1,
		"account_no": "123456",
		"holder_name":"Test",
	}
	jsonBody, _ := json.Marshal(body)

	req := httptest.NewRequest(http.MethodPost, "/api/wallet/withdraw", bytes.NewReader(jsonBody))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Test-User-ID", "user1")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusBadRequest, w.Code)
}

func TestTransfer_DescriptionTooLong(t *testing.T) {
	svc := &MockWalletService{}
	handler := NewWalletHandler(svc)
	r := setupRouterEvo(handler)

	longDesc := string(make([]byte, maxDescriptionLen+1))
	body := map[string]interface{}{
		"from_user_id": "user1",
		"to_user_id":   "user2",
		"amount":       100,
		"description":  longDesc,
	}
	jsonBody, _ := json.Marshal(body)

	req := httptest.NewRequest(http.MethodPost, "/api/wallet/transfer", bytes.NewReader(jsonBody))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Test-User-ID", "user1")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusBadRequest, w.Code)
}

func TestGetWallet_ServiceError(t *testing.T) {
	svc := &MockWalletService{}
	handler := NewWalletHandler(svc)
	r := setupRouterEvo(handler)

	svc.On("GetWallet", mock.Anything, "user123").Return(nil, fmt.Errorf("database error"))

	req := httptest.NewRequest(http.MethodGet, "/api/wallet/user123", nil)
	req.Header.Set("Test-User-ID", "user123")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusNotFound, w.Code)
}