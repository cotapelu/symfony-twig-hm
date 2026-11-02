package fraud

import (
	"context"
	"testing"

	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"
	fraudmodels "community.local/modules/FraudDetection/internal/models"
)

func TestFraudCaseRepositoryImpl_NilDB_Errors(t *testing.T) {
	repo := NewFraudCaseRepository(nil)

	caseObj := &fraudmodels.FraudCase{
		AlertID: 1,
		UserID: "user-1",
		Status:  "open",
	}

	err := repo.CreateCase(context.Background(), caseObj)
	require.Error(t, err)

	_, err = repo.GetCase(context.Background(), 1)
	require.Error(t, err)

	_, err = repo.GetCasesByUser(context.Background(), "user-1", 10, 0)
	require.Error(t, err)

	err = repo.UpdateCaseStatus(context.Background(), 1, "resolved")
	require.Error(t, err)

	err = repo.UpdateCaseResolution(context.Background(), 1, "resolved")
	require.Error(t, err)
}

func TestFraudCaseRepositoryImpl_NilDB_NoPanic(t *testing.T) {
	assert.NotPanics(t, func() {
		_ = NewFraudCaseRepository(nil)
	})
}

func TestFraudRuleRepositoryImpl_NilDB_Errors(t *testing.T) {
	repo := NewFraudRuleRepository(nil)

	rule := &fraudmodels.FraudRule{
		Name: "rule-1",
		
	}

	err := repo.CreateRule(context.Background(), rule)
	require.Error(t, err)

	_, err = repo.GetRule(context.Background(), 1)
	require.Error(t, err)

	_, err = repo.GetAllRules(context.Background())
	require.Error(t, err)

	_, err = repo.GetActiveRules(context.Background())
	require.Error(t, err)

	err = repo.UpdateRuleStatus(context.Background(), 1, true)
	require.Error(t, err)

	err = repo.UpdateRule(context.Background(), &fraudmodels.FraudRule{ID: 1})
	require.Error(t, err)
}

func TestFraudRuleRepositoryImpl_NilDB_NoPanic(t *testing.T) {
	assert.NotPanics(t, func() {
		_ = NewFraudRuleRepository(nil)
	})
}
