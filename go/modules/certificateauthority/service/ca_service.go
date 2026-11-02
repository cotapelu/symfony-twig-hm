package service

import (
	"crypto/rand"
	"crypto/rsa"
	"crypto/x509"
	"crypto/x509/pkix"
	"encoding/json"
	"encoding/pem"
	"fmt"
	"math/big"
	"os"
	"path/filepath"
	"strings"
	"sync"
	"time"

	"community.local/modules/certificateauthority/entity"
)

type CAService struct {
	mu                     sync.RWMutex
	dataDir                string
	certificates           map[string]entity.Certificate
	certificateAuthorities map[string]entity.CertificateAuthority
	csrs                   map[string]entity.CertificateSigningRequest
	nextCertID             int
	nextCSRID              int
}

func NewCAService(dataDir string) (*CAService, error) {
	s := &CAService{
		dataDir:                dataDir,
		certificates:           make(map[string]entity.Certificate),
		certificateAuthorities: make(map[string]entity.CertificateAuthority),
		csrs:                   make(map[string]entity.CertificateSigningRequest),
		nextCertID:             1,
		nextCSRID:              1,
	}

	s.ensureDataDirectory()
	s.restoreFromFile()

	return s, nil
}

func (s *CAService) ensureDataDirectory() {
	dir := filepath.Dir(s.dataDir)
	if dir != "" && dir != "." {
		os.MkdirAll(dir, 0755)
	}
}

func (s *CAService) restoreFromFile() {
	data, err := os.ReadFile(s.dataDir)
	if err != nil {
		if os.IsNotExist(err) {
			return
		}
		return
	}

	var saved struct {
		Certificates map[string]entity.Certificate               `json:"certificates"`
		CAs          map[string]entity.CertificateAuthority      `json:"cas"`
		CSRs         map[string]entity.CertificateSigningRequest `json:"csrs"`
		NextCertID   int                                         `json:"next_cert_id"`
		NextCSRID    int                                         `json:"next_csrid"`
	}

	if err := json.Unmarshal(data, &saved); err != nil {
		return
	}

	s.certificates = saved.Certificates
	s.certificateAuthorities = saved.CAs
	s.csrs = saved.CSRs
	s.nextCertID = saved.NextCertID
	s.nextCSRID = saved.NextCSRID
}

func (s *CAService) persistToFile() {
	data, err := json.MarshalIndent(map[string]interface{}{
		"certificates": s.certificates,
		"cas":          s.certificateAuthorities,
		"csrs":         s.csrs,
		"next_cert_id": s.nextCertID,
		"next_csrid":   s.nextCSRID,
	}, "", "  ")
	if err != nil {
		return
	}
	os.WriteFile(s.dataDir, data, 0644)
}

func (s *CAService) CreateRootCA(req *entity.CreateCARequest) (*entity.CertificateAuthority, error) {
	s.mu.Lock()
	defer s.mu.Unlock()

	privateKey, err := rsa.GenerateKey(rand.Reader, 4096)
	if err != nil {
		return nil, fmt.Errorf("failed to generate private key: %v", err)
	}

	template := &x509.Certificate{
		SerialNumber: big.NewInt(int64(s.nextCertID)),
		Subject: pkix.Name{
			CommonName:   req.CommonName,
			Organization: []string{req.Name},
		},
		NotBefore:             time.Now(),
		NotAfter:              time.Now().Add(10 * 365 * 24 * time.Hour),
		KeyUsage:              x509.KeyUsageCertSign | x509.KeyUsageCRLSign,
		BasicConstraintsValid: true,
		IsCA:                  true,
		MaxPathLen:            req.PathLength,
		SubjectKeyId:          []byte(fmt.Sprintf("%d", s.nextCertID)),
	}

	certDER, err := x509.CreateCertificate(rand.Reader, template, template, &privateKey.PublicKey, privateKey)
	if err != nil {
		return nil, fmt.Errorf("failed to create certificate: %v", err)
	}

	certPEM := pem.EncodeToMemory(&pem.Block{
		Type:  "CERTIFICATE",
		Bytes: certDER,
	})

	privateKeyPEM := pem.EncodeToMemory(&pem.Block{
		Type:  "RSA PRIVATE KEY",
		Bytes: x509.MarshalPKCS1PrivateKey(privateKey),
	})

	ca := &entity.CertificateAuthority{
		ID:               fmt.Sprintf("ca_%d", s.nextCertID),
		Name:             req.Name,
		CertPEM:          string(certPEM),
		PrivateKeyPEM:    string(privateKeyPEM),
		IsRoot:           true,
		NotBefore:        template.NotBefore,
		NotAfter:         template.NotAfter,
		KeyUsage:         "Certificate Sign, CRL Sign",
		PathLength:       req.PathLength,
		ChildrenCount:    0,
		IssuedCertsCount: 0,
		CreatedAt:        time.Now(),
	}

	s.certificateAuthorities[ca.ID] = *ca
	s.nextCertID++

	s.persistToFile()
	return ca, nil
}

func (s *CAService) GetCA(id string) (*entity.CertificateAuthority, error) {
	s.mu.RLock()
	defer s.mu.RUnlock()

	ca, exists := s.certificateAuthorities[id]
	if !exists {
		return nil, fmt.Errorf("CA not found")
	}
	return &ca, nil
}

func (s *CAService) CreateCSR(req *entity.SignCertificateRequest, requestedBy string) (*entity.CertificateSigningRequest, error) {
	s.mu.Lock()
	defer s.mu.Unlock()

	csr := &entity.CertificateSigningRequest{
		ID:              fmt.Sprintf("csr_%d", s.nextCSRID),
		CommonName:      req.CommonName,
		SubjectAltNames: req.SubjectAltNames,
		CSRPEM:          req.CSRPEM,
		KeyUsage:        req.KeyUsage,
		ExtKeyUsage:     req.ExtKeyUsage,
		RequestedBy:     requestedBy,
		CAID:            req.CAID,
		Status:          "pending",
		CreatedAt:       time.Now(),
	}

	s.csrs[csr.ID] = *csr
	s.nextCSRID++

	s.persistToFile()
	return csr, nil
}

func (s *CAService) SignCSR(csrID string) (*entity.IssueCertificateResponse, error) {
	s.mu.Lock()
	defer s.mu.Unlock()

	csr, exists := s.csrs[csrID]
	if !exists {
		return nil, fmt.Errorf("CSR not found")
	}

	if csr.Status != "pending" {
		return nil, fmt.Errorf("CSR is not pending")
	}

	ca, caExists := s.certificateAuthorities[csr.CAID]
	if !caExists {
		return nil, fmt.Errorf("CA not found")
	}

	caKey, err := s.parsePrivateKey(ca.PrivateKeyPEM)
	if err != nil {
		return nil, fmt.Errorf("failed to parse CA private key: %v", err)
	}

	caCert, err := s.parseCertificate(ca.CertPEM)
	if err != nil {
		return nil, fmt.Errorf("failed to parse CA certificate: %v", err)
	}

	csrBlock, _ := pem.Decode([]byte(csr.CSRPEM))
	if csrBlock == nil {
		return nil, fmt.Errorf("failed to decode CSR")
	}

	csrX509, err := x509.ParseCertificateRequest(csrBlock.Bytes)
	if err != nil {
		return nil, fmt.Errorf("failed to parse CSR: %v", err)
	}

	validityDays := 365
	for _, ext := range csr.ExtKeyUsage {
		if strings.Contains(strings.ToLower(ext), "server") {
			validityDays = 90
			break
		}
	}

	template := &x509.Certificate{
		SerialNumber:          big.NewInt(int64(s.nextCertID)),
		Subject:               csrX509.Subject,
		NotBefore:             time.Now(),
		NotAfter:              time.Now().Add(time.Duration(validityDays) * 24 * time.Hour),
		KeyUsage:              x509.KeyUsageDigitalSignature | x509.KeyUsageKeyEncipherment,
		ExtKeyUsage:           s.parseExtKeyUsage(csr.ExtKeyUsage),
		BasicConstraintsValid: true,
		IsCA:                  false,
	}

	certDER, err := x509.CreateCertificate(rand.Reader, template, caCert, csrX509.PublicKey, caKey)
	if err != nil {
		return nil, fmt.Errorf("failed to create certificate: %v", err)
	}

	certPEM := pem.EncodeToMemory(&pem.Block{
		Type:  "CERTIFICATE",
		Bytes: certDER,
	})

	privateKey, err := rsa.GenerateKey(rand.Reader, 2048)
	if err != nil {
		return nil, fmt.Errorf("failed to generate private key: %v", err)
	}

	privateKeyPEM := pem.EncodeToMemory(&pem.Block{
		Type:  "RSA PRIVATE KEY",
		Bytes: x509.MarshalPKCS1PrivateKey(privateKey),
	})

	cert := &entity.Certificate{
		ID:              fmt.Sprintf("cert_%d", s.nextCertID),
		CommonName:      csr.CommonName,
		SubjectAltNames: csr.SubjectAltNames,
		SerialNumber:    fmt.Sprintf("%d", s.nextCertID),
		CertPEM:         string(certPEM),
		PrivateKeyPEM:   string(privateKeyPEM),
		IssuerID:        ca.ID,
		CAID:            csr.CAID,
		NotBefore:       template.NotBefore,
		NotAfter:        template.NotAfter,
		IsCA:            false,
		IsRevoked:       false,
		KeyUsage:        csr.KeyUsage,
		CreatedAt:       time.Now(),
	}

	s.certificates[cert.ID] = *cert

	now := time.Now()
	csr.Status = "issued"
	csr.CertID = cert.ID
	csr.ProcessedAt = &now
	s.csrs[csrID] = csr

	ca.IssuedCertsCount++
	s.certificateAuthorities[ca.ID] = ca

	s.nextCertID++
	s.persistToFile()

	return &entity.IssueCertificateResponse{
		CertificateID: cert.ID,
		CertPEM:       cert.CertPEM,
		PrivateKeyPEM: cert.PrivateKeyPEM,
		NotBefore:     cert.NotBefore,
		NotAfter:      cert.NotAfter,
	}, nil
}

func (s *CAService) RevokeCertificate(certID, reason string) error {
	s.mu.Lock()
	defer s.mu.Unlock()

	cert, exists := s.certificates[certID]
	if !exists {
		return fmt.Errorf("certificate not found")
	}

	if cert.IsRevoked {
		return fmt.Errorf("certificate already revoked")
	}

	now := time.Now()
	cert.IsRevoked = true
	cert.RevokedAt = &now
	cert.RevocationReason = reason
	s.certificates[certID] = cert

	s.persistToFile()
	return nil
}

func (s *CAService) GetCertificate(certID string) (*entity.Certificate, error) {
	s.mu.RLock()
	defer s.mu.RUnlock()

	cert, exists := s.certificates[certID]
	if !exists {
		return nil, fmt.Errorf("certificate not found")
	}
	return &cert, nil
}

func (s *CAService) ListCertificates() ([]entity.Certificate, error) {
	s.mu.RLock()
	defer s.mu.RUnlock()

	certs := make([]entity.Certificate, 0, len(s.certificates))
	for _, cert := range s.certificates {
		certs = append(certs, cert)
	}
	return certs, nil
}

func (s *CAService) GetCSR(csrID string) (*entity.CertificateSigningRequest, error) {
	s.mu.RLock()
	defer s.mu.RUnlock()

	csr, exists := s.csrs[csrID]
	if !exists {
		return nil, fmt.Errorf("CSR not found")
	}
	return &csr, nil
}

func (s *CAService) ListCSRs() ([]entity.CertificateSigningRequest, error) {
	s.mu.RLock()
	defer s.mu.RUnlock()

	csrs := make([]entity.CertificateSigningRequest, 0, len(s.csrs))
	for _, csr := range s.csrs {
		csrs = append(csrs, csr)
	}
	return csrs, nil
}

func (s *CAService) parsePrivateKey(pemData string) (*rsa.PrivateKey, error) {
	block, _ := pem.Decode([]byte(pemData))
	if block == nil {
		return nil, fmt.Errorf("failed to decode PEM block")
	}
	return x509.ParsePKCS1PrivateKey(block.Bytes)
}

func (s *CAService) parseCertificate(pemData string) (*x509.Certificate, error) {
	block, _ := pem.Decode([]byte(pemData))
	if block == nil {
		return nil, fmt.Errorf("failed to decode PEM block")
	}
	return x509.ParseCertificate(block.Bytes)
}

func (s *CAService) parseExtKeyUsage(extKeyUsage []string) []x509.ExtKeyUsage {
	var result []x509.ExtKeyUsage
	for _, ext := range extKeyUsage {
		switch strings.ToLower(ext) {
		case "serverauth":
			result = append(result, x509.ExtKeyUsageServerAuth)
		case "clientauth":
			result = append(result, x509.ExtKeyUsageClientAuth)
		case "codesigning":
			result = append(result, x509.ExtKeyUsageCodeSigning)
		case "emailprotection":
			result = append(result, x509.ExtKeyUsageEmailProtection)
		case "timestamping":
			result = append(result, x509.ExtKeyUsageTimeStamping)
		}
	}
	if len(result) == 0 {
		result = append(result, x509.ExtKeyUsageServerAuth, x509.ExtKeyUsageClientAuth)
	}
	return result
}

func (s *CAService) GenerateKeyPair() (privateKeyPEM, publicKeyPEM string, err error) {
	privateKey, err := rsa.GenerateKey(rand.Reader, 2048)
	if err != nil {
		return "", "", fmt.Errorf("failed to generate key pair: %v", err)
	}

	privateKeyPEM = string(pem.EncodeToMemory(&pem.Block{
		Type:  "RSA PRIVATE KEY",
		Bytes: x509.MarshalPKCS1PrivateKey(privateKey),
	}))

	publicKeyPEM = string(pem.EncodeToMemory(&pem.Block{
		Type:  "PUBLIC KEY",
		Bytes: x509.MarshalPKCS1PublicKey(&privateKey.PublicKey),
	}))

	return privateKeyPEM, publicKeyPEM, nil
}

func (s *CAService) GenerateCSR(commonName string, privateKeyPEM string) (string, error) {
	block, _ := pem.Decode([]byte(privateKeyPEM))
	if block == nil {
		return "", fmt.Errorf("failed to decode private key")
	}

	privateKey, err := x509.ParsePKCS1PrivateKey(block.Bytes)
	if err != nil {
		return "", fmt.Errorf("failed to parse private key: %v", err)
	}

	template := &x509.CertificateRequest{
		Subject: pkix.Name{
			CommonName: commonName,
		},
	}

	csrDER, err := x509.CreateCertificateRequest(rand.Reader, template, privateKey)
	if err != nil {
		return "", fmt.Errorf("failed to create CSR: %v", err)
	}

	csrPEM := pem.EncodeToMemory(&pem.Block{
		Type:  "CERTIFICATE REQUEST",
		Bytes: csrDER,
	})

	return string(csrPEM), nil
}
