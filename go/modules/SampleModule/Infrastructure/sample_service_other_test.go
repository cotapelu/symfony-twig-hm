package infrastructure

import (
	"testing"

	"github.com/stretchr/testify/assert"
)

func TestSampleService_ProcessFile(t *testing.T) {
	svc := NewSampleService()

	// ProcessFile returns an error "not implemented"
	_, err := svc.ProcessFile("/tmp/foo")
	assert.Error(t, err)
	assert.Equal(t, "not implemented", err.Error())
}

func TestSampleService_SendNotification(t *testing.T) {
	svc := NewSampleService()

	// Since the function uses fmt.Printf directly, we cannot easily capture without redirecting stdout.
	// For now, we just assert it returns nil.
	err := svc.SendNotification("Hello")
	assert.NoError(t, err)
	// Could extend with dependency injection in future to enable capture.
}
