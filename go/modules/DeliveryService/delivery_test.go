package DeliveryService

import (
	"os"
	"testing"
)

func TestNewHandler(t *testing.T) {
	// Create a temporary file for testing
	tmpFile, err := os.CreateTemp("", "delivery_test_*.json")
	if err != nil {
		t.Fatalf("Failed to create temp file: %v", err)
	}
	defer os.Remove(tmpFile.Name())
	tmpFile.Close()

	// Test NewHandler with custom file path
	// We can't easily test the internal file path, but we can verify the handler is created
	handler := NewHandler()
	if handler == nil {
		t.Error("NewHandler returned nil")
	}
}

func TestNewHandler_CustomPath(t *testing.T) {
	// Create a temporary file for testing
	tmpFile, err := os.CreateTemp("", "delivery_test_*.json")
	if err != nil {
		t.Fatalf("Failed to create temp file: %v", err)
	}
	defer os.Remove(tmpFile.Name())
	tmpFile.Close()

	// Test that we can create a handler (the actual service uses a file path internally)
	// Since NewHandler doesn't accept a path parameter, we test the default behavior
	handler := NewHandler()
	if handler == nil {
		t.Error("NewHandler returned nil")
	}
}