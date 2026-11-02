package banking

import (
	"database/sql"
	"testing"

	_ "github.com/mattn/go-sqlite3"
)

// TestNewHandler tests the NewHandler function
func TestNewHandler(t *testing.T) {
	db, err := sql.Open("sqlite3", ":memory:")
	if err != nil {
		t.Fatalf("Failed to open SQLite: %v", err)
	}
	defer db.Close()

	// Test that NewHandler returns a non-nil handler
	handler := NewHandler(db)
	if handler == nil {
		t.Fatal("NewHandler returned nil handler")
	}

	// Verify the handler has the service properly initialized
	// This just ensures the function executes without panic
	_ = handler
}

// TestNewHandlerWithNilDB tests NewHandler with nil database
func TestNewHandlerWithNilDB(t *testing.T) {
	// This should not panic but the service will have nil DB
	// The nil-db guards in the repository should handle this
	handler := NewHandler(nil)
	if handler == nil {
		t.Fatal("NewHandler returned nil handler with nil DB")
	}
}