package handler

import (
	"context"
	"database/sql"
	"encoding/json"
	"fmt"
	"net/http"
	"net/http/httptest"
	"strings"
	"testing"

	_ "github.com/mattn/go-sqlite3"

	"github.com/gin-gonic/gin"
	"github.com/stretchr/testify/assert"

	bpmodels "community.local/modules/BillsPayment/internal/models"
	"community.local/testing/helper"
)

func setupBillsSQLiteSchema(t *testing.T, db *sql.DB) {
	t.Helper()
	_, _ = db.Exec("PRAGMA foreign_keys = ON")

	// providers
	_, err := db.Exec(`
		CREATE TABLE bill_providers (
			id INTEGER PRIMARY KEY AUTOINCREMENT,
			name TEXT NOT NULL,
			code TEXT UNIQUE NOT NULL,
			category TEXT NOT NULL,
			processing_fee REAL NOT NULL,
			min_amount REAL NOT NULL,
			max_amount REAL NOT NULL,
			is_active BOOLEAN NOT NULL DEFAULT true
		);
	`)
	if err != nil {
		t.Fatalf("create providers failed: %v", err)
	}

	// bills
	_, err = db.Exec(`
		CREATE TABLE bills (
			id INTEGER PRIMARY KEY AUTOINCREMENT,
			bill_type TEXT NOT NULL,
			provider TEXT NOT NULL,
			account_number TEXT NOT NULL,
			customer_name TEXT NOT NULL,
			amount REAL NOT NULL,
			period TEXT,
			status TEXT NOT NULL DEFAULT 'unpaid',
			due_date DATETIME,
			paid_at DATETIME,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
		);
	`)
	if err != nil {
		t.Fatalf("create bills failed: %v", err)
	}

	// bill_payments
	_, err = db.Exec(`
		CREATE TABLE bill_payments (
			id INTEGER PRIMARY KEY AUTOINCREMENT,
			bill_id INTEGER NOT NULL REFERENCES bills(id),
			user_id TEXT NOT NULL,
			amount REAL NOT NULL,
			payment_method TEXT NOT NULL,
			transaction_id TEXT,
			status TEXT NOT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
		);
	`)
	if err != nil {
		t.Fatalf("create payments failed: %v", err)
	}
}

func TestBillsPaymentHandler_CreateBill(t *testing.T) {
	gin.SetMode(gin.TestMode)

	db := helper.SetupTestDB(t)
	defer db.Close()
	setupBillsSQLiteSchema(t, db)

	svc := bpmodels.NewBillsPaymentService(db, nil)
	h := NewBillsPaymentHandler(svc)
	r := gin.New()
	// CreateBill requires auth
	r.Use(func(c *gin.Context) {
		if uid := c.GetHeader("X-User-ID"); uid != "" {
			token := createTestToken(uid, nil)
			c.Request.Header.Set("Authorization", "Bearer "+token)
		}
		c.Next()
	})
	h.RegisterRoutes(r)

	// Prepare a due date: format "2006-01-02"
	body := `{"bill_type":"electricity","provider":"ElectricCo","account_number":"ACC-001","customer_name":"John","amount":250000,"period":"2025-01","due_date":"2025-01-31"}`
	req := httptest.NewRequest("POST", "/api/bills", strings.NewReader(body))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("X-User-ID", "testuser")
	w := httptest.NewRecorder()

	r.ServeHTTP(w, req)
	assert.Equal(t, http.StatusCreated, w.Code)

	var resp struct{ Bill bpmodels.Bill }
	assert.NoError(t, json.Unmarshal(w.Body.Bytes(), &resp))
	assert.Equal(t, "electricity", resp.Bill.BillType)
	assert.Equal(t, "ElectricCo", resp.Bill.Provider)
	assert.Equal(t, "unpaid", resp.Bill.Status)
	assert.NotZero(t, resp.Bill.ID)
}

func TestBillsPaymentHandler_GetBill(t *testing.T) {
	gin.SetMode(gin.TestMode)

	db := helper.SetupTestDB(t)
	defer db.Close()
	setupBillsSQLiteSchema(t, db)

	svc := bpmodels.NewBillsPaymentService(db, nil)
	h := NewBillsPaymentHandler(svc)
	r := gin.New()
	// GetBill requires auth
	r.Use(func(c *gin.Context) {
		if uid := c.GetHeader("X-User-ID"); uid != "" {
			token := createTestToken(uid, nil)
			c.Request.Header.Set("Authorization", "Bearer "+token)
		}
		c.Next()
	})
	h.RegisterRoutes(r)

	// Insert a bill directly to avoid service call
	_, err := db.Exec(`INSERT INTO bills (bill_type, provider, account_number, customer_name, amount, period, status, due_date) VALUES ($1, $2, $3, $4, $5, $6, $7, $8)`,
		"internet", "NetCo", "ACC-2", "Alice", 150000, "2025-02", "unpaid", "2025-02-15")
	if err != nil {
		t.Fatalf("insert bill failed: %v", err)
	}
	var billID int64
	err = db.QueryRowContext(context.Background(), `SELECT last_insert_rowid()`).Scan(&billID)
	if err != nil {
		t.Fatalf("get last bill id failed: %v", err)
	}

	// GET /api/bills/:id
	req := httptest.NewRequest("GET", "/api/bills/"+fmt.Sprint(billID), nil)
	req.Header.Set("X-User-ID", "testuser")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)
	assert.Equal(t, http.StatusOK, w.Code)

	var resp struct{ Bill bpmodels.Bill }
	assert.NoError(t, json.Unmarshal(w.Body.Bytes(), &resp))
	assert.Equal(t, billID, resp.Bill.ID)
	assert.Equal(t, "internet", resp.Bill.BillType)
}

func TestBillsPaymentHandler_PayBill(t *testing.T) {
	gin.SetMode(gin.TestMode)

	db := helper.SetupTestDB(t)
	defer db.Close()
	setupBillsSQLiteSchema(t, db)

	svc := bpmodels.NewBillsPaymentService(db, nil)
	h := NewBillsPaymentHandler(svc)

	// Auth middleware to create JWT token from X-User-ID header
	authMiddleware := func() gin.HandlerFunc {
		return func(c *gin.Context) {
			if uid := c.GetHeader("X-User-ID"); uid != "" {
				token := createTestToken(uid, nil)
				c.Request.Header.Set("Authorization", "Bearer "+token)
			}
			c.Next()
		}
	}

	r := gin.New()
	r.Use(authMiddleware())
	h.RegisterRoutes(r)

	// Insert a provider and a bill directly (status=unpaid)
	_, err := db.Exec(`INSERT INTO bill_providers (name, code, category, processing_fee, min_amount, max_amount, is_active) VALUES ($1,$2,$3,$4,$5,$6,$7)`,
		"Electricity", "ELEC", "utility", 1000.0, 0, 1000000.0, true)
	if err != nil {
		t.Fatalf("insert provider failed: %v", err)
	}
	_, err = db.Exec(`INSERT INTO bills (bill_type, provider, account_number, customer_name, amount, period, status, due_date) VALUES ($1,$2,$3,$4,$5,$6,$7,$8)`,
		"electricity", "Electricity", "ACC-123", "John", 500000, "2025-01", "unpaid", "2025-01-31")
	if err != nil {
		t.Fatalf("insert bill failed: %v", err)
	}
	var billID int64
	err = db.QueryRowContext(context.Background(), `SELECT last_insert_rowid()`).Scan(&billID)
	if err != nil {
		t.Fatalf("get bill id failed: %v", err)
	}

	// Pay the bill
	payBody := `{"user_id":"user123","payment_method":"bank_transfer"}`
	payReq := httptest.NewRequest("POST", "/api/bills/"+fmt.Sprint(billID)+"/pay", strings.NewReader(payBody))
	payReq.Header.Set("Content-Type", "application/json")
	payReq.Header.Set("X-User-ID", "user123")
	payReq.Header.Set("Idempotency-Key", "test-key-123")
	payW := httptest.NewRecorder()
	r.ServeHTTP(payW, payReq)
	assert.Equal(t, http.StatusOK, payW.Code)

	var payResp struct {
		Payment bpmodels.BillPayment
		Message string
	}
	assert.NoError(t, json.Unmarshal(payW.Body.Bytes(), &payResp))
	assert.Equal(t, billID, payResp.Payment.BillID)
	assert.Equal(t, "completed", payResp.Payment.Status)

	// Get bill to verify status paid
	getReq := httptest.NewRequest("GET", "/api/bills/"+fmt.Sprint(billID), nil)
	getReq.Header.Set("X-User-ID", "testuser")
	getW := httptest.NewRecorder()
	r.ServeHTTP(getW, getReq)
	assert.Equal(t, http.StatusOK, getW.Code)

	var getResp struct{ Bill bpmodels.Bill }
	assert.NoError(t, json.Unmarshal(getW.Body.Bytes(), &getResp))
	assert.Equal(t, "paid", getResp.Bill.Status)
	assert.NotNil(t, getResp.Bill.PaidAt)
}