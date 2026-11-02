package service

import (
	"crypto"
	"crypto/aes"
	"crypto/cipher"
	"crypto/rand"
	"crypto/rsa"
	"crypto/sha256"
	"crypto/x509"
	"encoding/base64"
	"fmt"
	"strings"

	"community.local/modules/keymanagement/entity"
	"community.local/modules/keymanagement/repository"
)

type KeyService struct {
	keyRepo *repository.KeyRepository
	dataDir string
}

func NewKeyService(dataDir string) (*KeyService, error) {
	s := &KeyService{
		dataDir: dataDir,
	}

	keyFile := dataDir + "/keys.json"
	s.keyRepo = repository.NewKeyRepository(keyFile)

	return s, nil
}

func (s *KeyService) CreateMasterKey(name string) (*entity.MasterKey, error) {
	keyBytes := make([]byte, 32)
	if _, err := rand.Read(keyBytes); err != nil {
		return nil, fmt.Errorf("failed to generate key: %v", err)
	}

	key := &entity.MasterKey{
		ID:        s.generateUUID(),
		Name:      name,
		Key:       base64.RawStdEncoding.EncodeToString(keyBytes),
		Algorithm: "AES-256-GCM",
		IsActive:  false,
	}

	if err := s.keyRepo.CreateMasterKey(key); err != nil {
		return nil, err
	}

	s.keyRepo.AddAuditLog(entity.KeyAuditLog{
		Operation: "CREATE",
		KeyID:     key.ID,
		KeyType:   "MASTER_KEY",
		Success:   true,
	})

	return key, nil
}

func (s *KeyService) GetMasterKey(id string) (*entity.MasterKey, error) {
	return s.keyRepo.GetMasterKey(id)
}

func (s *KeyService) GetActiveMasterKey() (*entity.MasterKey, error) {
	keys, err := s.keyRepo.ListMasterKeys()
	if err != nil {
		return nil, err
	}

	for _, key := range keys {
		if key.IsActive && key.RetiredAt == nil {
			return &key, nil
		}
	}
	return nil, fmt.Errorf("no active master key found")
}

func (s *KeyService) ActivateMasterKey(id string) error {
	err := s.keyRepo.ActivateMasterKey(id)
	if err != nil {
		return err
	}

	s.keyRepo.AddAuditLog(entity.KeyAuditLog{
		Operation: "ACTIVATE",
		KeyID:     id,
		KeyType:   "MASTER_KEY",
		Success:   true,
	})

	return nil
}

func (s *KeyService) RotateMasterKey(id string) (*entity.RotateKeyResponse, error) {
	currentKey, err := s.keyRepo.GetMasterKey(id)
	if err != nil {
		return nil, err
	}

	previousVersion := currentKey.Version

	newKeyBytes := make([]byte, 32)
	if _, err := rand.Read(newKeyBytes); err != nil {
		return nil, fmt.Errorf("failed to generate new key: %v", err)
	}

	newVersion := entity.KeyVersion{
		ID:           fmt.Sprintf("%s-v%d", id, previousVersion+1),
		MasterKeyID:  id,
		Version:      previousVersion + 1,
		EncryptedKey: base64.RawStdEncoding.EncodeToString(newKeyBytes),
	}

	if err := s.keyRepo.AddKeyVersion(id, newVersion); err != nil {
		return nil, err
	}

	now := currentKey.CreatedAt
	currentKey.Key = newVersion.EncryptedKey
	currentKey.Version = newVersion.Version
	currentKey.RotatedAt = &now
	s.keyRepo.UpdateMasterKey(currentKey)

	s.keyRepo.AddAuditLog(entity.KeyAuditLog{
		Operation: "ROTATE",
		KeyID:     id,
		KeyType:   "MASTER_KEY",
		Success:   true,
		Metadata:  fmt.Sprintf("previous_version: %d, new_version: %d", previousVersion, newVersion.Version),
	})

	return &entity.RotateKeyResponse{
		PreviousVersion: previousVersion,
		NewVersion:      newVersion.Version,
		MasterKeyID:     id,
		RotatedAt:       now,
	}, nil
}

func (s *KeyService) RetireMasterKey(id string) error {
	err := s.keyRepo.RetireMasterKey(id)
	if err != nil {
		return err
	}

	s.keyRepo.AddAuditLog(entity.KeyAuditLog{
		Operation: "RETIRE",
		KeyID:     id,
		KeyType:   "MASTER_KEY",
		Success:   true,
	})

	return nil
}

func (s *KeyService) CreateDEK(name, masterKeyID string) (*entity.DataEncryptionKey, error) {
	keyBytes := make([]byte, 32)
	if _, err := rand.Read(keyBytes); err != nil {
		return nil, fmt.Errorf("failed to generate DEK: %v", err)
	}

	masterKey, err := s.keyRepo.GetMasterKey(masterKeyID)
	if err != nil {
		return nil, err
	}

	masterKeyBytes, _ := base64.RawStdEncoding.DecodeString(masterKey.Key)
	ciphertext, iv, tag, err := s.encryptAESGCM(masterKeyBytes, keyBytes)
	if err != nil {
		return nil, fmt.Errorf("failed to encrypt DEK: %v", err)
	}
	encryptedKey := base64.StdEncoding.EncodeToString(ciphertext) + ":" + base64.StdEncoding.EncodeToString(iv) + ":" + base64.StdEncoding.EncodeToString(tag)

	dek := &entity.DataEncryptionKey{
		ID:           s.generateUUID(),
		Name:         name,
		EncryptedKey: encryptedKey,
		Algorithm:    "AES-256-GCM",
		MasterKeyID:  masterKeyID,
	}

	if err := s.keyRepo.CreateDEK(dek); err != nil {
		return nil, err
	}

	s.keyRepo.AddAuditLog(entity.KeyAuditLog{
		Operation: "CREATE",
		KeyID:     dek.ID,
		KeyType:   "DATA_ENCRYPTION_KEY",
		Success:   true,
	})

	return dek, nil
}

func (s *KeyService) GetDEK(id string) (*entity.DataEncryptionKey, error) {
	return s.keyRepo.GetDEK(id)
}

func (s *KeyService) Encrypt(req *entity.EncryptRequest, userID, ipAddress string) (*entity.EncryptResponse, error) {
	var keyBytes []byte
	var algorithm string

	if req.KeyName != "" {
		dek, err := s.keyRepo.GetDEKByName(req.KeyName)
		if err != nil {
			s.keyRepo.AddAuditLog(entity.KeyAuditLog{
				Operation:    "ENCRYPT",
				KeyID:        req.KeyName,
				KeyType:      "DATA_ENCRYPTION_KEY",
				UserID:       userID,
				IPAddress:    ipAddress,
				Success:      false,
				ErrorMessage: err.Error(),
			})
			return nil, fmt.Errorf("DEK not found: %v", err)
		}

		masterKey, err := s.keyRepo.GetMasterKey(dek.MasterKeyID)
		if err != nil {
			return nil, err
		}

		masterKeyBytes, _ := base64.RawStdEncoding.DecodeString(masterKey.Key)
		parts := strings.Split(dek.EncryptedKey, ":")
		if len(parts) != 3 {
			return nil, fmt.Errorf("invalid encrypted DEK format")
		}
		ciphertext, err1 := base64.StdEncoding.DecodeString(parts[0])
		iv, err2 := base64.StdEncoding.DecodeString(parts[1])
		tag, err3 := base64.StdEncoding.DecodeString(parts[2])
		if err1 != nil || err2 != nil || err3 != nil {
			return nil, fmt.Errorf("invalid base64 in DEK")
		}
		combined := append(iv, ciphertext...)
		combined = append(combined, tag...)
		keyBytes, err = s.decryptAESGCM(masterKeyBytes, combined)
		if err != nil {
			return nil, fmt.Errorf("failed to decrypt DEK: %v", err)
		}
		algorithm = dek.Algorithm
	} else {
		masterKey, err := s.GetActiveMasterKey()
		if err != nil {
			s.keyRepo.AddAuditLog(entity.KeyAuditLog{
				Operation:    "ENCRYPT",
				KeyID:        "active",
				KeyType:      "MASTER_KEY",
				UserID:       userID,
				IPAddress:    ipAddress,
				Success:      false,
				ErrorMessage: err.Error(),
			})
			return nil, err
		}

		keyBytes, _ = base64.RawStdEncoding.DecodeString(masterKey.Key)
		algorithm = masterKey.Algorithm
	}

	plaintext, err := base64.StdEncoding.DecodeString(req.Plaintext)
	if err != nil {
		plaintext = []byte(req.Plaintext)
	}

	ciphertext, iv, tag, err := s.encryptAESGCM(keyBytes, plaintext)
	if err != nil {
		s.keyRepo.AddAuditLog(entity.KeyAuditLog{
			Operation:    "ENCRYPT",
			KeyID:        req.KeyName,
			KeyType:      "DATA_ENCRYPTION_KEY",
			UserID:       userID,
			IPAddress:    ipAddress,
			Success:      false,
			ErrorMessage: err.Error(),
		})
		return nil, fmt.Errorf("encryption failed: %v", err)
	}

	s.keyRepo.AddAuditLog(entity.KeyAuditLog{
		Operation: "ENCRYPT",
		KeyID:     req.KeyName,
		KeyType:   "DATA_ENCRYPTION_KEY",
		UserID:    userID,
		IPAddress: ipAddress,
		Success:   true,
	})

	return &entity.EncryptResponse{
		Ciphertext: base64.StdEncoding.EncodeToString(ciphertext),
		Algorithm:  algorithm,
		IV:         base64.StdEncoding.EncodeToString(iv),
		Tag:        base64.StdEncoding.EncodeToString(tag),
	}, nil
}

func (s *KeyService) Decrypt(req *entity.DecryptRequest, userID, ipAddress string) (*entity.DecryptResponse, error) {
	var keyBytes []byte

	if req.KeyName != "" {
		dek, err := s.keyRepo.GetDEKByName(req.KeyName)
		if err != nil {
			s.keyRepo.AddAuditLog(entity.KeyAuditLog{
				Operation:    "DECRYPT",
				KeyID:        req.KeyName,
				KeyType:      "DATA_ENCRYPTION_KEY",
				UserID:       userID,
				IPAddress:    ipAddress,
				Success:      false,
				ErrorMessage: err.Error(),
			})
			return nil, fmt.Errorf("DEK not found: %v", err)
		}

		masterKey, err := s.keyRepo.GetMasterKey(dek.MasterKeyID)
		if err != nil {
			return nil, err
		}

		masterKeyBytes, _ := base64.RawStdEncoding.DecodeString(masterKey.Key)
		parts := strings.Split(dek.EncryptedKey, ":")
		if len(parts) != 3 {
			return nil, fmt.Errorf("invalid encrypted DEK format")
		}
		ciphertext, err1 := base64.StdEncoding.DecodeString(parts[0])
		iv, err2 := base64.StdEncoding.DecodeString(parts[1])
		tag, err3 := base64.StdEncoding.DecodeString(parts[2])
		if err1 != nil || err2 != nil || err3 != nil {
			return nil, fmt.Errorf("invalid base64 in DEK")
		}
		combined := append(iv, ciphertext...)
		combined = append(combined, tag...)
		keyBytes, err = s.decryptAESGCM(masterKeyBytes, combined)
		if err != nil {
			return nil, fmt.Errorf("failed to decrypt DEK: %v", err)
		}
	} else {
		masterKey, err := s.GetActiveMasterKey()
		if err != nil {
			s.keyRepo.AddAuditLog(entity.KeyAuditLog{
				Operation:    "DECRYPT",
				KeyID:        "active",
				KeyType:      "MASTER_KEY",
				UserID:       userID,
				IPAddress:    ipAddress,
				Success:      false,
				ErrorMessage: err.Error(),
			})
			return nil, err
		}

		keyBytes, _ = base64.RawStdEncoding.DecodeString(masterKey.Key)
	}

	ciphertext, _ := base64.StdEncoding.DecodeString(req.Ciphertext)
	iv, _ := base64.StdEncoding.DecodeString(req.IV)
	tag, _ := base64.StdEncoding.DecodeString(req.Tag)
	combined := append(iv, ciphertext...)
	combined = append(combined, tag...)

	plaintext, err := s.decryptAESGCM(keyBytes, combined)
	if err != nil {
		s.keyRepo.AddAuditLog(entity.KeyAuditLog{
			Operation:    "DECRYPT",
			KeyID:        req.KeyName,
			KeyType:      "DATA_ENCRYPTION_KEY",
			UserID:       userID,
			IPAddress:    ipAddress,
			Success:      false,
			ErrorMessage: err.Error(),
		})
		return nil, fmt.Errorf("decryption failed: %v", err)
	}

	s.keyRepo.AddAuditLog(entity.KeyAuditLog{
		Operation: "DECRYPT",
		KeyID:     req.KeyName,
		KeyType:   "DATA_ENCRYPTION_KEY",
		UserID:    userID,
		IPAddress: ipAddress,
		Success:   true,
	})

	return &entity.DecryptResponse{
		Plaintext: string(plaintext),
	}, nil
}

func (s *KeyService) EncryptRSA(plaintext []byte, publicKey *rsa.PublicKey) ([]byte, error) {
	ciphertext, err := rsa.EncryptOAEP(sha256.New(), rand.Reader, publicKey, plaintext, nil)
	if err != nil {
		return nil, fmt.Errorf("RSA encryption failed: %v", err)
	}
	return ciphertext, nil
}

func (s *KeyService) DecryptRSA(ciphertext []byte, privateKey *rsa.PrivateKey) ([]byte, error) {
	plaintext, err := rsa.DecryptOAEP(sha256.New(), rand.Reader, privateKey, ciphertext, nil)
	if err != nil {
		return nil, fmt.Errorf("RSA decryption failed: %v", err)
	}
	return plaintext, nil
}

func (s *KeyService) Sign(data []byte, privateKey *rsa.PrivateKey) ([]byte, error) {
	hashed := sha256.Sum256(data)
	signature, err := rsa.SignPKCS1v15(rand.Reader, privateKey, crypto.SHA256, hashed[:])
	if err != nil {
		return nil, fmt.Errorf("signing failed: %v", err)
	}
	return signature, nil
}

func (s *KeyService) Verify(data []byte, signature []byte, publicKey *rsa.PublicKey) bool {
	hashed := sha256.Sum256(data)
	err := rsa.VerifyPKCS1v15(publicKey, crypto.SHA256, hashed[:], signature)
	return err == nil
}

func (s *KeyService) GenerateRSAKeyPair() (*rsa.PrivateKey, *rsa.PublicKey, error) {
	privateKey, err := rsa.GenerateKey(rand.Reader, 2048)
	if err != nil {
		return nil, nil, fmt.Errorf("failed to generate RSA key pair: %v", err)
	}
	return privateKey, &privateKey.PublicKey, nil
}

func (s *KeyService) ExportRSAPrivateKey(privateKey *rsa.PrivateKey) []byte {
	return x509.MarshalPKCS1PrivateKey(privateKey)
}

func (s *KeyService) ImportRSAPrivateKey(data []byte) (*rsa.PrivateKey, error) {
	return x509.ParsePKCS1PrivateKey(data)
}

func (s *KeyService) ExportRSAPublicKey(publicKey *rsa.PublicKey) []byte {
	return x509.MarshalPKCS1PublicKey(publicKey)
}

func (s *KeyService) ImportRSAPublicKey(data []byte) (*rsa.PublicKey, error) {
	pub, err := x509.ParsePKCS1PublicKey(data)
	if err != nil {
		return nil, err
	}
	return pub, nil
}

func (s *KeyService) encryptAESGCM(key, plaintext []byte) ([]byte, []byte, []byte, error) {
	block, err := aes.NewCipher(key)
	if err != nil {
		return nil, nil, nil, err
	}

	gcm, err := cipher.NewGCM(block)
	if err != nil {
		return nil, nil, nil, err
	}

	iv := make([]byte, gcm.NonceSize())
	if _, err := rand.Read(iv); err != nil {
		return nil, nil, nil, err
	}

	ciphertext := gcm.Seal(nil, iv, plaintext, nil)
	ct := ciphertext[:len(plaintext)]
	tag := ciphertext[len(plaintext):]

	return ct, iv, tag, nil
}

func (s *KeyService) decryptAESGCM(key, encrypted []byte) ([]byte, error) {
	block, err := aes.NewCipher(key)
	if err != nil {
		return nil, err
	}

	gcm, err := cipher.NewGCM(block)
	if err != nil {
		return nil, err
	}

	nonceSize := gcm.NonceSize()
	if len(encrypted) < nonceSize+gcm.Overhead() {
		return nil, fmt.Errorf("ciphertext too short")
	}

	iv := encrypted[:nonceSize]
	ciphertext := encrypted[nonceSize:]

	plaintext, err := gcm.Open(nil, iv, ciphertext, nil)
	if err != nil {
		return nil, err
	}

	return plaintext, nil
}

func (s *KeyService) generateUUID() string {
	b := make([]byte, 16)
	rand.Read(b)
	return fmt.Sprintf("%x-%x-%x-%x-%x", b[0:4], b[4:6], b[6:8], b[8:10], b[10:])
}

func (s *KeyService) GetAuditLogs(keyID string, limit int) ([]entity.KeyAuditLog, error) {
	return s.keyRepo.GetAuditLogs(keyID, limit)
}

func (s *KeyService) ListMasterKeys() ([]entity.MasterKey, error) {
	return s.keyRepo.ListMasterKeys()
}
