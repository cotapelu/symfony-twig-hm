package frauddetection

import (
	"context"
	"database/sql"
	"encoding/json"
	"errors"
	"fmt"
	"sync"
	"time"

	fraudmodels "community.local/modules/FraudDetection/internal/models"
)

var (
	ErrAlertNotFound       = errors.New("alert not found")
	ErrRuleNotFound        = errors.New("rule not found")
	ErrInvalidRiskScore    = errors.New("invalid risk score")
	ErrInvalidAmount       = errors.New("invalid amount")
	ErrUserIDRequired      = errors.New("user ID is required")
	ErrTransactionRequired = errors.New("transaction type is required")
)

type FraudRepository interface {
	CreateAlert(ctx context.Context, alert *fraudmodels.FraudAlert) error
	GetAlertByID(ctx context.Context, id int64) (*fraudmodels.FraudAlert, error)
	GetAlertsByUser(ctx context.Context, userID string) ([]*fraudmodels.FraudAlert, error)
	GetOpenAlerts(ctx context.Context) ([]*fraudmodels.FraudAlert, error)
	ResolveAlert(ctx context.Context, id int64, resolvedBy, resolution string) error
	CreateRule(ctx context.Context, rule *fraudmodels.FraudRule) error
	GetActiveRules(ctx context.Context) ([]*fraudmodels.FraudRule, error)
	GetRuleByID(ctx context.Context, id int64) (*fraudmodels.FraudRule, error)
	UpdateRule(ctx context.Context, rule *fraudmodels.FraudRule) error
	DeleteRule(ctx context.Context, id int64) error
}

type FraudService struct {
	repo        FraudRepository
	idempotency map[string]*IdempotencyResult
	mu          sync.RWMutex
}

type IdempotencyResult struct {
	RiskScore float64
	Alert     *fraudmodels.FraudAlert
	CreatedAt time.Time
}

func NewFraudService(repo FraudRepository) *FraudService {
	return &FraudService{
		repo:        repo,
		idempotency: make(map[string]*IdempotencyResult),
	}
}

func (s *FraudService) AnalyzeTransaction(ctx context.Context, txCtx TransactionContext) (*fraudmodels.FraudAlert, float64, error) {
	// Input validation
	if err := s.validateTransactionContext(txCtx); err != nil {
		return nil, 0, err
	}

	// Check idempotency cache
	cacheKey := fmt.Sprintf("%s:%s", txCtx.UserID, txCtx.TransactionID)
	if cached := s.GetCachedResult(cacheKey); cached != nil {
		return cached.Alert, cached.RiskScore, nil
	}

	rules, err := s.repo.GetActiveRules(ctx)
	if err != nil {
		return nil, 0, fmt.Errorf("failed to get active rules: %w", err)
	}

	result, err := s.calculateRiskScore(txCtx, rules)
	if err != nil {
		return nil, 0, err
	}

	alert, err := s.createFraudAlertIfNeeded(ctx, txCtx, result)
	if err != nil {
		return nil, 0, err
	}

	// Cache result
	if alert != nil {
		s.CacheResult(cacheKey, &IdempotencyResult{
			RiskScore: result.TotalScore,
			Alert:     alert,
			CreatedAt: time.Now(),
		})
	}

	return alert, result.TotalScore, nil
}

func (s *FraudService) validateTransactionContext(ctx TransactionContext) error {
	if ctx.UserID == "" {
		return ErrUserIDRequired
	}
	if ctx.Amount < 0 {
		return ErrInvalidAmount
	}
	if ctx.TransactionType == "" {
		return ErrTransactionRequired
	}
	return nil
}

type RiskCalculationResult struct {
	TotalScore     float64
	TriggeredRules []string
}

func (s *FraudService) calculateRiskScore(ctx TransactionContext, rules []*fraudmodels.FraudRule) (RiskCalculationResult, error) {
	totalScore := 0.0
	var triggeredRules []string

	for _, rule := range rules {
		score := s.evaluateRule(rule, ctx)
		if score > 0 {
			totalScore += score
			triggeredRules = append(triggeredRules, fmt.Sprintf("%s: %.1f", rule.Name, score))
		}
	}

	// Apply additional risk factors
	if err := s.applyAdditionalRiskFactors(ctx, &totalScore, &triggeredRules); err != nil {
		return RiskCalculationResult{}, err
	}

	return RiskCalculationResult{
		TotalScore:     totalScore,
		TriggeredRules: triggeredRules,
	}, nil
}

func (s *FraudService) applyAdditionalRiskFactors(ctx TransactionContext, totalScore *float64, triggeredRules *[]string) error {
	avgAmt := ctx.History.AvgTransactionAmt
	if ctx.History.TotalTransactions > 0 && avgAmt > 0 && ctx.Amount > avgAmt*10 {
		*totalScore += 20
		*triggeredRules = append(*triggeredRules, fmt.Sprintf("High amount (%.1fx avg): 20", ctx.Amount/avgAmt))
	}

	if len(ctx.History.Countries) > 3 {
		*totalScore += 15
		*triggeredRules = append(*triggeredRules, "Multiple countries: 15")
	}

	if ctx.TimeOfDay < 6 || ctx.TimeOfDay > 23 {
		*totalScore += 10
		*triggeredRules = append(*triggeredRules, fmt.Sprintf("Unusual time (%d:00): 10", ctx.TimeOfDay))
	}

	return nil
}

func (s *FraudService) createFraudAlertIfNeeded(ctx context.Context, txCtx TransactionContext, result RiskCalculationResult) (*fraudmodels.FraudAlert, error) {
	if result.TotalScore < 40 {
		return nil, nil
	}

	alert := &fraudmodels.FraudAlert{
		UserID:        txCtx.UserID,
		TransactionID: fmt.Sprintf("TXN-%d", time.Now().UnixNano()),
		Type:          "transaction",
		RiskLevel:     s.determineRiskLevel(result.TotalScore),
		Score:         result.TotalScore,
		Details:       fmt.Sprintf("Triggered: %v", result.TriggeredRules),
		Status:        "open",
		CreatedAt:     time.Now(),
	}

	if err := s.repo.CreateAlert(ctx, alert); err != nil {
		return nil, fmt.Errorf("failed to create fraud alert: %w", err)
	}

	return alert, nil
}

func (s *FraudService) determineRiskLevel(score float64) string {
	switch {
	case score >= 80:
		return "critical"
	case score >= 60:
		return "high"
	case score >= 40:
		return "medium"
	default:
		return "low"
	}
}

func (s *FraudService) evaluateRule(rule *fraudmodels.FraudRule, ctx TransactionContext) float64 {
	switch rule.RuleType {
	case "amount":
		var threshold float64
		if err := json.Unmarshal([]byte(rule.Condition), &threshold); err != nil {
			return 0
		}
		if ctx.Amount > threshold {
			return rule.RiskScore
		}
	case "frequency":
		var maxCount int
		if err := json.Unmarshal([]byte(rule.Condition), &maxCount); err != nil {
			return 0
		}
		if ctx.History.TotalTransactions > maxCount {
			return rule.RiskScore
		}
	case "location":
		var countries []string
		if err := json.Unmarshal([]byte(rule.Condition), &countries); err != nil {
			return 0
		}
		for _, c := range countries {
			for _, uc := range ctx.History.Countries {
				if c == uc {
					return rule.RiskScore
				}
			}
		}
	case "payment_method":
		var methods []string
		if err := json.Unmarshal([]byte(rule.Condition), &methods); err != nil {
			return 0
		}
		for _, m := range methods {
			if m == ctx.PaymentMethod {
				return rule.RiskScore
			}
		}
	}
	return 0
}

func (s *FraudService) CreateAlert(ctx context.Context, userID, txnID, alertType, riskLevel, details string) (*fraudmodels.FraudAlert, error) {
	// Input validation
	if userID == "" {
		return nil, ErrUserIDRequired
	}
	if riskLevel == "" {
		riskLevel = "medium"
	}

	score := map[string]float64{"low": 30, "medium": 50, "high": 70, "critical": 90}[riskLevel]

	alert := &fraudmodels.FraudAlert{
		UserID:        userID,
		TransactionID: txnID,
		Type:          alertType,
		RiskLevel:     riskLevel,
		Score:         score,
		Details:       details,
		Status:        "open",
		CreatedAt:     time.Now(),
	}

	if err := s.repo.CreateAlert(ctx, alert); err != nil {
		return nil, err
	}

	return alert, nil
}

func (s *FraudService) GetAlert(ctx context.Context, id int64) (*fraudmodels.FraudAlert, error) {
	alert, err := s.repo.GetAlertByID(ctx, id)
	if err != nil {
		if errors.Is(err, sql.ErrNoRows) {
			return nil, ErrAlertNotFound
		}
		return nil, err
	}
	return alert, nil
}

func (s *FraudService) GetOpenAlerts(ctx context.Context) ([]*fraudmodels.FraudAlert, error) {
	return s.repo.GetOpenAlerts(ctx)
}

func (s *FraudService) GetUserAlerts(ctx context.Context, userID string) ([]*fraudmodels.FraudAlert, error) {
	if userID == "" {
		return nil, ErrUserIDRequired
	}
	return s.repo.GetAlertsByUser(ctx, userID)
}

func (s *FraudService) ResolveAlert(ctx context.Context, alertID int64, resolvedBy, resolution string) error {
	if alertID <= 0 {
		return ErrAlertNotFound
	}
	if resolvedBy == "" {
		return errors.New("resolved by is required")
	}
	return s.repo.ResolveAlert(ctx, alertID, resolvedBy, resolution)
}

func (s *FraudService) CreateRule(ctx context.Context, name, desc, ruleType, condition, action string, score float64) error {
	// Input validation
	if name == "" {
		return errors.New("rule name is required")
	}
	if ruleType == "" {
		return errors.New("rule type is required")
	}
	if score < 0 || score > 100 {
		return ErrInvalidRiskScore
	}

	rule := &fraudmodels.FraudRule{
		Name:        name,
		Description: desc,
		RuleType:    ruleType,
		Condition:   condition,
		Action:      action,
		RiskScore:   score,
		IsActive:    true,
		CreatedAt:   time.Now(),
	}
	// Map to repository fields
	rule.Type = rule.RuleType
	rule.Severity = rule.RiskScore
	rule.IsEnabled = rule.IsActive

	return s.repo.CreateRule(ctx, rule)
}

func (s *FraudService) GetActiveRules(ctx context.Context) ([]*fraudmodels.FraudRule, error) {
	return s.repo.GetActiveRules(ctx)
}

func (s *FraudService) GetRule(ctx context.Context, id int64) (*fraudmodels.FraudRule, error) {
	rule, err := s.repo.GetRuleByID(ctx, id)
	if err != nil {
		if errors.Is(err, sql.ErrNoRows) {
			return nil, ErrRuleNotFound
		}
		return nil, err
	}
	return rule, nil
}

func (s *FraudService) UpdateRule(ctx context.Context, rule *fraudmodels.FraudRule) error {
	if rule.ID <= 0 {
		return ErrRuleNotFound
	}
	return s.repo.UpdateRule(ctx, rule)
}

func (s *FraudService) DeleteRule(ctx context.Context, id int64) error {
	if id <= 0 {
		return ErrRuleNotFound
	}
	return s.repo.DeleteRule(ctx, id)
}

func (s *FraudService) GetCachedResult(key string) *IdempotencyResult {
	s.mu.RLock()
	defer s.mu.RUnlock()

	result, exists := s.idempotency[key]
	if !exists {
		return nil
	}

	if time.Since(result.CreatedAt) > 24*time.Hour {
		return nil
	}

	return result
}

func (s *FraudService) CacheResult(key string, result *IdempotencyResult) {
	s.mu.Lock()
	defer s.mu.Unlock()

	s.idempotency[key] = &IdempotencyResult{
		RiskScore: result.RiskScore,
		Alert:     result.Alert,
		CreatedAt: time.Now(),
	}
}

// GetFraudReports generates fraud reports (stub implementation)
func (s *FraudService) GetFraudReports(ctx context.Context, startDate, endDate time.Time) ([]map[string]interface{}, error) {
	// Stub: return empty report
	return []map[string]interface{}{}, nil
}

type TransactionContext struct {
	UserID          string
	TransactionID   string
	Amount          float64
	TransactionType string
	PaymentMethod   string
	IPAddress       string
	DeviceID        string
	Location        string
	TimeOfDay       int
	History         TransactionHistory
}

type TransactionHistory struct {
	TotalTransactions int
	TotalAmount       float64
	AvgTransactionAmt float64
	MaxTransactionAmt float64
	DaysSinceFirstTx  int
	Countries         []string
}