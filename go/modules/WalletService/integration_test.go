package WalletService

import (
	"context"
	"database/sql"
	"encoding/json"
	"fmt"
	"net/http"
	"net/http/httptest"
	"os"
	"strings"
	"testing"

	_ "github.com/lib/pq"
	"github.com/golang-jwt/jwt/v5"
	"github.com/stretchr/testify/assert"
	"github.com/gin-gonic/gin"

	"community.local/internal/middleware"

	wh "community.local/modules/WalletService/handler"
	wm "community.local/modules/WalletService/internal/models"
)

func getDB(t *testing.T) *sql.DB {
	dsn := os.Getenv("INTEGRATION_TEST_DATABASE_URL")
	if dsn == "" {
		t.Skip("INTEGRATION_TEST_DATABASE_URL not set, skipping integration tests")
	}
	if !strings.Contains(dsn, "sslmode") {
		dsn += "?sslmode=disable"
	}
	db, err := sql.Open("postgres", dsn)
	if err != nil {
		t.Fatalf("failed to open database: %v", err)
	}
	ctx, cancel := context.WithTimeout(context.Background(), 10_000_000_000)
	defer cancel()
	if err := db.PingContext(ctx); err != nil {
		t.Fatalf("failed to ping database: %v", err)
	}
	return db
}

func createWalletSchema(db *sql.DB) error {
	// Drop existing tables to ensure clean schema
	_, _ = db.Exec(`DROP TABLE IF EXISTS wallet_transactions CASCADE;`)
	_, _ = db.Exec(`DROP TABLE IF EXISTS wallets CASCADE;`)
	// wallets
	_, err := db.Exec(`
		CREATE TABLE wallets (
			id SERIAL PRIMARY KEY,
			user_id VARCHAR(255) NOT NULL,
			balance BIGINT NOT NULL DEFAULT 0,
			currency VARCHAR(3) NOT NULL DEFAULT 'VND',
			status VARCHAR(50) NOT NULL DEFAULT 'active',
			is_default BOOLEAN NOT NULL DEFAULT false,
			version BIGINT NOT NULL DEFAULT 1,
			created_at TIMESTAMP NOT NULL DEFAULT NOW(),
			updated_at TIMESTAMP NOT NULL DEFAULT NOW()
		);
	`)
	if err != nil {
		return err
	}
	// wallet_transactions
	_, err = db.Exec(`
		CREATE TABLE wallet_transactions (
			id SERIAL PRIMARY KEY,
			wallet_id INT NOT NULL REFERENCES wallets(id),
			type VARCHAR(50) NOT NULL,
			amount BIGINT NOT NULL,
			balance_after BIGINT NOT NULL,
			description TEXT,
			reference_id VARCHAR(255) UNIQUE,
			metadata JSONB,
			status VARCHAR(50) NOT NULL,
			created_at TIMESTAMP NOT NULL DEFAULT NOW()
		);
	`)
	return err
}

func truncateWalletTables(db *sql.DB) error {
	_, err := db.Exec(`TRUNCATE wallet_transactions, wallets RESTART IDENTITY CASCADE;`)
	return err
}

func createTestToken(userID string, roles []string) (string, error) {
	claims := jwt.MapClaims{
		"userId": userID,
		"roles":  roles,
	}
	token := jwt.NewWithClaims(jwt.SigningMethodHS256, claims)
	return token.SignedString([]byte("dev-secret-change-in-production"))
}

func TestWalletService_CreateWalletAndGet(t *testing.T) {
	db := getDB(t)
	defer db.Close()

	if err := createWalletSchema(db); err != nil {
		t.Fatalf("schema creation failed: %v", err)
	}
	if err := truncateWalletTables(db); err != nil {
		t.Fatalf("truncate failed: %v", err)
	}

	svc := wm.NewWalletService(db, nil)
	handler := wh.NewWalletHandler(svc)

	r := gin.New()
	authMiddleware := middleware.NewAuthMiddleware("dev-secret-change-in-production")
	r.Use(func(c *gin.Context) {
		path := c.Request.URL.Path
		if strings.HasPrefix(path, "/api/") {
			authMiddleware.AuthRequired()(c)
		} else {
			c.Next()
		}
	})
	handler.RegisterRoutes(r)

	// Create test token
	token, err := createTestToken("user123", []string{})
	if err != nil {
		t.Fatalf("failed to create test token: %v", err)
	}

	// Create wallet
	body := `{"user_id":"user123"}`
	req := httptest.NewRequest("POST", "/api/wallet/create", strings.NewReader(body))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Authorization", "Bearer "+token)
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)
	assert.Equal(t, http.StatusCreated, w.Code)

	var resp struct {
		Wallet wm.Wallet `json:"wallet"`
	}
	if err := json.Unmarshal(w.Body.Bytes(), &resp); err != nil {
		t.Fatalf("failed to parse response: %v", err)
	}
	wallet := resp.Wallet
	assert.Equal(t, "user123", wallet.UserID)
	assert.Equal(t, "VND", wallet.Currency)
	assert.Equal(t, "active", wallet.Status)
	assert.NotZero(t, wallet.ID)
	assert.NotNil(t, wallet.CreatedAt)

	// Get wallet by user_id
	userID := wallet.UserID
	getReq := httptest.NewRequest("GET", fmt.Sprintf("/api/wallet/%s", userID), nil)
	getReq.Header.Set("Authorization", "Bearer "+token)
	getW := httptest.NewRecorder()
	r.ServeHTTP(getW, getReq)
	assert.Equal(t, http.StatusOK, getW.Code)

	var getResp struct {
		Wallet wm.Wallet `json:"wallet"`
	}
	if err := json.Unmarshal(getW.Body.Bytes(), &getResp); err != nil {
		t.Fatalf("failed to parse get response: %v", err)
	}
	assert.Equal(t, wallet.ID, getResp.Wallet.ID)
	assert.Equal(t, wallet.UserID, getResp.Wallet.UserID)
}

func TestWalletService_TopUp(t *testing.T) {
	db := getDB(t)
	defer db.Close()

	if err := createWalletSchema(db); err != nil {
		t.Fatalf("schema creation failed: %v", err)
	}
	if err := truncateWalletTables(db); err != nil {
		t.Fatalf("truncate failed: %v", err)
	}

	svc := wm.NewWalletService(db, nil)
	handler := wh.NewWalletHandler(svc)
	r := gin.New()
	authMiddleware := middleware.NewAuthMiddleware("dev-secret-change-in-production")
	r.Use(func(c *gin.Context) {
		path := c.Request.URL.Path
		if strings.HasPrefix(path, "/api/") {
			authMiddleware.AuthRequired()(c)
		} else {
			c.Next()
		}
	})
	handler.RegisterRoutes(r)

	// Create test token
	token, err := createTestToken("user456", []string{})
	if err != nil {
		t.Fatalf("failed to create test token: %v", err)
	}

	// Create wallet
	body := `{"user_id":"user456"}`
	req := httptest.NewRequest("POST", "/api/wallet/create", strings.NewReader(body))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Authorization", "Bearer "+token)
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)
	assert.Equal(t, http.StatusCreated, w.Code)
	var resp struct{ Wallet wm.Wallet }
	json.Unmarshal(w.Body.Bytes(), &resp)
	walletID := resp.Wallet.ID

	// TopUp - amount in cents (1000000 = 10,000 VND)
	topupBody := `{"user_id":"user456","amount":1000000,"payment_method":"bank_transfer"}`
	topupReq := httptest.NewRequest("POST", "/api/wallet/topup", strings.NewReader(topupBody))
	topupReq.Header.Set("Content-Type", "application/json")
	topupReq.Header.Set("Authorization", "Bearer "+token)
	topupReq.Header.Set("Idempotency-Key", "test-topup-key")
	topupW := httptest.NewRecorder()
	r.ServeHTTP(topupW, topupReq)
	assert.Equal(t, http.StatusOK, topupW.Code)

	var topupResp struct {
		Transaction wm.WalletTransaction `json:"transaction"`
	}
	if err := json.Unmarshal(topupW.Body.Bytes(), &topupResp); err != nil {
		t.Fatalf("failed to parse topup response: %v", err)
	}
	txn := topupResp.Transaction
	assert.Equal(t, walletID, txn.WalletID)
	assert.Equal(t, "topup", txn.Type)
	assert.Equal(t, int64(1000000), txn.Amount)
	assert.Equal(t, "completed", txn.Status)
	assert.NotZero(t, txn.ID)
	assert.NotZero(t, txn.BalanceAfter)
	assert.GreaterOrEqual(t, txn.BalanceAfter, int64(1000000))
}

func TestWalletService_Withdraw(t *testing.T) {
	db := getDB(t)
	defer db.Close()

	if err := createWalletSchema(db); err != nil {
		t.Fatalf("schema creation failed: %v", err)
	}
	if err := truncateWalletTables(db); err != nil {
		t.Fatalf("truncate failed: %v", err)
	}

	svc := wm.NewWalletService(db, nil)
	handler := wh.NewWalletHandler(svc)
	r := gin.New()
	authMiddleware := middleware.NewAuthMiddleware("dev-secret-change-in-production")
	r.Use(func(c *gin.Context) {
		path := c.Request.URL.Path
		if strings.HasPrefix(path, "/api/") {
			authMiddleware.AuthRequired()(c)
		} else {
			c.Next()
		}
	})
	handler.RegisterRoutes(r)

	// Create test token
	token, err := createTestToken("user789", []string{})
	if err != nil {
		t.Fatalf("failed to create test token: %v", err)
	}

	// Create wallet
	body := `{"user_id":"user789"}`
	req := httptest.NewRequest("POST", "/api/wallet/create", strings.NewReader(body))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Authorization", "Bearer "+token)
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)
	assert.Equal(t, http.StatusCreated, w.Code)
	var resp struct{ Wallet wm.Wallet }
	json.Unmarshal(w.Body.Bytes(), &resp)
	walletID := resp.Wallet.ID

	// TopUp first - 50,000 VND = 5000000 cents
	topupBody := `{"user_id":"user789","amount":5000000,"payment_method":"bank_transfer"}`
	topupReq := httptest.NewRequest("POST", "/api/wallet/topup", strings.NewReader(topupBody))
	topupReq.Header.Set("Content-Type", "application/json")
	topupReq.Header.Set("Authorization", "Bearer "+token)
	topupReq.Header.Set("Idempotency-Key", "test-topup-initial")
	topupW := httptest.NewRecorder()
	r.ServeHTTP(topupW, topupReq)
	assert.Equal(t, http.StatusOK, topupW.Code)

	// Withdraw - 20,000 VND = 2000000 cents
	withdrawBody := `{"user_id":"user789","amount":2000000,"bank_code":"VCB","account_no":"123456789","holder_name":"Test User"}`
	withdrawReq := httptest.NewRequest("POST", "/api/wallet/withdraw", strings.NewReader(withdrawBody))
	withdrawReq.Header.Set("Content-Type", "application/json")
	withdrawReq.Header.Set("Authorization", "Bearer "+token)
	withdrawReq.Header.Set("Idempotency-Key", "test-withdraw-key")
	withdrawW := httptest.NewRecorder()
	r.ServeHTTP(withdrawW, withdrawReq)
	assert.Equal(t, http.StatusOK, withdrawW.Code)

	var withdrawResp struct {
		Transaction wm.WalletTransaction `json:"transaction"`
	}
	if err := json.Unmarshal(withdrawW.Body.Bytes(), &withdrawResp); err != nil {
		t.Fatalf("failed to parse withdraw response: %v", err)
	}
	txn := withdrawResp.Transaction
	assert.Equal(t, walletID, txn.WalletID)
	assert.Equal(t, "withdraw", txn.Type)
	assert.Equal(t, int64(-2000000), txn.Amount) // withdraw stored as negative
	assert.Contains(t, txn.Status, "pending") // status is pending
	assert.NotZero(t, txn.ID)
	assert.NotZero(t, txn.BalanceAfter)
}

func TestWalletService_Transfer(t *testing.T) {
	db := getDB(t)
	defer db.Close()

	if err := createWalletSchema(db); err != nil {
		t.Fatalf("schema creation failed: %v", err)
	}
	if err := truncateWalletTables(db); err != nil {
		t.Fatalf("truncate failed: %v", err)
	}

	svc := wm.NewWalletService(db, nil)
	handler := wh.NewWalletHandler(svc)
	r := gin.New()
	authMiddleware := middleware.NewAuthMiddleware("dev-secret-change-in-production")
	r.Use(func(c *gin.Context) {
		path := c.Request.URL.Path
		if strings.HasPrefix(path, "/api/") {
			authMiddleware.AuthRequired()(c)
		} else {
			c.Next()
		}
	})
	handler.RegisterRoutes(r)

	// Create test tokens for both users
	tokenA, err := createTestToken("userA", []string{})
	if err != nil {
		t.Fatalf("failed to create tokenA: %v", err)
	}
	tokenB, err := createTestToken("userB", []string{})
	if err != nil {
		t.Fatalf("failed to create tokenB: %v", err)
	}

	// Create two wallets
	createWallet := func(userID, token string) wm.Wallet {
		body := fmt.Sprintf(`{"user_id":"%s"}`, userID)
		req := httptest.NewRequest("POST", "/api/wallet/create", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("Authorization", "Bearer "+token)
		w := httptest.NewRecorder()
		r.ServeHTTP(w, req)
		assert.Equal(t, http.StatusCreated, w.Code)
		var resp struct{ Wallet wm.Wallet }
		if err := json.Unmarshal(w.Body.Bytes(), &resp); err != nil {
			t.Fatalf("failed to parse create wallet response: %v", err)
		}
		return resp.Wallet
	}
	walletA := createWallet("userA", tokenA)
	createWallet("userB", tokenB) // ensure recipient wallet exists

	// TopUp walletA - 30,000 VND = 3000000 cents
	topupBody := `{"user_id":"userA","amount":3000000,"payment_method":"bank_transfer"}`
	topupReq := httptest.NewRequest("POST", "/api/wallet/topup", strings.NewReader(topupBody))
	topupReq.Header.Set("Content-Type", "application/json")
	topupReq.Header.Set("Authorization", "Bearer "+tokenA)
	topupReq.Header.Set("Idempotency-Key", "test-topup-userA")
	topupW := httptest.NewRecorder()
	r.ServeHTTP(topupW, topupReq)
	assert.Equal(t, http.StatusOK, topupW.Code)

	// Transfer from walletA to walletB - 10,000 VND = 1000000 cents
	transferBody := `{"from_user_id":"userA","to_user_id":"userB","amount":1000000}`
	transferReq := httptest.NewRequest("POST", "/api/wallet/transfer", strings.NewReader(transferBody))
	transferReq.Header.Set("Content-Type", "application/json")
	transferReq.Header.Set("Authorization", "Bearer "+tokenA)
	transferReq.Header.Set("Idempotency-Key", "test-transfer-key")
	transferW := httptest.NewRecorder()
	r.ServeHTTP(transferW, transferReq)
	assert.Equal(t, http.StatusOK, transferW.Code)

	var transferResp struct {
		Transaction wm.WalletTransaction `json:"transaction"`
	}
	if err := json.Unmarshal(transferW.Body.Bytes(), &transferResp); err != nil {
		t.Fatalf("failed to parse transfer response: %v", err)
	}
	txn := transferResp.Transaction
	assert.Equal(t, walletA.ID, txn.WalletID)
	assert.Equal(t, "transfer_out", txn.Type)
	assert.Equal(t, int64(-1000000), txn.Amount) // negative for outgoing
	assert.Equal(t, "completed", txn.Status)
	assert.NotZero(t, txn.ID)
	assert.NotZero(t, txn.BalanceAfter)
	assert.GreaterOrEqual(t, txn.BalanceAfter, int64(2000000))
}