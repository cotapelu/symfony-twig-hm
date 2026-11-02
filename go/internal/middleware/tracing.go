package middleware

import (
	"context"

	"go.opentelemetry.io/otel"
	"go.opentelemetry.io/otel/attribute"
	"go.opentelemetry.io/otel/propagation"
	"go.opentelemetry.io/otel/trace"

	"github.com/gin-gonic/gin"
)

// TracingMiddleware provides OpenTelemetry distributed tracing
func TracingMiddleware(tracerName string) gin.HandlerFunc {
	return func(c *gin.Context) {
		// Extract trace context from headers
		propagator := otel.GetTextMapPropagator()
		ctx := propagator.Extract(c.Request.Context(), propagation.HeaderCarrier(c.Request.Header))
		
		// Start span
		tracer := otel.Tracer(tracerName)
		ctx, span := tracer.Start(ctx, c.Request.Method+" "+c.Request.URL.Path,
			trace.WithSpanKind(trace.SpanKindServer),
		)
		
		// Store span in context
		c.Request = c.Request.WithContext(ctx)
		
		// Add trace ID to response headers
		traceID := span.SpanContext().TraceID().String()
		c.Header("X-Trace-ID", traceID)
		
		// Process request
		c.Next()
		
		// Set span status based on response
		if c.Writer.Status() >= 400 {
			span.SetAttributes(attribute.Int("http.status_code", c.Writer.Status()))
		}
		
		// End span
		span.End()
	}
}

// GetTraceIDFromContext extracts trace ID for logging
func GetTraceIDFromContext(ctx context.Context) string {
	if span := trace.SpanFromContext(ctx); span != nil {
		return span.SpanContext().TraceID().String()
	}
	return ""
}