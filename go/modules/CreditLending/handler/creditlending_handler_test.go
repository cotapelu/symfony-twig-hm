//go:build ignore

package handler

import (
	"bytes"
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"testing"

	"github.com/gin-gonic/gin"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/mock"

	creditlending "community.local/modules/CreditLending/internal/models"
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

func TestCreateApplication_Unauthorized(t *testing.T) {
	gin.SetMode(gin.TestMode)
	
	h := CreditLendingHandler{}
	
	router := gin.New()
	router.POST("/applications", h.CreateApplication)
	
	reqBody := map[string]interface{}{
		"user_id":     "user123",
		"credit_type": "personal",
		"amount":      1000,
		"term":        12,
	}
	body, _ := json.Marshal(reqBody)
	
	req := httptest.NewRequest("POST", "/applications", bytes.NewReader(body))
	req.Header.Set("Content-Type", "application/json")
	// No auth = unauthorized
	
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)
	
	assert.Equal(t, http.StatusUnauthorized, w.Code)
}

func TestCreateApplication_BadRequest(t *testing.T) {
	gin.SetMode(gin.TestMode)
	
	h := CreditLendingHandler{}
	
	router := gin.New()
	router.POST("/applications", h.CreateApplication)
	
	req := httptest.NewRequest("POST", "/applications", bytes.NewReader([]byte{}))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("X-User-ID", "user123")
	
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)
	
	assert.Equal(t, http.StatusBadRequest, w.Code)
}

func TestGetApplication_Unauthorized(t *testing.T) {
	gin.SetMode(gin.TestMode)
	
	h := CreditLendingHandler{}
	
	router := gin.New()
	router.GET("/applications/:id", h.GetApplication)
	
	req := httptest.NewRequest("GET", "/applications/123", nil)
	// No auth
	
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)
	
	assert.Equal(t, http.StatusUnauthorized, w.Code)
}

func TestGetUserApplications_Unauthorized(t *testing.T) {
	gin.SetMode(gin.TestMode)
	
	h := CreditLendingHandler{}
	
	router := gin.New()
	router.GET("/applications/user/:user_id", h.GetUserApplications)
	
	req := httptest.NewRequest("GET", "/applications/user/user123", nil)
	// No auth
	
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)
	
	assert.Equal(t, http.StatusUnauthorized, w.Code)
}

func TestApproveApplication_Unauthorized(t *testing.T) {
	gin.SetMode(gin.TestMode)
	
	h := CreditLendingHandler{}
	
	router := gin.New()
	router.POST("/applications/:id/approve", h.ApproveApplication)
	
	reqBody := map[string]interface{}{
		"approved_amount": 1000,
	}
	body, _ := json.Marshal(reqBody)
	
	req := httptest.NewRequest("POST", "/applications/123/approve", bytes.NewReader(body))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("X-User-ID", "user123")
	// Need admin role for approve
	
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)
	
	assert.Equal(t, http.StatusForbidden, w.Code)
}

func TestDisburse_Unauthorized(t *testing.T) {
	gin.SetMode(gin.TestMode)
	
	h := CreditLendingHandler{}
	
	router := gin.New()
	router.POST("/applications/:id/disburse", h.Disburse)
	
	req := httptest.NewRequest("POST", "/applications/123/disburse", nil)
	req.Header.Set("X-User-ID", "user123")
	// Need admin role
	
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)
	
	assert.Equal(t, http.StatusForbidden, w.Code)
}

func TestRepay_Unauthorized(t *testing.T) {
	gin.SetMode(gin.TestMode)
	
	h := CreditLendingHandler{}
	
	router := gin.New()
	router.POST("/applications/:id/repay", h.Repay)
	
	reqBody := map[string]interface{}{
		"amount": 100,
	}
	body, _ := json.Marshal(reqBody)
	
	req := httptest.NewRequest("POST", "/applications/123/repay", bytes.NewReader(body))
	req.Header.Set("Content-Type", "application/json")
	// No auth
	
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)
	
	assert.Equal(t, http.StatusUnauthorized, w.Code)
}

func TestGetRepayments_Unauthorized(t *testing.T) {
	gin.SetMode(gin.TestMode)
	
	h := CreditLendingHandler{}
	
	router := gin.New()
	router.GET("/applications/:id/repayments", h.GetRepayments)
	
	req := httptest.NewRequest("GET", "/applications/123/repayments", nil)
	// No auth
	
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)
	
	assert.Equal(t, http.StatusUnauthorized, w.Code)
}

func TestGetCreditScore_Unauthorized(t *testing.T) {
	gin.SetMode(gin.TestMode)
	
	h := CreditLendingHandler{}
	
	router := gin.New()
	router.GET("/score/:user_id", h.GetCreditScore)
	
	req := httptest.NewRequest("GET", "/score/user123", nil)
	// No auth
	
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)
	
	assert.Equal(t, http.StatusUnauthorized, w.Code)
}

func TestApproveApplication_NoIdempotencyKey(t *testing.T) {
	gin.SetMode(gin.TestMode)
	
	h := CreditLendingHandler{}
	
	router := gin.New()
	router.POST("/applications/:id/approve", h.ApproveApplication)
	
	reqBody := map[string]interface{}{
		"approved_amount": 1000,
	}
	body, _ := json.Marshal(reqBody)
	
	req := httptest.NewRequest("POST", "/applications/123/approve", bytes.NewReader(body))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("X-User-ID", "admin")
	req.Header.Set("X-User-Role", "ROLE_ADMIN")
	// No Idempotency-Key
	
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)
	
	assert.Equal(t, http.StatusBadRequest, w.Code)
}

func TestRepay_NoIdempotencyKey(t *testing.T) {
	gin.SetMode(gin.TestMode)
	
	h := CreditLendingHandler{}
	
	router := gin.New()
	router.POST("/applications/:id/repay", h.Repay)
	
	reqBody := map[string]interface{}{
		"amount": 100,
	}
	body, _ := json.Marshal(reqBody)
	
	req := httptest.NewRequest("POST", "/applications/123/repay", bytes.NewReader(body))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("X-User-ID", "user123")
	// No Idempotency-Key
	
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)
	
	assert.Equal(t, http.StatusBadRequest, w.Code)
}

func TestIsAdmin(t *testing.T) {
	gin.SetMode(gin.TestMode)
	
	w := httptest.NewRecorder()
	c, _ := gin.CreateTestContext(w)
	
	assert.False(t, isAdmin(c))
	
	c.Set("user_roles", []string{"admin"})
	assert.True(t, isAdmin(c))
	
	c.Set("user_roles", []string{"ROLE_ADMIN"})
	assert.True(t, isAdmin(c))
	
	c.Set("user_roles", []string{"USER"})
	assert.False(t, isAdmin(c))
}

func TestRequireAuth(t *testing.T) {
	gin.SetMode(gin.TestMode)
	
	w := httptest.NewRecorder()
	c, _ := gin.CreateTestContext(w)
	
	_, ok := requireAuth(c)
	assert.False(t, ok)
	assert.Equal(t, http.StatusUnauthorized, w.Code)
	
	c.Set("user_id", "user123")
	userID, ok := requireAuth(c)
	assert.True(t, ok)
	assert.Equal(t, "user123", userID)
}