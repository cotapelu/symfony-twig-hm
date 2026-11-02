package CreditLending

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

	// Create tables needed by CreditLending
	_, err = db.ExecContext(context.Background(), `
		CREATE TABLE IF NOT EXISTS credit_applications (
			id TEXT PRIMARY KEY,
			user_id TEXT NOT NULL,
			amount INTEGER NOT NULL,
			purpose TEXT NOT NULL,
			status TEXT NOT NULL DEFAULT 'pending',
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
		);
		CREATE TABLE IF NOT EXISTS credit_scores (
			user_id TEXT PRIMARY KEY,
			score INTEGER NOT NULL,
			factors TEXT,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
		);
		CREATE TABLE IF NOT EXISTS credit_repayments (
			id TEXT PRIMARY KEY,
			application_id TEXT NOT NULL,
			amount INTEGER NOT NULL,
			due_date DATETIME NOT NULL,
			status TEXT NOT NULL DEFAULT 'pending',
			paid_at DATETIME
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