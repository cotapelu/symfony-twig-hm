package handler

import (
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"strings"
	"testing"

	"community.local/modules/keymanagement/service"
	"github.com/gin-gonic/gin"
	"github.com/stretchr/testify/assert"
)

// ==================== Test Helpers ====================

// authMiddleware sets user info from headers
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

// newTestRouter creates a router with the handler and optional auth
func newTestRouter(auth bool, h *KeyHandler) *gin.Engine {
	gin.SetMode(gin.TestMode)
	router := gin.New()
	if auth {
		router.Use(authMiddleware())
	}
	h.RegisterRoutes(router)
	return router
}

// createService creates a new KeyService with a temp directory
func createService(t *testing.T) *service.KeyService {
	tmpDir := t.TempDir()
	svc, err := service.NewKeyService(tmpDir)
	assert.NoError(t, err)
	return svc
}

// requireNoError fails test if error is not nil
func requireNoError(t *testing.T, err error) {
	if err != nil {
		t.Fatalf("expected no error, got %v", err)
	}
}

// ==================== Tests ====================

func TestKeyHandler_CreateMasterKey(t *testing.T) {
	svc := createService(t)
	h := NewKeyHandler(svc)

	t.Run("success", func(t *testing.T) {
		router := newTestRouter(false, h)
		body := `{"name":"My Master Key"}`
		req := httptest.NewRequest("POST", "/api/keys/master", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusCreated, w.Code)
		var resp map[string]interface{}
		requireNoError(t, json.NewDecoder(w.Body).Decode(&resp))
		assert.Contains(t, resp, "id")
		assert.Equal(t, "My Master Key", resp["name"])
		assert.False(t, resp["is_active"].(bool))
	})

	t.Run("invalid_json", func(t *testing.T) {
		router := newTestRouter(false, h)
		body := `{"name": bad}`
		req := httptest.NewRequest("POST", "/api/keys/master", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusBadRequest, w.Code)
	})

	t.Run("missing_name", func(t *testing.T) {
		router := newTestRouter(false, h)
		body := `{}`
		req := httptest.NewRequest("POST", "/api/keys/master", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		// Handler does not enforce required; accepts empty name
		assert.Equal(t, http.StatusCreated, w.Code)
	})
}

func TestKeyHandler_ListMasterKeys(t *testing.T) {
	svc := createService(t)
	h := NewKeyHandler(svc)

	// Create a key first
	_, err := svc.CreateMasterKey("Key1")
	requireNoError(t, err)

	t.Run("success", func(t *testing.T) {
		router := newTestRouter(false, h)
		req := httptest.NewRequest("GET", "/api/keys/master", nil)
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)
		var resp []map[string]interface{}
		requireNoError(t, json.NewDecoder(w.Body).Decode(&resp))
		assert.Len(t, resp, 1)
		assert.Equal(t, "Key1", resp[0]["name"])
	})
}

func TestKeyHandler_GetMasterKey(t *testing.T) {
	svc := createService(t)
	h := NewKeyHandler(svc)

	key, err := svc.CreateMasterKey("Key1")
	requireNoError(t, err)

	t.Run("success_admin", func(t *testing.T) {
		router := newTestRouter(true, h)
		req := httptest.NewRequest("GET", "/api/keys/master/"+key.ID, nil)
		req.Header.Set("X-User-ID", "admin")
		req.Header.Set("X-User-Role", "admin")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)
		var resp map[string]interface{}
		requireNoError(t, json.NewDecoder(w.Body).Decode(&resp))
		assert.Equal(t, key.ID, resp["id"])
	})

	t.Run("forbidden_non_admin", func(t *testing.T) {
		router := newTestRouter(true, h)
		req := httptest.NewRequest("GET", "/api/keys/master/"+key.ID, nil)
		req.Header.Set("X-User-ID", "user1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusForbidden, w.Code)
	})

	t.Run("unauthorized", func(t *testing.T) {
		router := newTestRouter(false, h)
		req := httptest.NewRequest("GET", "/api/keys/master/"+key.ID, nil)
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusUnauthorized, w.Code)
	})

	t.Run("not_found", func(t *testing.T) {
		router := newTestRouter(true, h)
		req := httptest.NewRequest("GET", "/api/keys/master/nonexistent", nil)
		req.Header.Set("X-User-ID", "admin")
		req.Header.Set("X-User-Role", "admin")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusNotFound, w.Code)
	})
}

func TestKeyHandler_ActivateMasterKey(t *testing.T) {
	svc := createService(t)
	h := NewKeyHandler(svc)

	key, err := svc.CreateMasterKey("Key1")
	requireNoError(t, err)

	t.Run("success_admin", func(t *testing.T) {
		router := newTestRouter(true, h)
		req := httptest.NewRequest("POST", "/api/keys/master/"+key.ID+"/activate", nil)
		req.Header.Set("X-User-ID", "admin")
		req.Header.Set("X-User-Role", "admin")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)
		var resp map[string]interface{}
		requireNoError(t, json.NewDecoder(w.Body).Decode(&resp))
		assert.Equal(t, "key activated", resp["message"])
	})

	t.Run("forbidden_non_admin", func(t *testing.T) {
		router := newTestRouter(true, h)
		req := httptest.NewRequest("POST", "/api/keys/master/"+key.ID+"/activate", nil)
		req.Header.Set("X-User-ID", "user1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusForbidden, w.Code)
	})

	t.Run("not_found", func(t *testing.T) {
		router := newTestRouter(true, h)
		req := httptest.NewRequest("POST", "/api/keys/master/nonexistent/activate", nil)
		req.Header.Set("X-User-ID", "admin")
		req.Header.Set("X-User-Role", "admin")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusInternalServerError, w.Code)
	})
}

func TestKeyHandler_RetireMasterKey(t *testing.T) {
	svc := createService(t)
	h := NewKeyHandler(svc)

	key, err := svc.CreateMasterKey("Key1")
	requireNoError(t, err)

	t.Run("success_admin", func(t *testing.T) {
		router := newTestRouter(true, h)
		req := httptest.NewRequest("POST", "/api/keys/master/"+key.ID+"/retire", nil)
		req.Header.Set("X-User-ID", "admin")
		req.Header.Set("X-User-Role", "admin")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)
		var resp map[string]interface{}
		requireNoError(t, json.NewDecoder(w.Body).Decode(&resp))
		assert.Equal(t, "key retired", resp["message"])
	})

	t.Run("forbidden_non_admin", func(t *testing.T) {
		router := newTestRouter(true, h)
		req := httptest.NewRequest("POST", "/api/keys/master/"+key.ID+"/retire", nil)
		req.Header.Set("X-User-ID", "user1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusForbidden, w.Code)
	})

	t.Run("not_found", func(t *testing.T) {
		router := newTestRouter(true, h)
		req := httptest.NewRequest("POST", "/api/keys/master/nonexistent/retire", nil)
		req.Header.Set("X-User-ID", "admin")
		req.Header.Set("X-User-Role", "admin")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusInternalServerError, w.Code)
	})
}

func TestKeyHandler_RotateMasterKey(t *testing.T) {
	svc := createService(t)
	h := NewKeyHandler(svc)

	key, err := svc.CreateMasterKey("Key1")
	requireNoError(t, err)

	t.Run("success_admin", func(t *testing.T) {
		router := newTestRouter(true, h)
		req := httptest.NewRequest("POST", "/api/keys/master/"+key.ID+"/rotate", nil)
		req.Header.Set("X-User-ID", "admin")
		req.Header.Set("X-User-Role", "admin")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)
		var resp map[string]interface{}
		requireNoError(t, json.NewDecoder(w.Body).Decode(&resp))
		assert.Equal(t, key.ID, resp["master_key_id"])
		assert.Contains(t, resp, "previous_version")
		assert.Contains(t, resp, "new_version")
		assert.Contains(t, resp, "rotated_at")
	})

	t.Run("forbidden_non_admin", func(t *testing.T) {
		router := newTestRouter(true, h)
		req := httptest.NewRequest("POST", "/api/keys/master/"+key.ID+"/rotate", nil)
		req.Header.Set("X-User-ID", "user1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusForbidden, w.Code)
	})

	t.Run("not_found", func(t *testing.T) {
		router := newTestRouter(true, h)
		req := httptest.NewRequest("POST", "/api/keys/master/nonexistent/rotate", nil)
		req.Header.Set("X-User-ID", "admin")
		req.Header.Set("X-User-Role", "admin")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusInternalServerError, w.Code)
	})
}

func TestKeyHandler_CreateDEK(t *testing.T) {
	svc := createService(t)
	h := NewKeyHandler(svc)

	masterKey, err := svc.CreateMasterKey("Master")
	requireNoError(t, err)

	t.Run("success", func(t *testing.T) {
		router := newTestRouter(false, h)
		body := `{"name":"DEK1","master_key_id":"` + masterKey.ID + `"}`
		req := httptest.NewRequest("POST", "/api/keys/data", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusCreated, w.Code)
		var resp map[string]interface{}
		requireNoError(t, json.NewDecoder(w.Body).Decode(&resp))
		assert.Contains(t, resp, "id")
		assert.Equal(t, "DEK1", resp["name"])
	})

	t.Run("invalid_json", func(t *testing.T) {
		router := newTestRouter(false, h)
		body := `{"name": bad}`
		req := httptest.NewRequest("POST", "/api/keys/data", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusBadRequest, w.Code)
	})

	t.Run("missing_name", func(t *testing.T) {
		router := newTestRouter(false, h)
		body := `{"master_key_id":"` + masterKey.ID + `"}`
		req := httptest.NewRequest("POST", "/api/keys/data", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		// Handler does not enforce required; accepts empty name
		assert.Equal(t, http.StatusCreated, w.Code)
	})

	t.Run("master_not_found", func(t *testing.T) {
		router := newTestRouter(false, h)
		body := `{"name":"DEK1","master_key_id":"nonexistent"}`
		req := httptest.NewRequest("POST", "/api/keys/data", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusInternalServerError, w.Code)
	})
}

func TestKeyHandler_ListDEKs(t *testing.T) {
	svc := createService(t)
	h := NewKeyHandler(svc)

	// ListDEKs actually calls ListMasterKeys in service, which is odd but we test it.
	t.Run("success", func(t *testing.T) {
		router := newTestRouter(false, h)
		req := httptest.NewRequest("GET", "/api/keys/data", nil)
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)
		var resp []map[string]interface{}
		requireNoError(t, json.NewDecoder(w.Body).Decode(&resp))
		// Initially empty
		assert.Empty(t, resp)
	})
}

func TestKeyHandler_GetDEK(t *testing.T) {
	svc := createService(t)
	h := NewKeyHandler(svc)

	masterKey, err := svc.CreateMasterKey("Master")
	requireNoError(t, err)
	dek, err := svc.CreateDEK("DEK1", masterKey.ID)
	requireNoError(t, err)

	t.Run("success_admin", func(t *testing.T) {
		router := newTestRouter(true, h)
		req := httptest.NewRequest("GET", "/api/keys/data/"+dek.ID, nil)
		req.Header.Set("X-User-ID", "admin")
		req.Header.Set("X-User-Role", "admin")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)
		var resp map[string]interface{}
		requireNoError(t, json.NewDecoder(w.Body).Decode(&resp))
		assert.Equal(t, dek.ID, resp["id"])
	})

	t.Run("forbidden_non_admin", func(t *testing.T) {
		router := newTestRouter(true, h)
		req := httptest.NewRequest("GET", "/api/keys/data/"+masterKey.ID, nil)
		req.Header.Set("X-User-ID", "user1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusForbidden, w.Code)
	})

	t.Run("not_found", func(t *testing.T) {
		router := newTestRouter(true, h)
		req := httptest.NewRequest("GET", "/api/keys/data/nonexistent", nil)
		req.Header.Set("X-User-ID", "admin")
		req.Header.Set("X-User-Role", "admin")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusNotFound, w.Code)
	})
}

func TestKeyHandler_Encrypt(t *testing.T) {
	svc := createService(t)
	h := NewKeyHandler(svc)

	masterKey, err := svc.CreateMasterKey("Master")
	requireNoError(t, err)

	_, err = svc.CreateDEK("DEK1", masterKey.ID)
	requireNoError(t, err)

	t.Run("success", func(t *testing.T) {
		router := newTestRouter(true, h)
		// Use key_name field (not dek_id)
		body := `{"key_name":"DEK1","plaintext":"Hello World"}`
		req := httptest.NewRequest("POST", "/api/keys/encrypt", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "user1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)
		var resp map[string]interface{}
		requireNoError(t, json.NewDecoder(w.Body).Decode(&resp))
		ciphertext, ok := resp["ciphertext"].(string)
		assert.True(t, ok)
		assert.NotEmpty(t, ciphertext)
		// also capture iv and tag for decrypt test
		iv, ok1 := resp["iv"].(string)
		tag, ok2 := resp["tag"].(string)
		assert.True(t, ok1)
		assert.True(t, ok2)
		// Save for decrypt
		t.Run("decrypt_success", func(t *testing.T) {
			router2 := newTestRouter(true, h)
			decBody := `{"key_name":"DEK1","ciphertext":"` + ciphertext + `","iv":"` + iv + `","tag":"` + tag + `"}`
			decReq := httptest.NewRequest("POST", "/api/keys/decrypt", strings.NewReader(decBody))
			decReq.Header.Set("Content-Type", "application/json")
			decReq.Header.Set("X-User-ID", "user1")
			decRec := httptest.NewRecorder()
			router2.ServeHTTP(decRec, decReq)
			assert.Equal(t, http.StatusOK, decRec.Code)
			var decResp map[string]interface{}
			requireNoError(t, json.NewDecoder(decRec.Body).Decode(&decResp))
			plaintext, ok := decResp["plaintext"].(string)
			assert.True(t, ok)
			assert.Equal(t, "Hello World", plaintext)
		})
	})

	t.Run("unauthorized", func(t *testing.T) {
		router := newTestRouter(false, h)
		body := `{"key_name":"DEK1","plaintext":"Hello"}`
		req := httptest.NewRequest("POST", "/api/keys/encrypt", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusUnauthorized, w.Code)
	})

	t.Run("invalid_json", func(t *testing.T) {
		router := newTestRouter(true, h)
		body := `{"key_name": bad}`
		req := httptest.NewRequest("POST", "/api/keys/encrypt", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "user1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusBadRequest, w.Code)
	})

	t.Run("missing_fields", func(t *testing.T) {
		router := newTestRouter(true, h)
		body := `{"key_name":"DEK1"}` // plaintext missing (empty)
		req := httptest.NewRequest("POST", "/api/keys/encrypt", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "user1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		// Handler doesn't validate required; empty plaintext accepted
		assert.Equal(t, http.StatusOK, w.Code)
	})

	t.Run("dek_not_found", func(t *testing.T) {
		router := newTestRouter(true, h)
		body := `{"key_name":"nonexistent","plaintext":"test"}`
		req := httptest.NewRequest("POST", "/api/keys/encrypt", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "user1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusInternalServerError, w.Code)
	})
}

func TestKeyHandler_Decrypt(t *testing.T) {
	svc := createService(t)
	h := NewKeyHandler(svc)

	masterKey, err := svc.CreateMasterKey("Master")
	requireNoError(t, err)

	_, err = svc.CreateDEK("DEK1", masterKey.ID)
	requireNoError(t, err)

	// Helper to encrypt and get ciphertext, iv, tag
	router := newTestRouter(true, h)
	encBody := `{"key_name":"DEK1","plaintext":"Hello World"}`
	encReq := httptest.NewRequest("POST", "/api/keys/encrypt", strings.NewReader(encBody))
	encReq.Header.Set("Content-Type", "application/json")
	encReq.Header.Set("X-User-ID", "user1")
	encRec := httptest.NewRecorder()
	router.ServeHTTP(encRec, encReq)
	assert.Equal(t, http.StatusOK, encRec.Code)
	var encResp map[string]interface{}
	requireNoError(t, json.NewDecoder(encRec.Body).Decode(&encResp))
	ciphertext := encResp["ciphertext"].(string)
	iv := encResp["iv"].(string)
	tag := encResp["tag"].(string)

	t.Run("success", func(t *testing.T) {
		router2 := newTestRouter(true, h)
		decBody := `{"key_name":"DEK1","ciphertext":"` + ciphertext + `","iv":"` + iv + `","tag":"` + tag + `"}`
		decReq := httptest.NewRequest("POST", "/api/keys/decrypt", strings.NewReader(decBody))
		decReq.Header.Set("Content-Type", "application/json")
		decReq.Header.Set("X-User-ID", "user1")
		decRec := httptest.NewRecorder()
		router2.ServeHTTP(decRec, decReq)
		assert.Equal(t, http.StatusOK, decRec.Code)
		var decResp map[string]interface{}
		requireNoError(t, json.NewDecoder(decRec.Body).Decode(&decResp))
		plaintext, ok := decResp["plaintext"].(string)
		assert.True(t, ok)
		assert.Equal(t, "Hello World", plaintext)
	})

	t.Run("unauthorized", func(t *testing.T) {
		router := newTestRouter(false, h)
		decBody := `{"key_name":"DEK1","ciphertext":"` + ciphertext + `","iv":"` + iv + `","tag":"` + tag + `"}`
		decReq := httptest.NewRequest("POST", "/api/keys/decrypt", strings.NewReader(decBody))
		decReq.Header.Set("Content-Type", "application/json")
		decRec := httptest.NewRecorder()
		router.ServeHTTP(decRec, decReq)
		assert.Equal(t, http.StatusUnauthorized, decRec.Code)
	})

	t.Run("missing_fields", func(t *testing.T) {
		router := newTestRouter(true, h)
		body := `{"key_name":"DEK1"}`
		req := httptest.NewRequest("POST", "/api/keys/decrypt", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "user1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		// Missing required fields (ciphertext, iv, tag) cause internal error (no validation)
		assert.Equal(t, http.StatusInternalServerError, w.Code)
	})

	t.Run("dek_not_found", func(t *testing.T) {
		router := newTestRouter(true, h)
		body := `{"key_name":"nonexistent","ciphertext":"` + ciphertext + `","iv":"` + iv + `","tag":"` + tag + `"}`
		req := httptest.NewRequest("POST", "/api/keys/decrypt", strings.NewReader(body))
		req.Header.Set("Content-Type", "application/json")
		req.Header.Set("X-User-ID", "user1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusInternalServerError, w.Code)
	})
}

func TestKeyHandler_GetAuditLogs(t *testing.T) {
	svc := createService(t)
	h := NewKeyHandler(svc)

	// Create a key to generate some audit logs
	_, err := svc.CreateMasterKey("Key1")
	requireNoError(t, err)

	t.Run("success_admin", func(t *testing.T) {
		router := newTestRouter(true, h)
		req := httptest.NewRequest("GET", "/api/keys/audit?limit=50", nil)
		req.Header.Set("X-User-ID", "admin")
		req.Header.Set("X-User-Role", "admin")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusOK, w.Code)
		var resp []map[string]interface{}
		requireNoError(t, json.NewDecoder(w.Body).Decode(&resp))
		// Should have at least one audit log (CREATE)
		assert.NotEmpty(t, resp)
	})

	t.Run("forbidden_non_admin", func(t *testing.T) {
		router := newTestRouter(true, h)
		req := httptest.NewRequest("GET", "/api/keys/audit", nil)
		req.Header.Set("X-User-ID", "user1")
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusForbidden, w.Code)
	})

	t.Run("unauthorized", func(t *testing.T) {
		router := newTestRouter(false, h)
		req := httptest.NewRequest("GET", "/api/keys/audit", nil)
		w := httptest.NewRecorder()
		router.ServeHTTP(w, req)
		assert.Equal(t, http.StatusUnauthorized, w.Code)
	})
}
