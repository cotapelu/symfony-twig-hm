package integration

import (
	"net/http"
	"net/http/httptest"
	"testing"
)

func TestNewIntegrationService(t *testing.T) {
	svc := NewIntegrationService()
	if svc == nil {
		t.Error("NewIntegrationService returned nil")
	}
}

func TestRegisterWebhook(t *testing.T) {
	svc := NewIntegrationService()

	webhook := svc.RegisterWebhook("https://example.com/webhook", []string{"event1", "event2"}, "secret123")
	if webhook == nil {
		t.Error("RegisterWebhook returned nil")
		return
	}

	if webhook.URL != "https://example.com/webhook" {
		t.Errorf("Expected URL 'https://example.com/webhook', got '%s'", webhook.URL)
	}
	if len(webhook.Events) != 2 || webhook.Events[0] != "event1" || webhook.Events[1] != "event2" {
		t.Errorf("Expected events [event1, event2], got %v", webhook.Events)
	}
	if webhook.Secret != "secret123" {
		t.Errorf("Expected secret 'secret123', got '%s'", webhook.Secret)
	}
	if !webhook.Active {
		t.Error("Expected webhook to be active")
	}
	if webhook.ID == "" {
		t.Error("Expected webhook ID to be generated")
	}
	if webhook.CreatedAt.IsZero() {
		t.Error("Expected CreatedAt to be set")
	}
}

func TestRegisterWebhook_Multiple(t *testing.T) {
	svc := NewIntegrationService()

	wh1 := svc.RegisterWebhook("https://example.com/1", []string{"event1"}, "secret1")
	wh2 := svc.RegisterWebhook("https://example.com/2", []string{"event2"}, "secret2")

	if wh1.ID == wh2.ID {
		t.Error("Expected different webhook IDs")
	}
}

func TestTriggerWebhooks_NoMatchingEvent(t *testing.T) {
	svc := NewIntegrationService()
	svc.RegisterWebhook("https://example.com/webhook", []string{"event1"}, "secret")

	errors := svc.TriggerWebhooks("event2", map[string]string{"data": "test"})
	if len(errors) != 0 {
		t.Errorf("Expected no errors for non-matching event, got %v", errors)
	}
}

func TestTriggerWebhooks_InactiveWebhook(t *testing.T) {
	svc := NewIntegrationService()
	wh := svc.RegisterWebhook("https://example.com/webhook", []string{"event1"}, "secret")
	wh.Active = false

	errors := svc.TriggerWebhooks("event1", map[string]string{"data": "test"})
	if len(errors) != 0 {
		t.Errorf("Expected no errors for inactive webhook, got %v", errors)
	}
}

func TestTriggerWebhooks_Success(t *testing.T) {
	svc := NewIntegrationService()

	// Create a test server that accepts the webhook
	server := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		if r.Header.Get("X-Webhook-Secret") != "secret123" {
			http.Error(w, "Invalid secret", http.StatusUnauthorized)
			return
		}
		w.WriteHeader(http.StatusOK)
	}))
	defer server.Close()

	svc.RegisterWebhook(server.URL, []string{"event1"}, "secret123")

	errors := svc.TriggerWebhooks("event1", map[string]string{"data": "test"})
	if len(errors) > 0 {
		t.Errorf("Expected no errors, got %v", errors)
	}
}

func TestTriggerWebhooks_HTTPError(t *testing.T) {
	svc := NewIntegrationService()

	// Create a test server that returns an error
	server := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		http.Error(w, "Internal Server Error", http.StatusInternalServerError)
	}))
	defer server.Close()

	svc.RegisterWebhook(server.URL, []string{"event1"}, "secret123")

	// The client doesn't treat HTTP error status as errors
	// It only returns errors for network/transport failures
	errors := svc.TriggerWebhooks("event1", map[string]string{"data": "test"})
	// HTTP 500 is not a transport error, so no errors expected
	if len(errors) > 0 {
		t.Errorf("Unexpected errors for HTTP 500: %v", errors)
	}
}

func TestGenerateWebhookID(t *testing.T) {
	id := generateWebhookID()
	if id == "" {
		t.Error("generateWebhookID returned empty string")
	}
	if len(id) < 10 {
		t.Errorf("Expected ID length >= 10, got %d", len(id))
	}
}

func TestRandomString(t *testing.T) {
	s := randomString(10)
	if len(s) != 10 {
		t.Errorf("Expected length 10, got %d", len(s))
	}

	// Test that it generates different strings
	s2 := randomString(10)
	if s == s2 {
		t.Error("Expected different random strings")
	}
}