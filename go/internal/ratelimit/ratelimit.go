// Package ratelimit provides token bucket rate limiting
package ratelimit

import (
	"sync"
	"time"

	"github.com/gin-gonic/gin"
)

// TokenBucket implements the token bucket algorithm
type TokenBucket struct {
	mu           sync.Mutex
	rate         float64 // tokens per second
	capacity     float64
	tokens       float64
	lastRefill   time.Time
}

// NewTokenBucket creates a new token bucket
func NewTokenBucket(rate, capacity float64) *TokenBucket {
	return &TokenBucket{
		rate:       rate,
		capacity:   capacity,
		tokens:     capacity,
		lastRefill: time.Now(),
	}
}

// Allow checks if a request is allowed
func (tb *TokenBucket) Allow() bool {
	tb.mu.Lock()
	defer tb.mu.Unlock()
	
	tb.refill()
	
	if tb.tokens >= 1 {
		tb.tokens -= 1
		return true
	}
	return false
}

// AllowN checks if n requests are allowed
func (tb *TokenBucket) AllowN(n int) bool {
	tb.mu.Lock()
	defer tb.mu.Unlock()
	
	tb.refill()
	
	if tb.tokens >= float64(n) {
		tb.tokens -= float64(n)
		return true
	}
	return false
}

// refill adds tokens based on elapsed time
func (tb *TokenBucket) refill() {
	now := time.Now()
	elapsed := now.Sub(tb.lastRefill).Seconds()
	
	tb.tokens += elapsed * tb.rate
	if tb.tokens > tb.capacity {
		tb.tokens = tb.capacity
	}
	
	tb.lastRefill = now
}

// Tokens returns current token count
func (tb *TokenBucket) Tokens() float64 {
	tb.mu.Lock()
	defer tb.mu.Unlock()
	
	tb.refill()
	return tb.tokens
}

// RateLimiter manages multiple token buckets
type RateLimiter struct {
	buckets map[string]*TokenBucket
	mu      sync.RWMutex
	rate    float64
	capacity float64
}

// NewRateLimiter creates a new rate limiter
func NewRateLimiter(rate, capacity float64) *RateLimiter {
	return &RateLimiter{
		buckets: make(map[string]*TokenBucket),
		rate:    rate,
		capacity: capacity,
	}
}

// GetBucket returns or creates a bucket for a key
func (rl *RateLimiter) GetBucket(key string) *TokenBucket {
	rl.mu.Lock()
	defer rl.mu.Unlock()
	
	if bucket, exists := rl.buckets[key]; exists {
		return bucket
	}
	
	bucket := NewTokenBucket(rl.rate, rl.capacity)
	rl.buckets[key] = bucket
	return bucket
}

// RateLimitMiddleware creates a gin middleware for rate limiting
func RateLimitMiddleware(limiter *RateLimiter, keyFunc func(*gin.Context) string) gin.HandlerFunc {
	return func(c *gin.Context) {
		key := keyFunc(c)
		bucket := limiter.GetBucket(key)
		
		if !bucket.Allow() {
			c.Header("X-RateLimit-Remaining", "0")
			c.Header("Retry-After", "1")
			c.AbortWithStatusJSON(429, gin.H{
				"error": "Rate limit exceeded",
			})
			return
		}
		
		c.Header("X-RateLimit-Remaining", "1")
		c.Next()
	}
}

// IPKeyFunc returns IP address as rate limit key
func IPKeyFunc(c *gin.Context) string {
	return c.ClientIP()
}

// UserIDKeyFunc returns user ID as rate limit key (requires auth middleware first)
func UserIDKeyFunc(c *gin.Context) string {
	if userID, exists := c.Get("user_id"); exists {
		return userID.(string)
	}
	return c.ClientIP()
}