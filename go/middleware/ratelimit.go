package middleware

import (
	"net/http"
	"sync"
	"time"
)

type RateLimiter struct {
	tokens     map[string]int
	lastUpdate map[string]time.Time
	maxTokens  int
	refillRate time.Duration
	mu         sync.RWMutex
}

func NewRateLimiter(maxTokens int, refillRate time.Duration) *RateLimiter {
	return &RateLimiter{
		tokens:     make(map[string]int),
		lastUpdate: make(map[string]time.Time),
		maxTokens:  maxTokens,
		refillRate: refillRate,
	}
}

func (rl *RateLimiter) Allow(key string) bool {
	rl.mu.Lock()
	defer rl.mu.Unlock()

	now := time.Now()
	
	if _, exists := rl.tokens[key]; !exists {
		rl.tokens[key] = rl.maxTokens
		rl.lastUpdate[key] = now
	}

	elapsed := now.Sub(rl.lastUpdate[key])
	tokensToAdd := int(elapsed / rl.refillRate)
	
	if tokensToAdd > 0 {
		rl.tokens[key] = min(rl.maxTokens, rl.tokens[key]+tokensToAdd)
		rl.lastUpdate[key] = now
	}

	if rl.tokens[key] > 0 {
		rl.tokens[key]--
		return true
	}

	return false
}

func min(a, b int) int {
	if a < b {
		return a
	}
	return b
}

func RateLimitMiddleware(limiter *RateLimiter) func(http.Handler) http.Handler {
	return func(next http.Handler) http.Handler {
		return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
			key := r.RemoteAddr
			
			if !limiter.Allow(key) {
				w.Header().Set("Content-Type", "application/json")
				w.WriteHeader(http.StatusTooManyRequests)
				w.Write([]byte(`{"error": "rate limit exceeded", "message": "Too many requests"}`))
				return
			}

			next.ServeHTTP(w, r)
		})
	}
}
