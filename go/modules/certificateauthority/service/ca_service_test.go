package service

import (
	"path/filepath"
	"testing"

	"github.com/stretchr/testify/assert"

	"community.local/modules/certificateauthority/entity"
)

func TestCAService_CreateRootCA(t *testing.T) {
	dir := t.TempDir()
	dataFile := filepath.Join(dir, "ca_data.json")

	svc, err := NewCAService(dataFile)
	assert.NoError(t, err)

	req := &entity.CreateCARequest{
		CommonName: "Test Root CA",
		Name:       "TestCA",
		PathLength: 0,
	}

	ca, err := svc.CreateRootCA(req)
	assert.NoError(t, err)
	assert.NotEmpty(t, ca.ID)

	// We'll check the fields known: ID, Name, CertPEM, PrivateKeyPEM, IsRoot.
	assert.Equal(t, "TestCA", ca.Name)
	assert.True(t, ca.IsRoot)
	assert.NotNil(t, ca.NotBefore)
	assert.NotNil(t, ca.NotAfter)
	assert.Contains(t, ca.CertPEM, "-----BEGIN CERTIFICATE-----")
	assert.Contains(t, ca.PrivateKeyPEM, "-----BEGIN RSA PRIVATE KEY-----")
}

func TestCAService_GetCA(t *testing.T) {
	dir := t.TempDir()
	dataFile := filepath.Join(dir, "ca_data.json")

	svc, _ := NewCAService(dataFile)

	req := &entity.CreateCARequest{
		CommonName: "My CA",
		Name:       "MyCA",
		PathLength: 1,
	}

	ca, _ := svc.CreateRootCA(req)

	retrieved, err := svc.GetCA(ca.ID)
	assert.NoError(t, err)
	assert.Equal(t, ca.ID, retrieved.ID)
	assert.Equal(t, ca.Name, retrieved.Name)
	assert.True(t, retrieved.IsRoot)
}

func TestCAService_GetCA_NotFound(t *testing.T) {
	dir := t.TempDir()
	dataFile := filepath.Join(dir, "ca_data.json")
	svc, _ := NewCAService(dataFile)

	_, err := svc.GetCA("nonexistent")
	assert.Error(t, err)
	assert.Contains(t, err.Error(), "CA not found")
}

// Additional tests could cover CreateIntermediateCA, CreateCSR, SignCSR if needed.
