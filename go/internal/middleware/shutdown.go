package middleware

import (
	"context"
	"fmt"
	"net/http"
	"os"
	"os/signal"
	"sync"
	"sync/atomic"
	"syscall"
	"time"

	"github.com/gin-gonic/gin"
)

// ShutdownConfig holds graceful shutdown configuration
type ShutdownConfig struct {
	Timeout         time.Duration
	WaitForRequests bool
	HealthCheckPath string
}

// DefaultShutdownConfig returns default shutdown configuration
func DefaultShutdownConfig() ShutdownConfig {
	return ShutdownConfig{
		Timeout:         30 * time.Second,
		WaitForRequests: true,
		HealthCheckPath: "/health/live",
	}
}

// ShutdownManager manages graceful shutdown
type ShutdownManager struct {
	config        ShutdownConfig
	server        *http.Server
	activeRequests int64
	shutdownInitiated atomic.Bool
	shutdownChan    chan struct{}
	wg              sync.WaitGroup
	mu              sync.Mutex
	preShutdownHooks []func() error
	postShutdownHooks []func() error
}

// NewShutdownManager creates a new shutdown manager
func NewShutdownManager(server *http.Server, config ShutdownConfig) *ShutdownManager {
	if config.Timeout <= 0 {
		config.Timeout = 30 * time.Second
	}

	sm := &ShutdownManager{
		config:         config,
		server:         server,
		shutdownChan:   make(chan struct{}),
		preShutdownHooks: make([]func() error, 0),
		postShutdownHooks: make([]func() error, 0),
	}

	return sm
}

// AddPreShutdownHook adds a function to run before shutdown
func (sm *ShutdownManager) AddPreShutdownHook(fn func() error) {
	sm.mu.Lock()
	defer sm.mu.Unlock()
	sm.preShutdownHooks = append(sm.preShutdownHooks, fn)
}

// AddPostShutdownHook adds a function to run after shutdown
func (sm *ShutdownManager) AddPostShutdownHook(fn func() error) {
	sm.mu.Lock()
	defer sm.mu.Unlock()
	sm.postShutdownHooks = append(sm.postShutdownHooks, fn)
}

// IncrementRequests increments the active request counter
func (sm *ShutdownManager) IncrementRequests() {
	if sm.config.WaitForRequests {
		atomic.AddInt64(&sm.activeRequests, 1)
		sm.wg.Add(1)
	}
}

// DecrementRequests decrements the active request counter
func (sm *ShutdownManager) DecrementRequests() {
	if sm.config.WaitForRequests {
		atomic.AddInt64(&sm.activeRequests, -1)
		sm.wg.Done()
	}
}

// ActiveRequests returns the number of active requests
func (sm *ShutdownManager) ActiveRequests() int64 {
	return atomic.LoadInt64(&sm.activeRequests)
}

// IsShuttingDown returns true if shutdown has been initiated
func (sm *ShutdownManager) IsShuttingDown() bool {
	return sm.shutdownInitiated.Load()
}

// ShutdownMiddleware creates a Gin handler that tracks active requests
func ShutdownMiddleware(sm *ShutdownManager) gin.HandlerFunc {
	return func(c *gin.Context) {
		// Check if shutdown initiated
		if sm.IsShuttingDown() {
			c.AbortWithStatusJSON(http.StatusServiceUnavailable, gin.H{
				"error":   "service shutting down",
				"message": "Service is shutting down, please try again later",
			})
			return
		}

		// Track active request
		sm.IncrementRequests()
		defer sm.DecrementRequests()

		c.Next()
	}
}

// Start begins listening for shutdown signals
func (sm *ShutdownManager) Start() {
	sigChan := make(chan os.Signal, 1)
	signal.Notify(sigChan, syscall.SIGTERM, syscall.SIGINT)

	go func() {
		<-sigChan
		fmt.Println("Shutdown signal received, initiating graceful shutdown...")
		sm.Shutdown()
	}()
}

// Shutdown initiates graceful shutdown
func (sm *ShutdownManager) Shutdown() {
	if !sm.shutdownInitiated.CompareAndSwap(false, true) {
		fmt.Println("Shutdown already in progress")
		return
	}

	fmt.Println("Starting graceful shutdown...")

	// Run pre-shutdown hooks
	sm.mu.Lock()
	hooks := make([]func() error, len(sm.preShutdownHooks))
	copy(hooks, sm.preShutdownHooks)
	sm.mu.Unlock()

	for _, hook := range hooks {
		if err := hook(); err != nil {
			fmt.Printf("Pre-shutdown hook error: %v\n", err)
		}
	}

	// Stop accepting new connections by closing the server
	ctx, cancel := context.WithTimeout(context.Background(), sm.config.Timeout)
	defer cancel()

	// Wait for active requests to complete
	if sm.config.WaitForRequests {
		fmt.Printf("Waiting for %d active requests to complete...\n", sm.ActiveRequests())
		
		done := make(chan struct{})
		go func() {
			sm.wg.Wait()
			close(done)
		}()

		select {
		case <-done:
			fmt.Println("All active requests completed")
		case <-ctx.Done():
			fmt.Printf("Timeout waiting for requests, %d still active\n", sm.ActiveRequests())
		}
	}

	// Shutdown the HTTP server
	if err := sm.server.Shutdown(ctx); err != nil {
		fmt.Printf("Server shutdown error: %v\n", err)
	}

	// Run post-shutdown hooks
	sm.mu.Lock()
	hooks = make([]func() error, len(sm.postShutdownHooks))
	copy(hooks, sm.postShutdownHooks)
	sm.mu.Unlock()

	for _, hook := range hooks {
		if err := hook(); err != nil {
			fmt.Printf("Post-shutdown hook error: %v\n", err)
		}
	}

	close(sm.shutdownChan)
	fmt.Println("Graceful shutdown completed")
}

// WaitForShutdown blocks until shutdown is complete
func (sm *ShutdownManager) WaitForShutdown() {
	<-sm.shutdownChan
}

// HealthMiddleware creates a Gin handler that updates health status during shutdown
func HealthMiddleware(sm *ShutdownManager) gin.HandlerFunc {
	return func(c *gin.Context) {
		if sm.IsShuttingDown() {
			c.Header("X-Shutting-Down", "true")
			c.JSON(http.StatusServiceUnavailable, gin.H{
				"status":    "shutting_down",
				"message":   "Service is shutting down",
				"active_requests": sm.ActiveRequests(),
			})
			c.Abort()
			return
		}
		c.Next()
	}
}

// DefaultShutdownManager creates a shutdown manager with common hooks
func DefaultShutdownManager(server *http.Server) *ShutdownManager {
	sm := NewShutdownManager(server, DefaultShutdownConfig())

	// Add common pre-shutdown hooks
	sm.AddPreShutdownHook(func() error {
		fmt.Println("Running pre-shutdown hooks...")
		// Close database connections, Redis connections, etc.
		// This would be replaced with actual cleanup
		return nil
	})

	sm.AddPreShutdownHook(func() error {
		// Disable health check endpoint to signal unreadiness
		// This would integrate with the actual health check system
		return nil
	})

	sm.AddPostShutdownHook(func() error {
		fmt.Println("Post-shutdown cleanup completed")
		return nil
	})

	return sm
}