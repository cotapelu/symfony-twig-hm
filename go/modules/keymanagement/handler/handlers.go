package handler

import (
	"net/http"

	"community.local/modules/keymanagement/entity"
	"community.local/modules/keymanagement/service"

	"github.com/gin-gonic/gin"
)

func getCurrentUserID(c *gin.Context) (string, bool) {
	userID, exists := c.Get("user_id")
	if !exists {
		return "", false
	}
	return userID.(string), true
}

func requireAuth(c *gin.Context) (string, bool) {
	userID, ok := getCurrentUserID(c)
	if !ok {
		c.JSON(401, gin.H{"error": "authentication required"})
		return "", false
	}
	return userID, true
}

func isAdmin(c *gin.Context) bool {
	if roles, ok := c.Get("user_roles"); ok {
		for _, r := range roles.([]string) {
			if r == "ROLE_ADMIN" || r == "admin" {
				return true
			}
		}
	}
	return false
}

type KeyHandler struct {
	svc *service.KeyService
}

func NewKeyHandler(svc *service.KeyService) *KeyHandler {
	return &KeyHandler{svc: svc}
}

func (h *KeyHandler) RegisterRoutes(router *gin.Engine) {
	keys := router.Group("/api/keys")
	{
		keys.POST("/master", h.CreateMasterKey)
		keys.GET("/master", h.ListMasterKeys)
		keys.GET("/master/:id", h.GetMasterKey)
		keys.POST("/master/:id/activate", h.ActivateMasterKey)
		keys.POST("/master/:id/retire", h.RetireMasterKey)
		keys.POST("/master/:id/rotate", h.RotateMasterKey)
		keys.POST("/data", h.CreateDEK)
		keys.GET("/data", h.ListDEKs)
		keys.GET("/data/:id", h.GetDEK)
		keys.POST("/encrypt", h.Encrypt)
		keys.POST("/decrypt", h.Decrypt)
		keys.GET("/audit", h.GetAuditLogs)
	}
}

func (h *KeyHandler) CreateMasterKey(c *gin.Context) {
	var req struct {
		Name string `json:"name"`
	}
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	key, err := h.svc.CreateMasterKey(req.Name)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusCreated, key)
}

func (h *KeyHandler) ListMasterKeys(c *gin.Context) {
	keys, err := h.svc.ListMasterKeys()
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusOK, keys)
}

func (h *KeyHandler) GetMasterKey(c *gin.Context) {
	// Admin only
	if _, ok := requireAuth(c); !ok {
		return
	}
	if !isAdmin(c) {
		c.JSON(403, gin.H{"error": "access denied: only admin can view master keys"})
		return
	}

	id := c.Param("id")

	key, err := h.svc.GetMasterKey(id)
	if err != nil {
		c.JSON(http.StatusNotFound, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusOK, key)
}

func (h *KeyHandler) ActivateMasterKey(c *gin.Context) {
	// Admin only
	if _, ok := requireAuth(c); !ok {
		return
	}
	if !isAdmin(c) {
		c.JSON(403, gin.H{"error": "access denied: only admin can activate keys"})
		return
	}

	id := c.Param("id")

	err := h.svc.ActivateMasterKey(id)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusOK, gin.H{"message": "key activated"})
}

func (h *KeyHandler) RetireMasterKey(c *gin.Context) {
	// Admin only
	if _, ok := requireAuth(c); !ok {
		return
	}
	if !isAdmin(c) {
		c.JSON(403, gin.H{"error": "access denied: only admin can retire keys"})
		return
	}

	id := c.Param("id")

	err := h.svc.RetireMasterKey(id)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusOK, gin.H{"message": "key retired"})
}

func (h *KeyHandler) RotateMasterKey(c *gin.Context) {
	// Admin only
	if _, ok := requireAuth(c); !ok {
		return
	}
	if !isAdmin(c) {
		c.JSON(403, gin.H{"error": "access denied: only admin can rotate keys"})
		return
	}

	id := c.Param("id")

	result, err := h.svc.RotateMasterKey(id)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusOK, result)
}

func (h *KeyHandler) CreateDEK(c *gin.Context) {
	var req struct {
		Name        string `json:"name"`
		MasterKeyID string `json:"master_key_id"`
	}
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	key, err := h.svc.CreateDEK(req.Name, req.MasterKeyID)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusCreated, key)
}

func (h *KeyHandler) ListDEKs(c *gin.Context) {
	keys, err := h.svc.ListMasterKeys()
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusOK, keys)
}

func (h *KeyHandler) GetDEK(c *gin.Context) {
	// Admin only
	if _, ok := requireAuth(c); !ok {
		return
	}
	if !isAdmin(c) {
		c.JSON(403, gin.H{"error": "access denied: only admin can view DEKs"})
		return
	}

	id := c.Param("id")

	key, err := h.svc.GetDEK(id)
	if err != nil {
		c.JSON(http.StatusNotFound, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusOK, key)
}

func (h *KeyHandler) Encrypt(c *gin.Context) {
	// Require authentication
	if _, ok := requireAuth(c); !ok {
		return
	}

	var req entity.EncryptRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	userID, _ := getCurrentUserID(c)
	result, err := h.svc.Encrypt(&req, userID, c.ClientIP())
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusOK, result)
}

func (h *KeyHandler) Decrypt(c *gin.Context) {
	// Require authentication
	if _, ok := requireAuth(c); !ok {
		return
	}

	var req entity.DecryptRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	userID, _ := getCurrentUserID(c)
	result, err := h.svc.Decrypt(&req, userID, c.ClientIP())
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusOK, result)
}

func (h *KeyHandler) GetAuditLogs(c *gin.Context) {
	// Admin only
	if _, ok := requireAuth(c); !ok {
		return
	}
	if !isAdmin(c) {
		c.JSON(403, gin.H{"error": "access denied: only admin can view audit logs"})
		return
	}

	id := c.Query("key_id")
	limit := 100

	logs, err := h.svc.GetAuditLogs(id, limit)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusOK, logs)
}
