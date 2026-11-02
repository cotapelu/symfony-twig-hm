package handler

import (
    "net/http/httptest"
    "testing"

    "github.com/gin-gonic/gin"
)

// sink prevents compiler from optimizing away benchmark results.
var sink interface{}

func setupAuthContext(userID string, admin bool) *gin.Context {
    req := httptest.NewRequest("GET", "/", nil)
    c := &gin.Context{
        Request: req,
        Keys: make(map[string]interface{}),
    }
    c.Set("user_id", userID)
    if admin {
        c.Set("user_roles", []string{"admin"})
    }
    return c
}

// BenchmarkRequireAuth benchmarks the requireAuth function (success case).
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

// BenchmarkIsAdmin benchmarks isAdmin with admin role (true case).
func BenchmarkIsAdmin(b *testing.B) {
    c := setupAuthContext("bench-user", true)
    b.ResetTimer()
    for i := 0; i < b.N; i++ {
        admin := isAdmin(c)
        sink = admin
    }
}

// BenchmarkIsAdminNonAdmin benchmarks isAdmin with non-admin (false case).
func BenchmarkIsAdminNonAdmin(b *testing.B) {
    c := setupAuthContext("bench-user", false)
    b.ResetTimer()
    for i := 0; i < b.N; i++ {
        admin := isAdmin(c)
        sink = admin
    }
}

// BenchmarkOwnershipCheck benchmarks the typical ownership denial check:
// authUserID != requestedUserID && !isAdmin(c)
func BenchmarkOwnershipCheck(b *testing.B) {
    authUserID := "auth-user"
    requestedUserID := "other-user"
    c := setupAuthContext(authUserID, false)
    b.ResetTimer()
    for i := 0; i < b.N; i++ {
        denied := (authUserID != requestedUserID && !isAdmin(c))
        sink = denied
    }
}

// BenchmarkOwnershipCheckSelf benchmarks the ownership check when same user (allowed).
func BenchmarkOwnershipCheckSelf(b *testing.B) {
    authUserID := "same-user"
    requestedUserID := "same-user"
    c := setupAuthContext(authUserID, false)
    b.ResetTimer()
    for i := 0; i < b.N; i++ {
        denied := (authUserID != requestedUserID && !isAdmin(c))
        sink = denied
    }
}
