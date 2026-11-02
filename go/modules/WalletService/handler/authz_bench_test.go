package handler

import (
    "net/http/httptest"
    "testing"

    "github.com/gin-gonic/gin"
)

var sink interface{}

func setupAuthContext(userID string, admin bool) *gin.Context {
    req := httptest.NewRequest("GET", "/", nil)
    c := &gin.Context{
        Request: req,
        Keys: make(map[string]interface{}),
    }
    c.Set("user_id", userID)
    if admin {
        c.Set("user_role", "admin")
        c.Set("user_roles", []string{"admin"})
    }
    return c
}

// BenchmarkRequireAuth benchmarks requireAuth (success case).
func BenchmarkRequireAuth(b *testing.B) {
    c := setupAuthContext("bench-user", false)
    b.ResetTimer()
    for i := 0; i < b.N; i++ {
        uid, ok := requireAuth(c)
        if !ok {
            b.Fatal("expected ok")
        }
        sink = uid
    }
}

// BenchmarkRequireAdmin benchmarks requireAdmin (true case).
func BenchmarkRequireAdmin(b *testing.B) {
    c := setupAuthContext("bench-user", true)
    b.ResetTimer()
    for i := 0; i < b.N; i++ {
        admin := requireAdmin(c)
        sink = admin
    }
}

// BenchmarkRequireAdminNonAdmin benchmarks requireAdmin (false case).
func BenchmarkRequireAdminNonAdmin(b *testing.B) {
    c := setupAuthContext("bench-user", false)
    b.ResetTimer()
    for i := 0; i < b.N; i++ {
        admin := requireAdmin(c)
        sink = admin
    }
}

// BenchmarkIsAdmin benchmarks isAdmin with admin role.
func BenchmarkIsAdmin(b *testing.B) {
    c := setupAuthContext("bench-user", true)
    b.ResetTimer()
    for i := 0; i < b.N; i++ {
        admin := isAdmin(c)
        sink = admin
    }
}

// BenchmarkIsAdminNonAdmin benchmarks isAdmin with non-admin.
func BenchmarkIsAdminNonAdmin(b *testing.B) {
    c := setupAuthContext("bench-user", false)
    b.ResetTimer()
    for i := 0; i < b.N; i++ {
        admin := isAdmin(c)
        sink = admin
    }
}

// BenchmarkOwnershipCheck benchmarks the pattern used in many endpoints:
// if authUserID != requestedUserID && !requireAdmin(c) { ... }
func BenchmarkOwnershipCheck(b *testing.B) {
    authUserID := "auth-user"
    requestedUserID := "other-user"
    c := setupAuthContext(authUserID, false)
    b.ResetTimer()
    for i := 0; i < b.N; i++ {
        denied := (authUserID != requestedUserID && !requireAdmin(c))
        sink = denied
    }
}

// BenchmarkOwnershipCheckSelf benchmarks the same-user case.
func BenchmarkOwnershipCheckSelf(b *testing.B) {
    authUserID := "same-user"
    requestedUserID := "same-user"
    c := setupAuthContext(authUserID, false)
    b.ResetTimer()
    for i := 0; i < b.N; i++ {
        denied := (authUserID != requestedUserID && !requireAdmin(c))
        sink = denied
    }
}
