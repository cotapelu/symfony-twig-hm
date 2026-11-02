package eventbus

import (
	"errors"
	"sync"
	"testing"
	"time"

	"community.local/internal/domain"
)

func TestNewEventBus(t *testing.T) {
	eb := NewEventBus()
	if eb == nil {
		t.Fatal("NewEventBus returned nil")
	}
	if eb.handlers == nil {
		t.Error("handlers map not initialized")
	}
}

func TestSubscribe(t *testing.T) {
	eb := NewEventBus()
	handler := func(e *domain.DomainEvent) error { return nil }

	eb.Subscribe(domain.EventMessageSent, handler)

	if len(eb.handlers[domain.EventMessageSent]) != 1 {
		t.Errorf("Expected 1 handler, got %d", len(eb.handlers[domain.EventMessageSent]))
	}
}

func TestPublish_NoHandlers(t *testing.T) {
	eb := NewEventBus()
	event := &domain.DomainEvent{ID: "1", Type: domain.EventMessageSent}

	err := eb.Publish(event)
	if err != nil {
		t.Errorf("Publish() error = %v, want nil", err)
	}
}

func TestPublish_SingleHandler(t *testing.T) {
	eb := NewEventBus()
	called := false
	handler := func(e *domain.DomainEvent) error {
		if e.ID != "test-id" {
			t.Errorf("Expected event ID test-id, got %s", e.ID)
		}
		called = true
		return nil
	}

	eb.Subscribe(domain.EventMessageSent, handler)
	event := &domain.DomainEvent{ID: "test-id", Type: domain.EventMessageSent}

	err := eb.Publish(event)
	if err != nil {
		t.Fatalf("Publish() error = %v", err)
	}
	if !called {
		t.Error("Handler not called")
	}
}

func TestPublish_MultipleHandlers(t *testing.T) {
	eb := NewEventBus()
	var mu sync.Mutex
	calls := 0
	handler := func(e *domain.DomainEvent) error {
		mu.Lock()
		defer mu.Unlock()
		calls++
		return nil
	}

	eb.Subscribe(domain.EventMessageSent, handler)
	eb.Subscribe(domain.EventMessageSent, handler)
	eb.Subscribe(domain.EventMessageSent, handler)

	event := &domain.DomainEvent{ID: "multi", Type: domain.EventMessageSent}
	if err := eb.Publish(event); err != nil {
		t.Fatalf("Publish() error = %v", err)
	}

	mu.Lock()
	defer mu.Unlock()
	if calls != 3 {
		t.Errorf("Expected 3 handler calls, got %d", calls)
	}
}

func TestPublish_HandlerError(t *testing.T) {
	eb := NewEventBus()
	handler := func(e *domain.DomainEvent) error {
		return errors.New("handler failed")
	}

	eb.Subscribe(domain.EventMessageSent, handler)
	event := &domain.DomainEvent{ID: "err", Type: domain.EventMessageSent}

	err := eb.Publish(event)
	if err == nil {
		t.Fatal("Expected error from handler")
	}
	if !contains(err.Error(), "handler failed") {
		t.Errorf("Unexpected error message: %v", err)
	}
}

func TestPublish_MultipleHandlersWithErrors(t *testing.T) {
	eb := NewEventBus()
	handler1 := func(e *domain.DomainEvent) error {
		return errors.New("handler1 error")
	}
	handler2 := func(e *domain.DomainEvent) error {
		return errors.New("handler2 error")
	}
	handler3 := func(e *domain.DomainEvent) error {
		return nil
	}

	eb.Subscribe(domain.EventMessageSent, handler1)
	eb.Subscribe(domain.EventMessageSent, handler2)
	eb.Subscribe(domain.EventMessageSent, handler3)

	event := &domain.DomainEvent{ID: "multi-err", Type: domain.EventMessageSent}
	err := eb.Publish(event)
	if err == nil {
		t.Fatal("Expected error from multiple handlers")
	}
	// Should contain both errors
	if !contains(err.Error(), "handler1 error") || !contains(err.Error(), "handler2 error") {
		t.Errorf("Error should contain both handler errors: %v", err)
	}
}

func contains(s, substr string) bool {
	return len(s) >= len(substr) && (s == substr || len(s) > len(substr) && indexOf(s, substr) != -1)
}

func indexOf(s, substr string) int {
	for i := 0; i <= len(s)-len(substr); i++ {
		if s[i:i+len(substr)] == substr {
			return i
		}
	}
	return -1
}

func TestPublish_PanicHandler(t *testing.T) {
	eb := NewEventBus()
	handler := func(e *domain.DomainEvent) error {
		panic("handler panicked")
	}

	eb.Subscribe(domain.EventMessageSent, handler)
	event := &domain.DomainEvent{ID: "panic", Type: domain.EventMessageSent}

	err := eb.Publish(event)
	if err == nil {
		t.Fatal("Expected error from panicking handler")
	}
	if !contains(err.Error(), "handler panicked") {
		t.Errorf("Unexpected error: %v", err)
	}
}

func TestPublishAsync(t *testing.T) {
	eb := NewEventBus()
	called := make(chan bool, 1)
	handler := func(e *domain.DomainEvent) error {
		called <- true
		return nil
	}

	eb.Subscribe(domain.EventMessageSent, handler)
	event := &domain.DomainEvent{ID: "async", Type: domain.EventMessageSent}

	eb.PublishAsync(event)

	select {
	case <-called:
		// good
	case <-timeoutAfter(100 * time.Millisecond):
		t.Fatal("Handler not called within timeout")
	}
}

// Helper to simulate timeout channel
func timeoutAfter(d time.Duration) <-chan time.Time {
	ch := make(chan time.Time, 1)
	go func() {
		time.Sleep(d)
		ch <- time.Now()
	}()
	return ch
}

func TestConcurrentSubscribeAndPublish(t *testing.T) {
	eb := NewEventBus()
	const numGoros = 100
	var wg sync.WaitGroup

	// Start many publishers
	for i := 0; i < numGoros; i++ {
		wg.Add(1)
		go func(idx int) {
			defer wg.Done()
			event := &domain.DomainEvent{ID: string(rune(idx)), Type: domain.EventMessageSent}
			if err := eb.Publish(event); err != nil && err.Error() != "no handlers" {
				// we may not have subscribed yet; ignore
			}
		}(i)
	}

	// Start subscribers concurrently
	for i := 0; i < 10; i++ {
		wg.Add(1)
		go func() {
			defer wg.Done()
			handler := func(e *domain.DomainEvent) error { return nil }
			eb.Subscribe(domain.EventMessageSent, handler)
		}()
	}

	wg.Wait()
	// If we get here without deadlock or crash, test passes
}
