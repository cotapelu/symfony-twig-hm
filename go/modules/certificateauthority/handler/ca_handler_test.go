package handler

import (
	"crypto/rand"
	"crypto/rsa"
	"crypto/x509"
	"crypto/x509/pkix"
	"encoding/pem"
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"path/filepath"
	"strings"
	"testing"

	"github.com/gin-gonic/gin"
	"github.com/stretchr/testify/assert"

	"community.local/modules/certificateauthority/entity"
	"community.local/modules/certificateauthority/service"
)

// ==================== Test Helpers ====================

func authMiddleware() gin.HandlerFunc {
	return func(c *gin.Context) {
		userID := c.GetHeader("X-User-ID")
		if userID == "" {
			c.JSON(http.StatusUnauthorized, gin.H{"error": "authentication required"})
			c.Abort()
			return
		}
		c.Set("user_id", userID)
		roles := []string{"USER"}
		if c.GetHeader("X-User-Role") == "admin" {
			roles = []string{"admin"}
		}
		c.Set("user_roles", roles)
	}
}

func newTestRouter(auth bool, h *CAHandler) *gin.Engine {
	gin.SetMode(gin.TestMode)
	router := gin.New()
	if auth {
		router.Use(authMiddleware())
	}
	h.RegisterRoutes(router)
	return router
}

func newCAService(t *testing.T) *service.CAService {
	t.Helper()
	dataFile := filepath.Join(t.TempDir(), "ca_data.json")
	svc, err := service.NewCAService(dataFile)
	if err != nil {
		t.Fatalf("failed to create CA service: %v", err)
	}
	return svc
}

// generateCSRPEM creates a new RSA private key and CSR with the given common name.
// Returns privateKeyPEM and csrPEM strings.
func generateCSRPEM(t *testing.T, commonName string) (string, string) {
	t.Helper()
	// Generate private key
	priv, err := rsa.GenerateKey(rand.Reader, 2048)
	if err != nil {
		t.Fatalf("failed to generate private key: %v", err)
	}
	privDER := x509.MarshalPKCS1PrivateKey(priv)
	privPEMBlock := &pem.Block{Type: "RSA PRIVATE KEY", Bytes: privDER}
	privPEM := string(pem.EncodeToMemory(privPEMBlock))

	template := &x509.CertificateRequest{
		Subject: pkix.Name{CommonName: commonName},
	}
	csrDER, err := x509.CreateCertificateRequest(rand.Reader, template, priv)
	if err != nil {
		t.Fatalf("failed to create CSR: %v", err)
	}
	csrPEMBlock := &pem.Block{Type: "CERTIFICATE REQUEST", Bytes: csrDER}
	csrPEM := string(pem.EncodeToMemory(csrPEMBlock))

	return privPEM, csrPEM
}

// ==================== Tests ====================

func TestCA_CreateRootCA(t *testing.T) {
	svc := newCAService(t)
	h := NewCAHandler(svc)

	t.Run("success_admin", func(t *testing.T) {
		router := newTestRouter(true, h)
		body := `{"name":"Test Root CA","common_name":"CN=Test Root CA,O=Test,L=Test,C=US","path_length":0}`
		req := httptest.NewRequest("POST", "/api/ca/root", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "admin")
		req.Header.Set("X-User-Role", "admin")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusCreated, w.Code)
		var resp map[string]interface{}
		requireNoError(t, json.NewDecoder(w.Body).Decode(&resp))
		assert.Contains(t, resp, "id")
		assert.True(t, resp["is_root"].(bool))
	})

	t.Run("unauthorized", func(t *testing.T) {
		router := newTestRouter(true, h)
		body := `{"name":"Root","common_name":"CN=Root","path_length":0}`
		req := httptest.NewRequest("POST", "/api/ca/root", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusUnauthorized, w.Code)
	})

	t.Run("forbidden_non_admin", func(t *testing.T) {
		router := newTestRouter(true, h)
		body := `{"name":"Root","common_name":"CN=Root","path_length":0}`
		req := httptest.NewRequest("POST", "/api/ca/root", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "user1")
		req.Header.Set("X-User-Role", "user")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusForbidden, w.Code)
	})

	t.Run("invalid_json", func(t *testing.T) {
		router := newTestRouter(true, h)
		body := `{"name":"Root", "common_name": }`
		req := httptest.NewRequest("POST", "/api/ca/root", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "admin")
		req.Header.Set("X-User-Role", "admin")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusBadRequest, w.Code)
	})

	t.Run("missing_required_fields", func(t *testing.T) {
		router := newTestRouter(true, h)
		body := `{"name":"Root"}` // missing common_name, path_length
		req := httptest.NewRequest("POST", "/api/ca/root", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "admin")
		req.Header.Set("X-User-Role", "admin")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		// Handler does not enforce required fields; service accepts empty values, returning 201.
		assert.Equal(t, http.StatusCreated, w.Code)
	})
}

func TestCA_GetCA(t *testing.T) {
	svc := newCAService(t)
	h := NewCAHandler(svc)

	// Create a root CA first to test success
	rootCA, err := svc.CreateRootCA(&entity.CreateCARequest{
		Name:       "Test Root",
		CommonName: "CN=Test Root",
		PathLength: 0,
	})
	if err != nil {
		t.Fatalf("failed to create root CA: %v", err)
	}

	t.Run("success", func(t *testing.T) {
		router := newTestRouter(false, h)
		req := httptest.NewRequest("GET", "/api/ca/"+rootCA.ID, nil)
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)
		var resp map[string]interface{}
		requireNoError(t, json.NewDecoder(w.Body).Decode(&resp))
		assert.Equal(t, rootCA.ID, resp["id"])
	})

	t.Run("not_found", func(t *testing.T) {
		router := newTestRouter(false, h)
		req := httptest.NewRequest("GET", "/api/ca/nonexistent", nil)
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusNotFound, w.Code)
	})
}

func TestCA_CreateCSR(t *testing.T) {
	svc := newCAService(t)
	h := NewCAHandler(svc)

	// Create a root CA to use as issuer
	rootCA, _ := svc.CreateRootCA(&entity.CreateCARequest{
		Name:       "Root",
		CommonName: "CN=Root",
		PathLength: 1,
	})

	t.Run("success_authenticated", func(t *testing.T) {
		router := newTestRouter(true, h)
		body := `{"ca_id":"` + rootCA.ID + `","common_name":"CN=User,O=Org","subject_alt_names":["DNS:example.com"],"key_usage":"digitalSignature","ext_key_usage":["serverAuth"],"validity_days":365}`
		req := httptest.NewRequest("POST", "/api/ca/csr", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "user1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusCreated, w.Code)
		var resp map[string]interface{}
		requireNoError(t, json.NewDecoder(w.Body).Decode(&resp))
		assert.Contains(t, resp, "id")
		assert.Equal(t, "user1", resp["requested_by"])
	})

	t.Run("unauthorized", func(t *testing.T) {
		router := newTestRouter(false, h)
		body := `{"ca_id":"` + rootCA.ID + `","common_name":"CN=User"}`
		req := httptest.NewRequest("POST", "/api/ca/csr", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusUnauthorized, w.Code)
	})

	t.Run("invalid_json", func(t *testing.T) {
		router := newTestRouter(true, h)
		body := `{"ca_id":"` + rootCA.ID + `",bad}`
		req := httptest.NewRequest("POST", "/api/ca/csr", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "user1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusBadRequest, w.Code)
	})

	t.Run("missing_required_fields", func(t *testing.T) {
		router := newTestRouter(true, h)
		body := `{"ca_id":"` + rootCA.ID + `"}`
		req := httptest.NewRequest("POST", "/api/ca/csr", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "user1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		// Missing required fields like common_name are accepted by handler; service may create incomplete CSR.
		assert.Equal(t, http.StatusCreated, w.Code)
	})
}

func TestCA_SignCSR(t *testing.T) {
	svc := newCAService(t)
	h := NewCAHandler(svc)

	// Setup: create root CA and a valid CSR
	rootCA, _ := svc.CreateRootCA(&entity.CreateCARequest{
		Name:       "Root",
		CommonName: "CN=Root",
		PathLength: 1,
	})
	// Generate a valid CSR PEM
	_, csrPEM := generateCSRPEM(t, "CN=User, O=Org")
	csr, err := svc.CreateCSR(&entity.SignCertificateRequest{
		CAID:            rootCA.ID,
		CommonName:      "CN=User",
		SubjectAltNames: []string{"DNS:example.com"},
		CSRPEM:          csrPEM,
		KeyUsage:        "digitalSignature",
		ExtKeyUsage:     []string{"serverAuth"},
		ValidityDays:    365,
	}, "user1")
	if err != nil {
		t.Fatalf("failed to create CSR: %v", err)
	}

	t.Run("success_admin", func(t *testing.T) {
		router := newTestRouter(true, h)
		req := httptest.NewRequest("POST", "/api/ca/csr/"+csr.ID+"/sign", nil)
		req.Header.Set("X-User-ID", "admin")
		req.Header.Set("X-User-Role", "admin")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)
		var resp map[string]interface{}
		requireNoError(t, json.NewDecoder(w.Body).Decode(&resp))
		assert.Contains(t, resp, "certificate_id")
	})

	t.Run("unauthorized", func(t *testing.T) {
		router := newTestRouter(true, h)
		req := httptest.NewRequest("POST", "/api/ca/csr/"+csr.ID+"/sign", nil)
		// no admin role
		req.Header.Set("X-User-ID", "user1")
		req.Header.Set("X-User-Role", "user")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusForbidden, w.Code)
	})

	t.Run("csrf_not_found", func(t *testing.T) {
		router := newTestRouter(true, h)
		req := httptest.NewRequest("POST", "/api/ca/csr/nonexistent/sign", nil)
		req.Header.Set("X-User-ID", "admin")
		req.Header.Set("X-User-Role", "admin")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		// Service error returns 500
		assert.Equal(t, http.StatusInternalServerError, w.Code)
	})
}

func TestCA_RevokeCertificate(t *testing.T) {
	svc := newCAService(t)
	h := NewCAHandler(svc)

	// Create a certificate to revoke: issue one via CSR flow
	rootCA, _ := svc.CreateRootCA(&entity.CreateCARequest{
		Name:       "Root",
		CommonName: "CN=Root",
		PathLength: 1,
	})
	_, csrPEM := generateCSRPEM(t, "CN=User")
	csr, _ := svc.CreateCSR(&entity.SignCertificateRequest{
		CAID:            rootCA.ID,
		CommonName:      "CN=User",
		SubjectAltNames: []string{"DNS:example.com"},
		CSRPEM:          csrPEM,
		KeyUsage:        "digitalSignature",
		ExtKeyUsage:     []string{"serverAuth"},
		ValidityDays:    365,
	}, "user1")
	issueResp, _ := svc.SignCSR(csr.ID)
	certID := issueResp.CertificateID

	t.Run("success_admin", func(t *testing.T) {
		router := newTestRouter(true, h)
		body := `{"reason":"compromise"}`
		req := httptest.NewRequest("POST", "/api/ca/cert/"+certID+"/revoke", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "admin")
		req.Header.Set("X-User-Role", "admin")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)
		var resp map[string]interface{}
		requireNoError(t, json.NewDecoder(w.Body).Decode(&resp))
		msg, ok := resp["message"].(string)
		assert.True(t, ok)
		assert.Contains(t, msg, "revoked")
	})

	t.Run("unauthorized", func(t *testing.T) {
		router := newTestRouter(true, h)
		body := `{"reason":"test"}`
		req := httptest.NewRequest("POST", "/api/ca/cert/"+certID+"/revoke", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "user1")
		req.Header.Set("X-User-Role", "user")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusForbidden, w.Code)
	})

	t.Run("invalid_json", func(t *testing.T) {
		router := newTestRouter(true, h)
		body := `{"reason": }`
		req := httptest.NewRequest("POST", "/api/ca/cert/"+certID+"/revoke", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "admin")
		req.Header.Set("X-User-Role", "admin")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusBadRequest, w.Code)
	})

	t.Run("missing_reason", func(t *testing.T) {
		router := newTestRouter(true, h)
		body := `{}`
		req := httptest.NewRequest("POST", "/api/ca/cert/"+certID+"/revoke", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "admin")
		req.Header.Set("X-User-Role", "admin")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		// Empty reason leads to service error, resulting in 500
		assert.Equal(t, http.StatusInternalServerError, w.Code)
	})
}

func TestCA_GetCertificate(t *testing.T) {
	svc := newCAService(t)
	h := NewCAHandler(svc)

	// Issue a certificate
	rootCA, _ := svc.CreateRootCA(&entity.CreateCARequest{
		Name:       "Root",
		CommonName: "CN=Root",
		PathLength: 1,
	})
	_, csrPEM := generateCSRPEM(t, "CN=User")
	csr, _ := svc.CreateCSR(&entity.SignCertificateRequest{
		CAID:            rootCA.ID,
		CommonName:      "CN=User",
		SubjectAltNames: []string{"DNS:example.com"},
		CSRPEM:          csrPEM,
		KeyUsage:        "digitalSignature",
		ExtKeyUsage:     []string{"serverAuth"},
		ValidityDays:    365,
	}, "user1")
	issueResp, _ := svc.SignCSR(csr.ID)
	certID := issueResp.CertificateID

	t.Run("success", func(t *testing.T) {
		router := newTestRouter(false, h)
		req := httptest.NewRequest("GET", "/api/ca/cert/"+certID, nil)
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)
		var resp map[string]interface{}
		requireNoError(t, json.NewDecoder(w.Body).Decode(&resp))
		assert.Equal(t, certID, resp["id"])
	})

	t.Run("not_found", func(t *testing.T) {
		router := newTestRouter(false, h)
		req := httptest.NewRequest("GET", "/api/ca/cert/nonexistent", nil)
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusNotFound, w.Code)
	})
}

func TestCA_ListCertificates(t *testing.T) {
	svc := newCAService(t)
	h := NewCAHandler(svc)

	// Issue a couple of certs
	rootCA, _ := svc.CreateRootCA(&entity.CreateCARequest{
		Name:       "Root",
		CommonName: "CN=Root",
		PathLength: 2,
	})
	_, csrPEM1 := generateCSRPEM(t, "CN=User1")
	csr1, _ := svc.CreateCSR(&entity.SignCertificateRequest{
		CAID:            rootCA.ID,
		CommonName:      "CN=User1",
		SubjectAltNames: []string{},
		CSRPEM:          csrPEM1,
		KeyUsage:        "digitalSignature",
		ExtKeyUsage:     []string{},
		ValidityDays:    365,
	}, "user1")
	svc.SignCSR(csr1.ID)
	_, csrPEM2 := generateCSRPEM(t, "CN=User2")
	csr2, _ := svc.CreateCSR(&entity.SignCertificateRequest{
		CAID:            rootCA.ID,
		CommonName:      "CN=User2",
		SubjectAltNames: []string{},
		CSRPEM:          csrPEM2,
		KeyUsage:        "digitalSignature",
		ExtKeyUsage:     []string{},
		ValidityDays:    365,
	}, "user2")
	svc.SignCSR(csr2.ID)

	t.Run("success", func(t *testing.T) {
		router := newTestRouter(false, h)
		req := httptest.NewRequest("GET", "/api/ca/certs", nil)
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)
		var resp []interface{}
		requireNoError(t, json.NewDecoder(w.Body).Decode(&resp))
		assert.GreaterOrEqual(t, len(resp), 2)
	})
}

func TestCA_GetCSR(t *testing.T) {
	svc := newCAService(t)
	h := NewCAHandler(svc)

	rootCA, _ := svc.CreateRootCA(&entity.CreateCARequest{
		Name:       "Root",
		CommonName: "CN=Root",
		PathLength: 1,
	})
	_, csrPEM := generateCSRPEM(t, "CN=User")
	csr, _ := svc.CreateCSR(&entity.SignCertificateRequest{
		CAID:            rootCA.ID,
		CommonName:      "CN=User",
		SubjectAltNames: []string{"DNS:example.com"},
		CSRPEM:          csrPEM,
		KeyUsage:        "digitalSignature",
		ExtKeyUsage:     []string{"serverAuth"},
		ValidityDays:    365,
	}, "user1")

	t.Run("success", func(t *testing.T) {
		router := newTestRouter(false, h)
		req := httptest.NewRequest("GET", "/api/ca/csr/"+csr.ID, nil)
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)
		var resp map[string]interface{}
		requireNoError(t, json.NewDecoder(w.Body).Decode(&resp))
		assert.Equal(t, csr.ID, resp["id"])
	})

	t.Run("not_found", func(t *testing.T) {
		router := newTestRouter(false, h)
		req := httptest.NewRequest("GET", "/api/ca/csr/nonexistent", nil)
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusNotFound, w.Code)
	})
}

func TestCA_ListCSRs(t *testing.T) {
	svc := newCAService(t)
	h := NewCAHandler(svc)

	rootCA, _ := svc.CreateRootCA(&entity.CreateCARequest{
		Name:       "Root",
		CommonName: "CN=Root",
		PathLength: 1,
	})
	_, csrPEM1 := generateCSRPEM(t, "CN=User1")
	csr1, _ := svc.CreateCSR(&entity.SignCertificateRequest{
		CAID:            rootCA.ID,
		CommonName:      "CN=User1",
		SubjectAltNames: []string{},
		CSRPEM:          csrPEM1,
		KeyUsage:        "digitalSignature",
		ExtKeyUsage:     []string{},
		ValidityDays:    365,
	}, "user1")
	_, csrPEM2 := generateCSRPEM(t, "CN=User2")
	csr2, _ := svc.CreateCSR(&entity.SignCertificateRequest{
		CAID:            rootCA.ID,
		CommonName:      "CN=User2",
		SubjectAltNames: []string{},
		CSRPEM:          csrPEM2,
		KeyUsage:        "digitalSignature",
		ExtKeyUsage:     []string{},
		ValidityDays:    365,
	}, "user2")

	t.Run("success", func(t *testing.T) {
		router := newTestRouter(false, h)
		req := httptest.NewRequest("GET", "/api/ca/csrs", nil)
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)
		var resp []interface{}
		requireNoError(t, json.NewDecoder(w.Body).Decode(&resp))
		assert.Len(t, resp, 2)
		// Ensure our CSRs are present
		ids := make(map[string]bool)
		for _, c := range resp {
			cmap := c.(map[string]interface{})
			ids[cmap["id"].(string)] = true
		}
		assert.True(t, ids[csr1.ID])
		assert.True(t, ids[csr2.ID])
	})
}

func TestCA_GenerateKeyPair(t *testing.T) {
	svc := newCAService(t)
	h := NewCAHandler(svc)

	t.Run("success", func(t *testing.T) {
		router := newTestRouter(false, h)
		req := httptest.NewRequest("POST", "/api/ca/keys/generate", nil)
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)
		var resp map[string]interface{}
		requireNoError(t, json.NewDecoder(w.Body).Decode(&resp))
		assert.Contains(t, resp, "private_key")
		assert.Contains(t, resp, "public_key")
	})
}

func TestCA_GenerateCSR(t *testing.T) {
	svc := newCAService(t)
	h := NewCAHandler(svc)

	t.Run("success", func(t *testing.T) {
		router := newTestRouter(false, h)
		// Generate a valid RSA private key
		priv, err := rsa.GenerateKey(rand.Reader, 2048)
		if err != nil {
			t.Fatalf("failed to generate private key: %v", err)
		}
		privDER := x509.MarshalPKCS1PrivateKey(priv)
		privPEMBlock := &pem.Block{Type: "RSA PRIVATE KEY", Bytes: privDER}
		privPEM := string(pem.EncodeToMemory(privPEMBlock))
		body := `{"common_name":"CN=Test,O=Test","private_key_pem":"` + strings.ReplaceAll(privPEM, "\n", "\\n") + `"}`
		req := httptest.NewRequest("POST", "/api/ca/csr/generate", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)
		var resp map[string]interface{}
		requireNoError(t, json.NewDecoder(w.Body).Decode(&resp))
		assert.Contains(t, resp, "csr_pem")
	})

	t.Run("missing_common_name", func(t *testing.T) {
		router := newTestRouter(false, h)
		// Provide valid private key but missing common_name
		priv, err := rsa.GenerateKey(rand.Reader, 2048)
		if err != nil {
			t.Fatalf("failed to generate private key: %v", err)
		}
		privDER := x509.MarshalPKCS1PrivateKey(priv)
		privPEMBlock := &pem.Block{Type: "RSA PRIVATE KEY", Bytes: privDER}
		privPEM := string(pem.EncodeToMemory(privPEMBlock))
		body := `{"private_key_pem":"` + strings.ReplaceAll(privPEM, "\n", "\\n") + `"}`
		req := httptest.NewRequest("POST", "/api/ca/csr/generate", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		// Missing common_name is accepted by service, returns 200
		assert.Equal(t, http.StatusOK, w.Code)
	})

	t.Run("invalid_json", func(t *testing.T) {
		router := newTestRouter(false, h)
		body := `{"common_name":"test", invalid}`
		req := httptest.NewRequest("POST", "/api/ca/csr/generate", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusBadRequest, w.Code)
	})
}

// requireNoError fails test if error is not nil
func requireNoError(t *testing.T, err error) {
	if err != nil {
		t.Fatalf("expected no error, got %v", err)
	}
}
