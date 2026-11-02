package handler

import (
	"context"
	"net/http"
	"strconv"

	"github.com/gin-gonic/gin"

	"community.local/internal/idempotency"
	"community.local/internal/middleware"
	walletmodels "community.local/modules/WalletService/internal/models"
)

const maxAmount = 1000000000 // max amount in currency units (e.g., VND)
const maxDescriptionLen = 500
const maxReasonLen = 200

func getAuthenticatedUserID(c *gin.Context) (string, bool) {
	if userID, exists := c.Get("user_id"); exists {
		return userID.(string), true
	}
	if userID := c.GetHeader("X-User-ID"); userID != "" {
		return userID, true
	}
	return "", false
}

func requireAuth(c *gin.Context) (string, bool) {
	userID, ok := getAuthenticatedUserID(c)
	if !ok {
		c.JSON(http.StatusUnauthorized, gin.H{"error": "authentication required"})
		return "", false
	}
	return userID, true
}

func requireAdmin(c *gin.Context) bool {
	// Check single role
	if role, ok := c.Get("user_role"); ok && role == "admin" {
		return true
	}
	// Check roles array
	if roles, ok := c.Get("user_roles"); ok {
		for _, r := range roles.([]string) {
			if r == "admin" || r == "ROLE_ADMIN" {
				return true
			}
		}
	}
	return false
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

func validateAmount(amount int64) error {
	if amount <= 0 {
		return http.ErrNoCookie
	}
	if amount > maxAmount {
		return http.ErrNoCookie
	}
	return nil
}

func validateUserID(userID string) error {
	if userID == "" || len(userID) > 100 {
		return http.ErrNoCookie
	}
	return nil
}

// WalletService interface defines the wallet service methods needed by the handler
type WalletService interface {
	CreateWallet(ctx context.Context, userID, currency string) (*walletmodels.Wallet, error)
	GetWallet(ctx context.Context, userID string) (*walletmodels.Wallet, error)
	GetBalance(ctx context.Context, userID string) (int64, error)
	GetTransactions(ctx context.Context, userID string, limit, offset int) ([]*walletmodels.WalletTransaction, error)
	TopUp(ctx context.Context, req *walletmodels.TopUpRequest) (*walletmodels.WalletTransaction, error)
	Withdraw(ctx context.Context, req *walletmodels.WithdrawalRequest) (*walletmodels.WalletTransaction, error)
	Transfer(ctx context.Context, req *walletmodels.TransferRequest) (*walletmodels.WalletTransaction, error)
	DeleteWallet(ctx context.Context, userID string) error
	LockWallet(ctx context.Context, userID, reason string) error
	UnlockWallet(ctx context.Context, userID string) error
	ExportTransactions(ctx context.Context, userID string, limit, offset int) ([]*walletmodels.WalletTransaction, error)
}

// WalletHandler handles wallet HTTP requests
type WalletHandler struct {
	svc WalletService
}

// NewWalletHandler creates a new wallet handler
func NewWalletHandler(svc WalletService) *WalletHandler {
	return &WalletHandler{svc: svc}
}

// RegisterRoutes registers all wallet routes
func (h *WalletHandler) RegisterRoutes(router *gin.Engine) {
	wallet := router.Group("/api/wallet")
	wallet.Use(middleware.TimeoutMiddleware(middleware.DefaultTimeoutConfig()))
	wallet.Use(middleware.AuthRequired())
	wallet.Use(middleware.RateLimitPayment())
	{
		wallet.POST("/create", h.CreateWallet)
		wallet.GET("/me", h.GetMyWallet)
		wallet.GET("/me/balance", h.GetMyBalance)
		wallet.GET("/me/transactions", h.GetMyTransactions)
		wallet.GET("/me/export", h.ExportMyTransactions)
		wallet.GET("/:user_id", h.GetWallet)
		wallet.GET("/:user_id/balance", h.GetBalance)
		wallet.GET("/:user_id/transactions", h.GetTransactions)
		wallet.GET("/:user_id/export", h.ExportTransactions)
		wallet.POST("/topup", h.TopUp)
		wallet.POST("/transfer", h.Transfer)
		wallet.POST("/withdraw", h.Withdraw)
		wallet.DELETE("/me", h.DeleteMyWallet)
		wallet.DELETE("/:user_id", h.DeleteWallet)
		wallet.PATCH("/:user_id/lock", h.LockWallet)
		wallet.PATCH("/:user_id/unlock", h.UnlockWallet)
	}
}

func (h *WalletHandler) CreateWallet(c *gin.Context) {
	ctx := c.Request.Context()
	var req struct {
		UserID   string `json:"user_id" binding:"required"`
		Currency string `json:"currency"`
	}

	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	if err := validateUserID(req.UserID); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "invalid user_id"})
		return
	}

	authUserID, ok := requireAuth(c)
	if !ok {
		return
	}
	if authUserID != req.UserID && !requireAdmin(c) {
		c.JSON(http.StatusForbidden, gin.H{"error": "can only create your own wallet"})
		return
	}

	if req.Currency == "" {
		req.Currency = "VND"
	}

	wallet, err := h.svc.CreateWallet(ctx, req.UserID, req.Currency)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusCreated, gin.H{"wallet": wallet})
}

func (h *WalletHandler) GetMyWallet(c *gin.Context) {
	ctx := c.Request.Context()
	authUserID, ok := requireAuth(c)
	if !ok {
		return
	}

	wallet, err := h.svc.GetWallet(ctx, authUserID)
	if err != nil {
		c.JSON(http.StatusNotFound, gin.H{"error": "wallet not found"})
		return
	}

	c.JSON(http.StatusOK, gin.H{"wallet": wallet})
}

func (h *WalletHandler) GetMyBalance(c *gin.Context) {
	ctx := c.Request.Context()
	authUserID, ok := requireAuth(c)
	if !ok {
		return
	}

	balance, err := h.svc.GetBalance(ctx, authUserID)
	if err != nil {
		c.JSON(http.StatusNotFound, gin.H{"error": "wallet not found"})
		return
	}

	c.JSON(http.StatusOK, gin.H{"balance": balance})
}

func (h *WalletHandler) GetMyTransactions(c *gin.Context) {
	ctx := c.Request.Context()
	authUserID, ok := requireAuth(c)
	if !ok {
		return
	}
	limit, _ := strconv.Atoi(c.DefaultQuery("limit", "20"))
	offset, _ := strconv.Atoi(c.DefaultQuery("offset", "0"))

	if limit < 1 || limit > 100 {
		limit = 20
	}

	transactions, err := h.svc.GetTransactions(ctx, authUserID, limit, offset)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"transactions": transactions,
		"limit":        limit,
		"offset":       offset,
	})
}

func (h *WalletHandler) ExportMyTransactions(c *gin.Context) {
	ctx := c.Request.Context()
	authUserID, ok := requireAuth(c)
	if !ok {
		return
	}
	limit, _ := strconv.Atoi(c.DefaultQuery("limit", "20"))
	offset, _ := strconv.Atoi(c.DefaultQuery("offset", "0"))

	transactions, err := h.svc.ExportTransactions(ctx, authUserID, limit, offset)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"user_id":      authUserID,
		"transactions": transactions,
		"count":        len(transactions),
	})
}

func (h *WalletHandler) GetWallet(c *gin.Context) {
	ctx := c.Request.Context()
	authUserID, ok := requireAuth(c)
	if !ok {
		return
	}
	requestedUserID := c.Param("user_id")

	if authUserID != requestedUserID && !requireAdmin(c) {
		c.JSON(http.StatusForbidden, gin.H{"error": "can only access your own wallet"})
		return
	}

	wallet, err := h.svc.GetWallet(ctx, requestedUserID)
	if err != nil {
		c.JSON(http.StatusNotFound, gin.H{"error": "wallet not found"})
		return
	}

	c.JSON(http.StatusOK, gin.H{"wallet": wallet})
}

func (h *WalletHandler) GetBalance(c *gin.Context) {
	ctx := c.Request.Context()
	authUserID, ok := requireAuth(c)
	if !ok {
		return
	}
	requestedUserID := c.Param("user_id")

	if authUserID != requestedUserID && !requireAdmin(c) {
		c.JSON(http.StatusForbidden, gin.H{"error": "can only access your own balance"})
		return
	}

	balance, err := h.svc.GetBalance(ctx, requestedUserID)
	if err != nil {
		c.JSON(http.StatusNotFound, gin.H{"error": "wallet not found"})
		return
	}

	c.JSON(http.StatusOK, gin.H{"balance": balance})
}

func (h *WalletHandler) GetTransactions(c *gin.Context) {
	ctx := c.Request.Context()
	authUserID, ok := requireAuth(c)
	if !ok {
		return
	}
	requestedUserID := c.Param("user_id")
	limit, _ := strconv.Atoi(c.DefaultQuery("limit", "20"))
	offset, _ := strconv.Atoi(c.DefaultQuery("offset", "0"))

	if authUserID != requestedUserID && !requireAdmin(c) {
		c.JSON(http.StatusForbidden, gin.H{"error": "can only access your own transactions"})
		return
	}
	if limit < 1 || limit > 100 {
		limit = 20
	}

	transactions, err := h.svc.GetTransactions(ctx, requestedUserID, limit, offset)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"transactions": transactions,
		"limit":        limit,
		"offset":       offset,
	})
}

func (h *WalletHandler) TopUp(c *gin.Context) {
	ctx := c.Request.Context()
	var req walletmodels.TopUpRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	authUserID, ok := requireAuth(c)
	if !ok {
		return
	}
	if authUserID != req.UserID && !requireAdmin(c) {
		c.JSON(http.StatusForbidden, gin.H{"error": "can only top up your own wallet"})
		return
	}

	if req.Amount <= 0 || req.Amount > maxAmount {
		c.JSON(http.StatusBadRequest, gin.H{"error": "amount must be between 0 and 1 billion"})
		return
	}
	if req.Method == "" {
		req.Method = "bank_transfer"
	}

	// Check idempotency
	idemKey := c.GetHeader("Idempotency-Key")
	if idemKey == "" {
		c.JSON(http.StatusBadRequest, gin.H{"error": "Idempotency-Key header required"})
		return
	}
	if cached, ok := idempotency.Get(idemKey); ok {
		c.JSON(http.StatusOK, cached)
		return
	}

	txn, err := h.svc.TopUp(ctx, &req)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	resp := gin.H{
		"transaction": txn,
		"message":     "Top up successful",
	}
	idempotency.Set(idemKey, resp)

	c.JSON(http.StatusOK, resp)
}

func (h *WalletHandler) Transfer(c *gin.Context) {
	ctx := c.Request.Context()
	var req walletmodels.TransferRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	authUserID, ok := requireAuth(c)
	if !ok {
		return
	}
	if authUserID != req.FromUserID && !requireAdmin(c) {
		c.JSON(http.StatusForbidden, gin.H{"error": "can only transfer from your own wallet"})
		return
	}

	if req.Amount <= 0 || req.Amount > maxAmount {
		c.JSON(http.StatusBadRequest, gin.H{"error": "amount must be between 0 and 1 billion"})
		return
	}
	if req.FromUserID == req.ToUserID {
		c.JSON(http.StatusBadRequest, gin.H{"error": "cannot transfer to yourself"})
		return
	}
	if len(req.Description) > maxDescriptionLen {
		c.JSON(http.StatusBadRequest, gin.H{"error": "description too long"})
		return
	}

	// Check idempotency
	idemKey := c.GetHeader("Idempotency-Key")
	if idemKey == "" {
		c.JSON(http.StatusBadRequest, gin.H{"error": "Idempotency-Key header required"})
		return
	}
	if cached, ok := idempotency.Get(idemKey); ok {
		c.JSON(http.StatusOK, cached)
		return
	}

	txn, err := h.svc.Transfer(ctx, &req)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	resp := gin.H{
		"transaction": txn,
		"message":     "Transfer successful",
	}
	idempotency.Set(idemKey, resp)

	c.JSON(http.StatusOK, resp)
}

func (h *WalletHandler) Withdraw(c *gin.Context) {
	ctx := c.Request.Context()
	var req walletmodels.WithdrawalRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	authUserID, ok := requireAuth(c)
	if !ok {
		return
	}
	if authUserID != req.UserID && !requireAdmin(c) {
		c.JSON(http.StatusForbidden, gin.H{"error": "can only withdraw from your own wallet"})
		return
	}

	if req.Amount <= 0 || req.Amount > maxAmount {
		c.JSON(http.StatusBadRequest, gin.H{"error": "amount must be between 0 and 1 billion"})
		return
	}
	if req.AccountNo == "" || req.HolderName == "" {
		c.JSON(http.StatusBadRequest, gin.H{"error": "account number and holder name required"})
		return
	}

	// Check idempotency
	idemKey := c.GetHeader("Idempotency-Key")
	if idemKey == "" {
		c.JSON(http.StatusBadRequest, gin.H{"error": "Idempotency-Key header required"})
		return
	}
	if cached, ok := idempotency.Get(idemKey); ok {
		c.JSON(http.StatusOK, cached)
		return
	}

	txn, err := h.svc.Withdraw(ctx, &req)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	resp := gin.H{
		"transaction": txn,
		"message":     "Withdrawal request submitted",
	}
	idempotency.Set(idemKey, resp)

	c.JSON(http.StatusOK, resp)
}

func (h *WalletHandler) DeleteMyWallet(c *gin.Context) {
	ctx := c.Request.Context()
	authUserID, ok := requireAuth(c)
	if !ok {
		return
	}

	if err := h.svc.DeleteWallet(ctx, authUserID); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusOK, gin.H{"message": "Wallet deleted successfully"})
}

func (h *WalletHandler) DeleteWallet(c *gin.Context) {
	ctx := c.Request.Context()
	authUserID, ok := requireAuth(c)
	if !ok {
		return
	}
	requestedUserID := c.Param("user_id")

	if authUserID != requestedUserID && !requireAdmin(c) {
		c.JSON(http.StatusForbidden, gin.H{"error": "can only delete your own wallet"})
		return
	}

	if err := h.svc.DeleteWallet(ctx, requestedUserID); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusOK, gin.H{"message": "Wallet deleted successfully"})
}

func (h *WalletHandler) LockWallet(c *gin.Context) {
	ctx := c.Request.Context()
	userID := c.Param("user_id")
	var req struct {
		Reason string `json:"reason" binding:"required"`
	}

	if !requireAdmin(c) {
		c.JSON(http.StatusForbidden, gin.H{"error": "admin access required"})
		return
	}
	if len(req.Reason) > maxReasonLen {
		c.JSON(http.StatusBadRequest, gin.H{"error": "reason too long"})
		return
	}

	if err := h.svc.LockWallet(ctx, userID, req.Reason); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusOK, gin.H{"message": "Wallet locked successfully"})
}

func (h *WalletHandler) UnlockWallet(c *gin.Context) {
	ctx := c.Request.Context()
	userID := c.Param("user_id")

	if !requireAdmin(c) {
		c.JSON(http.StatusForbidden, gin.H{"error": "admin access required"})
		return
	}

	if err := h.svc.UnlockWallet(ctx, userID); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusOK, gin.H{"message": "Wallet unlocked successfully"})
}

func (h *WalletHandler) ExportTransactions(c *gin.Context) {
	ctx := c.Request.Context()
	authUserID, ok := requireAuth(c)
	if !ok {
		return
	}
	requestedUserID := c.Param("user_id")
	limit, _ := strconv.Atoi(c.DefaultQuery("limit", "20"))
	offset, _ := strconv.Atoi(c.DefaultQuery("offset", "0"))

	if authUserID != requestedUserID && !requireAdmin(c) {
		c.JSON(http.StatusForbidden, gin.H{"error": "can only export your own transactions"})
		return
	}

	transactions, err := h.svc.ExportTransactions(ctx, requestedUserID, limit, offset)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"user_id":      requestedUserID,
		"transactions": transactions,
		"count":        len(transactions),
	})
}