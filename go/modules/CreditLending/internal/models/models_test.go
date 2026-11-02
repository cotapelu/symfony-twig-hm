//go:build ignore

package creditlending

import (
	"context"
	"database/sql"
	"errors"
	"testing"
	"time"

	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/mock"
)

// MockCreditRepository mocks CreditRepository
type MockCreditRepository struct {
	mock.Mock
}

func (m *MockCreditRepository) CreateApplication(a *CreditApplication) error {
	args := m.Called(a)
	if args.Get(0) != nil {
		a.ID = args.Get(0).(int64)
		a.CreatedAt = args.Get(1).(time.Time)
	}
	return args.Error(2)
}

func (m *MockCreditRepository) GetApplication(id int64) (*CreditApplication, error) {
	args := m.Called(id)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).(*CreditApplication), args.Error(1)
}

func (m *MockCreditRepository) GetApplicationsByUser(userID string) ([]*CreditApplication, error) {
	args := m.Called(userID)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).([]*CreditApplication), args.Error(1)
}

func (m *MockCreditRepository) UpdateApplicationStatus(id int64, status string) error {
	args := m.Called(id, status)
	return args.Error(0)
}

func (m *MockCreditRepository) ApproveApplication(id int64, approvedAmount, monthlyPayment, totalRepayment float64) error {
	args := m.Called(id, approvedAmount, monthlyPayment, totalRepayment)
	return args.Error(0)
}

func (m *MockCreditRepository) Disburse(id int64) error {
	args := m.Called(id)
	return args.Error(0)
}

func (m *MockCreditRepository) CreateRepayment(rp *CreditRepayment) error {
	args := m.Called(rp)
	if args.Get(0) != nil {
		rp.ID = args.Get(0).(int64)
		rp.CreatedAt = args.Get(1).(time.Time)
	}
	return args.Error(2)
}

func (m *MockCreditRepository) GetRepayments(appID int64) ([]*CreditRepayment, error) {
	args := m.Called(appID)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).([]*CreditRepayment), args.Error(1)
}

// MockCreditScoreRepository mocks CreditScoreRepository
type MockCreditScoreRepository struct {
	mock.Mock
}

func (m *MockCreditScoreRepository) GetScore(userID string) (*CreditScore, error) {
	args := m.Called(userID)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).(*CreditScore), args.Error(1)
}

// CreditLendingService tests

func TestCreditLendingService_CreateApplication(t *testing.T) {
	ctx := context.Background()
	repo := &MockCreditRepository{}
	scoreRepo := &MockCreditScoreRepository{}
	svc := NewCreditLendingService(repo, scoreRepo)

	now := time.Now()
	app := &CreditApplication{
		UserID:       "user1",
		CreditType:   "personal",
		Amount:       10000,
		Term:         12,
		InterestRate: 0.024,
		Status:       "pending",
	}

	repo.On("CreateApplication", app).Return(int64(1), now, nil).Once()

	created, err := svc.CreateApplication(ctx, app)
	assert.NoError(t, err)
	assert.Equal(t, int64(1), created.ID)
	assert.WithinDuration(t, now, created.CreatedAt, 1*time.Second)
	assert.Equal(t, "user1", created.UserID)

	repo.AssertExpectations(t)
}

func TestCreditLendingService_GetApplication(t *testing.T) {
	ctx := context.Background()
	repo := &MockCreditRepository{}
	scoreRepo := &MockCreditScoreRepository{}
	svc := NewCreditLendingService(repo, scoreRepo)

	app := &CreditApplication{ID: 1, UserID: "user1"}
	repo.On("GetApplication", int64(1)).Return(app, nil)

	result, err := svc.GetApplication(ctx, 1)
	assert.NoError(t, err)
	assert.Equal(t, app, result)

	// Not found
	repo.On("GetApplication", int64(999)).Return(nil, sql.ErrNoRows)
	_, err = svc.GetApplication(ctx, 999)
	assert.Error(t, err)

	repo.AssertExpectations(t)
}

func TestCreditLendingService_GetApplicationsByUser(t *testing.T) {
	ctx := context.Background()
	repo := &MockCreditRepository{}
	scoreRepo := &MockCreditScoreRepository{}
	svc := NewCreditLendingService(repo, scoreRepo)

	apps := []*CreditApplication{{ID: 1, UserID: "user1"}, {ID: 2, UserID: "user1"}}
	repo.On("GetApplicationsByUser", "user1").Return(apps, nil)

	result, err := svc.GetApplicationsByUser(ctx, "user1")
	assert.NoError(t, err)
	assert.Len(t, result, 2)

	repo.AssertExpectations(t)
}

func TestCreditLendingService_UpdateApplicationStatus(t *testing.T) {
	ctx := context.Background()
	repo := &MockCreditRepository{}
	scoreRepo := &MockCreditScoreRepository{}
	svc := NewCreditLendingService(repo, scoreRepo)

	repo.On("UpdateApplicationStatus", int64(1), "approved").Return(nil).Once()

	app, err := svc.UpdateApplicationStatus(ctx, 1, "approved")
	assert.NoError(t, err)
	assert.Equal(t, "approved", app.Status)

	repo.AssertExpectations(t)
}

func TestCreditLendingService_ApproveApplication(t *testing.T) {
	ctx := context.Background()
	repo := &MockCreditRepository{}
	scoreRepo := &MockCreditScoreRepository{}
	svc := NewCreditLendingService(repo, scoreRepo)

	app := &CreditApplication{
		ID:            1,
		Amount:        1000,
		InterestRate:  0.024,
		Term:          12,
		Status:        "pending",
		MonthlyPayment: 89.63,
		TotalRepayment: 1075.56,
	}

	repo.On("GetApplication", int64(1)).Return(app, nil)
	repo.On("ApproveApplication", int64(1), 800.0, 89.63, 1075.56).Return(nil).Once()
	repo.On("GetApplication", int64(1)).Return(app, nil) // after approve

	approved, err := svc.ApproveApplication(ctx, 1, 800.0)
	assert.NoError(t, err)
	assert.Equal(t, "approved", approved.Status)
	assert.Equal(t, 800.0, approved.ApprovedAmount)

	repo.AssertExpectations(t)
}

func TestCreditLendingService_ApproveApplication_AmountExceeds(t *testing.T) {
	ctx := context.Background()
	repo := &MockCreditRepository{}
	scoreRepo := &MockCreditScoreRepository{}
	svc := NewCreditLendingService(repo, scoreRepo)

	app := &CreditApplication{ID: 1, Amount: 1000}
	repo.On("GetApplication", int64(1)).Return(app, nil)

	_, err := svc.ApproveApplication(ctx, 1, 1200.0)
	assert.Error(t, err)
	assert.Contains(t, err.Error(), "approved amount cannot exceed requested amount")
}

func TestCreditLendingService_Disburse(t *testing.T) {
	ctx := context.Background()
	repo := &MockCreditRepository{}
	scoreRepo := &MockCreditScoreRepository{}
	svc := NewCreditLendingService(repo, scoreRepo)

	app := &CreditApplication{ID: 1, Status: "approved"}
	repo.On("GetApplication", int64(1)).Return(app, nil)
	repo.On("Disburse", int64(1)).Return(nil).Once()
	repo.On("GetApplication", int64(1)).Return(app, nil)

	disbursed, err := svc.Disburse(ctx, 1)
	assert.NoError(t, err)
	assert.Equal(t, "disbursed", disbursed.Status)

	repo.AssertExpectations(t)
}

func TestCreditLendingService_Disburse_NotApproved(t *testing.T) {
	ctx := context.Background()
	repo := &MockCreditRepository{}
	scoreRepo := &MockCreditScoreRepository{}
	svc := NewCreditLendingService(repo, scoreRepo)

	app := &CreditApplication{ID: 1, Status: "pending"}
	repo.On("GetApplication", int64(1)).Return(app, nil)

	_, err := svc.Disburse(ctx, 1)
	assert.Error(t, err)
	assert.Contains(t, err.Error(), "application not approved")
}

func TestCreditLendingService_Repay(t *testing.T) {
	ctx := context.Background()
	repo := &MockCreditRepository{}
	scoreRepo := &MockCreditScoreRepository{}
	svc := NewCreditLendingService(repo, scoreRepo)

	app := &CreditApplication{
		ID:             1,
		Status:         "disbursed",
		TotalRepayment: 1000,
	}
	repo.On("GetApplication", int64(1)).Return(app, nil)
	repo.On("GetRepayments", int64(1)).Return([]*CreditRepayment{}, nil)
	repo.On("CreateRepayment", mock.AnythingOfType("*CreditRepayment")).Return(nil).Once().Run(func(args mock.Arguments) {
		rp := args.Get(0).(*CreditRepayment)
		rp.ID = 1
		rp.CreatedAt = time.Now()
	})
	// No existing repayments, after create we may call UpdateApplicationStatus if balance <=0, but here balance remains >0

	repay, err := svc.Repay(ctx, 1, 200.0)
	assert.NoError(t, err)
	assert.Equal(t, 200.0, repay.Amount)
	assert.Within(t, 0.0, repay.Principal, 195.0) // approximation not exact, but principal around 195.3
	assert.True(t, repay.Status == "completed")

	repo.AssertExpectations(t)
}

func TestCreditLendingService_GetCreditScore(t *testing.T) {
	ctx := context.Background()
	repo := &MockCreditRepository{}
	scoreRepo := &MockCreditScoreRepository{}
	svc := NewCreditLendingService(repo, scoreRepo)

	score := &CreditScore{UserID: "user1", Score: 750}
	scoreRepo.On("GetScore", "user1").Return(score, nil)

	result, err := svc.GetCreditScore(ctx, "user1")
	assert.NoError(t, err)
	assert.Equal(t, score, result)
}

func TestCreditLendingService_GetRepayments(t *testing.T) {
	ctx := context.Background()
	repo := &MockCreditRepository{}
	scoreRepo := &MockCreditScoreRepository{}
	svc := NewCreditLendingService(repo, scoreRepo)

	repays := []*CreditRepayment{{ID: 1}, {ID: 2}}
	repo.On("GetRepayments", int64(1)).Return(repays, nil)

	result, err := svc.GetRepayments(ctx, 1)
	assert.NoError(t, err)
	assert.Len(t, result, 2)
}
