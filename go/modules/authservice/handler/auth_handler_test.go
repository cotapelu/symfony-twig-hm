package handler

import (
	"bytes"
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"strings"
	"testing"

	"community.local/modules/authservice/service"
	"github.com/gin-gonic/gin"
)

func TestNewAuthHandler(t *testing.T) {
	authService := service.NewAuthService("test-secret")
	handler := NewAuthHandler(authService)

	if handler == nil {
		t.Fatal("NewAuthHandler returned nil")
	}

	if handler.authService != authService {
		t.Error("Handler should have the authService")
	}
}

func TestRegisterSuccess(t *testing.T) {
	authService := service.NewAuthService("test-secret")
	handler := NewAuthHandler(authService)

	body := map[string]string{
		"email":    "test@example.com",
		"password": "password123",
		"name":     "Test User",
	}
	jsonBody, _ := json.Marshal(body)

	req := httptest.NewRequest(http.MethodPost, "/api/auth/register", bytes.NewBuffer(jsonBody))
	rec := httptest.NewRecorder()

	c, _ := gin.CreateTestContext(rec)
	c.Request = req
	handler.Register(c)

	if rec.Code != http.StatusCreated {
		t.Errorf("Expected status %d, got %d", http.StatusCreated, rec.Code)
	}

	var response map[string]interface{}
	json.Unmarshal(rec.Body.Bytes(), &response)

	userData := response["user"].(map[string]interface{})
	if userData["email"] != "test@example.com" {
		t.Errorf("Expected email 'test@example.com', got '%v'", userData["email"])
	}

	if response["message"] != "registration successful" {
		t.Errorf("Expected message 'registration successful', got '%v'", response["message"])
	}
}

func TestRegisterInvalidJSON(t *testing.T) {
	authService := service.NewAuthService("test-secret")
	handler := NewAuthHandler(authService)

	req := httptest.NewRequest(http.MethodPost, "/api/auth/register", bytes.NewBuffer([]byte("invalid json")))
	rec := httptest.NewRecorder()

	c, _ := gin.CreateTestContext(rec)
	c.Request = req
	handler.Register(c)

	if rec.Code != http.StatusBadRequest {
		t.Errorf("Expected status %d, got %d", http.StatusBadRequest, rec.Code)
	}

	var response map[string]string
	json.Unmarshal(rec.Body.Bytes(), &response)

	if response["error"] != "invalid request body" {
		t.Errorf("Expected error 'invalid request body', got '%s'", response["error"])
	}
}

func TestRegisterMissingFields(t *testing.T) {
	authService := service.NewAuthService("test-secret")
	handler := NewAuthHandler(authService)

	tests := []struct {
		name   string
		body   map[string]string
		expect string
	}{
		{
			name:   "Missing email",
			body:   map[string]string{"password": "pass", "name": "Test"},
			expect: "invalid request body",
		},
		{
			name:   "Missing password",
			body:   map[string]string{"email": "test@example.com", "name": "Test"},
			expect: "invalid request body",
		},
		{
			name:   "Missing name",
			body:   map[string]string{"email": "test@example.com", "password": "pass"},
			expect: "invalid request body",
		},
		{
			name:   "All empty",
			body:   map[string]string{},
			expect: "invalid request body",
		},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			jsonBody, _ := json.Marshal(tt.body)
			req := httptest.NewRequest(http.MethodPost, "/api/auth/register", bytes.NewBuffer(jsonBody))
			rec := httptest.NewRecorder()

			c, _ := gin.CreateTestContext(rec)
	c.Request = req
	handler.Register(c)

			if rec.Code != http.StatusBadRequest {
				t.Errorf("Expected status %d, got %d", http.StatusBadRequest, rec.Code)
			}

			var response map[string]string
			json.Unmarshal(rec.Body.Bytes(), &response)

			if response["error"] != tt.expect {
				t.Errorf("Expected error '%s', got '%s'", tt.expect, response["error"])
			}
		})
	}
}

func TestRegisterDuplicateEmail(t *testing.T) {
	authService := service.NewAuthService("test-secret")
	handler := NewAuthHandler(authService)

	// Register first user
	body := map[string]string{
		"email":    "test@example.com",
		"password": "password123",
		"name":     "Test User",
	}
	jsonBody, _ := json.Marshal(body)

	req := httptest.NewRequest(http.MethodPost, "/api/auth/register", bytes.NewBuffer(jsonBody))
	rec := httptest.NewRecorder()
	c, _ := gin.CreateTestContext(rec)
	c.Request = req
	handler.Register(c)

	// Try to register again with same email
	req2 := httptest.NewRequest(http.MethodPost, "/api/auth/register", bytes.NewBuffer(jsonBody))
	rec2 := httptest.NewRecorder()
	c2, _ := gin.CreateTestContext(rec2)
	c2.Request = req2
	handler.Register(c2)

	if rec2.Code != http.StatusBadRequest {
		t.Errorf("Expected status %d, got %d", http.StatusBadRequest, rec2.Code)
	}

	var response map[string]string
	json.Unmarshal(rec2.Body.Bytes(), &response)

	if response["error"] != "user already exists" {
		t.Errorf("Expected error 'user already exists', got '%s'", response["error"])
	}
}

func TestRegisterWrongMethod(t *testing.T) {
	authService := service.NewAuthService("test-secret")
	handler := NewAuthHandler(authService)

	body := map[string]string{
		"email":    "test@example.com",
		"password": "password123",
		"name":     "Test User",
	}
	jsonBody, _ := json.Marshal(body)

	req := httptest.NewRequest(http.MethodGet, "/api/auth/register", bytes.NewBuffer(jsonBody))
	rec := httptest.NewRecorder()

	c, _ := gin.CreateTestContext(rec)
	c.Request = req
	handler.Register(c)

	if rec.Code != http.StatusMethodNotAllowed {
		t.Errorf("Expected status %d, got %d", http.StatusMethodNotAllowed, rec.Code)
	}
}

func TestLoginSuccess(t *testing.T) {
	authService := service.NewAuthService("test-secret")
	handler := NewAuthHandler(authService)

	// Register a user first
	_, _ = authService.Register("test@example.com", "password123", "Test User")

	body := map[string]string{
		"email":    "test@example.com",
		"password": "password123",
	}
	jsonBody, _ := json.Marshal(body)

	req := httptest.NewRequest(http.MethodPost, "/api/auth/login", bytes.NewBuffer(jsonBody))
	rec := httptest.NewRecorder()

	c, _ := gin.CreateTestContext(rec)
	c.Request = req
	handler.Login(c)

	if rec.Code != http.StatusOK {
		t.Errorf("Expected status %d, got %d", http.StatusOK, rec.Code)
	}

	var response map[string]interface{}
	json.Unmarshal(rec.Body.Bytes(), &response)

	if response["token"] == "" {
		t.Error("Token should not be empty")
	}

	userData := response["user"].(map[string]interface{})
	if userData["email"] != "test@example.com" {
		t.Errorf("Expected email 'test@example.com', got '%v'", userData["email"])
	}
}

func TestLoginInvalidCredentials(t *testing.T) {
	authService := service.NewAuthService("test-secret")
	handler := NewAuthHandler(authService)

	_, _ = authService.Register("test@example.com", "password123", "Test User")

	body := map[string]string{
		"email":    "test@example.com",
		"password": "wrongpassword",
	}
	jsonBody, _ := json.Marshal(body)

	req := httptest.NewRequest(http.MethodPost, "/api/auth/login", bytes.NewBuffer(jsonBody))
	rec := httptest.NewRecorder()

	c, _ := gin.CreateTestContext(rec)
	c.Request = req
	handler.Login(c)

	if rec.Code != http.StatusUnauthorized {
		t.Errorf("Expected status %d, got %d", http.StatusUnauthorized, rec.Code)
	}

	var response map[string]string
	json.Unmarshal(rec.Body.Bytes(), &response)

	if response["error"] != "invalid credentials" {
		t.Errorf("Expected error 'invalid credentials', got '%s'", response["error"])
	}
}

func TestLoginUserNotFound(t *testing.T) {
	authService := service.NewAuthService("test-secret")
	handler := NewAuthHandler(authService)

	body := map[string]string{
		"email":    "nonexistent@example.com",
		"password": "password123",
	}
	jsonBody, _ := json.Marshal(body)

	req := httptest.NewRequest(http.MethodPost, "/api/auth/login", bytes.NewBuffer(jsonBody))
	rec := httptest.NewRecorder()

	c, _ := gin.CreateTestContext(rec)
	c.Request = req
	handler.Login(c)

	if rec.Code != http.StatusUnauthorized {
		t.Errorf("Expected status %d, got %d", http.StatusUnauthorized, rec.Code)
	}

	var response map[string]string
	json.Unmarshal(rec.Body.Bytes(), &response)

	if response["error"] != "user not found" {
		t.Errorf("Expected error 'user not found', got '%s'", response["error"])
	}
}

func TestValidateTokenSuccess(t *testing.T) {
	authService := service.NewAuthService("test-secret")
	handler := NewAuthHandler(authService)

	// Generate a token
	user, _ := authService.Register("test@example.com", "password123", "Test User")
	token, _ := authService.GenerateToken(user)

	body := map[string]string{"token": token}
	jsonBody, _ := json.Marshal(body)

	req := httptest.NewRequest(http.MethodPost, "/api/auth/validate", bytes.NewBuffer(jsonBody))
	rec := httptest.NewRecorder()
	c, _ := gin.CreateTestContext(rec)
	c.Request = req
	handler.ValidateToken(c)

	if rec.Code != http.StatusOK {
		t.Errorf("Expected status %d, got %d", http.StatusOK, rec.Code)
	}

	var claims map[string]interface{}
	json.Unmarshal(rec.Body.Bytes(), &claims)

	if claims["user_id"] != user.ID {
		t.Errorf("Expected user_id '%s', got '%v'", user.ID, claims["user_id"])
	}
}

func TestValidateTokenInvalid(t *testing.T) {
	authService := service.NewAuthService("test-secret")
	handler := NewAuthHandler(authService)

	body := map[string]string{"token": "invalid.token.here"}
	jsonBody, _ := json.Marshal(body)

	req := httptest.NewRequest(http.MethodPost, "/api/auth/validate", bytes.NewBuffer(jsonBody))
	rec := httptest.NewRecorder()
	c, _ := gin.CreateTestContext(rec)
	c.Request = req
	handler.ValidateToken(c)

	if rec.Code != http.StatusUnauthorized {
		t.Errorf("Expected status %d, got %d", http.StatusUnauthorized, rec.Code)
	}

	var response map[string]string
	json.Unmarshal(rec.Body.Bytes(), &response)

	if response["error"] == "" {
		t.Error("Expected error message")
	}
}

func TestValidateTokenMissing(t *testing.T) {
	authService := service.NewAuthService("test-secret")
	handler := NewAuthHandler(authService)

	body := map[string]string{}
	jsonBody, _ := json.Marshal(body)

	req := httptest.NewRequest(http.MethodPost, "/api/auth/validate", bytes.NewBuffer(jsonBody))
	rec := httptest.NewRecorder()
	c, _ := gin.CreateTestContext(rec)
	c.Request = req
	handler.ValidateToken(c)

	if rec.Code != http.StatusBadRequest {
		t.Errorf("Expected status %d, got %d", http.StatusBadRequest, rec.Code)
	}

	var response map[string]string
	json.Unmarshal(rec.Body.Bytes(), &response)

	if response["error"] != "invalid request body" {
		t.Errorf("Expected error 'token is required', got '%s'", response["error"])
	}
}

func TestGetProfileSuccess(t *testing.T) {
	authService := service.NewAuthService("test-secret")
	handler := NewAuthHandler(authService)

	// Register and create token
	user, _ := authService.Register("test@example.com", "password123", "Test User")
	token, _ := authService.GenerateToken(user)

	req := httptest.NewRequest(http.MethodGet, "/api/auth/profile", nil)
	req.Header.Set("Authorization", "Bearer "+token)
	rec := httptest.NewRecorder()

	c, _ := gin.CreateTestContext(rec)
	c.Request = req
	handler.GetProfile(c)

	if rec.Code != http.StatusOK {
		t.Errorf("Expected status %d, got %d", http.StatusOK, rec.Code)
	}

	var response map[string]interface{}
	json.Unmarshal(rec.Body.Bytes(), &response)

	if response["email"] != "test@example.com" {
		t.Errorf("Expected email 'test@example.com', got '%v'", response["email"])
	}

	roles := response["roles"].([]interface{})
	if len(roles) != 1 || roles[0] != "user" {
		t.Errorf("Expected roles ['user'], got %v", roles)
	}
}

func TestGetProfileMissingAuthHeader(t *testing.T) {
	authService := service.NewAuthService("test-secret")
	handler := NewAuthHandler(authService)

	req := httptest.NewRequest(http.MethodGet, "/api/auth/profile", nil)
	rec := httptest.NewRecorder()

	c, _ := gin.CreateTestContext(rec)
	c.Request = req
	handler.GetProfile(c)

	if rec.Code != http.StatusUnauthorized {
		t.Errorf("Expected status %d, got %d", http.StatusUnauthorized, rec.Code)
	}

	var response map[string]string
	json.Unmarshal(rec.Body.Bytes(), &response)

	if response["error"] != "unauthorized" {
		t.Errorf("Expected error 'unauthorized', got '%s'", response["error"])
	}
}

func TestGetProfileInvalidAuthHeader(t *testing.T) {
	authService := service.NewAuthService("test-secret")
	handler := NewAuthHandler(authService)

	req := httptest.NewRequest(http.MethodGet, "/api/auth/profile", nil)
	req.Header.Set("Authorization", "Invalid token")
	rec := httptest.NewRecorder()

	c, _ := gin.CreateTestContext(rec)
	c.Request = req
	handler.GetProfile(c)

	if rec.Code != http.StatusUnauthorized {
		t.Errorf("Expected status %d, got %d", http.StatusUnauthorized, rec.Code)
	}
}

func TestGetProfileInvalidToken(t *testing.T) {
	authService := service.NewAuthService("test-secret")
	handler := NewAuthHandler(authService)

	req := httptest.NewRequest(http.MethodGet, "/api/auth/profile", nil)
	req.Header.Set("Authorization", "Bearer invalid.token.here")
	rec := httptest.NewRecorder()

	c, _ := gin.CreateTestContext(rec)
	c.Request = req
	handler.GetProfile(c)

	if rec.Code != http.StatusUnauthorized {
		t.Errorf("Expected status %d, got %d", http.StatusUnauthorized, rec.Code)
	}
}

func TestUpdatePasswordSuccess(t *testing.T) {
	authService := service.NewAuthService("test-secret")
	handler := NewAuthHandler(authService)

	// Register and create token
	user, _ := authService.Register("test@example.com", "password123", "Test User")
	token, _ := authService.GenerateToken(user)

	body := map[string]string{
		"current_password": "password123",
		"new_password":     "newpassword",
	}
	jsonBody, _ := json.Marshal(body)

	req := httptest.NewRequest(http.MethodPut, "/api/auth/password", bytes.NewBuffer(jsonBody))
	req.Header.Set("Authorization", "Bearer "+token)
	rec := httptest.NewRecorder()

	c, _ := gin.CreateTestContext(rec)
	c.Request = req
	handler.UpdatePassword(c)

	if rec.Code != http.StatusOK {
		t.Errorf("Expected status %d, got %d", http.StatusOK, rec.Code)
	}

	var response map[string]string
	json.Unmarshal(rec.Body.Bytes(), &response)

	if response["message"] != "password updated successfully" {
		t.Errorf("Expected message 'password updated successfully', got '%s'", response["message"])
	}
}

func TestUpdatePasswordInvalidCurrent(t *testing.T) {
	authService := service.NewAuthService("test-secret")
	handler := NewAuthHandler(authService)

	user, _ := authService.Register("test@example.com", "password123", "Test User")
	token, _ := authService.GenerateToken(user)

	body := map[string]string{
		"current_password": "wrongpassword",
		"new_password":     "newpassword",
	}
	jsonBody, _ := json.Marshal(body)

	req := httptest.NewRequest(http.MethodPut, "/api/auth/password", bytes.NewBuffer(jsonBody))
	req.Header.Set("Authorization", "Bearer "+token)
	rec := httptest.NewRecorder()

	c, _ := gin.CreateTestContext(rec)
	c.Request = req
	handler.UpdatePassword(c)

	if rec.Code != http.StatusBadRequest {
		t.Errorf("Expected status %d, got %d", http.StatusBadRequest, rec.Code)
	}

	var response map[string]string
	json.Unmarshal(rec.Body.Bytes(), &response)

	if response["error"] != "current password is incorrect" {
		t.Errorf("Expected error 'current password is incorrect', got '%s'", response["error"])
	}
}

func TestLogoutSuccess(t *testing.T) {
	authService := service.NewAuthService("test-secret")
	handler := NewAuthHandler(authService)

	req := httptest.NewRequest(http.MethodPost, "/api/auth/logout", nil)
	rec := httptest.NewRecorder()

	c, _ := gin.CreateTestContext(rec)
	c.Request = req
	handler.Logout(c)

	if rec.Code != http.StatusOK {
		t.Errorf("Expected status %d, got %d", http.StatusOK, rec.Code)
	}

	var response map[string]string
	json.Unmarshal(rec.Body.Bytes(), &response)

	if response["message"] != "logged out successfully" {
		t.Errorf("Expected message 'logged out successfully', got '%s'", response["message"])
	}
}

/*
func TestSetupRoutes(t *testing.T) {
	authService := service.NewAuthService("test-secret")
	handler := NewAuthHandler(authService)

	mux := http.NewServeMux()
	handler.SetupRoutes(mux)

	if mux == nil {
		t.Fatal("Handler should set up routes")
	}
}
*/

func TestContentTypeHeader(t *testing.T) {
	authService := service.NewAuthService("test-secret")
	handler := NewAuthHandler(authService)

	body := map[string]string{
		"email":    "test@example.com",
		"password": "password123",
		"name":     "Test User",
	}
	jsonBody, _ := json.Marshal(body)

	req := httptest.NewRequest(http.MethodPost, "/api/auth/register", bytes.NewBuffer(jsonBody))
	rec := httptest.NewRecorder()

	c, _ := gin.CreateTestContext(rec)
	c.Request = req
	handler.Register(c)

	contentType := rec.Header().Get("Content-Type")
	if !strings.Contains(contentType, "application/json") {
		t.Errorf("Expected Content-Type 'application/json', got '%s'", contentType)
	}
}
