package middleware

import (
	"net/http"
	"net/http/httptest"
	"strconv"
	"testing"
	"time"

	"go.uber.org/zap"
	"github.com/stretchr/testify/assert"
)

func TestRateLimitMiddleware_SlidingWindow(t *testing.T) {
	config := RateLimiterConfig{
		LocalStorage:  true,
		SlidingWindow: true,
	}
	mw := RateLimitMiddleware(2, 100*time.Millisecond, config)
	handler := http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		w.WriteHeader(http.StatusOK)
	})

	makeReq := func() (int, int) {
		req := httptest.NewRequest("GET", "/", nil)
		rec := httptest.NewRecorder()
		mw(handler).ServeHTTP(rec, req)
		rem, _ := strconv.Atoi(rec.Header().Get("X-RateLimit-Remaining"))
		return rec.Code, rem
	}

	code1, _ := makeReq()
	assert.Equal(t, http.StatusOK, code1)

	code2, _ := makeReq()
	assert.Equal(t, http.StatusOK, code2)

	code3, _ := makeReq()
	assert.Equal(t, http.StatusTooManyRequests, code3)
}

func TestRateLimitMiddleware_TokenBucket(t *testing.T) {
	config := RateLimiterConfig{
		LocalStorage:  true,
		SlidingWindow: false,
	}
	mw := RateLimitMiddleware(3, 50*time.Millisecond, config)
	handler := http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		w.WriteHeader(http.StatusOK)
	})

	makeReq := func() int {
		req := httptest.NewRequest("GET", "/", nil)
		rec := httptest.NewRecorder()
		mw(handler).ServeHTTP(rec, req)
		return rec.Code
	}

	for i := 0; i < 3; i++ {
		assert.Equal(t, http.StatusOK, makeReq())
	}
	assert.Equal(t, http.StatusTooManyRequests, makeReq())

	time.Sleep(60 * time.Millisecond)
	assert.Equal(t, http.StatusOK, makeReq())
}

func TestRateLimiter_GetCurrentUsage(t *testing.T) {
	rl := &RateLimiter{
		config: RateLimiterConfig{LocalStorage: true, SlidingWindow: false},
		logger: zap.NewNop(),
	}
	rl.Allow("client", 3, time.Second)
	rl.Allow("client", 3, time.Second)

	assert.Equal(t, 2, rl.getCurrentUsage("client"))
	assert.Equal(t, 0, rl.getCurrentUsage("unknown"))
}

func TestRateLimiter_Reset(t *testing.T) {
	rl := &RateLimiter{
		config: RateLimiterConfig{LocalStorage: true},
		logger: zap.NewNop(),
	}
	rl.Allow("client", 5, time.Minute)
	rl.Allow("client", 5, time.Minute)

	rl.Reset("client")
	assert.Equal(t, 0, rl.getCurrentUsage("client"))
}

func TestRateLimiter_GetUsage(t *testing.T) {
	rl := &RateLimiter{
		config: RateLimiterConfig{LocalStorage: true, SlidingWindow: false},
		logger: zap.NewNop(),
	}
	max := 10
	window := 2 * time.Second
	rl.Allow("client", max, window)
	rl.Allow("client", max, window)
	rl.Allow("client", max, window) // 3

	usage := rl.GetUsage("client", max, window)
	assert.Equal(t, "client", usage.Key)
	assert.Equal(t, max, usage.MaxRequests)
	assert.Equal(t, 3, usage.CurrentUsage)
	assert.WithinDuration(t, time.Now().Add(window), usage.ResetTime, 100*time.Millisecond)
}

func TestRateLimitMiddleware_MultipleKeys(t *testing.T) {
	config := RateLimiterConfig{LocalStorage: true, SlidingWindow: true}
	mw := RateLimitMiddleware(1, time.Minute, config)
	handler := http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		w.WriteHeader(http.StatusOK)
	})

	makeReqWithAddr := func(addr string) int {
		req := httptest.NewRequest("GET", "/", nil)
		req.RemoteAddr = addr
		rec := httptest.NewRecorder()
		mw(handler).ServeHTTP(rec, req)
		return rec.Code
	}

	assert.Equal(t, http.StatusOK, makeReqWithAddr("192.168.1.1"))
	assert.Equal(t, http.StatusOK, makeReqWithAddr("192.168.1.2"))
	assert.Equal(t, http.StatusTooManyRequests, makeReqWithAddr("192.168.1.1"))
}
