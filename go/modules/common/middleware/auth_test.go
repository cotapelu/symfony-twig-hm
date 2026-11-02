package middleware

import (
	"context"
	"net/http"
	"net/http/httptest"
	"testing"

	"github.com/stretchr/testify/assert"
)

func TestAuthMiddleware(t *testing.T) {
	tests := []struct {
		name           string
		authHeader     string
		wantStatus     int
		wantUserID     string
		wantRoles      []string
		setupContext   func(r *http.Request) *http.Request
		afterMiddleware func(w http.ResponseWriter, r *http.Request) bool
	}{
		{
			name:         "missing Authorization header",
			authHeader:   "",
			wantStatus:   http.StatusUnauthorized,
			wantUserID:   "",
			wantRoles:    nil,
		},
		{
			name:         "empty Authorization header",
			authHeader:   " ",
			wantStatus:   http.StatusUnauthorized,
			wantUserID:   "",
			wantRoles:    nil,
		},
		{
			name:         "non-Bearer token",
			authHeader:   "Token abc123",
			wantStatus:   http.StatusUnauthorized,
			wantUserID:   "",
			wantRoles:    nil,
		},
		{
			name:         "Bearer token without user- prefix",
			authHeader:   "Bearer abc123",
			wantStatus:   http.StatusUnauthorized,
			wantUserID:   "",
			wantRoles:    nil,
		},
		{
			name:         "Bearer token with empty user ID",
			authHeader:   "Bearer user-",
			wantStatus:   http.StatusUnauthorized,
			wantUserID:   "",
			wantRoles:    nil,
		},
		{
			name:         "valid token sets context and calls next",
			authHeader:   "Bearer user-42",
			wantStatus:   http.StatusOK,
			wantUserID:   "42",
			wantRoles:    []string{"user"},
			setupContext: func(r *http.Request) *http.Request {
				// next handler will check context
				return r
			},
			afterMiddleware: func(w http.ResponseWriter, r *http.Request) bool {
				// Verify context is set
				uid, ok := GetUserID(r.Context())
				return ok && uid == "42"
			},
		},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			// Create a test handler that asserts context values and returns status
			handler := http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
				// If afterMiddleware provided, use it to validate
				if tt.afterMiddleware != nil {
					if !tt.afterMiddleware(w, r) {
						http.Error(w, `{"error": "validation failed"}`, http.StatusInternalServerError)
						return
					}
				}
				w.WriteHeader(http.StatusOK)
				w.Write([]byte(`{"ok":true}`))
			})

			// Build request
			req := httptest.NewRequest(http.MethodGet, "/", nil)
			if tt.setupContext != nil {
				req = tt.setupContext(req)
			}
			if tt.authHeader != "" {
				req.Header.Set("Authorization", tt.authHeader)
			}
			rr := httptest.NewRecorder()

			// Wrap handler with middleware
			middleware := AuthMiddleware(handler)
			middleware.ServeHTTP(rr, req)

			assert.Equal(t, tt.wantStatus, rr.Code, "status code mismatch")
			if tt.wantStatus == http.StatusOK {
				assert.Equal(t, `{"ok":true}`, rr.Body.String())
			}
		})
	}
}

func TestRequireRole(t *testing.T) {
	// Helper to create request with context containing roles
	withRoles := func(roles []string) *http.Request {
		ctx := context.Background()
		ctx = context.WithValue(ctx, RolesKey, roles)
		req := httptest.NewRequest(http.MethodGet, "/", nil)
		return req.WithContext(ctx)
	}

	tests := []struct {
		name         string
		requiredRole string
		req          *http.Request
		wantStatus   int
	}{
		{
			name:         "no roles in context",
			requiredRole: "admin",
			req:          httptest.NewRequest(http.MethodGet, "/", nil),
			wantStatus:   http.StatusForbidden,
		},
		{
			name:         "user has required role",
			requiredRole: "user",
			req:          withRoles([]string{"user"}),
			wantStatus:   http.StatusOK,
		},
		{
			name:         "user has multiple roles including required",
			requiredRole: "moderator",
			req:          withRoles([]string{"user", "moderator", "editor"}),
			wantStatus:   http.StatusOK,
		},
		{
			name:         "user lacks required role",
			requiredRole: "admin",
			req:          withRoles([]string{"user", "editor"}),
			wantStatus:   http.StatusForbidden,
		},
		{
			name:         "empty roles list",
			requiredRole: "admin",
			req:          withRoles([]string{}),
			wantStatus:   http.StatusForbidden,
		},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			handler := http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
				w.WriteHeader(http.StatusOK)
				w.Write([]byte(`{"ok":true}`))
			})

			middleware := RequireRole(tt.requiredRole)(handler)
			rr := httptest.NewRecorder()
			middleware.ServeHTTP(rr, tt.req)

			assert.Equal(t, tt.wantStatus, rr.Code, "status code mismatch")
		})
	}
}

func TestGetUserID(t *testing.T) {
	tests := []struct {
		name  string
		ctx   context.Context
		want  string
		wantOk bool
	}{
		{
			name:  "user ID present",
			ctx:   context.WithValue(context.Background(), UserIDKey, "123"),
			want:  "123",
			wantOk: true,
		},
		{
			name:  "user ID of different type",
			ctx:   context.WithValue(context.Background(), UserIDKey, 123),
			want:  "",
			wantOk: false,
		},
		{
			name:  "no user ID",
			ctx:   context.Background(),
			want:  "",
			wantOk: false,
		},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			got, ok := GetUserID(tt.ctx)
			assert.Equal(t, tt.want, got)
			assert.Equal(t, tt.wantOk, ok)
		})
	}
}

func TestGetRoles(t *testing.T) {
	tests := []struct {
		name string
		ctx  context.Context
		want []string
	}{
		{
			name: "roles present",
			ctx:  context.WithValue(context.Background(), RolesKey, []string{"admin", "user"}),
			want: []string{"admin", "user"},
		},
		{
			name: "roles of wrong type",
			ctx:  context.WithValue(context.Background(), RolesKey, "not-slice"),
			want: []string{},
		},
		{
			name: "no roles",
			ctx:  context.Background(),
			want: []string{},
		},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			got := GetRoles(tt.ctx)
			assert.Equal(t, tt.want, got)
		})
	}
}

func TestAuthorizeResource(t *testing.T) {
	tests := []struct {
		name    string
		userID  string
		ownerID string
		want    bool
	}{
		{
			name:    "matching IDs",
			userID:  "123",
			ownerID: "123",
			want:    true,
		},
		{
			name:    "different IDs",
			userID:  "123",
			ownerID: "456",
			want:    false,
		},
		{
			name:    "empty user ID",
			userID:  "",
			ownerID: "123",
			want:    false,
		},
		{
			name:    "empty owner ID",
			userID:  "123",
			ownerID: "",
			want:    false,
		},
		{
			name:    "both empty",
			userID:  "",
			ownerID: "",
			want:    true, // both empty authorizes (technically equal)
		},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			got := AuthorizeResource(tt.userID, tt.ownerID)
			assert.Equal(t, tt.want, got)
		})
	}
}
