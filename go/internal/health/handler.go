package health

import (
	"encoding/json"
	"net/http"
	"time"
)

type HealthHandler struct{}

func NewHealthHandler() *HealthHandler {
	return &HealthHandler{}
}

func (h *HealthHandler) HealthHandler(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "application/json")

	checks := map[string]interface{}{
		"status":    "healthy",
		"timestamp": time.Now().Format(time.RFC3339),
		"service":   "community-go",
		"checks": map[string]interface{}{
			"database": map[string]interface{}{
				"status":  "unknown",
				"message": "Not configured",
			},
			"cache": map[string]interface{}{
				"status":  "unknown",
				"message": "Not configured",
			},
			"redis": map[string]interface{}{
				"status":  "unknown",
				"message": "Not configured",
			},
		},
	}

	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(checks)
}

func (h *HealthHandler) ReadyHandler(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "application/json")

	checks := map[string]interface{}{
		"status":    "ready",
		"timestamp": time.Now().Format(time.RFC3339),
		"service":   "community-go",
		"checks": map[string]interface{}{
			"database": map[string]interface{}{
				"status":  "unknown",
				"message": "Not configured",
			},
			"cache": map[string]interface{}{
				"status":  "unknown",
				"message": "Not configured",
			},
		},
	}

	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(checks)
}

func (h *HealthHandler) LiveHandler(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "application/json")

	response := map[string]interface{}{
		"status":    "alive",
		"timestamp": time.Now().Format(time.RFC3339),
		"service":   "community-go",
	}

	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(response)
}
