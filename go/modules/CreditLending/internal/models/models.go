package creditlending

import (
	"database/sql"
	"fmt"
	"math"
	"time"
)

type CreditApplication struct {
	ID             int64      `json:"id"`
	UserID         string     `json:"user_id"`
	CreditType     string     `json:"credit_type"`
	Amount         float64    `json:"amount"`
	Term           int        `json:"term"`
	InterestRate   float64    `json:"interest_rate"`
	Status         string     `json:"status"`
	ApprovedAmount float64    `json:"approved_amount"`
	MonthlyPayment float64    `json:"monthly_payment"`
	TotalRepayment float64    `json:"total_repayment"`
	DisbursedAt    *time.Time `json:"disbursed_at"`
	CompletedAt    *time.Time `json:"completed_at"`
	CreatedAt      time.Time  `json:"created_at"`
}

type CreditRepayment struct {
	ID            int64      `json:"id"`
	ApplicationID int64      `json:"application_id"`
	Amount        float64    `json:"amount"`
	Principal     float64    `json:"principal"`
	Interest      float64    `json:"interest"`
	Penalty       float64    `json:"penalty"`
	BalanceAfter  float64    `json:"balance_after"`
	PaidAt        *time.Time `json:"paid_at"`
	Status        string     `json:"status"`
	CreatedAt     time.Time  `json:"created_at"`
}

type CreditScore struct {
	UserID          string    `json:"user_id"`
	Score           int       `json:"score"`
	RiskLevel       string    `json:"risk_level"`
	MaxCreditAmount float64   `json:"max_credit_amount"`
	UpdatedAt       time.Time `json:"updated_at"`
}

type CreditRepository struct {
	db *sql.DB
}

func NewCreditRepository(db *sql.DB) *CreditRepository {
	return &CreditRepository{db: db}
}

func (r *CreditRepository) CreateApplication(a *CreditApplication) error {
	query := `INSERT INTO credit_applications (user_id, credit_type, amount, term, interest_rate, status, approved_amount, monthly_payment, total_repayment)
	          VALUES ($1, $2, $3, $4, $5, $6, 0, 0, 0) RETURNING id, created_at`
	return r.db.QueryRow(query, a.UserID, a.CreditType, a.Amount, a.Term, a.InterestRate, a.Status).Scan(&a.ID, &a.CreatedAt)
}

func (r *CreditRepository) GetApplication(id int64) (*CreditApplication, error) {
	a := &CreditApplication{}
	query := `SELECT id, user_id, credit_type, amount, term, interest_rate, status, approved_amount,
	          monthly_payment, total_repayment, disbursed_at, completed_at, created_at
	          FROM credit_applications WHERE id = $1`
	err := r.db.QueryRow(query, id).Scan(&a.ID, &a.UserID, &a.CreditType, &a.Amount, &a.Term, &a.InterestRate, &a.Status,
		&a.ApprovedAmount, &a.MonthlyPayment, &a.TotalRepayment, &a.DisbursedAt, &a.CompletedAt, &a.CreatedAt)
	if err != nil {
		return nil, err
	}
	return a, nil
}

func (r *CreditRepository) GetApplicationsByUser(userID string) ([]*CreditApplication, error) {
	rows, err := r.db.Query(`SELECT id, user_id, credit_type, amount, term, interest_rate, status, approved_amount,
	                        monthly_payment, total_repayment, disbursed_at, completed_at, created_at
	                        FROM credit_applications WHERE user_id = $1 ORDER BY created_at DESC`, userID)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var apps []*CreditApplication
	for rows.Next() {
		a := &CreditApplication{}
		if err := rows.Scan(&a.ID, &a.UserID, &a.CreditType, &a.Amount, &a.Term, &a.InterestRate, &a.Status,
			&a.ApprovedAmount, &a.MonthlyPayment, &a.TotalRepayment, &a.DisbursedAt, &a.CompletedAt, &a.CreatedAt); err != nil {
			return nil, err
		}
		apps = append(apps, a)
	}
	return apps, nil
}

func (r *CreditRepository) UpdateApplicationStatus(id int64, status string) error {
	query := `UPDATE credit_applications SET status = $1 WHERE id = $2`
	_, err := r.db.Exec(query, status, id)
	return err
}

func (r *CreditRepository) ApproveApplication(id int64, approvedAmount, monthlyPayment, totalRepayment float64) error {
	query := `UPDATE credit_applications SET status = 'approved', approved_amount = $1, monthly_payment = $2, total_repayment = $3 WHERE id = $4`
	_, err := r.db.Exec(query, approvedAmount, monthlyPayment, totalRepayment, id)
	return err
}

func (r *CreditRepository) Disburse(id int64) error {
	query := `UPDATE credit_applications SET status = 'disbursed', disbursed_at = CURRENT_TIMESTAMP WHERE id = $1`
	_, err := r.db.Exec(query, id)
	return err
}

func (r *CreditRepository) CreateRepayment(rp *CreditRepayment) error {
	query := `INSERT INTO credit_repayments (application_id, amount, principal, interest, penalty, balance_after, status)
	          VALUES ($1, $2, $3, $4, $5, $6, $7) RETURNING id, created_at`
	return r.db.QueryRow(query, rp.ApplicationID, rp.Amount, rp.Principal, rp.Interest, rp.Penalty, rp.BalanceAfter, rp.Status).Scan(&rp.ID, &rp.CreatedAt)
}

func (r *CreditRepository) GetRepayments(appID int64) ([]*CreditRepayment, error) {
	rows, err := r.db.Query(`SELECT id, application_id, amount, principal, interest, penalty, balance_after, paid_at, status, created_at
	                        FROM credit_repayments WHERE application_id = $1 ORDER BY created_at ASC`, appID)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var reps []*CreditRepayment
	for rows.Next() {
		rp := &CreditRepayment{}
		if err := rows.Scan(&rp.ID, &rp.ApplicationID, &rp.Amount, &rp.Principal, &rp.Interest, &rp.Penalty, &rp.BalanceAfter, &rp.PaidAt, &rp.Status, &rp.CreatedAt); err != nil {
			return nil, err
		}
		reps = append(reps, rp)
	}
	return reps, nil
}

type CreditScoreRepository struct {
	db *sql.DB
}

func NewCreditScoreRepository(db *sql.DB) *CreditScoreRepository {
	return &CreditScoreRepository{db: db}
}

func (r *CreditScoreRepository) GetScore(userID string) (*CreditScore, error) {
	s := &CreditScore{UserID: userID}
	query := `SELECT score, risk_level, max_credit_amount, updated_at FROM credit_scores WHERE user_id = $1`
	err := r.db.QueryRow(query, userID).Scan(&s.Score, &s.RiskLevel, &s.MaxCreditAmount, &s.UpdatedAt)
	if err == sql.ErrNoRows {
		s.Score = 650
		s.RiskLevel = "medium"
		s.MaxCreditAmount = 10000000
		s.UpdatedAt = time.Now()
		return s, nil
	}
	if err != nil {
		return nil, err
	}
	return s, nil
}

func (r *CreditScoreRepository) UpdateScore(userID string, score int, riskLevel string, maxAmount float64) error {
	query := `INSERT INTO credit_scores (user_id, score, risk_level, max_credit_amount, updated_at)
	          VALUES ($1, $2, $3, $4, CURRENT_TIMESTAMP) ON CONFLICT (user_id) DO UPDATE SET score = $2, risk_level = $3, max_credit_amount = $4, updated_at = CURRENT_TIMESTAMP`
	_, err := r.db.Exec(query, userID, score, riskLevel, maxAmount)
	return err
}

type CreditLendingService struct {
	repo       *CreditRepository
	scoreRepo  *CreditScoreRepository
	bankingURL string
}

func NewCreditLendingService(db *sql.DB, bankingURL string) *CreditLendingService {
	repo := &CreditRepository{db: db}
	scoreRepo := &CreditScoreRepository{db: db}
	return &CreditLendingService{
		repo:       repo,
		scoreRepo:  scoreRepo,
		bankingURL: bankingURL,
	}
}

func (s *CreditLendingService) CreateApplication(a *CreditApplication) error {
	return s.repo.CreateApplication(a)
}

func (s *CreditLendingService) GetApplication(id int64) (*CreditApplication, error) {
	return s.repo.GetApplication(id)
}

func (s *CreditLendingService) GetUserApplications(userID string) ([]*CreditApplication, error) {
	return s.repo.GetApplicationsByUser(userID)
}

func (s *CreditLendingService) ApproveApplication(id int64, approvedAmount float64) (*CreditApplication, error) {
	app, err := s.repo.GetApplication(id)
	if err != nil {
		return nil, err
	}

	if approvedAmount > app.Amount {
		return nil, fmt.Errorf("approved amount cannot exceed requested amount")
	}

	rate := app.InterestRate
	if rate <= 0 {
		rate = 0.024
	}
	monthlyRate := rate / 12
	months := float64(app.Term)
	if months <= 0 {
		months = 12
	}
	monthlyPayment := approvedAmount * (monthlyRate * math.Pow(1+monthlyRate, months)) / (math.Pow(1+monthlyRate, months) - 1)
	totalRepayment := monthlyPayment * months

	if err := s.repo.ApproveApplication(id, approvedAmount, monthlyPayment, totalRepayment); err != nil {
		return nil, err
	}
	return s.repo.GetApplication(id)
}

func (s *CreditLendingService) Disburse(id int64) (*CreditApplication, error) {
	app, err := s.repo.GetApplication(id)
	if err != nil {
		return nil, err
	}
	if app.Status != "approved" {
		return nil, fmt.Errorf("application not approved")
	}
	if err := s.repo.Disburse(id); err != nil {
		return nil, err
	}
	return s.repo.GetApplication(id)
}

func (s *CreditLendingService) Repay(id int64, amount float64) (*CreditRepayment, error) {
	app, err := s.repo.GetApplication(id)
	if err != nil {
		return nil, err
	}
	if app.Status != "disbursed" {
		return nil, fmt.Errorf("credit not disbursed")
	}
	reps, _ := s.repo.GetRepayments(id)
	var totalPaid float64
	for _, rp := range reps {
		totalPaid += rp.Amount
	}
	balance := app.TotalRepayment - totalPaid
	if amount > balance {
		amount = balance
	}
	principal := amount / 1.024
	interest := amount - principal

	rp := &CreditRepayment{
		ApplicationID: id,
		Amount:        amount,
		Principal:     principal,
		Interest:      interest,
		Penalty:       0,
		BalanceAfter:  balance - amount,
		Status:        "completed",
		PaidAt:        func() *time.Time { t := time.Now(); return &t }(),
	}
	if err := s.repo.CreateRepayment(rp); err != nil {
		return nil, err
	}
	if rp.BalanceAfter <= 0 {
		s.repo.UpdateApplicationStatus(id, "completed")
		now := time.Now()
		if app, err := s.repo.GetApplication(id); err == nil {
			app.CompletedAt = &now
		}
	}
	return rp, nil
}

func (s *CreditLendingService) GetCreditScore(userID string) (*CreditScore, error) {
	return s.scoreRepo.GetScore(userID)
}

func (s *CreditLendingService) GetRepayments(appID int64) ([]*CreditRepayment, error) {
	return s.repo.GetRepayments(appID)
}
