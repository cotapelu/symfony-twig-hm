package fraud

import (
	"context"
	"database/sql"
	"errors"
	"time"

	fraudmodels "community.local/modules/FraudDetection/internal/models"
)

type FraudRuleRepositoryImpl struct {
	db *sql.DB
}

func NewFraudRuleRepository(db *sql.DB) *FraudRuleRepositoryImpl {
	return &FraudRuleRepositoryImpl{db: db}
}

func (r *FraudRuleRepositoryImpl) CreateRule(ctx context.Context, rule *fraudmodels.FraudRule) error {
	if r.db == nil {
		return errors.New("database connection is nil")
	}
	query := `INSERT INTO fraud_rules (name, type, condition, action, severity, is_enabled, created_at, updated_at) VALUES ($1, $2, $3, $4, $5, $6, $7, $8) RETURNING id`
	return r.db.QueryRowContext(ctx, query, rule.Name, rule.Type, rule.Condition, rule.Action, rule.Severity, rule.IsEnabled, rule.CreatedAt, rule.UpdatedAt).Scan(&rule.ID)
}

func (r *FraudRuleRepositoryImpl) GetRule(ctx context.Context, id int64) (*fraudmodels.FraudRule, error) {
	if r.db == nil {
		return nil, errors.New("database connection is nil")
	}
	query := `SELECT id, name, type, condition, action, severity, is_enabled, created_at, updated_at FROM fraud_rules WHERE id = $1`
	rule := &fraudmodels.FraudRule{}
	err := r.db.QueryRowContext(ctx, query, id).Scan(&rule.ID, &rule.Name, &rule.Type, &rule.Condition, &rule.Action, &rule.Severity, &rule.IsEnabled, &rule.CreatedAt, &rule.UpdatedAt)
	if err != nil {
		return nil, err
	}
	return rule, nil
}

func (r *FraudRuleRepositoryImpl) GetAllRules(ctx context.Context) ([]*fraudmodels.FraudRule, error) {
	if r.db == nil {
		return nil, errors.New("database connection is nil")
	}
	query := `SELECT id, name, type, condition, action, severity, is_enabled, created_at, updated_at FROM fraud_rules ORDER BY created_at DESC`
	rows, err := r.db.QueryContext(ctx, query)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var rules []*fraudmodels.FraudRule
	for rows.Next() {
		rule := &fraudmodels.FraudRule{}
		err := rows.Scan(&rule.ID, &rule.Name, &rule.Type, &rule.Condition, &rule.Action, &rule.Severity, &rule.IsEnabled, &rule.CreatedAt, &rule.UpdatedAt)
		if err != nil {
			return nil, err
		}
		rules = append(rules, rule)
	}
	return rules, nil
}

func (r *FraudRuleRepositoryImpl) GetActiveRules(ctx context.Context) ([]*fraudmodels.FraudRule, error) {
	if r.db == nil {
		return nil, errors.New("database connection is nil")
	}
	query := `SELECT id, name, type, condition, action, severity, is_enabled, created_at, updated_at FROM fraud_rules WHERE is_enabled = true ORDER BY created_at DESC`
	rows, err := r.db.QueryContext(ctx, query)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var rules []*fraudmodels.FraudRule
	for rows.Next() {
		rule := &fraudmodels.FraudRule{}
		err := rows.Scan(&rule.ID, &rule.Name, &rule.Type, &rule.Condition, &rule.Action, &rule.Severity, &rule.IsEnabled, &rule.CreatedAt, &rule.UpdatedAt)
		if err != nil {
			return nil, err
		}
		rules = append(rules, rule)
	}
	return rules, nil
}

func (r *FraudRuleRepositoryImpl) UpdateRuleStatus(ctx context.Context, ruleID int64, isEnabled bool) error {
	if r.db == nil {
		return errors.New("database connection is nil")
	}
	query := `UPDATE fraud_rules SET is_enabled = $1, updated_at = $2 WHERE id = $3`
	_, err := r.db.ExecContext(ctx, query, isEnabled, time.Now(), ruleID)
	return err
}

func (r *FraudRuleRepositoryImpl) UpdateRule(ctx context.Context, rule *fraudmodels.FraudRule) error {
	if r.db == nil {
		return errors.New("database connection is nil")
	}
	query := `UPDATE fraud_rules SET name = $1, type = $2, condition = $3, action = $4, severity = $5, updated_at = $6 WHERE id = $7`
	_, err := r.db.ExecContext(ctx, query, rule.Name, rule.Type, rule.Condition, rule.Action, rule.Severity, time.Now(), rule.ID)
	if err != nil {
		return err
	}
	return nil
}
