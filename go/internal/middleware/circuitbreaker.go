package middleware

import (
	"errors"
	"sync"
	"time"
)

var (
	ErrCircuitOpen     = errors.New("circuit breaker is open")
	ErrTooManyRequests = errors.New("too many requests")
)

type CircuitState int

const (
	StateClosed CircuitState = iota
	StateOpen
	StateHalfOpen
)

func (s CircuitState) String() string {
	switch s {
	case StateClosed:
		return "closed"
	case StateOpen:
		return "open"
	case StateHalfOpen:
		return "half-open"
	default:
		return "unknown"
	}
}

type CircuitBreaker struct {
	name            string
	state           CircuitState
	failures        int
	successes       int
	requestCount    int
	lastStateChange time.Time
	mu              sync.RWMutex

	maxRequests      int
	failureThreshold int
	successThreshold int
	timeout          time.Duration
	readyToTrip      func(failures, successes, requests int) bool
}

type CircuitBreakerOption func(*CircuitBreaker)

func WithMaxRequests(n int) CircuitBreakerOption {
	return func(cb *CircuitBreaker) {
		cb.maxRequests = n
	}
}

func WithFailureThreshold(n int) CircuitBreakerOption {
	return func(cb *CircuitBreaker) {
		cb.failureThreshold = n
	}
}

func WithSuccessThreshold(n int) CircuitBreakerOption {
	return func(cb *CircuitBreaker) {
		cb.successThreshold = n
	}
}

func WithTimeout(d time.Duration) CircuitBreakerOption {
	return func(cb *CircuitBreaker) {
		cb.timeout = d
	}
}

func NewCircuitBreaker(name string, opts ...CircuitBreakerOption) *CircuitBreaker {
	cb := &CircuitBreaker{
		name:             name,
		state:            StateClosed,
		maxRequests:      100,
		failureThreshold: 5,
		successThreshold: 2,
		timeout:          30 * time.Second,
		readyToTrip: func(failures, successes, requests int) bool {
			return failures >= 5
		},
	}

	for _, opt := range opts {
		opt(cb)
	}

	return cb
}

func (cb *CircuitBreaker) Execute(fn func() error) error {
	if err := cb.beforeRequest(); err != nil {
		return err
	}

	err := fn()
	cb.afterRequest(err == nil)
	return err
}

func (cb *CircuitBreaker) beforeRequest() error {
	cb.mu.Lock()
	state := cb.state
	cb.requestCount++
	// If state is open, check if timeout has elapsed to transition to half-open
	if state == StateOpen {
		if time.Since(cb.lastStateChange) > cb.timeout {
			cb.setState(StateHalfOpen)
			state = StateHalfOpen
		}
	}
	cb.mu.Unlock()

	if state == StateOpen {
		return ErrCircuitOpen
	}
	return nil
}

func (cb *CircuitBreaker) afterRequest(success bool) {
	cb.mu.Lock()
	defer cb.mu.Unlock()

	if success {
		cb.successes++
		cb.failures = 0
	} else {
		cb.failures++
	}

	cb.requestCount++

	switch cb.state {
	case StateClosed:
		if cb.readyToTrip(cb.failures, cb.successes, cb.requestCount) {
			cb.setState(StateOpen)
		}
	case StateHalfOpen:
		if success {
			cb.successes++
			if cb.successes >= cb.successThreshold {
				cb.setState(StateClosed)
				cb.failures = 0
				cb.successes = 0
			}
		} else {
			cb.setState(StateOpen)
		}
	}

	if cb.state == StateOpen && time.Since(cb.lastStateChange) > cb.timeout {
		cb.setState(StateHalfOpen)
	}
}

func (cb *CircuitBreaker) setState(state CircuitState) {
	if cb.state == state {
		return
	}

	cb.lastStateChange = time.Now()
	cb.state = state
}

func (cb *CircuitBreaker) State() CircuitState {
	cb.mu.RLock()
	defer cb.mu.RUnlock()
	return cb.state
}

func (cb *CircuitBreaker) Counts() (failures, successes, requests int) {
	cb.mu.RLock()
	defer cb.mu.RUnlock()
	return cb.failures, cb.successes, cb.requestCount
}
