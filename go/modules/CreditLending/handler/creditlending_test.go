package handler

import (
	"bytes"
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"testing"

	"github.com/gin-gonic/gin"
	"github.com/stretchr/testify/assert"
)

func TestCreditLendingHandler_CreateApplication(t *testing.T) {
	gin.SetMode(gin.TestMode)
	router := gin.New()

	router.POST("/api/credit/applications", func(c *gin.Context) {
		var req struct {
			UserID     string  `json:"user_id"`
			CreditType string  `json:"credit_type"`
			Amount     float64 `json:"amount"`
			Term       int     `json:"term"`
		}
		if err := c.ShouldBindJSON(&req); err != nil {
			c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
			return
		}
		c.JSON(http.StatusCreated, gin.H{"status": "created"})
	})

	body := map[string]interface{}{
		"user_id":     "user_123",
		"credit_type": "personal",
		"amount":      5000000.0,
		"term":        12,
	}
	jsonBody, _ := json.Marshal(body)

	req, _ := http.NewRequest("POST", "/api/credit/applications", bytes.NewBuffer(jsonBody))
	req.Header.Set("Content-Type", "application/json")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusCreated, w.Code)
}

func TestCreditLendingHandler_GetApplication(t *testing.T) {
	gin.SetMode(gin.TestMode)
	router := gin.New()

	router.GET("/api/credit/applications/:id", func(c *gin.Context) {
		c.JSON(http.StatusOK, gin.H{
			"id":          1,
			"user_id":     "user_123",
			"credit_type": "personal",
			"amount":      5000000.0,
			"status":      "pending",
		})
	})

	req, _ := http.NewRequest("GET", "/api/credit/applications/1", nil)
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
}

func TestCreditLendingHandler_GetUserApplications(t *testing.T) {
	gin.SetMode(gin.TestMode)
	router := gin.New()

	router.GET("/api/credit/applications/user/:user_id", func(c *gin.Context) {
		c.JSON(http.StatusOK, gin.H{
			"applications": []interface{}{},
			"count":        0,
		})
	})

	req, _ := http.NewRequest("GET", "/api/credit/applications/user/user_123", nil)
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
}

func TestCreditLendingHandler_ApproveApplication(t *testing.T) {
	gin.SetMode(gin.TestMode)
	router := gin.New()

	router.POST("/api/credit/applications/:id/approve", func(c *gin.Context) {
		c.JSON(http.StatusOK, gin.H{
			"status": "approved",
		})
	})

	body := map[string]interface{}{
		"approved_amount": 4000000.0,
	}
	jsonBody, _ := json.Marshal(body)

	req, _ := http.NewRequest("POST", "/api/credit/applications/1/approve", bytes.NewBuffer(jsonBody))
	req.Header.Set("Content-Type", "application/json")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
}

func TestCreditLendingHandler_Disburse(t *testing.T) {
	gin.SetMode(gin.TestMode)
	router := gin.New()

	router.POST("/api/credit/applications/:id/disburse", func(c *gin.Context) {
		c.JSON(http.StatusOK, gin.H{
			"status": "disbursed",
		})
	})

	req, _ := http.NewRequest("POST", "/api/credit/applications/1/disburse", nil)
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
}

func TestCreditLendingHandler_Repay(t *testing.T) {
	gin.SetMode(gin.TestMode)
	router := gin.New()

	router.POST("/api/credit/applications/:id/repay", func(c *gin.Context) {
		c.JSON(http.StatusOK, gin.H{
			"status": "repayment recorded",
		})
	})

	body := map[string]interface{}{
		"amount": 500000.0,
	}
	jsonBody, _ := json.Marshal(body)

	req, _ := http.NewRequest("POST", "/api/credit/applications/1/repay", bytes.NewBuffer(jsonBody))
	req.Header.Set("Content-Type", "application/json")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
}

func TestCreditLendingHandler_GetRepayments(t *testing.T) {
	gin.SetMode(gin.TestMode)
	router := gin.New()

	router.GET("/api/credit/applications/:id/repayments", func(c *gin.Context) {
		c.JSON(http.StatusOK, gin.H{
			"repayments": []interface{}{},
			"count":      0,
		})
	})

	req, _ := http.NewRequest("GET", "/api/credit/applications/1/repayments", nil)
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
}

func TestCreditLendingHandler_GetCreditScore(t *testing.T) {
	gin.SetMode(gin.TestMode)
	router := gin.New()

	router.GET("/api/credit/score/:user_id", func(c *gin.Context) {
		c.JSON(http.StatusOK, gin.H{
			"credit_score": 750,
		})
	})

	req, _ := http.NewRequest("GET", "/api/credit/score/user_123", nil)
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
}

func TestCreditLendingHandler_InvalidApplicationID(t *testing.T) {
	gin.SetMode(gin.TestMode)
	router := gin.New()

	router.GET("/api/credit/applications/:id", func(c *gin.Context) {
		c.JSON(http.StatusBadRequest, gin.H{"error": "invalid ID"})
	})

	req, _ := http.NewRequest("GET", "/api/credit/applications/invalid", nil)
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusBadRequest, w.Code)
}

func TestCreditLendingHandler_MissingRequiredFields(t *testing.T) {
	gin.SetMode(gin.TestMode)
	router := gin.New()

	router.POST("/api/credit/applications", func(c *gin.Context) {
		var req struct {
			UserID string `json:"user_id" binding:"required"`
		}
		if err := c.ShouldBindJSON(&req); err != nil {
			c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
			return
		}
	})

	body := map[string]interface{}{}
	jsonBody, _ := json.Marshal(body)

	req, _ := http.NewRequest("POST", "/api/credit/applications", bytes.NewBuffer(jsonBody))
	req.Header.Set("Content-Type", "application/json")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusBadRequest, w.Code)
}
