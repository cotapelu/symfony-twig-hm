package coreservices

import (
	"testing"
)

func TestNewHandler(t *testing.T) {
	// Test NewHandler creates a handler
	handler := NewHandler()
	if handler == nil {
		t.Error("NewHandler returned nil")
	}
}