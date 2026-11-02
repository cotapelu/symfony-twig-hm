// Package tracing provides OpenTelemetry distributed tracing utilities
package tracing

import (
	"context"
	"log"

	"go.opentelemetry.io/otel"
	"go.opentelemetry.io/otel/exporters/jaeger"
	"go.opentelemetry.io/otel/sdk/resource"
	sdktrace "go.opentelemetry.io/otel/sdk/trace"
	semconv "go.opentelemetry.io/otel/semconv/v1.17.0"
	"go.opentelemetry.io/otel/trace"
)

var tracer trace.Tracer

// InitTracer initializes OpenTelemetry with Jaeger exporter
func InitTracer(jaegerEndpoint string, serviceName string) (func(), error) {
	// Create Jaeger exporter
	exp, err := jaeger.New(jaeger.WithCollectorEndpoint(jaeger.WithEndpoint(jaegerEndpoint)))
	if err != nil {
		return nil, err
	}

	// Create resource
	res, err := resource.New(context.Background(),
		resource.WithAttributes(
			semconv.ServiceNameKey.String(serviceName),
			semconv.ServiceVersionKey.String("1.0.0"),
		),
	)
	if err != nil {
		return nil, err
	}

	// Create tracer provider
	tp := sdktrace.NewTracerProvider(
		sdktrace.WithBatcher(exp),
		sdktrace.WithResource(res),
		sdktrace.WithSampler(sdktrace.AlwaysSample()),
	)

	// Register tracer provider
	otel.SetTracerProvider(tp)

	// Get tracer
	tracer = otel.Tracer(serviceName)

	return func() {
		if err := tp.Shutdown(context.Background()); err != nil {
			log.Printf("Error shutting down tracer provider: %v", err)
		}
	}, nil
}

// StartSpan starts a new span for tracing
func StartSpan(ctx context.Context, name string, opts ...trace.SpanStartOption) (context.Context, trace.Span) {
	if tracer == nil {
		tracer = otel.Tracer("default")
	}
	return tracer.Start(ctx, name, opts...)
}

// StartSpanFromContext starts a span from gin context
func StartSpanFromContext(ctx context.Context, name string) (context.Context, trace.Span) {
	return StartSpan(ctx, name)
}

// TraceWithContext adds trace context to context
func TraceWithContext(ctx context.Context, tp string) context.Context {
	return context.WithValue(ctx, "traceparent", tp)
}

// GetTraceID extracts trace ID from context
func GetTraceID(ctx context.Context) string {
	span := trace.SpanFromContext(ctx)
	if span != nil {
		sc := span.SpanContext()
		if sc.TraceID().IsValid() {
			return sc.TraceID().String()
		}
	}
	return ""
}