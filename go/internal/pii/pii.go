// Package pii provides PII filtering for logs and data
package pii

import (
	"regexp"
	"strings"
)

// PIIMasker handles filtering of sensitive data
type PIIMasker struct {
	patterns []*regexp.Regexp
	masks    map[string]string
}

// NewPIIMasker creates a new PII masker
func NewPIIMasker() *PIIMasker {
	return &PIIMasker{
		patterns: []*regexp.Regexp{
			// Email addresses
			regexp.MustCompile(`(?i)[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}`),
			// Credit card numbers (Visa, Mastercard, etc.)
			regexp.MustCompile(`\b(?:\d[ -]*?){13,16}\b`),
			// SSN
			regexp.MustCompile(`\b\d{3}-?\d{2}-?\d{4}\b`),
			// Phone numbers
			regexp.MustCompile(`\b(?:\+?1[-.\s]?)?\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4}\b`),
			// API keys (common patterns)
			regexp.MustCompile(`(?i)(api[_-]?key|apikey)["']?\s*[:=]\s*["']?[\w-]{20,}["']?`),
			// Password in config
			regexp.MustCompile(`(?i)(password|passwd|pwd)["']?\s*[:=]\s*["']?[^"',\s]+["']?`),
		},
		masks: map[string]string{
			"EMAIL":     "[EMAIL REDACTED]",
			"CREDIT":    "[CREDIT CARD REDACTED]",
			"SSN":       "[SSN REDACTED]",
			"PHONE":     "[PHONE REDACTED]",
			"API_KEY":   "[API KEY REDACTED]",
			"PASSWORD":  "[PASSWORD REDACTED]",
		},
	}
}

// MaskPII applies masking to sensitive data in text
func (m *PIIMasker) MaskPII(text string) string {
	for _, pattern := range m.patterns {
		text = pattern.ReplaceAllString(text, "[REDACTED]")
	}
	return text
}

// MaskMap applies PII masking to map values
func (m *PIIMasker) MaskMap(data map[string]interface{}) map[string]interface{} {
	result := make(map[string]interface{})

	for key, value := range data {
		if str, ok := value.(string); ok {
			// Check if this key should be masked regardless of content
			if ShouldMask(key) {
				result[key] = "[REDACTED]"
			} else {
				result[key] = m.MaskPII(str)
			}
		} else if nested, ok := value.(map[string]interface{}); ok {
			result[key] = m.MaskMap(nested)
		} else if slice, ok := value.([]string); ok {
			masked := make([]string, len(slice))
			for i, s := range slice {
				masked[i] = m.MaskPII(s)
			}
			result[key] = masked
		} else {
			result[key] = value
		}
	}

	return result
}

// MaskString applies masking to a string value
func (m *PIIMasker) MaskString(value string) string {
	return m.MaskPII(value)
}

// PISensitiveKeys contains field names that should be masked
var PISensitiveKeys = map[string]bool{
	"password":     true,
	"passwd":       true,
	"pwd":          true,
	"secret":       true,
	"api_key":      true,
	"apikey":       true,
	"token":        true,
	"ssn":          true,
	"credit_card":  true,
	"card_number":  true,
	"email":        true,
	"phone":        true,
}

// ShouldMask checks if a key should be masked
func ShouldMask(key string) bool {
	return PISensitiveKeys[strings.ToLower(key)]
}

// MaskJSONField masks specific fields in JSON-like structures
func MaskJSONField(key string, value interface{}) interface{} {
	if ShouldMask(key) {
		if str, ok := value.(string); ok {
			if len(str) > 4 {
				return str[:2] + strings.Repeat("*", len(str)-4) + str[len(str)-2:]
			}
			return "****"
		}
		return "***"
	}
	return value
}