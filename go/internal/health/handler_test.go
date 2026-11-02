package health

import (
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"strings"
	"testing"
)

func TestNewHealthHandler(t *testing.T) {
	h := NewHealthHandler()
	if h == nil {
		t.Errorf("NewHealthHandler() returned nil")
	}
}

func TestHealthHandler(t *testing.T) {
	h := NewHealthHandler()
	req := httptest.NewRequest("GET", "/health", nil)
	rr := httptest.NewRecorder()

	h.HealthHandler(rr, req)

	if rr.Code != http.StatusOK {
		t.Errorf("HealthHandler() status code = %v, want %v", rr.Code, http.StatusOK)
	}
	if !strings.Contains(rr.Header().Get("Content-Type"), "application/json") {
		t.Errorf("HealthHandler() content-type = %v, want application/json", rr.Header().Get("Content-Type"))
	}

	var resp map[string]interface{}
	err := json.NewDecoder(rr.Body).Decode(&resp)
	if err != nil {
		t.Fatalf("HealthHandler() decode error: %v", err)
	}

	if resp["status"] != "healthy" {
		t.Errorf("HealthHandler() status = %v, want healthy", resp["status"])
	}
	if resp["service"] != "community-go" {
		t.Errorf("HealthHandler() service = %v, want community-go", resp["service"])
	}
	// Check checks structure
	checks, ok := resp["checks"].(map[string]interface{})
	if !ok {
		t.Fatalf("HealthHandler() checks not map")
	}
	if _, exists := checks["database"]; !exists {
		t.Errorf("HealthHandler() missing database check")
	}
}

func TestReadyHandler(t *testing.T) {
	h := NewHealthHandler()
	req := httptest.NewRequest("GET", "/ready", nil)
	rr := httptest.NewRecorder()

	h.ReadyHandler(rr, req)

	if rr.Code != http.StatusOK {
		t.Errorf("ReadyHandler() status code = %v, want %v", rr.Code, http.StatusOK)
	}

	var resp map[string]interface{}
	err := json.NewDecoder(rr.Body).Decode(&resp)
	if err != nil {
		t.Fatalf("ReadyHandler() decode error: %v", err)
	}

	if resp["status"] != "ready" {
		t.Errorf("ReadyHandler() status = %v, want ready", resp["status"])
	}
	if resp["service"] != "community-go" {
		t.Errorf("ReadyHandler() service = %v, want community-go", resp["service"])
	}
}

func TestLiveHandler(t *testing.T) {
	h := NewHealthHandler()
	req := httptest.NewRequest("GET", "/live", nil)
	rr := httptest.NewRecorder()

	h.LiveHandler(rr, req)

	if rr.Code != http.StatusOK {
		t.Errorf("LiveHandler() status code = %v, want %v", rr.Code, http.StatusOK)
	}

	var resp map[string]interface{}
	err := json.NewDecoder(rr.Body).Decode(&resp)
	if err != nil {
		t.Fatalf("LiveHandler() decode error: %v", err)
	}

	if resp["status"] != "alive" {
		t.Errorf("LiveHandler() status = %v, want alive", resp["status"])
	}
	if resp["service"] != "community-go" {
		t.Errorf("LiveHandler() service = %v, want community-go", resp["service"])
	}
}
