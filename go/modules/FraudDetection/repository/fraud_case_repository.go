package fraud

import (
	"context"
	"database/sql"
	"errors"
	"time"

	fraudmodels "community.local/modules/FraudDetection/internal/models"
)

type FraudCaseRepositoryImpl struct {
	db *sql.DB
}

func NewFraudCaseRepository(db *sql.DB) *FraudCaseRepositoryImpl {
	return &FraudCaseRepositoryImpl{db: db}
}

func (r *FraudCaseRepositoryImpl) CreateCase(ctx context.Context, fraudCase *fraudmodels.FraudCase) error {
	if r.db == nil {
		return errors.New("database connection is nil")
	}
	query := `
	INSERT INTO fraud_cases (alert_id, user_id, status, description, resolution, created_at, updated_at)
	VALUES ($1, $2, $3, $4, $5, $6, $7)
	RETURNING id`
	return r.db.QueryRowContext(ctx, query, fraudCase.AlertID, fraudCase.UserID, fraudCase.Status, fraudCase.Description, fraudCase.Resolution, fraudCase.CreatedAt, fraudCase.UpdatedAt).Scan(&fraudCase.ID)
}

func (r *FraudCaseRepositoryImpl) GetCase(ctx context.Context, id int64) (*fraudmodels.FraudCase, error) {
	if r.db == nil {
		return nil, errors.New("database connection is nil")
	}
	query := `
	SELECT id, alert_id, user_id, status, description, resolution, created_at, updated_at
	FROM fraud_cases
	WHERE id = $1`
	fraudCase := &fraudmodels.FraudCase{}
	err := r.db.QueryRowContext(ctx, query, id).Scan(
		&fraudCase.ID, &fraudCase.AlertID, &fraudCase.UserID, &fraudCase.Status,
		&fraudCase.Description, &fraudCase.Resolution, &fraudCase.CreatedAt, &fraudCase.UpdatedAt,
	)
	if err != nil {
		return nil, err
	}
	return fraudCase, nil
}

func (r *FraudCaseRepositoryImpl) GetCasesByUser(ctx context.Context, userID string, limit, offset int) ([]*fraudmodels.FraudCase, error) {
	if r.db == nil {
		return nil, errors.New("database connection is nil")
	}
	query := `
	SELECT id, alert_id, user_id, status, description, resolution, created_at, updated_at
	FROM fraud_cases
	WHERE user_id = $1
	ORDER BY created_at DESC
	LIMIT $2 OFFSET $3`
	rows, err := r.db.QueryContext(ctx, query, userID, limit, offset)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var cases []*fraudmodels.FraudCase
	for rows.Next() {
		fraudCase := &fraudmodels.FraudCase{}
		err := rows.Scan(
			&fraudCase.ID, &fraudCase.AlertID, &fraudCase.UserID, &fraudCase.Status,
			&fraudCase.Description, &fraudCase.Resolution, &fraudCase.CreatedAt, &fraudCase.UpdatedAt,
		)
		if err != nil {
			return nil, err
		}
		cases = append(cases, fraudCase)
	}
	return cases, nil
}

func (r *FraudCaseRepositoryImpl) UpdateCaseStatus(ctx context.Context, caseID int64, status string) error {
	if r.db == nil {
		return errors.New("database connection is nil")
	}
	query := `UPDATE fraud_cases SET status = $1, updated_at = $2 WHERE id = $3`
	_, err := r.db.ExecContext(ctx, query, status, time.Now(), caseID)
	return err
}

func (r *FraudCaseRepositoryImpl) UpdateCaseResolution(ctx context.Context, caseID int64, resolution string) error {
	if r.db == nil {
		return errors.New("database connection is nil")
	}
	query := `UPDATE fraud_cases SET resolution = $1, status = 'resolved', updated_at = $2 WHERE id = $3`
	_, err := r.db.ExecContext(ctx, query, resolution, time.Now(), caseID)
	return err
}
