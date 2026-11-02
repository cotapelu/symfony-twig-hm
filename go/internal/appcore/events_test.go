package appcore

import (
	"fmt"
	"strings"
	"testing"
)

type mockEvent struct {
	name string
}

func (m *mockEvent) Name() string {
	return m.name
}

type mockHandler struct {
	called bool
	err    error
}

func (m *mockHandler) Handle(event Event) error {
	m.called = true
	return m.err
}

func TestNewEventDispatcher(t *testing.T) {
	ed := NewEventDispatcher()
	if ed == nil {
		t.Fatal("NewEventDispatcher returned nil")
	}
	if ed.handlers == nil {
		t.Error("handlers map not initialized")
	}
}

func TestEventDispatcher_SubscribeAndDispatch(t *testing.T) {
	ed := NewEventDispatcher()
	handler := &mockHandler{}
	ed.Subscribe("test", handler)
	ev := &mockEvent{name: "test"}
	if err := ed.Dispatch(ev); err != nil {
		t.Errorf("Dispatch() error = %v", err)
	}
	if !handler.called {
		t.Error("Handler not called")
	}
}

func TestEventDispatcher_Dispatch_NoHandlers(t *testing.T) {
	ed := NewEventDispatcher()
	ev := &mockEvent{name: "unknown"}
	if err := ed.Dispatch(ev); err != nil {
		t.Errorf("Dispatch() error = %v", err)
	}
}

func TestEventDispatcher_Dispatch_ErrorFromHandler(t *testing.T) {
	ed := NewEventDispatcher()
	handler := &mockHandler{err: fmt.Errorf("handler error")}
	ed.Subscribe("test", handler)
	ev := &mockEvent{name: "test"}
	err := ed.Dispatch(ev)
	if err == nil {
		t.Fatal("Expected error from handler")
	}
	if !strings.Contains(err.Error(), "handler error") {
		t.Errorf("Error message = %v", err)
	}
}

func TestEventDispatcher_MultipleHandlers(t *testing.T) {
	ed := NewEventDispatcher()
	h1 := &mockHandler{}
	h2 := &mockHandler{}
	ed.Subscribe("test", h1)
	ed.Subscribe("test", h2)
	ev := &mockEvent{name: "test"}
	if err := ed.Dispatch(ev); err != nil {
		t.Errorf("Dispatch() error = %v", err)
	}
	if !h1.called || !h2.called {
		t.Error("Not all handlers called")
	}
}

func TestEventDispatcher_StopsOnFirstError(t *testing.T) {
	ed := NewEventDispatcher()
	h1 := &mockHandler{err: fmt.Errorf("first error")}
	h2 := &mockHandler{} // should not be called
	ed.Subscribe("test", h1)
	ed.Subscribe("test", h2)
	ev := &mockEvent{name: "test"}
	err := ed.Dispatch(ev)
	if err == nil {
		t.Fatal("Expected error from first handler")
	}
	if !strings.Contains(err.Error(), "first error") {
		t.Errorf("Error = %v", err)
	}
	if h2.called {
		t.Error("Second handler should not be called after first error")
	}
}
