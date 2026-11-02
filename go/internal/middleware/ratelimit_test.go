package middleware

import (
	"fmt"
	"net/http"
	"net/http/httptest"
	"testing"
	"time"

	"github.com/gin-gonic/gin"
)

func TestNewRateLimiter(t *testing.T) {
	rl := NewRateLimiter(10, time.Second)
	if rl == nil {
		t.Fatal("NewRateLimiter returned nil")
	}
	if rl.limit != 10 {
		t.Errorf("limit = %d, want 10", rl.limit)
	}
	if rl.window != time.Second {
		t.Errorf("window = %v, want 1s", rl.window)
	}
}

func TestRateLimiter_LimitAllowsWithin(t *testing.T) {
	rl := NewRateLimiter(3, 1*time.Second)
	handler := rl.Limit()

	router := gin.New()
	router.Use(handler)
	router.GET("/", func(c *gin.Context) { c.Status(http.StatusOK) })

	for i := 0; i < 3; i++ {
		req := httptest.NewRequest("GET", "/", nil)
		req.RemoteAddr = "192.0.2.1:12345"
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		if w.Code != http.StatusOK {
			t.Errorf("Request %d: status = %d, want %d", i+1, w.Code, http.StatusOK)
		}
	}
}

func TestRateLimiter_LimitBlocksExcess(t *testing.T) {
	rl := NewRateLimiter(2, 1*time.Second)
	handler := rl.Limit()

	router := gin.New()
	router.Use(handler)
	router.GET("/", func(c *gin.Context) { c.Status(http.StatusOK) })

	// First 2 should pass
	for i := 0; i < 2; i++ {
		req := httptest.NewRequest("GET", "/", nil)
		req.RemoteAddr = "192.0.2.1:12345"
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		if w.Code != http.StatusOK {
			t.Errorf("Request %d: status = %d, want %d", i+1, w.Code, http.StatusOK)
		}
	}
	// 3rd should be rate limited
	req := httptest.NewRequest("GET", "/", nil)
	req.RemoteAddr = "192.0.2.1:12345"
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)
	if w.Code != http.StatusTooManyRequests {
		t.Errorf("Request 3: status = %d, want %d", w.Code, http.StatusTooManyRequests)
	}
}

func TestRateLimiter_WindowExpiry(t *testing.T) {
	// Limit 2 per 50ms
	rl := NewRateLimiter(2, 50*time.Millisecond)
	handler := rl.Limit()

	router := gin.New()
	router.Use(handler)
	router.GET("/", func(c *gin.Context) { c.Status(http.StatusOK) })

	// 2 requests succeed
	for i := 0; i < 2; i++ {
		req := httptest.NewRequest("GET", "/", nil)
		req.RemoteAddr = "192.0.2.1:12345"
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		if w.Code != http.StatusOK {
			t.Errorf("Request %d: status = %d, want %d", i+1, w.Code, http.StatusOK)
		}
	}
	// 3rd blocked
	req := httptest.NewRequest("GET", "/", nil)
	req.RemoteAddr = "192.0.2.1:12345"
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)
	if w.Code != http.StatusTooManyRequests {
		t.Errorf("Request 3: status = %d, want %d", w.Code, http.StatusTooManyRequests)
	}

	// Wait for window to pass
	time.Sleep(60 * time.Millisecond)

	// After window, should allow new request
	req = httptest.NewRequest("GET", "/", nil)
	req.RemoteAddr = "192.0.2.1:12345"
	w = httptest.NewRecorder()
	router.ServeHTTP(w, req)
	if w.Code != http.StatusOK {
		t.Errorf("Request after window: status = %d, want %d", w.Code, http.StatusOK)
	}
}

func TestRateLimiter_Concurrent(t *testing.T) {
	rl := NewRateLimiter(20, 1*time.Second)
	handler := rl.Limit()

	router := gin.New()
	router.Use(handler)
	router.GET("/", func(c *gin.Context) { c.Status(http.StatusOK) })

	// Simulate 50 concurrent requests
	errors := make(chan error, 50)
	for i := 0; i < 50; i++ {
		go func(idx int) {
			req := httptest.NewRequest("GET", "/", nil)
			req.RemoteAddr = "192.0.2.1:12345"
			w := httptest.NewRecorder()
			router.ServeHTTP(w, req)
			if w.Code != http.StatusOK && w.Code != http.StatusTooManyRequests {
				errors <- fmt.Errorf("unexpected status %d", w.Code)
			} else {
				errors <- nil
			}
		}(i)
	}
	for i := 0; i < 50; i++ {
		if err := <-errors; err != nil {
			t.Error(err)
		}
	}
}
