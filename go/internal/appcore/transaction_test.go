package appcore

import (
	"testing"
)

func TestNewSimpleTransactionManager(t *testing.T) {
	mgr := NewSimpleTransactionManager()
	if mgr == nil {
		t.Fatal("NewSimpleTransactionManager returned nil")
	}
}

func TestSimpleTransactionManager_Begin(t *testing.T) {
	mgr := NewSimpleTransactionManager()
	tx, err := mgr.Begin()
	if err != nil {
		t.Fatalf("Begin() error = %v", err)
	}
	if tx == nil {
		t.Fatal("Begin() returned nil transaction")
	}
}

func TestSimpleTransaction_Commit(t *testing.T) {
	tx := &SimpleTransaction{}
	if err := tx.Commit(); err != nil {
		t.Errorf("Commit() error = %v", err)
	}
	if !tx.committed {
		t.Error("Commit(): committed flag not set")
	}
	if tx.rolledBack {
		t.Error("Commit(): rolledBack flag should be false")
	}
}

func TestSimpleTransaction_Rollback(t *testing.T) {
	tx := &SimpleTransaction{}
	if err := tx.Rollback(); err != nil {
		t.Errorf("Rollback() error = %v", err)
	}
	if !tx.rolledBack {
		t.Error("Rollback(): rolledBack flag not set")
	}
	if tx.committed {
		t.Error("Rollback(): committed flag should be false")
	}
}

func TestSimpleTransaction_CommitAfterRollback(t *testing.T) {
	tx := &SimpleTransaction{}
	tx.Rollback()
	err := tx.Commit()
	if err == nil {
		t.Fatal("Expected error when Commit after Rollback")
	}
	if err.Error() != "transaction already rolled back" {
		t.Errorf("Unexpected error: %v", err)
	}
}

func TestSimpleTransaction_RollbackAfterCommit(t *testing.T) {
	tx := &SimpleTransaction{}
	tx.Commit()
	err := tx.Rollback()
	if err == nil {
		t.Fatal("Expected error when Rollback after Commit")
	}
	if err.Error() != "transaction already committed" {
		t.Errorf("Unexpected error: %v", err)
	}
}
