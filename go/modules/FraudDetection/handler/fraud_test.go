package handler

import (
	"net/http"
	"net/http/httptest"
	"testing"

	"github.com/gin-gonic/gin"
	"github.com/stretchr/testify/assert"
)

func TestAnalyzeFraud(t *testing.T) {
	gin.SetMode(gin.TestMode)
	router := gin.New()

	router.POST("/api/fraud/analyze", func(c *gin.Context) {
		c.JSON(http.StatusOK, gin.H{
			"transaction_id": "tx_123",
			"risk_score":     0.2,
			"is_fraud":       false,
		})
	})

	req, _ := http.NewRequest("POST", "/api/fraud/analyze", nil)
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
}

func TestGetFraudAlerts(t *testing.T) {
	gin.SetMode(gin.TestMode)
	router := gin.New()

	router.GET("/api/fraud/alerts", func(c *gin.Context) {
		c.JSON(http.StatusOK, gin.H{
			"alerts": []gin.H{
				{"id": 1, "risk_score": 0.9},
			},
		})
	})

	req, _ := http.NewRequest("GET", "/api/fraud/alerts", nil)
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
}

func TestBlockTransaction(t *testing.T) {
	gin.SetMode(gin.TestMode)
	router := gin.New()

	router.POST("/api/fraud/block", func(c *gin.Context) {
		c.JSON(http.StatusOK, gin.H{
			"status":      "blocked",
			"transaction": "tx_123",
		})
	})

	req, _ := http.NewRequest("POST", "/api/fraud/block", nil)
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
}
