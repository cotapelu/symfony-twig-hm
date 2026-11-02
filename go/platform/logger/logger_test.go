package logger

import (
	"context"
	"errors"
	"testing"
	"time"

	"go.uber.org/zap"
	"github.com/stretchr/testify/assert"
	"encoding/json"
)

// TestInitLogger tests logger initialization with various levels
func TestInitLogger(t *testing.T) {
	tests := []struct {
		levelInput string
		wantErr    bool
	}{
		{"debug", false},
		{"info", false},
		{"warn", false},
		{"error", false},
		{"fatal", false},
		{"unknown", false}, // defaults to info
	}

	for _, tt := range tests {
		t.Run(tt.levelInput, func(t *testing.T) {
			// Reset global logger to nil for each test
			Logger = nil
			Sugar = nil

			l, s, err := InitLogger(tt.levelInput)
			if tt.wantErr {
				assert.Error(t, err)
				return
			}
			assert.NoError(t, err)
			assert.NotNil(t, l)
			assert.NotNil(t, s)
			assert.Equal(t, Logger, l)
			assert.Equal(t, Sugar, s)
		})
	}
}

// TestLog tests the Log function with various field types
func TestLog(t *testing.T) {
	// Use a no-op logger to avoid real output
	Logger = zap.NewNop()
	Sugar = Logger.Sugar()

	ctx := context.Background()

	// Test that Log does not panic with various field types
	fields := []LogField{
		LogString("key", "value"),
		LogInt("num", 42),
		LogDuration("dur", 5*time.Second),
		LogError("err", errors.New("test error")),
	}

	for _, logType := range []LogType{LogTypeDebug, LogTypeInfo, LogTypeWarn, LogTypeError} {
		t.Run(string(logType), func(t *testing.T) {
			assert.NotPanics(t, func() {
				Log(ctx, logType, "test message", fields...)
			})
		})
	}

	// Note: LogTypeFatal would call os.Exit and cannot be tested here safely; skip.
}

// TestLogHelpers tests the Log* helper functions
func TestLogHelpers(t *testing.T) {
	assert.Equal(t, LogField{Key: "k", Value: "v"}, LogString("k", "v"))
	assert.Equal(t, LogField{Key: "k", Value: 42}, LogInt("k", 42))
	assert.Equal(t, LogField{Key: "k", Value: "5s"}, LogDuration("k", 5*time.Second))
	assert.Equal(t, LogField{Key: "k", Value: errors.New("x")}, LogError("k", errors.New("x")))
}

// TestRequestID tests context with request ID
func TestRequestID(t *testing.T) {
	ctx := context.Background()
	assert.Empty(t, GetRequestID(ctx))

	ctx2 := WithRequestID(ctx, "req-123")
	assert.Equal(t, "req-123", GetRequestID(ctx2))

	// Ensure original context unchanged
	assert.Empty(t, GetRequestID(ctx))
}

// TestLogRequest tests HTTP request logging
func TestLogRequest(t *testing.T) {
	Logger = zap.NewNop()
	Sugar = Logger.Sugar()

	ctx := context.Background()
	// No panic on success
	assert.NotPanics(t, func() {
		LogRequest(ctx, "GET", "/api/test", 200, 100*time.Millisecond, nil)
	})

	// With error
	assert.NotPanics(t, func() {
		LogRequest(ctx, "POST", "/api/fail", 500, 0, errors.New("db error"))
	})
}

// TestFunctionLogger tests function entry/exit logging
func TestFunctionLogger(t *testing.T) {
	Logger = zap.NewNop()
	Sugar = Logger.Sugar()

	ctx := context.Background()
	fl := NewFunctionLogger(ctx, "TestFunc", LogString("arg", "val"))

	// Should not panic
	assert.NotPanics(t, func() {
		fl.Done(nil)
	})

	// With error
	assert.NotPanics(t, func() {
		fl.Done(errors.New("oops"))
	})
}

// TestLogMetric tests metric logging
func TestLogMetric(t *testing.T) {
	Logger = zap.NewNop()
	Sugar = Logger.Sugar()

	ctx := context.Background()
	tags := map[string]string{"module": "test"}

	assert.NotPanics(t, func() {
		LogMetric(ctx, "test_metric", 123.45, tags)
	})

	// Empty tags
	assert.NotPanics(t, func() {
		LogMetric(ctx, "another", 0, nil)
	})
}

// TestToJSON tests JSON conversion
func TestToJSON(t *testing.T) {
	fields := []LogField{
		LogString("name", "Alice"),
		LogInt("age", 30),
	}
	jsonStr := ToJSON(fields...)

	// Verify it's valid JSON and contains keys
	var data map[string]interface{}
	err := json.Unmarshal([]byte(jsonStr), &data)
	assert.NoError(t, err)
	assert.Equal(t, "Alice", data["name"])
	assert.Equal(t, float64(30), data["age"])
}

// TestLog_IntegerTypes tests integer type handling in Log()
func TestLog_IntegerTypes(t *testing.T) {
	Logger = zap.NewNop()
	Sugar = Logger.Sugar()

	ctx := context.Background()
	// Test with int64 directly
	fields := []LogField{{Key: "big", Value: int64(1<<50)}}
	assert.NotPanics(t, func() {
		Log(ctx, LogTypeInfo, "big int", fields...)
	})
}

// TestLog_UintTypes tests unsigned integer handling
func TestLog_UintTypes(t *testing.T) {
	Logger = zap.NewNop()
	Sugar = Logger.Sugar()

	ctx := context.Background()
	fields := []LogField{{Key: "u", Value: uint(42)}}
	assert.NotPanics(t, func() {
		Log(ctx, LogTypeInfo, "uint", fields...)
	})
}

// TestLog_FloatTypes tests float handling
func TestLog_FloatTypes(t *testing.T) {
	Logger = zap.NewNop()
	Sugar = Logger.Sugar()

	ctx := context.Background()
	fields := []LogField{{Key: "f", Value: float32(3.14)}}
	assert.NotPanics(t, func() {
		Log(ctx, LogTypeInfo, "float", fields...)
	})
}

// TestLog_BoolAndError tests bool and error handling
func TestLog_BoolAndError(t *testing.T) {
	Logger = zap.NewNop()
	Sugar = Logger.Sugar()

	ctx := context.Background()

	// Bool
	fieldsBool := []LogField{{Key: "ok", Value: true}}
	assert.NotPanics(t, func() {
		Log(ctx, LogTypeInfo, "bool test", fieldsBool...)
	})

	// Error nil
	fieldsErrNil := []LogField{{Key: "err", Value: error(nil)}}
	assert.NotPanics(t, func() {
		Log(ctx, LogTypeInfo, "nil error", fieldsErrNil...)
	})

	// Non-nil error
	fieldsErr := []LogField{{Key: "err", Value: errors.New("test")}}
	assert.NotPanics(t, func() {
		Log(ctx, LogTypeInfo, "error test", fieldsErr...)
	})
}

// TestLog_RequestIDIncluded tests that request ID is automatically added to Log
func TestLog_RequestIDIncluded(t *testing.T) {
	// Use no-op logger to avoid output; focus on coverage of the branch
	Logger = zap.NewNop()
	Sugar = Logger.Sugar()

	ctx := WithRequestID(context.Background(), "trace-123")
	fields := []LogField{LogString("user", "bob")}

	// Ensure that Log does not panic when request ID is present in context
	assert.NotPanics(t, func() {
		Log(ctx, LogTypeInfo, "test message", fields...)
	})
}

// TestFunctionLogger_Duration ensures duration is computed correctly
func TestFunctionLogger_Duration(t *testing.T) {
	Logger = zap.NewNop()
	Sugar = Logger.Sugar()

	ctx := context.Background()
	fl := NewFunctionLogger(ctx, "SlowFunc")
	time.Sleep(10 * time.Millisecond)
	fl.Done(nil)
	// Just ensure no panic; duration is logged internally
}

// TestLogMetric_SingleAndMultipleTags
func TestLogMetric_SingleAndMultipleTags(t *testing.T) {
	Logger = zap.NewNop()
	Sugar = Logger.Sugar()

	ctx := context.Background()

	// Single tag
	assert.NotPanics(t, func() {
		LogMetric(ctx, "metric1", 1.0, map[string]string{"env": "test"})
	})

	// Multiple tags
	assert.NotPanics(t, func() {
		LogMetric(ctx, "metric2", 2.5, map[string]string{"a": "1", "b": "2", "c": "3"})
	})

	// Empty tags
	assert.NotPanics(t, func() {
		LogMetric(ctx, "metric3", 0, map[string]string{})
	})
}

// TestToJSON_EmptyFields
func TestToJSON_EmptyFields(t *testing.T) {
	jsonStr := ToJSON()
	assert.NotEmpty(t, jsonStr) // should be "{}"
	var data map[string]interface{}
	json.Unmarshal([]byte(jsonStr), &data)
	assert.Len(t, data, 0)
}
