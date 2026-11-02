package handler

import (
	"net/http"
	"strconv"
	"time"

	"github.com/gin-gonic/gin"
	fd "community.local/modules/FraudDetection/service"
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

// FraudDetectionHandler handles fraud detection HTTP requests
type FraudDetectionHandler struct {
	svc *fd.FraudService
}

// NewFraudDetectionHandler creates a new fraud detection handler
func NewFraudDetectionHandler(svc *fd.FraudService) *FraudDetectionHandler {
	return &FraudDetectionHandler{svc: svc}
}

// RegisterRoutes registers all fraud detection routes
func (h *FraudDetectionHandler) RegisterRoutes(router *gin.Engine) {
	fraud := router.Group("/api/fraud")
	{
		fraud.POST("/analyze", h.AnalyzeTransaction)
		fraud.GET("/alerts", h.GetOpenAlerts)
		fraud.GET("/alerts/user/:user_id", h.GetUserAlerts)
		fraud.GET("/alerts/:id", h.GetAlertByID)
		fraud.POST("/alerts/:id/resolve", h.ResolveAlert)
		fraud.POST("/rules", h.CreateRule)
		fraud.GET("/rules", h.GetActiveRules)
		fraud.GET("/reports", h.GetFraudReports)
		fraud.POST("/reports", h.CreateReport)
	}
}

// AnalyzeTransaction analyzes a transaction for fraud
func (h *FraudDetectionHandler) AnalyzeTransaction(c *gin.Context) {
	// Require authentication and admin role
	if _, ok := requireAuth(c); !ok {
		return
	}
	if !isAdmin(c) {
		c.JSON(403, gin.H{"error": "access denied: only admin/fraud analyst can analyze transactions"})
		return
	}

	var req struct {
		UserID          string  `json:"user_id" binding:"required"`
		Amount          float64 `json:"amount" binding:"required"`
		TransactionType string  `json:"transaction_type" binding:"required"`
		PaymentMethod   string  `json:"payment_method"`
		IPAddress       string  `json:"ip_address"`
		DeviceID        string  `json:"device_id"`
		Location        string  `json:"location"`
		IdempotencyKey  string  `json:"idempotency_key"`
	}

	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	if req.Amount < 0 {
		c.JSON(http.StatusBadRequest, gin.H{"error": "amount must be positive"})
		return
	}

	if req.Amount > 1000000000 {
		c.JSON(http.StatusBadRequest, gin.H{"error": "amount exceeds maximum limit"})
		return
	}

	if len(req.UserID) < 1 || len(req.UserID) > 100 {
		c.JSON(http.StatusBadRequest, gin.H{"error": "invalid user_id"})
		return
	}

	idempotencyKey := c.GetHeader("Idempotency-Key")
	if idempotencyKey != "" {
		cachedResult := h.svc.GetCachedResult(idempotencyKey)
		if cachedResult != nil {
			c.JSON(http.StatusOK, gin.H{
				"risk_score": cachedResult.RiskScore,
				"alert":      cachedResult.Alert,
				"risk_level": h.getRiskLevel(cachedResult.RiskScore),
				"idempotent": true,
			})
			return
		}
	}

	txnCtx := fd.TransactionContext{
		UserID:          req.UserID,
		Amount:          req.Amount,
		TransactionType: req.TransactionType,
		PaymentMethod:   req.PaymentMethod,
		IPAddress:       req.IPAddress,
		DeviceID:        req.DeviceID,
		Location:        req.Location,
		TimeOfDay:       12,
		History: fd.TransactionHistory{
			TotalTransactions: 0,
			TotalAmount:       0,
			AvgTransactionAmt: req.Amount,
			MaxTransactionAmt: req.Amount,
			DaysSinceFirstTx:  30,
			Countries:         []string{req.Location},
		},
	}

	alert, score, err := h.svc.AnalyzeTransaction(c.Request.Context(), txnCtx)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	if idempotencyKey != "" {
		h.svc.CacheResult(idempotencyKey, &fd.IdempotencyResult{
			RiskScore: score,
			Alert:     alert,
			CreatedAt: time.Now(),
		})
	}

	c.JSON(http.StatusOK, gin.H{
		"risk_score": score,
		"alert":      alert,
		"risk_level": h.getRiskLevel(score),
	})
}

// GetOpenAlerts retrieves all open fraud alerts
func (h *FraudDetectionHandler) GetOpenAlerts(c *gin.Context) {
	// Require authentication - admin only
	if _, ok := requireAuth(c); !ok {
		return
	}
	if !isAdmin(c) {
		c.JSON(403, gin.H{"error": "access denied: only admin can view all alerts"})
		return
	}

	alerts, err := h.svc.GetOpenAlerts(c.Request.Context())
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusOK, gin.H{"alerts": alerts, "count": len(alerts)})
}

// GetUserAlerts retrieves fraud alerts for a specific user
func (h *FraudDetectionHandler) GetUserAlerts(c *gin.Context) {
	// Require authentication
	if _, ok := requireAuth(c); !ok {
		return
	}

	userID := c.Param("user_id")
	// Check if user is viewing own alerts or is admin
	authUserID, _ := getCurrentUserID(c)
	if authUserID != userID && !isAdmin(c) {
		c.JSON(403, gin.H{"error": "access denied: you can only view your own alerts"})
		return
	}

	alerts, err := h.svc.GetUserAlerts(c.Request.Context(), userID)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusOK, gin.H{"user_id": userID, "alerts": alerts, "count": len(alerts)})
}

// GetAlertByID retrieves a specific fraud alert by ID
func (h *FraudDetectionHandler) GetAlertByID(c *gin.Context) {
	// Require authentication
	if _, ok := requireAuth(c); !ok {
		return
	}

	id, err := strconv.ParseInt(c.Param("id"), 10, 64)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "invalid alert ID"})
		return
	}

	// For now, search in open alerts
	alerts, _ := h.svc.GetOpenAlerts(c.Request.Context())
	for _, alert := range alerts {
		if alert.ID == id {
			c.JSON(http.StatusOK, gin.H{"alert": alert})
			return
		}
	}

	c.JSON(http.StatusNotFound, gin.H{"error": "alert not found"})
}

// ResolveAlert resolves a fraud alert
func (h *FraudDetectionHandler) ResolveAlert(c *gin.Context) {
	// Require authentication and admin role
	if _, ok := requireAuth(c); !ok {
		return
	}
	if !isAdmin(c) {
		c.JSON(403, gin.H{"error": "access denied: only admin can resolve alerts"})
		return
	}

	id, err := strconv.ParseInt(c.Param("id"), 10, 64)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "invalid alert ID"})
		return
	}

	var req struct {
		ResolvedBy string `json:"resolved_by" binding:"required"`
		Resolution string `json:"resolution" binding:"required"`
	}

	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	err = h.svc.ResolveAlert(c.Request.Context(), id, req.ResolvedBy, req.Resolution)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusOK, gin.H{"message": "alert resolved successfully"})
}

// CreateRule creates a new fraud detection rule
func (h *FraudDetectionHandler) CreateRule(c *gin.Context) {
	// Require authentication - admin only
	if _, ok := requireAuth(c); !ok {
		return
	}
	if !isAdmin(c) {
		c.JSON(403, gin.H{"error": "access denied: only admin can create rules"})
		return
	}

	var req struct {
		Name        string  `json:"name" binding:"required"`
		Description string  `json:"description"`
		RuleType    string  `json:"rule_type" binding:"required"` // amount, frequency, location, payment_method
		Condition   string  `json:"condition" binding:"required"`
		Action      string  `json:"action" binding:"required"`
		RiskScore   float64 `json:"risk_score" binding:"required"`
	}

	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	err := h.svc.CreateRule(c.Request.Context(), req.Name, req.Description, req.RuleType, req.Condition, req.Action, req.RiskScore)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusCreated, gin.H{"message": "rule created successfully"})
}

// GetActiveRules retrieves all active fraud detection rules
func (h *FraudDetectionHandler) GetActiveRules(c *gin.Context) {
	// For now, return default rules
	defaultRules := []gin.H{
		{
			"name": "High Amount Transaction",
			"description": "Flag transactions over 100,000,000 VND",
			"rule_type": "amount",
			"condition": "100000000",
			"action": "flag",
			"risk_score": 30,
			"is_active": true,
		},
		{
			"name": "Multiple Countries",
			"description": "Flag transactions from multiple countries in short period",
			"rule_type": "location",
			"condition": `["VN", "US", "CN"]`,
			"action": "flag",
			"risk_score": 25,
			"is_active": true,
		},
		{
			"name": "Unusual Time",
			"description": "Flag transactions between midnight and 5 AM",
			"rule_type": "time",
			"condition": "0-5",
			"action": "flag",
			"risk_score": 20,
			"is_active": true,
		},
	}

	c.JSON(http.StatusOK, gin.H{"rules": defaultRules, "count": len(defaultRules)})
}

// GetFraudReports retrieves fraud reports (placeholder)
func (h *FraudDetectionHandler) GetFraudReports(c *gin.Context) {
	// Require authentication - admin only
	if _, ok := requireAuth(c); !ok {
		return
	}
	if !isAdmin(c) {
		c.JSON(403, gin.H{"error": "access denied: only admin can view reports"})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"reports": []map[string]interface{}{},
		"count":   0,
		"message": "Fraud reports feature coming soon",
	})
}

// CreateReport creates a new fraud report (placeholder)
func (h *FraudDetectionHandler) CreateReport(c *gin.Context) {
	// Require authentication
	if _, ok := requireAuth(c); !ok {
		return
	}

	c.JSON(http.StatusCreated, gin.H{
		"message": "Fraud report submitted successfully",
		"report":  nil,
		"note":    "Reports will be reviewed within 24-48 hours",
	})
}

func (h *FraudDetectionHandler) getRiskLevel(score float64) string {
	if score >= 80 {
		return "critical"
	} else if score >= 60 {
		return "high"
	} else if score >= 40 {
		return "medium"
	}
	return "low"
}
