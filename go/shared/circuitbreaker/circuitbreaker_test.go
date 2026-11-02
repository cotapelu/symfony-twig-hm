package circuitbreaker

import (
	"errors"
	"testing"
	"time"

	"github.com/stretchr/testify/assert"
)

func TestNewCircuitBreaker(t *testing.T) {
	cfg := Config{FailureThreshold: 3, Timeout: 5 * time.Second}
	cb := NewCircuitBreaker(cfg)
	assert.Equal(t, StateClosed, cb.GetState())
}

func TestExecute_Success(t *testing.T) {
	cfg := Config{FailureThreshold: 3, Timeout: 5 * time.Second}
	cb := NewCircuitBreaker(cfg)

	err := cb.Execute(func() error {
		return nil
	})
	assert.NoError(t, err)
	assert.Equal(t, StateClosed, cb.GetState())
}

func TestExecute_Failure(t *testing.T) {
	cfg := Config{FailureThreshold: 2, Timeout: 1 * time.Second}
	cb := NewCircuitBreaker(cfg)

	// First failure
	err := cb.Execute(func() error {
		return errors.New("fail")
	})
	assert.Error(t, err)
	assert.Equal(t, StateClosed, cb.GetState()) // still closed, not reached threshold

	// Second failure should trip open
	err = cb.Execute(func() error {
		return errors.New("fail")
	})
	assert.Error(t, err)
	assert.Equal(t, StateOpen, cb.GetState())
}

func TestExecute_OpensAndRecovers(t *testing.T) {
	// Use zero timeout so transition to half-open is immediate
	cfg := Config{FailureThreshold: 1, Timeout: 0, MaxRetries: 3}
	cb := NewCircuitBreaker(cfg)

	// One failure opens circuit
	cb.Execute(func() error {
		return errors.New("fail")
	})
	assert.Equal(t, StateOpen, cb.GetState())

	// Next execute should transition to half-open because timeout=0
	err := cb.Execute(func() error {
		return nil
	})
	assert.NoError(t, err)
	// After one success in half-open, still half-open (needs 2 successes)
	assert.Equal(t, StateHalfOpen, cb.GetState())

	// Second success should close circuit
	err = cb.Execute(func() error {
		return nil
	})
	assert.NoError(t, err)
	assert.Equal(t, StateClosed, cb.GetState())
}

func TestExecute_HalfOpenFailure(t *testing.T) {
	cfg := Config{FailureThreshold: 1, Timeout: 0}
	cb := NewCircuitBreaker(cfg)

	// Open circuit
	cb.Execute(func() error {
		return errors.New("fail")
	})
	assert.Equal(t, StateOpen, cb.GetState())

	// First attempt after open goes to half-open
	cb.Execute(func() error {
		return nil
	})
	assert.Equal(t, StateHalfOpen, cb.GetState())

	// Failure in half-open reopens
	cb.Execute(func() error {
		return errors.New("fail")
	})
	assert.Equal(t, StateOpen, cb.GetState())
}

func TestDefaultConfig(t *testing.T) {
	cfg := DefaultConfig()
	assert.Equal(t, 5, cfg.FailureThreshold)
	assert.Equal(t, 60*time.Second, cfg.Timeout)
	assert.Equal(t, 3, cfg.MaxRetries)
}
