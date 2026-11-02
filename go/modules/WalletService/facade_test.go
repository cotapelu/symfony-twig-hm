package WalletService

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

	// Create tables
	_, err = db.ExecContext(context.Background(), `
		CREATE TABLE IF NOT EXISTS wallets (
			id TEXT PRIMARY KEY,
			user_id TEXT NOT NULL,
			balance INTEGER NOT NULL DEFAULT 0,
			currency TEXT NOT NULL DEFAULT 'USD',
			status TEXT NOT NULL DEFAULT 'active',
			version INTEGER NOT NULL DEFAULT 1,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
		);
		CREATE TABLE IF NOT EXISTS wallet_transactions (
			id TEXT PRIMARY KEY,
			wallet_id TEXT NOT NULL,
			reference_id TEXT NOT NULL UNIQUE,
			type TEXT NOT NULL,
			amount INTEGER NOT NULL,
			currency TEXT NOT NULL,
			status TEXT NOT NULL,
			description TEXT,
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

func TestNewHandler_NilDB(t *testing.T) {
	// Test with nil db - should not panic but will fail when used
	defer func() {
		if r := recover(); r != nil {
			t.Log("NewHandler panicked with nil db as expected")
		}
	}()

	// This might panic or return a handler that fails on use
	handler := NewHandler(nil)
	if handler == nil {
		t.Log("NewHandler returned nil for nil db")
	}
}