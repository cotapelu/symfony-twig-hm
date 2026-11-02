package middleware

import (
	"context"
	"net/http"
	"net/http/httptest"
	"testing"

	"github.com/gin-gonic/gin"
	"github.com/stretchr/testify/assert"
)

func TestCorrelationID_WithExistingHeader(t *testing.T) {
	gin.SetMode(gin.TestMode)
	router := gin.New()
	router.Use(CorrelationID())
	router.GET("/test", func(c *gin.Context) {
		c.String(http.StatusOK, "ok")
	})

	req := httptest.NewRequest("GET", "/test", nil)
	req.Header.Set("X-Request-ID", "test-request-id-123")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
	assert.Equal(t, "test-request-id-123", w.Header().Get("X-Request-ID"))
}

func TestCorrelationID_GeneratesUUIDWhenMissing(t *testing.T) {
	gin.SetMode(gin.TestMode)
	router := gin.New()
	router.Use(CorrelationID())
	router.GET("/test", func(c *gin.Context) {
		c.String(http.StatusOK, "ok")
	})

	req := httptest.NewRequest("GET", "/test", nil)
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
	reqID := w.Header().Get("X-Request-ID")
	assert.NotEmpty(t, reqID)
	// Should be a valid UUID string (simple check)
	assert.Len(t, reqID, 36) // UUID v4 length with hyphens
}

func TestFromContext_ExtractsID(t *testing.T) {
	ctx := context.Background()
	reqID := "my-request-id"
	ctx = context.WithValue(ctx, correlationIDKey{}, reqID)

	id := FromContext(ctx)
	assert.Equal(t, reqID, id)
}

func TestFromContext_ReturnsEmptyWhenMissing(t *testing.T) {
	ctx := context.Background()
	id := FromContext(ctx)
	assert.Empty(t, id)
}
