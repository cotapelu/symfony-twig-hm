package handler

import (
	"database/sql"
	"net/http"
	"strconv"

	creditlending "community.local/modules/CreditLending/internal/models"
	"community.local/internal/idempotency"

	"github.com/gin-gonic/gin"
)

func getCurrentUserID(c *gin.Context) (string, bool) {
	userID, exists := c.Get("user_id")
	if !exists {
		return "", false
	}
	return userID.(string), true
}

func requireAuth(c *gin.Context) (string, bool) {
	userID, ok := getCurrentUserID(c)
	if !ok {
		c.JSON(401, gin.H{"error": "authentication required"})
		return "", false
	}
	return userID, true
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

// CreditLendingService interface defines the service methods needed by the handler
type CreditLendingService interface {
	CreateApplication(a *creditlending.CreditApplication) error
	GetApplication(id int64) (*creditlending.CreditApplication, error)
	GetUserApplications(userID string) ([]*creditlending.CreditApplication, error)
	ApproveApplication(id int64, approvedAmount float64) (*creditlending.CreditApplication, error)
	Disburse(id int64) (*creditlending.CreditApplication, error)
	Repay(id int64, amount float64) (*creditlending.CreditRepayment, error)
	GetRepayments(appID int64) ([]*creditlending.CreditRepayment, error)
	GetCreditScore(userID string) (*creditlending.CreditScore, error)
}

// CreditLendingHandler handles credit lending HTTP requests
type CreditLendingHandler struct {
	svc CreditLendingService
}

// NewCreditLendingHandler creates a new credit lending handler
func NewCreditLendingHandler(db *sql.DB) *CreditLendingHandler {
	return &CreditLendingHandler{
		svc: creditlending.NewCreditLendingService(db, ""),
	}
}

// RegisterRoutes registers all credit lending routes
func (h *CreditLendingHandler) RegisterRoutes(router *gin.Engine) {
	credit := router.Group("/api/credit")
	{
		credit.POST("/applications", h.CreateApplication)
		credit.GET("/applications/:id", h.GetApplication)
		credit.GET("/applications/user/:user_id", h.GetUserApplications)
		credit.POST("/applications/:id/approve", h.ApproveApplication)
		credit.POST("/applications/:id/disburse", h.Disburse)
		credit.POST("/applications/:id/repay", h.Repay)
		credit.GET("/applications/:id/repayments", h.GetRepayments)
		credit.GET("/score/:user_id", h.GetCreditScore)
	}
}

// CreateApplication creates a new credit application
func (h *CreditLendingHandler) CreateApplication(c *gin.Context) {
	// Require authentication
	authUserID, ok := requireAuth(c)
	if !ok {
		return
	}

	var req struct {
		UserID     string  `json:"user_id" binding:"required"`
		CreditType string  `json:"credit_type" binding:"required"`
		Amount     float64 `json:"amount" binding:"required"`
		Term       int     `json:"term" binding:"required"`
	}

	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	// User can only create application for themselves unless admin
	if req.UserID != authUserID && !isAdmin(c) {
		c.JSON(403, gin.H{"error": "access denied: can only create application for yourself"})
		return
	}

	application := &creditlending.CreditApplication{
		UserID:       authUserID, // use authenticated user
		CreditType:   req.CreditType,
		Amount:       req.Amount,
		Term:         req.Term,
		InterestRate: 0.024,
		Status:       "pending",
	}

	if err := h.svc.CreateApplication(application); err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusCreated, gin.H{"application": application})
}

// GetApplication retrieves a credit application by ID
func (h *CreditLendingHandler) GetApplication(c *gin.Context) {
	// Require authentication
	if _, ok := requireAuth(c); !ok {
		return
	}

	id, err := strconv.ParseInt(c.Param("id"), 10, 64)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "invalid application ID"})
		return
	}

	application, err := h.svc.GetApplication(id)
	if err != nil {
		c.JSON(http.StatusNotFound, gin.H{"error": "application not found"})
		return
	}

	// Check ownership: only the applicant or admin can view
	authUserID, _ := getCurrentUserID(c)
	if application.UserID != authUserID && !isAdmin(c) {
		c.JSON(403, gin.H{"error": "access denied: you can only view your own applications"})
		return
	}

	c.JSON(http.StatusOK, gin.H{"application": application})
}

// GetUserApplications retrieves all applications for a user
func (h *CreditLendingHandler) GetUserApplications(c *gin.Context) {
	// Require authentication
	if _, ok := requireAuth(c); !ok {
		return
	}

	userID := c.Param("user_id")
	// Check ownership: user can view their own applications, admin can view any
	authUserID, _ := getCurrentUserID(c)
	if userID != authUserID && !isAdmin(c) {
		c.JSON(403, gin.H{"error": "access denied: you can only view your own applications"})
		return
	}

	applications, err := h.svc.GetUserApplications(userID)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusOK, gin.H{"applications": applications, "count": len(applications)})
}

// ApproveApplication approves a credit application
func (h *CreditLendingHandler) ApproveApplication(c *gin.Context) {
	// Admin only
	if _, ok := requireAuth(c); !ok {
		return
	}
	if !isAdmin(c) {
		c.JSON(403, gin.H{"error": "access denied: only admin can approve applications"})
		return
	}

	id, err := strconv.ParseInt(c.Param("id"), 10, 64)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "invalid application ID"})
		return
	}

	var req struct {
		ApprovedAmount float64 `json:"approved_amount" binding:"required"`
	}

	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	// Validate amount
	if req.ApprovedAmount <= 0 {
		c.JSON(http.StatusBadRequest, gin.H{"error": "approved amount must be positive"})
		return
	}
	if req.ApprovedAmount > 1000000000 {
		c.JSON(http.StatusBadRequest, gin.H{"error": "amount exceeds maximum limit"})
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

	application, err := h.svc.ApproveApplication(id, req.ApprovedAmount)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	resp := gin.H{"application": application, "message": "application approved"}
	idempotency.Set(idemKey, resp)

	c.JSON(http.StatusOK, resp)
}

// Disburse disburses an approved credit
func (h *CreditLendingHandler) Disburse(c *gin.Context) {
	// Admin only
	if _, ok := requireAuth(c); !ok {
		return
	}
	if !isAdmin(c) {
		c.JSON(403, gin.H{"error": "access denied: only admin can disburse credit"})
		return
	}

	id, err := strconv.ParseInt(c.Param("id"), 10, 64)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "invalid application ID"})
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

	application, err := h.svc.Disburse(id)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	resp := gin.H{"application": application, "message": "credit disbursed"}
	idempotency.Set(idemKey, resp)

	c.JSON(http.StatusOK, resp)
}

// Repay makes a credit repayment
func (h *CreditLendingHandler) Repay(c *gin.Context) {
	// Require authentication
	if _, ok := requireAuth(c); !ok {
		return
	}

	id, err := strconv.ParseInt(c.Param("id"), 10, 64)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "invalid application ID"})
		return
	}

	var req struct {
		Amount float64 `json:"amount" binding:"required"`
	}

	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	// Validate repayment amount
	if req.Amount <= 0 {
		c.JSON(http.StatusBadRequest, gin.H{"error": "repayment amount must be positive"})
		return
	}
	if req.Amount > 1000000000 {
		c.JSON(http.StatusBadRequest, gin.H{"error": "amount exceeds maximum limit"})
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

	// Get application to check ownership (after idempotency to avoid unnecessary DB calls)
	application, err := h.svc.GetApplication(id)
	if err != nil {
		c.JSON(http.StatusNotFound, gin.H{"error": "application not found"})
		return
	}
	authUserID, _ := getCurrentUserID(c)
	if application.UserID != authUserID && !isAdmin(c) {
		c.JSON(403, gin.H{"error": "access denied: you can only repay your own loans"})
		return
	}

	repayment, err := h.svc.Repay(id, req.Amount)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	resp := gin.H{"repayment": repayment, "message": "repayment recorded"}
	idempotency.Set(idemKey, resp)

	c.JSON(http.StatusOK, resp)
}

// GetRepayments retrieves all repayments for an application
func (h *CreditLendingHandler) GetRepayments(c *gin.Context) {
	// Require authentication
	if _, ok := requireAuth(c); !ok {
		return
	}

	id, err := strconv.ParseInt(c.Param("id"), 10, 64)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "invalid application ID"})
		return
	}

	// Check ownership: application must belong to user or admin
	application, err := h.svc.GetApplication(id)
	if err != nil {
		c.JSON(http.StatusNotFound, gin.H{"error": "application not found"})
		return
	}
	authUserID, _ := getCurrentUserID(c)
	if application.UserID != authUserID && !isAdmin(c) {
		c.JSON(403, gin.H{"error": "access denied: you can only view repayments for your own applications"})
		return
	}

	repayments, err := h.svc.GetRepayments(id)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusOK, gin.H{"repayments": repayments, "count": len(repayments)})
}

// GetCreditScore retrieves a user's credit score
func (h *CreditLendingHandler) GetCreditScore(c *gin.Context) {
	// Require authentication
	if _, ok := requireAuth(c); !ok {
		return
	}

	userID := c.Param("user_id")
	// Check ownership: users can view their own score, admin can view any
	authUserID, _ := getCurrentUserID(c)
	if userID != authUserID && !isAdmin(c) {
		c.JSON(403, gin.H{"error": "access denied: you can only view your own credit score"})
		return
	}

	score, err := h.svc.GetCreditScore(userID)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusOK, gin.H{"credit_score": score})
}
