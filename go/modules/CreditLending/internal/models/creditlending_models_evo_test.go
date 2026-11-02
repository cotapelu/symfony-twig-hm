package creditlending

import (
	"database/sql"
	"math"
	"testing"
	"time"

	_ "github.com/mattn/go-sqlite3"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"
)

func setupDB(t *testing.T) *sql.DB {
	t.Helper()
	db, err := sql.Open("sqlite3", ":memory:")
	require.NoError(t, err)
	// Enable foreign keys
	_, err = db.Exec(`PRAGMA foreign_keys = ON`)
	require.NoError(t, err)

	// Create tables
	createApps := `CREATE TABLE credit_applications (
		id INTEGER PRIMARY KEY AUTOINCREMENT,
		user_id TEXT NOT NULL,
		credit_type TEXT NOT NULL,
		amount REAL NOT NULL,
		term INTEGER NOT NULL,
		interest_rate REAL NOT NULL,
		status TEXT NOT NULL,
		approved_amount REAL NOT NULL DEFAULT 0,
		monthly_payment REAL NOT NULL DEFAULT 0,
		total_repayment REAL NOT NULL DEFAULT 0,
		disbursed_at DATETIME,
		completed_at DATETIME,
		created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
	)`
	createRepayments := `CREATE TABLE credit_repayments (
		id INTEGER PRIMARY KEY AUTOINCREMENT,
		application_id INTEGER NOT NULL,
		amount REAL NOT NULL,
		principal REAL NOT NULL,
		interest REAL NOT NULL,
		penalty REAL NOT NULL,
		balance_after REAL NOT NULL,
		paid_at DATETIME,
		status TEXT NOT NULL,
		created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		FOREIGN KEY (application_id) REFERENCES credit_applications(id) ON DELETE CASCADE
	)`
	createScores := `CREATE TABLE credit_scores (
		user_id TEXT PRIMARY KEY,
		score INTEGER NOT NULL,
		risk_level TEXT NOT NULL,
		max_credit_amount REAL NOT NULL,
		updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
	)`

	_, err = db.Exec(createApps)
	require.NoError(t, err)
	_, err = db.Exec(createRepayments)
	require.NoError(t, err)
	_, err = db.Exec(createScores)
	require.NoError(t, err)

	return db
}

func newTestApp() *CreditApplication {
	return &CreditApplication{
		UserID:       "user1",
		CreditType:   "personal",
		Amount:       10000,
		Term:         12,
		InterestRate: 0.024,
		Status:       "pending",
	}
}

// ==================== CreditRepository Tests ====================

func TestCreditRepository_CreateApplication(t *testing.T) {
	db := setupDB(t)
	defer db.Close()
	repo := NewCreditRepository(db)

	app := newTestApp()
	err := repo.CreateApplication(app)
	require.NoError(t, err)
	assert.NotZero(t, app.ID)
	assert.False(t, app.CreatedAt.IsZero())
	assert.True(t, app.CreatedAt.Before(time.Now().Add(1*time.Second)))
}

func TestCreditRepository_GetApplication(t *testing.T) {
	db := setupDB(t)
	defer db.Close()
	repo := NewCreditRepository(db)

	app := newTestApp()
	require.NoError(t, repo.CreateApplication(app))

	fetched, err := repo.GetApplication(app.ID)
	require.NoError(t, err)
	assert.Equal(t, app.ID, fetched.ID)
	assert.Equal(t, app.UserID, fetched.UserID)
	assert.Equal(t, app.CreditType, fetched.CreditType)
	assert.Equal(t, app.Amount, fetched.Amount)
	assert.Equal(t, app.Term, fetched.Term)
	assert.Equal(t, app.InterestRate, fetched.InterestRate)
	assert.Equal(t, app.Status, fetched.Status)
}

func TestCreditRepository_GetApplicationsByUser(t *testing.T) {
	db := setupDB(t)
	defer db.Close()
	repo := NewCreditRepository(db)

	// Two for user1, one for user2
	require.NoError(t, repo.CreateApplication(newTestApp()))
	app2 := newTestApp()
	require.NoError(t, repo.CreateApplication(app2))
	app3 := newTestApp()
	app3.UserID = "user2"
	require.NoError(t, repo.CreateApplication(app3))

	apps, err := repo.GetApplicationsByUser("user1")
	require.NoError(t, err)
	assert.Len(t, apps, 2)
	for _, a := range apps {
		assert.Equal(t, "user1", a.UserID)
	}
}

func TestCreditRepository_UpdateApplicationStatus(t *testing.T) {
	db := setupDB(t)
	defer db.Close()
	repo := NewCreditRepository(db)

	app := newTestApp()
	require.NoError(t, repo.CreateApplication(app))

	err := repo.UpdateApplicationStatus(app.ID, "approved")
	require.NoError(t, err)

	fetched, err := repo.GetApplication(app.ID)
	require.NoError(t, err)
	assert.Equal(t, "approved", fetched.Status)
}

func TestCreditRepository_ApproveApplication(t *testing.T) {
	db := setupDB(t)
	defer db.Close()
	repo := NewCreditRepository(db)

	app := newTestApp()
	require.NoError(t, repo.CreateApplication(app))

	approvedAmount := 8000.0
	rate := app.InterestRate
	term := float64(app.Term)
	monthlyRate := rate / 12
	monthlyPayment := approvedAmount * (monthlyRate * math.Pow(1+monthlyRate, term)) / (math.Pow(1+monthlyRate, term) - 1)
	totalRepayment := monthlyPayment * term

	err := repo.ApproveApplication(app.ID, approvedAmount, monthlyPayment, totalRepayment)
	require.NoError(t, err)

	fetched, err := repo.GetApplication(app.ID)
	require.NoError(t, err)
	assert.Equal(t, "approved", fetched.Status)
	assert.InDelta(t, approvedAmount, fetched.ApprovedAmount, 0.01)
	assert.InDelta(t, monthlyPayment, fetched.MonthlyPayment, 0.01)
	assert.InDelta(t, totalRepayment, fetched.TotalRepayment, 0.01)
}

func TestCreditRepository_Disburse(t *testing.T) {
	db := setupDB(t)
	defer db.Close()
	repo := NewCreditRepository(db)

	app := newTestApp()
	require.NoError(t, repo.CreateApplication(app))
	// Approve first
	require.NoError(t, repo.ApproveApplication(app.ID, app.Amount, 100, 1200))

	err := repo.Disburse(app.ID)
	require.NoError(t, err)

	fetched, err := repo.GetApplication(app.ID)
	require.NoError(t, err)
	assert.Equal(t, "disbursed", fetched.Status)
	assert.NotNil(t, fetched.DisbursedAt)
}

func TestCreditRepository_CreateRepayment(t *testing.T) {
	db := setupDB(t)
	defer db.Close()
	repo := NewCreditRepository(db)

	app := newTestApp()
	require.NoError(t, repo.CreateApplication(app))

	rp := &CreditRepayment{
		ApplicationID: app.ID,
		Amount:        100,
		Principal:     80,
		Interest:      20,
		Penalty:       0,
		BalanceAfter: 900,
		Status:        "completed",
	}
	err := repo.CreateRepayment(rp)
	require.NoError(t, err)
	assert.NotZero(t, rp.ID)
	assert.False(t, rp.CreatedAt.IsZero())
}

func TestCreditRepository_GetRepayments(t *testing.T) {
	db := setupDB(t)
	defer db.Close()
	repo := NewCreditRepository(db)

	app := newTestApp()
	require.NoError(t, repo.CreateApplication(app))

	now := time.Now().UTC()
	rp1 := &CreditRepayment{
		ApplicationID: app.ID,
		Amount:        100,
		Principal:     80,
		Interest:      20,
		Penalty:       0,
		BalanceAfter: 900,
		Status:        "completed",
		PaidAt:        &now,
	}
	rp2 := &CreditRepayment{
		ApplicationID: app.ID,
		Amount:        200,
		Principal:     160,
		Interest:     40,
		Penalty:       0,
		BalanceAfter: 700,
		Status:        "completed",
		PaidAt:        &now,
	}
	require.NoError(t, repo.CreateRepayment(rp1))
	time.Sleep(10 * time.Millisecond)
	require.NoError(t, repo.CreateRepayment(rp2))

	reps, err := repo.GetRepayments(app.ID)
	require.NoError(t, err)
	assert.Len(t, reps, 2)
	assert.Equal(t, rp1.ID, reps[0].ID)
	assert.Equal(t, rp2.ID, reps[1].ID)
}

// ==================== CreditScoreRepository Tests ====================

func TestCreditScoreRepository_GetScore_NoRow(t *testing.T) {
	db := setupDB(t)
	defer db.Close()
	repo := NewCreditScoreRepository(db)

	score, err := repo.GetScore("nonexistent")
	require.NoError(t, err)
	assert.Equal(t, 650, score.Score)
	assert.Equal(t, "medium", score.RiskLevel)
	assert.InDelta(t, 10000000.0, score.MaxCreditAmount, 0.01)
}

func TestCreditScoreRepository_GetScore_Existing(t *testing.T) {
	db := setupDB(t)
	defer db.Close()
	repo := NewCreditScoreRepository(db)

	now := time.Now().UTC()
	_, err := db.Exec(`INSERT INTO credit_scores (user_id, score, risk_level, max_credit_amount, updated_at) VALUES (?, ?, ?, ?, ?)`,
		"user1", 700, "low", 20000000.0, now)
	require.NoError(t, err)

	score, err := repo.GetScore("user1")
	require.NoError(t, err)
	assert.Equal(t, 700, score.Score)
	assert.Equal(t, "low", score.RiskLevel)
	assert.InDelta(t, 20000000.0, score.MaxCreditAmount, 0.01)
}

func TestCreditScoreRepository_UpdateScore_Insert(t *testing.T) {
	db := setupDB(t)
	defer db.Close()
	repo := NewCreditScoreRepository(db)

	err := repo.UpdateScore("user1", 720, "low", 15000000.0)
	require.NoError(t, err)

	score, err := repo.GetScore("user1")
	require.NoError(t, err)
	assert.Equal(t, 720, score.Score)
	assert.Equal(t, "low", score.RiskLevel)
	assert.InDelta(t, 15000000.0, score.MaxCreditAmount, 0.01)
}

func TestCreditScoreRepository_UpdateScore_Update(t *testing.T) {
	db := setupDB(t)
	defer db.Close()
	repo := NewCreditScoreRepository(db)

	now := time.Now().UTC()
	_, err := db.Exec(`INSERT INTO credit_scores (user_id, score, risk_level, max_credit_amount, updated_at) VALUES (?, ?, ?, ?, ?)`,
		"user1", 500, "high", 5000000.0, now)
	require.NoError(t, err)

	err = repo.UpdateScore("user1", 600, "medium", 8000000.0)
	require.NoError(t, err)

	score, err := repo.GetScore("user1")
	require.NoError(t, err)
	assert.Equal(t, 600, score.Score)
	assert.Equal(t, "medium", score.RiskLevel)
	assert.InDelta(t, 8000000.0, score.MaxCreditAmount, 0.01)
}

// ==================== CreditLendingService Tests ====================

func TestCreditLendingService_CreateApplication(t *testing.T) {
	db := setupDB(t)
	defer db.Close()
	repo := NewCreditRepository(db)
	scoreRepo := NewCreditScoreRepository(db)
	svc := &CreditLendingService{repo: repo, scoreRepo: scoreRepo, bankingURL: ""}

	app := newTestApp()
	err := svc.CreateApplication(app)
	require.NoError(t, err)
	assert.NotZero(t, app.ID)
}

func TestCreditLendingService_GetApplication(t *testing.T) {
	db := setupDB(t)
	defer db.Close()
	repo := NewCreditRepository(db)
	scoreRepo := NewCreditScoreRepository(db)
	svc := &CreditLendingService{repo: repo, scoreRepo: scoreRepo}

	app := newTestApp()
	require.NoError(t, repo.CreateApplication(app))

	fetched, err := svc.GetApplication(app.ID)
	require.NoError(t, err)
	assert.Equal(t, app.ID, fetched.ID)

	_, err = svc.GetApplication(999)
	assert.Error(t, err)
}

func TestCreditLendingService_GetUserApplications(t *testing.T) {
	db := setupDB(t)
	defer db.Close()
	repo := NewCreditRepository(db)
	scoreRepo := NewCreditScoreRepository(db)
	svc := &CreditLendingService{repo: repo, scoreRepo: scoreRepo}

	require.NoError(t, repo.CreateApplication(newTestApp()))
	app2 := newTestApp()
	require.NoError(t, repo.CreateApplication(app2))

	apps, err := svc.GetUserApplications("user1")
	require.NoError(t, err)
	assert.Len(t, apps, 2)
}

func TestCreditLendingService_ApproveApplication_BusinessLogic(t *testing.T) {
	db := setupDB(t)
	defer db.Close()
	repo := NewCreditRepository(db)
	scoreRepo := NewCreditScoreRepository(db)
	svc := &CreditLendingService{repo: repo, scoreRepo: scoreRepo}

	app := newTestApp()
	require.NoError(t, repo.CreateApplication(app))

	approvedAmount := 8000.0
	approvedApp, err := svc.ApproveApplication(app.ID, approvedAmount)
	require.NoError(t, err)
	assert.Equal(t, "approved", approvedApp.Status)
	assert.InDelta(t, approvedAmount, approvedApp.ApprovedAmount, 0.01)
	assert.Greater(t, approvedApp.MonthlyPayment, 0.0)
	assert.Greater(t, approvedApp.TotalRepayment, 0.0)

	// Exceeds amount should error
	_, err = svc.ApproveApplication(app.ID, app.Amount+1)
	assert.Error(t, err)
	assert.Contains(t, err.Error(), "approved amount cannot exceed requested amount")
}

func TestCreditLendingService_Disburse(t *testing.T) {
	db := setupDB(t)
	defer db.Close()
	repo := NewCreditRepository(db)
	scoreRepo := NewCreditScoreRepository(db)
	svc := &CreditLendingService{repo: repo, scoreRepo: scoreRepo}

	app := newTestApp()
	require.NoError(t, repo.CreateApplication(app))
	require.NoError(t, repo.ApproveApplication(app.ID, app.Amount, 100, 1200))

	disbursedApp, err := svc.Disburse(app.ID)
	require.NoError(t, err)
	assert.Equal(t, "disbursed", disbursedApp.Status)
	assert.NotNil(t, disbursedApp.DisbursedAt)
}

func TestCreditLendingService_Disburse_InvalidStatus(t *testing.T) {
	db := setupDB(t)
	defer db.Close()
	repo := NewCreditRepository(db)
	scoreRepo := NewCreditScoreRepository(db)
	svc := &CreditLendingService{repo: repo, scoreRepo: scoreRepo}

	app := newTestApp()
	require.NoError(t, repo.CreateApplication(app))

	_, err := svc.Disburse(app.ID)
	assert.Error(t, err)
	assert.Contains(t, err.Error(), "application not approved")
}

func TestCreditLendingService_Repay(t *testing.T) {
	db := setupDB(t)
	defer db.Close()
	repo := NewCreditRepository(db)
	scoreRepo := NewCreditScoreRepository(db)
	svc := &CreditLendingService{repo: repo, scoreRepo: scoreRepo}

	app := newTestApp()
	require.NoError(t, repo.CreateApplication(app))
	require.NoError(t, repo.ApproveApplication(app.ID, app.Amount, 100, 1200))
	require.NoError(t, repo.Disburse(app.ID))

	rpAmount := 100.0
	rp, err := svc.Repay(app.ID, rpAmount)
	require.NoError(t, err)
	assert.Equal(t, app.ID, rp.ApplicationID)
	assert.InDelta(t, rpAmount, rp.Amount, 0.01)
	assert.LessOrEqual(t, rp.BalanceAfter, 1200-rpAmount)
	assert.Equal(t, "completed", rp.Status)

	// Balance should be total - paid
	fetchedApp, err := repo.GetApplication(app.ID)
	require.NoError(t, err)
	expectedBalance := fetchedApp.TotalRepayment - rp.Amount
	assert.InDelta(t, expectedBalance, rp.BalanceAfter, 0.01)
}

func TestCreditLendingService_Repay_Full(t *testing.T) {
	db := setupDB(t)
	defer db.Close()
	repo := NewCreditRepository(db)
	scoreRepo := NewCreditScoreRepository(db)
	svc := &CreditLendingService{repo: repo, scoreRepo: scoreRepo}

	app := newTestApp()
	require.NoError(t, repo.CreateApplication(app))
	require.NoError(t, repo.ApproveApplication(app.ID, app.Amount, 100, 1200))
	require.NoError(t, repo.Disburse(app.ID))

	rp, err := svc.Repay(app.ID, 1200)
	require.NoError(t, err)
	assert.True(t, rp.BalanceAfter <= 0)

	// Application status should be completed
	fetched, err := repo.GetApplication(app.ID)
	require.NoError(t, err)
	assert.Equal(t, "completed", fetched.Status)
}

func TestCreditLendingService_Repay_ExceedsBalance(t *testing.T) {
	db := setupDB(t)
	defer db.Close()
	repo := NewCreditRepository(db)
	scoreRepo := NewCreditScoreRepository(db)
	svc := &CreditLendingService{repo: repo, scoreRepo: scoreRepo}

	app := newTestApp()
	require.NoError(t, repo.CreateApplication(app))
	require.NoError(t, repo.ApproveApplication(app.ID, app.Amount, 100, 1200))
	require.NoError(t, repo.Disburse(app.ID))

	rp, err := svc.Repay(app.ID, 2000)
	require.NoError(t, err)
	assert.LessOrEqual(t, rp.Amount, 1200.0)
}

func TestCreditLendingService_Repay_NotDisbursed(t *testing.T) {
	db := setupDB(t)
	defer db.Close()
	repo := NewCreditRepository(db)
	scoreRepo := NewCreditScoreRepository(db)
	svc := &CreditLendingService{repo: repo, scoreRepo: scoreRepo}

	app := newTestApp()
	require.NoError(t, repo.CreateApplication(app))

	_, err := svc.Repay(app.ID, 100)
	assert.Error(t, err)
	assert.Contains(t, err.Error(), "credit not disbursed")
}

func TestCreditLendingService_GetCreditScore(t *testing.T) {
	db := setupDB(t)
	defer db.Close()
	repo := NewCreditRepository(db)
	scoreRepo := NewCreditScoreRepository(db)
	svc := &CreditLendingService{repo: repo, scoreRepo: scoreRepo}

	// Default
	score, err := svc.GetCreditScore("none")
	require.NoError(t, err)
	assert.Equal(t, 650, score.Score)

	// Existing
	now := time.Now().UTC()
	_, err = db.Exec(`INSERT INTO credit_scores (user_id, score, risk_level, max_credit_amount, updated_at) VALUES (?, ?, ?, ?, ?)`,
		"user2", 700, "low", 15000000.0, now)
	require.NoError(t, err)
	score2, err := svc.GetCreditScore("user2")
	require.NoError(t, err)
	assert.Equal(t, 700, score2.Score)
}

func TestCreditLendingService_GetRepayments(t *testing.T) {
	db := setupDB(t)
	defer db.Close()
	repo := NewCreditRepository(db)
	scoreRepo := NewCreditScoreRepository(db)
	svc := &CreditLendingService{repo: repo, scoreRepo: scoreRepo}

	app := newTestApp()
	require.NoError(t, repo.CreateApplication(app))
	now := time.Now().UTC()
	rp := &CreditRepayment{
		ApplicationID: app.ID,
		Amount:        100,
		Principal:     80,
		Interest:      20,
		Penalty:       0,
		BalanceAfter: 900,
		Status:        "completed",
		PaidAt:        &now,
	}
	require.NoError(t, repo.CreateRepayment(rp))

	reps, err := svc.GetRepayments(app.ID)
	require.NoError(t, err)
	assert.Len(t, reps, 1)
	assert.Equal(t, rp.ID, reps[0].ID)
}
