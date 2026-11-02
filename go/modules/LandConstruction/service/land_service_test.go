package landconstruction

import (
	"context"
	"database/sql"
	"errors"
	"testing"
	"time"

	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/mock"

	landmodels "community.local/modules/LandConstruction/internal/models"
)

// MockLandRepository mocks LandRepository
type MockLandRepository struct {
	mock.Mock
}

func (m *MockLandRepository) CreateLandRecord(ctx context.Context, record *landmodels.LandRecord) error {
	args := m.Called(ctx, record)
	return args.Error(0)
}

func (m *MockLandRepository) GetLandRecordByID(ctx context.Context, id int64) (*landmodels.LandRecord, error) {
	args := m.Called(ctx, id)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).(*landmodels.LandRecord), args.Error(1)
}

func (m *MockLandRepository) GetLandRecordByPlotNumber(ctx context.Context, plotNumber string) (*landmodels.LandRecord, error) {
	args := m.Called(ctx, plotNumber)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).(*landmodels.LandRecord), args.Error(1)
}

func (m *MockLandRepository) GetAllLandRecords(ctx context.Context, limit, offset int) ([]*landmodels.LandRecord, error) {
	args := m.Called(ctx, limit, offset)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).([]*landmodels.LandRecord), args.Error(1)
}

func (m *MockLandRepository) UpdateLandRecord(ctx context.Context, record *landmodels.LandRecord) error {
	args := m.Called(ctx, record)
	return args.Error(0)
}

func (m *MockLandRepository) DeleteLandRecord(ctx context.Context, id int64) error {
	args := m.Called(ctx, id)
	return args.Error(0)
}

func (m *MockLandRepository) GetLandStatistics(ctx context.Context) (*landmodels.LandStatistics, error) {
	args := m.Called(ctx)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).(*landmodels.LandStatistics), args.Error(1)
}

// MockPermitRepository mocks PermitRepository
type MockPermitRepository struct {
	mock.Mock
}

func (m *MockPermitRepository) CreatePermit(ctx context.Context, permit *landmodels.ConstructionPermit) error {
	args := m.Called(ctx, permit)
	return args.Error(0)
}

func (m *MockPermitRepository) GetPermitByID(ctx context.Context, id int64) (*landmodels.ConstructionPermit, error) {
	args := m.Called(ctx, id)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).(*landmodels.ConstructionPermit), args.Error(1)
}

func (m *MockPermitRepository) GetAllPermits(ctx context.Context, limit, offset int) ([]*landmodels.ConstructionPermit, error) {
	args := m.Called(ctx, limit, offset)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).([]*landmodels.ConstructionPermit), args.Error(1)
}

func (m *MockPermitRepository) UpdatePermit(ctx context.Context, permit *landmodels.ConstructionPermit) error {
	args := m.Called(ctx, permit)
	return args.Error(0)
}

func (m *MockPermitRepository) DeletePermit(ctx context.Context, id int64) error {
	args := m.Called(ctx, id)
	return args.Error(0)
}

func (m *MockPermitRepository) GetPermitStatistics(ctx context.Context) (*landmodels.PermitStatistics, error) {
	args := m.Called(ctx)
	if args.Get(0) == nil {
		return nil, args.Error(1)
	}
	return args.Get(0).(*landmodels.PermitStatistics), args.Error(1)
}

// Tests

func TestLandService_CreateLandRecord_Validation(t *testing.T) {
	landRepo := &MockLandRepository{}
	permitRepo := &MockPermitRepository{}
	svc := NewLandService(landRepo, permitRepo)

	now := time.Now()

	reqValid := &CreateLandRecordRequest{
		PlotNumber: "A-123",
		Owner:      "John Doe",
		Area:       500.5,
		Address:    "123 Main St",
		Type:       "đất ở",
	}

	// Mock check for duplicate
	landRepo.On("GetLandRecordByPlotNumber", context.Background(), reqValid.PlotNumber).Return(nil, nil).Once()
	landRepo.On("CreateLandRecord", context.Background(), mock.AnythingOfType("*landmodels.LandRecord")).Return(nil).Once().Run(func(args mock.Arguments) {
		rec := args.Get(1).(*landmodels.LandRecord)
		rec.ID = 1
		rec.CreatedAt = now
		rec.UpdatedAt = now
	})

	record, err := svc.CreateLandRecord(context.Background(), reqValid)
	assert.NoError(t, err)
	assert.NotNil(t, record)
	assert.Equal(t, reqValid.PlotNumber, record.PlotNumber)
	assert.Equal(t, "pending", record.Status)

	landRepo.AssertExpectations(t)

	// Validation tests
	tests := []struct {
		name  string
		req   *CreateLandRecordRequest
		err   error
		check func(*testing.T, error)
	}{
		{
			name: "empty plot number",
			req: &CreateLandRecordRequest{
				PlotNumber: "",
				Owner:      "John",
				Area:       100,
				Address:    "Addr",
				Type:       "đất ở",
			},
			err: ErrInvalidPlotNumber,
		},
		{
			name: "empty owner",
			req: &CreateLandRecordRequest{
				PlotNumber: "A-1",
				Owner:      "",
				Area:       100,
				Address:    "Addr",
				Type:       "đất ở",
			},
			err: ErrInvalidOwner,
		},
		{
			name: "empty address",
			req: &CreateLandRecordRequest{
				PlotNumber: "A-1",
				Owner:      "John",
				Area:       100,
				Address:    "",
				Type:       "đất ở",
			},
			err: ErrInvalidAddress,
		},
		{
			name: "invalid area zero",
			req: &CreateLandRecordRequest{
				PlotNumber: "A-1",
				Owner:      "John",
				Area:       0,
				Address:    "Addr",
				Type:       "đất ở",
			},
			err: ErrInvalidArea,
		},
		{
			name: "invalid area negative",
			req: &CreateLandRecordRequest{
				PlotNumber: "A-1",
				Owner:      "John",
				Area:       -50,
				Address:    "Addr",
				Type:       "đất ở",
			},
			err: ErrInvalidArea,
		},
		{
			name: "invalid land type",
			req: &CreateLandRecordRequest{
				PlotNumber: "A-1",
				Owner:      "John",
				Area:       100,
				Address:    "Addr",
				Type:       "invalid",
			},
			err: errors.New("invalid land type"),
		},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			_, err := svc.CreateLandRecord(context.Background(), tt.req)
			assert.Equal(t, tt.err.Error(), err.Error())
		})
	}

	// Duplicate plot number
	landRepo2 := &MockLandRepository{}
	permitRepo2 := &MockPermitRepository{}
	svc2 := NewLandService(landRepo2, permitRepo2)
	existing := &landmodels.LandRecord{PlotNumber: reqValid.PlotNumber}
	landRepo2.On("GetLandRecordByPlotNumber", context.Background(), reqValid.PlotNumber).Return(existing, nil).Once()
	_, err = svc2.CreateLandRecord(context.Background(), reqValid)
	assert.ErrorIs(t, err, ErrDuplicatePlotNumber)
}

func TestLandService_GetLandRecord(t *testing.T) {
	ctx := context.Background()
	landRepo := &MockLandRepository{}
	permitRepo := &MockPermitRepository{}
	svc := NewLandService(landRepo, permitRepo)

	record := &landmodels.LandRecord{ID: 1, PlotNumber: "A-123"}
	landRepo.On("GetLandRecordByID", ctx, int64(1)).Return(record, nil)

	result, err := svc.GetLandRecord(ctx, 1)
	assert.NoError(t, err)
	assert.Equal(t, record, result)

	// Invalid ID (<=0)
	_, err = svc.GetLandRecord(ctx, 0)
	assert.ErrorIs(t, err, ErrLandRecordNotFound)

	// Not found from repo
	landRepo.On("GetLandRecordByID", ctx, int64(999)).Return(nil, sql.ErrNoRows)
	_, err = svc.GetLandRecord(ctx, 999)
	assert.ErrorIs(t, err, ErrLandRecordNotFound)

	landRepo.AssertExpectations(t)
}

func TestLandService_GetLandRecordByPlotNumber(t *testing.T) {
	ctx := context.Background()
	landRepo := &MockLandRepository{}
	permitRepo := &MockPermitRepository{}
	svc := NewLandService(landRepo, permitRepo)

	record := &landmodels.LandRecord{PlotNumber: "B-456"}
	landRepo.On("GetLandRecordByPlotNumber", ctx, "B-456").Return(record, nil)

	result, err := svc.GetLandRecordByPlotNumber(ctx, "B-456")
	assert.NoError(t, err)
	assert.Equal(t, record, result)

	// Not found
	landRepo.On("GetLandRecordByPlotNumber", ctx, "none").Return(nil, sql.ErrNoRows)
	_, err = svc.GetLandRecordByPlotNumber(ctx, "none")
	assert.ErrorIs(t, err, ErrLandRecordNotFound)

	landRepo.AssertExpectations(t)
}

func TestLandService_GetLandRecords(t *testing.T) {
	ctx := context.Background()
	landRepo := &MockLandRepository{}
	permitRepo := &MockPermitRepository{}
	svc := NewLandService(landRepo, permitRepo)

	records := []*landmodels.LandRecord{{ID: 1}, {ID: 2}}
	landRepo.On("GetAllLandRecords", ctx, 20, 0).Return(records, nil)

	result, err := svc.GetLandRecords(ctx, 20, 0)
	assert.NoError(t, err)
	assert.Len(t, result, 2)

	// Default limit and offset
	landRepo.On("GetAllLandRecords", ctx, 20, 10).Return(records, nil)
	result, _ = svc.GetLandRecords(ctx, 0, 10)
	assert.Len(t, result, 2)

	// Limit clamped to 100
	landRepo.On("GetAllLandRecords", ctx, 100, 0).Return(records, nil)
	result, _ = svc.GetLandRecords(ctx, 200, 0)
	assert.Len(t, result, 2)

	landRepo.AssertExpectations(t)
}

func TestLandService_UpdateLandRecord(t *testing.T) {
	ctx := context.Background()
	landRepo := &MockLandRepository{}
	permitRepo := &MockPermitRepository{}
	svc := NewLandService(landRepo, permitRepo)

	existing := &landmodels.LandRecord{ID: 1, Owner: "Old", Area: 100, Address: "OldAddr", Type: "old", Status: "active"}
	landRepo.On("GetLandRecordByID", ctx, int64(1)).Return(existing, nil)
	landRepo.On("UpdateLandRecord", ctx, existing).Return(nil).Once()

	req := &UpdateLandRecordRequest{
		Owner:  "New",
		Area:   200,
		Status: "updated",
	}
	// The service updates only non-zero fields if provided; but since Owner, Area, Status are provided, it will update those.
	before := time.Now()
	updated, err := svc.UpdateLandRecord(ctx, 1, req)
	assert.NoError(t, err)
	assert.Equal(t, "New", updated.Owner)
	assert.Equal(t, 200.0, updated.Area)
	assert.Equal(t, "updated", updated.Status)
	assert.True(t, updated.UpdatedAt.After(before), "updated time should be after call")

	landRepo.AssertExpectations(t)

	// Invalid ID
	_, err = svc.UpdateLandRecord(ctx, 0, req)
	assert.ErrorIs(t, err, ErrLandRecordNotFound)

	// Not found
	landRepo2 := &MockLandRepository{}
	permitRepo2 := &MockPermitRepository{}
	svc2 := NewLandService(landRepo2, permitRepo2)
	landRepo2.On("GetLandRecordByID", ctx, int64(999)).Return(nil, sql.ErrNoRows)
	_, err = svc2.UpdateLandRecord(ctx, 999, req)
	assert.ErrorIs(t, err, ErrLandRecordNotFound)
}

func TestLandService_DeleteLandRecord(t *testing.T) {
	ctx := context.Background()
	landRepo := &MockLandRepository{}
	permitRepo := &MockPermitRepository{}
	svc := NewLandService(landRepo, permitRepo)

	landRepo.On("DeleteLandRecord", ctx, int64(1)).Return(nil).Once()

	err := svc.DeleteLandRecord(ctx, 1)
	assert.NoError(t, err)

	// Invalid ID
	err = svc.DeleteLandRecord(ctx, 0)
	assert.ErrorIs(t, err, ErrLandRecordNotFound)

	landRepo.AssertExpectations(t)
}

func TestLandService_GetLandStatistics(t *testing.T) {
	ctx := context.Background()
	landRepo := &MockLandRepository{}
	permitRepo := &MockPermitRepository{}
	svc := NewLandService(landRepo, permitRepo)

	stats := &landmodels.LandStatistics{
		TotalRecords: 100,
		TotalArea:    50000.0,
		ByType: map[string]int{
			"đất ở": 50,
		},
	}
	landRepo.On("GetLandStatistics", ctx).Return(stats, nil)

	result, err := svc.GetLandStatistics(ctx)
	assert.NoError(t, err)
	assert.Equal(t, stats, result)

	landRepo.AssertExpectations(t)
}

// ============== PERMIT TESTS ==============

func TestLandService_CreatePermit_Validation(t *testing.T) {
	landRepo := &MockLandRepository{}
	permitRepo := &MockPermitRepository{}
	svc := NewLandService(landRepo, permitRepo)

	now := time.Now()

	reqValid := &CreatePermitRequest{
		Applicant:   "John Doe",
		Address:     "123 Main St",
		PermitType:  "xây dựng mới",
		Area:        200.0,
		Description: "New house",
	}

	permitRepo.On("CreatePermit", context.Background(), mock.AnythingOfType("*landmodels.ConstructionPermit")).Return(nil).Once().Run(func(args mock.Arguments) {
		permit := args.Get(1).(*landmodels.ConstructionPermit)
		permit.ID = 1
		permit.CreatedAt = now
		permit.UpdatedAt = now
	})

	permit, err := svc.CreatePermit(context.Background(), reqValid)
	assert.NoError(t, err)
	assert.NotNil(t, permit)
	assert.Equal(t, "pending", permit.Status)

	// Validation tests
	tests := []struct {
		name string
		req  *CreatePermitRequest
		err  error
	}{
		{
			name: "empty applicant",
			req: &CreatePermitRequest{
				Applicant:  "",
				Address:    "Addr",
				PermitType: "xây dựng mới",
				Area:       100,
			},
			err: ErrInvalidApplicant,
		},
		{
			name: "empty address",
			req: &CreatePermitRequest{
				Applicant:  "John",
				Address:    "",
				PermitType: "xây dựng mới",
				Area:       100,
			},
			err: ErrInvalidAddress,
		},
		{
			name: "empty permit type",
			req: &CreatePermitRequest{
				Applicant:  "John",
				Address:    "Addr",
				PermitType: "",
				Area:       100,
			},
			err: ErrInvalidPermitType,
		},
		{
			name: "invalid permit type",
			req: &CreatePermitRequest{
				Applicant:  "John",
				Address:    "Addr",
				PermitType: "invalid",
				Area:       100,
			},
			err: ErrInvalidPermitType,
		},
		{
			name: "invalid area zero",
			req: &CreatePermitRequest{
				Applicant:  "John",
				Address:    "Addr",
				PermitType: "xây dựng mới",
				Area:       0,
			},
			err: ErrInvalidArea,
		},
		{
			name: "invalid area negative",
			req: &CreatePermitRequest{
				Applicant:  "John",
				Address:    "Addr",
				PermitType: "xây dựng mới",
				Area:       -50,
			},
			err: ErrInvalidArea,
		},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			_, err := svc.CreatePermit(context.Background(), tt.req)
			assert.ErrorIs(t, err, tt.err)
		})
	}
}

func TestLandService_GetPermit(t *testing.T) {
	ctx := context.Background()
	landRepo := &MockLandRepository{}
	permitRepo := &MockPermitRepository{}
	svc := NewLandService(landRepo, permitRepo)

	permit := &landmodels.ConstructionPermit{ID: 1, Applicant: "John"}
	permitRepo.On("GetPermitByID", ctx, int64(1)).Return(permit, nil)

	result, err := svc.GetPermit(ctx, 1)
	assert.NoError(t, err)
	assert.Equal(t, permit, result)

	// Invalid ID
	_, err = svc.GetPermit(ctx, 0)
	assert.ErrorIs(t, err, ErrPermitNotFound)

	// Not found
	permitRepo.On("GetPermitByID", ctx, int64(999)).Return(nil, sql.ErrNoRows)
	_, err = svc.GetPermit(ctx, 999)
	assert.ErrorIs(t, err, ErrPermitNotFound)

	permitRepo.AssertExpectations(t)
}

func TestLandService_GetPermits(t *testing.T) {
	ctx := context.Background()
	landRepo := &MockLandRepository{}
	permitRepo := &MockPermitRepository{}
	svc := NewLandService(landRepo, permitRepo)

	permits := []*landmodels.ConstructionPermit{{ID: 1}, {ID: 2}}
	permitRepo.On("GetAllPermits", ctx, 20, 0).Return(permits, nil)

	result, err := svc.GetPermits(ctx, 20, 0)
	assert.NoError(t, err)
	assert.Len(t, result, 2)

	// Default limit
	permitRepo.On("GetAllPermits", ctx, 20, 10).Return(permits, nil)
	result, _ = svc.GetPermits(ctx, 0, 10)
	assert.Len(t, result, 2)

	// Max limit
	permitRepo.On("GetAllPermits", ctx, 100, 0).Return(permits, nil)
	result, _ = svc.GetPermits(ctx, 200, 0)
	assert.Len(t, result, 2)

	permitRepo.AssertExpectations(t)
}

func TestLandService_UpdatePermit(t *testing.T) {
	ctx := context.Background()
	landRepo := &MockLandRepository{}
	permitRepo := &MockPermitRepository{}
	svc := NewLandService(landRepo, permitRepo)

	existing := &landmodels.ConstructionPermit{ID: 1, Status: "pending", AssignedTo: nil}
	permitRepo.On("GetPermitByID", ctx, int64(1)).Return(existing, nil)
	permitRepo.On("UpdatePermit", ctx, existing).Return(nil).Once()

	req := &UpdatePermitRequest{
		Status:     "approved",
		AssignedTo: "officer123",
	}
	updated, err := svc.UpdatePermit(ctx, 1, req)
	assert.NoError(t, err)
	assert.Equal(t, "approved", updated.Status)
	assert.NotNil(t, updated.AssignedTo)
	assert.Equal(t, "officer123", *updated.AssignedTo)

	permitRepo.AssertExpectations(t)

	// Invalid ID
	_, err = svc.UpdatePermit(ctx, 0, req)
	assert.ErrorIs(t, err, ErrPermitNotFound)

	// Not found
	landRepo2 := &MockLandRepository{}
	permitRepo2 := &MockPermitRepository{}
	svc2 := NewLandService(landRepo2, permitRepo2)
	permitRepo2.On("GetPermitByID", ctx, int64(999)).Return(nil, sql.ErrNoRows)
	_, err = svc2.UpdatePermit(ctx, 999, req)
	assert.ErrorIs(t, err, ErrPermitNotFound)
}

func TestLandService_DeletePermit(t *testing.T) {
	ctx := context.Background()
	landRepo := &MockLandRepository{}
	permitRepo := &MockPermitRepository{}
	svc := NewLandService(landRepo, permitRepo)

	permitRepo.On("DeletePermit", ctx, int64(1)).Return(nil).Once()

	err := svc.DeletePermit(ctx, 1)
	assert.NoError(t, err)

	// Invalid ID
	err = svc.DeletePermit(ctx, 0)
	assert.ErrorIs(t, err, ErrPermitNotFound)

	permitRepo.AssertExpectations(t)
}

func TestLandService_GetPermitStatistics(t *testing.T) {
	ctx := context.Background()
	landRepo := &MockLandRepository{}
	permitRepo := &MockPermitRepository{}
	svc := NewLandService(landRepo, permitRepo)

	stats := &landmodels.PermitStatistics{
		TotalPermits: 50,
		ByStatus: map[string]int{
			"pending": 10,
			"approved": 30,
			"rejected": 5,
		},
		ByPermitType: map[string]int{
			"building": 20,
			"demolition": 15,
			"renovation": 15,
		},
		AverageProcessing: 7.5,
	}
	permitRepo.On("GetPermitStatistics", ctx).Return(stats, nil)

	result, err := svc.GetPermitStatistics(ctx)
	assert.NoError(t, err)
	assert.Equal(t, stats, result)

	permitRepo.AssertExpectations(t)
}
