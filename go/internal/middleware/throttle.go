package middleware

import (
	"net/http"
	"sync"
	"time"

	"github.com/gin-gonic/gin"
)

// ThrottleMiddleware implements request throttling using the leaky bucket algorithm
// This smooths out request traffic by allowing bursts up to a limit, then processing
// requests at a steady rate
type ThrottleMiddleware struct {
	rate     float64 // requests per second
	capacity int     // maximum burst size
	mu       sync.RWMutex
	lastReq  map[string]time.Time
	tokens   map[string]float64
}

// NewThrottleMiddleware creates a new throttle middleware
func NewThrottleMiddleware(ratePerSecond float64, burstSize int) *ThrottleMiddleware {
	return &ThrottleMiddleware{
		rate:     ratePerSecond,
		capacity: burstSize,
		lastReq:  make(map[string]time.Time),
		tokens:   make(map[string]float64),
	}
}

// Throttle returns a gin handler function that throttles requests
func (tm *ThrottleMiddleware) Throttle() gin.HandlerFunc {
	return func(c *gin.Context) {
		// Use IP + user_id (if authenticated) for throttling
		key := c.ClientIP()
		if userID, exists := c.Get("user_id"); exists {
			key = key + "|" + userID.(string)
		}

		tm.mu.Lock()
		now := time.Now()

		// Initialize tokens for new clients
		if _, exists := tm.tokens[key]; !exists {
			tm.tokens[key] = float64(tm.capacity)
			tm.lastReq[key] = now
		}

		// Calculate time since last request
		elapsed := now.Sub(tm.lastReq[key]).Seconds()

		// Add tokens based on elapsed time and rate
		tm.tokens[key] = tm.tokens[key] + (elapsed * tm.rate)

		// Cap tokens at maximum capacity
		if tm.tokens[key] > float64(tm.capacity) {
			tm.tokens[key] = float64(tm.capacity)
		}

		// Check if we have enough tokens to process this request
		if tm.tokens[key] < 1.0 {
			tm.mu.Unlock()
			c.AbortWithStatusJSON(http.StatusTooManyRequests, gin.H{
				"error":   "Throttle limit exceeded",
				"message": "Too many requests, please try again later",
			})
			return
		}

		// Consume one token
		tm.tokens[key]--
		tm.lastReq[key] = now
		tm.mu.Unlock()

		// Continue to the next handler
		c.Next()
	}
}
