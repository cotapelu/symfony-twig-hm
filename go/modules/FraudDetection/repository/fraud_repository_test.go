package fraud

import (
	"context"
	"database/sql"
	"testing"
	"time"

	"github.com/DATA-DOG/go-sqlmock"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"

	fraudmodels "community.local/modules/FraudDetection/internal/models"
)

func newMockDB() (*sql.DB, sqlmock.Sqlmock) {
	db, mock, err := sqlmock.New()
	if err != nil {
		panic(err)
	}
	return db, mock
}

func TestFraudCaseRepositoryImpl_CreateCase(t *testing.T) {
	db, mock := newMockDB()
	repo := NewFraudCaseRepository(db)

	now := time.Now()
	caseObj := &fraudmodels.FraudCase{
		AlertID:     123,
		UserID:      "user-1",
		Status:      "open",
		Description: "Suspicious activity",
		Resolution:  "",
		CreatedAt:   now,
		UpdatedAt:   now,
	}
	returnedID := int64(456)
	query := `INSERT INTO fraud_cases \(alert_id, user_id, status, description, resolution, created_at, updated_at\) VALUES \(\$1, \$2, \$3, \$4, \$5, \$6, \$7\) RETURNING id`
	mock.ExpectQuery(query).WithArgs(
		caseObj.AlertID, caseObj.UserID, caseObj.Status,
		caseObj.Description, caseObj.Resolution,
		caseObj.CreatedAt, caseObj.UpdatedAt,
	).WillReturnRows(sqlmock.NewRows([]string{"id"}).AddRow(returnedID))

	err := repo.CreateCase(context.Background(), caseObj)
	require.NoError(t, err)
	assert.Equal(t, returnedID, caseObj.ID)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestFraudCaseRepositoryImpl_GetCase_Found(t *testing.T) {
	db, mock := newMockDB()
	repo := NewFraudCaseRepository(db)

	now := time.Now()
	caseObj := &fraudmodels.FraudCase{
		ID:          456,
		AlertID:     123,
		UserID:      "user-1",
		Status:      "open",
		Description: "Suspicious",
		Resolution:  "",
		CreatedAt:   now,
		UpdatedAt:   now,
	}
	query := `SELECT id, alert_id, user_id, status, description, resolution, created_at, updated_at FROM fraud_cases WHERE id = \$1`
	rows := sqlmock.NewRows([]string{"id", "alert_id", "user_id", "status", "description", "resolution", "created_at", "updated_at"}).
		AddRow(caseObj.ID, caseObj.AlertID, caseObj.UserID, caseObj.Status, caseObj.Description, caseObj.Resolution, caseObj.CreatedAt, caseObj.UpdatedAt)
	mock.ExpectQuery(query).WithArgs(caseObj.ID).WillReturnRows(rows)

	result, err := repo.GetCase(context.Background(), caseObj.ID)
	require.NoError(t, err)
	assert.Equal(t, caseObj, result)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestFraudCaseRepositoryImpl_GetCase_NotFound(t *testing.T) {
	db, mock := newMockDB()
	repo := NewFraudCaseRepository(db)

	query := `SELECT id, alert_id, user_id, status, description, resolution, created_at, updated_at FROM fraud_cases WHERE id = \$1`
	mock.ExpectQuery(query).WithArgs(999).WillReturnError(sql.ErrNoRows)

	result, err := repo.GetCase(context.Background(), 999)
	assert.ErrorIs(t, err, sql.ErrNoRows)
	assert.Nil(t, result)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestFraudCaseRepositoryImpl_GetCasesByUser_Success(t *testing.T) {
	db, mock := newMockDB()
	repo := NewFraudCaseRepository(db)

	now := time.Now()
	cases := []*fraudmodels.FraudCase{
		{ID: 1, AlertID: 101, UserID: "user-1", Status: "open", Description: "desc1", Resolution: "", CreatedAt: now, UpdatedAt: now},
		{ID: 2, AlertID: 102, UserID: "user-1", Status: "closed", Description: "desc2", Resolution: "resolved", CreatedAt: now, UpdatedAt: now},
	}
	query := `SELECT id, alert_id, user_id, status, description, resolution, created_at, updated_at FROM fraud_cases WHERE user_id = \$1 ORDER BY created_at DESC LIMIT \$2 OFFSET \$3`
	rows := sqlmock.NewRows([]string{"id", "alert_id", "user_id", "status", "description", "resolution", "created_at", "updated_at"}).
		AddRow(cases[0].ID, cases[0].AlertID, cases[0].UserID, cases[0].Status, cases[0].Description, cases[0].Resolution, cases[0].CreatedAt, cases[0].UpdatedAt).
		AddRow(cases[1].ID, cases[1].AlertID, cases[1].UserID, cases[1].Status, cases[1].Description, cases[1].Resolution, cases[1].CreatedAt, cases[1].UpdatedAt)
	mock.ExpectQuery(query).WithArgs("user-1", 10, 0).WillReturnRows(rows)

	result, err := repo.GetCasesByUser(context.Background(), "user-1", 10, 0)
	require.NoError(t, err)
	assert.Equal(t, cases, result)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestFraudCaseRepositoryImpl_UpdateCaseStatus(t *testing.T) {
	db, mock := newMockDB()
	repo := NewFraudCaseRepository(db)

	caseID := int64(123)
	status := "investigating"
	query := `UPDATE fraud_cases SET status = \$1, updated_at = \$2 WHERE id = \$3`
	mock.ExpectExec(query).WithArgs(status, sqlmock.AnyArg(), caseID).WillReturnResult(sqlmock.NewResult(0, 1))

	err := repo.UpdateCaseStatus(context.Background(), caseID, status)
	require.NoError(t, err)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestFraudCaseRepositoryImpl_UpdateCaseResolution(t *testing.T) {
	db, mock := newMockDB()
	repo := NewFraudCaseRepository(db)

	caseID := int64(123)
	resolution := "fraud confirmed"
	query := `UPDATE fraud_cases SET resolution = \$1, status = 'resolved', updated_at = \$2 WHERE id = \$3`
	mock.ExpectExec(query).WithArgs(resolution, sqlmock.AnyArg(), caseID).WillReturnResult(sqlmock.NewResult(0, 1))

	err := repo.UpdateCaseResolution(context.Background(), caseID, resolution)
	require.NoError(t, err)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestFraudRuleRepositoryImpl_CreateRule(t *testing.T) {
	db, mock := newMockDB()
	repo := NewFraudRuleRepository(db)

	now := time.Now()
	rule := &fraudmodels.FraudRule{
		Name:        "Large amount",
		RuleType:    "amount",
		Type:        "transaction",
		Condition:   "{\"amount\": 10000}",
		Action:      "block",
		RiskScore:   85.5,
		Severity:    9.0,
		IsEnabled:   true,
		CreatedAt:   now,
		UpdatedAt:   now,
	}
	returnedID := int64(789)
	query := `INSERT INTO fraud_rules \(name, type, condition, action, severity, is_enabled, created_at, updated_at\) VALUES \(\$1, \$2, \$3, \$4, \$5, \$6, \$7, \$8\) RETURNING id`
	mock.ExpectQuery(query).WithArgs(
		rule.Name, rule.Type, rule.Condition,
		rule.Action, rule.Severity, rule.IsEnabled,
		rule.CreatedAt, rule.UpdatedAt,
	).WillReturnRows(sqlmock.NewRows([]string{"id"}).AddRow(returnedID))

	err := repo.CreateRule(context.Background(), rule)
	require.NoError(t, err)
	assert.Equal(t, returnedID, rule.ID)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestFraudRuleRepositoryImpl_GetRule_Found(t *testing.T) {
	db, mock := newMockDB()
	repo := NewFraudRuleRepository(db)

	now := time.Now()
	rule := &fraudmodels.FraudRule{
		ID:        789,
		Name:      "Large amount",
		Type:      "transaction",
		Condition: "{\"amount\": 10000}",
		Action:    "block",
		Severity:  9.0,
		IsEnabled: true,
		CreatedAt: now,
		UpdatedAt: now,
	}
	query := `SELECT id, name, type, condition, action, severity, is_enabled, created_at, updated_at FROM fraud_rules WHERE id = \$1`
	rows := sqlmock.NewRows([]string{"id", "name", "type", "condition", "action", "severity", "is_enabled", "created_at", "updated_at"}).
		AddRow(rule.ID, rule.Name, rule.Type, rule.Condition, rule.Action, rule.Severity, rule.IsEnabled, rule.CreatedAt, rule.UpdatedAt)
	mock.ExpectQuery(query).WithArgs(rule.ID).WillReturnRows(rows)

	result, err := repo.GetRule(context.Background(), rule.ID)
	require.NoError(t, err)
	assert.Equal(t, rule, result)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestFraudRuleRepositoryImpl_GetRule_NotFound(t *testing.T) {
	db, mock := newMockDB()
	repo := NewFraudRuleRepository(db)

	query := `SELECT id, name, type, condition, action, severity, is_enabled, created_at, updated_at FROM fraud_rules WHERE id = \$1`
	mock.ExpectQuery(query).WithArgs(999).WillReturnError(sql.ErrNoRows)

	result, err := repo.GetRule(context.Background(), 999)
	assert.ErrorIs(t, err, sql.ErrNoRows)
	assert.Nil(t, result)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestFraudRuleRepositoryImpl_GetAllRules(t *testing.T) {
	db, mock := newMockDB()
	repo := NewFraudRuleRepository(db)

	now := time.Now()
	rules := []*fraudmodels.FraudRule{
		{ID: 1, Name: "Rule1", Type: "type1", Condition: "{}", Action: "alert", Severity: 5.0, IsEnabled: true, CreatedAt: now, UpdatedAt: now},
		{ID: 2, Name: "Rule2", Type: "type2", Condition: "{}", Action: "block", Severity: 8.0, IsEnabled: false, CreatedAt: now, UpdatedAt: now},
	}
	query := `SELECT id, name, type, condition, action, severity, is_enabled, created_at, updated_at FROM fraud_rules ORDER BY created_at DESC`
	rows := sqlmock.NewRows([]string{"id", "name", "type", "condition", "action", "severity", "is_enabled", "created_at", "updated_at"}).
		AddRow(rules[0].ID, rules[0].Name, rules[0].Type, rules[0].Condition, rules[0].Action, rules[0].Severity, rules[0].IsEnabled, rules[0].CreatedAt, rules[0].UpdatedAt).
		AddRow(rules[1].ID, rules[1].Name, rules[1].Type, rules[1].Condition, rules[1].Action, rules[1].Severity, rules[1].IsEnabled, rules[1].CreatedAt, rules[1].UpdatedAt)
	mock.ExpectQuery(query).WillReturnRows(rows)

	result, err := repo.GetAllRules(context.Background())
	require.NoError(t, err)
	assert.Equal(t, rules, result)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestFraudRuleRepositoryImpl_GetActiveRules(t *testing.T) {
	db, mock := newMockDB()
	repo := NewFraudRuleRepository(db)

	now := time.Now()
	rules := []*fraudmodels.FraudRule{
		{ID: 1, Name: "Active1", Type: "t1", Condition: "{}", Action: "alert", Severity: 5.0, IsEnabled: true, CreatedAt: now, UpdatedAt: now},
		{ID: 2, Name: "Active2", Type: "t2", Condition: "{}", Action: "block", Severity: 9.0, IsEnabled: true, CreatedAt: now, UpdatedAt: now},
	}
	query := `SELECT id, name, type, condition, action, severity, is_enabled, created_at, updated_at FROM fraud_rules WHERE is_enabled = true ORDER BY created_at DESC`
	rows := sqlmock.NewRows([]string{"id", "name", "type", "condition", "action", "severity", "is_enabled", "created_at", "updated_at"}).
		AddRow(rules[0].ID, rules[0].Name, rules[0].Type, rules[0].Condition, rules[0].Action, rules[0].Severity, rules[0].IsEnabled, rules[0].CreatedAt, rules[0].UpdatedAt).
		AddRow(rules[1].ID, rules[1].Name, rules[1].Type, rules[1].Condition, rules[1].Action, rules[1].Severity, rules[1].IsEnabled, rules[1].CreatedAt, rules[1].UpdatedAt)
	mock.ExpectQuery(query).WillReturnRows(rows)

	result, err := repo.GetActiveRules(context.Background())
	require.NoError(t, err)
	assert.Equal(t, rules, result)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestFraudRuleRepositoryImpl_UpdateRuleStatus(t *testing.T) {
	db, mock := newMockDB()
	repo := NewFraudRuleRepository(db)

	ruleID := int64(123)
	isEnabled := false
	query := `UPDATE fraud_rules SET is_enabled = \$1, updated_at = \$2 WHERE id = \$3`
	mock.ExpectExec(query).WithArgs(isEnabled, sqlmock.AnyArg(), ruleID).WillReturnResult(sqlmock.NewResult(0, 1))

	err := repo.UpdateRuleStatus(context.Background(), ruleID, isEnabled)
	require.NoError(t, err)
	require.NoError(t, mock.ExpectationsWereMet())
}

func TestFraudRuleRepositoryImpl_UpdateRule(t *testing.T) {
	db, mock := newMockDB()
	repo := NewFraudRuleRepository(db)

	now := time.Now()
	rule := &fraudmodels.FraudRule{
		ID:         789,
		Name:       "Updated rule",
		RuleType:   "frequency",
		Type:       "login",
		Condition:  "{\"count\": 10}",
		Action:     "alert",
		RiskScore:  70.0,
		Severity:   7.0,
		IsEnabled:  true,
		CreatedAt:  now,
		UpdatedAt:  now,
	}
	query := `UPDATE fraud_rules SET name = \$1, type = \$2, condition = \$3, action = \$4, severity = \$5, updated_at = \$6 WHERE id = \$7`
	mock.ExpectExec(query).WithArgs(
		rule.Name, rule.Type, rule.Condition,
		rule.Action, rule.Severity, sqlmock.AnyArg(), rule.ID,
	).WillReturnResult(sqlmock.NewResult(0, 1))

	err := repo.UpdateRule(context.Background(), rule)
	require.NoError(t, err)
	require.NoError(t, mock.ExpectationsWereMet())
}
