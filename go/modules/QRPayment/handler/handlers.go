package handler

import (
	"net/http"
	"strconv"

	bm "community.local/modules/QRPayment/internal/models"
	qrpayment "community.local/modules/QRPayment/service"
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

// Simple ownership check - merchant_id should match user_id or user is admin
func requireMerchantAuth(c *gin.Context, merchantID string) bool {
	userID, ok := requireAuth(c)
	if !ok {
		return false
	}
	if userID == merchantID {
		return true
	}
	// Check admin role
	if roles, ok := c.Get("user_roles"); ok {
		roleList, _ := roles.([]string)
		for _, r := range roleList {
			if r == "ROLE_ADMIN" || r == "admin" {
				return true
			}
		}
	}
	c.JSON(http.StatusForbidden, gin.H{"error": "access denied: insufficient permissions"})
	return false
}

// QRPaymentHandler handles QR payment HTTP requests
type QRPaymentHandler struct {
	svc *qrpayment.QRPaymentService
}

// NewQRPaymentHandler creates a new QR payment handler
func NewQRPaymentHandler(svc *qrpayment.QRPaymentService) *QRPaymentHandler {
	return &QRPaymentHandler{svc: svc}
}

// RegisterRoutes registers all QR payment routes
func (h *QRPaymentHandler) RegisterRoutes(router *gin.Engine) {
	qr := router.Group("/api/qr")
	qr.Use(middleware.TimeoutMiddleware(middleware.DefaultTimeoutConfig()))
	qr.Use(middleware.AuthRequired())
	qr.Use(middleware.RateLimitPayment())
	{
		qr.POST("/generate", h.GenerateQRCode)
		qr.GET("/static/:merchant_id", h.GetStaticQRCode)
		qr.GET("/:code", h.GetQRCode)
		qr.POST("/pay", h.ProcessPayment)
		qr.GET("/payment/:id", h.GetPayment)
		qr.GET("/payment/ref/:reference_id", h.GetPaymentByReference)
		qr.GET("/payments/:user_id", h.GetUserPayments)
	}
}

// GenerateQRCode generates a new QR code for payment
func (h *QRPaymentHandler) GenerateQRCode(c *gin.Context) {
	var req struct {
		MerchantID    string  `json:"merchant_id" binding:"required"`
		MerchantName  string  `json:"merchant_name" binding:"required"`
		Amount        float64 `json:"amount"`
		Currency      string  `json:"currency"`
		Description   string  `json:"description"`
		ExpiryMinutes int     `json:"expiry_minutes"`
	}

	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	// Require authentication and merchant authorization
	if !requireMerchantAuth(c, req.MerchantID) {
		return
	}

	if req.Currency == "" {
		req.Currency = "VND"
	}
	if req.ExpiryMinutes == 0 {
		req.ExpiryMinutes = 30
	}

	serviceReq := &bm.GenerateQRRequest{
		MerchantID:    req.MerchantID,
		MerchantName:  req.MerchantName,
		Amount:        req.Amount,
		Currency:      req.Currency,
		Description:   req.Description,
		ExpiryMinutes: req.ExpiryMinutes,
	}

	ctx := c.Request.Context()
	qr, err := h.svc.GenerateQRCode(ctx, serviceReq)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusCreated, gin.H{
		"qr_code":    qr,
		"qr_string":  qr.Code,
		"expires_at": qr.ExpiresAt,
	})
}

// GetStaticQRCode retrieves or creates a static QR code for a merchant
func (h *QRPaymentHandler) GetStaticQRCode(c *gin.Context) {
	ctx := c.Request.Context()
	merchantID := c.Param("merchant_id")
	merchantName := c.Query("name")

	if merchantName == "" {
		merchantName = merchantID
	}

	qr, err := h.svc.GetStaticQRCode(ctx, merchantID, merchantName)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"qr_code":   qr,
		"qr_string": qr.Code,
	})
}

// GetQRCode retrieves a QR code by its code string
func (h *QRPaymentHandler) GetQRCode(c *gin.Context) {
	ctx := c.Request.Context()
	code := c.Param("code")

	qr, err := h.svc.GetQRCodeByCode(ctx, code)
	if err != nil {
		c.JSON(http.StatusNotFound, gin.H{"error": "QR code not found"})
		return
	}

	c.JSON(http.StatusOK, gin.H{"qr_code": qr})
}

// ProcessPayment processes a payment via QR code
func (h *QRPaymentHandler) ProcessPayment(c *gin.Context) {
	ctx := c.Request.Context()
	var req bm.PaymentRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	// Validate payment amount
	if req.Amount <= 0 {
		c.JSON(http.StatusBadRequest, gin.H{"error": "amount must be positive"})
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

	payment, err := h.svc.ProcessPayment(ctx, &req)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	resp := gin.H{"payment": payment, "message": "Payment successful"}
	idempotency.Set(idemKey, resp)

	c.JSON(http.StatusOK, resp)
}

// GetPayment retrieves a payment by ID
func (h *QRPaymentHandler) GetPayment(c *gin.Context) {
	ctx := c.Request.Context()
	id, _ := strconv.ParseInt(c.Param("id"), 10, 64)

	payment, err := h.svc.GetPayment(ctx, id)
	if err != nil {
		c.JSON(http.StatusNotFound, gin.H{"error": "payment not found"})
		return
	}

	// Require authentication and check ownership
	userID, ok := requireAuth(c)
	if !ok {
		return
	}
	if payment.PayerID != userID {
		// Allow admin
		if roles, _ := c.Get("user_roles"); roles != nil {
			for _, r := range roles.([]string) {
				if r == "ROLE_ADMIN" || r == "admin" {
					goto authorized
				}
			}
		}
		c.JSON(http.StatusForbidden, gin.H{"error": "access denied: you can only view your own payments"})
		return
	}
authorized:
	c.JSON(http.StatusOK, gin.H{"payment": payment})
}

// GetPaymentByReference retrieves a payment by reference ID
func (h *QRPaymentHandler) GetPaymentByReference(c *gin.Context) {
	ctx := c.Request.Context()
	refID := c.Param("reference_id")

	payment, err := h.svc.GetPaymentByReference(ctx, refID)
	if err != nil {
		c.JSON(http.StatusNotFound, gin.H{"error": "payment not found"})
		return
	}

	// Require authentication - payments are private
	if _, ok := requireAuth(c); !ok {
		return
	}

	c.JSON(http.StatusOK, gin.H{"payment": payment})
}

// GetUserPayments retrieves all payments for a user
func (h *QRPaymentHandler) GetUserPayments(c *gin.Context) {
	ctx := c.Request.Context()
	requestedUserID := c.Param("user_id")
	limit, _ := strconv.Atoi(c.DefaultQuery("limit", "20"))
	offset, _ := strconv.Atoi(c.DefaultQuery("offset", "0"))

	// Require authentication
	authUserID, ok := requireAuth(c)
	if !ok {
		return
	}

	// Users can only view their own payments unless admin
	if authUserID != requestedUserID {
		if roles, _ := c.Get("user_roles"); roles != nil {
			for _, r := range roles.([]string) {
				if r == "ROLE_ADMIN" || r == "admin" {
					goto authorized
				}
			}
		}
		c.JSON(http.StatusForbidden, gin.H{"error": "access denied: you can only view your own payments"})
		return
	}
authorized:
	payments, err := h.svc.GetUserPayments(ctx, requestedUserID, limit, offset)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"payments": payments,
		"limit":    limit,
		"offset":   offset,
	})
}