package middleware

import (
    "context"
    "github.com/google/uuid"
    "github.com/gin-gonic/gin"
)

type correlationIDKey struct{}

// CorrelationID returns a Gin handler that injects X-Request-ID into context and response.
func CorrelationID() gin.HandlerFunc {
    return func(c *gin.Context) {
        reqID := c.GetHeader("X-Request-ID")
        if reqID == "" {
            reqID = uuid.New().String()
        }
        // store in request context
        ctx := context.WithValue(c.Request.Context(), correlationIDKey{}, reqID)
        c.Request = c.Request.WithContext(ctx)
        // set response header
        c.Header("X-Request-ID", reqID)
        c.Next()
    }
}

// FromContext extracts the request ID from the context, if present.
func FromContext(ctx context.Context) string {
    if id, ok := ctx.Value(correlationIDKey{}).(string); ok {
        return id
    }
    return ""
}
