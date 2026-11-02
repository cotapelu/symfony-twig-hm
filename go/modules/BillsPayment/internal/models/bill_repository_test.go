package billspayment

import (
	"context"
	"database/sql"
	"testing"
	"time"

	_ "github.com/mattn/go-sqlite3"
	"github.com/stretchr/testify/assert"

	"community.local/testing/helper"
	"github.com/mattn/go-sqlite3"
)

func setupBillsSQLiteSchema(t *testing.T, db *sql.DB) {
	t.Helper()
	_, _ = db.Exec("PRAGMA foreign_keys = ON")

	// bill_providers
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

func TestBillRepository_CreateAndGetBill(t *testing.T) {
	db := helper.SetupTestDB(t)
	defer db.Close()
	setupBillsSQLiteSchema(t, db)

	repo := NewBillRepository(db)
	ctx := context.Background()

	dueDate, _ := time.Parse("2006-01-02", "2025-01-31")
	bill := &Bill{
		BillType:      "electricity",
		Provider:      "ElectricCo",
		AccountNumber: "ACC-999",
		CustomerName:  "Jane Doe",
		Amount:        250000,
		Period:        "2025-02",
		Status:        "unpaid",
		DueDate:       dueDate,
	}
	err := repo.CreateBill(ctx, bill)
	assert.NoError(t, err)
	assert.NotZero(t, bill.ID)
	assert.NotZero(t, bill.CreatedAt)

	// Get by ID
	fetched, err := repo.GetBill(ctx, bill.ID)
	assert.NoError(t, err)
	assert.Equal(t, bill.ID, fetched.ID)
	assert.Equal(t, "electricity", fetched.BillType)
}

func TestBillRepository_MarkAsPaid(t *testing.T) {
	db := helper.SetupTestDB(t)
	defer db.Close()
	setupBillsSQLiteSchema(t, db)

	repo := NewBillRepository(db)
	ctx := context.Background()

	dueDate, _ := time.Parse("2006-01-02", "2025-01-31")
	bill := &Bill{
		BillType:      "water",
		Provider:      "WaterCo",
		AccountNumber: "ACC-W1",
		CustomerName:  "John Water",
		Amount:        100000,
		Period:        "2025-01",
		Status:        "unpaid",
		DueDate:       dueDate,
	}
	err := repo.CreateBill(ctx, bill)
	assert.NoError(t, err)

	// Mark as paid
	err = repo.MarkAsPaid(ctx, bill.ID)
	assert.NoError(t, err)

	updated, err := repo.GetBill(ctx, bill.ID)
	assert.NoError(t, err)
	assert.Equal(t, "paid", updated.Status)
	assert.NotNil(t, updated.PaidAt)
}

func TestBillRepository_MarkAsPaid_NotFound(t *testing.T) {
	db := helper.SetupTestDB(t)
	defer db.Close()
	setupBillsSQLiteSchema(t, db)

	repo := NewBillRepository(db)
	ctx := context.Background()

	// Attempt to mark a non-existent bill as paid
	err := repo.MarkAsPaid(ctx, 999)
	assert.Error(t, err)
}

func TestBillRepository_GetBillsByAccount(t *testing.T) {
	db := helper.SetupTestDB(t)
	defer db.Close()
	setupBillsSQLiteSchema(t, db)

	repo := NewBillRepository(db)
	ctx := context.Background()

	dueDate, _ := time.Parse("2006-01-02", "2025-01-15")
	bills := []*Bill{
		{BillType: "electricity", Provider: "E", AccountNumber: "ACC-1", CustomerName: "A", Amount: 100, Period: "2025-01", Status: "unpaid", DueDate: dueDate},
		{BillType: "water", Provider: "W", AccountNumber: "ACC-1", CustomerName: "B", Amount: 200, Period: "2025-01", Status: "unpaid", DueDate: dueDate},
		{BillType: "internet", Provider: "I", AccountNumber: "ACC-2", CustomerName: "C", Amount: 300, Period: "2025-01", Status: "unpaid", DueDate: dueDate},
	}
	for _, b := range bills {
		err := repo.CreateBill(ctx, b)
		assert.NoError(t, err)
	}

	// Get by account ACC-1
	accBills, err := repo.GetBillsByAccount(ctx, "ACC-1")
	assert.NoError(t, err)
	assert.Len(t, accBills, 2)
}

func TestBillRepository_GetUnpaidBills(t *testing.T) {
	db := helper.SetupTestDB(t)
	defer db.Close()
	// Skip on SQLite due to NOW() function incompatibility
	if _, ok := db.Driver().(*sqlite3.SQLiteDriver); ok {
		t.Skip("Skipping GetUnpaidBills on SQLite (NOW() not supported)")
	}
	setupBillsSQLiteSchema(t, db)

	repo := NewBillRepository(db)
	ctx := context.Background()

	// Insert some bills with different statuses and due dates
	now := time.Now()
	unpaidBill := &Bill{
		BillType:      "utilities",
		Provider:      "PowerCo",
		AccountNumber: "ACC-001",
		CustomerName:  "Alice",
		Amount:        100.0,
		Period:        "2024-01",
		Status:        "unpaid",
		DueDate:       now.Add(24 * time.Hour), // due in future
	}
	if err := repo.CreateBill(ctx, unpaidBill); err != nil {
		t.Fatalf("CreateBill failed: %v", err)
	}
	paidBill := &Bill{
		BillType:      "utilities",
		Provider:      "WaterCo",
		AccountNumber: "ACC-002",
		CustomerName:  "Bob",
		Amount:        50.0,
		Period:        "2024-01",
		Status:        "paid",
		DueDate:       now.Add(-24 * time.Hour), // past due but paid
	}
	if err := repo.CreateBill(ctx, paidBill); err != nil {
		t.Fatalf("CreateBill failed: %v", err)
	}
	futureUnpaid := &Bill{
		BillType:      "internet",
		Provider:      "NetCo",
		AccountNumber: "ACC-003",
		CustomerName:  "Charlie",
		Amount:        75.0,
		Period:        "2024-01",
		Status:        "unpaid",
		DueDate:       now.Add(48 * time.Hour), // further future
	}
	if err := repo.CreateBill(ctx, futureUnpaid); err != nil {
		t.Fatalf("CreateBill failed: %v", err)
	}

	// Query unpaid bills
	unpaid, err := repo.GetUnpaidBills(ctx)
	if err != nil {
		t.Fatalf("GetUnpaidBills failed: %v", err)
	}

	// Should return only unpaid bills with due_date >= NOW()
	// SQL: WHERE status = 'unpaid' AND due_date >= NOW()
	// So unpaidBill and futureUnpaid qualify (both status unpaid and due in future)
	// paidBill status is 'paid' so excluded.
	if len(unpaid) != 2 {
		t.Errorf("Expected 2 unpaid bills, got %d", len(unpaid))
	}
}

func TestBillRepository_GetUpcomingBills(t *testing.T) {
	db := helper.SetupTestDB(t)
	defer db.Close()
	// Skip on SQLite due to INTERVAL syntax incompatibility
	if _, ok := db.Driver().(*sqlite3.SQLiteDriver); ok {
		t.Skip("Skipping GetUpcomingBills on SQLite (INTERVAL not supported)")
	}
	setupBillsSQLiteSchema(t, db)

	repo := NewBillRepository(db)
	ctx := context.Background()

	now := time.Now()
	// Create bills: one unpaid due within 1 day, one unpaid due in 5 days, one paid due within 1 day
	bill1 := &Bill{
		BillType:      "utilities",
		Provider:      "PowerCo",
		AccountNumber: "ACC-001",
		CustomerName:  "Alice",
		Amount:        100.0,
		Period:        "2024-01",
		Status:        "unpaid",
		DueDate:       now.Add(12 * time.Hour), // within 1 day
	}
	if err := repo.CreateBill(ctx, bill1); err != nil {
		t.Fatalf("CreateBill failed: %v", err)
	}
	bill2 := &Bill{
		BillType:      "utilities",
		Provider:      "WaterCo",
		AccountNumber: "ACC-002",
		CustomerName:  "Bob",
		Amount:        50.0,
		Period:        "2024-01",
		Status:        "unpaid",
		DueDate:       now.Add(5 * 24 * time.Hour), // 5 days, outside 1-day window
	}
	if err := repo.CreateBill(ctx, bill2); err != nil {
		t.Fatalf("CreateBill failed: %v", err)
	}
	bill3 := &Bill{
		BillType:      "internet",
		Provider:      "NetCo",
		AccountNumber: "ACC-003",
		CustomerName:  "Charlie",
		Amount:        75.0,
		Period:        "2024-01",
		Status:        "paid",
		DueDate:       now.Add(6 * time.Hour), // within 1 day but paid
	}
	if err := repo.CreateBill(ctx, bill3); err != nil {
		t.Fatalf("CreateBill failed: %v", err)
	}

	// Query upcoming bills for next 1 day
	upcoming, err := repo.GetUpcomingBills(ctx, 1)
	if err != nil {
		t.Fatalf("GetUpcomingBills failed: %v", err)
	}

	// Should return only unpaid bills with due_date between NOW() and NOW()+1 day
	// Expected: only bill1
	if len(upcoming) != 1 {
		t.Errorf("Expected 1 upcoming bill, got %d", len(upcoming))
	}
	if upcoming[0].ID != bill1.ID {
		t.Errorf("Expected upcoming bill to be bill1, got %d", upcoming[0].ID)
	}
}
