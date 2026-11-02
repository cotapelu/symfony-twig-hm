package QRPayment

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

	qh "community.local/modules/QRPayment/handler"
	qm "community.local/modules/QRPayment/service"
	bm "community.local/modules/QRPayment/internal/models"
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
	ctx, cancel := context.WithTimeout(context.TODO(), 10_000_000_000)
	defer cancel()
	if err := db.PingContext(ctx); err != nil {
		t.Fatalf("failed to ping database: %v", err)
	}
	return db
}

func createQRPaymentSchema(db *sql.DB) error {
	// qr_codes
	_, err := db.Exec(`
		CREATE TABLE IF NOT EXISTS qr_codes (
			id SERIAL PRIMARY KEY,
			code VARCHAR(255) UNIQUE NOT NULL,
			code_type VARCHAR(50) NOT NULL,
			merchant_id VARCHAR(255) NOT NULL,
			merchant_name VARCHAR(255) NOT NULL,
			amount NUMERIC NOT NULL,
			currency VARCHAR(3) NOT NULL DEFAULT 'VND',
			description TEXT,
			status VARCHAR(50) NOT NULL DEFAULT 'active',
			expires_at TIMESTAMP NOT NULL,
			used_at TIMESTAMP,
			created_at TIMESTAMP NOT NULL DEFAULT NOW()
		);
	`)
	if err != nil {
		return err
	}
	// qr_payments
	_, err = db.Exec(`
		CREATE TABLE IF NOT EXISTS qr_payments (
			id SERIAL PRIMARY KEY,
			qr_code_id INT NOT NULL REFERENCES qr_codes(id),
			payer_id VARCHAR(255) NOT NULL,
			amount NUMERIC NOT NULL,
			fee NUMERIC NOT NULL,
			net_amount NUMERIC NOT NULL,
			currency VARCHAR(3) NOT NULL,
			payment_method VARCHAR(100) NOT NULL,
			reference_id VARCHAR(255) UNIQUE NOT NULL,
			bank_code VARCHAR(50),
			status VARCHAR(50) NOT NULL,
			created_at TIMESTAMP NOT NULL DEFAULT NOW(),
			completed_at TIMESTAMP
		);
	`)
	return err
}

func truncateQRPTables(db *sql.DB) error {
	_, err := db.Exec(`TRUNCATE qr_payments, qr_codes RESTART IDENTITY CASCADE;`)
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

func TestQRPayment_GenerateAndPay(t *testing.T) {
	db := getDB(t)
	defer db.Close()

	if err := createQRPaymentSchema(db); err != nil {
		t.Fatalf("schema creation failed: %v", err)
	}
	if err := truncateQRPTables(db); err != nil {
		t.Fatalf("truncate failed: %v", err)
	}

	svc := qm.NewQRPaymentService(db, nil)
	handler := qh.NewQRPaymentHandler(svc)

	r := gin.New()
	// Apply auth middleware like in main.go
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

	// Create test token for merchant user
	token, err := createTestToken("M001", []string{})
	if err != nil {
		t.Fatalf("failed to create test token: %v", err)
	}

	// Generate QR code
	genBody := `{"merchant_id":"M001","merchant_name":"Test Merchant","amount":100000.0,"currency":"VND","description":"Test","expiry_minutes":10}`
	req := httptest.NewRequest("POST", "/api/qr/generate", strings.NewReader(genBody))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Authorization", "Bearer "+token)
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)
	assert.Equal(t, http.StatusCreated, w.Code)

	var genResp struct {
		QRCode bm.QRCode `json:"qr_code"`
	}
	if err := json.Unmarshal(w.Body.Bytes(), &genResp); err != nil {
		t.Fatalf("failed to parse generate response: %v", err)
	}
	qr := genResp.QRCode
	assert.Equal(t, "M001", qr.MerchantID)
	assert.Equal(t, 100000.0, qr.Amount)
	assert.Equal(t, "dynamic", qr.CodeType)
	assert.NotZero(t, qr.ID)
	assert.NotEmpty(t, qr.Code)

	// Process payment - create token for payer
	payerToken, err := createTestToken("user123", []string{})
	if err != nil {
		t.Fatalf("failed to create payer token: %v", err)
	}

	payBody := fmt.Sprintf(`{"qr_code":"%s","payer_id":"user123","amount":100000.0,"payment_method":"bank_transfer","bank_code":"B001"}`, qr.Code)
	payReq := httptest.NewRequest("POST", "/api/qr/pay", strings.NewReader(payBody))
	payReq.Header.Set("Content-Type", "application/json")
	payReq.Header.Set("Authorization", "Bearer "+payerToken)
	payReq.Header.Set("Idempotency-Key", "test-key-123")
	payW := httptest.NewRecorder()
	r.ServeHTTP(payW, payReq)
	if payW.Code != http.StatusOK {
		t.Logf("Payment response: %s", payW.Body.String())
	}
	assert.Equal(t, http.StatusOK, payW.Code)

	var payResp struct {
		Payment bm.QRCPayment `json:"payment"`
	}
	if err := json.Unmarshal(payW.Body.Bytes(), &payResp); err != nil {
		t.Fatalf("failed to parse payment response: %v", err)
	}
	payment := payResp.Payment
	assert.Equal(t, qr.ID, payment.QRCodeID)
	assert.Equal(t, "user123", payment.PayerID)
	assert.Equal(t, 100000.0, payment.Amount)
	assert.GreaterOrEqual(t, payment.Fee, 1000.0)
	assert.Equal(t, "completed", payment.Status)
	assert.NotZero(t, payment.ID)
}