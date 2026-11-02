package middleware

import (
	"context"
	"errors"
	"fmt"
	"net/http"
	"sync"
	"sync/atomic"
	"time"

	"github.com/gin-gonic/gin"
)

// ErrBulkheadFull is returned when bulkhead is at capacity
var ErrBulkheadFull = errors.New("bulkhead at capacity, request rejected")

// BulkheadConfig holds bulkhead configuration
type BulkheadConfig struct {
	Name           string
	MaxConcurrent  int
	MaxQueue       int
	Timeout        time.Duration
}

// DefaultBulkheadConfig returns default bulkhead configuration
func DefaultBulkheadConfig() BulkheadConfig {
	return BulkheadConfig{
		Name:          "default",
		MaxConcurrent: 100,
		MaxQueue:      1000,
		Timeout:       30 * time.Second,
	}
}

// Bulkhead implements the bulkhead pattern for resource isolation
type Bulkhead struct {
	name           string
	maxConcurrent  int
	maxQueue       int
	timeout        time.Duration
	semaphore      chan struct{}
	queue          chan struct{}
	activeCount    int64
	queuedCount    int64
	rejectedCount  int64
	completedCount int64
	mu             sync.RWMutex
}

// NewBulkhead creates a new bulkhead
func NewBulkhead(config BulkheadConfig) *Bulkhead {
	if config.MaxConcurrent <= 0 {
		config.MaxConcurrent = 100
	}
	if config.MaxQueue <= 0 {
		config.MaxQueue = 1000
	}
	if config.Timeout <= 0 {
		config.Timeout = 30 * time.Second
	}

	return &Bulkhead{
		name:          config.Name,
		maxConcurrent: config.MaxConcurrent,
		maxQueue:      config.MaxQueue,
		timeout:       config.Timeout,
		semaphore:     make(chan struct{}, config.MaxConcurrent),
		queue:         make(chan struct{}, config.MaxQueue),
	}
}

// Execute runs the function within the bulkhead
func (b *Bulkhead) Execute(ctx context.Context, fn func() error) error {
	// Try to acquire semaphore
	select {
	case b.semaphore <- struct{}{}:
		atomic.AddInt64(&b.activeCount, 1)
		defer func() {
			<-b.semaphore
			atomic.AddInt64(&b.activeCount, -1)
			atomic.AddInt64(&b.completedCount, 1)
		}()
		return fn()
	case b.queue <- struct{}{}:
		atomic.AddInt64(&b.queuedCount, 1)
		defer atomic.AddInt64(&b.queuedCount, -1)

		select {
		case b.semaphore <- struct{}{}:
			atomic.AddInt64(&b.activeCount, 1)
			defer func() {
				<-b.semaphore
				atomic.AddInt64(&b.activeCount, -1)
				atomic.AddInt64(&b.completedCount, 1)
			}()
			return fn()
		case <-ctx.Done():
			return ctx.Err()
		case <-time.After(b.timeout):
			atomic.AddInt64(&b.rejectedCount, 1)
			return ErrBulkheadFull
		}
	case <-ctx.Done():
		return ctx.Err()
	case <-time.After(b.timeout):
		atomic.AddInt64(&b.rejectedCount, 1)
		return ErrBulkheadFull
	}
}

// Stats returns current bulkhead statistics
func (b *Bulkhead) Stats() BulkheadStats {
	b.mu.RLock()
	defer b.mu.RUnlock()

	return BulkheadStats{
		Name:            b.name,
		MaxConcurrent:   b.maxConcurrent,
		MaxQueue:        b.maxQueue,
		ActiveCount:     atomic.LoadInt64(&b.activeCount),
		QueuedCount:     atomic.LoadInt64(&b.queuedCount),
		RejectedCount:   atomic.LoadInt64(&b.rejectedCount),
		CompletedCount:  atomic.LoadInt64(&b.completedCount),
	}
}

// BulkheadStats holds bulkhead statistics
type BulkheadStats struct {
	Name           string
	MaxConcurrent  int
	MaxQueue       int
	ActiveCount    int64
	QueuedCount    int64
	RejectedCount  int64
	CompletedCount int64
}

// BulkheadRegistry manages multiple bulkheads
type BulkheadRegistry struct {
	bulkheads map[string]*Bulkhead
	mu        sync.RWMutex
}

// NewBulkheadRegistry creates a new bulkhead registry
func NewBulkheadRegistry() *BulkheadRegistry {
	return &BulkheadRegistry{
		bulkheads: make(map[string]*Bulkhead),
	}
}

// Register registers a new bulkhead
func (r *BulkheadRegistry) Register(config BulkheadConfig) *Bulkhead {
	r.mu.Lock()
	defer r.mu.Unlock()

	if _, exists := r.bulkheads[config.Name]; exists {
		return r.bulkheads[config.Name]
	}

	b := NewBulkhead(config)
	r.bulkheads[config.Name] = b
	return b
}

// Get retrieves a bulkhead by name
func (r *BulkheadRegistry) Get(name string) (*Bulkhead, bool) {
	r.mu.RLock()
	defer r.mu.RUnlock()
	b, ok := r.bulkheads[name]
	return b, ok
}

// Execute executes a function using the named bulkhead
func (r *BulkheadRegistry) Execute(ctx context.Context, name string, fn func() error) error {
	b, ok := r.Get(name)
	if !ok {
		return fmt.Errorf("bulkhead %s not found", name)
	}
	return b.Execute(ctx, fn)
}

// AllStats returns stats for all bulkheads
func (r *BulkheadRegistry) AllStats() map[string]BulkheadStats {
	r.mu.RLock()
	defer r.mu.RUnlock()

	stats := make(map[string]BulkheadStats)
	for name, b := range r.bulkheads {
		stats[name] = b.Stats()
	}
	return stats
}

// BulkheadMiddleware creates a Gin handler that uses bulkhead for resource isolation
func BulkheadMiddleware(bulkhead *Bulkhead) gin.HandlerFunc {
	return func(c *gin.Context) {
		ctx, cancel := context.WithTimeout(c.Request.Context(), bulkhead.timeout)
		defer cancel()

		err := bulkhead.Execute(ctx, func() error {
			c.Next()
			return nil
		})

		if err != nil {
			if err == ErrBulkheadFull {
				c.AbortWithStatusJSON(http.StatusServiceUnavailable, gin.H{
					"error":   "service overloaded",
					"message": "Too many concurrent requests, please try again later",
					"retry":   true,
				})
			} else {
				c.AbortWithStatusJSON(http.StatusGatewayTimeout, gin.H{
					"error":   "request timeout",
					"message": "Request took too long to acquire resources",
				})
			}
		}
	}
}

// Global bulkhead registry
var globalBulkheadRegistry = NewBulkheadRegistry()

// GetGlobalBulkheadRegistry returns the global bulkhead registry
func GetGlobalBulkheadRegistry() *BulkheadRegistry {
	return globalBulkheadRegistry
}

// Default bulkheads for common resources
func init() {
	// Database bulkhead - limit concurrent DB connections
	globalBulkheadRegistry.Register(BulkheadConfig{
		Name:           "database",
		MaxConcurrent:  50,
		MaxQueue:       200,
		Timeout:        30 * time.Second,
	})

	// HTTP client bulkhead - limit concurrent external HTTP calls
	globalBulkheadRegistry.Register(BulkheadConfig{
		Name:           "http-client",
		MaxConcurrent:  100,
		MaxQueue:       500,
		Timeout:        15 * time.Second,
	})

	// Redis bulkhead - limit concurrent Redis operations
	globalBulkheadRegistry.Register(BulkheadConfig{
		Name:           "redis",
		MaxConcurrent:  100,
		MaxQueue:       200,
		Timeout:        10 * time.Second,
	})

	// External API bulkhead - limit concurrent external API calls
	globalBulkheadRegistry.Register(BulkheadConfig{
		Name:           "external-api",
		MaxConcurrent:  20,
		MaxQueue:       100,
		Timeout:        30 * time.Second,
	})
}