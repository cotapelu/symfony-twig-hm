package repository

import (
	"context"
	"database/sql"
	"errors"
	"community.local/modules/location/entity"
)

type LocationHistoryRepositoryImpl struct {
	db *sql.DB
}

func NewLocationHistoryRepository(db *sql.DB) *LocationHistoryRepositoryImpl {
	return &LocationHistoryRepositoryImpl{db: db}
}

func (r *LocationHistoryRepositoryImpl) CreateHistory(ctx context.Context, history *entity.UserLocation) error {
	if r.db == nil {
		return errors.New("database connection is nil")
	}
	query := `
	INSERT INTO location_history (user_id, latitude, longitude, updated_at)
	VALUES ($1, $2, $3, $4)
	RETURNING id`
	return r.db.QueryRowContext(ctx, query, history.UserID, history.Location.Latitude, history.Location.Longitude, history.UpdatedAt).Scan(&history.ID)
}

func (r *LocationHistoryRepositoryImpl) GetHistory(ctx context.Context, id int64) (*entity.UserLocation, error) {
	if r.db == nil {
		return nil, errors.New("database connection is nil")
	}
	query := `
	SELECT id, user_id, latitude, longitude, updated_at
	FROM location_history
	WHERE id = $1`
	history := &entity.UserLocation{}
	err := r.db.QueryRowContext(ctx, query, id).Scan(
		&history.ID, &history.UserID, &history.Location.Latitude, &history.Location.Longitude, &history.UpdatedAt,
	)
	if err != nil {
		return nil, err
	}
	return history, nil
}

func (r *LocationHistoryRepositoryImpl) GetHistoryByUser(ctx context.Context, userID string, limit, offset int) ([]*entity.UserLocation, error) {
	if r.db == nil {
		return nil, errors.New("database connection is nil")
	}
	query := `
	SELECT id, user_id, latitude, longitude, updated_at
	FROM location_history
	WHERE user_id = $1
	ORDER BY updated_at DESC
	LIMIT $2 OFFSET $3`
	rows, err := r.db.QueryContext(ctx, query, userID, limit, offset)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var history []*entity.UserLocation
	for rows.Next() {
		h := &entity.UserLocation{}
		err := rows.Scan(
			&h.ID, &h.UserID, &h.Location.Latitude, &h.Location.Longitude, &h.UpdatedAt,
		)
		if err != nil {
			return nil, err
		}
		history = append(history, h)
	}
	return history, nil
}

func (r *LocationHistoryRepositoryImpl) GetHistoryByDateRange(ctx context.Context, userID string, startDate, endDate string, limit, offset int) ([]*entity.UserLocation, error) {
	if r.db == nil {
		return nil, errors.New("database connection is nil")
	}
	query := `
	SELECT id, user_id, latitude, longitude, updated_at
	FROM location_history
	WHERE user_id = $1 AND updated_at >= $2 AND updated_at <= $3
	ORDER BY updated_at DESC
	LIMIT $4 OFFSET $5`
	rows, err := r.db.QueryContext(ctx, query, userID, startDate, endDate, limit, offset)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var history []*entity.UserLocation
	for rows.Next() {
		h := &entity.UserLocation{}
		err := rows.Scan(
			&h.ID, &h.UserID, &h.Location.Latitude, &h.Location.Longitude, &h.UpdatedAt,
		)
		if err != nil {
			return nil, err
		}
		history = append(history, h)
	}
	return history, nil
}

func (r *LocationHistoryRepositoryImpl) DeleteOldHistory(ctx context.Context, days int) error {
	if r.db == nil {
		return errors.New("database connection is nil")
	}
	query := `DELETE FROM location_history WHERE updated_at < NOW() - INTERVAL '1 day' * $1`
	_, err := r.db.ExecContext(ctx, query, days)
	return err
}
