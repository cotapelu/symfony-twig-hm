package handler

import (
	"bytes"
	"context"
	"database/sql"
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"testing"
	"time"

	"github.com/gin-gonic/gin"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/mock"
	"community.local/modules/Escrow/models"
)

// MockEscrowRepository implements repository.EscrowRepository
type MockEscrowRepository struct {
	mock.Mock
}

func (m *MockEscrowRepository) Create(ctx context.Context, escrow *models.Escrow) error {
	args := m.Called(ctx, escrow)
	return args.Error(0)
}

func (m *MockEscrowRepository) GetByID(ctx context.Context, id string) (*models.Escrow, error) {
	args := m.Called(ctx, id)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).(*models.Escrow), args.Error(1)
}

func (m *MockEscrowRepository) Update(ctx context.Context, escrow *models.Escrow) error {
	args := m.Called(ctx, escrow)
	return args.Error(0)
}

// newHandlerWithMock creates an EscrowHandler with the mock repository injected.
func newHandlerWithMock(mockRepo *MockEscrowRepository) *EscrowHandler {
	return &EscrowHandler{repo: mockRepo}
}

func TestNewEscrowHandler(t *testing.T) {
	db, _ := sql.Open("sqlite3", ":memory:")
	h := NewEscrowHandler(db)
	assert.NotNil(t, h)
}

func TestCreateEscrow_Success(t *testing.T) {
	gin.SetMode(gin.TestMode)

	mockRepo := new(MockEscrowRepository)
	mockRepo.On("Create", mock.Anything, mock.AnythingOfType("*models.Escrow")).Return(nil).Once()
	h := newHandlerWithMock(mockRepo)

	router := gin.New()
	router.POST("/api/escrow/create", h.CreateEscrow)

	reqBody := CreateEscrowRequest{
		BuyerID:  "buyer123",
		SellerID: "seller456",
		Amount:   100.0,
		Currency: "USD",
	}
	body, _ := json.Marshal(reqBody)

	req := httptest.NewRequest("POST", "/api/escrow/create", bytes.NewReader(body))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("X-User-ID", "buyer123")

	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
	mockRepo.AssertExpectations(t)
}

func TestCreateEscrow_Unauthorized(t *testing.T) {
	gin.SetMode(gin.TestMode)

	mockRepo := new(MockEscrowRepository)
	h := newHandlerWithMock(mockRepo)

	router := gin.New()
	router.POST("/api/escrow/create", h.CreateEscrow)

	reqBody := CreateEscrowRequest{
		BuyerID:  "buyer123",
		SellerID: "seller456",
		Amount:   100.0,
		Currency: "USD",
	}
	body, _ := json.Marshal(reqBody)

	req := httptest.NewRequest("POST", "/api/escrow/create", bytes.NewReader(body))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("X-User-ID", "other_user")

	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusForbidden, w.Code)
	mockRepo.AssertExpectations(t) // should not have been called
}

func TestGetEscrow_NotFound(t *testing.T) {
	gin.SetMode(gin.TestMode)

	mockRepo := new(MockEscrowRepository)
	mockRepo.On("GetByID", mock.Anything, "nonexistent").Return(nil, nil).Once()
	h := newHandlerWithMock(mockRepo)

	router := gin.New()
	router.GET("/api/escrow/:id", h.GetEscrow)

	req := httptest.NewRequest("GET", "/api/escrow/nonexistent", nil)
	req.Header.Set("X-User-ID", "user123")

	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusNotFound, w.Code)
	mockRepo.AssertExpectations(t)
}

func TestFundEscrow_NotFound(t *testing.T) {
	gin.SetMode(gin.TestMode)

	mockRepo := new(MockEscrowRepository)
	mockRepo.On("GetByID", mock.Anything, "test123").Return(nil, nil).Once()
	mockRepo.On("Update", mock.Anything, mock.AnythingOfType("*models.Escrow")).Return(nil).Maybe()
	h := newHandlerWithMock(mockRepo)

	router := gin.New()
	router.POST("/api/escrow/:id/fund", h.FundEscrow)

	req := httptest.NewRequest("POST", "/api/escrow/test123/fund", nil)
	req.Header.Set("X-User-ID", "buyer123")

	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusNotFound, w.Code)
	mockRepo.AssertExpectations(t)
}

func TestReleaseEscrow_NotFound(t *testing.T) {
	gin.SetMode(gin.TestMode)

	mockRepo := new(MockEscrowRepository)
	mockRepo.On("GetByID", mock.Anything, "test123").Return(nil, nil).Once()
	mockRepo.On("Update", mock.Anything, mock.AnythingOfType("*models.Escrow")).Return(nil).Maybe()
	h := newHandlerWithMock(mockRepo)

	router := gin.New()
	router.POST("/api/escrow/:id/release", h.ReleaseEscrow)

	req := httptest.NewRequest("POST", "/api/escrow/test123/release", nil)
	req.Header.Set("X-User-ID", "seller456")

	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusNotFound, w.Code)
	mockRepo.AssertExpectations(t)
}

func TestRefundEscrow_NotFound(t *testing.T) {
	gin.SetMode(gin.TestMode)

	mockRepo := new(MockEscrowRepository)
	mockRepo.On("GetByID", mock.Anything, "test123").Return(nil, nil).Once()
	mockRepo.On("Update", mock.Anything, mock.AnythingOfType("*models.Escrow")).Return(nil).Maybe()
	h := newHandlerWithMock(mockRepo)

	router := gin.New()
	router.POST("/api/escrow/:id/refund", h.RefundEscrow)

	req := httptest.NewRequest("POST", "/api/escrow/test123/refund", nil)
	req.Header.Set("X-User-ID", "buyer123")

	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusNotFound, w.Code)
	mockRepo.AssertExpectations(t)
}

func TestGetAuthenticatedUserID(t *testing.T) {
	gin.SetMode(gin.TestMode)

	w := httptest.NewRecorder()
	c, _ := gin.CreateTestContext(w)
	c.Set("user_id", "test_user")

	userID, ok := getAuthenticatedUserID(c)
	assert.True(t, ok)
	assert.Equal(t, "test_user", userID)
}

func TestIsAdmin(t *testing.T) {
	gin.SetMode(gin.TestMode)

	w := httptest.NewRecorder()
	c, _ := gin.CreateTestContext(w)

	assert.False(t, isAdmin(c))

	c.Set("user_roles", []string{"ROLE_ADMIN"})
	assert.True(t, isAdmin(c))

	c.Set("user_roles", []string{"USER"})
	assert.False(t, isAdmin(c))
}

func TestEscrowModel(t *testing.T) {
	escrow := &models.Escrow{
		ID:        "test123",
		BuyerID:   "buyer1",
		SellerID:  "seller1",
		Amount:    100.0,
		Currency:  "USD",
		Status:    "created",
		CreatedAt: time.Now(),
		UpdatedAt: time.Now(),
	}

	assert.Equal(t, "test123", escrow.ID)
	assert.Equal(t, "buyer1", escrow.BuyerID)
	assert.Equal(t, "created", escrow.Status)
}
