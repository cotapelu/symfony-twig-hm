package repository

import (
	"encoding/json"
	"fmt"
	"log"
	"os"
	"path/filepath"
	"strings"
	"sync"
	"time"

	"community.local/modules/authservice/entity"
)

type RepositoryConfig struct {
	FilePath  string
	DataType  string
	IndexType string
}

func EnsureDataDirectory(filePath string) {
	dir := filepath.Dir(filePath)
	if dir != "" && dir != "." {
		os.MkdirAll(dir, 0755)
	}
}

func RestoreDataFromFile[T any](filePath string, data *T) bool {
	content, err := os.ReadFile(filePath)
	if err != nil {
		if os.IsNotExist(err) {
			return false
		}
		log.Printf("Warning: Failed to read %s file: %v", filePath, err)
		return false
	}

	if err := json.Unmarshal(content, data); err != nil {
		log.Printf("Warning: Failed to unmarshal data from %s: %v", filePath, err)
		return false
	}
	return true
}

func PersistDataToFile(filePath string, data interface{}) {
	content, err := json.MarshalIndent(data, "", "  ")
	if err != nil {
		log.Printf("Error: Failed to marshal data for %s: %v", filePath, err)
		return
	}
	if err := os.WriteFile(filePath, content, 0644); err != nil {
		log.Printf("Error: Failed to write %s file: %v", filePath, err)
	}
}

type UserRepository struct {
	mu       sync.RWMutex
	filePath string
	users    map[string]entity.User
	emailIdx map[string]string
}

func NewUserRepository(filePath string) *UserRepository {
	repo := &UserRepository{
		filePath: filePath,
		users:    make(map[string]entity.User),
		emailIdx: make(map[string]string),
	}
	EnsureDataDirectory(filePath)
	repo.restoreFromFile()
	return repo
}

func (r *UserRepository) restoreFromFile() {
	data, err := os.ReadFile(r.filePath)
	if err != nil {
		if os.IsNotExist(err) {
			return
		}
		log.Printf("Warning: Failed to read users file: %v", err)
		return
	}

	var saved struct {
		Users    map[string]entity.User `json:"users"`
		EmailIdx map[string]string      `json:"email_idx"`
	}

	if err := json.Unmarshal(data, &saved); err != nil {
		log.Printf("Warning: Failed to unmarshal users data: %v", err)
		return
	}

	r.users = saved.Users
	r.emailIdx = saved.EmailIdx
}

func (r *UserRepository) persistToFile() {
	data, err := json.MarshalIndent(map[string]interface{}{
		"users":     r.users,
		"email_idx": r.emailIdx,
	}, "", "  ")
	if err != nil {
		log.Printf("Error: Failed to marshal users data: %v", err)
		return
	}
	if err := os.WriteFile(r.filePath, data, 0644); err != nil {
		log.Printf("Error: Failed to write users file: %v", err)
	}
}

func (r *UserRepository) Create(user *entity.User) error {
	r.mu.Lock()
	defer r.mu.Unlock()

	if _, exists := r.users[user.ID]; exists {
		return fmt.Errorf("user already exists")
	}
	if _, exists := r.emailIdx[strings.ToLower(user.Email)]; exists {
		return fmt.Errorf("email already registered")
	}

	user.CreatedAt = time.Now()
	user.UpdatedAt = time.Now()

	r.users[user.ID] = *user
	r.emailIdx[strings.ToLower(user.Email)] = user.ID

	r.persistToFile()
	return nil
}

func (r *UserRepository) GetByID(id string) (*entity.User, error) {
	r.mu.RLock()
	defer r.mu.RUnlock()

	user, exists := r.users[id]
	if !exists {
		return nil, fmt.Errorf("user not found")
	}
	return &user, nil
}

func (r *UserRepository) GetByEmail(email string) (*entity.User, error) {
	r.mu.RLock()
	defer r.mu.RUnlock()

	id, exists := r.emailIdx[strings.ToLower(email)]
	if !exists {
		return nil, fmt.Errorf("user not found")
	}

	user, exists := r.users[id]
	if !exists {
		return nil, fmt.Errorf("user not found")
	}
	return &user, nil
}

func (r *UserRepository) Update(user *entity.User) error {
	r.mu.Lock()
	defer r.mu.Unlock()

	existing, exists := r.users[user.ID]
	if !exists {
		return fmt.Errorf("user not found")
	}

	if existing.Email != user.Email {
		delete(r.emailIdx, strings.ToLower(existing.Email))
		r.emailIdx[strings.ToLower(user.Email)] = user.ID
	}

	user.UpdatedAt = time.Now()
	r.users[user.ID] = *user

	r.persistToFile()
	return nil
}

func (r *UserRepository) Delete(id string) error {
	r.mu.Lock()
	defer r.mu.Unlock()

	user, exists := r.users[id]
	if !exists {
		return fmt.Errorf("user not found")
	}

	delete(r.emailIdx, strings.ToLower(user.Email))
	delete(r.users, id)

	r.persistToFile()
	return nil
}

func (r *UserRepository) List() ([]entity.User, error) {
	r.mu.RLock()
	defer r.mu.RUnlock()

	users := make([]entity.User, 0, len(r.users))
	for _, user := range r.users {
		users = append(users, user)
	}
	return users, nil
}

type SessionRepository struct {
	mu       sync.RWMutex
	filePath string
	sessions map[string]entity.Session
	userIdx  map[string][]string
}

func NewSessionRepository(filePath string) *SessionRepository {
	repo := &SessionRepository{
		filePath: filePath,
		sessions: make(map[string]entity.Session),
		userIdx:  make(map[string][]string),
	}
	EnsureDataDirectory(filePath)
	repo.restoreFromFile()
	return repo
}

func (r *SessionRepository) restoreFromFile() {
	data, err := os.ReadFile(r.filePath)
	if err != nil {
		if os.IsNotExist(err) {
			return
		}
		log.Printf("Warning: Failed to read sessions file: %v", err)
		return
	}

	var saved struct {
		Sessions map[string]entity.Session `json:"sessions"`
		UserIdx  map[string][]string       `json:"user_idx"`
	}

	if err := json.Unmarshal(data, &saved); err != nil {
		log.Printf("Warning: Failed to unmarshal sessions data: %v", err)
		return
	}

	r.sessions = saved.Sessions
	r.userIdx = saved.UserIdx
}

func (r *SessionRepository) persistToFile() {
	data, err := json.MarshalIndent(map[string]interface{}{
		"sessions": r.sessions,
		"user_idx": r.userIdx,
	}, "", "  ")
	if err != nil {
		log.Printf("Error: Failed to marshal sessions data: %v", err)
		return
	}
	if err := os.WriteFile(r.filePath, data, 0644); err != nil {
		log.Printf("Error: Failed to write sessions file: %v", err)
	}
}

func (r *SessionRepository) Create(session *entity.Session) error {
	r.mu.Lock()
	defer r.mu.Unlock()

	if _, exists := r.sessions[session.ID]; exists {
		return fmt.Errorf("session already exists")
	}

	session.CreatedAt = time.Now()
	session.LastActiveAt = time.Now()

	r.sessions[session.ID] = *session
	r.userIdx[session.UserID] = append(r.userIdx[session.UserID], session.ID)

	r.persistToFile()
	return nil
}

func (r *SessionRepository) GetByID(id string) (*entity.Session, error) {
	r.mu.RLock()
	defer r.mu.RUnlock()

	session, exists := r.sessions[id]
	if !exists {
		return nil, fmt.Errorf("session not found")
	}
	return &session, nil
}

func (r *SessionRepository) GetByUserID(userID string) ([]entity.Session, error) {
	r.mu.RLock()
	defer r.mu.RUnlock()

	sessionIDs, exists := r.userIdx[userID]
	if !exists {
		return []entity.Session{}, nil
	}

	sessions := make([]entity.Session, 0, len(sessionIDs))
	for _, id := range sessionIDs {
		if session, ok := r.sessions[id]; ok {
			sessions = append(sessions, session)
		}
	}
	return sessions, nil
}

func (r *SessionRepository) Update(session *entity.Session) error {
	r.mu.Lock()
	defer r.mu.Unlock()

	existing, exists := r.sessions[session.ID]
	if !exists {
		return fmt.Errorf("session not found")
	}

	session.CreatedAt = existing.CreatedAt
	session.LastActiveAt = time.Now()

	r.sessions[session.ID] = *session

	r.persistToFile()
	return nil
}

func (r *SessionRepository) Delete(id string) error {
	r.mu.Lock()
	defer r.mu.Unlock()

	session, exists := r.sessions[id]
	if !exists {
		return fmt.Errorf("session not found")
	}

	delete(r.sessions, id)

	userSessions := r.userIdx[session.UserID]
	for i, sid := range userSessions {
		if sid == id {
			r.userIdx[session.UserID] = append(userSessions[:i], userSessions[i+1:]...)
			break
		}
	}

	r.persistToFile()
	return nil
}

func (r *SessionRepository) InvalidateUserSessions(userID string) error {
	r.mu.Lock()
	defer r.mu.Unlock()

	sessionIDs, exists := r.userIdx[userID]
	if !exists {
		return nil
	}

	for _, id := range sessionIDs {
		if session, ok := r.sessions[id]; ok {
			session.IsValid = false
			r.sessions[id] = session
		}
	}

	r.persistToFile()
	return nil
}

func (r *SessionRepository) CleanExpiredSessions() error {
	r.mu.Lock()
	defer r.mu.Unlock()

	now := time.Now()
	for id, session := range r.sessions {
		if session.ExpiresAt.Before(now) {
			delete(r.sessions, id)
			userSessions := r.userIdx[session.UserID]
			for i, sid := range userSessions {
				if sid == id {
					r.userIdx[session.UserID] = append(userSessions[:i], userSessions[i+1:]...)
					break
				}
			}
		}
	}

	r.persistToFile()
	return nil
}

type RefreshTokenRepository struct {
	mu       sync.RWMutex
	filePath string
	tokens   map[string]entity.RefreshToken
	userIdx  map[string][]string
}

func NewRefreshTokenRepository(filePath string) *RefreshTokenRepository {
	repo := &RefreshTokenRepository{
		filePath: filePath,
		tokens:   make(map[string]entity.RefreshToken),
		userIdx:  make(map[string][]string),
	}
	EnsureDataDirectory(filePath)
	repo.restoreFromFile()
	return repo
}

func (r *RefreshTokenRepository) restoreFromFile() {
	data, err := os.ReadFile(r.filePath)
	if err != nil {
		if os.IsNotExist(err) {
			return
		}
		log.Printf("Warning: Failed to read refresh tokens file: %v", err)
		return
	}

	var saved struct {
		Tokens  map[string]entity.RefreshToken `json:"tokens"`
		UserIdx map[string][]string            `json:"user_idx"`
	}

	if err := json.Unmarshal(data, &saved); err != nil {
		log.Printf("Warning: Failed to unmarshal refresh tokens data: %v", err)
		return
	}

	r.tokens = saved.Tokens
	r.userIdx = saved.UserIdx
}

func (r *RefreshTokenRepository) persistToFile() {
	data, err := json.MarshalIndent(map[string]interface{}{
		"tokens":   r.tokens,
		"user_idx": r.userIdx,
	}, "", "  ")
	if err != nil {
		log.Printf("Error: Failed to marshal refresh tokens data: %v", err)
		return
	}
	if err := os.WriteFile(r.filePath, data, 0644); err != nil {
		log.Printf("Error: Failed to write refresh tokens file: %v", err)
	}
}

func (r *RefreshTokenRepository) Create(token *entity.RefreshToken) error {
	r.mu.Lock()
	defer r.mu.Unlock()

	if _, exists := r.tokens[token.Token]; exists {
		return fmt.Errorf("refresh token already exists")
	}

	token.CreatedAt = time.Now()

	r.tokens[token.Token] = *token
	r.userIdx[token.UserID] = append(r.userIdx[token.UserID], token.Token)

	r.persistToFile()
	return nil
}

func (r *RefreshTokenRepository) GetByToken(tokenString string) (*entity.RefreshToken, error) {
	r.mu.RLock()
	defer r.mu.RUnlock()

	token, exists := r.tokens[tokenString]
	if !exists {
		return nil, fmt.Errorf("refresh token not found")
	}
	return &token, nil
}

func (r *RefreshTokenRepository) GetByUserID(userID string) ([]entity.RefreshToken, error) {
	r.mu.RLock()
	defer r.mu.RUnlock()

	tokenStrings, exists := r.userIdx[userID]
	if !exists {
		return []entity.RefreshToken{}, nil
	}

	tokens := make([]entity.RefreshToken, 0, len(tokenStrings))
	for _, ts := range tokenStrings {
		if token, ok := r.tokens[ts]; ok {
			tokens = append(tokens, token)
		}
	}
	return tokens, nil
}

func (r *RefreshTokenRepository) Revoke(tokenString string) error {
	r.mu.Lock()
	defer r.mu.Unlock()

	token, exists := r.tokens[tokenString]
	if !exists {
		return fmt.Errorf("refresh token not found")
	}

	now := time.Now()
	token.RevokedAt = &now
	token.IsRevoked = true
	r.tokens[tokenString] = token

	r.persistToFile()
	return nil
}

func (r *RefreshTokenRepository) RevokeAllForUser(userID string) error {
	r.mu.Lock()
	defer r.mu.Unlock()

	tokenStrings, exists := r.userIdx[userID]
	if !exists {
		return nil
	}

	now := time.Now()
	for _, ts := range tokenStrings {
		if token, ok := r.tokens[ts]; ok {
			token.RevokedAt = &now
			token.IsRevoked = true
			r.tokens[ts] = token
		}
	}

	r.persistToFile()
	return nil
}

func (r *RefreshTokenRepository) CleanExpiredTokens() error {
	r.mu.Lock()
	defer r.mu.Unlock()

	now := time.Now()
	for tokenString, token := range r.tokens {
		if token.ExpiresAt.Before(now) || token.IsRevoked {
			delete(r.tokens, tokenString)
			userTokens := r.userIdx[token.UserID]
			for i, ts := range userTokens {
				if ts == tokenString {
					r.userIdx[token.UserID] = append(userTokens[:i], userTokens[i+1:]...)
					break
				}
			}
		}
	}

	r.persistToFile()
	return nil
}
