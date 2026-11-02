package appcore

import (
	"testing"

	"github.com/stretchr/testify/assert"
)

func TestBootstrap(t *testing.T) {
	facade, err := Bootstrap()
	if err != nil {
		t.Fatalf("Bootstrap failed: %v", err)
	}
	if facade == nil {
		t.Fatal("Facade is nil")
	}
	// Ensure facade components are initialized
	if facade.registry == nil {
		t.Error("Registry is nil")
	}
	if facade.eventDispatcher == nil {
		t.Error("EventDispatcher is nil")
	}
	if facade.transactionManager == nil {
		t.Error("TransactionManager is nil")
	}
}

func TestInitializePlatformServices(t *testing.T) {
	// This function is internal and currently a placeholder.
	// We can test it indirectly via Bootstrap, but also ensure it doesn't panic.
	// Call via reflection is not needed; Bootstrap already covers it.
	// This test exists only to ensure the function is reachable and returns nil.
	err := initializePlatformServices()
	assert.NoError(t, err)
}
