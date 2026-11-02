package middleware

import (
	"net/http"
	"net/http/httptest"
	"testing"
	"time"

	"github.com/stretchr/testify/assert"
)

func TestNewRateLimiter(t *testing.T) {
	rl := NewRateLimiter(10, time.Second)
	assert.NotNil(t, rl)
	assert.Equal(t, 10, rl.maxTokens)
	assert.Equal(t, time.Second, rl.refillRate)
	assert.Empty(t, rl.tokens)
	assert.Empty(t, rl.lastUpdate)
}

func TestRateLimiter_Allow_Initial(t *testing.T) {
	rl := NewRateLimiter(5, time.Second)

	// First request should be allowed and consume a token
	assert.True(t, rl.Allow("client1"))
	// Remaining tokens should be max-1 (but internal state not exposed, we infer by allowing)
	assert.True(t, rl.Allow("client1"))
	assert.True(t, rl.Allow("client1"))
	assert.True(t, rl.Allow("client1"))
	assert.True(t, rl.Allow("client1"))

	// Exhaust tokens
	assert.False(t, rl.Allow("client1"))
}

func TestRateLimiter_Allow_Refill(t *testing.T) {
	rl := NewRateLimiter(2, 100*time.Millisecond)

	// Use both tokens
	assert.True(t, rl.Allow("client1"))
	assert.True(t, rl.Allow("client1"))
	assert.False(t, rl.Allow("client1"))

	// Wait for refill
	time.Sleep(150 * time.Millisecond)

	// Should be able to get at least one token back
	assert.True(t, rl.Allow("client1"))
}

func TestRateLimiter_Allow_MultipleClients(t *testing.T) {
	rl := NewRateLimiter(3, time.Minute)

	// Each client has its own bucket
	assert.True(t, rl.Allow("clientA"))
	assert.True(t, rl.Allow("clientA"))
	assert.True(t, rl.Allow("clientA"))
	assert.False(t, rl.Allow("clientA"))

	assert.True(t, rl.Allow("clientB")) // separate bucket
	assert.True(t, rl.Allow("clientB"))
	assert.True(t, rl.Allow("clientB"))
	assert.False(t, rl.Allow("clientB"))
}

func TestRateLimitMiddleware_Allowed(t *testing.T) {
	rl := NewRateLimiter(5, time.Minute)
	mw := RateLimitMiddleware(rl)

	// Create a handler that records that it was called
	called := false
	handler := http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		called = true
		w.WriteHeader(http.StatusOK)
	})

	// Create a request
	req := httptest.NewRequest("GET", "/", nil)
	rec := httptest.NewRecorder()

	mw(handler).ServeHTTP(rec, req)

	assert.True(t, called)
	assert.Equal(t, http.StatusOK, rec.Code)
}

func TestRateLimitMiddleware_Denied(t *testing.T) {
	rl := NewRateLimiter(1, time.Minute)
	mw := RateLimitMiddleware(rl)

	handler := http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		w.WriteHeader(http.StatusOK)
	})

	// First request should pass
	req1 := httptest.NewRequest("GET", "/", nil)
	rec1 := httptest.NewRecorder()
	mw(handler).ServeHTTP(rec1, req1)
	assert.Equal(t, http.StatusOK, rec1.Code)

	// Second request from same IP should be rate limited
	req2 := httptest.NewRequest("GET", "/", nil)
	rec2 := httptest.NewRecorder()
	mw(handler).ServeHTTP(rec2, req2)
	assert.Equal(t, http.StatusTooManyRequests, rec2.Code)
}

func TestRateLimiter_RefillMultipleIntervals(t *testing.T) {
	rl := NewRateLimiter(1, 50*time.Millisecond)

	// Use the single token
	assert.True(t, rl.Allow("client"))

	// Exhausted
	assert.False(t, rl.Allow("client"))

	// Wait for a full refill period
	time.Sleep(60 * time.Millisecond)

	// Should be allowed again
	assert.True(t, rl.Allow("client"))
}
