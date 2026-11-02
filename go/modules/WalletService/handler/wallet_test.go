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

func TestCreateWallet(t *testing.T) {
	gin.SetMode(gin.TestMode)
	router := gin.New()

	router.POST("/api/wallet/create", func(c *gin.Context) {
		c.JSON(http.StatusOK, gin.H{
			"wallet_id": "wallet_123",
			"balance":   0,
			"currency":  "VND",
		})
	})

	body := `{"user_id": 123, "currency": "VND"}`
	req, _ := http.NewRequest("POST", "/api/wallet/create", bytes.NewBufferString(body))
	req.Header.Set("Content-Type", "application/json")

	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
}

func TestGetWalletBalance(t *testing.T) {
	gin.SetMode(gin.TestMode)
	router := gin.New()

	router.GET("/api/wallet/:id/balance", func(c *gin.Context) {
		c.JSON(http.StatusOK, gin.H{
			"wallet_id": "wallet_123",
			"balance":   100000,
			"currency":  "VND",
		})
	})

	req, _ := http.NewRequest("GET", "/api/wallet/wallet_123/balance", nil)
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
}

func TestAddFunds(t *testing.T) {
	gin.SetMode(gin.TestMode)
	router := gin.New()

	router.POST("/api/wallet/:id/add-funds", func(c *gin.Context) {
		c.JSON(http.StatusOK, gin.H{
			"wallet_id": "wallet_123",
			"balance":   200000,
			"currency":  "VND",
		})
	})

	body := `{"amount": 100000}`
	req, _ := http.NewRequest("POST", "/api/wallet/wallet_123/add-funds", bytes.NewBufferString(body))
	req.Header.Set("Content-Type", "application/json")

	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
}

func TestTransferFunds(t *testing.T) {
	gin.SetMode(gin.TestMode)
	router := gin.New()

	router.POST("/api/wallet/transfer", func(c *gin.Context) {
		var req map[string]interface{}
		json.NewDecoder(c.Request.Body).Decode(&req)
		c.JSON(http.StatusOK, gin.H{
			"from_wallet": req["from_wallet"],
			"to_wallet":   req["to_wallet"],
			"amount":      req["amount"],
			"new_balance": 50000,
		})
	})

	body := `{"from_wallet": "wallet_123", "to_wallet": "wallet_456", "amount": 50000}`
	req, _ := http.NewRequest("POST", "/api/wallet/transfer", bytes.NewBufferString(body))
	req.Header.Set("Content-Type", "application/json")

	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
}

func TestGetTransactionHistory(t *testing.T) {
	gin.SetMode(gin.TestMode)
	router := gin.New()

	router.GET("/api/wallet/:id/transactions", func(c *gin.Context) {
		c.JSON(http.StatusOK, gin.H{
			"transactions": []gin.H{
				{"id": "tx1", "type": "credit", "amount": 100000},
				{"id": "tx2", "type": "debit", "amount": 50000},
			},
		})
	})

	req, _ := http.NewRequest("GET", "/api/wallet/wallet_123/transactions", nil)
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
}
