package middleware

import (
	"errors"
	"sync"
	"testing"
	"time"
)

func TestNewCircuitBreaker(t *testing.T) {
	cb := NewCircuitBreaker("test")
	if cb == nil {
		t.Fatal("NewCircuitBreaker returned nil")
	}
	if cb.State() != StateClosed {
		t.Errorf("Initial state = %v, want %v", cb.State(), StateClosed)
	}
}

func TestCircuitBreaker_Execute_Success(t *testing.T) {
	cb := NewCircuitBreaker("test")
	err := cb.Execute(func() error { return nil })
	if err != nil {
		t.Errorf("Execute() error = %v", err)
	}
	if cb.State() != StateClosed {
		t.Errorf("State after success = %v, want closed", cb.State())
	}
}

func TestCircuitBreaker_Execute_Failure(t *testing.T) {
	cb := NewCircuitBreaker("test")
	err := cb.Execute(func() error { return errors.New("fail") })
	if err == nil {
		t.Fatal("Expected error from fn")
	}
	if cb.State() != StateClosed {
		t.Errorf("State after single failure = %v, want closed", cb.State())
	}
	f, s, _ := cb.Counts()
	if f != 1 {
		t.Errorf("Failures count = %d, want 1", f)
	}
	if s != 0 {
		t.Errorf("Successes count = %d, want 0", s)
	}
}

func TestCircuitBreaker_TripToOpen(t *testing.T) {
	cb := NewCircuitBreaker("test")
	for i := 0; i < 5; i++ {
		cb.Execute(func() error { return errors.New("fail") })
	}
	if cb.State() != StateOpen {
		t.Errorf("State after 5 failures = %v, want open", cb.State())
	}
}

func TestCircuitBreaker_Execute_Open(t *testing.T) {
	cb := NewCircuitBreaker("test")
	// Trip to open
	for i := 0; i < 5; i++ {
		cb.Execute(func() error { return errors.New("fail") })
	}
	err := cb.Execute(func() error { return nil })
	if !errors.Is(err, ErrCircuitOpen) {
		t.Errorf("Expected ErrCircuitOpen, got %v", err)
	}
	if cb.State() != StateOpen {
		t.Errorf("State after Execute while open = %v, want open", cb.State())
	}
}

func TestCircuitBreaker_HalfOpenAfterTimeout(t *testing.T) {
	cb := NewCircuitBreaker("test", WithTimeout(2*time.Millisecond))
	// Trip to open
	for i := 0; i < 5; i++ {
		cb.Execute(func() error { return errors.New("fail") })
	}
	if cb.State() != StateOpen {
		t.Fatal("Precondition: state should be open")
	}
	// Wait for timeout to elapse
	time.Sleep(3 * time.Millisecond)
	// This request should transition to half-open and succeed, closing the circuit
	cb.Execute(func() error { return nil })
	// After a successful half-open request, circuit should be closed
	if cb.State() != StateClosed {
		t.Errorf("State after half-open success = %v, want closed", cb.State())
	}
}

func TestCircuitBreaker_HalfOpen_FailureResetsOpen(t *testing.T) {
	cb := NewCircuitBreaker("test", WithTimeout(2*time.Millisecond))
	// Trip to open
	for i := 0; i < 5; i++ {
		cb.Execute(func() error { return errors.New("fail") })
	}
	// Wait for timeout
	time.Sleep(3 * time.Millisecond)
	// In half-open, a failure should revert to open
	cb.Execute(func() error { return errors.New("fail") })
	if cb.State() != StateOpen {
		t.Errorf("State after half-open failure = %v, want open", cb.State())
	}
}

func TestCircuitBreaker_Counts(t *testing.T) {
	cb := NewCircuitBreaker("test")
	cb.Execute(func() error { return nil })
	cb.Execute(func() error { return errors.New("fail") })
	f, s, r := cb.Counts()
	// Note: requestCount increments twice per Execute (beforeRequest + afterRequest)
	// So 2 Execute -> requestCount = 4
	if f != 1 {
		t.Errorf("Failures = %d, want 1", f)
	}
	if s != 1 {
		t.Errorf("Successes = %d, want 1", s)
	}
	if r != 4 {
		t.Errorf("Requests = %d, want 4", r)
	}
}

func TestCircuitBreaker_StringState(t *testing.T) {
	if StateClosed.String() != "closed" {
		t.Error("Closed.String() incorrect")
	}
	if StateOpen.String() != "open" {
		t.Error("Open.String() incorrect")
	}
	if StateHalfOpen.String() != "half-open" {
		t.Error("HalfOpen.String() incorrect")
	}
}

func TestCircuitBreaker_ConcurrentExecute(t *testing.T) {
	cb := NewCircuitBreaker("test")
	var wg sync.WaitGroup
	// Mixed successes and failures; successes reset failures so circuit remains closed
	for i := 0; i < 50; i++ {
		wg.Add(1)
		go func(idx int) {
			defer wg.Done()
			cb.Execute(func() error {
				if idx%2 == 0 {
					return nil
				}
				return errors.New("fail")
			})
		}(i)
	}
	wg.Wait()
	// No crash; state should remain closed due to interleaved successes
	if state := cb.State(); state != StateClosed {
		t.Errorf("State after mixed concurrent calls = %v, want closed", state)
	}
}
