package Courier

import (
	"testing"

	"github.com/gin-gonic/gin"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"
)

func TestNewHandler(t *testing.T) {
	h := NewHandler(nil)
	require.NotNil(t, h)
	require.NotNil(t, h.inner)
}

func TestRegisterRoutes(t *testing.T) {
	gin.SetMode(gin.TestMode)
	router := gin.New()

	h := NewHandler(nil)
	// Should not panic when registering routes
	assert.NotPanics(t, func() {
		h.RegisterRoutes(router)
	})
}
