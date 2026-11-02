package middleware

import (
	"context"
	"fmt"
	"math"
	"net/http"
	"time"

	"github.com/gin-gonic/gin"
)

// RetryConfig holds retry middleware configuration
type RetryConfig struct {
	MaxRetries      int
	BaseDelay       time.Duration
	MaxDelay        time.Duration
	BackoffFactor   float64
	Jitter          bool
	RetryableCodes  []int
	RetryableErrors []string
}

// DefaultRetryConfig returns default retry configuration
func DefaultRetryConfig() RetryConfig {
	return RetryConfig{
		MaxRetries:     3,
		BaseDelay:      100 * time.Millisecond,
		MaxDelay:       5 * time.Second,
		BackoffFactor:  2.0,
		Jitter:         true,
		RetryableCodes: []int{429, 500, 502, 503, 504},
		RetryableErrors: []string{
			"connection refused",
			"connection reset",
			"timeout",
			"temporary failure",
			"deadlock",
			"ECONNREFUSED",
			"ECONNRESET",
			"ETIMEDOUT",
		},
	}
}

// RetryMiddleware creates a Gin handler that retries failed requests
func RetryMiddleware(config RetryConfig) gin.HandlerFunc {
	// Apply defaults
	if config.MaxRetries <= 0 {
		config.MaxRetries = 3
	}
	if config.BaseDelay <= 0 {
		config.BaseDelay = 100 * time.Millisecond
	}
	if config.MaxDelay <= 0 {
		config.MaxDelay = 5 * time.Second
	}
	if config.BackoffFactor <= 1.0 {
		config.BackoffFactor = 2.0
	}

	return func(c *gin.Context) {
		// Only retry idempotent methods by default
		if !isIdempotentMethod(c.Request.Method) && !config.retryNonIdempotent() {
			c.Next()
			return
		}

		var lastErr error
		var statusCode int

		for attempt := 0; attempt <= config.MaxRetries; attempt++ {
			// Create a new response writer that we can inspect
			tw := &retryResponseWriter{
				ResponseWriter: c.Writer,
			}
			c.Writer = tw

			// Create a context with timeout for this attempt
			ctx, cancel := context.WithTimeout(c.Request.Context(), 30*time.Second); defer cancel()
			c.Request = c.Request.WithContext(ctx)

			// Reset panic channel for each attempt
			panicChan := make(chan interface{}, 1)

			go func() {
				defer func() {
					if p := recover(); p != nil {
						panicChan <- p
					}
				}()
				c.Next()
			}()

			select {
			case p := <-panicChan:
				panic(p)
			case <-c.Request.Context().Done():
				lastErr = c.Request.Context().Err()
			default:
				// Normal completion
			}

			statusCode = tw.Status()

			// Check if we should retry
			if !shouldRetry(statusCode, lastErr, config) {
				break
			}

			// Don't retry on last attempt
			if attempt == config.MaxRetries {
				break
			}

			// Calculate delay with exponential backoff
			delay := calculateDelay(attempt, config)

			// Log retry attempt
			if config.RetryableCodes != nil || config.RetryableErrors != nil {
				fmt.Printf("Retry attempt %d/%d after %v: status=%d, err=%v\n",
					attempt+1, config.MaxRetries, delay, statusCode, lastErr)
			}

			// Wait before retry
			select {
			case <-time.After(delay):
				// Continue to next attempt
			case <-c.Request.Context().Done():
				return // Context cancelled
			}

			// Reset writer for next attempt
			c.Writer = &retryResponseWriter{ResponseWriter: c.Writer}
		}

		// If we've exhausted retries and still have an error, return it
		if lastErr != nil || (statusCode >= 500 && statusCode < 600) {
			if !c.Writer.Written() {
				if lastErr != nil {
					c.AbortWithStatusJSON(http.StatusServiceUnavailable, gin.H{
						"error":   "service unavailable after retries",
						"message": lastErr.Error(),
						"retries": config.MaxRetries,
					})
				} else {
					c.AbortWithStatus(statusCode)
				}
			}
		}
	}
}

// retryResponseWriter wraps gin.ResponseWriter to capture status
type retryResponseWriter struct {
	gin.ResponseWriter
	statusCode int
}

func (w *retryResponseWriter) WriteHeader(statusCode int) {
	w.statusCode = statusCode
	w.ResponseWriter.WriteHeader(statusCode)
}

func (w *retryResponseWriter) Status() int {
	if w.statusCode == 0 {
		return http.StatusOK
	}
	return w.statusCode
}

// isIdempotentMethod checks if HTTP method is idempotent
func isIdempotentMethod(method string) bool {
	switch method {
	case http.MethodGet, http.MethodHead, http.MethodPut, http.MethodDelete, http.MethodOptions, http.MethodTrace:
		return true
	default:
		return false
	}
}

// retryNonIdempotent is a placeholder for config option to retry non-idempotent
func (r RetryConfig) retryNonIdempotent() bool {
	// This could be a config option, for now default to false
	return false
}

// shouldRetry determines if a request should be retried
func shouldRetry(statusCode int, err error, config RetryConfig) bool {
	// Check retryable status codes
	for _, code := range config.RetryableCodes {
		if statusCode == code {
			return true
		}
	}

	// Check retryable errors
	if err != nil {
		errStr := err.Error()
		for _, retryErr := range config.RetryableErrors {
			if containsIgnoreCase(errStr, retryErr) {
				return true
			}
		}
	}

	return false
}

// calculateDelay calculates delay with exponential backoff and optional jitter
func calculateDelay(attempt int, config RetryConfig) time.Duration {
	delay := float64(config.BaseDelay) * math.Pow(config.BackoffFactor, float64(attempt))
	if delay > float64(config.MaxDelay) {
		delay = float64(config.MaxDelay)
	}

	if config.Jitter {
		// Add jitter: ±25%
		jitter := delay * 0.25 * (2*float64(time.Now().UnixNano()%1000)/1000 - 1)
		delay += jitter
	}

	return time.Duration(delay)
}

func containsIgnoreCase(s, substr string) bool {
	sLower := toLower(s)
	substrLower := toLower(substr)
	return len(sLower) >= len(substrLower) && (sLower == substrLower || len(sLower) > len(substrLower) && (sLower[:len(substrLower)] == substrLower || sLower[len(sLower)-len(substrLower):] == substrLower || containsSubstring(sLower, substrLower)))
}

func toLower(s string) string {
	result := make([]byte, len(s))
	for i := 0; i < len(s); i++ {
		c := s[i]
		if c >= 'A' && c <= 'Z' {
			result[i] = c + 32
		} else {
			result[i] = c
		}
	}
	return string(result)
}

func containsSubstring(s, substr string) bool {
	for i := 0; i <= len(s)-len(substr); i++ {
		if s[i:i+len(substr)] == substr {
			return true
		}
	}
	return false
}