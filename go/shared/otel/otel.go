package otel

import (
	"context"

	"go.opentelemetry.io/otel"
	"go.opentelemetry.io/otel/attribute"
	"go.opentelemetry.io/otel/exporters/otlp/otlptrace/otlptracehttp"
	"go.opentelemetry.io/otel/sdk/resource"
	sdktrace "go.opentelemetry.io/otel/sdk/trace"
	semconv "go.opentelemetry.io/otel/semconv/v1.17.0"
	"go.opentelemetry.io/otel/trace"
	"github.com/gin-gonic/gin"
)

func InitTracer(serviceName string) (trace.Tracer, error) {
	ctx := context.Background()

	exporter, err := otlptracehttp.New(ctx,
		otlptracehttp.WithEndpoint("localhost:4318"),
		otlptracehttp.WithInsecure(),
	)
	if err != nil {
		return nil, err
	}

	tp := sdktrace.NewTracerProvider(
		sdktrace.WithBatcher(exporter),
		sdktrace.WithResource(resource.NewWithAttributes(
			semconv.SchemaURL,
			semconv.ServiceNameKey.String(serviceName),
			semconv.ServiceVersionKey.String("1.0.0"),
		)),
	)
	otel.SetTracerProvider(tp)

	return tp.Tracer(serviceName), nil
}

// InstrumentHandler wraps gin handler with tracing
func InstrumentHandler(next func(ctx context.Context)) func(ctx context.Context) {
	return func(ctx context.Context) {
		tracer := otel.Tracer("handler")
		_, span := tracer.Start(ctx, "handler.request")
		defer span.End()
		next(ctx)
	}
}

// Middleware for gin
func TracingMiddleware() gin.HandlerFunc {
	return func(c *gin.Context) {
		ctx, span := otel.Tracer("http").Start(c.Request.Context(), c.FullPath())
		defer span.End()
		
		span.SetAttributes(
			attribute.String("http.method", c.Request.Method),
			attribute.String("http.path", c.Request.URL.Path),
		)
		
		c.Request = c.Request.WithContext(ctx)
		c.Next()
		
		span.SetAttributes(attribute.Int("http.status_code", c.Writer.Status()))
	}
}