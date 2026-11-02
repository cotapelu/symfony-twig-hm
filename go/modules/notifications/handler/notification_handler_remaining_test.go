package handler

import (
	"net/http"
	"net/http/httptest"
	"testing"

	"github.com/stretchr/testify/assert"
)

func TestNotificationMiddleware_MissingAuth(t *testing.T) {
	next := http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		w.WriteHeader(http.StatusOK)
	})
	mid := NotificationMiddleware(next)

	req := httptest.NewRequest(http.MethodGet, "/", nil)
	w := httptest.NewRecorder()
	mid.ServeHTTP(w, req)

	assert.Equal(t, http.StatusUnauthorized, w.Code)
}

func TestNotificationMiddleware_ValidAuth(t *testing.T) {
	var capturedUserID string
	next := http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		if uid, ok := r.Context().Value("userID").(string); ok {
			capturedUserID = uid
		}
		w.WriteHeader(http.StatusOK)
	})
	mid := NotificationMiddleware(next)

	req := httptest.NewRequest(http.MethodGet, "/", nil)
	req.Header.Set("X-User-ID", "user123")
	w := httptest.NewRecorder()
	mid.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
	assert.Equal(t, "user123", capturedUserID)
}

func TestNotificationMiddleware_AdminRole(t *testing.T) {
	var capturedIsAdmin bool
	next := http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		if isAdmin, ok := r.Context().Value("isAdmin").(bool); ok {
			capturedIsAdmin = isAdmin
			if isAdmin {
				w.WriteHeader(http.StatusOK)
				return
			}
		}
		w.WriteHeader(http.StatusForbidden)
	})
	mid := NotificationMiddleware(next)

	req := httptest.NewRequest(http.MethodGet, "/", nil)
	req.Header.Set("X-User-ID", "admin")
	req.Header.Set("X-User-Roles", "admin,user")
	w := httptest.NewRecorder()
	mid.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
	assert.True(t, capturedIsAdmin)
}

func TestNotificationMiddleware_NonAdminRole(t *testing.T) {
	var capturedIsAdmin bool
	next := http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		if isAdmin, ok := r.Context().Value("isAdmin").(bool); ok {
			capturedIsAdmin = isAdmin
			if isAdmin {
				w.WriteHeader(http.StatusForbidden)
				return
			}
		}
		w.WriteHeader(http.StatusOK)
	})
	mid := NotificationMiddleware(next)

	req := httptest.NewRequest(http.MethodGet, "/", nil)
	req.Header.Set("X-User-ID", "user")
	req.Header.Set("X-User-Roles", "user")
	w := httptest.NewRecorder()
	mid.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
	assert.False(t, capturedIsAdmin)
}

