package service

import (
	"context"
	"errors"
	"time"

	"community.local/internal/models"
)

type LandService struct {
	repo models.LandRepository
}

func NewLandService(repo models.LandRepository) *LandService {
	return &LandService{repo: repo}
}

// CreateLandRecord creates a new land record
func (s *LandService) CreateLandRecord(ctx context.Context, ownerID, plotNumber, landType, address string, area float64) (*models.LandRecord, error) {
	if ownerID == "" || plotNumber == "" || landType == "" {
		return nil, errors.New("missing required fields")
	}
	if area <= 0 {
		return nil, errors.New("area must be positive")
	}
	now := time.Now()
	record := &models.LandRecord{
		OwnerID:    ownerID,
		PlotNumber: plotNumber,
		Address:    address,
		Area:       area,
		Type:       landType,
		Status:     "ACTIVE",
		CreatedAt:  now,
		UpdatedAt:  now,
	}
	err := s.repo.CreateRecord(ctx, record)
	if err != nil {
		return nil, err
	}
	return record, nil
}

// GetLandRecord retrieves by ID
func (s *LandService) GetLandRecord(ctx context.Context, id string) (*models.LandRecord, error) {
	rec, err := s.repo.GetRecord(ctx, id)
	if err != nil {
		return nil, err
	}
	if rec == nil {
		return nil, errors.New("land record not found")
	}
	return rec, nil
}

// GetRecordsByOwner returns all land records for an owner
func (s *LandService) GetRecordsByOwner(ctx context.Context, ownerID string) ([]*models.LandRecord, error) {
	if ownerID == "" {
		return nil, errors.New("owner ID required")
	}
	recs, err := s.repo.GetRecordsByOwner(ctx, ownerID)
	if err != nil {
		return nil, err
	}
	return recs, nil
}

// ApplyPermit creates a new land permit
func (s *LandService) ApplyPermit(ctx context.Context, recordID, applicantID, permitType string) (*models.LandPermit, error) {
	if recordID == "" || applicantID == "" || permitType == "" {
		return nil, errors.New("missing required fields")
	}
	now := time.Now()
	permit := &models.LandPermit{
		LandRecordID: recordID,
		ApplicantID:  applicantID,
		PermitType:   permitType,
		Status:       "PENDING",
		CreatedAt:    now,
		UpdatedAt:    now,
	}
	err := s.repo.CreatePermit(ctx, permit)
	if err != nil {
		return nil, err
	}
	return permit, nil
}

// GetPermit retrieves a permit by ID
func (s *LandService) GetPermit(ctx context.Context, id string) (*models.LandPermit, error) {
	perm, err := s.repo.GetPermit(ctx, id)
	if err != nil {
		return nil, err
	}
	if perm == nil {
		return nil, errors.New("permit not found")
	}
	return perm, nil
}

// GetPermitsByApplicant returns permits for an applicant
func (s *LandService) GetPermitsByApplicant(ctx context.Context, applicantID string) ([]*models.LandPermit, error) {
	if applicantID == "" {
		return nil, errors.New("applicant ID required")
	}
	permits, err := s.repo.GetPermitsByApplicant(ctx, applicantID)
	if err != nil {
		return nil, err
	}
	return permits, nil
}
