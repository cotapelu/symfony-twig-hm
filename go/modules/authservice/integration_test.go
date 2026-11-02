package authservice

import (
	"context"
	"database/sql"
	"encoding/json"
	"fmt"
	"net/http"
	"net/http/httptest"
	"os"
	"strings"
	"testing"

	"github.com/gin-gonic/gin"
	_ "github.com/lib/pq"
	"github.com/stretchr/testify/assert"

	ah "community.local/modules/authservice/handler"
	am "community.local/modules/authservice/service"
)

func getDB(t *testing.T) *sql.DB {
	dsn := os.Getenv("INTEGRATION_TEST_DATABASE_URL")
	if dsn == "" {
		t.Skip("INTEGRATION_TEST_DATABASE_URL not set, skipping integration tests")
	}
	if !strings.Contains(dsn, "sslmode") {
		dsn += "?sslmode=disable"
	}
	db, err := sql.Open("postgres", dsn)
	if err != nil {
		t.Fatalf("failed to open database: %v", err)
	}
	ctx, cancel := context.WithTimeout(context.Background(), 10_000_000_000)
	defer cancel()
	if err := db.PingContext(ctx); err != nil {
		t.Fatalf("failed to ping database: %v", err)
	}
	return db
}

func createAuthSchema(db *sql.DB) error {
	return nil
}

func truncateAuthTables(db *sql.DB) error {
	return nil
}

func TestAuthService_RegisterAndLogin(t *testing.T) {
	authService := am.NewAuthService("test-secret-key")
	handler := ah.NewAuthHandler(authService)

	r := gin.New()
	handler.RegisterRoutes(r)

	// Test registration
	t.Run("Register", func(t *testing.T) {
		body := `{"email":"test@example.com","password":"securepass123","name":"Test User"}`
		req := httptest.NewRequest("POST", "/api/auth/register", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		w := httptest.NewRecorder()
		r.ServeHTTP(w, req)
		assert.Equal(t, http.StatusCreated, w.Code)

		var resp struct {
			User struct {
				ID    string `json:"id"`
				Email string `json:"email"`
				Name  string `json:"name"`
			} `json:"user"`
			Message string `json:"message"`
		}
		if err := json.Unmarshal(w.Body.Bytes(), &resp); err != nil {
			t.Fatalf("failed to parse response: %v", err)
		}
		assert.Equal(t, "test@example.com", resp.User.Email)
		assert.Equal(t, "Test User", resp.User.Name)
		assert.NotEmpty(t, resp.User.ID)
		assert.Equal(t, "registration successful", resp.Message)
	})

	// Test duplicate registration
	t.Run("DuplicateRegister", func(t *testing.T) {
		body := `{"email":"test@example.com","password":"anotherpass","name":"Test User 2"}`
		req := httptest.NewRequest("POST", "/api/auth/register", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		w := httptest.NewRecorder()
		r.ServeHTTP(w, req)
		assert.Equal(t, http.StatusBadRequest, w.Code)
	})

	// Test login
	t.Run("Login", func(t *testing.T) {
		body := `{"email":"test@example.com","password":"securepass123"}`
		req := httptest.NewRequest("POST", "/api/auth/login", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		w := httptest.NewRecorder()
		r.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)

		var resp struct {
			Token string `json:"token"`
			User  struct {
				ID    string `json:"id"`
				Email string `json:"email"`
				Name  string `json:"name"`
			} `json:"user"`
			Message string `json:"message"`
		}
		if err := json.Unmarshal(w.Body.Bytes(), &resp); err != nil {
			t.Fatalf("failed to parse response: %v", err)
		}
		assert.NotEmpty(t, resp.Token)
		assert.Equal(t, "test@example.com", resp.User.Email)
		assert.Equal(t, "Test User", resp.User.Name)
		assert.Equal(t, "login successful", resp.Message)

		// Store token for next test
		token := resp.Token

		// Test token validation
		t.Run("ValidateToken", func(t *testing.T) {
			valBody := fmt.Sprintf(`{"token":"%s"}`, token)
			valReq := httptest.NewRequest("POST", "/api/auth/validate", strings.NewReader(valBody))
			valReq.Header.Set("Content-Type", "application/json")
			valW := httptest.NewRecorder()
			r.ServeHTTP(valW, valReq)
			assert.Equal(t, http.StatusOK, valW.Code)

			var claimsResp struct {
				UserID string   `json:"user_id"`
				Email  string   `json:"email"`
				Name   string   `json:"name"`
				Roles  []string `json:"roles"`
			}
			if err := json.Unmarshal(valW.Body.Bytes(), &claimsResp); err != nil {
				t.Fatalf("failed to parse validation response: %v", err)
			}
			assert.Equal(t, "test@example.com", claimsResp.Email)
			assert.Equal(t, "Test User", claimsResp.Name)
			assert.Contains(t, claimsResp.Roles, "user")
		})

		// Test profile retrieval
		t.Run("GetProfile", func(t *testing.T) {
			profileReq := httptest.NewRequest("GET", "/api/auth/profile", nil)
			profileReq.Header.Set("Authorization", "Bearer "+token)
			profileW := httptest.NewRecorder()
			r.ServeHTTP(profileW, profileReq)
			assert.Equal(t, http.StatusOK, profileW.Code)

			var profileResp struct {
				ID    string   `json:"id"`
				Email string   `json:"email"`
				Name  string   `json:"name"`
				Roles []string `json:"roles"`
			}
			if err := json.Unmarshal(profileW.Body.Bytes(), &profileResp); err != nil {
				t.Fatalf("failed to parse profile response: %v", err)
			}
			assert.Equal(t, "test@example.com", profileResp.Email)
			assert.Equal(t, "Test User", profileResp.Name)
			assert.Contains(t, profileResp.Roles, "user")
		})
	})

	// Test invalid login credentials
	t.Run("InvalidLogin", func(t *testing.T) {
		body := `{"email":"test@example.com","password":"wrongpass"}`
		req := httptest.NewRequest("POST", "/api/auth/login", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		w := httptest.NewRecorder()
		r.ServeHTTP(w, req)
		assert.Equal(t, http.StatusUnauthorized, w.Code)
	})

	// Test missing fields
	t.Run("MissingFields", func(t *testing.T) {
		body := `{"email":"test@example.com"}`
		req := httptest.NewRequest("POST", "/api/auth/register", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		w := httptest.NewRecorder()
		r.ServeHTTP(w, req)
		assert.Equal(t, http.StatusBadRequest, w.Code)
	})
}
