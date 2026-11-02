package billspayment

import (
	"testing"

	"github.com/stretchr/testify/require"
)

func TestNewHandler(t *testing.T) {
	h := NewHandler(nil)
	require.NotNil(t, h)
}
