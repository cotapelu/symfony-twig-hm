package service

import (
	"context"
	"errors"
	"testing"
	"time"

	"community.local/internal/models"
)

type mockLandRepo struct {
	records []*models.LandRecord
	permits []*models.LandPermit
}

func (m *mockLandRepo) CreateRecord(ctx context.Context, record *models.LandRecord) error {
	record.ID = "rec-" + time.Now().Format("20060102150405")
	record.CreatedAt = time.Now()
	record.UpdatedAt = time.Now()
	m.records = append(m.records, record)
	return nil
}
func (m *mockLandRepo) GetRecord(ctx context.Context, id string) (*models.LandRecord, error) {
	for _, r := range m.records {
		if r.ID == id {
			return r, nil
		}
	}
	return nil, errors.New("not found")
}
func (m *mockLandRepo) GetRecordByPlotNumber(ctx context.Context, plotNumber string) (*models.LandRecord, error) {
	for _, r := range m.records {
		if r.PlotNumber == plotNumber {
			return r, nil
		}
	}
	return nil, errors.New("not found")
}
func (m *mockLandRepo) GetRecordsByOwner(ctx context.Context, ownerID string) ([]*models.LandRecord, error) {
	var res []*models.LandRecord
	for _, r := range m.records {
		if r.OwnerID == ownerID {
			res = append(res, r)
		}
	}
	return res, nil
}
func (m *mockLandRepo) CreatePermit(ctx context.Context, permit *models.LandPermit) error {
	permit.ID = "perm-" + time.Now().Format("20060102150405")
	permit.CreatedAt = time.Now()
	permit.UpdatedAt = time.Now()
	m.permits = append(m.permits, permit)
	return nil
}
func (m *mockLandRepo) GetPermit(ctx context.Context, id string) (*models.LandPermit, error) {
	for _, p := range m.permits {
		if p.ID == id {
			return p, nil
		}
	}
	return nil, errors.New("not found")
}
func (m *mockLandRepo) GetPermitsByApplicant(ctx context.Context, applicantID string) ([]*models.LandPermit, error) {
	var res []*models.LandPermit
	for _, p := range m.permits {
		if p.ApplicantID == applicantID {
			res = append(res, p)
		}
	}
	return res, nil
}

func TestLand_CreateRecord(t *testing.T) {
	repo := &mockLandRepo{}
	svc := NewLandService(repo)
	ctx := context.Background()
	rec, err := svc.CreateLandRecord(ctx, "owner-1", "PLOT-001", "RESIDENTIAL", "Hanoi", 100.5)
	if err != nil {
		t.Fatalf("CreateLandRecord failed: %v", err)
	}
	if rec.OwnerID != "owner-1" {
		t.Errorf("Wrong owner")
	}
	if rec.PlotNumber != "PLOT-001" {
		t.Errorf("Wrong plot number")
	}
	if rec.Type != "RESIDENTIAL" {
		t.Errorf("Wrong land type")
	}
}

func TestLand_GetRecordsByOwner(t *testing.T) {
	repo := &mockLandRepo{
		records: []*models.LandRecord{
			{ID: "r1", OwnerID: "owner-1", PlotNumber: "p1", Area: 100, Address: "Addr1", Type: "RESIDENTIAL", Status: "ACTIVE"},
			{ID: "r2", OwnerID: "owner-1", PlotNumber: "p2", Area: 200, Address: "Addr2", Type: "AGRICULTURAL", Status: "ACTIVE"},
			{ID: "r3", OwnerID: "owner-2", PlotNumber: "p3", Area: 150, Address: "Addr3", Type: "COMMERCIAL", Status: "ACTIVE"},
		},
	}
	svc := NewLandService(repo)
	ctx := context.Background()
	recs, err := svc.GetRecordsByOwner(ctx, "owner-1")
	if err != nil {
		t.Fatalf("GetRecordsByOwner failed: %v", err)
	}
	if len(recs) != 2 {
		t.Errorf("Expected 2 records for owner-1, got %d", len(recs))
	}
}

func TestLand_ApplyPermit(t *testing.T) {
	repo := &mockLandRepo{}
	svc := NewLandService(repo)
	ctx := context.Background()
	perm, err := svc.ApplyPermit(ctx, "rec-1", "applicant-1", "BUILDING")
	if err != nil {
		t.Fatalf("ApplyPermit failed: %v", err)
	}
	if perm.PermitType != "BUILDING" {
		t.Errorf("Wrong permit type")
	}
	if perm.Status != "PENDING" {
		t.Errorf("Expected PENDING, got %s", perm.Status)
	}
}

func TestLand_GetPermitsByApplicant(t *testing.T) {
	now := time.Now()
	repo := &mockLandRepo{
		permits: []*models.LandPermit{
			{ID: "p1", ApplicantID: "app-1", PermitType: "BUILDING", Status: "PENDING", CreatedAt: now},
			{ID: "p2", ApplicantID: "app-1", PermitType: "RENOVATE", Status: "APPROVED", CreatedAt: now},
			{ID: "p3", ApplicantID: "app-2", PermitType: "DEMOLISH", Status: "PENDING", CreatedAt: now},
		},
	}
	svc := NewLandService(repo)
	ctx := context.Background()
	permits, err := svc.GetPermitsByApplicant(ctx, "app-1")
	if err != nil {
		t.Fatalf("GetPermitsByApplicant failed: %v", err)
	}
	if len(permits) != 2 {
		t.Errorf("Expected 2 permits for app-1, got %d", len(permits))
	}
}

func TestGetLandRecord_Success(t *testing.T) {
	now := time.Now()
	repo := &mockLandRepo{
		records: []*models.LandRecord{
			{ID: "rec-1", OwnerID: "owner-1", PlotNumber: "PLOT-001", Area: 100.5, Type: "RESIDENTIAL", Status: "ACTIVE", CreatedAt: now, UpdatedAt: now},
		},
	}
	svc := NewLandService(repo)
	ctx := context.Background()

	rec, err := svc.GetLandRecord(ctx, "rec-1")
	if err != nil {
		t.Fatalf("GetLandRecord failed: %v", err)
	}
	if rec.ID != "rec-1" {
		t.Errorf("Expected ID rec-1, got %s", rec.ID)
	}
}

func TestGetLandRecord_NotFound(t *testing.T) {
	repo := &mockLandRepo{records: []*models.LandRecord{}}
	svc := NewLandService(repo)
	ctx := context.Background()

	_, err := svc.GetLandRecord(ctx, "nonexistent")
	if err == nil {
		t.Fatal("Expected error, got nil")
	}
}

func TestGetPermit_Success(t *testing.T) {
	now := time.Now()
	repo := &mockLandRepo{
		permits: []*models.LandPermit{
			{ID: "perm-1", LandRecordID: "rec-1", ApplicantID: "app-1", PermitType: "BUILDING", Status: "PENDING", CreatedAt: now, UpdatedAt: now},
		},
	}
	svc := NewLandService(repo)
	ctx := context.Background()

	perm, err := svc.GetPermit(ctx, "perm-1")
	if err != nil {
		t.Fatalf("GetPermit failed: %v", err)
	}
	if perm.ID != "perm-1" {
		t.Errorf("Expected ID perm-1, got %s", perm.ID)
	}
}

func TestGetPermit_NotFound(t *testing.T) {
	repo := &mockLandRepo{permits: []*models.LandPermit{}}
	svc := NewLandService(repo)
	ctx := context.Background()

	_, err := svc.GetPermit(ctx, "nonexistent")
	if err == nil {
		t.Fatal("Expected error, got nil")
	}
}
