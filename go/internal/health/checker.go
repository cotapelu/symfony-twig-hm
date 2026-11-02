package health

import (
	"context"
	"database/sql"
	"encoding/json"
	"fmt"
	"net/http"
	"sync"
	"time"

	"github.com/redis/go-redis/v9"
)

// Check represents a single health check
type Check struct {
	Name        string                 `json:"name"`
	Status      string                 `json:"status"` // "healthy", "degraded", "unhealthy"
	Message     string                 `json:"message,omitempty"`
	Component   string                 `json:"component,omitempty"`
	ObservedAt  string                 `json:"observed_at"`
	ObservedValue interface{}           `json:"observed_value,omitempty"`
	Metadata    map[string]interface{} `json:"metadata,omitempty"`
}

// HealthStatus represents overall health status
type HealthStatus struct {
	Status    string  `json:"status"`    // "healthy", "degraded", "unhealthy"
	Timestamp string  `json:"timestamp"`
	Service   string  `json:"service"`
	Version   string  `json:"version,omitempty"`
	Checks    []Check `json:"checks"`
}

// CheckFunc is a function that performs a health check
type CheckFunc func(ctx context.Context) Check

// HealthChecker manages health checks
type HealthChecker struct {
	serviceName string
	version     string
	checks      map[string]CheckFunc
	mu          sync.RWMutex
	liveCheck   CheckFunc
	readyCheck  CheckFunc
}

// NewHealthChecker creates a new health checker
func NewHealthChecker(serviceName, version string) *HealthChecker {
	return &HealthChecker{
		serviceName: serviceName,
		version:     version,
		checks:      make(map[string]CheckFunc),
		liveCheck: func(ctx context.Context) Check {
			return Check{
				Name:      "liveness",
				Status:    "healthy",
				Message:   "Service is alive",
				Component: "self",
				ObservedAt: time.Now().Format(time.RFC3339),
			}
		},
		readyCheck: func(ctx context.Context) Check {
			return Check{
				Name:      "readiness",
				Status:    "ready",
				Message:   "Service is ready",
				Component: "self",
				ObservedAt: time.Now().Format(time.RFC3339),
			}
		},
	}
}

// RegisterCheck registers a health check
func (hc *HealthChecker) RegisterCheck(name string, fn CheckFunc) {
	hc.mu.Lock()
	defer hc.mu.Unlock()
	hc.checks[name] = fn
}

// SetLiveCheck sets the liveness check
func (hc *HealthChecker) SetLiveCheck(fn CheckFunc) {
	hc.mu.Lock()
	defer hc.mu.Unlock()
	hc.liveCheck = fn
}

// SetReadyCheck sets the readiness check
func (hc *HealthChecker) SetReadyCheck(fn CheckFunc) {
	hc.mu.Lock()
	defer hc.mu.Unlock()
	hc.readyCheck = fn
}

// RunChecks executes all registered checks
func (hc *HealthChecker) RunChecks(ctx context.Context) []Check {
	hc.mu.RLock()
	checks := make(map[string]CheckFunc, len(hc.checks))
	for k, v := range hc.checks {
		checks[k] = v
	}
	hc.mu.RUnlock()

	results := make([]Check, 0, len(checks))
	for name, fn := range checks {
		result := fn(ctx)
		result.Name = name
		results = append(results, result)
	}
	return results
}

// GetHealthStatus returns the overall health status
func (hc *HealthChecker) GetHealthStatus(ctx context.Context) HealthStatus {
	checks := hc.RunChecks(ctx)

	status := "healthy"
	for _, check := range checks {
		if check.Status == "unhealthy" {
			status = "unhealthy"
			break
		}
		if check.Status == "degraded" && status == "healthy" {
			status = "degraded"
		}
	}

	return HealthStatus{
		Status:    status,
		Timestamp: time.Now().Format(time.RFC3339),
		Service:   hc.serviceName,
		Version:   hc.version,
		Checks:    checks,
	}
}

// LiveHandler handles liveness probe
func (hc *HealthChecker) LiveHandler(w http.ResponseWriter, r *http.Request) {
	ctx, cancel := context.WithTimeout(r.Context(), 5*time.Second)
	defer cancel()

	check := hc.liveCheck(ctx)

	w.Header().Set("Content-Type", "application/json")
	if check.Status == "unhealthy" {
		w.WriteHeader(http.StatusServiceUnavailable)
	} else {
		w.WriteHeader(http.StatusOK)
	}
	json.NewEncoder(w).Encode(check)
}

// ReadyHandler handles readiness probe
func (hc *HealthChecker) ReadyHandler(w http.ResponseWriter, r *http.Request) {
	ctx, cancel := context.WithTimeout(r.Context(), 10*time.Second)
	defer cancel()

	health := hc.GetHealthStatus(ctx)

	w.Header().Set("Content-Type", "application/json")
	if health.Status == "unhealthy" {
		w.WriteHeader(http.StatusServiceUnavailable)
	} else if health.Status == "degraded" {
		w.WriteHeader(http.StatusOK) // Still return 200 for degraded
	} else {
		w.WriteHeader(http.StatusOK)
	}
	json.NewEncoder(w).Encode(health)
}

// HealthHandler handles full health check
func (hc *HealthChecker) HealthHandler(w http.ResponseWriter, r *http.Request) {
	ctx, cancel := context.WithTimeout(r.Context(), 10*time.Second)
	defer cancel()

	health := hc.GetHealthStatus(ctx)

	w.Header().Set("Content-Type", "application/json")
	if health.Status == "unhealthy" {
		w.WriteHeader(http.StatusServiceUnavailable)
	} else if health.Status == "degraded" {
		w.WriteHeader(http.StatusOK)
	} else {
		w.WriteHeader(http.StatusOK)
	}
	json.NewEncoder(w).Encode(health)
}

// Predefined check functions

// DatabaseCheck creates a database health check
func DatabaseCheck(db *sql.DB, name string) CheckFunc {
	return func(ctx context.Context) Check {
		ctx, cancel := context.WithTimeout(ctx, 3*time.Second)
		defer cancel()

		start := time.Now()
		err := db.PingContext(ctx)
		latency := time.Since(start)

		if err != nil {
			return Check{
				Name:      name,
				Status:    "unhealthy",
				Message:   fmt.Sprintf("Database ping failed: %v", err),
				Component: "database",
				ObservedAt: time.Now().Format(time.RFC3339),
				ObservedValue: map[string]interface{}{
					"latency_ms": latency.Milliseconds(),
					"error":      err.Error(),
				},
			}
		}

		// Check connection pool stats
		stats := db.Stats()
		return Check{
			Name:      name,
			Status:    "healthy",
			Message:   "Database connection OK",
			Component: "database",
			ObservedAt: time.Now().Format(time.RFC3339),
			ObservedValue: map[string]interface{}{
				"latency_ms":        latency.Milliseconds(),
				"open_connections":  stats.OpenConnections,
				"in_use":            stats.InUse,
				"idle":              stats.Idle,
				"wait_count":        stats.WaitCount,
				"wait_duration_ms":  stats.WaitDuration.Milliseconds(),
			},
		}
	}
}

// RedisCheck creates a Redis health check
func RedisCheck(client *redis.Client, name string) CheckFunc {
	return func(ctx context.Context) Check {
		ctx, cancel := context.WithTimeout(ctx, 3*time.Second)
		defer cancel()

		start := time.Now()
		err := client.Ping(ctx).Err()
		latency := time.Since(start)

		if err != nil {
			return Check{
				Name:      name,
				Status:    "unhealthy",
				Message:   fmt.Sprintf("Redis ping failed: %v", err),
				Component: "redis",
				ObservedAt: time.Now().Format(time.RFC3339),
				ObservedValue: map[string]interface{}{
					"latency_ms": latency.Milliseconds(),
					"error":      err.Error(),
				},
			}
		}

		// Get Redis info
		info, _ := client.Info(ctx, "memory", "clients").Result()
		return Check{
			Name:      name,
			Status:    "healthy",
			Message:   "Redis connection OK",
			Component: "redis",
			ObservedAt: time.Now().Format(time.RFC3339),
			ObservedValue: map[string]interface{}{
				"latency_ms": latency.Milliseconds(),
				"info":       info,
			},
		}
	}
}

// HTTPCheck creates an HTTP endpoint health check
func HTTPCheck(url, name string) CheckFunc {
	return func(ctx context.Context) Check {
		ctx, cancel := context.WithTimeout(ctx, 5*time.Second)
		defer cancel()

		start := time.Now()
		req, _ := http.NewRequestWithContext(ctx, "GET", url, nil)
		client := &http.Client{Timeout: 5 * time.Second}
		resp, err := client.Do(req)
		latency := time.Since(start)

		if err != nil {
			return Check{
				Name:      name,
				Status:    "unhealthy",
				Message:   fmt.Sprintf("HTTP check failed: %v", err),
				Component: "http",
				ObservedAt: time.Now().Format(time.RFC3339),
				ObservedValue: map[string]interface{}{
					"url":         url,
					"latency_ms":  latency.Milliseconds(),
					"error":       err.Error(),
				},
			}
		}
		defer resp.Body.Close()

		status := "healthy"
		if resp.StatusCode >= 500 {
			status = "unhealthy"
		} else if resp.StatusCode >= 400 {
			status = "degraded"
		}

		return Check{
			Name:      name,
			Status:    status,
			Message:   fmt.Sprintf("HTTP %d", resp.StatusCode),
			Component: "http",
			ObservedAt: time.Now().Format(time.RFC3339),
			ObservedValue: map[string]interface{}{
				"url":         url,
				"status_code": resp.StatusCode,
				"latency_ms":  latency.Milliseconds(),
			},
		}
	}
}

// DiskSpaceCheck creates a disk space health check
func DiskSpaceCheck(path string, minFreePercent float64, name string) CheckFunc {
	return func(ctx context.Context) Check {
		// Simplified check - in production, use syscall.Statfs
		return Check{
			Name:      name,
			Status:    "healthy",
			Message:   "Disk space check not implemented",
			Component: "disk",
			ObservedAt: time.Now().Format(time.RFC3339),
			ObservedValue: map[string]interface{}{
				"path":            path,
				"min_free_percent": minFreePercent,
			},
		}
	}
}

// MemoryCheck creates a memory usage health check
func MemoryCheck(maxPercent float64, name string) CheckFunc {
	return func(ctx context.Context) Check {
		// Simplified check - in production, use runtime.ReadMemStats
		return Check{
			Name:      name,
			Status:    "healthy",
			Message:   "Memory check not implemented",
			Component: "memory",
			ObservedAt: time.Now().Format(time.RFC3339),
			ObservedValue: map[string]interface{}{
				"max_percent": maxPercent,
			},
		}
	}
}

// DependencyCheck checks a dependent service
func DependencyCheck(dependencyName string, checkFn CheckFunc) CheckFunc {
	return func(ctx context.Context) Check {
		return checkFn(ctx)
	}
}

// CompositeCheck runs multiple checks and returns the worst status
func CompositeCheck(name string, checks ...CheckFunc) CheckFunc {
	return func(ctx context.Context) Check {
		worstStatus := "healthy"
		messages := make([]string, 0)
		allObserved := make(map[string]interface{})

		for i, fn := range checks {
			result := fn(ctx)
			if result.Status == "unhealthy" {
				worstStatus = "unhealthy"
			} else if result.Status == "degraded" && worstStatus == "healthy" {
				worstStatus = "degraded"
			}
			messages = append(messages, fmt.Sprintf("%s: %s", result.Name, result.Message))
			allObserved[fmt.Sprintf("check_%d", i)] = result.ObservedValue
		}

		return Check{
			Name:      name,
			Status:    worstStatus,
			Message:   fmt.Sprintf("Composite: %s", joinMessages(messages, "; ")),
			Component: "composite",
			ObservedAt: time.Now().Format(time.RFC3339),
			ObservedValue: allObserved,
		}
	}
}

func joinMessages(messages []string, sep string) string {
	if len(messages) == 0 {
		return ""
	}
	result := messages[0]
	for i := 1; i < len(messages); i++ {
		result += sep + messages[i]
	}
	return result
}