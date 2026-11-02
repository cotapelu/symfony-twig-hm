package handler

import (
	"context"
	"database/sql"
	"net/http"
	"strconv"
	"time"

	"github.com/gin-gonic/gin"
	"community.local/modules/Escrow/models"
	"community.local/modules/Escrow/repository"
	"community.local/internal/middleware"
)

type Repository interface {
	Create(ctx context.Context, escrow *models.Escrow) error
	GetByID(ctx context.Context, id string) (*models.Escrow, error)
	Update(ctx context.Context, escrow *models.Escrow) error
}

type EscrowHandler struct {
	repo Repository
}

func NewEscrowHandler(db *sql.DB) *EscrowHandler {
	return &EscrowHandler{repo: repository.NewEscrowRepository(db)}
}

func (h *EscrowHandler) RegisterRoutes(router *gin.Engine) {
	api := router.Group("/api/escrow")
	api.Use(middleware.TimeoutMiddleware(middleware.DefaultTimeoutConfig()))
	api.Use(middleware.AuthRequired())
	api.Use(middleware.RateLimitPayment())
	{
		api.POST("/create", h.CreateEscrow)
		api.GET("/:id", h.GetEscrow)
		api.POST("/:id/fund", h.FundEscrow)
		api.POST("/:id/release", h.ReleaseEscrow)
		api.POST("/:id/refund", h.RefundEscrow)
	}
}

func getAuthenticatedUserID(c *gin.Context) (string, bool) {
	if userID, exists := c.Get("user_id"); exists {
		return userID.(string), true
	}
	if userID := c.GetHeader("X-User-ID"); userID != "" {
		return userID, true
	}
	return "", false
}

func isAdmin(c *gin.Context) bool {
	if roles, ok := c.Get("user_roles"); ok {
		for _, r := range roles.([]string) {
			if r == "ROLE_ADMIN" || r == "admin" {
				return true
			}
		}
	}
	return false
}

type CreateEscrowRequest struct {
	BuyerID  string  `json:"buyer_id" binding:"required"`
	SellerID string  `json:"seller_id" binding:"required"`
	Amount   float64 `json:"amount" binding:"required,gt=0"`
	Currency string  `json:"currency" binding:"required"`
}

func (h *EscrowHandler) CreateEscrow(c *gin.Context) {
	ctx := c.Request.Context()
	var req CreateEscrowRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}
	// Simple auth: ensure the caller is the buyer or an admin
	caller, ok := getAuthenticatedUserID(c)
	if !ok || (caller != req.BuyerID && !isAdmin(c)) {
		c.JSON(http.StatusForbidden, gin.H{"error": "not authorized"})
		return
	}
	id := strconv.FormatInt(time.Now().UnixNano(), 36) // simple unique id
	now := time.Now().UTC()
	escrow := &models.Escrow{
		ID:       id,
		BuyerID:  req.BuyerID,
		SellerID: req.SellerID,
		Amount:   req.Amount,
		Currency: req.Currency,
		Status:   "created",
		CreatedAt: now,
		UpdatedAt: now,
	}
	if err := h.repo.Create(ctx, escrow); err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}
	c.JSON(http.StatusOK, escrow)
}

func (h *EscrowHandler) GetEscrow(c *gin.Context) {
	ctx := c.Request.Context()
	id := c.Param("id")
	escrow, err := h.repo.GetByID(ctx, id)
	if err != nil || escrow == nil {
		c.JSON(http.StatusNotFound, gin.H{"error": "escrow not found"})
		return
	}
	// Auth: only buyer/seller/admin can view
	userID, ok := getAuthenticatedUserID(c)
	if !ok || (userID != escrow.BuyerID && userID != escrow.SellerID && !isAdmin(c)) {
		c.JSON(http.StatusForbidden, gin.H{"error": "not authorized"})
		return
	}
	c.JSON(http.StatusOK, escrow)
}

func (h *EscrowHandler) FundEscrow(c *gin.Context) {
	ctx := c.Request.Context()
	id := c.Param("id")
	escrow, err := h.repo.GetByID(ctx, id)
	if err != nil || escrow == nil {
		c.JSON(http.StatusNotFound, gin.H{"error": "escrow not found"})
		return
	}
	userID, ok := getAuthenticatedUserID(c)
	if !ok || userID != escrow.BuyerID {
		c.JSON(http.StatusForbidden, gin.H{"error": "only buyer can fund"})
		return
	}
	if escrow.Status != "created" {
		c.JSON(http.StatusBadRequest, gin.H{"error": "escrow not in created state"})
		return
	}
	// In a real implementation, we would generate a crypto address or accept fiat.
	// Here we simply set status to funded and record a dummy tx.
	escrow.Status = "funded"
	escrow.Contract = "0xContractAddressPlaceholder"
	escrow.UpdatedAt = time.Now().UTC()
	if err := h.repo.Update(ctx, escrow); err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}
	c.JSON(http.StatusOK, escrow)
}

func (h *EscrowHandler) ReleaseEscrow(c *gin.Context) {
	ctx := c.Request.Context()
	id := c.Param("id")
	escrow, err := h.repo.GetByID(ctx, id)
	if err != nil || escrow == nil {
		c.JSON(http.StatusNotFound, gin.H{"error": "escrow not found"})
		return
	}
	userID, ok := getAuthenticatedUserID(c)
	if !ok || userID != escrow.SellerID && !isAdmin(c) {
		c.JSON(http.StatusForbidden, gin.H{"error": "only seller or admin can release"})
		return
	}
	if escrow.Status != "funded" {
		c.JSON(http.StatusBadRequest, gin.H{"error": "escrow not funded"})
		return
	}
	escrow.Status = "released"
	escrow.TxHash = "0xTxHashPlaceholder"
	escrow.UpdatedAt = time.Now().UTC()
	if err := h.repo.Update(ctx, escrow); err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}
	c.JSON(http.StatusOK, escrow)
}

func (h *EscrowHandler) RefundEscrow(c *gin.Context) {
	ctx := c.Request.Context()
	id := c.Param("id")
	escrow, err := h.repo.GetByID(ctx, id)
	if err != nil || escrow == nil {
		c.JSON(http.StatusNotFound, gin.H{"error": "escrow not found"})
		return
	}
	userID, ok := getAuthenticatedUserID(c)
	if !ok || userID != escrow.BuyerID && !isAdmin(c) {
		c.JSON(http.StatusForbidden, gin.H{"error": "only buyer or admin can refund"})
		return
	}
	if escrow.Status != "funded" {
		c.JSON(http.StatusBadRequest, gin.H{"error": "escrow not funded"})
		return
	}
	escrow.Status = "refunded"
	escrow.UpdatedAt = time.Now().UTC()
	if err := h.repo.Update(ctx, escrow); err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}
	c.JSON(http.StatusOK, escrow)
}