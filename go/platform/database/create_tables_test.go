package database

import (
	"errors"
	"testing"

	"github.com/DATA-DOG/go-sqlmock"
)

func TestCreateTables(t *testing.T) {
	db, mock, err := sqlmock.New()
	if err != nil {
		t.Fatalf("failed to create sqlmock: %v", err)
	}
	defer db.Close()

	// There are 32 migrations in CreateTables as of now.
	const migrationCount = 32
	for i := 0; i < migrationCount; i++ {
		mock.ExpectExec(`(?s).+`).WillReturnResult(sqlmock.NewResult(0, 1))
	}

	err = CreateTables(db)
	if err != nil {
		t.Fatalf("CreateTables returned error: %v", err)
	}

	if err := mock.ExpectationsWereMet(); err != nil {
		t.Fatalf("unmet expectations: %v", err)
	}
}

func TestCreateTables_Error(t *testing.T) {
	db, mock, err := sqlmock.New()
	if err != nil {
		t.Fatalf("failed to create sqlmock: %v", err)
	}
	defer db.Close()

	// First migration succeeds, second fails
	mock.ExpectExec(`(?s).+`).WillReturnResult(sqlmock.NewResult(0, 1))
	mock.ExpectExec(`(?s).+`).WillReturnError(errors.New("simulated exec error"))

	err = CreateTables(db)
	if err == nil {
		t.Fatal("expected error, got nil")
	}
	// The error should indicate migration failure
	// Optionally check error message contains "failed"
	// Not checking exact message to avoid brittleness
}
