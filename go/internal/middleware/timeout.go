package middleware

import (
	"context"
	"fmt"
	"net/http"
	"sync"
	"sync/atomic"
	"time"

	"github.com/gin-gonic/gin"
	"go.opentelemetry.io/otel/trace"
)

// TimeoutConfig holds timeout middleware configuration
type TimeoutConfig struct {
	Timeout time.Duration
}

// DefaultTimeoutConfig returns default timeout configuration
func DefaultTimeoutConfig() TimeoutConfig {
	return TimeoutConfig{
		Timeout: 30 * time.Second,
	}
}

// timeoutResponseWriter wraps http.ResponseWriter to prevent writes after timeout
type timeoutResponseWriter struct {
	gin.ResponseWriter
	underlying    http.ResponseWriter
	mu            sync.Mutex
	headerWritten int32
	bodyWritten   int32
	timeoutFired  int32
}

func (w *timeoutResponseWriter) WriteHeader(statusCode int) {
	w.mu.Lock()
	defer w.mu.Unlock()

	timeoutFired := atomic.LoadInt32(&w.timeoutFired)
	headerWritten := atomic.LoadInt32(&w.headerWritten)

	if timeoutFired == 1 && headerWritten == 0 {
		w.underlying.WriteHeader(statusCode)
		atomic.StoreInt32(&w.headerWritten, 1)
		return
	}

	if headerWritten == 0 && timeoutFired == 0 {
		w.underlying.WriteHeader(statusCode)
		atomic.StoreInt32(&w.headerWritten, 1)
	}
}

func (w *timeoutResponseWriter) Write(data []byte) (int, error) {
	w.mu.Lock()
	defer w.mu.Unlock()

	timeoutFired := atomic.LoadInt32(&w.timeoutFired)
	bodyWritten := atomic.LoadInt32(&w.bodyWritten)

	if timeoutFired == 1 && bodyWritten == 0 {
		n, err := w.underlying.Write(data)
		if n > 0 {
			atomic.StoreInt32(&w.bodyWritten, 1)
		}
		return n, err
	}

	if bodyWritten == 0 && timeoutFired == 0 {
		n, err := w.underlying.Write(data)
		if n > 0 {
			atomic.StoreInt32(&w.bodyWritten, 1)
		}
		return n, err
	}
	return 0, nil
}

func (w *timeoutResponseWriter) Written() bool {
	return atomic.LoadInt32(&w.bodyWritten) == 1
}

// TimeoutMiddleware creates a Gin handler that enforces request timeout
func TimeoutMiddleware(config TimeoutConfig) gin.HandlerFunc {
	return func(c *gin.Context) {
		// Create timeout context
		ctx, cancel := context.WithTimeout(c.Request.Context(), config.Timeout)
		defer cancel()

		// Propagate X-Request-ID if present
		requestID := c.GetHeader("X-Request-ID")
		if requestID == "" {
			// Generate request ID from trace context if available
			if span := trace.SpanFromContext(ctx); span.SpanContext().IsValid() {
				requestID = span.SpanContext().TraceID().String()
			}
		}
		if requestID != "" {
			ctx = context.WithValue(ctx, requestIDKey{}, requestID)
			c.Header("X-Request-ID", requestID)
		}

		// Propagate X-Trace-ID if present
		traceID := c.GetHeader("X-Trace-ID")
		if traceID == "" {
			if span := trace.SpanFromContext(ctx); span.SpanContext().IsValid() {
				traceID = span.SpanContext().TraceID().String()
			}
		}
		if traceID != "" {
			ctx = context.WithValue(ctx, traceIDKey{}, traceID)
			c.Header("X-Trace-ID", traceID)
		}

		// Replace request context
		c.Request = c.Request.WithContext(ctx)

		// Wrap the response writer to prevent writes after timeout
		// Store the original writer before wrapping
		originalWriter := c.Writer
		tw := &timeoutResponseWriter{
			ResponseWriter: originalWriter,
			underlying:     originalWriter,
		}
		c.Writer = tw

		// Create channel to track completion
		done := make(chan struct{})
		panicChan := make(chan interface{}, 1)

		go func() {
			defer func() {
				if p := recover(); p != nil {
					panicChan <- p
				}
				close(done)
			}()
			c.Next()
			// Mark as written after handler completes (in case handler didn't write)
			atomic.StoreInt32(&tw.bodyWritten, 1)
			atomic.StoreInt32(&tw.headerWritten, 1)
		}()

		select {
		case <-done:
			// Check for panic
			select {
			case p := <-panicChan:
				panic(p)
			default:
				// Normal completion
			}
		case <-ctx.Done():
			// Timeout occurred
			// Only write timeout response if handler hasn't written yet
			if !tw.Written() {
				atomic.StoreInt32(&tw.timeoutFired, 1)
				tw.Header().Set("X-Timeout", "true")
				c.AbortWithStatusJSON(http.StatusGatewayTimeout, gin.H{
					"error":   "request timeout",
					"message": fmt.Sprintf("Request exceeded %s timeout", config.Timeout),
				})
			}
		}
	}
}

// Context key types for request ID and trace ID
type requestIDKey struct{}
type traceIDKey struct{}

// GetRequestIDFromContext extracts request ID from context
func GetRequestIDFromContext(ctx context.Context) string {
	if v := ctx.Value(requestIDKey{}); v != nil {
		if s, ok := v.(string); ok {
			return s
		}
	}
	return ""
}

// WithRequestID adds request ID to context
func WithRequestID(ctx context.Context, requestID string) context.Context {
	return context.WithValue(ctx, requestIDKey{}, requestID)
}

// WithTraceID adds trace ID to context
func WithTraceID(ctx context.Context, traceID string) context.Context {
	return context.WithValue(ctx, traceIDKey{}, traceID)
}

// GetTraceIDFromContextValue extracts trace ID from context (custom context value, not OpenTelemetry span)
func GetTraceIDFromContextValue(ctx context.Context) string {
	if v := ctx.Value(traceIDKey{}); v != nil {
		if s, ok := v.(string); ok {
			return s
		}
	}
	return ""
}