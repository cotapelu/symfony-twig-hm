package landconstruction

import (
	"context"
	"database/sql"
	"errors"
	"sync"
	"time"

	landmodels "community.local/modules/LandConstruction/internal/models"
)

var (
	ErrLandRecordNotFound   = errors.New("land record not found")
	ErrPermitNotFound        = errors.New("construction permit not found")
	ErrInvalidArea           = errors.New("invalid area value")
	ErrInvalidPlotNumber     = errors.New("plot number is required")
	ErrInvalidOwner          = errors.New("owner is required")
	ErrInvalidAddress        = errors.New("address is required")
	ErrInvalidLandType       = errors.New("invalid land type")
	ErrDuplicatePlotNumber  = errors.New("plot number already exists")
	ErrInvalidPermitType     = errors.New("invalid permit type")
	ErrInvalidApplicant      = errors.New("applicant is required")
)

type LandRepository interface {
	CreateLandRecord(ctx context.Context, record *landmodels.LandRecord) error
	GetLandRecordByID(ctx context.Context, id int64) (*landmodels.LandRecord, error)
	GetLandRecordByPlotNumber(ctx context.Context, plotNumber string) (*landmodels.LandRecord, error)
	GetAllLandRecords(ctx context.Context, limit, offset int) ([]*landmodels.LandRecord, error)
	UpdateLandRecord(ctx context.Context, record *landmodels.LandRecord) error
	DeleteLandRecord(ctx context.Context, id int64) error
	GetLandStatistics(ctx context.Context) (*landmodels.LandStatistics, error)
}

type PermitRepository interface {
	CreatePermit(ctx context.Context, permit *landmodels.ConstructionPermit) error
	GetPermitByID(ctx context.Context, id int64) (*landmodels.ConstructionPermit, error)
	GetAllPermits(ctx context.Context, limit, offset int) ([]*landmodels.ConstructionPermit, error)
	UpdatePermit(ctx context.Context, permit *landmodels.ConstructionPermit) error
	DeletePermit(ctx context.Context, id int64) error
	GetPermitStatistics(ctx context.Context) (*landmodels.PermitStatistics, error)
}

type LandService struct {
	landRepo  LandRepository
	permitRepo PermitRepository
	mu        sync.RWMutex
}

func NewLandService(landRepo LandRepository, permitRepo PermitRepository) *LandService {
	return &LandService{
		landRepo:   landRepo,
		permitRepo: permitRepo,
	}
}

// ============== LAND RECORD OPERATIONS ==============

func (s *LandService) CreateLandRecord(ctx context.Context, req *CreateLandRecordRequest) (*landmodels.LandRecord, error) {
	// Input validation
	if err := s.validateLandRecordRequest(req); err != nil {
		return nil, err
	}

	// Check for duplicate plot number
	existing, err := s.landRepo.GetLandRecordByPlotNumber(ctx, req.PlotNumber)
	if err == nil && existing != nil {
		return nil, ErrDuplicatePlotNumber
	}

	record := &landmodels.LandRecord{
		PlotNumber: req.PlotNumber,
		Owner:      req.Owner,
		Area:       req.Area,
		Address:    req.Address,
		Type:       req.Type,
		Status:     "pending",
		CreatedAt:  time.Now(),
		UpdatedAt:  time.Now(),
	}

	if err := s.landRepo.CreateLandRecord(ctx, record); err != nil {
		return nil, err
	}

	return record, nil
}

func (s *LandService) validateLandRecordRequest(req *CreateLandRecordRequest) error {
	if req.PlotNumber == "" {
		return ErrInvalidPlotNumber
	}
	if req.Owner == "" {
		return ErrInvalidOwner
	}
	if req.Address == "" {
		return ErrInvalidAddress
	}
	if req.Area <= 0 {
		return ErrInvalidArea
	}
	if req.Area > 1000000 { // Max 1 million square meters
		return ErrInvalidArea
	}
	// Validate land type
	validTypes := map[string]bool{
		"đất ở":                  true,
		"đất nông nghiệp":        true,
		"đất phi nông nghiệp":    true,
		"đất công nghiệp":        true,
		"đất nuôi trồng thủy sản": true,
	}
	if req.Type != "" && !validTypes[req.Type] {
		return ErrInvalidLandType
	}
	return nil
}

func (s *LandService) GetLandRecord(ctx context.Context, id int64) (*landmodels.LandRecord, error) {
	if id <= 0 {
		return nil, ErrLandRecordNotFound
	}

	record, err := s.landRepo.GetLandRecordByID(ctx, id)
	if err != nil {
		if errors.Is(err, sql.ErrNoRows) {
			return nil, ErrLandRecordNotFound
		}
		return nil, err
	}

	return record, nil
}

func (s *LandService) GetLandRecords(ctx context.Context, limit, offset int) ([]*landmodels.LandRecord, error) {
	if limit <= 0 {
		limit = 20
	}
	if limit > 100 {
		limit = 100
	}
	if offset < 0 {
		offset = 0
	}

	return s.landRepo.GetAllLandRecords(ctx, limit, offset)
}

func (s *LandService) GetLandRecordByPlotNumber(ctx context.Context, plotNumber string) (*landmodels.LandRecord, error) {
	if plotNumber == "" {
		return nil, ErrInvalidPlotNumber
	}

	record, err := s.landRepo.GetLandRecordByPlotNumber(ctx, plotNumber)
	if err != nil {
		if errors.Is(err, sql.ErrNoRows) {
			return nil, ErrLandRecordNotFound
		}
		return nil, err
	}

	return record, nil
}

func (s *LandService) UpdateLandRecord(ctx context.Context, id int64, req *UpdateLandRecordRequest) (*landmodels.LandRecord, error) {
	if id <= 0 {
		return nil, ErrLandRecordNotFound
	}

	record, err := s.landRepo.GetLandRecordByID(ctx, id)
	if err != nil {
		if errors.Is(err, sql.ErrNoRows) {
			return nil, ErrLandRecordNotFound
		}
		return nil, err
	}

	// Update fields if provided
	if req.Owner != "" {
		record.Owner = req.Owner
	}
	if req.Area > 0 {
		record.Area = req.Area
	}
	if req.Address != "" {
		record.Address = req.Address
	}
	if req.Type != "" {
		record.Type = req.Type
	}
	if req.Status != "" {
		record.Status = req.Status
	}

	record.UpdatedAt = time.Now()

	if err := s.landRepo.UpdateLandRecord(ctx, record); err != nil {
		return nil, err
	}

	return record, nil
}

func (s *LandService) DeleteLandRecord(ctx context.Context, id int64) error {
	if id <= 0 {
		return ErrLandRecordNotFound
	}

	return s.landRepo.DeleteLandRecord(ctx, id)
}

func (s *LandService) GetLandStatistics(ctx context.Context) (*landmodels.LandStatistics, error) {
	return s.landRepo.GetLandStatistics(ctx)
}

// ============== CONSTRUCTION PERMIT OPERATIONS ==============

func (s *LandService) CreatePermit(ctx context.Context, req *CreatePermitRequest) (*landmodels.ConstructionPermit, error) {
	// Input validation
	if err := s.validatePermitRequest(req); err != nil {
		return nil, err
	}

	permit := &landmodels.ConstructionPermit{
		Applicant:     req.Applicant,
		Address:       req.Address,
		PermitType:    req.PermitType,
		Area:          req.Area,
		Description:   req.Description,
		Status:        "pending",
		SubmittedDate: time.Now(),
		CreatedAt:    time.Now(),
		UpdatedAt:    time.Now(),
	}

	if err := s.permitRepo.CreatePermit(ctx, permit); err != nil {
		return nil, err
	}

	return permit, nil
}

func (s *LandService) validatePermitRequest(req *CreatePermitRequest) error {
	if req.Applicant == "" {
		return ErrInvalidApplicant
	}
	if req.Address == "" {
		return ErrInvalidAddress
	}
	if req.PermitType == "" {
		return ErrInvalidPermitType
	}

	// Validate permit type
	validTypes := map[string]bool{
		"xây dựng mới":  true,
		"cải tạo":       true,
		"sửa chữa":      true,
		"phá dỡ":        true,
		"thay đổi quy hoạch": true,
	}
	if !validTypes[req.PermitType] {
		return ErrInvalidPermitType
	}

	if req.Area <= 0 {
		return ErrInvalidArea
	}
	if req.Area > 100000 { // Max 100,000 square meters
		return ErrInvalidArea
	}

	return nil
}

func (s *LandService) GetPermit(ctx context.Context, id int64) (*landmodels.ConstructionPermit, error) {
	if id <= 0 {
		return nil, ErrPermitNotFound
	}

	permit, err := s.permitRepo.GetPermitByID(ctx, id)
	if err != nil {
		if errors.Is(err, sql.ErrNoRows) {
			return nil, ErrPermitNotFound
		}
		return nil, err
	}

	return permit, nil
}

func (s *LandService) GetPermits(ctx context.Context, limit, offset int) ([]*landmodels.ConstructionPermit, error) {
	if limit <= 0 {
		limit = 20
	}
	if limit > 100 {
		limit = 100
	}
	if offset < 0 {
		offset = 0
	}

	return s.permitRepo.GetAllPermits(ctx, limit, offset)
}

func (s *LandService) UpdatePermit(ctx context.Context, id int64, req *UpdatePermitRequest) (*landmodels.ConstructionPermit, error) {
	if id <= 0 {
		return nil, ErrPermitNotFound
	}

	permit, err := s.permitRepo.GetPermitByID(ctx, id)
	if err != nil {
		if errors.Is(err, sql.ErrNoRows) {
			return nil, ErrPermitNotFound
		}
		return nil, err
	}

	// Update fields if provided
	if req.Status != "" {
		permit.Status = req.Status
	}
	if req.AssignedTo != "" {
		permit.AssignedTo = &req.AssignedTo
	}

	permit.UpdatedAt = time.Now()

	if err := s.permitRepo.UpdatePermit(ctx, permit); err != nil {
		return nil, err
	}

	return permit, nil
}

func (s *LandService) DeletePermit(ctx context.Context, id int64) error {
	if id <= 0 {
		return ErrPermitNotFound
	}

	return s.permitRepo.DeletePermit(ctx, id)
}

func (s *LandService) GetPermitStatistics(ctx context.Context) (*landmodels.PermitStatistics, error) {
	return s.permitRepo.GetPermitStatistics(ctx)
}

// ============== REQUEST STRUCTURES ==============

type CreateLandRecordRequest struct {
	PlotNumber string  `json:"plotNumber"`
	Owner      string  `json:"owner"`
	Area       float64 `json:"area"`
	Address    string  `json:"address"`
	Type       string  `json:"type"`
}

type UpdateLandRecordRequest struct {
	Owner   string  `json:"owner"`
	Area    float64 `json:"area"`
	Address string  `json:"address"`
	Type    string  `json:"type"`
	Status  string  `json:"status"`
}

type CreatePermitRequest struct {
	Applicant   string  `json:"applicant"`
	Address     string  `json:"address"`
	PermitType  string  `json:"permitType"`
	Area        float64 `json:"area"`
	Description string  `json:"description"`
}

type UpdatePermitRequest struct {
	Status      string `json:"status"`
	AssignedTo  string `json:"assignedTo"`
}