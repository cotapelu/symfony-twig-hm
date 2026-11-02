package pii

import (
	"testing"

	"github.com/stretchr/testify/assert"
)

func TestNewPIIMasker(t *testing.T) {
	masker := NewPIIMasker()
	assert.NotNil(t, masker)
}

func TestMaskEmail(t *testing.T) {
	masker := NewPIIMasker()
	
	tests := []string{
		"Contact john@example.com for info",
		"Email: alice@test.org is the admin",
	}
	
	for _, test := range tests {
		result := masker.MaskPII(test)
		assert.Contains(t, result, "[REDACTED]")
		assert.NotContains(t, result, "@example.com")
	}
}

func TestMaskCreditCard(t *testing.T) {
	masker := NewPIIMasker()
	
	text := "Card: 4111-1111-1111-1111 charged"
	result := masker.MaskPII(text)
	assert.Contains(t, result, "[REDACTED]")
}

func TestMaskSSN(t *testing.T) {
	masker := NewPIIMasker()
	
	text := "SSN: 123-45-6789 verified"
	result := masker.MaskPII(text)
	assert.Contains(t, result, "[REDACTED]")
}

func TestMaskPhone(t *testing.T) {
	masker := NewPIIMasker()
	
	tests := []string{
		"Phone: 555-123-4567",
		"Call +1 (555) 123-4567",
	}
	
	for _, test := range tests {
		result := masker.MaskPII(test)
		assert.Contains(t, result, "[REDACTED]")
	}
}

func TestMaskPassword(t *testing.T) {
	masker := NewPIIMasker()
	
	text := "password='secret123'"
	result := masker.MaskPII(text)
	assert.Contains(t, result, "[REDACTED]")
}

func TestMaskMap(t *testing.T) {
	masker := NewPIIMasker()
	
	data := map[string]interface{}{
		"name":     "John Doe",
		"email":    "john@example.com",
		"password": "secret123",
		"phone":    "555-123-4567",
	}
	
	result := masker.MaskMap(data)
	assert.Contains(t, result["email"].(string), "[REDACTED]")
	assert.Contains(t, result["password"].(string), "[REDACTED]")
	assert.Contains(t, result["phone"].(string), "[REDACTED]")
	assert.Equal(t, "John Doe", result["name"])
}

func TestShouldMask(t *testing.T) {
	assert.True(t, ShouldMask("password"))
	assert.True(t, ShouldMask("PASSWORD"))
	assert.True(t, ShouldMask("api_key"))
	assert.False(t, ShouldMask("username"))
}

func TestMaskJSONField(t *testing.T) {
	tests := []struct {
		key    string
		value  interface{}
		result interface{}
	}{
		{"password", "secret123", "s******123"},
		{"api_key", "abcdef", "a****f"},
		{"short", "ab", "***"},
		{"username", "john", "john"},
	}
	
	for _, tt := range tests {
		result := MaskJSONField(tt.key, tt.value)
		if str, ok := tt.value.(string); ok && !ShouldMask(tt.key) {
			assert.Equal(t, str, result)
		} else if ShouldMask(tt.key) {
			assert.NotEqual(t, tt.value, result)
		}
	}
}
func TestMaskString(t *testing.T) {
	masker := NewPIIMasker()
	text := "Support email: support@example.com"
	result := masker.MaskString(text)
	assert.Contains(t, result, "[REDACTED]")
	assert.NotContains(t, result, "support@example.com")
}

func TestMaskMap_WithSlice(t *testing.T) {
	masker := NewPIIMasker()
	data := map[string]interface{}{
		"tags": []string{"admin", "user_123", "credit: 4111-1111-1111-1111"},
	}
	result := masker.MaskMap(data)
	slice, ok := result["tags"].([]string)
	if !ok {
		t.Fatalf("Expected []interface{} or []string")
	}
	// Each element should be masked if contains PII
	if len(slice) != 3 {
		t.Fatalf("Expected 3 tags")
	}
	assert.Contains(t, slice[2], "[REDACTED]")
	assert.Equal(t, "admin", slice[0])
	assert.Equal(t, "user_123", slice[1])
}

func TestMaskMap_WithNestedMap(t *testing.T) {
	masker := NewPIIMasker()
	data := map[string]interface{}{
		"user": map[string]interface{}{
			"name":    "Alice",
			"email":   "alice@example.com",
			"account": "ACC123",
		},
	}
	result := masker.MaskMap(data)
	nested, ok := result["user"].(map[string]interface{})
	if !ok {
		t.Fatalf("Expected nested map")
	}
	assert.Equal(t, "Alice", nested["name"])
	assert.Contains(t, nested["email"].(string), "[REDACTED]")
	assert.Equal(t, "ACC123", nested["account"])
}

func TestMaskMap_WithOtherTypes(t *testing.T) {
	masker := NewPIIMasker()
	data := map[string]interface{}{
		"count":    42,
		"active":   true,
		"balance":  1234.56,
		"email":    "bob@example.com",
	}
	result := masker.MaskMap(data)
	assert.Equal(t, 42, result["count"])
	assert.Equal(t, true, result["active"])
	assert.Equal(t, 1234.56, result["balance"])
	assert.Contains(t, result["email"].(string), "[REDACTED]")
}

func TestMaskJSONField_NonString(t *testing.T) {
	// Should return "***" when value is not string but key is sensitive
	result := MaskJSONField("password", 12345)
	assert.Equal(t, "***", result)
	result = MaskJSONField("api_key", true)
	assert.Equal(t, "***", result)
}
