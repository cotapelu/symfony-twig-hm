package handler

import (
	"net/http"

	"community.local/modules/certificateauthority/entity"
	"community.local/modules/certificateauthority/service"

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

type CAHandler struct {
	svc *service.CAService
}

func NewCAHandler(svc *service.CAService) *CAHandler {
	return &CAHandler{svc: svc}
}

func (h *CAHandler) RegisterRoutes(router *gin.Engine) {
	ca := router.Group("/api/ca")
	{
		ca.POST("/root", h.CreateRootCA)
		ca.GET("/:id", h.GetCA)
		ca.POST("/csr", h.CreateCSR)
		ca.POST("/csr/:id/sign", h.SignCSR)
		ca.POST("/cert/:id/revoke", h.RevokeCertificate)
		ca.GET("/cert/:id", h.GetCertificate)
		ca.GET("/certs", h.ListCertificates)
		ca.GET("/csr/:id", h.GetCSR)
		ca.GET("/csrs", h.ListCSRs)
		ca.POST("/keys/generate", h.GenerateKeyPair)
		ca.POST("/csr/generate", h.GenerateCSR)
	}
}

func (h *CAHandler) CreateRootCA(c *gin.Context) {
	// Admin only
	if _, ok := requireAuth(c); !ok {
		return
	}
	if !isAdmin(c) {
		c.JSON(403, gin.H{"error": "access denied: only admin can create root CA"})
		return
	}

	var req entity.CreateCARequest
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	ca, err := h.svc.CreateRootCA(&req)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusCreated, ca)
}

func (h *CAHandler) GetCA(c *gin.Context) {
	id := c.Param("id")

	ca, err := h.svc.GetCA(id)
	if err != nil {
		c.JSON(http.StatusNotFound, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusOK, ca)
}

func (h *CAHandler) CreateCSR(c *gin.Context) {
	// Require authentication
	if _, ok := requireAuth(c); !ok {
		return
	}

	var req entity.SignCertificateRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	// Use authenticated user ID
	userID, _ := getCurrentUserID(c)
	csr, err := h.svc.CreateCSR(&req, userID)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusCreated, csr)
}

func (h *CAHandler) SignCSR(c *gin.Context) {
	// Admin only
	if _, ok := requireAuth(c); !ok {
		return
	}
	if !isAdmin(c) {
		c.JSON(403, gin.H{"error": "access denied: only admin can sign CSR"})
		return
	}

	csrID := c.Param("id")

	resp, err := h.svc.SignCSR(csrID)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusOK, resp)
}

func (h *CAHandler) RevokeCertificate(c *gin.Context) {
	// Admin only
	if _, ok := requireAuth(c); !ok {
		return
	}
	if !isAdmin(c) {
		c.JSON(403, gin.H{"error": "access denied: only admin can revoke certificates"})
		return
	}

	certID := c.Param("id")

	var req entity.RevokeCertificateRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	if err := h.svc.RevokeCertificate(certID, req.Reason); err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusOK, gin.H{"message": "certificate revoked"})
}

func (h *CAHandler) GetCertificate(c *gin.Context) {
	certID := c.Param("id")

	cert, err := h.svc.GetCertificate(certID)
	if err != nil {
		c.JSON(http.StatusNotFound, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusOK, cert)
}

func (h *CAHandler) ListCertificates(c *gin.Context) {
	certs, err := h.svc.ListCertificates()
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusOK, certs)
}

func (h *CAHandler) GetCSR(c *gin.Context) {
	csrID := c.Param("id")

	csr, err := h.svc.GetCSR(csrID)
	if err != nil {
		c.JSON(http.StatusNotFound, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusOK, csr)
}

func (h *CAHandler) ListCSRs(c *gin.Context) {
	csrs, err := h.svc.ListCSRs()
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusOK, csrs)
}

func (h *CAHandler) GenerateKeyPair(c *gin.Context) {
	privateKeyPEM, publicKeyPEM, err := h.svc.GenerateKeyPair()
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"private_key": privateKeyPEM,
		"public_key":  publicKeyPEM,
	})
}

func (h *CAHandler) GenerateCSR(c *gin.Context) {
	var req struct {
		CommonName    string `json:"common_name"`
		PrivateKeyPEM string `json:"private_key_pem"`
	}
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	csrPEM, err := h.svc.GenerateCSR(req.CommonName, req.PrivateKeyPEM)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusOK, gin.H{"csr_pem": csrPEM})
}
