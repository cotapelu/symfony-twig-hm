package service

import (
	"crypto/rsa"
	"encoding/base64"
	"testing"
	"time"

	"community.local/modules/keymanagement/entity"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"
)

func newTestService(t *testing.T) *KeyService {
	tmpDir := t.TempDir()
	svc, err := NewKeyService(tmpDir)
	require.NoError(t, err)
	return svc
}

func newNow() time.Time {
	return time.Now().Truncate(time.Microsecond)
}

func TestKeyService_CreateMasterKey(t *testing.T) {
	svc := newTestService(t)

	name := "test-master"
	key, err := svc.CreateMasterKey(name)
	assert.NoError(t, err)
	assert.NotEmpty(t, key.ID)
	assert.Equal(t, name, key.Name)
	assert.False(t, key.IsActive)
	assert.NotNil(t, key.CreatedAt)
	assert.Empty(t, key.RetiredAt)
	assert.Empty(t, key.RotatedAt)
	assert.Equal(t, 1, key.Version)
}

func TestKeyService_GetMasterKey(t *testing.T) {
	svc := newTestService(t)

	created, err := svc.CreateMasterKey("master1")
	assert.NoError(t, err)

	retrieved, err := svc.GetMasterKey(created.ID)
	assert.NoError(t, err)
	assert.Equal(t, created.ID, retrieved.ID)
	assert.Equal(t, created.Name, retrieved.Name)
}

func TestKeyService_GetMasterKey_NotFound(t *testing.T) {
	svc := newTestService(t)

	_, err := svc.GetMasterKey("nonexistent")
	assert.Error(t, err)
}

func TestKeyService_GetActiveMasterKey_None(t *testing.T) {
	svc := newTestService(t)

	_, err := svc.GetActiveMasterKey()
	assert.Error(t, err)
}

func TestKeyService_ActivateMasterKey(t *testing.T) {
	svc := newTestService(t)

	key, err := svc.CreateMasterKey("master1")
	assert.NoError(t, err)

	err = svc.ActivateMasterKey(key.ID)
	assert.NoError(t, err)

	// Verify activation via repository directly (or via GetMasterKey)
	activated, err := svc.GetMasterKey(key.ID)
	assert.NoError(t, err)
	assert.True(t, activated.IsActive)
}

func TestKeyService_GetActiveMasterKey_AfterActivation(t *testing.T) {
	svc := newTestService(t)

	key, _ := svc.CreateMasterKey("master1")
	svc.ActivateMasterKey(key.ID)

	active, err := svc.GetActiveMasterKey()
	assert.NoError(t, err)
	assert.Equal(t, key.ID, active.ID)
}

func TestKeyService_RotateMasterKey(t *testing.T) {
	svc := newTestService(t)

	key, err := svc.CreateMasterKey("master1")
	assert.NoError(t, err)
	initialVersion := key.Version

	// Activate before rotation
	svc.ActivateMasterKey(key.ID)

	resp, err := svc.RotateMasterKey(key.ID)
	assert.NoError(t, err)
	assert.NotNil(t, resp)
	assert.Equal(t, initialVersion, resp.PreviousVersion)
	assert.Equal(t, initialVersion+1, resp.NewVersion)
	assert.Equal(t, key.ID, resp.MasterKeyID)
	assert.NotNil(t, resp.RotatedAt)

	// Verify rotation in stored key
	rotated, _ := svc.GetMasterKey(key.ID)
	assert.Equal(t, initialVersion+1, rotated.Version)
	assert.NotNil(t, rotated.RotatedAt)
	assert.True(t, len(rotated.Key) > 0)
}

func TestKeyService_RetireMasterKey(t *testing.T) {
	svc := newTestService(t)

	key, _ := svc.CreateMasterKey("master1")
	svc.ActivateMasterKey(key.ID)

	err := svc.RetireMasterKey(key.ID)
	assert.NoError(t, err)

	retired, _ := svc.GetMasterKey(key.ID)
	assert.True(t, retired.RetiredAt != nil)
	assert.False(t, retired.IsActive)
}

// DEK Lifecycle

func TestKeyService_CreateDEK(t *testing.T) {
	svc := newTestService(t)

	// Create a master key first
	mk, err := svc.CreateMasterKey("master1")
	require.NoError(t, err)

	// Activate it so it can be used
	err = svc.ActivateMasterKey(mk.ID)
	require.NoError(t, err)

	dek, err := svc.CreateDEK("dek1", mk.ID)
	require.NoError(t, err)
	assert.NotEmpty(t, dek.ID)
	assert.Equal(t, "dek1", dek.Name)
	assert.Equal(t, mk.ID, dek.MasterKeyID)
	assert.Equal(t, "AES-256-GCM", dek.Algorithm)
	assert.NotEmpty(t, dek.EncryptedKey)
	assert.NotNil(t, dek.CreatedAt)
	assert.Nil(t, dek.RetiredAt)
}

func TestKeyService_CreateDEK_MasterKeyNotFound(t *testing.T) {
	svc := newTestService(t)

	_, err := svc.CreateDEK("dek1", "nonexistent-mk")
	assert.Error(t, err)
}

func TestKeyService_GetDEK(t *testing.T) {
	svc := newTestService(t)

	mk, err := svc.CreateMasterKey("master1")
	require.NoError(t, err)
	err = svc.ActivateMasterKey(mk.ID)
	require.NoError(t, err)

	dek, err := svc.CreateDEK("dek1", mk.ID)
	require.NoError(t, err)

	retrieved, err := svc.GetDEK(dek.ID)
	require.NoError(t, err)
	assert.Equal(t, dek.ID, retrieved.ID)
	assert.Equal(t, dek.Name, retrieved.Name)
	assert.Equal(t, dek.EncryptedKey, retrieved.EncryptedKey)
}

func TestKeyService_GetDEK_NotFound(t *testing.T) {
	svc := newTestService(t)

	_, err := svc.GetDEK("nonexistent")
	assert.Error(t, err)
}

// Encryption & Decryption

func TestKeyService_EncryptDecrypt_WithActiveMasterKey(t *testing.T) {
	svc := newTestService(t)

	// Create and activate master key
	mk, err := svc.CreateMasterKey("master1")
	require.NoError(t, err)
	err = svc.ActivateMasterKey(mk.ID)
	require.NoError(t, err)

	plaintext := "secret message"
	req := &entity.EncryptRequest{
		Plaintext: base64.StdEncoding.EncodeToString([]byte(plaintext)),
	}

	userID := "user123"
	ipAddr := "127.0.0.1"

	encResp, err := svc.Encrypt(req, userID, ipAddr)
	require.NoError(t, err)
	assert.NotEmpty(t, encResp.Ciphertext)
	assert.NotEmpty(t, encResp.IV)
	assert.NotEmpty(t, encResp.Tag)
	assert.Equal(t, "AES-256-GCM", encResp.Algorithm)

	decReq := &entity.DecryptRequest{
		Ciphertext: encResp.Ciphertext,
		IV:         encResp.IV,
		Tag:        encResp.Tag,
	}

	decResp, err := svc.Decrypt(decReq, userID, ipAddr)
	require.NoError(t, err)
	assert.Equal(t, plaintext, decResp.Plaintext)
}

func TestKeyService_EncryptDecrypt_WithDEK(t *testing.T) {
	svc := newTestService(t)

	// Create master key and activate
	mk, err := svc.CreateMasterKey("master1")
	require.NoError(t, err)
	err = svc.ActivateMasterKey(mk.ID)
	require.NoError(t, err)

	// Create DEK
	_, err = svc.CreateDEK("dek1", mk.ID)
	require.NoError(t, err)

	plaintext := "DEK encrypted data"
	req := &entity.EncryptRequest{
		Plaintext: plaintext,
		KeyName:   "dek1",
	}

	userID := "user456"
	ipAddr := "192.168.1.1"

	encResp, err := svc.Encrypt(req, userID, ipAddr)
	require.NoError(t, err)
	assert.NotEmpty(t, encResp.Ciphertext)

	decReq := &entity.DecryptRequest{
		Ciphertext: encResp.Ciphertext,
		IV:         encResp.IV,
		Tag:        encResp.Tag,
		KeyName:    "dek1",
	}

	decResp, err := svc.Decrypt(decReq, userID, ipAddr)
	require.NoError(t, err)
	assert.Equal(t, plaintext, decResp.Plaintext)
}

func TestKeyService_Encrypt_DEKNotFound(t *testing.T) {
	svc := newTestService(t)

	// Create master key and activate
	mk, err := svc.CreateMasterKey("master1")
	require.NoError(t, err)
	err = svc.ActivateMasterKey(mk.ID)
	require.NoError(t, err)

	req := &entity.EncryptRequest{
		Plaintext: "test",
		KeyName:   "nonexistent-dek",
	}

	_, err = svc.Encrypt(req, "user1", "127.0.0.1")
	assert.Error(t, err)
}

func TestKeyService_Decrypt_InvalidCiphertext(t *testing.T) {
	svc := newTestService(t)

	// Create master key and activate
	mk, err := svc.CreateMasterKey("master1")
	require.NoError(t, err)
	err = svc.ActivateMasterKey(mk.ID)
	require.NoError(t, err)

	decReq := &entity.DecryptRequest{
		Ciphertext: "invalid",
		IV:         "invalid",
		Tag:        "invalid",
	}

	_, err = svc.Decrypt(decReq, "user1", "127.0.0.1")
	assert.Error(t, err)
}

// RSA Operations

func TestKeyService_EncryptRSA_DecryptRSA(t *testing.T) {
	svc := newTestService(t)

	// Generate key pair
	priv, pub, err := svc.GenerateRSAKeyPair()
	require.NoError(t, err)
	var _ *rsa.PrivateKey = priv
	var _ *rsa.PublicKey = pub
	assert.NotNil(t, priv)
	assert.NotNil(t, pub)

	plaintext := []byte("rsa secret")

	ciphertext, err := svc.EncryptRSA(plaintext, pub)
	require.NoError(t, err)
	assert.NotEmpty(t, ciphertext)

	decrypted, err := svc.DecryptRSA(ciphertext, priv)
	require.NoError(t, err)
	assert.Equal(t, plaintext, decrypted)
}

func TestKeyService_Sign_Verify(t *testing.T) {
	svc := newTestService(t)

	priv, pub, err := svc.GenerateRSAKeyPair()
	require.NoError(t, err)
	var _ *rsa.PrivateKey = priv
	var _ *rsa.PublicKey = pub

	data := []byte("data to sign")

	sig, err := svc.Sign(data, priv)
	require.NoError(t, err)
	assert.NotEmpty(t, sig)

	valid := svc.Verify(data, sig, pub)
	assert.True(t, valid)

	// Tamper with data
	tampered := []byte("tampered data")
	valid = svc.Verify(tampered, sig, pub)
	assert.False(t, valid)
}

func TestKeyService_GenerateRSAKeyPair(t *testing.T) {
	svc := newTestService(t)

	priv, pub, err := svc.GenerateRSAKeyPair()
	require.NoError(t, err)
	var _ *rsa.PrivateKey = priv
	var _ *rsa.PublicKey = pub
	assert.NotNil(t, priv)
	assert.NotNil(t, pub)
	assert.Equal(t, 2048, priv.N.BitLen())
}

func TestKeyService_ExportImport_RSAPrivateKey(t *testing.T) {
	svc := newTestService(t)

	priv, _, err := svc.GenerateRSAKeyPair()
	require.NoError(t, err)
	var _ *rsa.PrivateKey = priv

	exported := svc.ExportRSAPrivateKey(priv)
	assert.NotEmpty(t, exported)

	imported, err := svc.ImportRSAPrivateKey(exported)
	require.NoError(t, err)
	var _ *rsa.PrivateKey = imported
	assert.Equal(t, priv.D, imported.D)
	assert.Equal(t, priv.N, imported.N)
}

func TestKeyService_ExportImport_RSAPublicKey(t *testing.T) {
	svc := newTestService(t)

	_, pub, err := svc.GenerateRSAKeyPair()
	require.NoError(t, err)
	var _ *rsa.PublicKey = pub

	exported := svc.ExportRSAPublicKey(pub)
	assert.NotEmpty(t, exported)

	imported, err := svc.ImportRSAPublicKey(exported)
	require.NoError(t, err)
	var _ *rsa.PublicKey = imported
	assert.Equal(t, pub.N, imported.N)
	assert.Equal(t, pub.E, imported.E)
}

// Audit Logs & Listing

func TestKeyService_GetAuditLogs(t *testing.T) {
	svc := newTestService(t)

	// Create some keys to generate audit logs
	mk, err := svc.CreateMasterKey("master1")
	require.NoError(t, err)
	err = svc.ActivateMasterKey(mk.ID)
	require.NoError(t, err)
	_, err = svc.CreateDEK("dek1", mk.ID)
	require.NoError(t, err)

	logs, err := svc.GetAuditLogs(mk.ID, 10)
	require.NoError(t, err)
	assert.NotEmpty(t, logs)

	// Verify logs contain expected operations
	foundCreate := false
	foundActivate := false
	for _, log := range logs {
		if log.KeyID == mk.ID {
			if log.Operation == "CREATE" && log.KeyType == "MASTER_KEY" {
				foundCreate = true
			}
			if log.Operation == "ACTIVATE" && log.KeyType == "MASTER_KEY" {
				foundActivate = true
			}
		}
	}
	assert.True(t, foundCreate, "CREATE audit log not found")
	assert.True(t, foundActivate, "ACTIVATE audit log not found")
}

func TestKeyService_GetAuditLogs_Empty(t *testing.T) {
	svc := newTestService(t)

	logs, err := svc.GetAuditLogs("nonexistent", 10)
	require.NoError(t, err)
	assert.Empty(t, logs)
}

func TestKeyService_ListMasterKeys(t *testing.T) {
	svc := newTestService(t)

	// Initially empty
	keys, err := svc.ListMasterKeys()
	require.NoError(t, err)
	assert.Empty(t, keys)

	// Create a couple of keys
	svc.CreateMasterKey("master1")
	svc.CreateMasterKey("master2")

	keys, err = svc.ListMasterKeys()
	require.NoError(t, err)
	assert.Len(t, keys, 2)
}

// Additional edge cases for existing methods

func TestKeyService_RotateMasterKey_NotFound(t *testing.T) {
	svc := newTestService(t)

	_, err := svc.RotateMasterKey("nonexistent")
	assert.Error(t, err)
}

func TestKeyService_ActivateMasterKey_NotFound(t *testing.T) {
	svc := newTestService(t)

	err := svc.ActivateMasterKey("nonexistent")
	assert.Error(t, err)
}

func TestKeyService_RetireMasterKey_NotFound(t *testing.T) {
	svc := newTestService(t)

	err := svc.RetireMasterKey("nonexistent")
	assert.Error(t, err)
}

