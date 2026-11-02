package billspayment

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

	bh "community.local/modules/BillsPayment/handler"
	bm "community.local/modules/BillsPayment/internal/models"
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

func createBillsSchema(db *sql.DB) error {
	// Drop existing tables to ensure clean schema (CASCADE handles dependencies)
	_, _ = db.Exec(`DROP TABLE IF EXISTS bill_payments CASCADE;`)
	_, _ = db.Exec(`DROP TABLE IF EXISTS bills CASCADE;`)
	_, _ = db.Exec(`DROP TABLE IF EXISTS bill_providers CASCADE;`)
	// bill_providers
	_, err := db.Exec(`
		CREATE TABLE bill_providers (
			id SERIAL PRIMARY KEY,
			name VARCHAR(255) NOT NULL,
			code VARCHAR(100) UNIQUE NOT NULL,
			category VARCHAR(100) NOT NULL,
			processing_fee NUMERIC NOT NULL,
			min_amount NUMERIC NOT NULL,
			max_amount NUMERIC NOT NULL,
			is_active BOOLEAN NOT NULL DEFAULT true
		);
	`)
	if err != nil {
		return err
	}
	// bills
	_, err = db.Exec(`
		CREATE TABLE bills (
			id SERIAL PRIMARY KEY,
			bill_type VARCHAR(100) NOT NULL,
			provider VARCHAR(255) NOT NULL,
			account_number VARCHAR(255) NOT NULL,
			customer_name VARCHAR(255) NOT NULL,
			amount NUMERIC NOT NULL,
			period VARCHAR(50),
			status VARCHAR(50) NOT NULL DEFAULT 'pending',
			due_date TIMESTAMP,
			paid_at TIMESTAMP,
			created_at TIMESTAMP NOT NULL DEFAULT NOW()
		);
	`)
	if err != nil {
		return err
	}
	// bill_payments
	_, err = db.Exec(`
		CREATE TABLE bill_payments (
			id SERIAL PRIMARY KEY,
			bill_id INT NOT NULL REFERENCES bills(id),
			user_id VARCHAR(255) NOT NULL,
			amount NUMERIC NOT NULL,
			payment_method VARCHAR(100) NOT NULL,
			transaction_id VARCHAR(255),
			status VARCHAR(50) NOT NULL,
			created_at TIMESTAMP NOT NULL DEFAULT NOW()
		);
	`)
	return err
}

func truncateBillsTables(db *sql.DB) error {
	_, err := db.Exec(`TRUNCATE bill_payments, bills, bill_providers RESTART IDENTITY CASCADE;`)
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

func TestBillsPayment_CreateBillAndGet(t *testing.T) {
	db := getDB(t)
	defer db.Close()

	if err := createBillsSchema(db); err != nil {
		t.Fatalf("schema creation failed: %v", err)
	}
	if err := truncateBillsTables(db); err != nil {
		t.Fatalf("truncate failed: %v", err)
	}

	svc := bm.NewBillsPaymentService(db, nil)
	handler := bh.NewBillsPaymentHandler(svc)

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
	token, err := createTestToken("testuser1", []string{})
	if err != nil {
		t.Fatalf("failed to create test token: %v", err)
	}

	// Create a provider first
	_, err = db.Exec(`INSERT INTO bill_providers (name, code, category, processing_fee, min_amount, max_amount, is_active) VALUES ($1, $2, $3, $4, $5, $6, $7)`,
		"Electricity", "ELEC", "utility", 1000.0, 0, 1000000.0, true)
	if err != nil {
		t.Fatalf("failed to insert provider: %v", err)
	}

	// Create bill via POST
	billBody := `{"bill_type":"electricity","provider":"Electricity","account_number":"ACC123","customer_name":"John Doe","amount":500000.0,"period":"2025-01","due_date":"2025-01-31"}`
	req := httptest.NewRequest("POST", "/api/bills", strings.NewReader(billBody))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Authorization", "Bearer "+token)
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)
	if w.Code != http.StatusCreated {
		t.Fatalf("create bill failed with status %d: %s", w.Code, w.Body.String())
	}

	var resp struct {
		Bill bm.Bill `json:"bill"`
	}
	if err := json.Unmarshal(w.Body.Bytes(), &resp); err != nil {
		t.Fatalf("failed to parse response: %v", err)
	}
	bill := resp.Bill
	assert.Equal(t, "electricity", bill.BillType)
	assert.Equal(t, "Electricity", bill.Provider)
	assert.Equal(t, "ACC123", bill.AccountNumber)
	assert.Equal(t, 500000.0, bill.Amount)
	assert.NotZero(t, bill.ID)

	// Get bill by ID
	billID := bill.ID
	getReq := httptest.NewRequest("GET", fmt.Sprintf("/api/bills/%d", billID), nil)
	getReq.Header.Set("Authorization", "Bearer "+token)
	getW := httptest.NewRecorder()
	r.ServeHTTP(getW, getReq)
	if getW.Code != http.StatusOK {
		t.Fatalf("get bill failed with status %d: %s", getW.Code, getW.Body.String())
	}

	var getResp struct {
		Bill bm.Bill `json:"bill"`
	}
	if err := json.Unmarshal(getW.Body.Bytes(), &getResp); err != nil {
		t.Fatalf("failed to parse get response: %v", err)
	}
	assert.Equal(t, bill.ID, getResp.Bill.ID)
	assert.Equal(t, bill.BillType, getResp.Bill.BillType)
}

func TestBillsPayment_PayBill(t *testing.T) {
	db := getDB(t)
	defer db.Close()

	if err := createBillsSchema(db); err != nil {
		t.Fatalf("schema creation failed: %v", err)
	}
	if err := truncateBillsTables(db); err != nil {
		t.Fatalf("truncate failed: %v", err)
	}

	svc := bm.NewBillsPaymentService(db, nil)
	handler := bh.NewBillsPaymentHandler(svc)
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

	// Create provider
	_, err = db.Exec(`INSERT INTO bill_providers (name, code, category, processing_fee, min_amount, max_amount, is_active) VALUES ($1, $2, $3, $4, $5, $6, $7)`,
		"Electricity", "ELEC", "utility", 1000.0, 0, 1000000.0, true)
	if err != nil {
		t.Fatalf("failed to insert provider: %v", err)
	}

	// Create bill with status pending
	billBody := `{"bill_type":"electricity","provider":"Electricity","account_number":"ACC123","customer_name":"John Doe","amount":500000.0,"period":"2025-01","due_date":"2025-01-31"}`
	req := httptest.NewRequest("POST", "/api/bills", strings.NewReader(billBody))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Authorization", "Bearer "+token)
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)
	assert.Equal(t, http.StatusCreated, w.Code)

	var createResp struct{ Bill bm.Bill }
	if err := json.Unmarshal(w.Body.Bytes(), &createResp); err != nil {
		t.Fatalf("failed to parse create bill response: %v", err)
	}
	bill := createResp.Bill
	billID := bill.ID

	// Pay the bill
	payBody := `{"payment_method":"bank_transfer"}`
	payReq := httptest.NewRequest("POST", fmt.Sprintf("/api/bills/%d/pay", billID), strings.NewReader(payBody))
	payReq.Header.Set("Content-Type", "application/json")
	payReq.Header.Set("Authorization", "Bearer "+token)
	payReq.Header.Set("Idempotency-Key", "test-pay-bill-key")
	payW := httptest.NewRecorder()
	r.ServeHTTP(payW, payReq)
	assert.Equal(t, http.StatusOK, payW.Code)

	var payResp struct {
		Payment bm.BillPayment `json:"payment"`
	}
	if err := json.Unmarshal(payW.Body.Bytes(), &payResp); err != nil {
		t.Fatalf("failed to parse pay response: %v", err)
	}
	payment := payResp.Payment
	assert.Equal(t, billID, payment.BillID)
	assert.Equal(t, "user123", payment.UserID)
	assert.Equal(t, 500000.0, payment.Amount)
	assert.Equal(t, "bank_transfer", payment.PaymentMethod)
	assert.Equal(t, "completed", payment.Status)
	assert.NotZero(t, payment.ID)

	// Get bill again to verify status paid
	getBillReq := httptest.NewRequest("GET", fmt.Sprintf("/api/bills/%d", billID), nil)
	getBillReq.Header.Set("Authorization", "Bearer "+token)
	getBillW := httptest.NewRecorder()
	r.ServeHTTP(getBillW, getBillReq)
	assert.Equal(t, http.StatusOK, getBillW.Code)

	var getBillResp struct{ Bill bm.Bill }
	if err := json.Unmarshal(getBillW.Body.Bytes(), &getBillResp); err != nil {
		t.Fatalf("failed to parse get bill response: %v", err)
	}
	paidBill := getBillResp.Bill
	assert.Equal(t, "paid", paidBill.Status)
	assert.NotNil(t, paidBill.PaidAt)
}