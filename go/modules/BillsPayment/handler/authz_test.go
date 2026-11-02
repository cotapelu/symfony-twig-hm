package handler

import (
	"context"
	"net/http"
	"net/http/httptest"
	"testing"

	"github.com/gin-gonic/gin"
	"github.com/stretchr/testify/assert"

	bpmodels "community.local/modules/BillsPayment/internal/models"
	"community.local/testing/helper"
)

// TestGetPaymentByTransaction_OwnershipCheck tests that only the owner (or admin) can retrieve a payment by transaction ID.
func TestGetPaymentByTransaction_OwnershipCheck(t *testing.T) {
	gin.SetMode(gin.TestMode)

	db := helper.SetupTestDB(t)
	defer db.Close()
	setupBillsSQLiteSchema(t, db)

	svc := bpmodels.NewBillsPaymentService(db, nil)
	h := NewBillsPaymentHandler(svc)

	router := gin.New()
	router.Use(authMiddleware())
	h.RegisterRoutes(router)

	// Insert a provider and a bill, then a payment for bill
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

	// Create a payment for user "user_owner"
	txID := "tx_owner_123"
	_, err = db.Exec(`INSERT INTO bill_payments (bill_id, user_id, amount, payment_method, transaction_id, status) VALUES ($1,$2,$3,$4,$5,$6)`,
		billID, "user_owner", 500000, "bank_transfer", txID, "completed")
	if err != nil {
		t.Fatalf("insert payment failed: %v", err)
	}

	// Request as user_owner -> should succeed (200)
	reqOwner := httptest.NewRequest("GET", "/api/bills/payments/tx/"+txID, nil)
	reqOwner.Header.Set("X-User-ID", "user_owner")
	wOwner := httptest.NewRecorder()
	router.ServeHTTP(wOwner, reqOwner)

	assert.Equal(t, http.StatusOK, wOwner.Code, "owner should get payment successfully")

	// Request as different user -> should be forbidden (403)
	reqOther := httptest.NewRequest("GET", "/api/bills/payments/tx/"+txID, nil)
	reqOther.Header.Set("X-User-ID", "user_other")
	wOther := httptest.NewRecorder()
	router.ServeHTTP(wOther, reqOther)

	assert.Equal(t, http.StatusForbidden, wOther.Code, "non-owner should be forbidden")
}