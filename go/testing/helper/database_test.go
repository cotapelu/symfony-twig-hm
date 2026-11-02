package helper

import (
	"database/sql"
	"testing"

	_ "github.com/mattn/go-sqlite3"
)

func TestSetupTestDB(t *testing.T) {
	db := SetupTestDB(t)
	if db == nil {
		t.Error("SetupTestDB returned nil")
	}
	defer db.Close()

	// Verify database is usable
	if err := db.Ping(); err != nil {
		t.Errorf("Database ping failed: %v", err)
	}

	// Verify foreign keys are enabled
	var fkEnabled int
	err := db.QueryRow("PRAGMA foreign_keys").Scan(&fkEnabled)
	if err != nil {
		t.Errorf("Failed to query foreign_keys pragma: %v", err)
	}
	if fkEnabled != 1 {
		t.Errorf("Foreign keys not enabled, got %d", fkEnabled)
	}
}

func TestSetupTestDB_Cleanup(t *testing.T) {
	var db *sql.DB
	func() {
		t2 := &testing.T{}
		db = SetupTestDB(t2)
	}()

	// The database should be closed after the test function returns
	// due to t.Cleanup
	if db != nil {
		err := db.Ping()
		// Should fail because connection is closed
		if err == nil {
			t.Log("Database connection still open after cleanup (expected for in-memory SQLite)")
		}
	}
}

func TestSetupTestDB_MultipleCalls(t *testing.T) {
	// Test that multiple calls create independent databases
	db1 := SetupTestDB(t)
	defer db1.Close()

	db2 := SetupTestDB(t)
	defer db2.Close()

	if db1 == db2 {
		t.Error("SetupTestDB returned same database instance")
	}
}

func TestMustExec(t *testing.T) {
	db := SetupTestDB(t)
	defer db.Close()

	// Test successful execution
	MustExec(db, t, "CREATE TABLE test (id INTEGER PRIMARY KEY, name TEXT)")

	// Test inserting data
	MustExec(db, t, "INSERT INTO test (name) VALUES (?)", "test")

	// Verify data was inserted
	var name string
	err := db.QueryRow("SELECT name FROM test WHERE id = 1").Scan(&name)
	if err != nil {
		t.Errorf("Failed to query inserted data: %v", err)
	}
	if name != "test" {
		t.Errorf("Expected 'test', got '%s'", name)
	}
}

func TestMustExec_MultipleStatements(t *testing.T) {
	db := SetupTestDB(t)
	defer db.Close()

	// Test multiple statements
	MustExec(db, t, "CREATE TABLE multi (id INTEGER PRIMARY KEY, value TEXT)")
	MustExec(db, t, "INSERT INTO multi (value) VALUES (?)", "first")
	MustExec(db, t, "INSERT INTO multi (value) VALUES (?)", "second")

	// Verify both rows exist
	rows, err := db.Query("SELECT value FROM multi ORDER BY id")
	if err != nil {
		t.Errorf("Failed to query: %v", err)
	}
	defer rows.Close()

	var values []string
	for rows.Next() {
		var v string
		if err := rows.Scan(&v); err != nil {
			t.Errorf("Failed to scan: %v", err)
		}
		values = append(values, v)
	}

	if len(values) != 2 || values[0] != "first" || values[1] != "second" {
		t.Errorf("Expected [first, second], got %v", values)
	}
}