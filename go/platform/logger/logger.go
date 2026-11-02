package logger

import (
	"context"
	"encoding/json"
	"fmt"
	"time"

	"go.uber.org/zap"
	"go.uber.org/zap/zapcore"
)

var (
	// Global logger instance
	Logger *zap.Logger
	Sugar  *zap.SugaredLogger
)

// LogField represents a key-value pair for structured logging
type LogField struct {
	Key   string
	Value interface{}
}

// LogType defines the type/level of log
type LogType string

const (
	LogTypeDebug LogType = "debug"
	LogTypeInfo  LogType = "info"
	LogTypeWarn  LogType = "warn"
	LogTypeError LogType = "error"
	LogTypeFatal LogType = "fatal"
)

// RequestIDKey is the key for request ID in context
type RequestIDKey struct{}

// InitLogger initializes the global logger
func InitLogger(level string) (*zap.Logger, *zap.SugaredLogger, error) {
	var zapLevel zapcore.Level
	switch level {
	case "debug":
		zapLevel = zapcore.DebugLevel
	case "info":
		zapLevel = zapcore.InfoLevel
	case "warn":
		zapLevel = zapcore.WarnLevel
	case "error":
		zapLevel = zapcore.ErrorLevel
	default:
		zapLevel = zapcore.InfoLevel
	}

	// Format time as RFC3339
	config := zap.Config{
		Level:            zap.NewAtomicLevelAt(zapLevel),
		Development:      false,
		Encoding:         "json",
		EncoderConfig:    zap.NewProductionEncoderConfig(),
		OutputPaths:      []string{"stdout"},
		ErrorOutputPaths: []string{"stderr"},
	}

	config.EncoderConfig.TimeKey = "timestamp"
	config.EncoderConfig.EncodeTime = zapcore.ISO8601TimeEncoder
	config.EncoderConfig.EncodeLevel = zapcore.CapitalLevelEncoder
	config.EncoderConfig.EncodeCaller = zapcore.ShortCallerEncoder

	logger, err := config.Build()
	if err != nil {
		return nil, nil, fmt.Errorf("failed to create logger: %w", err)
	}

	Logger = logger
	Sugar = logger.Sugar()

	Logger.Info("Logger initialized successfully")
	return logger, Sugar, nil
}

// Log writes a structured log entry
func Log(ctx context.Context, logType LogType, message string, fields ...LogField) {
	// Get request ID from context if available
	if requestID := GetRequestID(ctx); requestID != "" {
		fields = append(fields, LogString("request_id", requestID))
	}

	// Convert LogField slices to zap.Field slices
	zapFields := make([]zap.Field, 0, len(fields))
	for _, field := range fields {
		switch field.Value.(type) {
		case string:
			v := field.Value.(string)
			zapFields = append(zapFields, zap.String(field.Key, v))
		case int:
			v := field.Value.(int)
			zapFields = append(zapFields, zap.Int64(field.Key, int64(v)))
		case int8:
			v := field.Value.(int8)
			zapFields = append(zapFields, zap.Int64(field.Key, int64(v)))
		case int16:
			v := field.Value.(int16)
			zapFields = append(zapFields, zap.Int64(field.Key, int64(v)))
		case int32:
			v := field.Value.(int32)
			zapFields = append(zapFields, zap.Int64(field.Key, int64(v)))
		case int64:
			v := field.Value.(int64)
			zapFields = append(zapFields, zap.Int64(field.Key, v))
		case uint:
			v := field.Value.(uint)
			zapFields = append(zapFields, zap.Uint64(field.Key, uint64(v)))
		case uint8:
			v := field.Value.(uint8)
			zapFields = append(zapFields, zap.Uint64(field.Key, uint64(v)))
		case uint16:
			v := field.Value.(uint16)
			zapFields = append(zapFields, zap.Uint64(field.Key, uint64(v)))
		case uint32:
			v := field.Value.(uint32)
			zapFields = append(zapFields, zap.Uint64(field.Key, uint64(v)))
		case uint64:
			v := field.Value.(uint64)
			zapFields = append(zapFields, zap.Uint64(field.Key, v))
		case float32:
			v := field.Value.(float32)
			zapFields = append(zapFields, zap.Float64(field.Key, float64(v)))
		case float64:
			v := field.Value.(float64)
			zapFields = append(zapFields, zap.Float64(field.Key, v))
		case bool:
			v := field.Value.(bool)
			zapFields = append(zapFields, zap.Bool(field.Key, v))
		case error:
			v := field.Value.(error)
			if v != nil {
				zapFields = append(zapFields, zap.NamedError(field.Key, v))
			}
		default:
			zapFields = append(zapFields, zap.Any(field.Key, field.Value))
		}
	}

	// Switch on log type
	switch logType {
	case LogTypeDebug:
		Logger.Debug(message, zapFields...)
	case LogTypeInfo:
		Logger.Info(message, zapFields...)
	case LogTypeWarn:
		Logger.Warn(message, zapFields...)
	case LogTypeError:
		Logger.Error(message, zapFields...)
	case LogTypeFatal:
		Logger.Fatal(message, zapFields...)
	}
}

// LogString creates a LogField for string values
func LogString(key, value string) LogField {
	return LogField{Key: key, Value: value}
}

// LogInt creates a LogField for int values
func LogInt(key string, value int) LogField {
	return LogField{Key: key, Value: value}
}

// LogDuration creates a LogField for duration values
func LogDuration(key string, value time.Duration) LogField {
	return LogField{Key: key, Value: value.String()}
}

// LogError creates a LogField for error values
func LogError(key string, value error) LogField {
	return LogField{Key: key, Value: value}
}

// WithRequestID creates a new context with the request ID
func WithRequestID(ctx context.Context, requestID string) context.Context {
	return context.WithValue(ctx, RequestIDKey{}, requestID)
}

// GetRequestID retrieves the request ID from context
func GetRequestID(ctx context.Context) string {
	if requestID, ok := ctx.Value(RequestIDKey{}).(string); ok {
		return requestID
	}
	return ""
}

// FormatDuration formats a duration for logging
func FormatDuration(start time.Time) string {
	return time.Since(start).String()
}

// LogRequest logs an HTTP request
func LogRequest(ctx context.Context, method, path string, statusCode int, duration time.Duration, err error) {
	fields := []LogField{
		LogString("method", method),
		LogString("path", path),
		LogInt("status_code", statusCode),
		LogDuration("duration", duration),
	}

	if err != nil {
		fields = append(fields, LogError("error", err))
		Log(ctx, LogTypeError, "Request failed", fields...)
	} else {
		Log(ctx, LogTypeInfo, "Request completed", fields...)
	}
}

// LogFunctionCall logs a function entry/exit
type FunctionLogger struct {
	ctx      context.Context
	start    time.Time
	function string
	fields   []LogField
}

// NewFunctionLogger creates a new function logger
// NewFunctionLogger creates a new function logger
func NewFunctionLogger(ctx context.Context, function string, fields ...LogField) *FunctionLogger {
	start := time.Now()
	fLog := &FunctionLogger{
		ctx:      ctx,
		start:    start,
		function: function,
		fields:   fields,
	}
	Log(ctx, LogTypeInfo, fmt.Sprintf("Entering %s", function), fields...)
	return fLog
}

// Done logs function completion
func (f *FunctionLogger) Done(err error) {
	fields := f.fields
	fields = append(fields, LogDuration("duration", time.Since(f.start)))

	if err != nil {
		fields = append(fields, LogError("error", err))
		Log(f.ctx, LogTypeError, fmt.Sprintf("%s failed", f.function), fields...)
	} else {
		Log(f.ctx, LogTypeInfo, fmt.Sprintf("%s completed", f.function), fields...)
	}
}

// LogMetric logs a metric for monitoring
func LogMetric(ctx context.Context, metricName string, value float64, tags map[string]string) {
	fields := []LogField{LogString("metric", metricName), LogInt("value", int(value))}
	for k, v := range tags {
		fields = append(fields, LogString(k, v))
	}
	Log(ctx, LogTypeInfo, "Metric recorded", fields...)
}

// ToJSON converts logging context to JSON for structured output
func ToJSON(fields ...LogField) string {
	result := make(map[string]interface{})
	for _, field := range fields {
		if field.Value != nil {
			result[field.Key] = field.Value
		}
	}
	bytes, _ := json.Marshal(result)
	return string(bytes)
}
