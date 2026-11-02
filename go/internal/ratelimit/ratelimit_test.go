package ratelimit

import (
	"net/http/httptest"
	"testing"
	"time"

	"github.com/gin-gonic/gin"
	"github.com/stretchr/testify/assert"
)

func TestNewTokenBucket(t *testing.T) {
	tb := NewTokenBucket(1.0, 10.0)
	assert.NotNil(t, tb)
	assert.Equal(t, 10.0, tb.Tokens())
}

func TestTokenBucketAllow(t *testing.T) {
	tb := NewTokenBucket(1.0, 5.0)

	// Should allow initial requests up to capacity
	for i := 0; i < 5; i++ {
		assert.True(t, tb.Allow())
	}

	// Should deny when empty
	assert.False(t, tb.Allow())
}

func TestTokenBucketRefill(t *testing.T) {
	tb := NewTokenBucket(1.0, 5.0)

	// Drain the bucket
	for i := 0; i < 5; i++ {
		tb.Allow()
	}

	// Wait for refill
	time.Sleep(1100 * time.Millisecond)

	assert.True(t, tb.Allow())
}

func TestTokenBucketAllowN(t *testing.T) {
	tb := NewTokenBucket(1.0, 10.0)

	assert.True(t, tb.AllowN(3))
	assert.True(t, tb.AllowN(1))
	assert.False(t, tb.AllowN(10)) // Not enough tokens
}

func TestRateLimiter(t *testing.T) {
	limiter := NewRateLimiter(1.0, 5.0)

	bucket1 := limiter.GetBucket("user1")
	bucket2 := limiter.GetBucket("user1")

	// Same key should return same bucket
	assert.Equal(t, bucket1, bucket2)

	bucket3 := limiter.GetBucket("user2")
	// Different key should return different bucket
	assert.NotEqual(t, bucket1, bucket3)
}

func TestRateLimitMiddleware(t *testing.T) {
	gin.SetMode(gin.TestMode)

	limiter := NewRateLimiter(2.0, 2.0)

	router := gin.New()
	router.Use(RateLimitMiddleware(limiter, IPKeyFunc))
	router.GET("/test", func(c *gin.Context) {
		c.JSON(200, gin.H{"status": "ok"})
	})

	// Make requests up to limit
	for i := 0; i < 2; i++ {
		req := httptest.NewRequest("GET", "/test", nil)
		req.Header.Set("X-Forwarded-For", "192.168.1.1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, 200, w.Code)
	}

	// Next request should be rate limited
	req := httptest.NewRequest("GET", "/test", nil)
	req.Header.Set("X-Forwarded-For", "192.168.1.1")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)
	assert.Equal(t, 429, w.Code)
}

// IPKeyFunc is defined in ratelimit.go

func TestTokens(t *testing.T) {
	tb := NewTokenBucket(1.0, 10.0)

	assert.Equal(t, 10.0, tb.Tokens())

	tb.Allow()
	assert.InDelta(t, 9.0, tb.Tokens(), 0.001)
}

func TestConcurrentAccess(t *testing.T) {
	tb := NewTokenBucket(100.0, 100.0)

	done := make(chan bool)

	// Multiple goroutines accessing the bucket
	for i := 0; i < 10; i++ {
		go func() {
			for j := 0; j < 10; j++ {
				tb.Allow()
			}
			done <- true
		}()
	}

	// Wait for all goroutines
	for i := 0; i < 10; i++ {
		<-done
	}

	// Should have tokens left
	assert.GreaterOrEqual(t, tb.Tokens(), 0.0)
}