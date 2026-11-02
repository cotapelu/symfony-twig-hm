package handler

import (
	"fmt"
	"net/http"
	"strconv"
	"time"

	billspayment "community.local/modules/BillsPayment/internal/models"
	"community.local/internal/idempotency"
	"community.local/internal/middleware"

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
		c.JSON(http.StatusUnauthorized, gin.H{"error": "authentication required"})
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

// BillsPaymentHandler handles bills payment HTTP requests
type BillsPaymentHandler struct {
	svc *billspayment.BillsPaymentService
}

// NewBillsPaymentHandler creates a new bills payment handler
func NewBillsPaymentHandler(svc *billspayment.BillsPaymentService) *BillsPaymentHandler {
	return &BillsPaymentHandler{svc: svc}
}

// RegisterRoutes registers all bills payment routes
func (h *BillsPaymentHandler) RegisterRoutes(router *gin.Engine) {
	bills := router.Group("/api/bills")
	bills.Use(middleware.TimeoutMiddleware(middleware.DefaultTimeoutConfig()))
	bills.Use(middleware.AuthRequired())
	bills.Use(middleware.RateLimitPayment())
	{
		bills.POST("", h.CreateBill)
		bills.GET("/:id", h.GetBill)
		bills.GET("/account/:account_number", h.GetBillsByAccount)
		bills.GET("/upcoming", h.GetUpcomingBills)

		bills.GET("/providers", h.GetProviders)
		bills.GET("/providers/category/:category", h.GetProvidersByCategory)

		bills.POST("/:id/pay", h.PayBill)
		bills.GET("/payments/:user_id", h.GetPayments)
		bills.GET("/payments/tx/:transaction_id", h.GetPaymentByTransaction)
	}
}

// CreateBill creates a new bill
func (h *BillsPaymentHandler) CreateBill(c *gin.Context) {
	ctx := c.Request.Context()
	var req struct {
		BillType      string  `json:"bill_type" binding:"required"`
		Provider      string  `json:"provider" binding:"required"`
		AccountNumber string  `json:"account_number" binding:"required"`
		CustomerName  string  `json:"customer_name" binding:"required"`
		Amount        float64 `json:"amount" binding:"required"`
		Period        string  `json:"period" binding:"required"`
		DueDate       string  `json:"due_date" binding:"required"`
	}

	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	dueDate, err := parseDate(req.DueDate)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "invalid due date format"})
		return
	}

	bill, err := h.svc.CreateBill(ctx, req.BillType, req.Provider, req.AccountNumber, req.CustomerName, req.Amount, req.Period, *dueDate)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusCreated, gin.H{"bill": bill})
}

// GetBill retrieves a bill by ID
func (h *BillsPaymentHandler) GetBill(c *gin.Context) {
	ctx := c.Request.Context()
	id, err := strconv.ParseInt(c.Param("id"), 10, 64)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "invalid bill ID"})
		return
	}

	bill, err := h.svc.GetBill(ctx, id)
	if err != nil {
		c.JSON(http.StatusNotFound, gin.H{"error": "bill not found"})
		return
	}

	c.JSON(http.StatusOK, gin.H{"bill": bill})
}

// GetBillsByAccount retrieves all bills for an account number
func (h *BillsPaymentHandler) GetBillsByAccount(c *gin.Context) {
	ctx := c.Request.Context()
	accNum := c.Param("account_number")

	bills, err := h.svc.GetBillsByAccount(ctx, accNum)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusOK, gin.H{"bills": bills})
}

// GetUpcomingBills retrieves bills due within the specified number of days
func (h *BillsPaymentHandler) GetUpcomingBills(c *gin.Context) {
	ctx := c.Request.Context()
	days, _ := strconv.Atoi(c.DefaultQuery("days", "7"))

	bills, err := h.svc.GetUpcomingBills(ctx, days)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"bills": bills,
		"days":  days,
	})
}

// GetProviders retrieves all active bill providers
func (h *BillsPaymentHandler) GetProviders(c *gin.Context) {
	ctx := c.Request.Context()
	providers, err := h.svc.GetProviders(ctx)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusOK, gin.H{"providers": providers})
}

// GetProvidersByCategory retrieves bill providers by category
func (h *BillsPaymentHandler) GetProvidersByCategory(c *gin.Context) {
	ctx := c.Request.Context()
	category := c.Param("category")

	providers, err := h.svc.GetProvidersByCategory(ctx, category)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"providers": providers,
		"category":  category,
	})
}

// PayBill processes a bill payment
func (h *BillsPaymentHandler) PayBill(c *gin.Context) {
	ctx := c.Request.Context()
	id, err := strconv.ParseInt(c.Param("id"), 10, 64)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "invalid bill ID"})
		return
	}

	// Require authentication
	userID, ok := requireAuth(c)
	if !ok {
		return
	}

	var req struct {
		PaymentMethod string `json:"payment_method" binding:"required"`
	}

	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	// Idempotency check
	idemKey := c.GetHeader("Idempotency-Key")
	if idemKey == "" {
		c.JSON(http.StatusBadRequest, gin.H{"error": "Idempotency-Key header required"})
		return
	}
	if cached, ok := idempotency.Get(idemKey); ok {
		c.JSON(http.StatusOK, cached)
		return
	}

	payment, err := h.svc.PayBill(ctx, id, userID, req.PaymentMethod)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	resp := gin.H{
		"payment": payment,
		"message": "Bill payment successful",
	}
	idempotency.Set(idemKey, resp)

	c.JSON(http.StatusOK, resp)
}

// GetPayments retrieves all payments for a user
func (h *BillsPaymentHandler) GetPayments(c *gin.Context) {
	ctx := c.Request.Context()
	requestedUserID := c.Param("user_id")

	// Require authentication
	authUserID, ok := requireAuth(c)
	if !ok {
		return
	}

	// Users can only view their own payments unless admin
	if authUserID != requestedUserID && !isAdmin(c) {
		c.JSON(http.StatusForbidden, gin.H{"error": "access denied: you can only view your own payments"})
		return
	}

	payments, err := h.svc.GetPayments(ctx, requestedUserID)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusOK, gin.H{"payments": payments})
}

// GetPaymentByTransaction retrieves a payment by transaction ID
func (h *BillsPaymentHandler) GetPaymentByTransaction(c *gin.Context) {
	// Require authentication
	authUserID, ok := requireAuth(c)
	if !ok {
		return
	}

	txID := c.Param("transaction_id")
	ctx := c.Request.Context()

	payment, err := h.svc.GetPaymentByTransaction(ctx, txID)
	if err != nil {
		c.JSON(http.StatusNotFound, gin.H{"error": "payment not found"})
		return
	}

	// Ownership check: only the user who made the payment or admin can view
	if payment.UserID != authUserID && !isAdmin(c) {
		c.JSON(http.StatusForbidden, gin.H{"error": "access denied: you can only view your own payments"})
		return
	}

	c.JSON(http.StatusOK, gin.H{"payment": payment})
}

func parseDate(dateStr string) (*time.Time, error) {
	formats := []string{
		"2006-01-02",
		"02/01/2006",
		"2006/01/02",
	}

	for _, format := range formats {
		if t, err := time.Parse(format, dateStr); err == nil {
			return &t, nil
		}
	}

	return nil, fmt.Errorf("unable to parse date")
}