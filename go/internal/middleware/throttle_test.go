package middleware

import (
	"net/http"
	"net/http/httptest"
	"testing"

	"github.com/gin-gonic/gin"
)

func TestThrottle_AllowsWithinCapacity(t *testing.T) {
	// rate: 10 req/sec, capacity: 5
	tm := NewThrottleMiddleware(10, 5)
	handler := tm.Throttle()

	router := gin.New()
	router.Use(handler)
	router.GET("/test", func(c *gin.Context) {
		c.Status(http.StatusOK)
	})

	for i := 0; i < 5; i++ {
		req := httptest.NewRequest("GET", "/test", nil)
		req.RemoteAddr = "192.0.2.1:12345"
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		if w.Code != http.StatusOK {
			t.Errorf("Request %d: status = %d, want %d", i+1, w.Code, http.StatusOK)
		}
	}
}

func TestThrottle_ThrottlesBeyondCapacity(t *testing.T) {
	tm := NewThrottleMiddleware(10, 5)
	handler := tm.Throttle()

	router := gin.New()
	router.Use(handler)
	router.GET("/test", func(c *gin.Context) {
		c.Status(http.StatusOK)
	})

	// First 5 should be OK
	for i := 0; i < 5; i++ {
		req := httptest.NewRequest("GET", "/test", nil)
		req.RemoteAddr = "192.0.2.1:12345"
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		if w.Code != http.StatusOK {
			t.Errorf("Request %d: status = %d, want %d", i+1, w.Code, http.StatusOK)
		}
	}
	// 6th should be throttled
	req := httptest.NewRequest("GET", "/test", nil)
	req.RemoteAddr = "192.0.2.1:12345"
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)
	if w.Code != http.StatusTooManyRequests {
		t.Errorf("Request 6: status = %d, want %d", w.Code, http.StatusTooManyRequests)
	}
}
