package service

import (
	"crypto/rsa"
	"crypto/x509"
	"testing"
	"time"

	"community.local/modules/certificateauthority/entity"

	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"
)

// tempDataFile creates a temporary file path for CA data.
func tempDataFile(t *testing.T) string {
	t.Helper()
	dir := t.TempDir()
	return dir + "/ca_data.json"
}

// createTestCA creates a test CA and returns it.
func createTestCA(t *testing.T, svc *CAService) *entity.CertificateAuthority {
	req := &entity.CreateCARequest{
		CommonName: "Test CA",
		Name:       "TestCA",
		PathLength: 1,
	}
	ca, err := svc.CreateRootCA(req)
	require.NoError(t, err)
	return ca
}

// createTestCSR creates a CSR linked to the given CA and returns it.
func createTestCSR(t *testing.T, svc *CAService, ca *entity.CertificateAuthority) *entity.CertificateSigningRequest {
	privPEM, _, err := svc.GenerateKeyPair()
	require.NoError(t, err)

	csrPEM, err := svc.GenerateCSR("Test CN", privPEM)
	require.NoError(t, err)

	req := &entity.SignCertificateRequest{
		CAID:            ca.ID,
		CommonName:      "Test CN",
		CSRPEM:          csrPEM,
		KeyUsage:        "digitalSignature",
		ExtKeyUsage:     []string{"serverAuth"},
		SubjectAltNames: []string{"DNS:example.com"},
	}
	csr, err := svc.CreateCSR(req, "user1")
	require.NoError(t, err)
	return csr
}

// TestCAService_CreateCSR tests creating a certificate signing request.
func TestCAService_CreateCSR(t *testing.T) {
	dataFile := tempDataFile(t)
	svc, _ := NewCAService(dataFile)

	ca := createTestCA(t, svc)
	csr := createTestCSR(t, svc, ca)

	assert.Equal(t, ca.ID, csr.CAID)
	assert.Equal(t, "Test CN", csr.CommonName)
	assert.Equal(t, "pending", csr.Status)
	assert.NotEmpty(t, csr.ID)
}

// TestCAService_GetCSR tests retrieving a CSR by ID.
func TestCAService_GetCSR(t *testing.T) {
	dataFile := tempDataFile(t)
	svc, _ := NewCAService(dataFile)

	ca := createTestCA(t, svc)
	csr := createTestCSR(t, svc, ca)

	retrieved, err := svc.GetCSR(csr.ID)
	assert.NoError(t, err)
	assert.Equal(t, csr.ID, retrieved.ID)
	assert.Equal(t, csr.CommonName, retrieved.CommonName)
}

// TestCAService_GetCSR_NotFound tests error when CSR does not exist.
func TestCAService_GetCSR_NotFound(t *testing.T) {
	dataFile := tempDataFile(t)
	svc, _ := NewCAService(dataFile)

	_, err := svc.GetCSR("nonexistent")
	assert.Error(t, err)
	assert.Contains(t, err.Error(), "CSR not found")
}

// TestCAService_ListCSRs tests listing CSRs (empty and populated).
func TestCAService_ListCSRs(t *testing.T) {
	dataFile := tempDataFile(t)
	svc, _ := NewCAService(dataFile)

	// Empty list
	list, err := svc.ListCSRs()
	assert.NoError(t, err)
	assert.Empty(t, list)

	// Create a CA and CSR
	ca := createTestCA(t, svc)
	csr := createTestCSR(t, svc, ca)

	list, err = svc.ListCSRs()
	assert.NoError(t, err)
	assert.Len(t, list, 1)
	assert.Equal(t, csr.ID, list[0].ID)
}

// TestCAService_SignCSR_happyPath tests signing a CSR with serverAuth (short validity).
func TestCAService_SignCSR_happyPath(t *testing.T) {
	dataFile := tempDataFile(t)
	svc, _ := NewCAService(dataFile)

	ca := createTestCA(t, svc)
	csr := createTestCSR(t, svc, ca)

	// Sign the CSR
	resp, err := svc.SignCSR(csr.ID)
	require.NoError(t, err)
	assert.NotEmpty(t, resp.CertificateID)
	assert.Contains(t, resp.CertPEM, "-----BEGIN CERTIFICATE-----")
	assert.Contains(t, resp.PrivateKeyPEM, "-----BEGIN RSA PRIVATE KEY-----")

	// Verify certificate can be retrieved
	cert, err := svc.GetCertificate(resp.CertificateID)
	assert.NoError(t, err)
	assert.Equal(t, csr.CommonName, cert.CommonName)
	assert.False(t, cert.IsRevoked)
}

// TestCAService_SignCSR_defaultValidity tests signing without ExtKeyUsage triggers default (365d).
func TestCAService_SignCSR_defaultValidity(t *testing.T) {
	dataFile := tempDataFile(t)
	svc, _ := NewCAService(dataFile)

	ca := createTestCA(t, svc)

	// Create a CSR with no ExtKeyUsage
	privPEM, _, _ := svc.GenerateKeyPair()
	csrPEM, _ := svc.GenerateCSR("CN Default", privPEM)
	req := &entity.SignCertificateRequest{
		CAID:      ca.ID,
		CommonName: "CN Default",
		CSRPEM:    csrPEM,
		KeyUsage:  "digitalSignature",
	}
	csr, _ := svc.CreateCSR(req, "user1")

	resp, err := svc.SignCSR(csr.ID)
	require.NoError(t, err)

	// Retrieve the issued certificate using the ID from the response
	cert, err := svc.GetCertificate(resp.CertificateID)
	assert.NoError(t, err)
	// Check validity is around 365 days
	diff := cert.NotAfter.Sub(cert.NotBefore)
	assert.True(t, diff > 360*24*time.Hour && diff < 370*24*time.Hour)
}

// TestCAService_RevokeCertificate tests revoking an issued certificate.
func TestCAService_RevokeCertificate(t *testing.T) {
	dataFile := tempDataFile(t)
	svc, _ := NewCAService(dataFile)

	ca := createTestCA(t, svc)
	csr := createTestCSR(t, svc, ca)
	resp, _ := svc.SignCSR(csr.ID)
	certID := resp.CertificateID

	// Revoke
	err := svc.RevokeCertificate(certID, "testing")
	assert.NoError(t, err)

	cert, _ := svc.GetCertificate(certID)
	assert.True(t, cert.IsRevoked)
	assert.NotNil(t, cert.RevokedAt)
	assert.Equal(t, "testing", cert.RevocationReason)
}

// TestCAService_RevokeCertificate_errors tests error cases: not found and already revoked.
func TestCAService_RevokeCertificate_errors(t *testing.T) {
	dataFile := tempDataFile(t)
	svc, _ := NewCAService(dataFile)

	// Not found
	err := svc.RevokeCertificate("nonexistent", "reason")
	assert.Error(t, err)
	assert.Contains(t, err.Error(), "certificate not found")

	// Already revoked
	ca := createTestCA(t, svc)
	csr := createTestCSR(t, svc, ca)
	resp, _ := svc.SignCSR(csr.ID)
	certID := resp.CertificateID

	// First revoke
	assert.NoError(t, svc.RevokeCertificate(certID, "first"))

	// Second revoke should error
	err = svc.RevokeCertificate(certID, "second")
	assert.Error(t, err)
	assert.Contains(t, err.Error(), "already revoked")
}

// TestCAService_GetCertificate_NotFound tests error when certificate not found.
func TestCAService_GetCertificate_NotFound(t *testing.T) {
	dataFile := tempDataFile(t)
	svc, _ := NewCAService(dataFile)

	_, err := svc.GetCertificate("nonexistent")
	assert.Error(t, err)
	assert.Contains(t, err.Error(), "certificate not found")
}

// TestCAService_ListCertificates tests listing certificates (empty then after issue).
func TestCAService_ListCertificates(t *testing.T) {
	dataFile := tempDataFile(t)
	svc, _ := NewCAService(dataFile)

	// Empty list
	list, err := svc.ListCertificates()
	assert.NoError(t, err)
	assert.Empty(t, list)

	// Issue a certificate
	ca := createTestCA(t, svc)
	csr := createTestCSR(t, svc, ca)
	svc.SignCSR(csr.ID)

	list, err = svc.ListCertificates()
	assert.NoError(t, err)
	assert.Len(t, list, 1)
	assert.Equal(t, csr.CommonName, list[0].CommonName)
}

// TestCAService_GenerateKeyPair tests generating an RSA key pair.
func TestCAService_GenerateKeyPair(t *testing.T) {
	dataFile := tempDataFile(t)
	svc, _ := NewCAService(dataFile)

	privPEM, pubPEM, err := svc.GenerateKeyPair()
	assert.NoError(t, err)
	assert.Contains(t, privPEM, "-----BEGIN RSA PRIVATE KEY-----")
	assert.Contains(t, pubPEM, "-----BEGIN PUBLIC KEY-----")
}

// TestCAService_GenerateCSR tests creating a CSR from a private key.
func TestCAService_GenerateCSR(t *testing.T) {
	dataFile := tempDataFile(t)
	svc, _ := NewCAService(dataFile)

	privPEM, _, err := svc.GenerateKeyPair()
	require.NoError(t, err)

	csrPEM, err := svc.GenerateCSR("example.com", privPEM)
	assert.NoError(t, err)
	assert.Contains(t, csrPEM, "-----BEGIN CERTIFICATE REQUEST-----")
}

// TestCAService_GenerateCSR_invalidKey tests error when private key is invalid.
func TestCAService_GenerateCSR_invalidKey(t *testing.T) {
	dataFile := tempDataFile(t)
	svc, _ := NewCAService(dataFile)

	csrPEM, err := svc.GenerateCSR("example.com", "not-a-key")
	assert.Error(t, err)
	assert.Empty(t, csrPEM)
}

// TestCAService_parsePrivateKey tests parsing of PEM private keys (valid/invalid).
func TestCAService_parsePrivateKey(t *testing.T) {
	dataFile := tempDataFile(t)
	svc, _ := NewCAService(dataFile)

	// Generate valid PEM
	privPEM, _, err := svc.GenerateKeyPair()
	require.NoError(t, err)

	key, err := svc.parsePrivateKey(privPEM)
	assert.NoError(t, err)
	assert.IsType(t, &rsa.PrivateKey{}, key)

	// Invalid PEM
	_, err = svc.parsePrivateKey("invalid")
	assert.Error(t, err)
}

// TestCAService_parseCertificate tests parsing of PEM certificates.
func TestCAService_parseCertificate(t *testing.T) {
	dataFile := tempDataFile(t)
	svc, _ := NewCAService(dataFile)

	// Create CA to get a certificate PEM
	ca := createTestCA(t, svc)

	cert, err := svc.parseCertificate(ca.CertPEM)
	assert.NoError(t, err)
	// The CA certificate's Subject.CommonName should be the CommonName from the CreateCARequest
	expectedCommonName := "Test CA"
	assert.Equal(t, expectedCommonName, cert.Subject.CommonName)

	// Invalid PEM
	_, err = svc.parseCertificate("invalid")
	assert.Error(t, err)
}

// TestCAService_parseExtKeyUsage tests mapping of string ext key usage to x509 values.
func TestCAService_parseExtKeyUsage(t *testing.T) {
	dataFile := tempDataFile(t)
	svc, _ := NewCAService(dataFile)

	tests := []struct {
		input    []string
		expected []x509.ExtKeyUsage
	}{
		{[]string{"serverAuth"}, []x509.ExtKeyUsage{x509.ExtKeyUsageServerAuth}},
		{[]string{"clientAuth"}, []x509.ExtKeyUsage{x509.ExtKeyUsageClientAuth}},
		{[]string{"codeSigning"}, []x509.ExtKeyUsage{x509.ExtKeyUsageCodeSigning}},
		{[]string{"emailProtection"}, []x509.ExtKeyUsage{x509.ExtKeyUsageEmailProtection}},
		{[]string{"timestamping"}, []x509.ExtKeyUsage{x509.ExtKeyUsageTimeStamping}},
		{[]string{}, nil}, // empty yields default (server+client)
		{[]string{"unknown"}, nil}, // unknown yields default (since no matches)
	}

	for _, tt := range tests {
		result := svc.parseExtKeyUsage(tt.input)
		if len(tt.expected) == 0 {
			// default includes both server and client auth
			assert.Contains(t, result, x509.ExtKeyUsageServerAuth)
			assert.Contains(t, result, x509.ExtKeyUsageClientAuth)
		} else {
			assert.Equal(t, tt.expected, result)
		}
	}
}
