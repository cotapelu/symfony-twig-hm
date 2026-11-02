package frauddetection

import (
	"context"
	"database/sql"
	"testing"
	"time"

	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/mock"

	fraudmodels "community.local/modules/FraudDetection/internal/models"
)

// MockFraudRepository mocks the FraudRepository interface
type MockFraudRepository struct {
	mock.Mock
}

func (m *MockFraudRepository) CreateAlert(ctx context.Context, alert *fraudmodels.FraudAlert) error {
	args := m.Called(ctx, alert)
	return args.Error(0)
}

func (m *MockFraudRepository) GetAlertByID(ctx context.Context, id int64) (*fraudmodels.FraudAlert, error) {
	args := m.Called(ctx, id)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).(*fraudmodels.FraudAlert), args.Error(1)
}

func (m *MockFraudRepository) GetAlertsByUser(ctx context.Context, userID string) ([]*fraudmodels.FraudAlert, error) {
	args := m.Called(ctx, userID)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).([]*fraudmodels.FraudAlert), args.Error(1)
}

func (m *MockFraudRepository) GetOpenAlerts(ctx context.Context) ([]*fraudmodels.FraudAlert, error) {
	args := m.Called(ctx)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).([]*fraudmodels.FraudAlert), args.Error(1)
}

func (m *MockFraudRepository) ResolveAlert(ctx context.Context, alertID int64, resolvedBy, resolution string) error {
	args := m.Called(ctx, alertID, resolvedBy, resolution)
	return args.Error(0)
}

func (m *MockFraudRepository) CreateRule(ctx context.Context, rule *fraudmodels.FraudRule) error {
	args := m.Called(ctx, rule)
	return args.Error(0)
}

func (m *MockFraudRepository) GetActiveRules(ctx context.Context) ([]*fraudmodels.FraudRule, error) {
	args := m.Called(ctx)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).([]*fraudmodels.FraudRule), args.Error(1)
}

func (m *MockFraudRepository) GetRuleByID(ctx context.Context, id int64) (*fraudmodels.FraudRule, error) {
	args := m.Called(ctx, id)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).(*fraudmodels.FraudRule), args.Error(1)
}

func (m *MockFraudRepository) UpdateRule(ctx context.Context, rule *fraudmodels.FraudRule) error {
	args := m.Called(ctx, rule)
	return args.Error(0)
}

func (m *MockFraudRepository) DeleteRule(ctx context.Context, id int64) error {
	args := m.Called(ctx, id)
	return args.Error(0)
}

func TestNewFraudService(t *testing.T) {
	repo := &MockFraudRepository{}
	svc := NewFraudService(repo)
	if svc == nil {
		t.Fatal("NewFraudService returned nil")
	}
}

// Helper to create a fraud rule
func newRule(id int64, name string, ruleType string, condition string, riskScore float64, active bool) *fraudmodels.FraudRule {
	return &fraudmodels.FraudRule{
		ID:        id,
		Name:      name,
		RuleType:  ruleType,
		Condition: condition,
		RiskScore: riskScore,
		IsActive:  active,
		CreatedAt: time.Now(),
	}
}

func TestFraudService_AnalyzeTransaction_ValidationErrors(t *testing.T) {
	repo := &MockFraudRepository{}
	svc := NewFraudService(repo)

	tests := []struct {
		name    string
		txCtx   TransactionContext
		wantErr error
	}{
		{
			name: "empty user ID",
			txCtx: TransactionContext{
				UserID:          "",
				Amount:          100,
				TransactionType: "payment",
			},
			wantErr: ErrUserIDRequired,
		},
		{
			name: "negative amount",
			txCtx: TransactionContext{
				UserID:          "user1",
				Amount:          -50,
				TransactionType: "payment",
			},
			wantErr: ErrInvalidAmount,
		},
		{
			name: "missing transaction type",
			txCtx: TransactionContext{
				UserID:          "user1",
				Amount:          100,
				TransactionType: "",
			},
			wantErr: ErrTransactionRequired,
		},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			alert, score, err := svc.AnalyzeTransaction(context.Background(), tt.txCtx)
			assert.ErrorIs(t, err, tt.wantErr)
			assert.Nil(t, alert)
			assert.Zero(t, score)
		})
	}
}

func TestFraudService_AnalyzeTransaction_CacheHit(t *testing.T) {
	ctx := context.Background()
	repo := &MockFraudRepository{}
	svc := NewFraudService(repo)

	// Prepopulate cache
	cacheKey := "user1:txn123"
	svc.CacheResult(cacheKey, &IdempotencyResult{
		RiskScore: 50,
		Alert: &fraudmodels.FraudAlert{
			UserID:        "user1",
			TransactionID: "txn123",
			RiskLevel:     "medium",
			Score:        50,
		},
	})

	txCtx := TransactionContext{
		UserID:          "user1",
		TransactionID:   "txn123",
		Amount:          200,
		TransactionType: "payment",
	}

	alert, score, err := svc.AnalyzeTransaction(ctx, txCtx)
	assert.NoError(t, err)
	assert.NotNil(t, alert)
	assert.Equal(t, 50.0, score)
	// Verify repository not called
	repo.AssertNotCalled(t, "GetActiveRules")
}

func TestFraudService_AnalyzeTransaction_RuleTriggeredCreatesAlert(t *testing.T) {
	ctx := context.Background()
	repo := &MockFraudRepository{}
	svc := NewFraudService(repo)

	rule := newRule(1, "Large amount", "amount", `10`, 50, true)

	repo.On("GetActiveRules", ctx).Return([]*fraudmodels.FraudRule{rule}, nil).Once()
	repo.On("CreateAlert", ctx, mock.Anything).Return(nil).Once()

	txCtx := TransactionContext{
		UserID:          "user1",
		TransactionID:   "txn123",
		Amount:          100,
		TransactionType: "payment",
		PaymentMethod:   "card",
		TimeOfDay:       14,
		History: TransactionHistory{
			TotalTransactions: 5,
			TotalAmount:       500,
			AvgTransactionAmt:  100,
			MaxTransactionAmt:  200,
			DaysSinceFirstTx:  30,
			Countries:         []string{"US"},
		},
	}

	alert, score, err := svc.AnalyzeTransaction(ctx, txCtx)
	assert.NoError(t, err)
	assert.NotNil(t, alert)
	assert.GreaterOrEqual(t, score, 40.0)
	assert.Equal(t, "medium", alert.RiskLevel)

	repo.AssertExpectations(t)
}

func TestFraudService_AnalyzeTransaction_NoAlertWhenScoreLow(t *testing.T) {
	ctx := context.Background()
	repo := &MockFraudRepository{}
	svc := NewFraudService(repo)

	rule := newRule(1, "Small risk", "amount", `1000`, 10, true)

	repo.On("GetActiveRules", ctx).Return([]*fraudmodels.FraudRule{rule}, nil).Once()
	// No CreateAlert expected

	txCtx := TransactionContext{
		UserID:          "user1",
		TransactionID:   "txn123",
		Amount:          50,
		TransactionType: "payment",
	}

	alert, score, err := svc.AnalyzeTransaction(ctx, txCtx)
	assert.NoError(t, err)
	assert.Nil(t, alert)
	assert.Less(t, score, 40.0)

	repo.AssertExpectations(t)
}

func TestFraudService_CreateAlert(t *testing.T) {
	ctx := context.Background()
	repo := &MockFraudRepository{}
	svc := NewFraudService(repo)

	repo.On("CreateAlert", ctx, mock.Anything).Return(nil).Once()

	result, err := svc.CreateAlert(ctx, "user1", "txn123", "transaction", "high", "Test details")
	assert.NoError(t, err)
	assert.NotNil(t, result)
	assert.Equal(t, "user1", result.UserID)
	assert.Equal(t, "high", result.RiskLevel)
	assert.InDelta(t, 70, result.Score, 0.001)

	repo.AssertExpectations(t)
}

func TestFraudService_CreateAlert_InvalidInput(t *testing.T) {
	repo := &MockFraudRepository{}
	svc := NewFraudService(repo)

	// Empty userID
	_, err := svc.CreateAlert(context.Background(), "", "txn123", "transaction", "", "")
	assert.ErrorIs(t, err, ErrUserIDRequired)
}

func TestFraudService_GetAlert(t *testing.T) {
	ctx := context.Background()
	repo := &MockFraudRepository{}
	svc := NewFraudService(repo)

	alert := &fraudmodels.FraudAlert{ID: 1, UserID: "user1"}
	repo.On("GetAlertByID", ctx, int64(1)).Return(alert, nil).Once()

	result, err := svc.GetAlert(ctx, 1)
	assert.NoError(t, err)
	assert.Equal(t, alert, result)

	// Not found
	repo.On("GetAlertByID", ctx, int64(999)).Return(nil, sql.ErrNoRows).Once()
	_, err = svc.GetAlert(ctx, 999)
	assert.ErrorIs(t, err, ErrAlertNotFound)

	repo.AssertExpectations(t)
}

func TestFraudService_GetUserAlerts(t *testing.T) {
	ctx := context.Background()
	repo := &MockFraudRepository{}
	svc := NewFraudService(repo)

	alerts := []*fraudmodels.FraudAlert{
		{ID: 1, UserID: "user1"},
		{ID: 2, UserID: "user1"},
	}
	repo.On("GetAlertsByUser", ctx, "user1").Return(alerts, nil).Once()

	result, err := svc.GetUserAlerts(ctx, "user1")
	assert.NoError(t, err)
	assert.Equal(t, alerts, result)

	repo.AssertExpectations(t)
}

func TestFraudService_GetOpenAlerts(t *testing.T) {
	ctx := context.Background()
	repo := &MockFraudRepository{}
	svc := NewFraudService(repo)

	alerts := []*fraudmodels.FraudAlert{
		{ID: 1, Status: "open"},
		{ID: 2, Status: "open"},
	}
	repo.On("GetOpenAlerts", ctx).Return(alerts, nil).Once()

	result, err := svc.GetOpenAlerts(ctx)
	assert.NoError(t, err)
	assert.Equal(t, alerts, result)

	repo.AssertExpectations(t)
}

func TestFraudService_ResolveAlert(t *testing.T) {
	ctx := context.Background()
	repo := &MockFraudRepository{}
	svc := NewFraudService(repo)

	repo.On("ResolveAlert", ctx, int64(1), "admin_user", "false_positive").Return(nil).Once()

	err := svc.ResolveAlert(ctx, 1, "admin_user", "false_positive")
	assert.NoError(t, err)

	repo.AssertExpectations(t)
}

func TestFraudService_CreateRule(t *testing.T) {
	ctx := context.Background()
	repo := &MockFraudRepository{}
	svc := NewFraudService(repo)

	repo.On("CreateRule", ctx, mock.Anything).Return(nil).Once()

	err := svc.CreateRule(ctx, "Test Rule", "desc", "amount", `{"threshold":100}`, "some action", 50)
	assert.NoError(t, err)

	repo.AssertExpectations(t)
}

func TestFraudService_GetActiveRules(t *testing.T) {
	ctx := context.Background()
	repo := &MockFraudRepository{}
	svc := NewFraudService(repo)

	rules := []*fraudmodels.FraudRule{
		{ID: 1, Name: "Rule 1", IsActive: true},
		{ID: 2, Name: "Rule 2", IsActive: true},
	}
	repo.On("GetActiveRules", ctx).Return(rules, nil).Once()

	result, err := svc.GetActiveRules(ctx)
	assert.NoError(t, err)
	assert.Equal(t, rules, result)

	repo.AssertExpectations(t)
}

func TestFraudService_GetRule(t *testing.T) {
	ctx := context.Background()
	repo := &MockFraudRepository{}
	svc := NewFraudService(repo)

	rule := &fraudmodels.FraudRule{ID: 1, Name: "Test Rule"}
	repo.On("GetRuleByID", ctx, int64(1)).Return(rule, nil).Once()

	result, err := svc.GetRule(ctx, 1)
	assert.NoError(t, err)
	assert.Equal(t, rule, result)

	// Not found
	repo.On("GetRuleByID", ctx, int64(999)).Return(nil, sql.ErrNoRows).Once()
	_, err = svc.GetRule(ctx, 999)
	assert.ErrorIs(t, err, ErrRuleNotFound)

	repo.AssertExpectations(t)
}

func TestFraudService_UpdateRule(t *testing.T) {
	ctx := context.Background()
	repo := &MockFraudRepository{}
	svc := NewFraudService(repo)

	rule := &fraudmodels.FraudRule{ID: 1, Name: "Updated Rule"}
	repo.On("UpdateRule", ctx, mock.Anything).Return(nil).Once()

	err := svc.UpdateRule(ctx, rule)
	assert.NoError(t, err)

	repo.AssertExpectations(t)
}

func TestFraudService_DeleteRule(t *testing.T) {
	ctx := context.Background()
	repo := &MockFraudRepository{}
	svc := NewFraudService(repo)

	repo.On("DeleteRule", ctx, int64(1)).Return(nil).Once()

	err := svc.DeleteRule(ctx, 1)
	assert.NoError(t, err)

	repo.AssertExpectations(t)
}

func TestIdempotencyResult_GetCachedResult(t *testing.T) {
	svc := &FraudService{
		idempotency: make(map[string]*IdempotencyResult),
	}

	result := &IdempotencyResult{
		RiskScore: 75,
		Alert: &fraudmodels.FraudAlert{
			UserID: "user1",
		},
	}
	svc.CacheResult("user1:txn123", result)

	cached := svc.GetCachedResult("user1:txn123")
	assert.NotNil(t, cached)
	assert.InDelta(t, result.RiskScore, cached.RiskScore, 0.001)
	assert.Equal(t, result.Alert.UserID, cached.Alert.UserID)

	// Non-existent key
	assert.Nil(t, svc.GetCachedResult("unknown"))
}
