package entity

import "time"

type MasterKey struct {
	ID        string     `json:"id"`
	Name      string     `json:"name"`
	Version   int        `json:"version"`
	Key       string     `json:"key"`
	Algorithm string     `json:"algorithm"`
	IsActive  bool       `json:"is_active"`
	CreatedAt time.Time  `json:"created_at"`
	RetiredAt *time.Time `json:"retired_at,omitempty"`
	RotatedAt *time.Time `json:"rotated_at,omitempty"`
}

type DataEncryptionKey struct {
	ID           string     `json:"id"`
	Name         string     `json:"name"`
	EncryptedKey string     `json:"encrypted_key"`
	Algorithm    string     `json:"algorithm"`
	MasterKeyID  string     `json:"master_key_id"`
	CreatedAt    time.Time  `json:"created_at"`
	RetiredAt    *time.Time `json:"retired_at,omitempty"`
}

type KeyVersion struct {
	ID            string    `json:"id"`
	MasterKeyID   string    `json:"master_key_id"`
	Version       int       `json:"version"`
	EncryptedKey  string    `json:"encrypted_key"`
	CreatedAt     time.Time `json:"created_at"`
	EncryptedByID string    `json:"encrypted_by_id"`
}

type KeyAuditLog struct {
	ID           string    `json:"id"`
	Operation    string    `json:"operation"`
	KeyID        string    `json:"key_id"`
	KeyType      string    `json:"key_type"`
	UserID       string    `json:"user_id"`
	IPAddress    string    `json:"ip_address"`
	Success      bool      `json:"success"`
	ErrorMessage string    `json:"error_message,omitempty"`
	Metadata     string    `json:"metadata,omitempty"`
	CreatedAt    time.Time `json:"created_at"`
}

type EncryptRequest struct {
	Plaintext string `json:"plaintext"`
	Algorithm string `json:"algorithm"`
	KeyName   string `json:"key_name"`
}

type DecryptRequest struct {
	Ciphertext string `json:"ciphertext"`
	Algorithm  string `json:"algorithm"`
	KeyName    string `json:"key_name"`
	IV         string `json:"iv"`
	Tag        string `json:"tag"`
}

type SignRequest struct {
	Data      string `json:"data"`
	Algorithm string `json:"algorithm"`
	KeyName   string `json:"key_name"`
}

type VerifyRequest struct {
	Data      string `json:"data"`
	Signature string `json:"signature"`
	Algorithm string `json:"algorithm"`
	KeyName   string `json:"key_name"`
}

type RotateKeyRequest struct {
	KeyName string `json:"key_name"`
}

type EncryptResponse struct {
	Ciphertext string `json:"ciphertext"`
	Algorithm  string `json:"algorithm"`
	IV         string `json:"iv"`
	Tag        string `json:"tag"`
}

type DecryptResponse struct {
	Plaintext string `json:"plaintext"`
}

type SignResponse struct {
	Signature string `json:"signature"`
}

type VerifyResponse struct {
	Valid bool `json:"valid"`
}

type RotateKeyResponse struct {
	PreviousVersion int       `json:"previous_version"`
	NewVersion      int       `json:"new_version"`
	MasterKeyID     string    `json:"master_key_id"`
	RotatedAt       time.Time `json:"rotated_at"`
}
