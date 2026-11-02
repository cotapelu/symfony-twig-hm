package banking

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

	bb "community.local/modules/Banking/handler"
	bm "community.local/modules/Banking/internal/models"
)

func getDB(t *testing.T) *sql.DB {
	dsn := os.Getenv("INTEGRATION_TEST_DATABASE_URL")
	if dsn == "" {
		t.Skip("INTEGRATION_TEST_DATABASE_URL not set, skipping integration tests")
	}
	// Ensure SSL mode disabled for local test DB
	if !strings.Contains(dsn, "sslmode") {
		dsn += "?sslmode=disable"
	}
	db, err := sql.Open("postgres", dsn)
	if err != nil {
		t.Fatalf("failed to open database: %v", err)
	}
	ctx, cancel := context.WithTimeout(context.Background(), 10_000_000_000) // 10s
	defer cancel()
	if err := db.PingContext(ctx); err != nil {
		t.Fatalf("failed to ping database: %v", err)
	}
	return db
}

func createBankingSchema(db *sql.DB) error {
	// banking_accounts
	_, err := db.Exec(`
		CREATE TABLE IF NOT EXISTS banking_accounts (
			id SERIAL PRIMARY KEY,
			user_id VARCHAR(255) NOT NULL,
			account_number VARCHAR(255) UNIQUE NOT NULL,
			account_type VARCHAR(50) NOT NULL,
			balance NUMERIC NOT NULL DEFAULT 0,
			currency VARCHAR(3) NOT NULL DEFAULT 'VND',
			status VARCHAR(50) NOT NULL DEFAULT 'active',
			created_at TIMESTAMP NOT NULL DEFAULT NOW(),
			updated_at TIMESTAMP NOT NULL DEFAULT NOW()
		);
	`)
	if err != nil {
		return err
	}
	// banking_transactions
	_, err = db.Exec(`
		CREATE TABLE IF NOT EXISTS banking_transactions (
			id SERIAL PRIMARY KEY,
			account_id INT NOT NULL REFERENCES banking_accounts(id),
			type VARCHAR(50) NOT NULL,
			amount NUMERIC NOT NULL,
			balance_after NUMERIC NOT NULL,
			description TEXT,
			reference_id VARCHAR(255) UNIQUE,
			status VARCHAR(50) NOT NULL,
			created_at TIMESTAMP NOT NULL DEFAULT NOW()
		);
	`)
	return err
}

func truncateBankingTables(db *sql.DB) error {
	_, err := db.Exec(`TRUNCATE banking_transactions, banking_accounts RESTART IDENTITY CASCADE;`)
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

func TestBanking_CreateAccountAndGet(t *testing.T) {
	db := getDB(t)
	defer db.Close()

	// Setup schema and clean
	if err := createBankingSchema(db); err != nil {
		t.Fatalf("schema creation failed: %v", err)
	}
	if err := truncateBankingTables(db); err != nil {
		t.Fatalf("truncate failed: %v", err)
	}

	// Setup service and handler
	svc := bm.NewBankingService(db, nil)
	handler := bb.NewBankingHandler(svc)

	// Setup router with auth middleware
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

	// Create test token for user
	token, err := createTestToken("testuser1", []string{})
	if err != nil {
		t.Fatalf("failed to create test token: %v", err)
	}

	// Create account via POST
	reqBody := `{"user_id":"testuser1","account_type":"savings","currency":"VND"}`
	req := httptest.NewRequest("POST", "/api/banking/accounts", strings.NewReader(reqBody))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Authorization", "Bearer "+token)
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusCreated, w.Code)

	// Parse response
	var resp struct {
		Account bm.Account `json:"account"`
	}
	if err := json.Unmarshal(w.Body.Bytes(), &resp); err != nil {
		t.Fatalf("failed to parse response: %v", err)
	}
	account := resp.Account
	assert.Equal(t, "testuser1", account.UserID)
	assert.Equal(t, "savings", account.AccountType)
	assert.Equal(t, "VND", account.Currency)
	assert.Equal(t, float64(0), account.Balance)
	assert.Equal(t, "active", account.Status)
	assert.NotZero(t, account.ID)
	assert.NotNil(t, account.CreatedAt)

	// Get account by ID
	accID := account.ID
	getReq := httptest.NewRequest("GET", fmt.Sprintf("/api/banking/accounts/%d", accID), nil)
	getReq.Header.Set("Authorization", "Bearer "+token)
	getW := httptest.NewRecorder()
	r.ServeHTTP(getW, getReq)
	assert.Equal(t, http.StatusOK, getW.Code)
	var getResp struct {
		Account bm.Account `json:"account"`
	}
	if err := json.Unmarshal(getW.Body.Bytes(), &getResp); err != nil {
		t.Fatalf("failed to parse get response: %v", err)
	}
	assert.Equal(t, account.ID, getResp.Account.ID)
	assert.Equal(t, account.AccountNumber, getResp.Account.AccountNumber)
}

func TestBanking_Deposit(t *testing.T) {
	db := getDB(t)
	defer db.Close()

	if err := createBankingSchema(db); err != nil {
		t.Fatalf("schema creation failed: %v", err)
	}
	if err := truncateBankingTables(db); err != nil {
		t.Fatalf("truncate failed: %v", err)
	}

	svc := bm.NewBankingService(db, nil)
	handler := bb.NewBankingHandler(svc)
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
	token, err := createTestToken("testuser2", []string{})
	if err != nil {
		t.Fatalf("failed to create test token: %v", err)
	}

	// Create an account via POST
	accBody := `{"user_id":"testuser2","account_type":"savings","currency":"VND"}`
	accReq := httptest.NewRequest("POST", "/api/banking/accounts", strings.NewReader(accBody))
	accReq.Header.Set("Content-Type", "application/json")
	accReq.Header.Set("Authorization", "Bearer "+token)
	accW := httptest.NewRecorder()
	r.ServeHTTP(accW, accReq)
	assert.Equal(t, http.StatusCreated, accW.Code)
	var accResp struct{ Account bm.Account }
	json.Unmarshal(accW.Body.Bytes(), &accResp)
	accountID := accResp.Account.ID

	// Deposit
	depositBody := fmt.Sprintf(`{"account_id":%d,"amount":1000000.0,"description":"Test deposit"}`, accountID)
	depReq := httptest.NewRequest("POST", "/api/banking/deposit", strings.NewReader(depositBody))
	depReq.Header.Set("Content-Type", "application/json")
	depReq.Header.Set("Authorization", "Bearer "+token)
	depReq.Header.Set("Idempotency-Key", "test-deposit-key")
	depW := httptest.NewRecorder()
	r.ServeHTTP(depW, depReq)
	assert.Equal(t, http.StatusOK, depW.Code)

	var depResp struct {
		Transaction bm.Transaction `json:"transaction"`
	}
	if err := json.Unmarshal(depW.Body.Bytes(), &depResp); err != nil {
		t.Fatalf("failed to parse deposit response: %v", err)
	}
	txn := depResp.Transaction
	assert.Equal(t, accountID, txn.AccountID)
	assert.Equal(t, "deposit", txn.Type)
	assert.Equal(t, 1000000.0, txn.Amount)
	assert.Equal(t, "completed", txn.Status)
	assert.NotZero(t, txn.ID)
	assert.NotZero(t, txn.BalanceAfter)
}

func TestBanking_Withdraw(t *testing.T) {
	db := getDB(t)
	defer db.Close()

	if err := createBankingSchema(db); err != nil {
		t.Fatalf("schema creation failed: %v", err)
	}
	if err := truncateBankingTables(db); err != nil {
		t.Fatalf("truncate failed: %v", err)
	}

	svc := bm.NewBankingService(db, nil)
	handler := bb.NewBankingHandler(svc)
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
	token, err := createTestToken("testuser3", []string{})
	if err != nil {
		t.Fatalf("failed to create test token: %v", err)
	}

	// Create account
	accBody := `{"user_id":"testuser3","account_type":"savings","currency":"VND"}`
	accReq := httptest.NewRequest("POST", "/api/banking/accounts", strings.NewReader(accBody))
	accReq.Header.Set("Content-Type", "application/json")
	accReq.Header.Set("Authorization", "Bearer "+token)
	accW := httptest.NewRecorder()
	r.ServeHTTP(accW, accReq)
	assert.Equal(t, http.StatusCreated, accW.Code)
	var accResp struct{ Account bm.Account }
	json.Unmarshal(accW.Body.Bytes(), &accResp)
	accountID := accResp.Account.ID

	// Deposit first to have balance
	depositBody := fmt.Sprintf(`{"account_id":%d,"amount":2000000.0,"description":"Initial deposit"}`, accountID)
	depReq := httptest.NewRequest("POST", "/api/banking/deposit", strings.NewReader(depositBody))
	depReq.Header.Set("Content-Type", "application/json")
	depReq.Header.Set("Authorization", "Bearer "+token)
	depReq.Header.Set("Idempotency-Key", "test-initial-deposit")
	depW := httptest.NewRecorder()
	r.ServeHTTP(depW, depReq)
	assert.Equal(t, http.StatusOK, depW.Code)

	// Withdraw
	withdrawBody := fmt.Sprintf(`{"account_id":%d,"amount":500000.0,"description":"Test withdraw"}`, accountID)
	withdrawReq := httptest.NewRequest("POST", "/api/banking/withdraw", strings.NewReader(withdrawBody))
	withdrawReq.Header.Set("Content-Type", "application/json")
	withdrawReq.Header.Set("Authorization", "Bearer "+token)
	withdrawReq.Header.Set("Idempotency-Key", "test-withdraw-key")
	withdrawW := httptest.NewRecorder()
	r.ServeHTTP(withdrawW, withdrawReq)
	assert.Equal(t, http.StatusOK, withdrawW.Code)

	var withdrawResp struct {
		Transaction bm.Transaction `json:"transaction"`
	}
	if err := json.Unmarshal(withdrawW.Body.Bytes(), &withdrawResp); err != nil {
		t.Fatalf("failed to parse withdraw response: %v", err)
	}
	txn := withdrawResp.Transaction
	assert.Equal(t, accountID, txn.AccountID)
	assert.Equal(t, "withdraw", txn.Type)
	assert.Equal(t, -500000.0, txn.Amount) // withdraw is negative
	assert.Equal(t, "completed", txn.Status)
	assert.NotZero(t, txn.ID)
	assert.NotZero(t, txn.BalanceAfter)
	// Balance after should be initial 2M - 500k = 1.5M (assuming no other txs)
	assert.GreaterOrEqual(t, txn.BalanceAfter, 1500000.0)
}

func TestBanking_Transfer(t *testing.T) {
	db := getDB(t)
	defer db.Close()

	if err := createBankingSchema(db); err != nil {
		t.Fatalf("schema creation failed: %v", err)
	}
	if err := truncateBankingTables(db); err != nil {
		t.Fatalf("truncate failed: %v", err)
	}

	svc := bm.NewBankingService(db, nil)
	handler := bb.NewBankingHandler(svc)
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

	// Create two accounts
	acc1Body := `{"user_id":"userA","account_type":"savings","currency":"VND"}`
	acc1Req := httptest.NewRequest("POST", "/api/banking/accounts", strings.NewReader(acc1Body))
	acc1Req.Header.Set("Content-Type", "application/json")
	acc1Req.Header.Set("Authorization", "Bearer "+tokenA)
	acc1W := httptest.NewRecorder()
	r.ServeHTTP(acc1W, acc1Req)
	assert.Equal(t, http.StatusCreated, acc1W.Code)
	var acc1Resp struct{ Account bm.Account }
	json.Unmarshal(acc1W.Body.Bytes(), &acc1Resp)
	userA := acc1Resp.Account

	acc2Body := `{"user_id":"userB","account_type":"savings","currency":"VND"}`
	acc2Req := httptest.NewRequest("POST", "/api/banking/accounts", strings.NewReader(acc2Body))
	acc2Req.Header.Set("Content-Type", "application/json")
	acc2Req.Header.Set("Authorization", "Bearer "+tokenB)
	acc2W := httptest.NewRecorder()
	r.ServeHTTP(acc2W, acc2Req)
	assert.Equal(t, http.StatusCreated, acc2W.Code)
	var acc2Resp struct{ Account bm.Account }
	json.Unmarshal(acc2W.Body.Bytes(), &acc2Resp)
	userB := acc2Resp.Account

	// Deposit to userA
	depositBody := fmt.Sprintf(`{"account_id":%d,"amount":3000000.0,"description":"Initial deposit"}`, userA.ID)
	depReq := httptest.NewRequest("POST", "/api/banking/deposit", strings.NewReader(depositBody))
	depReq.Header.Set("Content-Type", "application/json")
	depReq.Header.Set("Authorization", "Bearer "+tokenA)
	depReq.Header.Set("Idempotency-Key", "test-deposit-userA")
	depW := httptest.NewRecorder()
	r.ServeHTTP(depW, depReq)
	assert.Equal(t, http.StatusOK, depW.Code)

	// Transfer from userA to userB (using userA's token since they own the source account)
	transferBody := fmt.Sprintf(`{"from_account":"%s","to_account":"%s","amount":1000000.0,"description":"Test transfer"}`, userA.AccountNumber, userB.AccountNumber)
	transferReq := httptest.NewRequest("POST", "/api/banking/transfer", strings.NewReader(transferBody))
	transferReq.Header.Set("Content-Type", "application/json")
	transferReq.Header.Set("Authorization", "Bearer "+tokenA)
	transferReq.Header.Set("Idempotency-Key", "test-transfer-key")
	transferW := httptest.NewRecorder()
	r.ServeHTTP(transferW, transferReq)
	assert.Equal(t, http.StatusOK, transferW.Code)

	var transferResp struct {
		Transaction bm.Transaction `json:"transaction"`
	}
	if err := json.Unmarshal(transferW.Body.Bytes(), &transferResp); err != nil {
		t.Fatalf("failed to parse transfer response: %v", err)
	}
	txn := transferResp.Transaction
	assert.Equal(t, userA.ID, txn.AccountID)
	assert.Equal(t, "transfer", txn.Type)
	assert.Equal(t, -1000000.0, txn.Amount)
	assert.Equal(t, "completed", txn.Status)
	assert.NotZero(t, txn.ID)
	assert.NotZero(t, txn.BalanceAfter)
	// Balance after should be ~2M
	assert.GreaterOrEqual(t, txn.BalanceAfter, 2000000.0)
}