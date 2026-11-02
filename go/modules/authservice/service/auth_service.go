package service

import (
	"errors"
	"regexp"
	"time"

	"github.com/golang-jwt/jwt/v5"
	"golang.org/x/crypto/bcrypt"
)

// Claims represents JWT claims
type Claims struct {
	UserID string   `json:"user_id"`
	Email  string   `json:"email"`
	Name   string   `json:"name"`
	Roles  []string `json:"roles"`
	jwt.RegisteredClaims
}

// AuthService handles authentication operations
type AuthService struct {
	jwtSecret   string
	tokenExpiry time.Duration
	users       map[string]*User // In-memory storage (use database in production)
}

// User represents a user in the system
type User struct {
	ID           string
	Email        string
	PasswordHash string
	Name         string
	Roles        []string
	CreatedAt    time.Time
}

// NewAuthService creates a new authentication service
func NewAuthService(jwtSecret string) *AuthService {
	return &AuthService{
		jwtSecret:   jwtSecret,
		tokenExpiry: 24 * time.Hour, // 24 hours
		users:       make(map[string]*User),
	}
}

// Register registers a new user
func (s *AuthService) Register(email, password, name string) (*User, error) {
	// Input validation
	if email == "" || !isValidEmail(email) {
		return nil, errors.New("invalid email format")
	}
	if len(password) < 8 {
		return nil, errors.New("password must be at least 8 characters")
	}
	if name == "" || len(name) > 100 {
		return nil, errors.New("name is required and must be less than 100 characters")
	}

	if _, exists := s.users[email]; exists {
		return nil, errors.New("user already exists")
	}

	passwordHash, err := bcrypt.GenerateFromPassword([]byte(password), bcrypt.DefaultCost)
	if err != nil {
		return nil, err
	}

	user := &User{
		ID:           generateID(),
		Email:        email,
		PasswordHash: string(passwordHash),
		Name:         name,
		Roles:        []string{"user"},
		CreatedAt:    time.Now(),
	}

	s.users[email] = user
	return user, nil
}

// Login authenticates a user and returns a JWT token
func (s *AuthService) Login(email, password string) (string, *User, error) {
	user, exists := s.users[email]
	if !exists {
		return "", nil, errors.New("user not found")
	}

	if err := bcrypt.CompareHashAndPassword([]byte(user.PasswordHash), []byte(password)); err != nil {
		return "", nil, errors.New("invalid credentials")
	}

	token, err := s.GenerateToken(user)
	if err != nil {
		return "", nil, err
	}

	return token, user, nil
}

// GenerateToken generates a JWT token for a user
func (s *AuthService) GenerateToken(user *User) (string, error) {
	claims := &Claims{
		UserID: user.ID,
		Email:  user.Email,
		Name:   user.Name,
		Roles:  user.Roles,
		RegisteredClaims: jwt.RegisteredClaims{
			ExpiresAt: jwt.NewNumericDate(time.Now().Add(s.tokenExpiry)),
			IssuedAt:  jwt.NewNumericDate(time.Now()),
			NotBefore: jwt.NewNumericDate(time.Now()),
			Issuer:    "symfony-twig-hm",
		},
	}

	token := jwt.NewWithClaims(jwt.SigningMethodHS256, claims)
	return token.SignedString([]byte(s.jwtSecret))
}

// ValidateToken validates a JWT token and returns the claims
func (s *AuthService) ValidateToken(tokenString string) (*Claims, error) {
	token, err := jwt.ParseWithClaims(tokenString, &Claims{}, func(token *jwt.Token) (interface{}, error) {
		if _, ok := token.Method.(*jwt.SigningMethodHMAC); !ok {
			return nil, errors.New("unexpected signing method")
		}
		return []byte(s.jwtSecret), nil
	})

	if err != nil {
		return nil, err
	}

	if claims, ok := token.Claims.(*Claims); ok && token.Valid {
		return claims, nil
	}

	return nil, errors.New("invalid token")
}

// GetUserByID retrieves a user by ID
func (s *AuthService) GetUserByID(userID string) (*User, error) {
	for _, user := range s.users {
		if user.ID == userID {
			return user, nil
		}
	}
	return nil, errors.New("user not found")
}

// GetByEmail retrieves a user by email
func (s *AuthService) GetByEmail(email string) (*User, error) {
	user, exists := s.users[email]
	if !exists {
		return nil, errors.New("user not found")
	}
	return user, nil
}

// UpdatePassword updates a user's password
func (s *AuthService) UpdatePassword(userID, currentPassword, newPassword string) error {
	for _, user := range s.users {
		if user.ID == userID {
			if err := bcrypt.CompareHashAndPassword([]byte(user.PasswordHash), []byte(currentPassword)); err != nil {
				return errors.New("current password is incorrect")
			}

			passwordHash, err := bcrypt.GenerateFromPassword([]byte(newPassword), bcrypt.DefaultCost)
			if err != nil {
				return err
			}

			user.PasswordHash = string(passwordHash)
			return nil
		}
	}
	return errors.New("user not found")
}

// ChangePassword changes a user's password (admin override)
func (s *AuthService) ChangePassword(userID, newPassword string) error {
	for _, user := range s.users {
		if user.ID == userID {
			passwordHash, err := bcrypt.GenerateFromPassword([]byte(newPassword), bcrypt.DefaultCost)
			if err != nil {
				return err
			}

			user.PasswordHash = string(passwordHash)
			return nil
		}
	}
	return errors.New("user not found")
}

// generateID generates a unique user ID
func generateID() string {
	return "user_" + time.Now().Format("20060102150405")
}

// isValidEmail validates email format
func isValidEmail(email string) bool {
	emailRegex := regexp.MustCompile(`^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$`)
	return emailRegex.MatchString(email)
}
