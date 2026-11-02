package handler

import (
	"context"
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"testing"
	"time"

	"github.com/gin-gonic/gin"
	"github.com/stretchr/testify/assert"
	"community.local/modules/Escrow/models"
)

// InMemoryEscrowRepository implements repository.Repository using an in-memory map.
type InMemoryEscrowRepository struct {
	store      map[string]*models.Escrow
	GetErr     error
	UpdateErr  error
	CreateErr  error
}

func newInMemoryEscrowRepo() *InMemoryEscrowRepository {
	return &InMemoryEscrowRepository{
		store: make(map[string]*models.Escrow),
	}
}

func (r *InMemoryEscrowRepository) Create(ctx context.Context, escrow *models.Escrow) error {
	if r.CreateErr != nil {
		return r.CreateErr
	}
	r.store[escrow.ID] = escrow
	return nil
}

func (r *InMemoryEscrowRepository) GetByID(ctx context.Context, id string) (*models.Escrow, error) {
	if r.GetErr != nil {
		return nil, r.GetErr
	}
	esc, ok := r.store[id]
	if !ok {
		return nil, nil
	}
	return esc, nil
}

func (r *InMemoryEscrowRepository) Update(ctx context.Context, escrow *models.Escrow) error {
	if r.UpdateErr != nil {
		return r.UpdateErr
	}
	if existing, ok := r.store[escrow.ID]; ok {
		existing.Status = escrow.Status
		existing.Contract = escrow.Contract
		existing.TxHash = escrow.TxHash
		existing.UpdatedAt = escrow.UpdatedAt
	}
	return nil
}

func TestGetEscrow_Success_Buyer(t *testing.T) {
	gin.SetMode(gin.TestMode)

	repo := newInMemoryEscrowRepo()
	now := time.Now().UTC()
	escrow := &models.Escrow{
		ID:        "esc123",
		BuyerID:   "buyer1",
		SellerID:  "seller1",
		Amount:    100.0,
		Currency:  "USD",
		Status:    "created",
		CreatedAt: now,
		UpdatedAt: now,
	}
	repo.Create(context.Background(), escrow)

	h := &EscrowHandler{repo: repo}
	router := gin.New()
	router.GET("/api/escrow/:id", h.GetEscrow)

	req := httptest.NewRequest("GET", "/api/escrow/esc123", nil)
	req.Header.Set("X-User-ID", "buyer1")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
	var resp models.Escrow
	json.Unmarshal(w.Body.Bytes(), &resp)
	assert.Equal(t, "esc123", resp.ID)
	assert.Equal(t, "buyer1", resp.BuyerID)
}

func TestGetEscrow_Success_Seller(t *testing.T) {
	gin.SetMode(gin.TestMode)

	repo := newInMemoryEscrowRepo()
	now := time.Now().UTC()
	escrow := &models.Escrow{
		ID:        "esc123",
		BuyerID:   "buyer1",
		SellerID:  "seller1",
		Amount:    100.0,
		Currency:  "USD",
		Status:    "created",
		CreatedAt: now,
		UpdatedAt: now,
	}
	repo.Create(context.Background(), escrow)

	h := &EscrowHandler{repo: repo}
	router := gin.New()
	router.GET("/api/escrow/:id", h.GetEscrow)

	req := httptest.NewRequest("GET", "/api/escrow/esc123", nil)
	req.Header.Set("X-User-ID", "seller1")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
}

func TestGetEscrow_Unauthorized(t *testing.T) {
	gin.SetMode(gin.TestMode)

	repo := newInMemoryEscrowRepo()
	now := time.Now().UTC()
	escrow := &models.Escrow{
		ID:        "esc123",
		BuyerID:   "buyer1",
		SellerID:  "seller1",
		Amount:    100.0,
		Currency:  "USD",
		Status:    "created",
		CreatedAt: now,
		UpdatedAt: now,
	}
	repo.Create(context.Background(), escrow)

	h := &EscrowHandler{repo: repo}
	router := gin.New()
	router.GET("/api/escrow/:id", h.GetEscrow)

	req := httptest.NewRequest("GET", "/api/escrow/esc123", nil)
	req.Header.Set("X-User-ID", "other_user")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusForbidden, w.Code)
}

func TestFundEscrow_Success(t *testing.T) {
	gin.SetMode(gin.TestMode)

	repo := newInMemoryEscrowRepo()
	now := time.Now().UTC()
	escrow := &models.Escrow{
		ID:        "esc123",
		BuyerID:   "buyer1",
		SellerID:  "seller1",
		Amount:    100.0,
		Currency:  "USD",
		Status:    "created",
		CreatedAt: now,
		UpdatedAt: now,
	}
	repo.Create(context.Background(), escrow)

	h := &EscrowHandler{repo: repo}
	router := gin.New()
	router.POST("/api/escrow/:id/fund", h.FundEscrow)

	req := httptest.NewRequest("POST", "/api/escrow/esc123/fund", nil)
	req.Header.Set("X-User-ID", "buyer1")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
	var resp models.Escrow
	json.Unmarshal(w.Body.Bytes(), &resp)
	assert.Equal(t, "funded", resp.Status)
	assert.NotEmpty(t, resp.Contract)
}

func TestFundEscrow_NotBuyer(t *testing.T) {
	gin.SetMode(gin.TestMode)

	repo := newInMemoryEscrowRepo()
	now := time.Now().UTC()
	escrow := &models.Escrow{
		ID:        "esc123",
		BuyerID:   "buyer1",
		SellerID:  "seller1",
		Amount:    100.0,
		Currency:  "USD",
		Status:    "created",
		CreatedAt: now,
		UpdatedAt: now,
	}
	repo.Create(context.Background(), escrow)

	h := &EscrowHandler{repo: repo}
	router := gin.New()
	router.POST("/api/escrow/:id/fund", h.FundEscrow)

	req := httptest.NewRequest("POST", "/api/escrow/esc123/fund", nil)
	req.Header.Set("X-User-ID", "seller1")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusForbidden, w.Code)
}

func TestFundEscrow_WrongStatus(t *testing.T) {
	gin.SetMode(gin.TestMode)

	repo := newInMemoryEscrowRepo()
	now := time.Now().UTC()
	escrow := &models.Escrow{
		ID:        "esc123",
		BuyerID:   "buyer1",
		SellerID:  "seller1",
		Amount:    100.0,
		Currency:  "USD",
		Status:    "funded", // already funded
		CreatedAt: now,
		UpdatedAt: now,
	}
	repo.Create(context.Background(), escrow)

	h := &EscrowHandler{repo: repo}
	router := gin.New()
	router.POST("/api/escrow/:id/fund", h.FundEscrow)

	req := httptest.NewRequest("POST", "/api/escrow/esc123/fund", nil)
	req.Header.Set("X-User-ID", "buyer1")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusBadRequest, w.Code)
}

func TestReleaseEscrow_Success(t *testing.T) {
	gin.SetMode(gin.TestMode)

	repo := newInMemoryEscrowRepo()
	now := time.Now().UTC()
	escrow := &models.Escrow{
		ID:        "esc123",
		BuyerID:   "buyer1",
		SellerID:  "seller1",
		Amount:    100.0,
		Currency:  "USD",
		Status:    "funded",
		CreatedAt: now,
		UpdatedAt: now,
	}
	repo.Create(context.Background(), escrow)

	h := &EscrowHandler{repo: repo}
	router := gin.New()
	router.POST("/api/escrow/:id/release", h.ReleaseEscrow)

	req := httptest.NewRequest("POST", "/api/escrow/esc123/release", nil)
	req.Header.Set("X-User-ID", "seller1")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
	var resp models.Escrow
	json.Unmarshal(w.Body.Bytes(), &resp)
	assert.Equal(t, "released", resp.Status)
	assert.NotEmpty(t, resp.TxHash)
}

func TestReleaseEscrow_AsAdmin(t *testing.T) {
	gin.SetMode(gin.TestMode)

	repo := newInMemoryEscrowRepo()
	now := time.Now().UTC()
	escrow := &models.Escrow{
		ID:        "esc123",
		BuyerID:   "buyer1",
		SellerID:  "seller1",
		Amount:    100.0,
		Currency:  "USD",
		Status:    "funded",
		CreatedAt: now,
		UpdatedAt: now,
	}
	repo.Create(context.Background(), escrow)

	h := &EscrowHandler{repo: repo}
	router := gin.New()
	router.POST("/api/escrow/:id/release", func(c *gin.Context) {
		// Inject admin role before handler
		c.Set("user_roles", []string{"ROLE_ADMIN"})
		h.ReleaseEscrow(c)
	})

	req := httptest.NewRequest("POST", "/api/escrow/esc123/release", nil)
	req.Header.Set("X-User-ID", "admin_user")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
	var resp models.Escrow
	json.Unmarshal(w.Body.Bytes(), &resp)
	assert.Equal(t, "released", resp.Status)
}

func TestReleaseEscrow_WrongStatus(t *testing.T) {
	gin.SetMode(gin.TestMode)

	repo := newInMemoryEscrowRepo()
	now := time.Now().UTC()
	escrow := &models.Escrow{
		ID:        "esc123",
		BuyerID:   "buyer1",
		SellerID:  "seller1",
		Amount:    100.0,
		Currency:  "USD",
		Status:    "created", // not funded
		CreatedAt: now,
		UpdatedAt: now,
	}
	repo.Create(context.Background(), escrow)

	h := &EscrowHandler{repo: repo}
	router := gin.New()
	router.POST("/api/escrow/:id/release", h.ReleaseEscrow)

	req := httptest.NewRequest("POST", "/api/escrow/esc123/release", nil)
	req.Header.Set("X-User-ID", "seller1")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusBadRequest, w.Code)
}

func TestRefundEscrow_Success(t *testing.T) {
	gin.SetMode(gin.TestMode)

	repo := newInMemoryEscrowRepo()
	now := time.Now().UTC()
	escrow := &models.Escrow{
		ID:        "esc123",
		BuyerID:   "buyer1",
		SellerID:  "seller1",
		Amount:    100.0,
		Currency:  "USD",
		Status:    "funded",
		CreatedAt: now,
		UpdatedAt: now,
	}
	repo.Create(context.Background(), escrow)

	h := &EscrowHandler{repo: repo}
	router := gin.New()
	router.POST("/api/escrow/:id/refund", h.RefundEscrow)

	req := httptest.NewRequest("POST", "/api/escrow/esc123/refund", nil)
	req.Header.Set("X-User-ID", "buyer1")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
	var resp models.Escrow
	json.Unmarshal(w.Body.Bytes(), &resp)
	assert.Equal(t, "refunded", resp.Status)
}

func TestRefundEscrow_AsAdmin(t *testing.T) {
	gin.SetMode(gin.TestMode)

	repo := newInMemoryEscrowRepo()
	now := time.Now().UTC()
	escrow := &models.Escrow{
		ID:        "esc123",
		BuyerID:   "buyer1",
		SellerID:  "seller1",
		Amount:    100.0,
		Currency:  "USD",
		Status:    "funded",
		CreatedAt: now,
		UpdatedAt: now,
	}
	repo.Create(context.Background(), escrow)

	h := &EscrowHandler{repo: repo}
	router := gin.New()
	router.POST("/api/escrow/:id/refund", func(c *gin.Context) {
		c.Set("user_roles", []string{"ROLE_ADMIN"})
		h.RefundEscrow(c)
	})

	req := httptest.NewRequest("POST", "/api/escrow/esc123/refund", nil)
	req.Header.Set("X-User-ID", "admin_user")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
	var resp models.Escrow
	json.Unmarshal(w.Body.Bytes(), &resp)
	assert.Equal(t, "refunded", resp.Status)
}

func TestRefundEscrow_WrongStatus(t *testing.T) {
	gin.SetMode(gin.TestMode)

	repo := newInMemoryEscrowRepo()
	now := time.Now().UTC()
	escrow := &models.Escrow{
		ID:        "esc123",
		BuyerID:   "buyer1",
		SellerID:  "seller1",
		Amount:    100.0,
		Currency:  "USD",
		Status:    "created", // not funded
		CreatedAt: now,
		UpdatedAt: now,
	}
	repo.Create(context.Background(), escrow)

	h := &EscrowHandler{repo: repo}
	router := gin.New()
	router.POST("/api/escrow/:id/refund", h.RefundEscrow)

	req := httptest.NewRequest("POST", "/api/escrow/esc123/refund", nil)
	req.Header.Set("X-User-ID", "buyer1")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusBadRequest, w.Code)
}
