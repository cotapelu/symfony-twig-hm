package service

import (
	"strings"
	"testing"
	"time"
)

func TestNewAuthService(t *testing.T) {
	auth := NewAuthService("test-secret")

	if auth == nil {
		t.Fatal("NewAuthService returned nil")
	}

	if auth.jwtSecret != "test-secret" {
		t.Errorf("Expected jwtSecret 'test-secret', got '%s'", auth.jwtSecret)
	}

	if auth.tokenExpiry != 24*time.Hour {
		t.Errorf("Expected tokenExpiry 24 hours, got %v", auth.tokenExpiry)
	}

	if auth.users == nil {
		t.Error("Expected users map to be initialized")
	}
}

func TestRegister(t *testing.T) {
	auth := NewAuthService("test-secret")

	user, err := auth.Register("test@example.com", "password123", "Test User")

	if err != nil {
		t.Fatalf("Register failed: %v", err)
	}

	if user == nil {
		t.Fatal("User is nil")
	}

	if user.Email != "test@example.com" {
		t.Errorf("Expected email 'test@example.com', got '%s'", user.Email)
	}

	if user.Name != "Test User" {
		t.Errorf("Expected name 'Test User', got '%s'", user.Name)
	}

	if len(user.Roles) != 1 || user.Roles[0] != "user" {
		t.Errorf("Expected roles ['user'], got %v", user.Roles)
	}

	if strings.TrimSpace(user.PasswordHash) == "" {
		t.Error("Password hash should not be empty")
	}
}

func TestRegisterDuplicateEmail(t *testing.T) {
	auth := NewAuthService("test-secret")

	// Register first user
	_, err := auth.Register("test@example.com", "password123", "Test User")
	if err != nil {
		t.Fatalf("First register failed: %v", err)
	}

	// Try to register with same email
	_, err = auth.Register("test@example.com", "differentpass", "Different User")
	if err == nil {
		t.Error("Expected error for duplicate email, got nil")
	}

	if err.Error() != "user already exists" {
		t.Errorf("Expected error 'user already exists', got '%s'", err.Error())
	}
}

func TestLogin(t *testing.T) {
	auth := NewAuthService("test-secret")

	// Register a user
	_, err := auth.Register("test@example.com", "password123", "Test User")
	if err != nil {
		t.Fatalf("Register failed: %v", err)
	}

	// Login with correct credentials
	token, user, err := auth.Login("test@example.com", "password123")
	if err != nil {
		t.Fatalf("Login failed: %v", err)
	}

	if token == "" {
		t.Error("Token should not be empty")
	}

	if user == nil {
		t.Fatal("User should not be nil")
	}

	if user.Email != "test@example.com" {
		t.Errorf("Expected email 'test@example.com', got '%s'", user.Email)
	}
}

func TestLoginUserNotFound(t *testing.T) {
	auth := NewAuthService("test-secret")

	token, user, err := auth.Login("nonexistent@example.com", "password")

	if err == nil {
		t.Error("Expected error, got nil")
	}

	if token != "" {
		t.Error("Token should be empty for failed login")
	}

	if user != nil {
		t.Error("User should be nil for failed login")
	}

	if err.Error() != "user not found" {
		t.Errorf("Expected error 'user not found', got '%s'", err.Error())
	}
}

func TestLoginInvalidPassword(t *testing.T) {
	auth := NewAuthService("test-secret")

	_, err := auth.Register("test@example.com", "password123", "Test User")
	if err != nil {
		t.Fatalf("Register failed: %v", err)
	}

	token, user, err := auth.Login("test@example.com", "wrongpassword")

	if err == nil {
		t.Error("Expected error for invalid password, got nil")
	}

	if token != "" {
		t.Error("Token should be empty for failed login")
	}

	if user != nil {
		t.Error("User should be nil for failed login")
	}

	if err.Error() != "invalid credentials" {
		t.Errorf("Expected error 'invalid credentials', got '%s'", err.Error())
	}
}

func TestGenerateToken(t *testing.T) {
	auth := NewAuthService("test-secret")

	user := &User{
		ID:    "user123",
		Email: "test@example.com",
		Name:  "Test User",
		Roles: []string{"user"},
	}

	token, err := auth.GenerateToken(user)
	if err != nil {
		t.Fatalf("GenerateToken failed: %v", err)
	}

	if token == "" {
		t.Error("Token should not be empty")
	}

	// Verify token contains 3 parts separated by dots
	parts := strings.Split(token, ".")
	if len(parts) != 3 {
		t.Errorf("Expected 3 parts in JWT token, got %d", len(parts))
	}
}

func TestValidateToken(t *testing.T) {
	auth := NewAuthService("test-secret")

	// Generate a valid token
	user := &User{
		ID:    "user123",
		Email: "test@example.com",
		Name:  "Test User",
		Roles: []string{"user"},
	}

	token, err := auth.GenerateToken(user)
	if err != nil {
		t.Fatalf("GenerateToken failed: %v", err)
	}

	// Validate the token
	claims, err := auth.ValidateToken(token)
	if err != nil {
		t.Fatalf("ValidateToken failed: %v", err)
	}

	if claims == nil {
		t.Fatal("Claims should not be nil")
	}

	if claims.UserID != "user123" {
		t.Errorf("Expected UserID 'user123', got '%s'", claims.UserID)
	}

	if claims.Email != "test@example.com" {
		t.Errorf("Expected Email 'test@example.com', got '%s'", claims.Email)
	}

	if claims.Name != "Test User" {
		t.Errorf("Expected Name 'Test User', got '%s'", claims.Name)
	}

	if len(claims.Roles) != 1 || claims.Roles[0] != "user" {
		t.Errorf("Expected Roles ['user'], got %v", claims.Roles)
	}
}

func TestValidateTokenInvalid(t *testing.T) {
	auth := NewAuthService("test-secret")

	// Test with invalid token
	claims, err := auth.ValidateToken("invalid.token.here")

	if err == nil {
		t.Error("Expected error for invalid token, got nil")
	}

	if claims != nil {
		t.Error("Claims should be nil for invalid token")
	}
}

func TestValidateTokenWrongSecret(t *testing.T) {
	auth1 := NewAuthService("secret1")
	auth2 := NewAuthService("secret2")

	user := &User{
		ID:    "user123",
		Email: "test@example.com",
		Name:  "Test User",
		Roles: []string{"user"},
	}

	token, err := auth1.GenerateToken(user)
	if err != nil {
		t.Fatalf("GenerateToken failed: %v", err)
	}

	// Try to validate token with different secret
	_, err = auth2.ValidateToken(token)

	if err == nil {
		t.Error("Expected error when validating token with wrong secret")
	}
}

func TestGetUserByID(t *testing.T) {
	auth := NewAuthService("test-secret")

	// Register a user
	registeredUser, err := auth.Register("test@example.com", "password123", "Test User")
	if err != nil {
		t.Fatalf("Register failed: %v", err)
	}

	// Get user by ID
	user, err := auth.GetUserByID(registeredUser.ID)
	if err != nil {
		t.Fatalf("GetUserByID failed: %v", err)
	}

	if user.ID != registeredUser.ID {
		t.Errorf("Expected ID '%s', got '%s'", registeredUser.ID, user.ID)
	}

	if user.Email != "test@example.com" {
		t.Errorf("Expected email 'test@example.com', got '%s'", user.Email)
	}
}

func TestGetUserByIDNotFound(t *testing.T) {
	auth := NewAuthService("test-secret")

	user, err := auth.GetUserByID("nonexistent-user")

	if err == nil {
		t.Error("Expected error for nonexistent user, got nil")
	}

	if user != nil {
		t.Error("User should be nil for nonexistent user")
	}

	if err.Error() != "user not found" {
		t.Errorf("Expected error 'user not found', got '%s'", err.Error())
	}
}

func TestGetByEmail(t *testing.T) {
	auth := NewAuthService("test-secret")

	// Register a user
	_, err := auth.Register("test@example.com", "password123", "Test User")
	if err != nil {
		t.Fatalf("Register failed: %v", err)
	}

	// Get user by email
	user, err := auth.GetByEmail("test@example.com")
	if err != nil {
		t.Fatalf("GetByEmail failed: %v", err)
	}

	if user.Email != "test@example.com" {
		t.Errorf("Expected email 'test@example.com', got '%s'", user.Email)
	}

	if user.Name != "Test User" {
		t.Errorf("Expected name 'Test User', got '%s'", user.Name)
	}
}

func TestGetByEmailNotFound(t *testing.T) {
	auth := NewAuthService("test-secret")

	user, err := auth.GetByEmail("nonexistent@example.com")

	if err == nil {
		t.Error("Expected error for nonexistent email, got nil")
	}

	if user != nil {
		t.Error("User should be nil for nonexistent email")
	}

	if err.Error() != "user not found" {
		t.Errorf("Expected error 'user not found', got '%s'", err.Error())
	}
}

func TestUpdatePassword(t *testing.T) {
	auth := NewAuthService("test-secret")

	// Register a user
	user, err := auth.Register("test@example.com", "password123", "Test User")
	if err != nil {
		t.Fatalf("Register failed: %v", err)
	}

	// Update password
	err = auth.UpdatePassword(user.ID, "password123", "newpassword")
	if err != nil {
		t.Fatalf("UpdatePassword failed: %v", err)
	}

	// Try to login with old password
	_, _, err = auth.Login("test@example.com", "password123")
	if err == nil {
		t.Error("Expected error with old password, got nil")
	}

	// Login with new password
	token, _, err := auth.Login("test@example.com", "newpassword")
	if err != nil {
		t.Errorf("Login with new password failed: %v", err)
	}

	if token == "" {
		t.Error("Token should not be empty")
	}
}

func TestUpdatePasswordInvalidCurrent(t *testing.T) {
	auth := NewAuthService("test-secret")

	user, err := auth.Register("test@example.com", "password123", "Test User")
	if err != nil {
		t.Fatalf("Register failed: %v", err)
	}

	err = auth.UpdatePassword(user.ID, "wrongcurrent", "newpassword")

	if err == nil {
		t.Error("Expected error for invalid current password, got nil")
	}

	if err.Error() != "current password is incorrect" {
		t.Errorf("Expected error 'current password is incorrect', got '%s'", err.Error())
	}
}

func TestUpdatePasswordUserNotFound(t *testing.T) {
	auth := NewAuthService("test-secret")

	err := auth.UpdatePassword("nonexistent-user", "password", "newpassword")

	if err == nil {
		t.Error("Expected error for nonexistent user, got nil")
	}

	if err.Error() != "user not found" {
		t.Errorf("Expected error 'user not found', got '%s'", err.Error())
	}
}

func TestChangePassword(t *testing.T) {
	auth := NewAuthService("test-secret")

	// Register a user
	user, err := auth.Register("test@example.com", "password123", "Test User")
	if err != nil {
		t.Fatalf("Register failed: %v", err)
	}

	// Change password (admin override - no current password required)
	err = auth.ChangePassword(user.ID, "newpassword")
	if err != nil {
		t.Fatalf("ChangePassword failed: %v", err)
	}

	// Login with new password
	token, _, err := auth.Login("test@example.com", "newpassword")
	if err != nil {
		t.Errorf("Login with new password failed: %v", err)
	}

	if token == "" {
		t.Error("Token should not be empty")
	}
}

func TestChangePasswordUserNotFound(t *testing.T) {
	auth := NewAuthService("test-secret")

	err := auth.ChangePassword("nonexistent-user", "newpassword")

	if err == nil {
		t.Error("Expected error for nonexistent user, got nil")
	}

	if err.Error() != "user not found" {
		t.Errorf("Expected error 'user not found', got '%s'", err.Error())
	}
}

func TestTokenExpiration(t *testing.T) {
	auth := NewAuthService("test-secret")
	auth.tokenExpiry = 1 * time.Millisecond // Set very short expiry

	user := &User{
		ID:    "user123",
		Email: "test@example.com",
		Name:  "Test User",
		Roles: []string{"user"},
	}

	token, err := auth.GenerateToken(user)
	if err != nil {
		t.Fatalf("GenerateToken failed: %v", err)
	}

	// Wait for token to expire
	time.Sleep(10 * time.Millisecond)

	// Try to validate expired token
	_, err = auth.ValidateToken(token)

	if err == nil {
		t.Error("Expected error for expired token, got nil")
	}
}

func TestTokenClaimsIssuer(t *testing.T) {
	auth := NewAuthService("test-secret")

	user := &User{
		ID:    "user123",
		Email: "test@example.com",
		Name:  "Test User",
		Roles: []string{"user"},
	}

	token, err := auth.GenerateToken(user)
	if err != nil {
		t.Fatalf("GenerateToken failed: %v", err)
	}

	claims, err := auth.ValidateToken(token)
	if err != nil {
		t.Fatalf("ValidateToken failed: %v", err)
	}

	if claims.Issuer != "symfony-twig-hm" {
		t.Errorf("Expected issuer 'symfony-twig-hm', got '%s'", claims.Issuer)
	}
}
