package handler

import (
	"bytes"
	"context"
	"encoding/json"
	"errors"
	"net/http"
	"net/http/httptest"
	"testing"
	"time"

	"github.com/gin-gonic/gin"
	"github.com/stretchr/testify/assert"
	"community.local/modules/Escrow/models"
)

func TestCreateEscrow_RepositoryError(t *testing.T) {
	gin.SetMode(gin.TestMode)

	repo := newInMemoryEscrowRepo()
	repo.CreateErr = errors.New("db error")
	h := &EscrowHandler{repo: repo}
	router := gin.New()
	router.POST("/api/escrow/create", h.CreateEscrow)

	reqBody := CreateEscrowRequest{
		BuyerID:  "buyer1",
		SellerID: "seller1",
		Amount:   100.0,
		Currency: "USD",
	}
	body, _ := json.Marshal(reqBody)

	req := httptest.NewRequest(http.MethodPost, "/api/escrow/create", bytes.NewReader(body))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("X-User-ID", "buyer1")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusInternalServerError, w.Code)
	var resp map[string]interface{}
	json.Unmarshal(w.Body.Bytes(), &resp)
	assert.Contains(t, resp["error"].(string), "db error")
}

func TestGetEscrow_RepositoryError(t *testing.T) {
	gin.SetMode(gin.TestMode)

	repo := newInMemoryEscrowRepo()
	repo.GetErr = errors.New("db error")
	// No need to create escrow; GetErr will cause error
	h := &EscrowHandler{repo: repo}
	router := gin.New()
	router.GET("/api/escrow/:id", h.GetEscrow)

	req := httptest.NewRequest(http.MethodGet, "/api/escrow/esc123", nil)
	req.Header.Set("X-User-ID", "buyer1")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	// The handler returns 404 when repository returns error (treated as not found)
	assert.Equal(t, http.StatusNotFound, w.Code)
}

func TestFundEscrow_UpdateError(t *testing.T) {
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
	repo.UpdateErr = errors.New("update failed")

	h := &EscrowHandler{repo: repo}
	router := gin.New()
	router.POST("/api/escrow/:id/fund", h.FundEscrow)

	req := httptest.NewRequest(http.MethodPost, "/api/escrow/esc123/fund", nil)
	req.Header.Set("X-User-ID", "buyer1")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusInternalServerError, w.Code)
	var resp map[string]interface{}
	json.Unmarshal(w.Body.Bytes(), &resp)
	assert.Contains(t, resp["error"].(string), "update failed")
}

func TestReleaseEscrow_UpdateError(t *testing.T) {
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
	repo.UpdateErr = errors.New("update failed")

	h := &EscrowHandler{repo: repo}
	router := gin.New()
	router.POST("/api/escrow/:id/release", h.ReleaseEscrow)

	req := httptest.NewRequest(http.MethodPost, "/api/escrow/esc123/release", nil)
	req.Header.Set("X-User-ID", "seller1")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusInternalServerError, w.Code)
	var resp map[string]interface{}
	json.Unmarshal(w.Body.Bytes(), &resp)
	assert.Contains(t, resp["error"].(string), "update failed")
}

func TestRefundEscrow_UpdateError(t *testing.T) {
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
	repo.UpdateErr = errors.New("update failed")

	h := &EscrowHandler{repo: repo}
	router := gin.New()
	router.POST("/api/escrow/:id/refund", h.RefundEscrow)

	req := httptest.NewRequest(http.MethodPost, "/api/escrow/esc123/refund", nil)
	req.Header.Set("X-User-ID", "buyer1")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusInternalServerError, w.Code)
	var resp map[string]interface{}
	json.Unmarshal(w.Body.Bytes(), &resp)
	assert.Contains(t, resp["error"].(string), "update failed")
}
