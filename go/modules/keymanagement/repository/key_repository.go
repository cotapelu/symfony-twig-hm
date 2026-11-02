package repository

import (
	"encoding/json"
	"fmt"
	"log"
	"os"
	"path/filepath"
	"sync"
	"time"

	"community.local/modules/keymanagement/entity"
)

type KeyRepository struct {
	mu          sync.RWMutex
	filePath    string
	masterKeys  map[string]entity.MasterKey
	dekKeys     map[string]entity.DataEncryptionKey
	keyVersions map[string][]entity.KeyVersion
	auditLogs   []entity.KeyAuditLog
	nextLogID   int
}

func NewKeyRepository(filePath string) *KeyRepository {
	repo := &KeyRepository{
		filePath:    filePath,
		masterKeys:  make(map[string]entity.MasterKey),
		dekKeys:     make(map[string]entity.DataEncryptionKey),
		keyVersions: make(map[string][]entity.KeyVersion),
		auditLogs:   make([]entity.KeyAuditLog, 0),
		nextLogID:   1,
	}
	repo.ensureDataDirectory()
	repo.restoreFromFile()
	return repo
}

func (r *KeyRepository) ensureDataDirectory() {
	dir := filepath.Dir(r.filePath)
	if dir != "" && dir != "." {
		os.MkdirAll(dir, 0755)
	}
}

func (r *KeyRepository) restoreFromFile() {
	data, err := os.ReadFile(r.filePath)
	if err != nil {
		if os.IsNotExist(err) {
			return
		}
		log.Printf("Warning: Failed to read keys file: %v", err)
		return
	}

	var saved struct {
		MasterKeys  map[string]entity.MasterKey         `json:"master_keys"`
		DEKKeys     map[string]entity.DataEncryptionKey `json:"dek_keys"`
		KeyVersions map[string][]entity.KeyVersion      `json:"key_versions"`
		AuditLogs   []entity.KeyAuditLog                `json:"audit_logs"`
		NextLogID   int                                 `json:"next_log_id"`
	}

	if err := json.Unmarshal(data, &saved); err != nil {
		log.Printf("Warning: Failed to unmarshal keys data: %v", err)
		return
	}

	r.masterKeys = saved.MasterKeys
	r.dekKeys = saved.DEKKeys
	r.keyVersions = saved.KeyVersions
	r.auditLogs = saved.AuditLogs
	r.nextLogID = saved.NextLogID
}

func (r *KeyRepository) persistToFile() {
	data, err := json.MarshalIndent(map[string]interface{}{
		"master_keys":  r.masterKeys,
		"dek_keys":     r.dekKeys,
		"key_versions": r.keyVersions,
		"audit_logs":   r.auditLogs,
		"next_log_id":  r.nextLogID,
	}, "", "  ")
	if err != nil {
		log.Printf("Error: Failed to marshal keys data: %v", err)
		return
	}
	if err := os.WriteFile(r.filePath, data, 0644); err != nil {
		log.Printf("Error: Failed to write keys file: %v", err)
	}
}

func (r *KeyRepository) CreateMasterKey(key *entity.MasterKey) error {
	r.mu.Lock()
	defer r.mu.Unlock()

	if _, exists := r.masterKeys[key.ID]; exists {
		return fmt.Errorf("master key already exists")
	}

	key.CreatedAt = time.Now()
	key.Version = 1
	key.IsActive = false

	r.masterKeys[key.ID] = *key
	r.keyVersions[key.ID] = append(r.keyVersions[key.ID], entity.KeyVersion{
		ID:           fmt.Sprintf("%s-v%d", key.ID, key.Version),
		MasterKeyID:  key.ID,
		Version:      key.Version,
		EncryptedKey: key.Key,
		CreatedAt:    key.CreatedAt,
	})

	r.persistToFile()
	return nil
}

func (r *KeyRepository) GetMasterKey(id string) (*entity.MasterKey, error) {
	r.mu.RLock()
	defer r.mu.RUnlock()

	key, exists := r.masterKeys[id]
	if !exists {
		return nil, fmt.Errorf("master key not found")
	}
	return &key, nil
}

func (r *KeyRepository) GetMasterKeyByName(name string) (*entity.MasterKey, error) {
	r.mu.RLock()
	defer r.mu.RUnlock()

	for _, key := range r.masterKeys {
		if key.Name == name {
			return &key, nil
		}
	}
	return nil, fmt.Errorf("master key not found")
}

func (r *KeyRepository) UpdateMasterKey(key *entity.MasterKey) error {
	r.mu.Lock()
	defer r.mu.Unlock()

	existing, exists := r.masterKeys[key.ID]
	if !exists {
		return fmt.Errorf("master key not found")
	}

	key.CreatedAt = existing.CreatedAt
	key.Version = existing.Version

	r.masterKeys[key.ID] = *key

	r.persistToFile()
	return nil
}

func (r *KeyRepository) ListMasterKeys() ([]entity.MasterKey, error) {
	r.mu.RLock()
	defer r.mu.RUnlock()

	keys := make([]entity.MasterKey, 0, len(r.masterKeys))
	for _, key := range r.masterKeys {
		keys = append(keys, key)
	}
	return keys, nil
}

func (r *KeyRepository) CreateDEK(key *entity.DataEncryptionKey) error {
	r.mu.Lock()
	defer r.mu.Unlock()

	if _, exists := r.dekKeys[key.ID]; exists {
		return fmt.Errorf("DEK already exists")
	}

	key.CreatedAt = time.Now()

	r.dekKeys[key.ID] = *key

	r.persistToFile()
	return nil
}

func (r *KeyRepository) GetDEK(id string) (*entity.DataEncryptionKey, error) {
	r.mu.RLock()
	defer r.mu.RUnlock()

	key, exists := r.dekKeys[id]
	if !exists {
		return nil, fmt.Errorf("DEK not found")
	}
	return &key, nil
}

func (r *KeyRepository) GetDEKByName(name string) (*entity.DataEncryptionKey, error) {
	r.mu.RLock()
	defer r.mu.RUnlock()

	for _, key := range r.dekKeys {
		if key.Name == name {
			return &key, nil
		}
	}
	return nil, fmt.Errorf("DEK not found")
}

func (r *KeyRepository) RetireDEK(id string) error {
	r.mu.Lock()
	defer r.mu.Unlock()

	key, exists := r.dekKeys[id]
	if !exists {
		return fmt.Errorf("DEK not found")
	}

	now := time.Now()
	key.RetiredAt = &now
	r.dekKeys[id] = key

	r.persistToFile()
	return nil
}

func (r *KeyRepository) AddKeyVersion(masterKeyID string, version entity.KeyVersion) error {
	r.mu.Lock()
	defer r.mu.Unlock()

	existing, exists := r.masterKeys[masterKeyID]
	if !exists {
		return fmt.Errorf("master key not found")
	}

	r.keyVersions[masterKeyID] = append(r.keyVersions[masterKeyID], version)
	existing.Version = version.Version
	r.masterKeys[masterKeyID] = existing

	r.persistToFile()
	return nil
}

func (r *KeyRepository) GetKeyVersions(masterKeyID string) ([]entity.KeyVersion, error) {
	r.mu.RLock()
	defer r.mu.RUnlock()

	versions, exists := r.keyVersions[masterKeyID]
	if !exists {
		return []entity.KeyVersion{}, nil
	}
	return versions, nil
}

func (r *KeyRepository) AddAuditLog(log entity.KeyAuditLog) error {
	r.mu.Lock()
	defer r.mu.Unlock()

	log.ID = fmt.Sprintf("log_%d", r.nextLogID)
	log.CreatedAt = time.Now()
	r.nextLogID++

	r.auditLogs = append(r.auditLogs, log)

	r.persistToFile()
	return nil
}

func (r *KeyRepository) GetAuditLogs(keyID string, limit int) ([]entity.KeyAuditLog, error) {
	r.mu.RLock()
	defer r.mu.RUnlock()

	var logs []entity.KeyAuditLog
	count := 0
	for i := len(r.auditLogs) - 1; i >= 0 && count < limit; i-- {
		if r.auditLogs[i].KeyID == keyID || keyID == "" {
			logs = append(logs, r.auditLogs[i])
			count++
		}
	}
	return logs, nil
}

func (r *KeyRepository) ActivateMasterKey(id string) error {
	r.mu.Lock()
	defer r.mu.Unlock()

	key, exists := r.masterKeys[id]
	if !exists {
		return fmt.Errorf("master key not found")
	}

	for _, k := range r.masterKeys {
		if k.IsActive && k.ID != id {
			k.IsActive = false
			r.masterKeys[k.ID] = k
		}
	}

	key.IsActive = true
	r.masterKeys[id] = key

	r.persistToFile()
	return nil
}

func (r *KeyRepository) RetireMasterKey(id string) error {
	r.mu.Lock()
	defer r.mu.Unlock()

	key, exists := r.masterKeys[id]
	if !exists {
		return fmt.Errorf("master key not found")
	}

	now := time.Now()
	key.RetiredAt = &now
	key.IsActive = false
	r.masterKeys[id] = key

	r.persistToFile()
	return nil
}
