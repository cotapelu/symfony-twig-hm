package handler

import (
	"context"
	"fmt"
	"net/http"
	"os"
	"strconv"

	bm "community.local/modules/Banking/internal/models"
	"community.local/internal/idempotency"
	"community.local/internal/middleware"

	"github.com/gin-gonic/gin"
)

// getCurrentUserID extracts user_id from context set by auth middleware
func getCurrentUserID(c *gin.Context) (string, bool) {
	userID, exists := c.Get("user_id")
	if !exists {
		return "", false
	}
	return userID.(string), true
}

// requireAuth checks if user is authenticated
func requireAuth(c *gin.Context) (string, bool) {
	userID, ok := getCurrentUserID(c)
	if !ok {
		c.JSON(http.StatusUnauthorized, gin.H{"error": "authentication required"})
		return "", false
	}
	return userID, true
}

// checkAccountOwnership verifies the authenticated user owns the account
func (h *BankingHandler) checkAccountOwnership(ctx context.Context, accountID int64, userID string) (bool, error) {
	account, err := h.svc.GetAccount(ctx, accountID)
	if err != nil {
		return false, err
	}
	return account.UserID == userID, nil
}

// BankingService interface defines all service methods
type BankingService interface {
	CreateAccount(ctx context.Context, userID, accountType, currency string) (*bm.Account, error)
	GetAccount(ctx context.Context, id int64) (*bm.Account, error)
	GetAccountByNumber(ctx context.Context, accNum string) (*bm.Account, error)
	GetUserAccounts(ctx context.Context, userID string) ([]*bm.Account, error)
	GetBalance(ctx context.Context, accountID int64) (float64, error)
	GetTransactions(ctx context.Context, accountID int64, limit, offset int) ([]*bm.Transaction, error)
	Deposit(ctx context.Context, accountID int64, amount float64, description string) (*bm.Transaction, error)
	Withdraw(ctx context.Context, accountID int64, amount float64, description string) (*bm.Transaction, error)
	Transfer(ctx context.Context, req *bm.TransferRequest) (*bm.Transaction, error)
}

// BankingHandler handles banking HTTP requests
type BankingHandler struct {
	svc BankingService
}

// NewBankingHandler creates a new banking handler
func NewBankingHandler(svc BankingService) *BankingHandler {
	fmt.Println("DEBUG: NewBankingHandler called")
	return &BankingHandler{svc: svc}
}

// RegisterRoutes registers all banking routes
func (h *BankingHandler) RegisterRoutes(router *gin.Engine) {
	banking := router.Group("/api/banking")
	banking.Use(middleware.TimeoutMiddleware(middleware.DefaultTimeoutConfig()))
	banking.Use(middleware.AuthRequired())
	banking.Use(middleware.RateLimitPayment())
	{
		banking.POST("/accounts", h.CreateAccount)
		banking.GET("/accounts/:id", h.GetAccount)
		banking.GET("/accounts/number/:number", h.GetAccountByNumber)
		banking.GET("/accounts/user/:user_id", h.GetUserAccounts)
		banking.GET("/accounts/:id/balance", h.GetBalance)
		banking.GET("/accounts/:id/transactions", h.GetTransactions)
		banking.POST("/deposit", h.Deposit)
		banking.POST("/withdraw", h.Withdraw)
		banking.POST("/transfer", h.Transfer)
	}
}

// CreateAccount creates a new banking account
func (h *BankingHandler) CreateAccount(c *gin.Context) {
	fmt.Fprintln(os.Stderr, "DEBUG: CreateAccount handler called")
	ctx := c.Request.Context()
	var req struct {
		UserID   string `json:"user_id" binding:"required"`
		AccType  string `json:"account_type" binding:"required"`
		Currency string `json:"currency"`
	}

	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	if req.Currency == "" {
		req.Currency = "VND"
	}

	fmt.Fprintf(os.Stderr, "DEBUG: CreateAccount request: user=%s, type=%s, currency=%s\n", req.UserID, req.AccType, req.Currency)
	acc, err := h.svc.CreateAccount(ctx, req.UserID, req.AccType, req.Currency)
	if err != nil {
		fmt.Fprintf(os.Stderr, "ERROR: CreateAccount service error: %v\n", err)
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	fmt.Fprintf(os.Stderr, "DEBUG: CreateAccount success, account ID: %d\n", acc.ID)
	c.JSON(http.StatusCreated, gin.H{"account": acc})
}

// GetAccount retrieves an account by ID
func (h *BankingHandler) GetAccount(c *gin.Context) {
	ctx := c.Request.Context()
	id, err := strconv.ParseInt(c.Param("id"), 10, 64)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "invalid account ID"})
		return
	}

	// Require authentication
	userID, ok := requireAuth(c)
	if !ok {
		return
	}

	// Check ownership
	owns, err := h.checkAccountOwnership(ctx, id, userID)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "failed to check ownership"})
		return
	}
	if !owns {
		c.JSON(http.StatusForbidden, gin.H{"error": "access denied: you can only access your own accounts"})
		return
	}

	acc, err := h.svc.GetAccount(ctx, id)
	if err != nil {
		c.JSON(http.StatusNotFound, gin.H{"error": "account not found"})
		return
	}

	c.JSON(http.StatusOK, gin.H{"account": acc})
}

// GetAccountByNumber retrieves an account by account number
func (h *BankingHandler) GetAccountByNumber(c *gin.Context) {
	ctx := c.Request.Context()
	accNum := c.Param("number")

	// Require authentication
	userID, ok := requireAuth(c)
	if !ok {
		return
	}

	acc, err := h.svc.GetAccountByNumber(ctx, accNum)
	if err != nil {
		c.JSON(http.StatusNotFound, gin.H{"error": "account not found"})
		return
	}

	// Check ownership - users can only view their own accounts
	if acc.UserID != userID {
		c.JSON(http.StatusForbidden, gin.H{"error": "access denied: you can only access your own accounts"})
		return
	}

	c.JSON(http.StatusOK, gin.H{"account": acc})
}

// GetUserAccounts retrieves all accounts for a user
func (h *BankingHandler) GetUserAccounts(c *gin.Context) {
	ctx := c.Request.Context()
	requestedUserID := c.Param("user_id")

	// Require authentication
	authUserID, ok := requireAuth(c)
	if !ok {
		return
	}

	// Users can only view their own accounts unless admin
	if authUserID != requestedUserID {
		// Check if admin (could be expanded with role check)
		roles, _ := c.Get("user_roles")
		if roles != nil {
			roleList := roles.([]string)
			for _, r := range roleList {
				if r == "ROLE_ADMIN" {
					// Admin can view any user's accounts
					goto proceed
				}
			}
		}
		c.JSON(http.StatusForbidden, gin.H{"error": "access denied: you can only view your own accounts"})
		return
	}

proceed:
	accounts, err := h.svc.GetUserAccounts(ctx, requestedUserID)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusOK, gin.H{"accounts": accounts})
}

// GetBalance retrieves an account's balance
func (h *BankingHandler) GetBalance(c *gin.Context) {
	ctx := c.Request.Context()
	id, err := strconv.ParseInt(c.Param("id"), 10, 64)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "invalid account ID"})
		return
	}

	// Require authentication
	_, ok := requireAuth(c)
	if !ok {
		return
	}

	// Check ownership
	authUserID, _ := getCurrentUserID(c)
	owns, err := h.checkAccountOwnership(ctx, id, authUserID)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "failed to check ownership"})
		return
	}
	if !owns {
		c.JSON(http.StatusForbidden, gin.H{"error": "access denied: you can only access your own accounts"})
		return
	}

	balance, err := h.svc.GetBalance(ctx, id)
	if err != nil {
		c.JSON(http.StatusNotFound, gin.H{"error": "account not found"})
		return
	}

	c.JSON(http.StatusOK, gin.H{"balance": balance})
}

// GetTransactions retrieves an account's transaction history
func (h *BankingHandler) GetTransactions(c *gin.Context) {
	ctx := c.Request.Context()
	id, err := strconv.ParseInt(c.Param("id"), 10, 64)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "invalid account ID"})
		return
	}

	// Require authentication
	_, ok := requireAuth(c)
	if !ok {
		return
	}

	// Check ownership
	authUserID, _ := getCurrentUserID(c)
	owns, err := h.checkAccountOwnership(ctx, id, authUserID)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "failed to check ownership"})
		return
	}
	if !owns {
		c.JSON(http.StatusForbidden, gin.H{"error": "access denied: you can only access your own accounts"})
		return
	}

	limit, _ := strconv.Atoi(c.DefaultQuery("limit", "20"))
	offset, _ := strconv.Atoi(c.DefaultQuery("offset", "0"))

	transactions, err := h.svc.GetTransactions(ctx, id, limit, offset)
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

// Deposit adds funds to an account
func (h *BankingHandler) Deposit(c *gin.Context) {
	ctx := c.Request.Context()
	var req struct {
		AccountID   int64   `json:"account_id" binding:"required"`
		Amount      float64 `json:"amount" binding:"required"`
		Description string  `json:"description"`
	}

	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	// Validate amount
	if req.Amount <= 0 {
		c.JSON(http.StatusBadRequest, gin.H{"error": "amount must be positive"})
		return
	}
	if req.Amount > 1000000000 {
		c.JSON(http.StatusBadRequest, gin.H{"error": "amount exceeds maximum limit"})
		return
	}

	// Require authentication and check ownership
	userID, ok := requireAuth(c)
	if !ok {
		return
	}
	if owns, err := h.checkAccountOwnership(ctx, req.AccountID, userID); err != nil || !owns {
		if err != nil {
			c.JSON(http.StatusInternalServerError, gin.H{"error": "failed to check ownership"})
			return
		}
		c.JSON(http.StatusForbidden, gin.H{"error": "access denied: you can only deposit into your own accounts"})
		return
	}

	if req.Description == "" {
		req.Description = "Deposit"
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

	txn, err := h.svc.Deposit(ctx, req.AccountID, req.Amount, req.Description)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	resp := gin.H{
		"transaction": txn,
		"message":     "Deposit successful",
	}
	idempotency.Set(idemKey, resp)

	c.JSON(http.StatusOK, resp)
}

// Withdraw withdraws funds from an account
func (h *BankingHandler) Withdraw(c *gin.Context) {
	ctx := c.Request.Context()
	var req struct {
		AccountID   int64   `json:"account_id" binding:"required"`
		Amount      float64 `json:"amount" binding:"required"`
		Description string  `json:"description"`
	}

	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	// Validate amount
	if req.Amount <= 0 {
		c.JSON(http.StatusBadRequest, gin.H{"error": "amount must be positive"})
		return
	}
	if req.Amount > 1000000000 {
		c.JSON(http.StatusBadRequest, gin.H{"error": "amount exceeds maximum limit"})
		return
	}

	// Require authentication and check ownership
	userID, ok := requireAuth(c)
	if !ok {
		return
	}
	if owns, err := h.checkAccountOwnership(ctx, req.AccountID, userID); err != nil || !owns {
		if err != nil {
			c.JSON(http.StatusInternalServerError, gin.H{"error": "failed to check ownership"})
			return
		}
		c.JSON(http.StatusForbidden, gin.H{"error": "access denied: you can only withdraw from your own accounts"})
		return
	}

	if req.Description == "" {
		req.Description = "Withdrawal"
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

	txn, err := h.svc.Withdraw(ctx, req.AccountID, req.Amount, req.Description)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	resp := gin.H{
		"transaction": txn,
		"message":     "Withdrawal successful",
	}
	idempotency.Set(idemKey, resp)

	c.JSON(http.StatusOK, resp)
}

// Transfer transfers funds between accounts
func (h *BankingHandler) Transfer(c *gin.Context) {
	ctx := c.Request.Context()
	var req bm.TransferRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	// Validate transfer
	if req.Amount <= 0 {
		c.JSON(http.StatusBadRequest, gin.H{"error": "amount must be positive"})
		return
	}
	if req.Amount > 1000000000 {
		c.JSON(http.StatusBadRequest, gin.H{"error": "amount exceeds maximum limit"})
		return
	}
	if req.FromAccount == req.ToAccount {
		c.JSON(http.StatusBadRequest, gin.H{"error": "cannot transfer to the same account"})
		return
	}

	// Require authentication
	userID, ok := requireAuth(c)
	if !ok {
		return
	}

	// Get source account to verify ownership
	fromAcc, err := h.svc.GetAccountByNumber(ctx, req.FromAccount)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "source account not found"})
		return
	}
	if fromAcc.UserID != userID {
		c.JSON(http.StatusForbidden, gin.H{"error": "access denied: you can only transfer from your own accounts"})
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