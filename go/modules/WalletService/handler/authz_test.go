package handler

import (
	"context"
	"database/sql"
	"net/http"
	"net/http/httptest"
	"strings"
	"testing"

	walletmodels "community.local/modules/WalletService/internal/models"
	walletservice "community.local/modules/WalletService/service"
	"community.local/testing/helper"

	"github.com/gin-gonic/gin"
	"github.com/stretchr/testify/assert"
)

func setupWalletSQLiteSchema(t *testing.T, db *sql.DB) {
	t.Helper()
	_, _ = db.ExecContext(context.Background(), `PRAGMA foreign_keys = ON`)
	_, _ = db.ExecContext(context.Background(), `CREATE TABLE wallets (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id TEXT NOT NULL, balance INTEGER NOT NULL DEFAULT 0, currency TEXT NOT NULL, status TEXT NOT NULL DEFAULT 'active', is_default BOOLEAN NOT NULL DEFAULT false, version INTEGER NOT NULL DEFAULT 1, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP);`)
	_, _ = db.ExecContext(context.Background(), `CREATE TABLE wallet_transactions (id INTEGER PRIMARY KEY AUTOINCREMENT, wallet_id INTEGER NOT NULL, type TEXT NOT NULL, amount INTEGER NOT NULL, balance_after INTEGER NOT NULL, description TEXT, reference_id TEXT, metadata TEXT, status TEXT NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP);`)
}

// authMiddleware creates a test middleware that sets up JWT authentication
func authMiddleware() gin.HandlerFunc {
	return func(c *gin.Context) {
		// Check for X-User-ID header and create JWT token if present
		if uid := c.GetHeader("X-User-ID"); uid != "" {
			roles := []string{}
			if roleHeader := c.GetHeader("X-Roles"); roleHeader != "" {
				roles = strings.Split(roleHeader, ",")
			}
			token := createTestToken(uid, roles)
			c.Request.Header.Set("Authorization", "Bearer "+token)
		}
		c.Next()
	}
}

// TestGetWallet_OwnershipCheck tests GET /api/wallet/:user_id ownership
func TestGetWallet_OwnershipCheck(t *testing.T) {
	gin.SetMode(gin.TestMode)
	db := helper.SetupTestDB(t)
	defer db.Close()
	setupWalletSQLiteSchema(t, db)
	repo := walletmodels.NewWalletRepository(db)
	txnRepo := walletmodels.NewTransactionRepository(db)
	svc := walletservice.NewWalletService(repo, txnRepo, nil)
	h := NewWalletHandler(svc)

	router := gin.New()
	router.Use(authMiddleware())
	h.RegisterRoutes(router)

	// Insert a wallet for user "alice"
	_, err := db.ExecContext(context.Background(), `INSERT INTO wallets (user_id, balance, currency, status, is_default, version) VALUES ($1, $2, $3, $4, $5, $6)`,
		"alice", 100000, "VND", "active", true, 1)
	if err != nil {
		t.Fatalf("insert wallet failed: %v", err)
	}

	// Request as alice -> should succeed (200)
	reqAlice := httptest.NewRequest("GET", "/api/wallet/alice", nil)
	reqAlice.Header.Set("X-User-ID", "alice")
	wAlice := httptest.NewRecorder()
	router.ServeHTTP(wAlice, reqAlice)
	assert.Equal(t, http.StatusOK, wAlice.Code, "alice should get own wallet")

	// Request as bob (different user) -> should be 403
	reqBob := httptest.NewRequest("GET", "/api/wallet/alice", nil)
	reqBob.Header.Set("X-User-ID", "bob")
	wBob := httptest.NewRecorder()
	router.ServeHTTP(wBob, reqBob)
	assert.Equal(t, http.StatusForbidden, wBob.Code, "bob should be forbidden")
}

// TestTopUp_RequiresOwnUser tests that TopUp requires auth and matches user_id from request body to authenticated user
func TestTopUp_RequiresOwnUser(t *testing.T) {
	gin.SetMode(gin.TestMode)
	db := helper.SetupTestDB(t)
	defer db.Close()
	setupWalletSQLiteSchema(t, db)
	repo := walletmodels.NewWalletRepository(db)
	txnRepo := walletmodels.NewTransactionRepository(db)
	svc := walletservice.NewWalletService(repo, txnRepo, nil)
	h := NewWalletHandler(svc)

	router := gin.New()
	router.Use(authMiddleware())
	h.RegisterRoutes(router)

	// Create a wallet for user "charlie"
	_, err := db.ExecContext(context.Background(), `INSERT INTO wallets (user_id, balance, currency, status, is_default, version) VALUES ($1, $2, $3, $4, $5, $6)`,
		"charlie", 50000, "VND", "active", true, 1)
	if err != nil {
		t.Fatalf("insert wallet failed: %v", err)
	}

	// TopUp as charlie with correct user_id should succeed
	body := `{"user_id":"charlie","amount":50000,"method":"bank_transfer"}`
	req := httptest.NewRequest("POST", "/api/wallet/topup", strings.NewReader(body))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("X-User-ID", "charlie")
	req.Header.Set("Idempotency-Key", "key-123")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)
	assert.Equal(t, http.StatusOK, w.Code, "charlie can top up own wallet")

	// TopUp as dave with charlie's user_id should be forbidden
	req2 := httptest.NewRequest("POST", "/api/wallet/topup", strings.NewReader(body))
	req2.Header.Set("Content-Type", "application/json")
	req2.Header.Set("X-User-ID", "dave")
	req2.Header.Set("Idempotency-Key", "key-456")
	w2 := httptest.NewRecorder()
	router.ServeHTTP(w2, req2)
	assert.Equal(t, http.StatusForbidden, w2.Code, "dave cannot top up charlie's wallet")
}

// TestTransfer_RequiresOwnFromUser tests that Transfer only allows from_user_id matching auth user (unless admin)
func TestTransfer_RequiresOwnFromUser(t *testing.T) {
	gin.SetMode(gin.TestMode)
	db := helper.SetupTestDB(t)
	defer db.Close()
	setupWalletSQLiteSchema(t, db)
	repo := walletmodels.NewWalletRepository(db)
	txnRepo := walletmodels.NewTransactionRepository(db)
	svc := walletservice.NewWalletService(repo, txnRepo, nil)
	h := NewWalletHandler(svc)

	router := gin.New()
	router.Use(authMiddleware())
	h.RegisterRoutes(router)

	// Create wallets for eve and frank
	_, err := db.ExecContext(context.Background(), `INSERT INTO wallets (user_id, balance, currency, status, is_default, version) VALUES ($1, $2, $3, $4, $5, $6)`,
		"eve", 100000, "VND", "active", true, 1)
	if err != nil {
		t.Fatalf("insert eve wallet failed: %v", err)
	}
	_, err = db.ExecContext(context.Background(), `INSERT INTO wallets (user_id, balance, currency, status, is_default, version) VALUES ($1, $2, $3, $4, $5, $6)`,
		"frank", 50000, "VND", "active", true, 1)
	if err != nil {
		t.Fatalf("insert frank wallet failed: %v", err)
	}

	// Transfer from eve to frank as eve -> should succeed (200)
	transferBody := `{"from_user_id":"eve","to_user_id":"frank","amount":10000,"description":"test transfer"}`
	reqEve := httptest.NewRequest("POST", "/api/wallet/transfer", strings.NewReader(transferBody))
	reqEve.Header.Set("Content-Type", "application/json")
	reqEve.Header.Set("X-User-ID", "eve")
	reqEve.Header.Set("Idempotency-Key", "transfer-key-1")
	wEve := httptest.NewRecorder()
	router.ServeHTTP(wEve, reqEve)
	if wEve.Code != http.StatusOK {
		t.Logf("Transfer as eve failed: %s", wEve.Body.String())
	}
	assert.Equal(t, http.StatusOK, wEve.Code, "eve can transfer from own wallet")

	// Transfer from eve to frank as frank (frank tries to use from_user_id=eve) -> should be 403
	reqFrank := httptest.NewRequest("POST", "/api/wallet/transfer", strings.NewReader(transferBody))
	reqFrank.Header.Set("Content-Type", "application/json")
	reqFrank.Header.Set("X-User-ID", "frank")
	reqFrank.Header.Set("Idempotency-Key", "transfer-key-2")
	wFrank := httptest.NewRecorder()
	router.ServeHTTP(wFrank, reqFrank)
	assert.Equal(t, http.StatusForbidden, wFrank.Code, "frank cannot impersonate eve as from_user_id")
}

// TestWithdraw_RequiresOwnUser tests that Withdraw only allows withdrawing from own wallet
func TestWithdraw_RequiresOwnUser(t *testing.T) {
	gin.SetMode(gin.TestMode)
	db := helper.SetupTestDB(t)
	defer db.Close()
	setupWalletSQLiteSchema(t, db)
	repo := walletmodels.NewWalletRepository(db)
	txnRepo := walletmodels.NewTransactionRepository(db)
	svc := walletservice.NewWalletService(repo, txnRepo, nil)
	h := NewWalletHandler(svc)

	router := gin.New()
	router.Use(authMiddleware())
	h.RegisterRoutes(router)

	// Insert wallet for grace
	_, err := db.ExecContext(context.Background(), `INSERT INTO wallets (user_id, balance, currency, status, is_default, version) VALUES ($1, $2, $3, $4, $5, $6)`,
		"grace", 200000, "VND", "active", true, 1)
	if err != nil {
		t.Fatalf("insert grace wallet failed: %v", err)
	}

	// Withdraw as grace -> should succeed
	withdrawBody := `{"user_id":"grace","amount":10000,"method":"bank_transfer","bank_code":"VCB","account_no":"123456","holder_name":"Grace"}`
	reqGrace := httptest.NewRequest("POST", "/api/wallet/withdraw", strings.NewReader(withdrawBody))
	reqGrace.Header.Set("Content-Type", "application/json")
	reqGrace.Header.Set("X-User-ID", "grace")
	reqGrace.Header.Set("Idempotency-Key", "withdraw-key-1")
	wGrace := httptest.NewRecorder()
	router.ServeHTTP(wGrace, reqGrace)
	if wGrace.Code != http.StatusOK {
		t.Logf("Withdraw as grace failed: %s", wGrace.Body.String())
	}
	assert.Equal(t, http.StatusOK, wGrace.Code, "grace can withdraw from own wallet")

	// Withdraw as heidi with grace's user_id -> 403
	reqHeidi := httptest.NewRequest("POST", "/api/wallet/withdraw", strings.NewReader(withdrawBody))
	reqHeidi.Header.Set("Content-Type", "application/json")
	reqHeidi.Header.Set("X-User-ID", "heidi")
	reqHeidi.Header.Set("Idempotency-Key", "withdraw-key-2")
	wHeidi := httptest.NewRecorder()
	router.ServeHTTP(wHeidi, reqHeidi)
	assert.Equal(t, http.StatusForbidden, wHeidi.Code, "heidi cannot withdraw from grace's wallet")
}

// TestDeleteWallet_OwnershipCheck tests DELETE /api/wallet/:user_id
func TestDeleteWallet_OwnershipCheck(t *testing.T) {
	gin.SetMode(gin.TestMode)
	db := helper.SetupTestDB(t)
	defer db.Close()
	setupWalletSQLiteSchema(t, db)
	repo := walletmodels.NewWalletRepository(db)
	txnRepo := walletmodels.NewTransactionRepository(db)
	svc := walletservice.NewWalletService(repo, txnRepo, nil)
	h := NewWalletHandler(svc)

	router := gin.New()
	router.Use(authMiddleware())
	h.RegisterRoutes(router)

	// Insert wallet for ivan
	_, err := db.ExecContext(context.Background(), `INSERT INTO wallets (user_id, balance, currency, status, is_default, version) VALUES ($1, $2, $3, $4, $5, $6)`,
		"ivan", 0, "VND", "active", true, 1)
	if err != nil {
		t.Fatalf("insert ivan wallet failed: %v", err)
	}

	// Delete as ivan -> 200 OK
	reqIvan := httptest.NewRequest("DELETE", "/api/wallet/ivan", nil)
	reqIvan.Header.Set("X-User-ID", "ivan")
	wIvan := httptest.NewRecorder()
	router.ServeHTTP(wIvan, reqIvan)
	if wIvan.Code != http.StatusOK {
		t.Logf("Delete as ivan failed: %s", wIvan.Body.String())
	}
	assert.Equal(t, http.StatusOK, wIvan.Code, "ivan can delete own wallet")

	// After deletion, recreate wallet for judy and test judy cannot delete ivan's wallet
	_, _ = db.ExecContext(context.Background(), `INSERT INTO wallets (user_id, balance, currency, status, is_default, version) VALUES ($1, $2, $3, $4, $5, $6)`,
		"judy", 50000, "VND", "active", true, 1)

	reqJudyl := httptest.NewRequest("DELETE", "/api/wallet/ivan", nil)
	reqJudyl.Header.Set("X-User-ID", "judy")
	wJudyl := httptest.NewRecorder()
	router.ServeHTTP(wJudyl, reqJudyl)
	assert.Equal(t, http.StatusForbidden, wJudyl.Code, "judy cannot delete ivan's wallet")
}

// TestLockWallet_RequiresAdmin tests that PATCH /api/wallet/:user_id/lock requires admin
func TestLockWallet_RequiresAdmin(t *testing.T) {
	gin.SetMode(gin.TestMode)
	db := helper.SetupTestDB(t)
	defer db.Close()
	setupWalletSQLiteSchema(t, db)
	repo := walletmodels.NewWalletRepository(db)
	txnRepo := walletmodels.NewTransactionRepository(db)
	svc := walletservice.NewWalletService(repo, txnRepo, nil)
	h := NewWalletHandler(svc)

	router := gin.New()
	router.Use(authMiddleware())
	h.RegisterRoutes(router)

	// Insert wallet for kate
	_, err := db.ExecContext(context.Background(), `INSERT INTO wallets (user_id, balance, currency, status, is_default, version) VALUES ($1, $2, $3, $4, $5, $6)`,
		"kate", 100000, "VND", "active", true, 1)
	if err != nil {
		t.Fatalf("insert kate wallet failed: %v", err)
	}

	// Lock as non-admin user -> should be forbidden
	lockBody := `{"reason":"suspicious activity"}`
	reqUser := httptest.NewRequest("PATCH", "/api/wallet/kate/lock", strings.NewReader(lockBody))
	reqUser.Header.Set("Content-Type", "application/json")
	reqUser.Header.Set("X-User-ID", "leo")
	wUser := httptest.NewRecorder()
	router.ServeHTTP(wUser, reqUser)
	assert.Equal(t, http.StatusForbidden, wUser.Code, "non-admin cannot lock wallet")

	// Lock as admin user -> should succeed (200)
	reqAdmin := httptest.NewRequest("PATCH", "/api/wallet/kate/lock", strings.NewReader(lockBody))
	reqAdmin.Header.Set("Content-Type", "application/json")
	reqAdmin.Header.Set("X-User-ID", "admin")
	reqAdmin.Header.Set("X-Roles", "admin")
	wAdmin := httptest.NewRecorder()
	router.ServeHTTP(wAdmin, reqAdmin)
	assert.Equal(t, http.StatusOK, wAdmin.Code, "admin can lock wallet")
}

// TestGetBalance_OwnershipCheck tests GET /api/wallet/:user_id/balance
func TestGetBalance_OwnershipCheck(t *testing.T) {
	gin.SetMode(gin.TestMode)
	db := helper.SetupTestDB(t)
	defer db.Close()
	setupWalletSQLiteSchema(t, db)
	repo := walletmodels.NewWalletRepository(db)
	txnRepo := walletmodels.NewTransactionRepository(db)
	svc := walletservice.NewWalletService(repo, txnRepo, nil)
	h := NewWalletHandler(svc)

	router := gin.New()
	router.Use(authMiddleware())
	h.RegisterRoutes(router)

	// Insert wallet for mary
	_, err := db.ExecContext(context.Background(), `INSERT INTO wallets (user_id, balance, currency, status, is_default, version) VALUES ($1, $2, $3, $4, $5, $6)`,
		"mary", 250000, "VND", "active", true, 1)
	if err != nil {
		t.Fatalf("insert mary wallet failed: %v", err)
	}

	// Get balance as mary -> 200
	reqMary := httptest.NewRequest("GET", "/api/wallet/mary/balance", nil)
	reqMary.Header.Set("X-User-ID", "mary")
	wMary := httptest.NewRecorder()
	router.ServeHTTP(wMary, reqMary)
	assert.Equal(t, http.StatusOK, wMary.Code, "mary can get own balance")

	// Get balance as nancy (different user) -> 403
	reqNancy := httptest.NewRequest("GET", "/api/wallet/mary/balance", nil)
	reqNancy.Header.Set("X-User-ID", "nancy")
	wNancy := httptest.NewRecorder()
	router.ServeHTTP(wNancy, reqNancy)
	assert.Equal(t, http.StatusForbidden, wNancy.Code, "nancy cannot get mary's balance")
}