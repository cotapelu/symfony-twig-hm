package middleware

import (
	"fmt"
	"net/http"
	"strconv"
	"sync"
	"time"

	"go.uber.org/zap"

	"community.local/platform/logger"
)

// RateLimiterConfig holds configuration for rate limiting
type RateLimiterConfig struct {
	RedisEnabled  bool   // Whether to use Redis for distributed rate limiting
	LocalStorage  bool   // Whether to use in-memory storage
	SlidingWindow bool   // Whether to use sliding window or token bucket algorithm
	RedisAddr     string // Redis server address (e.g., "localhost:6379")
	RedisPassword string // Redis password
	RedisDB       int    // Redis database number
}

// RateLimiter implements rate limiting using configurable algorithm
// RateLimiter implements rate limiting using configurable algorithm
type RateLimiter struct {
	config RateLimiterConfig
	// Storage for in-memory rate limiting
	data sync.Map // map[string]*RateLimitState
	logger *zap.Logger
	mu    sync.RWMutex
}

// RateLimitState holds the state for rate limiting per key
type RateLimitState struct {
	LastReset    time.Time
	AccessCount  int64
	RequestTimes []time.Time // For sliding window algorithm
}

// RateLimitMiddleware creates a new rate limiting middleware
func RateLimitMiddleware(maxRequests int, window time.Duration, config RateLimiterConfig) func(http.Handler) http.Handler {
	if maxRequests <= 0 {
		panic("maxRequests must be positive")
	}

	rl := &RateLimiter{
		config: config,
		logger: logger.Logger,
	}

	// Initialize logger if needed
	if rl.logger == nil {
		rl.logger, _, _ = logger.InitLogger("info")
	}

	return func(next http.Handler) http.Handler {
		return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
			// Get client identifier (IP or API key)
			key := getClientIdentifier(r)

			// Check rate limit
			allowed, err := rl.Allow(key, maxRequests, window)
			if err != nil {
				rl.logger.Error("Rate limit check failed",
					zap.String("error", err.Error()),
					zap.String("key", key),
				)
				// On error, allow the request to avoid breaking the app
				next.ServeHTTP(w, r)
				return
			}

			if !allowed {
				rl.logger.Warn("Rate limit exceeded",
					zap.String("key", key),
					zap.Int("max_requests", maxRequests),
					zap.String("window", window.String()),
					zap.String("remote_addr", r.RemoteAddr),
				)

				// Set rate limit headers
				w.Header().Set("X-RateLimit-Limit", strconv.Itoa(maxRequests))
				w.Header().Set("X-RateLimit-Reset", time.Now().Add(window).Format(http.TimeFormat))
				w.Header().Set("X-RateLimit-Remaining", "0")
				w.Header().Set("X-RateLimit-Policy", "sliding-window")

				http.Error(w, "Rate limit exceeded. Try again later.", http.StatusTooManyRequests)
				return
			}

			// Set rate limit headers
			remaining := maxRequests - rl.getCurrentUsage(key)
			w.Header().Set("X-RateLimit-Limit", strconv.Itoa(maxRequests))
			w.Header().Set("X-RateLimit-Remaining", strconv.Itoa(remaining))
			w.Header().Set("X-RateLimit-Reset", time.Now().Add(window).Format(http.TimeFormat))
			w.Header().Set("X-RateLimit-Policy", "sliding-window")

			next.ServeHTTP(w, r)
		})
	}
}

// getClientIdentifier generates a unique key for rate limiting
func getClientIdentifier(r *http.Request) string {
	// Try API key header first
	if key := r.Header.Get("X-API-Key"); key != "" {
		return fmt.Sprintf("api:%s", key)
	}

	// Fall back to IP address
	ip := r.RemoteAddr
	forwarded := r.Header.Get("X-Forwarded-For")
	if forwarded != "" {
		ip = forwarded
	}
	if ip == "" {
		ip = "unknown"
	}
	return fmt.Sprintf("ip:%s", ip)
}

// Allow checks if a request is allowed under the rate limit
func (rl *RateLimiter) Allow(key string, maxRequests int, window time.Duration) (bool, error) {
	now := time.Now()

	if rl.config.RedisEnabled {
		// TODO: Implement Redis-based distributed rate limiting
		// This would use Redis INCR with expiration
		return true, nil
	}

	// For in-memory rate limiting
	return rl.allowLocal(key, maxRequests, window, now)
}

// allowLocal implements in-memory rate limiting
func (rl *RateLimiter) allowLocal(key string, maxRequests int, window time.Duration, now time.Time) (bool, error) {
	value, _ := rl.data.LoadOrStore(key, &RateLimitState{LastReset: now})
	state := value.(*RateLimitState)

	rl.mu.Lock()
	defer rl.mu.Unlock()

	if rl.config.SlidingWindow {
		// Sliding window algorithm: count requests within the time window
		// Remove old entries
		cutoff := now.Add(-window)
		validTimes := make([]time.Time, 0, len(state.RequestTimes))
		for _, t := range state.RequestTimes {
			if t.After(cutoff) {
				validTimes = append(validTimes, t)
			}
		}
		state.RequestTimes = validTimes
		state.AccessCount = int64(len(validTimes))

		if len(validTimes) >= maxRequests {
			return false, nil
		}

		// Add current request
		state.RequestTimes = append(state.RequestTimes, now)
		return true, nil
	} else {
		// Token bucket / Fixed window algorithm: reset counter on window boundary
		if now.Sub(state.LastReset) > window {
			state.LastReset = now
			state.AccessCount = 0
		}

		if state.AccessCount >= int64(maxRequests) {
			return false, nil
		}

		state.AccessCount++
		return true, nil
	}
}

// getCurrentUsage gets the current usage count
func (rl *RateLimiter) getCurrentUsage(key string) int {
	value, ok := rl.data.Load(key)
	if !ok {
		return 0
	}
	state := value.(*RateLimitState)
	return int(state.AccessCount)
}

// Reset deletes a rate limit state (for testing or manual resets)
func (rl *RateLimiter) Reset(key string) {
	rl.data.Delete(key)
}

// Usage returns current usage statistics
type Usage struct {
	Key            string
	MaxRequests    int
	CurrentUsage   int
	ResetTime      time.Time
	TimeUntilReset time.Duration
}

// GetUsage returns the current usage for a key
func (rl *RateLimiter) GetUsage(key string, maxRequests int, window time.Duration) Usage {
	value, ok := rl.data.Load(key)
	if !ok {
		return Usage{
			Key:            key,
			MaxRequests:    maxRequests,
			CurrentUsage:   0,
			ResetTime:      time.Now().Add(window),
			TimeUntilReset: window,
		}
	}
	state := value.(*RateLimitState)

	return Usage{
		Key:            key,
		MaxRequests:    maxRequests,
		CurrentUsage:   int(state.AccessCount),
		ResetTime:      state.LastReset.Add(window),
		TimeUntilReset: time.Until(state.LastReset.Add(window)),
	}
}

// DefaultMiddleware returns a default rate limiting configuration
func DefaultMiddleware() RateLimiterConfig {
	return RateLimiterConfig{
		LocalStorage:  true,
		SlidingWindow: true,
	}
}
