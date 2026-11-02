package QRPayment

import (
	"context"
	"database/sql"
	"testing"

	_ "github.com/mattn/go-sqlite3"
)

func TestNewHandler(t *testing.T) {
	// Create in-memory database
	db, err := sql.Open("sqlite3", ":memory:")
	if err != nil {
		t.Fatalf("Failed to open database: %v", err)
	}
	defer db.Close()

	// Create tables needed by QRPayment
	_, err = db.ExecContext(context.Background(), `
		CREATE TABLE IF NOT EXISTS qr_payments (
			id TEXT PRIMARY KEY,
			merchant_id TEXT NOT NULL,
			amount INTEGER NOT NULL,
			currency TEXT NOT NULL DEFAULT 'USD',
			status TEXT NOT NULL DEFAULT 'pending',
			description TEXT,
			qr_code TEXT,
			expires_at DATETIME,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
		);
		CREATE TABLE IF NOT EXISTS qr_refunds (
			id TEXT PRIMARY KEY,
			payment_id TEXT NOT NULL,
			amount INTEGER NOT NULL,
			reason TEXT,
			status TEXT NOT NULL DEFAULT 'pending',
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
		);
	`)
	if err != nil {
		t.Fatalf("Failed to create tables: %v", err)
	}

	// Test NewHandler
	handler := NewHandler(db)
	if handler == nil {
		t.Error("NewHandler returned nil")
	}
}