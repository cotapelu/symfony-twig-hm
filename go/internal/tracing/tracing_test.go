package tracing

import (
	"context"
	"testing"
)

func TestStartSpan(t *testing.T) {
	ctx, span := StartSpan(context.Background(), "test-span")
	defer span.End()
	if ctx == nil {
		t.Fatal("StartSpan returned nil context")
	}
	if span == nil {
		t.Fatal("StartSpan returned nil span")
	}
}

func TestGetTraceIDFromContext(t *testing.T) {
	// Initialize a real tracer to get non-zero trace IDs
	shutdown, err := InitTracer("http://localhost:12345", "test-service")
	if err != nil {
		t.Fatalf("InitTracer failed: %v", err)
	}
	defer shutdown()

	ctx, span := StartSpan(context.Background(), "test-span")
	defer span.End()
	traceID := GetTraceID(ctx)
	if traceID == "" {
		t.Fatal("GetTraceID returned empty")
	}
	// Trace ID should be 32 hex characters
	if len(traceID) != 32 {
		t.Errorf("Trace ID length = %d, want 32", len(traceID))
	}
}

func TestGetTraceIDFromContext_NoSpan(t *testing.T) {
	traceID := GetTraceID(context.Background())
	if traceID != "" {
		t.Errorf("GetTraceID without span = %q, want empty", traceID)
	}
}

func TestTraceWithContext(t *testing.T) {
	ctx := TraceWithContext(context.Background(), "my-trace")
	val := ctx.Value("traceparent")
	if val != "my-trace" {
		t.Errorf("TraceWithContext value = %v, want my-trace", val)
	}
}

func TestInitTracer_Dummy(t *testing.T) {
	shutdown, err := InitTracer("http://localhost:12345", "test-service")
	if err != nil {
		t.Fatalf("InitTracer error = %v", err)
	}
	if shutdown == nil {
		t.Fatal("InitTracer returned nil shutdown")
	}
	shutdown()
}
