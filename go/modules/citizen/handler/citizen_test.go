package handler

import (
	"net/http"
	"net/http/httptest"
	"testing"

	"github.com/gin-gonic/gin"
	"github.com/stretchr/testify/assert"
)

func TestCreateCitizenWithoutAuth(t *testing.T) {
	gin.SetMode(gin.TestMode)
	router := gin.New()

	router.POST("/api/citizen", func(c *gin.Context) {
		userID, ok := requireAuth(c)
		if !ok {
			return // Already handled error response
		}
		_ = userID
		c.JSON(http.StatusCreated, gin.H{"status": "ok"})
	})

	req, _ := http.NewRequest("POST", "/api/citizen", nil)
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusUnauthorized, w.Code)
}

func TestGetCitizenOwnership(t *testing.T) {
	gin.SetMode(gin.TestMode)
	router := gin.New()

	router.GET("/api/citizen/:id", func(c *gin.Context) {
		c.Set("user_id", "user_123")
		c.Set("user_roles", []string{"ROLE_USER"})

		id := c.Param("id")
		authUserID, _ := getCurrentUserID(c)

		if authUserID != id && !isAdmin(c) {
			c.JSON(403, gin.H{"error": "can only view your own profile"})
			return
		}
		c.JSON(200, gin.H{"id": id, "status": "ok"})
	})

	// Should return 403 for different user
	req, _ := http.NewRequest("GET", "/api/citizen/user_456", nil)
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)
	assert.Equal(t, http.StatusForbidden, w.Code)

	// Should return 200 for same user
	req2, _ := http.NewRequest("GET", "/api/citizen/user_123", nil)
	w2 := httptest.NewRecorder()
	router.ServeHTTP(w2, req2)
	assert.Equal(t, http.StatusOK, w2.Code)
}

func TestSearchCitizensRequiresAdmin(t *testing.T) {
	gin.SetMode(gin.TestMode)
	router := gin.New()

	router.GET("/api/citizen/search", func(c *gin.Context) {
		if _, ok := requireAuth(c); !ok {
			return
		}
		if !isAdmin(c) {
			c.JSON(403, gin.H{"error": "only admin can search citizens"})
			return
		}
		c.JSON(200, gin.H{"results": []interface{}{}})
	})

	// Should return 401 without auth
	req, _ := http.NewRequest("GET", "/api/citizen/search?q=test", nil)
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)
	assert.Equal(t, http.StatusUnauthorized, w.Code)

	// Should return 403 with non-admin user
	router2 := gin.New()
	router2.GET("/api/citizen/search", func(c *gin.Context) {
		c.Set("user_id", "user_123")
		c.Set("user_roles", []string{"ROLE_USER"})

		if _, ok := requireAuth(c); !ok {
			return
		}
		if !isAdmin(c) {
			c.JSON(403, gin.H{"error": "only admin can search citizens"})
			return
		}
		c.JSON(200, gin.H{"results": []interface{}{}})
	})

	req2, _ := http.NewRequest("GET", "/api/citizen/search?q=test", nil)
	w2 := httptest.NewRecorder()
	router2.ServeHTTP(w2, req2)
	assert.Equal(t, http.StatusForbidden, w2.Code)

	// Should return 200 with admin user
	router3 := gin.New()
	router3.GET("/api/citizen/search", func(c *gin.Context) {
		c.Set("user_id", "admin_123")
		c.Set("user_roles", []string{"ROLE_ADMIN"})

		if _, ok := requireAuth(c); !ok {
			return
		}
		if !isAdmin(c) {
			c.JSON(403, gin.H{"error": "only admin can search citizens"})
			return
		}
		c.JSON(200, gin.H{"results": []interface{}{}})
	})

	req3, _ := http.NewRequest("GET", "/api/citizen/search?q=test", nil)
	w3 := httptest.NewRecorder()
	router3.ServeHTTP(w3, req3)
	assert.Equal(t, http.StatusOK, w3.Code)
}