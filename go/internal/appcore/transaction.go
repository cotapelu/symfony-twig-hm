package appcore

import "errors"

// Transaction interface
type Transaction interface {
    Commit() error
    Rollback() error
}

// TransactionManager interface
type TransactionManager interface {
    Begin() (Transaction, error)
}

// Simple in-memory transaction for demonstration
type SimpleTransaction struct {
    committed  bool
    rolledBack bool
}

func (t *SimpleTransaction) Commit() error {
    if t.rolledBack {
        return errors.New("transaction already rolled back")
    }
    t.committed = true
    return nil
}

func (t *SimpleTransaction) Rollback() error {
    if t.committed {
        return errors.New("transaction already committed")
    }
    t.rolledBack = true
    return nil
}

type SimpleTransactionManager struct{}

func NewSimpleTransactionManager() *SimpleTransactionManager {
    return &SimpleTransactionManager{}
}

func (stm *SimpleTransactionManager) Begin() (Transaction, error) {
    return &SimpleTransaction{}, nil
}