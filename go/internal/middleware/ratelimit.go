package middleware

import (
	"net/http"
	"sync"
	"time"

	"github.com/gin-gonic/gin"
)

type RateLimiter struct {
	requests map[string][]time.Time
	mu       sync.RWMutex
	limit    int
	window   time.Duration
}

func NewRateLimiter(limit int, window time.Duration) *RateLimiter {
	return &RateLimiter{
		requests: make(map[string][]time.Time),
		limit:    limit,
		window:   window,
	}
}

func (rl *RateLimiter) Limit() gin.HandlerFunc {
	return func(c *gin.Context) {
		ip := c.ClientIP()

		rl.mu.Lock()
		now := time.Now()
		windowStart := now.Add(-rl.window)

		if times, exists := rl.requests[ip]; exists {
			var validTimes []time.Time
			for _, t := range times {
				if t.After(windowStart) {
					validTimes = append(validTimes, t)
				}
			}
			rl.requests[ip] = validTimes
		}

		rl.requests[ip] = append(rl.requests[ip], now)
		count := len(rl.requests[ip])
		rl.mu.Unlock()

		if count > rl.limit {
			c.AbortWithStatusJSON(http.StatusTooManyRequests, gin.H{
				"error": "Rate limit exceeded",
				"limit": rl.limit,
			})
			return
		}

		c.Next()
	}
}

func (rl *RateLimiter) StrictLimit(maxRequests int, per time.Duration) gin.HandlerFunc {
	return NewRateLimiter(maxRequests, per).Limit()
}

var DefaultLimiter = NewRateLimiter(100, time.Minute)

func RateLimit() gin.HandlerFunc {
	return DefaultLimiter.Limit()
}

func RateLimitPayment() gin.HandlerFunc {
	return NewRateLimiter(10, time.Minute).Limit()
}
