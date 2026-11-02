//go:build ignore

package certificateauthority

import (
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"os"
	"strings"
	"testing"

	"github.com/gin-gonic/gin"
	"github.com/stretchr/testify/assert"

	ch "community.local/modules/certificateauthority/handler"
	cm "community.local/modules/certificateauthority/service"
)

func getTestCAService() *cm.CAService {
	tmpDir := os.TempDir()
	service, err := cm.NewCAService(tmpDir + "/test_ca_data.json")
	if err != nil {
		panic("Failed to create CA service: " + err.Error())
	}
	return service
}

func TestCAService_CreateAndGetCA(t *testing.T) {
	caService := getTestCAService()
	handler := ch.NewCAHandler(caService)

	r := gin.New()
	// Test auth middleware: simulate authenticated admin user
	r.Use(func(c *gin.Context) {
		c.Set("user_id", "test_admin")
		c.Set("user_roles", []string{"ROLE_ADMIN"})
	})
	handler.RegisterRoutes(r)

	var caID string
	var csrID string

	// Test creating a root CA
	t.Run("CreateRootCA", func(t *testing.T) {
		body := `{"common_name":"Test Root CA","name":"Test Root CA Organization","path_length":0}`
		req := httptest.NewRequest("POST", "/api/ca/root", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		w := httptest.NewRecorder()
		r.ServeHTTP(w, req)
		assert.Equal(t, http.StatusCreated, w.Code)

		var resp struct {
			ID            string `json:"id"`
			Name          string `json:"name"`
			CertPEM       string `json:"cert_pem"`
			PrivateKeyPEM string `json:"private_key_pem"`
			IsRoot        bool   `json:"is_root"`
			NotBefore     string `json:"not_before"`
			NotAfter      string `json:"not_after"`
		}
		if err := json.Unmarshal(w.Body.Bytes(), &resp); err != nil {
			t.Fatalf("failed to parse response: %v", err)
		}
		assert.NotEmpty(t, resp.ID)
		assert.Equal(t, "Test Root CA Organization", resp.Name)
		assert.True(t, resp.IsRoot)
		assert.NotEmpty(t, resp.CertPEM)
		assert.NotEmpty(t, resp.PrivateKeyPEM)
		caID = resp.ID
	})

	// Test getting the CA by ID
	t.Run("GetCA", func(t *testing.T) {
		getReq := httptest.NewRequest("GET", "/api/ca/"+caID, nil)
		getW := httptest.NewRecorder()
		r.ServeHTTP(getW, getReq)
		assert.Equal(t, http.StatusOK, getW.Code)

		var getResp struct {
			ID            string `json:"id"`
			Name          string `json:"name"`
			CertPEM       string `json:"cert_pem"`
			PrivateKeyPEM string `json:"private_key_pem"`
			IsRoot        bool   `json:"is_root"`
			NotBefore     string `json:"not_before"`
			NotAfter      string `json:"not_after"`
		}
		if err := json.Unmarshal(getW.Body.Bytes(), &getResp); err != nil {
			t.Fatalf("failed to parse get response: %v", err)
		}
		assert.Equal(t, caID, getResp.ID)
		assert.Equal(t, "Test Root CA Organization", getResp.Name)
		assert.True(t, getResp.IsRoot)
	})

	// Test creating a CSR
	t.Run("CreateCSR", func(t *testing.T) {
		body := `{"common_name":"test.example.com","subject_alt_names":["example.com","www.example.com"],"key_usage":"digital_signature,key_encipherment","ext_key_usage":["server_auth","client_auth"],"ca_id":"` + caID + `"}`
		req := httptest.NewRequest("POST", "/api/ca/csr", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		w := httptest.NewRecorder()
		r.ServeHTTP(w, req)
		assert.Equal(t, http.StatusCreated, w.Code)

		var resp struct {
			ID              string   `json:"id"`
			CommonName      string   `json:"common_name"`
			SubjectAltNames []string `json:"subject_alt_names"`
			CSRPEM          string   `json:"csr_pem"`
			KeyUsage        string   `json:"key_usage"`
			RequestedBy     string   `json:"requested_by"`
			CAID            string   `json:"ca_id"`
			Status          string   `json:"status"`
			CreatedAt       string   `json:"created_at"`
		}
		if err := json.Unmarshal(w.Body.Bytes(), &resp); err != nil {
			t.Fatalf("failed to parse response: %v", err)
		}
		assert.NotEmpty(t, resp.ID)
		assert.Equal(t, "test.example.com", resp.CommonName)
		assert.Contains(t, resp.SubjectAltNames, "example.com")
		assert.Equal(t, "user123", resp.RequestedBy)
		assert.Equal(t, caID, resp.CAID)
		assert.Equal(t, "pending", resp.Status)
		csrID = resp.ID
	})

	// Test signing the CSR
	t.Run("SignCSR", func(t *testing.T) {
		signReq := httptest.NewRequest("POST", "/api/ca/csr/"+csrID+"/sign", nil)
		signW := httptest.NewRecorder()
		r.ServeHTTP(signW, signReq)
		assert.Equal(t, http.StatusOK, signW.Code)

		var signResp struct {
			CertificateID string `json:"cert_id"` // Fixed: changed from certificate_id to cert_id to match service response
			CertPEM       string `json:"cert_pem"`
			PrivateKeyPEM string `json:"private_key_pem"`
			NotBefore     string `json:"not_before"`
			NotAfter      string `json:"not_after"`
		}
		if err := json.Unmarshal(signW.Body.Bytes(), &signResp); err != nil {
			t.Fatalf("failed to parse sign response: %v", err)
		}
		assert.NotEmpty(t, signResp.CertificateID)
		assert.NotEmpty(t, signResp.CertPEM)
		assert.NotEmpty(t, signResp.PrivateKeyPEM)

		// Test getting the signed certificate
		t.Run("GetCertificate", func(t *testing.T) {
			getCertReq := httptest.NewRequest("GET", "/api/ca/cert/"+signResp.CertificateID, nil)
			getCertW := httptest.NewRecorder()
			r.ServeHTTP(getCertW, getCertReq)
			assert.Equal(t, http.StatusOK, getCertW.Code)

			var getCertResp struct {
				ID              string   `json:"id"`
				CommonName      string   `json:"common_name"`
				SubjectAltNames []string `json:"subject_alt_names"`
				SerialNumber    string   `json:"serial_number"`
				CertPEM         string   `json:"cert_pem"`
				PrivateKeyPEM   string   `json:"private_key_pem"`
				IssuerID        string   `json:"issuer_id"`
				CAID            string   `json:"ca_id"`
				NotBefore       string   `json:"not_before"`
				NotAfter        string   `json:"not_after"`
				IsCA            bool     `json:"is_ca"`
				IsRevoked       bool     `json:"is_revoked"`
				CreatedAt       string   `json:"created_at"`
			}
			if err := json.Unmarshal(getCertW.Body.Bytes(), &getCertResp); err != nil {
				t.Fatalf("failed to parse get certificate response: %v", err)
			}
			assert.Equal(t, signResp.CertificateID, getCertResp.ID)
			assert.Equal(t, "test.example.com", getCertResp.CommonName)
			assert.Contains(t, getCertResp.SubjectAltNames, "example.com")
			assert.False(t, getCertResp.IsCA)
			assert.False(t, getCertResp.IsRevoked)
		})
	})

	// Test listing certificates
	t.Run("ListCertificates", func(t *testing.T) {
		listReq := httptest.NewRequest("GET", "/api/ca/certs", nil)
		listW := httptest.NewRecorder()
		r.ServeHTTP(listW, listReq)
		assert.Equal(t, http.StatusOK, listW.Code)

		var listResp []struct {
			ID string `json:"id"`
		}
		if err := json.Unmarshal(listW.Body.Bytes(), &listResp); err != nil {
			t.Fatalf("failed to parse list response: %v", err)
		}
		assert.GreaterOrEqual(t, len(listResp), 1)
	})

	// Test listing CSRs
	t.Run("ListCSRs", func(t *testing.T) {
		listCSRReq := httptest.NewRequest("GET", "/api/ca/csrs", nil)
		listCSRW := httptest.NewRecorder()
		r.ServeHTTP(listCSRW, listCSRReq)
		assert.Equal(t, http.StatusOK, listCSRW.Code)

		var listCSRResp []struct {
			ID string `json:"id"`
		}
		if err := json.Unmarshal(listCSRW.Body.Bytes(), &listCSRResp); err != nil {
			t.Fatalf("failed to parse list CSRs response: %v", err)
		}
		assert.GreaterOrEqual(t, len(listCSRResp), 1)
	})

	// Test generating key pair
	t.Run("GenerateKeyPair", func(t *testing.T) {
		keyReq := httptest.NewRequest("POST", "/api/ca/keys/generate", nil)
		keyW := httptest.NewRecorder()
		r.ServeHTTP(keyW, keyReq)
		assert.Equal(t, http.StatusOK, keyW.Code)

		var keyResp struct {
			PrivateKeyPEM string `json:"private_key"`
			PublicKeyPEM  string `json:"public_key"`
		}
		if err := json.Unmarshal(keyW.Body.Bytes(), &keyResp); err != nil {
			t.Fatalf("failed to parse key pair response: %v", err)
		}
		assert.NotEmpty(t, keyResp.PrivateKeyPEM)
		assert.NotEmpty(t, keyResp.PublicKeyPEM)
	})

	// Test generating CSR
	t.Run("GenerateCSR", func(t *testing.T) {
		// First generate a key pair
		keyReq := httptest.NewRequest("POST", "/api/ca/keys/generate", nil)
		keyW := httptest.NewRecorder()
		r.ServeHTTP(keyW, keyReq)
		assert.Equal(t, http.StatusOK, keyW.Code)

		var keyResp struct {
			PrivateKeyPEM string `json:"private_key"`
			PublicKeyPEM  string `json:"public_key"`
		}
		if err := json.Unmarshal(keyW.Body.Bytes(), &keyResp); err != nil {
			t.Fatalf("failed to parse key pair response: %v", err)
		}
		assert.NotEmpty(t, keyResp.PrivateKeyPEM)
		assert.NotEmpty(t, keyResp.PublicKeyPEM)

		// Now generate CSR using the private key
		body := `{"common_name":"generated.test.com","private_key_pem":"` + keyResp.PrivateKeyPEM + `"}`
		csrReq := httptest.NewRequest("POST", "/api/ca/csr/generate", strings.NewReader(body))
		csrReq.Header.Set("Content-Type", "application/json")
		csrW := httptest.NewRecorder()
		r.ServeHTTP(csrW, csrReq)
		assert.Equal(t, http.StatusOK, csrW.Code)

		var csrResp struct {
			CSRPEM string `json:"csr_pem"`
		}
		if err := json.Unmarshal(csrW.Body.Bytes(), &csrResp); err != nil {
			t.Fatalf("failed to parse CSR generation response: %v", err)
		}
		assert.NotEmpty(t, csrResp.CSRPEM)
		assert.Contains(t, csrResp.CSRPEM, "BEGIN CERTIFICATE REQUEST")
	})

	// Test getting the CA by ID
	t.Run("GetCA", func(t *testing.T) {
		getReq := httptest.NewRequest("GET", "/api/ca/"+caID, nil)
		getW := httptest.NewRecorder()
		r.ServeHTTP(getW, getReq)
		assert.Equal(t, http.StatusOK, getW.Code)

		var getResp struct {
			ID            string `json:"id"`
			Name          string `json:"name"`
			CertPEM       string `json:"cert_pem"`
			PrivateKeyPEM string `json:"private_key_pem"`
			IsRoot        bool   `json:"is_root"`
			NotBefore     string `json:"not_before"`
			NotAfter      string `json:"not_after"`
		}
		if err := json.Unmarshal(getW.Body.Bytes(), &getResp); err != nil {
			t.Fatalf("failed to parse get response: %v", err)
		}
		assert.Equal(t, caID, getResp.ID)
		assert.Equal(t, "Test Root CA Organization", getResp.Name)
		assert.True(t, getResp.IsRoot)
	})

	// Test creating a CSR
	t.Run("CreateCSR", func(t *testing.T) {
		body := `{"common_name":"test.example.com","subject_alt_names":["example.com","www.example.com"],"key_usage":"digital_signature,key_encipherment","ext_key_usage":["server_auth","client_auth"],"ca_id":"` + caID + `"}`
		req := httptest.NewRequest("POST", "/api/ca/csr", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		w := httptest.NewRecorder()
		r.ServeHTTP(w, req)
		assert.Equal(t, http.StatusCreated, w.Code)

		var resp struct {
			ID              string   `json:"id"`
			CommonName      string   `json:"common_name"`
			SubjectAltNames []string `json:"subject_alt_names"`
			CSRPEM          string   `json:"csr_pem"`
			KeyUsage        string   `json:"key_usage"`
			RequestedBy     string   `json:"requested_by"`
			CAID            string   `json:"ca_id"`
			Status          string   `json:"status"`
			CreatedAt       string   `json:"created_at"`
		}
		if err := json.Unmarshal(w.Body.Bytes(), &resp); err != nil {
			t.Fatalf("failed to parse response: %v", err)
		}
		assert.NotEmpty(t, resp.ID)
		assert.Equal(t, "test.example.com", resp.CommonName)
		assert.Contains(t, resp.SubjectAltNames, "example.com")
		assert.Equal(t, "user123", resp.RequestedBy)
		assert.Equal(t, caID, resp.CAID)
		assert.Equal(t, "pending", resp.Status)
		csrID = resp.ID
	})

	// Test signing the CSR
	t.Run("SignCSR", func(t *testing.T) {
		signReq := httptest.NewRequest("POST", "/api/ca/csr/"+csrID+"/sign", nil)
		signW := httptest.NewRecorder()
		r.ServeHTTP(signW, signReq)
		assert.Equal(t, http.StatusOK, signW.Code)

		var signResp struct {
			CertificateID string `json:"certificate_id"`
			CertPEM       string `json:"cert_pem"`
			PrivateKeyPEM string `json:"private_key_pem"`
			NotBefore     string `json:"not_before"`
			NotAfter      string `json:"not_after"`
		}
		if err := json.Unmarshal(signW.Body.Bytes(), &signResp); err != nil {
			t.Fatalf("failed to parse sign response: %v", err)
		}
		assert.NotEmpty(t, signResp.CertificateID)
		assert.NotEmpty(t, signResp.CertPEM)
		assert.NotEmpty(t, signResp.PrivateKeyPEM)

		// Test getting the signed certificate
		t.Run("GetCertificate", func(t *testing.T) {
			getCertReq := httptest.NewRequest("GET", "/api/ca/cert/"+signResp.CertificateID, nil)
			getCertW := httptest.NewRecorder()
			r.ServeHTTP(getCertW, getCertReq)
			assert.Equal(t, http.StatusOK, getCertW.Code)

			var getCertResp struct {
				ID              string   `json:"id"`
				CommonName      string   `json:"common_name"`
				SubjectAltNames []string `json:"subject_alt_names"`
				SerialNumber    string   `json:"serial_number"`
				CertPEM         string   `json:"cert_pem"`
				PrivateKeyPEM   string   `json:"private_key_pem"`
				IssuerID        string   `json:"issuer_id"`
				CAID            string   `json:"ca_id"`
				NotBefore       string   `json:"not_before"`
				NotAfter        string   `json:"not_after"`
				IsCA            bool     `json:"is_ca"`
				IsRevoked       bool     `json:"is_revoked"`
				CreatedAt       string   `json:"created_at"`
			}
			if err := json.Unmarshal(getCertW.Body.Bytes(), &getCertResp); err != nil {
				t.Fatalf("failed to parse get certificate response: %v", err)
			}
			assert.Equal(t, signResp.CertificateID, getCertResp.ID)
			assert.Equal(t, "test.example.com", getCertResp.CommonName)
			assert.Contains(t, getCertResp.SubjectAltNames, "example.com")
			assert.False(t, getCertResp.IsCA)
			assert.False(t, getCertResp.IsRevoked)
		})
	})

	// Test listing certificates
	t.Run("ListCertificates", func(t *testing.T) {
		listReq := httptest.NewRequest("GET", "/api/ca/certs", nil)
		listW := httptest.NewRecorder()
		r.ServeHTTP(listW, listReq)
		assert.Equal(t, http.StatusOK, listW.Code)

		var listResp []struct {
			ID string `json:"id"`
		}
		if err := json.Unmarshal(listW.Body.Bytes(), &listResp); err != nil {
			t.Fatalf("failed to parse list response: %v", err)
		}
		assert.GreaterOrEqual(t, len(listResp), 1)
	})

	// Test listing CSRs
	t.Run("ListCSRs", func(t *testing.T) {
		listCSRReq := httptest.NewRequest("GET", "/api/ca/csrs", nil)
		listCSRW := httptest.NewRecorder()
		r.ServeHTTP(listCSRW, listCSRReq)
		assert.Equal(t, http.StatusOK, listCSRW.Code)

		var listCSRResp []struct {
			ID string `json:"id"`
		}
		if err := json.Unmarshal(listCSRW.Body.Bytes(), &listCSRResp); err != nil {
			t.Fatalf("failed to parse list CSRs response: %v", err)
		}
		assert.GreaterOrEqual(t, len(listCSRResp), 1)
	})

	// Test generating key pair
	t.Run("GenerateKeyPair", func(t *testing.T) {
		keyReq := httptest.NewRequest("POST", "/api/ca/keys/generate", nil)
		keyW := httptest.NewRecorder()
		r.ServeHTTP(keyW, keyReq)
		assert.Equal(t, http.StatusOK, keyW.Code)

		var keyResp struct {
			PrivateKeyPEM string `json:"private_key"`
			PublicKeyPEM  string `json:"public_key"`
		}
		if err := json.Unmarshal(keyW.Body.Bytes(), &keyResp); err != nil {
			t.Fatalf("failed to parse key pair response: %v", err)
		}
		assert.NotEmpty(t, keyResp.PrivateKeyPEM)
		assert.NotEmpty(t, keyResp.PublicKeyPEM)
	})

	// Test generating CSR
	t.Run("GenerateCSR", func(t *testing.T) {
		// First generate a key pair
		keyReq := httptest.NewRequest("POST", "/api/ca/keys/generate", nil)
		keyW := httptest.NewRecorder()
		r.ServeHTTP(keyW, keyReq)
		assert.Equal(t, http.StatusOK, keyW.Code)

		var keyResp struct {
			PrivateKeyPEM string `json:"private_key"`
			PublicKeyPEM  string `json:"public_key"`
		}
		if err := json.Unmarshal(keyW.Body.Bytes(), &keyResp); err != nil {
			t.Fatalf("failed to parse key pair response: %v", err)
		}
		assert.NotEmpty(t, keyResp.PrivateKeyPEM)
		assert.NotEmpty(t, keyResp.PublicKeyPEM)

		// Now generate CSR using the private key
		body := `{"common_name":"generated.test.com","private_key_pem":"` + keyResp.PrivateKeyPEM + `"}`
		csrReq := httptest.NewRequest("POST", "/api/ca/csr/generate", strings.NewReader(body))
		csrReq.Header.Set("Content-Type", "application/json")
		csrW := httptest.NewRecorder()
		r.ServeHTTP(csrW, csrReq)
		assert.Equal(t, http.StatusOK, csrW.Code)

		var csrResp struct {
			CSRPEM string `json:"csr_pem"`
		}
		if err := json.Unmarshal(csrW.Body.Bytes(), &csrResp); err != nil {
			t.Fatalf("failed to parse CSR generation response: %v", err)
		}
		assert.NotEmpty(t, csrResp.CSRPEM)
		assert.Contains(t, csrResp.CSRPEM, "BEGIN CERTIFICATE REQUEST")
	})
}
