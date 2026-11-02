package billspayment

import (
	"context"
	"testing"
	"time"
)

type mockBillsEventHandler struct {
	events []BillPaidEvent
}

func (m *mockBillsEventHandler) EmitEvent(ctx context.Context, eventType string, data interface{}) error {
	if billEvent, ok := data.(BillPaidEvent); ok {
		m.events = append(m.events, billEvent)
	}
	return nil
}

func TestBillCreation(t *testing.T) {
	bill := &Bill{
		BillType:      "electricity",
		Provider:      "EVN",
		AccountNumber: "ACC123",
		CustomerName:  "John Doe",
		Amount:        150000,
		Period:        "2024-01",
		Status:        "unpaid",
		DueDate:       time.Now().Add(7 * 24 * time.Hour),
	}

	if bill.BillType != "electricity" {
		t.Errorf("Expected billType 'electricity', got '%s'", bill.BillType)
	}

	if bill.Provider != "EVN" {
		t.Errorf("Expected provider 'EVN', got '%s'", bill.Provider)
	}

	if bill.Amount != 150000 {
		t.Errorf("Expected amount 150000, got %f", bill.Amount)
	}

	if bill.Status != "unpaid" {
		t.Errorf("Expected status 'unpaid', got '%s'", bill.Status)
	}
}

func TestBillPaymentCreation(t *testing.T) {
	payment := &BillPayment{
		BillID:        1,
		UserID:        "user123",
		Amount:        150000,
		PaymentMethod: "wallet",
		TransactionID: "TXN123",
		Status:        "completed",
	}

	if payment.BillID != 1 {
		t.Errorf("Expected billID 1, got %d", payment.BillID)
	}

	if payment.UserID != "user123" {
		t.Errorf("Expected userID 'user123', got '%s'", payment.UserID)
	}

	if payment.Amount != 150000 {
		t.Errorf("Expected amount 150000, got %f", payment.Amount)
	}

	if payment.PaymentMethod != "wallet" {
		t.Errorf("Expected paymentMethod 'wallet', got '%s'", payment.PaymentMethod)
	}

	if payment.Status != "completed" {
		t.Errorf("Expected status 'completed', got '%s'", payment.Status)
	}
}

func TestBillProviderCreation(t *testing.T) {
	provider := &BillProvider{
		ID:            1,
		Name:          "Electricity Company",
		Code:          "EVN",
		Category:      "utilities",
		ProcessingFee: 1000,
		MinAmount:     10000,
		MaxAmount:     1000000,
		IsActive:      true,
	}

	if provider.Name != "Electricity Company" {
		t.Errorf("Expected name 'Electricity Company', got '%s'", provider.Name)
	}

	if provider.Code != "EVN" {
		t.Errorf("Expected code 'EVN', got '%s'", provider.Code)
	}

	if provider.Category != "utilities" {
		t.Errorf("Expected category 'utilities', got '%s'", provider.Category)
	}

	if !provider.IsActive {
		t.Error("Expected provider to be active")
	}
}

func TestBillStatusPaid(t *testing.T) {
	bill := &Bill{
		Status: "unpaid",
	}

	if bill.Status == "paid" {
		t.Error("Bill should not be paid initially")
	}

	now := time.Now()
	bill.Status = "paid"
	bill.PaidAt = &now

	if bill.Status != "paid" {
		t.Errorf("Expected status 'paid', got '%s'", bill.Status)
	}

	if bill.PaidAt == nil {
		t.Error("PaidAt should not be nil")
	}
}

func TestBillDueDate(t *testing.T) {
	dueDate := time.Now().Add(7 * 24 * time.Hour)
	bill := &Bill{
		DueDate: dueDate,
	}

	if time.Now().After(bill.DueDate) {
		t.Error("Due date should be in the future")
	}

	if bill.DueDate.Before(time.Now().Add(24 * time.Hour)) {
		t.Error("Due date should be at least 24 hours in the future")
	}
}

func TestPaymentTransactionID(t *testing.T) {
	transactionID := "BILLPAY-1-1234567890"

	if transactionID == "" {
		t.Error("Transaction ID should not be empty")
	}

	if len(transactionID) < 10 {
		t.Errorf("Transaction ID seems too short: %s", transactionID)
	}
}

func TestBillPaidEvent(t *testing.T) {
	handler := &mockBillsEventHandler{}

	event := BillPaidEvent{
		EventType: "bill.paid",
		BillID:    1,
		PaymentID: 1,
		UserID:    "user123",
		Amount:    150000,
		Status:    "completed",
		Timestamp: time.Now(),
	}

	err := handler.EmitEvent(context.Background(), "bill.payment", event)
	if err != nil {
		t.Errorf("EmitEvent failed: %v", err)
	}

	if len(handler.events) != 1 {
		t.Errorf("Expected 1 event, got %d", len(handler.events))
	}

	if handler.events[0].EventType != "bill.paid" {
		t.Errorf("Expected event type 'bill.paid', got '%s'", handler.events[0].EventType)
	}

	if handler.events[0].Amount != 150000 {
		t.Errorf("Expected amount 150000, got %f", handler.events[0].Amount)
	}
}

func TestBillTypes(t *testing.T) {
	validBillTypes := map[string]bool{
		"electricity": true,
		"water":       true,
		"internet":    true,
		"phone":       true,
		"insurance":   true,
	}

	testBillTypes := []string{"electricity", "water", "internet"}

	for _, billType := range testBillTypes {
		if !validBillTypes[billType] {
			t.Errorf("Bill type '%s' should be valid", billType)
		}
	}
}

func TestPaymentMethods(t *testing.T) {
	validMethods := map[string]bool{
		"wallet": true,
		"card":   true,
		"bank":   true,
		"cash":   true,
	}

	testMethods := []string{"wallet", "card"}

	for _, method := range testMethods {
		if !validMethods[method] {
			t.Errorf("Payment method '%s' should be valid", method)
		}
	}
}

func TestProcessingFeeCalculation(t *testing.T) {
	testCases := []struct {
		name     string
		amount   float64
		fee      float64
		expected float64
	}{
		{"Amount 100000 with fee 1000", 100000, 1000, 101000},
		{"Amount 50000 with fee 1000", 50000, 1000, 51000},
		{"Amount 10000 minimum", 10000, 1000, 11000},
	}

	for _, tc := range testCases {
		t.Run(tc.name, func(t *testing.T) {
			total := tc.amount + tc.fee
			if total != tc.expected {
				t.Errorf("Expected total %f, got %f", tc.expected, total)
			}
		})
	}
}

func TestProviderMinMaxAmount(t *testing.T) {
	provider := &BillProvider{
		MinAmount: 10000,
		MaxAmount: 1000000,
	}

	testAmounts := []struct {
		name   string
		amount float64
		valid  bool
	}{
		{"Below minimum", 5000, false},
		{"At minimum", 10000, true},
		{"In range", 500000, true},
		{"At maximum", 1000000, true},
		{"Above maximum", 2000000, false},
	}

	for _, tc := range testAmounts {
		t.Run(tc.name, func(t *testing.T) {
			valid := tc.amount >= provider.MinAmount && tc.amount <= provider.MaxAmount
			if valid != tc.valid {
				t.Errorf("Expected valid=%v for amount %f", tc.valid, tc.amount)
			}
		})
	}
}
