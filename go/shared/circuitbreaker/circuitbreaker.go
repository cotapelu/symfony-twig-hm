package circuitbreaker

import (
	"errors"
	"sync"
	"time"
)

// State represents circuit breaker state
type State int

const (
	StateClosed State = iota
	StateOpen
	StateHalfOpen
)

var (
	ErrCircuitOpen = errors.New("circuit breaker is open")
	ErrMaxRetries  = errors.New("max retries exceeded")
)

// Config holds circuit breaker configuration
type Config struct {
	FailureThreshold int
	Timeout          time.Duration
	MaxRetries       int
}

// CircuitBreaker implements the circuit breaker pattern
type CircuitBreaker struct {
	mu               sync.Mutex
	state            State
	failures         int
	lastFailure      time.Time
	config           Config
	halfOpenSuccesses int
}

// NewCircuitBreaker creates a new circuit breaker
func NewCircuitBreaker(config Config) *CircuitBreaker {
	return &CircuitBreaker{
		state:  StateClosed,
		config: config,
	}
}

// Execute runs the given function with circuit breaker protection
func (cb *CircuitBreaker) Execute(fn func() error) error {
	if !cb.canExecute() {
		return ErrCircuitOpen
	}

	err := fn()
	cb.recordResult(err)
	return err
}

func (cb *CircuitBreaker) canExecute() bool {
	cb.mu.Lock()
	defer cb.mu.Unlock()

	switch cb.state {
	case StateClosed:
		return true
	case StateOpen:
		if time.Since(cb.lastFailure) > cb.config.Timeout {
			cb.state = StateHalfOpen
			cb.halfOpenSuccesses = 0
			return true
		}
		return false
	case StateHalfOpen:
		return true
	default:
		return true
	}
}

func (cb *CircuitBreaker) recordResult(err error) {
	cb.mu.Lock()
	defer cb.mu.Unlock()

	if err != nil {
		cb.failures++
		cb.lastFailure = time.Now()

		if cb.failures >= cb.config.FailureThreshold {
			cb.state = StateOpen
		}
	} else {
		if cb.state == StateHalfOpen {
			cb.halfOpenSuccesses++
			if cb.halfOpenSuccesses >= 2 { // Require 2 successes to close
				cb.state = StateClosed
				cb.failures = 0
			}
		} else if cb.state == StateClosed {
			cb.failures = 0
		}
	}
}

// GetState returns current state
func (cb *CircuitBreaker) GetState() State {
	cb.mu.Lock()
	defer cb.mu.Unlock()
	return cb.state
}

// Default configuration
func DefaultConfig() Config {
	return Config{
		FailureThreshold: 5,
		Timeout:          60 * time.Second,
		MaxRetries:       3,
	}
}