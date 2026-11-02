package otel

import (
	"context"
	"net/http"
	"net/http/httptest"
	"testing"

	"github.com/gin-gonic/gin"
	"github.com/stretchr/testify/assert"
)

func TestInitTracer(t *testing.T) {
	// InitTracer should return a tracer and nil error even if no collector is listening
	// (the exporter creation does not fail until it tries to send spans).
	tracer, err := InitTracer("test-service")
	assert.NoError(t, err)
	assert.NotNil(t, tracer)
}

func TestInstrumentHandler(t *testing.T) {
	// Build a simple next handler that records it was called
	called := false
	next := func(ctx context.Context) {
		called = true
	}

	// Wrap with InstrumentHandler
	handler := InstrumentHandler(next)

	// Invoke with a background context
	ctx := context.Background()
	handler(ctx)

	assert.True(t, called, "next handler should be called")
}

func TestTracingMiddleware(t *testing.T) {
	gin.SetMode(gin.TestMode)
	router := gin.New()

	// Track if the next handler was called and check context carries span
	nextCalled := false
	nextHandler := func(c *gin.Context) {
		nextCalled = true
		// Ensure request context is not nil
		assert.NotNil(t, c.Request.Context())
		// Respond
		c.String(http.StatusOK, "ok")
	}

	// Use the TracingMiddleware as middleware followed by the handler
	router.GET("/test", TracingMiddleware(), nextHandler)

	// Perform request
	req := httptest.NewRequest("GET", "/test", nil)
	rec := httptest.NewRecorder()
	router.ServeHTTP(rec, req)

	assert.True(t, nextCalled, "next handler should be called")
	assert.Equal(t, http.StatusOK, rec.Code)
	assert.Equal(t, "ok", rec.Body.String())
}
