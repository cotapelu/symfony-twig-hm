package middleware

import (
	"net/http"
	"net/http/httptest"
	"testing"

	"github.com/gin-gonic/gin"
	"community.local/internal/tracing"
)

func TestTracingMiddleware(t *testing.T) {
	shutdown, err := tracing.InitTracer("http://localhost:12345", "test-service")
	if err != nil {
		t.Fatalf("InitTracer failed: %v", err)
	}
	defer shutdown()

	handler := TracingMiddleware("test-service")
	router := gin.New()
	router.Use(handler)
	router.GET("/test", func(c *gin.Context) {
		c.Status(http.StatusOK)
	})

	req := httptest.NewRequest("GET", "/test", nil)
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	if w.Code != http.StatusOK {
		t.Errorf("status = %d, want %d", w.Code, http.StatusOK)
	}
	traceID := w.Header().Get("X-Trace-ID")
	if traceID == "" {
		t.Error("X-Trace-ID header missing")
	} else if len(traceID) != 32 {
		t.Errorf("Trace ID length = %d, want 32", len(traceID))
	}
}
