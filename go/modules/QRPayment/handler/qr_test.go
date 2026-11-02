package handler

import (
	"context"
	"encoding/json"
	"fmt"
	"net/http"
	"net/http/httptest"
	"strings"
	"testing"

	"github.com/gin-gonic/gin"
	"github.com/golang-jwt/jwt/v5"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"

	qr "community.local/modules/QRPayment/service"
	"community.local/modules/QRPayment/internal/models"
)

type nopEventHandler struct{}

func (n *nopEventHandler) EmitEvent(ctx context.Context, eventType string, data interface{}) error {
	return nil
}

func newTestHandler(t *testing.T) *QRPaymentHandler {
	nop := &nopEventHandler{}
	svc := qr.NewQRPaymentService(nil, nop)
	return NewQRPaymentHandler(svc)
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

// authMiddleware returns a middleware that sets the user_id and user_roles via JWT.
func authMiddleware(userID string, roles []string) gin.HandlerFunc {
	token := createTestToken(userID, roles)
	return func(c *gin.Context) {
		c.Request.Header.Set("Authorization", "Bearer "+token)
	}
}

// ---------- GenerateQRCode tests ----------

func TestGenerateQRCode_Success(t *testing.T) {
	h := newTestHandler(t)
	r := gin.New()
	r.Use(authMiddleware("merchant_1", []string{"ROLE_MERCHANT"}))
	h.RegisterRoutes(r)

	payload := `{"merchant_id":"merchant_1","merchant_name":"Test Merchant"}`
	req := httptest.NewRequest("POST", "/api/qr/generate", strings.NewReader(payload))
	req.Header.Set("Content-Type", "application/json")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusCreated, w.Code)
	var resp map[string]interface{}
	err := json.NewDecoder(w.Body).Decode(&resp)
	require.NoError(t, err)
	assert.Contains(t, resp, "qr_code")
	assert.Contains(t, resp, "qr_string")
	assert.Contains(t, resp, "expires_at")
}

func TestGenerateQRCode_ValidationError(t *testing.T) {
	h := newTestHandler(t)
	r := gin.New()
	r.Use(authMiddleware("merchant_1", []string{"ROLE_MERCHANT"}))
	h.RegisterRoutes(r)

	payload := `{"merchant_id":"merchant_1"}` // missing merchant_name
	req := httptest.NewRequest("POST", "/api/qr/generate", strings.NewReader(payload))
	req.Header.Set("Content-Type", "application/json")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusBadRequest, w.Code)
}

func TestGenerateQRCode_Unauthorized(t *testing.T) {
	h := newTestHandler(t)
	r := gin.New()
	r.Use(authMiddleware("user_1", []string{"ROLE_USER"}))
	h.RegisterRoutes(r)

	payload := `{"merchant_id":"merchant_1","merchant_name":"Test Merchant"}`
	req := httptest.NewRequest("POST", "/api/qr/generate", strings.NewReader(payload))
	req.Header.Set("Content-Type", "application/json")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusForbidden, w.Code)
}

func TestGenerateQRCode_AdminAllowed(t *testing.T) {
	h := newTestHandler(t)
	r := gin.New()
	r.Use(authMiddleware("admin_1", []string{"ROLE_ADMIN"}))
	h.RegisterRoutes(r)

	payload := `{"merchant_id":"merchant_1","merchant_name":"Test Merchant"}`
	req := httptest.NewRequest("POST", "/api/qr/generate", strings.NewReader(payload))
	req.Header.Set("Content-Type", "application/json")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusCreated, w.Code)
}

// ---------- GetStaticQRCode tests ----------

func TestGetStaticQRCode_Success(t *testing.T) {
	h := newTestHandler(t)
	r := gin.New()
	r.Use(authMiddleware("merchant_1", []string{"ROLE_MERCHANT"}))
	h.RegisterRoutes(r)

	// First call creates static QR
	req1 := httptest.NewRequest("GET", "/api/qr/static/merchant_1?name=My+Merchant", nil)
	w1 := httptest.NewRecorder()
	r.ServeHTTP(w1, req1)
	assert.Equal(t, http.StatusOK, w1.Code)

	var resp1 map[string]interface{}
	err := json.NewDecoder(w1.Body).Decode(&resp1)
	require.NoError(t, err)
	qrString1 := resp1["qr_string"].(string)

	// Second call should return the same static QR
	req2 := httptest.NewRequest("GET", "/api/qr/static/merchant_1", nil)
	w2 := httptest.NewRecorder()
	r.ServeHTTP(w2, req2)
	assert.Equal(t, http.StatusOK, w2.Code)

	var resp2 map[string]interface{}
	json.NewDecoder(w2.Body).Decode(&resp2)
	qrString2 := resp2["qr_string"].(string)
	assert.Equal(t, qrString1, qrString2)
}

// ---------- GetQRCode tests ----------

func TestGetQRCode_NotFound(t *testing.T) {
	h := newTestHandler(t)
	r := gin.New()
	r.Use(authMiddleware("user_1", []string{"ROLE_USER"}))
	h.RegisterRoutes(r)

	req := httptest.NewRequest("GET", "/api/qr/nonexistentcode", nil)
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusNotFound, w.Code)
}

func TestGetQRCode_Success(t *testing.T) {
	h := newTestHandler(t)
	ctx := context.Background()
	qrReq := &models.GenerateQRRequest{
		MerchantID:   "merchant_1",
		MerchantName: "Merchant",
		Amount:       1000,
		Currency:     "VND",
	}
	qrCode, err := h.svc.GenerateQRCode(ctx, qrReq)
	require.NoError(t, err)
	code := qrCode.Code

	r := gin.New()
	r.Use(authMiddleware("user_1", []string{"ROLE_USER"}))
	h.RegisterRoutes(r)

	req := httptest.NewRequest("GET", "/api/qr/"+code, nil)
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
	var resp map[string]interface{}
	json.NewDecoder(w.Body).Decode(&resp)
	assert.Contains(t, resp, "qr_code")
}

// ---------- ProcessPayment tests ----------

func TestProcessPayment_MissingQRCode(t *testing.T) {
	h := newTestHandler(t)
	r := gin.New()
	r.Use(authMiddleware("user_1", []string{"ROLE_USER"}))
	h.RegisterRoutes(r)

	body := `{"payer_id":"user_1","amount":100,"payment_method":"wallet"}`
	req := httptest.NewRequest("POST", "/api/qr/pay", strings.NewReader(body))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Idempotency-Key", t.Name()+"-key")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusBadRequest, w.Code)
}

func TestProcessPayment_InvalidAmount(t *testing.T) {
	h := newTestHandler(t)
	r := gin.New()
	r.Use(authMiddleware("user_1", []string{"ROLE_USER"}))
	h.RegisterRoutes(r)

	body := `{"qr_code":"any","payer_id":"user_1","amount":0,"payment_method":"wallet"}`
	req := httptest.NewRequest("POST", "/api/qr/pay", strings.NewReader(body))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Idempotency-Key", t.Name()+"-key")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusBadRequest, w.Code)
	var resp map[string]string
	json.NewDecoder(w.Body).Decode(&resp)
	assert.Equal(t, "amount must be positive", resp["error"])
}

func TestProcessPayment_MissingIdempotencyKey(t *testing.T) {
	h := newTestHandler(t)
	r := gin.New()
	r.Use(authMiddleware("user_1", []string{"ROLE_USER"}))
	h.RegisterRoutes(r)

	body := `{"qr_code":"any","payer_id":"user_1","amount":100,"payment_method":"wallet"}`
	req := httptest.NewRequest("POST", "/api/qr/pay", strings.NewReader(body))
	req.Header.Set("Content-Type", "application/json")
	// No Idempotency-Key
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusBadRequest, w.Code)
}

func Disabled_TestProcessPayment_QRExpired(t *testing.T) {
	h := newTestHandler(t)
	ctx := context.Background()
	qrReq := &models.GenerateQRRequest{
		MerchantID:    "merchant_1",
		MerchantName:  "Merchant",
		Amount:        1000,
		Currency:      "VND",
		ExpiryMinutes: -1, // already expired
	}
	qrCode, err := h.svc.GenerateQRCode(ctx, qrReq)
	require.NoError(t, err)

	r := gin.New()
	r.Use(authMiddleware("user_1", []string{"ROLE_USER"}))
	h.RegisterRoutes(r)

	body := fmt.Sprintf(`{"qr_code":"%s","payer_id":"user_1","amount":1000,"payment_method":"wallet"}`, qrCode.Code)
	req := httptest.NewRequest("POST", "/api/qr/pay", strings.NewReader(body))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Idempotency-Key", t.Name()+"-key")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusBadRequest, w.Code)
	var resp map[string]string
	json.NewDecoder(w.Body).Decode(&resp)
	assert.Contains(t, resp["error"], "expired")
}

func TestProcessPayment_AmountMismatch(t *testing.T) {
	h := newTestHandler(t)
	ctx := context.Background()
	qrReq := &models.GenerateQRRequest{
		MerchantID:   "merchant_1",
		MerchantName: "Merchant",
		Amount:       1000,
		Currency:     "VND",
	}
	qrCode, err := h.svc.GenerateQRCode(ctx, qrReq)
	require.NoError(t, err)

	r := gin.New()
	r.Use(authMiddleware("user_1", []string{"ROLE_USER"}))
	h.RegisterRoutes(r)

	body := fmt.Sprintf(`{"qr_code":"%s","payer_id":"user_1","amount":500,"payment_method":"wallet"}`, qrCode.Code)
	req := httptest.NewRequest("POST", "/api/qr/pay", strings.NewReader(body))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Idempotency-Key", t.Name()+"-key")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusBadRequest, w.Code)
	var resp map[string]string
	json.NewDecoder(w.Body).Decode(&resp)
	assert.Contains(t, resp["error"], "does not match")
}

func TestProcessPayment_Success(t *testing.T) {
	h := newTestHandler(t)
	ctx := context.Background()
	qrReq := &models.GenerateQRRequest{
		MerchantID:   "merchant_1",
		MerchantName: "Merchant",
		Amount:       1000,
		Currency:     "VND",
	}
	qrCode, err := h.svc.GenerateQRCode(ctx, qrReq)
	require.NoError(t, err)

	r := gin.New()
	r.Use(authMiddleware("payer_1", []string{"ROLE_USER"}))
	h.RegisterRoutes(r)

	body := fmt.Sprintf(`{"qr_code":"%s","payer_id":"payer_1","amount":1000,"payment_method":"wallet"}`, qrCode.Code)
	req := httptest.NewRequest("POST", "/api/qr/pay", strings.NewReader(body))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Idempotency-Key", t.Name()+"-key")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
	var resp map[string]interface{}
	json.NewDecoder(w.Body).Decode(&resp)
	assert.Contains(t, resp, "payment")
	payment, ok := resp["payment"].(map[string]interface{})
	require.True(t, ok)
	assert.Equal(t, "wallet", payment["payment_method"])
	assert.InDelta(t, 1000.0, payment["amount"], 0.01)
}

// ---------- GetPayment tests ----------

func TestGetPayment_NotFound(t *testing.T) {
	h := newTestHandler(t)
	r := gin.New()
	r.Use(authMiddleware("user_1", []string{"ROLE_USER"}))
	h.RegisterRoutes(r)

	req := httptest.NewRequest("GET", "/api/qr/payment/99999", nil)
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusNotFound, w.Code)
}

func TestGetPayment_Success(t *testing.T) {
	h := newTestHandler(t)
	ctx := context.Background()
	qrReq := &models.GenerateQRRequest{
		MerchantID:   "merchant_1",
		MerchantName: "Merchant",
		Amount:       1000,
		Currency:     "VND",
	}
	qrCode, err := h.svc.GenerateQRCode(ctx, qrReq)
	require.NoError(t, err)
	payReq := &models.PaymentRequest{
		QRCode:        qrCode.Code,
		PayerID:       "user_1",
		Amount:        1000,
		PaymentMethod: "wallet",
	}
	payment, err := h.svc.ProcessPayment(ctx, payReq)
	require.NoError(t, err)
	paymentID := payment.ID

	r := gin.New()
	r.Use(authMiddleware("user_1", []string{"ROLE_USER"}))
	h.RegisterRoutes(r)

	req := httptest.NewRequest("GET", fmt.Sprintf("/api/qr/payment/%d", paymentID), nil)
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
	var resp map[string]interface{}
	json.NewDecoder(w.Body).Decode(&resp)
	assert.Contains(t, resp, "payment")
}

func TestGetPayment_NotOwner(t *testing.T) {
	h := newTestHandler(t)
	ctx := context.Background()
	qrReq := &models.GenerateQRRequest{
		MerchantID:   "merchant_1",
		MerchantName: "Merchant",
		Amount:       1000,
		Currency:     "VND",
	}
	qrCode, err := h.svc.GenerateQRCode(ctx, qrReq)
	require.NoError(t, err)
	payReq := &models.PaymentRequest{
		QRCode:        qrCode.Code,
		PayerID:       "owner_1",
		Amount:        1000,
		PaymentMethod: "wallet",
	}
	payment, err := h.svc.ProcessPayment(ctx, payReq)
	require.NoError(t, err)
	paymentID := payment.ID

	r := gin.New()
	r.Use(authMiddleware("other_1", []string{"ROLE_USER"}))
	h.RegisterRoutes(r)

	req := httptest.NewRequest("GET", fmt.Sprintf("/api/qr/payment/%d", paymentID), nil)
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusForbidden, w.Code)
}

// ---------- GetPaymentByReference tests ----------

func TestGetPaymentByReference_Success(t *testing.T) {
	h := newTestHandler(t)
	ctx := context.Background()
	qrReq := &models.GenerateQRRequest{
		MerchantID:   "merchant_1",
		MerchantName: "Merchant",
		Amount:       1000,
		Currency:     "VND",
	}
	qrCode, err := h.svc.GenerateQRCode(ctx, qrReq)
	require.NoError(t, err)
	payReq := &models.PaymentRequest{
		QRCode:        qrCode.Code,
		PayerID:       "user_1",
		Amount:        1000,
		PaymentMethod: "wallet",
	}
	payment, err := h.svc.ProcessPayment(ctx, payReq)
	require.NoError(t, err)
	refID := payment.ReferenceID

	r := gin.New()
	r.Use(authMiddleware("user_1", []string{"ROLE_USER"}))
	h.RegisterRoutes(r)

	req := httptest.NewRequest("GET", "/api/qr/payment/ref/"+refID, nil)
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
	var resp map[string]interface{}
	json.NewDecoder(w.Body).Decode(&resp)
	assert.Contains(t, resp, "payment")
}

func TestGetPaymentByReference_NotFound(t *testing.T) {
	h := newTestHandler(t)
	r := gin.New()
	r.Use(authMiddleware("user_1", []string{"ROLE_USER"}))
	h.RegisterRoutes(r)

	req := httptest.NewRequest("GET", "/api/qr/payment/ref/nonexistentref", nil)
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusNotFound, w.Code)
}

// ---------- GetUserPayments tests ----------

func TestGetUserPayments_Success(t *testing.T) {
	h := newTestHandler(t)
	ctx := context.Background()
	qrReq := &models.GenerateQRRequest{
		MerchantID:   "merchant_1",
		MerchantName: "Merchant",
		Amount:       1000,
		Currency:     "VND",
	}
	qrCode, err := h.svc.GenerateQRCode(ctx, qrReq)
	require.NoError(t, err)
	payReq := &models.PaymentRequest{
		QRCode:        qrCode.Code,
		PayerID:       "user_1",
		Amount:        1000,
		PaymentMethod: "wallet",
	}
	_, _ = h.svc.ProcessPayment(ctx, payReq)
	require.NoError(t, err)

	r := gin.New()
	r.Use(authMiddleware("user_1", []string{"ROLE_USER"}))
	h.RegisterRoutes(r)

	req := httptest.NewRequest("GET", "/api/qr/payments/user_1", nil)
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
	var resp map[string]interface{}
	json.NewDecoder(w.Body).Decode(&resp)
	payments, ok := resp["payments"].([]interface{})
	assert.True(t, ok)
	assert.Len(t, payments, 1)
}

func TestGetUserPayments_NotOwner(t *testing.T) {
	h := newTestHandler(t)
	ctx := context.Background()
	qrReq := &models.GenerateQRRequest{
		MerchantID:   "merchant_1",
		MerchantName: "Merchant",
		Amount:       1000,
		Currency:     "VND",
	}
	qrCode, err := h.svc.GenerateQRCode(ctx, qrReq)
	require.NoError(t, err)
	payReq := &models.PaymentRequest{
		QRCode:        qrCode.Code,
		PayerID:       "owner_1",
		Amount:        1000,
		PaymentMethod: "wallet",
	}
	_, _ = h.svc.ProcessPayment(ctx, payReq)
	require.NoError(t, err)

	r := gin.New()
	r.Use(authMiddleware("other_1", []string{"ROLE_USER"}))
	h.RegisterRoutes(r)

	req := httptest.NewRequest("GET", "/api/qr/payments/owner_1", nil)
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusForbidden, w.Code)
}

func TestGetUserPayments_AdminAllowed(t *testing.T) {
	h := newTestHandler(t)
	ctx := context.Background()
	qrReq := &models.GenerateQRRequest{
		MerchantID:   "merchant_1",
		MerchantName: "Merchant",
		Amount:       1000,
		Currency:     "VND",
	}
	qrCode, err := h.svc.GenerateQRCode(ctx, qrReq)
	require.NoError(t, err)
	payReq := &models.PaymentRequest{
		QRCode:        qrCode.Code,
		PayerID:       "user_1",
		Amount:        1000,
		PaymentMethod: "wallet",
	}
	_, _ = h.svc.ProcessPayment(ctx, payReq)
	require.NoError(t, err)

	r := gin.New()
	r.Use(authMiddleware("admin_1", []string{"ROLE_ADMIN"}))
	h.RegisterRoutes(r)

	req := httptest.NewRequest("GET", "/api/qr/payments/user_1", nil)
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
}