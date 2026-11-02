package helper

import (
    "database/sql"
    "path/filepath"
    "testing"

    _ "github.com/mattn/go-sqlite3"
)

// SetupTestDB creates a temporary file-based SQLite database for testing.
func SetupTestDB(t *testing.T) *sql.DB {
    t.Helper()
    dbDir := t.TempDir()
    dbPath := filepath.Join(dbDir, "test.db")
    db, err := sql.Open("sqlite3", dbPath)
    if err != nil {
        t.Fatalf("failed to open test database: %v", err)
    }

    // Enable foreign keys
    if _, err := db.Exec("PRAGMA foreign_keys = ON"); err != nil {
        t.Fatalf("failed to enable foreign keys: %v", err)
    }

    t.Cleanup(func() {
        db.Close()
    })

    return db
}

// MustExec runs an SQL statement and fails test on error.
func MustExec(db *sql.DB, t *testing.T, query string, args ...interface{}) {
    t.Helper()
    if _, err := db.Exec(query, args...); err != nil {
        t.Fatalf("query failed: %s\n%v", query, err)
    }
}
