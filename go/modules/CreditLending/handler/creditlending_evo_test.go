package handler

import (
	"bytes"
	"encoding/json"
	"fmt"
	"net/http"
	"net/http/httptest"
	"testing"
	"time"

	"github.com/gin-gonic/gin"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/mock"

	creditlending "community.local/modules/CreditLending/internal/models"

	"community.local/internal/idempotency"
)

// MockCreditLendingService implements CreditLendingService
type MockCreditLendingService struct {
	mock.Mock
}

func (m *MockCreditLendingService) CreateApplication(app *creditlending.CreditApplication) error {
	args := m.Called(app)
	return args.Error(0)
}

func (m *MockCreditLendingService) GetApplication(id int64) (*creditlending.CreditApplication, error) {
	args := m.Called(id)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).(*creditlending.CreditApplication), args.Error(1)
}

func (m *MockCreditLendingService) GetUserApplications(userID string) ([]*creditlending.CreditApplication, error) {
	args := m.Called(userID)
	return args.Get(0).([]*creditlending.CreditApplication), args.Error(1)
}

func (m *MockCreditLendingService) ApproveApplication(id int64, amount float64) (*creditlending.CreditApplication, error) {
	args := m.Called(id, amount)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).(*creditlending.CreditApplication), args.Error(1)
}

func (m *MockCreditLendingService) Disburse(id int64) (*creditlending.CreditApplication, error) {
	args := m.Called(id)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).(*creditlending.CreditApplication), args.Error(1)
}

func (m *MockCreditLendingService) Repay(id int64, amount float64) (*creditlending.CreditRepayment, error) {
	args := m.Called(id, amount)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).(*creditlending.CreditRepayment), args.Error(1)
}

func (m *MockCreditLendingService) GetRepayments(id int64) ([]*creditlending.CreditRepayment, error) {
	args := m.Called(id)
	return args.Get(0).([]*creditlending.CreditRepayment), args.Error(1)
}

func (m *MockCreditLendingService) GetCreditScore(userID string) (*creditlending.CreditScore, error) {
	args := m.Called(userID)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).(*creditlending.CreditScore), args.Error(1)
}

const maxAmount = 1000000000

func setupRouter(h *CreditLendingHandler, userID string, roles []string) *gin.Engine {
	gin.SetMode(gin.TestMode)
	router := gin.New()
	router.Use(func(c *gin.Context) {
		if userID != "" {
			c.Set("user_id", userID)
		}
		if roles != nil {
			c.Set("user_roles", roles)
		}
		c.Next()
	})
	h.RegisterRoutes(router)
	return router
}

func newCreditApplication(id int64, userID, status string, amount, approvedAmount, monthlyPayment, totalRepayment float64) *creditlending.CreditApplication {
	now := time.Now()
	return &creditlending.CreditApplication{
		ID:              id,
		UserID:          userID,
		CreditType:      "personal",
		Amount:          amount,
		Term:            12,
		InterestRate:    0.024,
		Status:          status,
		ApprovedAmount:  approvedAmount,
		MonthlyPayment:  monthlyPayment,
		TotalRepayment:  totalRepayment,
		DisbursedAt:     nil,
		CompletedAt:     nil,
		CreatedAt:       now,
	}
}

func newCreditRepayment(id, applicationID int64, amount float64, status string) *creditlending.CreditRepayment {
	now := time.Now()
	return &creditlending.CreditRepayment{
		ID:            id,
		ApplicationID: applicationID,
		Amount:        amount,
		Principal:     amount / 1.024,
		Interest:      amount - (amount / 1.024),
		Penalty:       0,
		BalanceAfter:  0,
		PaidAt:        &now,
		Status:        status,
		CreatedAt:     now,
	}
}

func newCreditScore(userID string, score int, riskLevel string) *creditlending.CreditScore {
	now := time.Now()
	return &creditlending.CreditScore{
		UserID:          userID,
		Score:           score,
		RiskLevel:       riskLevel,
		MaxCreditAmount: 10000000,
		UpdatedAt:       now,
	}
}

// ==================== Comprehensive tests for increased coverage ====================

func TestCreateApplication_Success(t *testing.T) {
	mockSvc := new(MockCreditLendingService)
	mockSvc.On("CreateApplication", mock.AnythingOfType("*creditlending.CreditApplication")).Return(nil).Once()

	h := &CreditLendingHandler{}
	h.svc = mockSvc

	router := setupRouter(h, "user1", nil)

	reqBody := map[string]interface{}{
		"user_id":     "user1",
		"credit_type": "personal",
		"amount":      1000,
		"term":        12,
	}
	body, _ := json.Marshal(reqBody)
	req := httptest.NewRequest("POST", "/api/credit/applications", bytes.NewReader(body))
	req.Header.Set("Content-Type", "application/json")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusCreated, w.Code)
	var resp map[string]interface{}
	_ = json.Unmarshal(w.Body.Bytes(), &resp)
	_, ok := resp["application"]
	assert.True(t, ok, "response should contain application")
}

func TestCreateApplication_ForbiddenOtherUser(t *testing.T) {
	mockSvc := new(MockCreditLendingService)
	h := &CreditLendingHandler{}
	h.svc = mockSvc
	router := setupRouter(h, "user1", nil)

	reqBody := map[string]interface{}{
		"user_id":     "other_user",
		"credit_type": "personal",
		"amount":      1000,
		"term":        12,
	}
	body, _ := json.Marshal(reqBody)
	req := httptest.NewRequest("POST", "/api/credit/applications", bytes.NewReader(body))
	req.Header.Set("Content-Type", "application/json")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusForbidden, w.Code)
	mockSvc.AssertNotCalled(t, "CreateApplication")
}

func TestCreateApplication_AdminCanCreateForOtherUser(t *testing.T) {
	mockSvc := new(MockCreditLendingService)
	mockSvc.On("CreateApplication", mock.AnythingOfType("*creditlending.CreditApplication")).Return(nil).Once()

	h := &CreditLendingHandler{}
	h.svc = mockSvc
	router := setupRouter(h, "admin", []string{"admin"})

	reqBody := map[string]interface{}{
		"user_id":     "other_user",
		"credit_type": "personal",
		"amount":      1000,
		"term":        12,
	}
	body, _ := json.Marshal(reqBody)
	req := httptest.NewRequest("POST", "/api/credit/applications", bytes.NewReader(body))
	req.Header.Set("Content-Type", "application/json")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusCreated, w.Code)
}

func TestCreateApplication_ServiceError(t *testing.T) {
	mockSvc := new(MockCreditLendingService)
	mockSvc.On("CreateApplication", mock.AnythingOfType("*creditlending.CreditApplication")).Return(fmt.Errorf("db error")).Once()

	h := &CreditLendingHandler{}
	h.svc = mockSvc
	router := setupRouter(h, "user1", nil)

	reqBody := map[string]interface{}{
		"user_id":     "user1",
		"credit_type": "personal",
		"amount":      1000,
		"term":        12,
	}
	body, _ := json.Marshal(reqBody)
	req := httptest.NewRequest("POST", "/api/credit/applications", bytes.NewReader(body))
	req.Header.Set("Content-Type", "application/json")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusInternalServerError, w.Code)
}

func TestGetApplication_Success(t *testing.T) {
	app := newCreditApplication(123, "user1", "pending", 1000, 0, 0, 0)
	mockSvc := new(MockCreditLendingService)
	mockSvc.On("GetApplication", int64(123)).Return(app, nil).Once()

	h := &CreditLendingHandler{}
	h.svc = mockSvc
	router := setupRouter(h, "user1", nil)

	req := httptest.NewRequest("GET", "/api/credit/applications/123", nil)
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
	var resp map[string]interface{}
	_ = json.Unmarshal(w.Body.Bytes(), &resp)
	_, ok := resp["application"]
	assert.True(t, ok)
}

func TestGetApplication_NotFound(t *testing.T) {
	mockSvc := new(MockCreditLendingService)
	mockSvc.On("GetApplication", int64(123)).Return((*creditlending.CreditApplication)(nil), fmt.Errorf("not found")).Once()

	h := &CreditLendingHandler{}
	h.svc = mockSvc
	router := setupRouter(h, "user1", nil)

	req := httptest.NewRequest("GET", "/api/credit/applications/123", nil)
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusNotFound, w.Code)
}

func TestGetApplication_InvalidID(t *testing.T) {
	h := &CreditLendingHandler{}
	router := setupRouter(h, "user1", nil)

	req := httptest.NewRequest("GET", "/api/credit/applications/abc", nil)
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusBadRequest, w.Code)
}

func TestGetApplication_ForbiddenOtherUser(t *testing.T) {
	app := newCreditApplication(123, "other_user", "pending", 1000, 0, 0, 0)
	mockSvc := new(MockCreditLendingService)
	mockSvc.On("GetApplication", int64(123)).Return(app, nil).Once()

	h := &CreditLendingHandler{}
	h.svc = mockSvc
	router := setupRouter(h, "user1", nil)

	req := httptest.NewRequest("GET", "/api/credit/applications/123", nil)
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusForbidden, w.Code)
}

func TestGetApplication_AdminAllowed(t *testing.T) {
	app := newCreditApplication(123, "other_user", "pending", 1000, 0, 0, 0)
	mockSvc := new(MockCreditLendingService)
	mockSvc.On("GetApplication", int64(123)).Return(app, nil).Once()

	h := &CreditLendingHandler{}
	h.svc = mockSvc
	router := setupRouter(h, "admin", []string{"admin"})

	req := httptest.NewRequest("GET", "/api/credit/applications/123", nil)
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
}

func TestGetUserApplications_Success(t *testing.T) {
	apps := []*creditlending.CreditApplication{
		newCreditApplication(1, "user1", "pending", 1000, 0, 0, 0),
		newCreditApplication(2, "user1", "approved", 2000, 1500, 100, 1200),
	}
	mockSvc := new(MockCreditLendingService)
	mockSvc.On("GetUserApplications", "user1").Return(apps, nil).Once()

	h := &CreditLendingHandler{}
	h.svc = mockSvc
	router := setupRouter(h, "user1", nil)

	req := httptest.NewRequest("GET", "/api/credit/applications/user/user1", nil)
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
	var resp map[string]interface{}
	_ = json.Unmarshal(w.Body.Bytes(), &resp)
	_, ok := resp["applications"]
	assert.True(t, ok)
	count := resp["count"].(float64)
	assert.Equal(t, 2, int(count))
}

func TestGetUserApplications_ForbiddenOtherUser(t *testing.T) {
	mockSvc := new(MockCreditLendingService)
	h := &CreditLendingHandler{}
	h.svc = mockSvc
	router := setupRouter(h, "user1", nil)

	req := httptest.NewRequest("GET", "/api/credit/applications/user/other_user", nil)
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusForbidden, w.Code)
	mockSvc.AssertNotCalled(t, "GetUserApplications")
}

func TestGetUserApplications_ServiceError(t *testing.T) {
	mockSvc := new(MockCreditLendingService)
	mockSvc.On("GetUserApplications", "user1").Return([]*creditlending.CreditApplication{}, fmt.Errorf("db error")).Once()

	h := &CreditLendingHandler{}
	h.svc = mockSvc
	router := setupRouter(h, "user1", nil)

	req := httptest.NewRequest("GET", "/api/credit/applications/user/user1", nil)
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusInternalServerError, w.Code)
}

func TestApproveApplication_Success(t *testing.T) {
	app := newCreditApplication(123, "user1", "approved", 1000, 800, 90, 1080)
	mockSvc := new(MockCreditLendingService)
	mockSvc.On("ApproveApplication", int64(123), float64(800)).Return(app, nil).Once()

	h := &CreditLendingHandler{}
	h.svc = mockSvc
	router := setupRouter(h, "admin", []string{"admin"})

	key := "idem-key-" + t.Name()
	reqBody := map[string]interface{}{
		"approved_amount": 800,
	}
	body, _ := json.Marshal(reqBody)
	req := httptest.NewRequest("POST", "/api/credit/applications/123/approve", bytes.NewReader(body))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Idempotency-Key", key)
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
	// ensure cache cleanup to avoid affecting other tests
	defer idempotency.Delete(key)

	var resp map[string]interface{}
	_ = json.Unmarshal(w.Body.Bytes(), &resp)
	_, ok := resp["application"]
	assert.True(t, ok)
	_, ok2 := resp["message"]
	assert.True(t, ok2)
	mockSvc.AssertExpectations(t)
}

func TestApproveApplication_IdempotencyCacheHit(t *testing.T) {
	app := newCreditApplication(123, "user1", "approved", 1000, 800, 90, 1080)
	mockSvc := new(MockCreditLendingService)
	mockSvc.On("ApproveApplication", int64(123), float64(800)).Return(app, nil).Once()

	h := &CreditLendingHandler{}
	h.svc = mockSvc
	router := setupRouter(h, "admin", []string{"admin"})

	key := "idem-key-" + t.Name()
	reqBody := map[string]interface{}{
		"approved_amount": 800,
	}
	body, _ := json.Marshal(reqBody)

	// First request
	req1 := httptest.NewRequest("POST", "/api/credit/applications/123/approve", bytes.NewReader(body))
	req1.Header.Set("Content-Type", "application/json")
	req1.Header.Set("Idempotency-Key", key)
	w1 := httptest.NewRecorder()
	router.ServeHTTP(w1, req1)
	assert.Equal(t, http.StatusOK, w1.Code)

	// Ensure idempotency store is populated
	defer idempotency.Delete(key)

	// Second request with same key
	req2 := httptest.NewRequest("POST", "/api/credit/applications/123/approve", bytes.NewReader(body))
	req2.Header.Set("Content-Type", "application/json")
	req2.Header.Set("Idempotency-Key", key)
	w2 := httptest.NewRecorder()
	router.ServeHTTP(w2, req2)
	assert.Equal(t, http.StatusOK, w2.Code)

	mockSvc.AssertNumberOfCalls(t, "ApproveApplication", 1)
}

func TestApproveApplication_InvalidAmount_Zero(t *testing.T) {
	h := &CreditLendingHandler{}
	router := setupRouter(h, "admin", []string{"admin"})

	reqBody := map[string]interface{}{
		"approved_amount": 0,
	}
	body, _ := json.Marshal(reqBody)
	req := httptest.NewRequest("POST", "/api/credit/applications/123/approve", bytes.NewReader(body))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Idempotency-Key", "somekey")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusBadRequest, w.Code)
}

func TestApproveApplication_InvalidAmount_TooLarge(t *testing.T) {
	h := &CreditLendingHandler{}
	router := setupRouter(h, "admin", []string{"admin"})

	reqBody := map[string]interface{}{
		"approved_amount": maxAmount + 1,
	}
	body, _ := json.Marshal(reqBody)
	req := httptest.NewRequest("POST", "/api/credit/applications/123/approve", bytes.NewReader(body))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Idempotency-Key", "somekey")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusBadRequest, w.Code)
}

func TestApproveApplication_ApprovedAmountExceedsRequested(t *testing.T) {
	mockSvc := new(MockCreditLendingService)
	mockSvc.On("ApproveApplication", int64(123), float64(1500)).Return((*creditlending.CreditApplication)(nil), fmt.Errorf("approved amount cannot exceed requested amount")).Once()

	h := &CreditLendingHandler{}
	h.svc = mockSvc
	router := setupRouter(h, "admin", []string{"admin"})

	reqBody := map[string]interface{}{
		"approved_amount": 1500,
	}
	body, _ := json.Marshal(reqBody)
	req := httptest.NewRequest("POST", "/api/credit/applications/123/approve", bytes.NewReader(body))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Idempotency-Key", "key123")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusBadRequest, w.Code)
	// Check error message?
	var resp map[string]string
	_ = json.Unmarshal(w.Body.Bytes(), &resp)
	assert.Contains(t, resp["error"], "cannot exceed")
}

func TestDisburse_Success(t *testing.T) {
	app := newCreditApplication(123, "user1", "approved", 1000, 800, 90, 1080)
	mockSvc := new(MockCreditLendingService)
	mockSvc.On("Disburse", int64(123)).Return(app, nil).Once()

	h := &CreditLendingHandler{}
	h.svc = mockSvc
	router := setupRouter(h, "admin", []string{"admin"})

	key := "idem-key-" + t.Name()
	req := httptest.NewRequest("POST", "/api/credit/applications/123/disburse", nil)
	req.Header.Set("Idempotency-Key", key)
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
	defer idempotency.Delete(key)
	var resp map[string]interface{}
	_ = json.Unmarshal(w.Body.Bytes(), &resp)
	_, ok := resp["application"]
	assert.True(t, ok)
}

func TestDisburse_IdempotencyCacheHit(t *testing.T) {
	app := newCreditApplication(123, "user1", "approved", 1000, 800, 90, 1080)
	mockSvc := new(MockCreditLendingService)
	mockSvc.On("Disburse", int64(123)).Return(app, nil).Once()

	h := &CreditLendingHandler{}
	h.svc = mockSvc
	router := setupRouter(h, "admin", []string{"admin"})

	key := "idem-key-" + t.Name()
	req1 := httptest.NewRequest("POST", "/api/credit/applications/123/disburse", nil)
	req1.Header.Set("Idempotency-Key", key)
	w1 := httptest.NewRecorder()
	router.ServeHTTP(w1, req1)
	assert.Equal(t, http.StatusOK, w1.Code)
	defer idempotency.Delete(key)

	// Second request
	req2 := httptest.NewRequest("POST", "/api/credit/applications/123/disburse", nil)
	req2.Header.Set("Idempotency-Key", key)
	w2 := httptest.NewRecorder()
	router.ServeHTTP(w2, req2)
	assert.Equal(t, http.StatusOK, w2.Code)

	mockSvc.AssertNumberOfCalls(t, "Disburse", 1)
}

func TestDisburse_ServiceError_NotApproved(t *testing.T) {
	mockSvc := new(MockCreditLendingService)
	mockSvc.On("Disburse", int64(123)).Return((*creditlending.CreditApplication)(nil), fmt.Errorf("application not approved")).Once()

	h := &CreditLendingHandler{}
	h.svc = mockSvc
	router := setupRouter(h, "admin", []string{"admin"})

	req := httptest.NewRequest("POST", "/api/credit/applications/123/disburse", nil)
	req.Header.Set("Idempotency-Key", "key123")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusBadRequest, w.Code)
}

func TestDisburse_NoIdempotencyKey(t *testing.T) {
	h := &CreditLendingHandler{}
	router := setupRouter(h, "admin", []string{"admin"})

	req := httptest.NewRequest("POST", "/api/credit/applications/123/disburse", nil)
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusBadRequest, w.Code)
}

func TestRepay_Success(t *testing.T) {
	app := newCreditApplication(123, "user1", "disbursed", 1000, 800, 90, 1080)
	repayment := newCreditRepayment(1, 123, 100, "completed")
	mockSvc := new(MockCreditLendingService)
	mockSvc.On("GetApplication", int64(123)).Return(app, nil).Once()
	mockSvc.On("Repay", int64(123), float64(100)).Return(repayment, nil).Once()

	h := &CreditLendingHandler{}
	h.svc = mockSvc
	router := setupRouter(h, "user1", nil)

	key := "idem-key-" + t.Name()
	reqBody := map[string]interface{}{
		"amount": 100,
	}
	body, _ := json.Marshal(reqBody)
	req := httptest.NewRequest("POST", "/api/credit/applications/123/repay", bytes.NewReader(body))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Idempotency-Key", key)
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
	defer idempotency.Delete(key)
	var resp map[string]interface{}
	_ = json.Unmarshal(w.Body.Bytes(), &resp)
	_, ok := resp["repayment"]
	assert.True(t, ok)
}

func TestRepay_IdempotencyCacheHit(t *testing.T) {
	app := newCreditApplication(123, "user1", "disbursed", 1000, 800, 90, 1080)
	repayment := newCreditRepayment(1, 123, 100, "completed")
	mockSvc := new(MockCreditLendingService)
	mockSvc.On("GetApplication", int64(123)).Return(app, nil).Once()
	mockSvc.On("Repay", int64(123), float64(100)).Return(repayment, nil).Once()

	h := &CreditLendingHandler{}
	h.svc = mockSvc
	router := setupRouter(h, "user1", nil)

	key := "idem-key-" + t.Name()
	reqBody := map[string]interface{}{
		"amount": 100,
	}
	body, _ := json.Marshal(reqBody)

	req1 := httptest.NewRequest("POST", "/api/credit/applications/123/repay", bytes.NewReader(body))
	req1.Header.Set("Content-Type", "application/json")
	req1.Header.Set("Idempotency-Key", key)
	w1 := httptest.NewRecorder()
	router.ServeHTTP(w1, req1)
	assert.Equal(t, http.StatusOK, w1.Code)
	defer idempotency.Delete(key)

	req2 := httptest.NewRequest("POST", "/api/credit/applications/123/repay", bytes.NewReader(body))
	req2.Header.Set("Content-Type", "application/json")
	req2.Header.Set("Idempotency-Key", key)
	w2 := httptest.NewRecorder()
	router.ServeHTTP(w2, req2)
	assert.Equal(t, http.StatusOK, w2.Code)

	mockSvc.AssertNumberOfCalls(t, "GetApplication", 1)
	mockSvc.AssertNumberOfCalls(t, "Repay", 1)
}

func TestRepay_InvalidAmount_Zero(t *testing.T) {
	h := &CreditLendingHandler{}
	router := setupRouter(h, "user1", nil)

	reqBody := map[string]interface{}{
		"amount": 0,
	}
	body, _ := json.Marshal(reqBody)
	req := httptest.NewRequest("POST", "/api/credit/applications/123/repay", bytes.NewReader(body))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Idempotency-Key", "key")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusBadRequest, w.Code)
}

func TestRepay_InvalidAmount_TooLarge(t *testing.T) {
	h := &CreditLendingHandler{}
	router := setupRouter(h, "user1", nil)

	reqBody := map[string]interface{}{
		"amount": maxAmount + 1,
	}
	body, _ := json.Marshal(reqBody)
	req := httptest.NewRequest("POST", "/api/credit/applications/123/repay", bytes.NewReader(body))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Idempotency-Key", "key")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusBadRequest, w.Code)
}

func TestRepay_ForbiddenOtherUser(t *testing.T) {
	app := newCreditApplication(123, "other_user", "disbursed", 1000, 800, 90, 1080)
	mockSvc := new(MockCreditLendingService)
	mockSvc.On("GetApplication", int64(123)).Return(app, nil).Once()

	h := &CreditLendingHandler{}
	h.svc = mockSvc
	router := setupRouter(h, "user1", nil)

	reqBody := map[string]interface{}{
		"amount": 100,
	}
	body, _ := json.Marshal(reqBody)
	req := httptest.NewRequest("POST", "/api/credit/applications/123/repay", bytes.NewReader(body))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Idempotency-Key", "key")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusForbidden, w.Code)
	mockSvc.AssertNotCalled(t, "Repay")
}

func TestRepay_ServiceError(t *testing.T) {
	app := newCreditApplication(123, "user1", "disbursed", 1000, 800, 90, 1080)
	mockSvc := new(MockCreditLendingService)
	mockSvc.On("GetApplication", int64(123)).Return(app, nil).Once()
	mockSvc.On("Repay", int64(123), float64(100)).Return((*creditlending.CreditRepayment)(nil), fmt.Errorf("service error")).Once()

	h := &CreditLendingHandler{}
	h.svc = mockSvc
	router := setupRouter(h, "user1", nil)

	key := "idem-key-" + t.Name()
	reqBody := map[string]interface{}{
		"amount": 100,
	}
	body, _ := json.Marshal(reqBody)
	req := httptest.NewRequest("POST", "/api/credit/applications/123/repay", bytes.NewReader(body))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Idempotency-Key", key)
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusBadRequest, w.Code)
	defer idempotency.Delete(key)
}

func TestGetRepayments_Success(t *testing.T) {
	app := newCreditApplication(123, "user1", "disbursed", 1000, 800, 90, 1080)
	repayments := []*creditlending.CreditRepayment{
		newCreditRepayment(1, 123, 100, "completed"),
		newCreditRepayment(2, 123, 100, "completed"),
	}
	mockSvc := new(MockCreditLendingService)
	mockSvc.On("GetApplication", int64(123)).Return(app, nil).Once()
	mockSvc.On("GetRepayments", int64(123)).Return(repayments, nil).Once()

	h := &CreditLendingHandler{}
	h.svc = mockSvc
	router := setupRouter(h, "user1", nil)

	req := httptest.NewRequest("GET", "/api/credit/applications/123/repayments", nil)
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
	var resp map[string]interface{}
	_ = json.Unmarshal(w.Body.Bytes(), &resp)
	_, ok := resp["repayments"]
	assert.True(t, ok)
	count := resp["count"].(float64)
	assert.Equal(t, 2, int(count))
}

func TestGetRepayments_NotFound(t *testing.T) {
	mockSvc := new(MockCreditLendingService)
	mockSvc.On("GetApplication", int64(123)).Return((*creditlending.CreditApplication)(nil), fmt.Errorf("not found")).Once()

	h := &CreditLendingHandler{}
	h.svc = mockSvc
	router := setupRouter(h, "user1", nil)

	req := httptest.NewRequest("GET", "/api/credit/applications/123/repayments", nil)
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusNotFound, w.Code)
}

func TestGetRepayments_ForbiddenOtherUser(t *testing.T) {
	app := newCreditApplication(123, "other_user", "disbursed", 1000, 800, 90, 1080)
	mockSvc := new(MockCreditLendingService)
	mockSvc.On("GetApplication", int64(123)).Return(app, nil).Once()

	h := &CreditLendingHandler{}
	h.svc = mockSvc
	router := setupRouter(h, "user1", nil)

	req := httptest.NewRequest("GET", "/api/credit/applications/123/repayments", nil)
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusForbidden, w.Code)
	mockSvc.AssertNotCalled(t, "GetRepayments")
}

func TestGetCreditScore_Success(t *testing.T) {
	score := newCreditScore("user1", 700, "low")
	mockSvc := new(MockCreditLendingService)
	mockSvc.On("GetCreditScore", "user1").Return(score, nil).Once()

	h := &CreditLendingHandler{}
	h.svc = mockSvc
	router := setupRouter(h, "user1", nil)

	req := httptest.NewRequest("GET", "/api/credit/score/user1", nil)
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
	var resp map[string]interface{}
	_ = json.Unmarshal(w.Body.Bytes(), &resp)
	_, ok := resp["credit_score"]
	assert.True(t, ok)
}

func TestGetCreditScore_AdminAllowed(t *testing.T) {
	score := newCreditScore("other_user", 700, "low")
	mockSvc := new(MockCreditLendingService)
	mockSvc.On("GetCreditScore", "other_user").Return(score, nil).Once()

	h := &CreditLendingHandler{}
	h.svc = mockSvc
	router := setupRouter(h, "admin", []string{"admin"})

	req := httptest.NewRequest("GET", "/api/credit/score/other_user", nil)
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
}

func TestGetCreditScore_ForbiddenOtherUser(t *testing.T) {
	mockSvc := new(MockCreditLendingService)
	h := &CreditLendingHandler{}
	h.svc = mockSvc
	router := setupRouter(h, "user1", nil)

	req := httptest.NewRequest("GET", "/api/credit/score/other_user", nil)
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusForbidden, w.Code)
	mockSvc.AssertNotCalled(t, "GetCreditScore")
}

func TestGetCreditScore_ServiceError(t *testing.T) {
	mockSvc := new(MockCreditLendingService)
	mockSvc.On("GetCreditScore", "user1").Return((*creditlending.CreditScore)(nil), fmt.Errorf("db error")).Once()

	h := &CreditLendingHandler{}
	h.svc = mockSvc
	router := setupRouter(h, "user1", nil)

	req := httptest.NewRequest("GET", "/api/credit/score/user1", nil)
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusInternalServerError, w.Code)
}