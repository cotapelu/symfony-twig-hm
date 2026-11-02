package appcore

import (
	"testing"
)

// fakeModule implements Module for testing
type fakeModule struct {
	name string
	err  error
}

func (fm *fakeModule) Name() string { return fm.name }
func (fm *fakeModule) Init() error { return fm.err }

// testEvent implements Event for testing
type testEvent struct {
	name string
}

func (te *testEvent) Name() string { return te.name }

// flagHandler implements EventHandler for testing
type flagHandler struct {
	called *bool
}

func (fh *flagHandler) Handle(event Event) error {
	*fh.called = true
	return nil
}

func TestFacade_GetModule(t *testing.T) {
	reg := NewRegistry()
	mod := &fakeModule{name: "testmod"}
	reg.Register(mod)
	fd := NewFacade(reg, NewEventDispatcher(), NewSimpleTransactionManager())
	got := fd.GetModule("testmod")
	if got != mod {
		t.Errorf("GetModule() = %v, want %v", got, mod)
	}
}

func TestFacade_DispatchEvent(t *testing.T) {
	ed := NewEventDispatcher()
	called := false
	handler := &flagHandler{called: &called}
	ed.Subscribe("test", handler)
	fd := NewFacade(NewRegistry(), ed, NewSimpleTransactionManager())
	ev := &testEvent{name: "test"}
	if err := fd.DispatchEvent(ev); err != nil {
		t.Errorf("DispatchEvent() error = %v", err)
	}
	if !called {
		t.Error("Handler not called via DispatchEvent")
	}
}

func TestFacade_BeginTransaction(t *testing.T) {
	fd := NewFacade(NewRegistry(), NewEventDispatcher(), NewSimpleTransactionManager())
	tx, err := fd.BeginTransaction()
	if err != nil {
		t.Fatalf("BeginTransaction() error = %v", err)
	}
	if tx == nil {
		t.Error("BeginTransaction() returned nil transaction")
	}
	// Basic operation on transaction should succeed
	if err := tx.Commit(); err != nil {
		t.Errorf("Transaction Commit() error = %v", err)
	}
}
