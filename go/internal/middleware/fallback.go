package middleware

import (
	"context"
	"fmt"
	"net/http"
	"sync"
	"time"

	"github.com/gin-gonic/gin"
)

// FallbackFunc is a function that provides a fallback response
type FallbackFunc func(ctx context.Context, err error) (interface{}, bool)

// FallbackConfig holds fallback configuration
type FallbackConfig struct {
	Name           string
	Fallback       FallbackFunc
	Timeout        time.Duration
	CacheTTL       time.Duration
	EnableCaching  bool
}

// DefaultFallbackConfig returns default fallback configuration
func DefaultFallbackConfig() FallbackConfig {
	return FallbackConfig{
		Timeout:       5 * time.Second,
		CacheTTL:      1 * time.Minute,
		EnableCaching: false,
	}
}

// FallbackCache provides simple in-memory caching for fallback responses
type FallbackCache struct {
	mu    sync.RWMutex
	items map[string]*cacheItem
	ttl   time.Duration
}

type cacheItem struct {
	value     interface{}
	expiresAt time.Time
}

// NewFallbackCache creates a new fallback cache
func NewFallbackCache(ttl time.Duration) *FallbackCache {
	c := &FallbackCache{
		items: make(map[string]*cacheItem),
		ttl:   ttl,
	}
	go c.cleanupLoop()
	return c
}

func (c *FallbackCache) cleanupLoop() {
	ticker := time.NewTicker(c.ttl)
	defer ticker.Stop()
	for range ticker.C {
		c.mu.Lock()
		now := time.Now()
		for key, item := range c.items {
			if now.After(item.expiresAt) {
				delete(c.items, key)
			}
		}
		c.mu.Unlock()
	}
}

func (c *FallbackCache) Get(key string) (interface{}, bool) {
	c.mu.RLock()
	defer c.mu.RUnlock()
	item, ok := c.items[key]
	if !ok {
		return nil, false
	}
	if time.Now().After(item.expiresAt) {
		return nil, false
	}
	return item.value, true
}

func (c *FallbackCache) Set(key string, value interface{}) {
	c.mu.Lock()
	defer c.mu.Unlock()
	c.items[key] = &cacheItem{
		value:     value,
		expiresAt: time.Now().Add(c.ttl),
	}
}

func (c *FallbackCache) Delete(key string) {
	c.mu.Lock()
	defer c.mu.Unlock()
	delete(c.items, key)
}

// FallbackMiddleware creates a Gin handler that provides fallback responses
func FallbackMiddleware(config FallbackConfig) gin.HandlerFunc {
	cache := NewFallbackCache(config.CacheTTL)

	return func(c *gin.Context) {
		// Execute the next handler
		c.Next()

		// If the response is an error (5xx), try fallback
		if c.Writer.Status() >= 500 && c.Writer.Status() < 600 {
			// Check cache first if enabled
			if config.EnableCaching {
				if cached, ok := cache.Get(c.Request.URL.Path); ok {
					c.JSON(http.StatusOK, cached)
					c.Header("X-Fallback", "cached")
					return
				}
			}

			// Try fallback function
			if config.Fallback != nil {
				ctx, cancel := context.WithTimeout(c.Request.Context(), config.Timeout)
				defer cancel()

				if fallbackResp, ok := config.Fallback(ctx, fmt.Errorf("upstream returned %d", c.Writer.Status())); ok {
					c.JSON(http.StatusOK, fallbackResp)
					c.Header("X-Fallback", "dynamic")

					// Cache the fallback response if enabled
					if config.EnableCaching {
						cache.Set(c.Request.URL.Path, fallbackResp)
					}
					return
				}
			}
		}
	}
}

// Predefined fallback functions

// StaticFallback returns a static fallback response
func StaticFallback(data interface{}) FallbackFunc {
	return func(ctx context.Context, err error) (interface{}, bool) {
		return data, true
	}
}

// CachedFallback returns cached data or generates it
func CachedFallback(generator func(ctx context.Context) (interface{}, error), ttl time.Duration) FallbackFunc {
	cache := NewFallbackCache(ttl)
	return func(ctx context.Context, err error) (interface{}, bool) {
		// Try cache first
		if cached, ok := cache.Get("fallback"); ok {
			return cached, true
		}

		// Generate new data
		data, err := generator(ctx)
		if err != nil {
			return nil, false
		}

		cache.Set("fallback", data)
		return data, true
	}
}

// DegradedModeFallback returns a simplified response indicating degraded mode
func DegradedModeFallback(serviceName string) FallbackFunc {
	return func(ctx context.Context, err error) (interface{}, bool) {
		return gin.H{
			"status":      "degraded",
			"service":     serviceName,
			"message":     "Service is running in degraded mode, some features may be unavailable",
			"error":       err.Error(),
			"timestamp":   time.Now().Format(time.RFC3339),
		}, true
	}
}

// EmptyListFallback returns an empty list for list endpoints
func EmptyListFallback() FallbackFunc {
	return func(ctx context.Context, err error) (interface{}, bool) {
		return gin.H{
			"data":        []interface{}{},
			"count":       0,
			"message":     "Service temporarily unavailable, returning empty list",
			"fallback":    true,
			"timestamp":   time.Now().Format(time.RFC3339),
		}, true
	}
}

// DefaultValueFallback returns a default value for a specific field
func DefaultValueFallback(defaultValue interface{}) FallbackFunc {
	return func(ctx context.Context, err error) (interface{}, bool) {
		return gin.H{
			"value":     defaultValue,
			"fallback":  true,
			"message":   "Using default value due to upstream error",
			"error":     err.Error(),
			"timestamp": time.Now().Format(time.RFC3339),
		}, true
	}
}

// FallbackRegistry manages fallback configurations per endpoint
type FallbackRegistry struct {
	fallbacks map[string]FallbackConfig
	mu        sync.RWMutex
}

// NewFallbackRegistry creates a new fallback registry
func NewFallbackRegistry() *FallbackRegistry {
	return &FallbackRegistry{
		fallbacks: make(map[string]FallbackConfig),
	}
}

// Register registers a fallback for an endpoint pattern
func (r *FallbackRegistry) Register(pattern string, config FallbackConfig) {
	r.mu.Lock()
	defer r.mu.Unlock()
	r.fallbacks[pattern] = config
}

// Get retrieves a fallback config for an endpoint
func (r *FallbackRegistry) Get(pattern string) (FallbackConfig, bool) {
	r.mu.RLock()
	defer r.mu.RUnlock()
	config, ok := r.fallbacks[pattern]
	return config, ok
}

// Global fallback registry
var globalFallbackRegistry = NewFallbackRegistry()

// GetGlobalFallbackRegistry returns the global fallback registry
func GetGlobalFallbackRegistry() *FallbackRegistry {
	return globalFallbackRegistry
}

// Initialize default fallbacks
func init() {
	// Default fallbacks for common endpoints
	globalFallbackRegistry.Register("/api/users", FallbackConfig{
		Name: "users-list",
		Fallback: EmptyListFallback(),
		CacheTTL: 30 * time.Second,
		EnableCaching: true,
	})

	globalFallbackRegistry.Register("/api/services", FallbackConfig{
		Name: "services-list",
		Fallback: EmptyListFallback(),
		CacheTTL: 30 * time.Second,
		EnableCaching: true,
	})

	globalFallbackRegistry.Register("/api/health", FallbackConfig{
		Name: "health-check",
		Fallback: StaticFallback(gin.H{
			"status": "degraded",
			"message": "Health check fallback",
		}),
	})

	globalFallbackRegistry.Register("/api/location", FallbackConfig{
		Name: "location-nearby",
		Fallback: EmptyListFallback(),
		CacheTTL: 1 * time.Minute,
		EnableCaching: true,
	})
}