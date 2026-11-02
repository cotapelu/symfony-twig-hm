package LandConstruction

import (
	"bytes"
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"testing"

	"github.com/gin-gonic/gin"
	"github.com/stretchr/testify/assert"
)

func setupLandConstructionTest() *gin.Engine {
	gin.SetMode(gin.TestMode)
	router := gin.New()
	// Add middleware to set admin roles for tests
	router.Use(func(c *gin.Context) {
		c.Set("user_roles", []string{"admin"})
		c.Next()
	})
	RegisterRoutes(router)
	return router
}

func TestGetLandRecords(t *testing.T) {
	router := setupLandConstructionTest()

	req, _ := http.NewRequest("GET", "/land", nil)
	req.Header.Set("Authorization", "Bearer test_token_12345")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
}

func TestGetLandRecords_WithFilter(t *testing.T) {
	router := setupLandConstructionTest()

	req, _ := http.NewRequest("GET", "/land?filter=Đất ở", nil)
	req.Header.Set("Authorization", "Bearer test_token_12345")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
}

func TestGetLandRecords_WithSearch(t *testing.T) {
	router := setupLandConstructionTest()

	req, _ := http.NewRequest("GET", "/land?search=Nguyễn", nil)
	req.Header.Set("Authorization", "Bearer test_token_12345")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
}

func TestGetLandStatistics(t *testing.T) {
	router := setupLandConstructionTest()

	req, _ := http.NewRequest("GET", "/land/statistics", nil)
	req.Header.Set("Authorization", "Bearer test_token_12345")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
}

func TestGetLandRecord(t *testing.T) {
	router := setupLandConstructionTest()

	req, _ := http.NewRequest("GET", "/land/1", nil)
	req.Header.Set("Authorization", "Bearer test_token_12345")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
}

func TestGetLandRecord_NotFound(t *testing.T) {
	router := setupLandConstructionTest()

	req, _ := http.NewRequest("GET", "/land/999", nil)
	req.Header.Set("Authorization", "Bearer test_token_12345")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusNotFound, w.Code)
}

func TestCreateLandRecord(t *testing.T) {
	router := setupLandConstructionTest()

	record := LandRecord{
		PlotNumber: "LĐ-999",
		Owner:      "Test Owner",
		Area:       100.0,
		Address:    "123 Test St",
		Type:       "Đất ở",
		Status:     "Chờ cấp",
	}
	jsonBody, _ := json.Marshal(record)

	req, _ := http.NewRequest("POST", "/land", bytes.NewBuffer(jsonBody))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Authorization", "Bearer test_token_12345")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusCreated, w.Code)
}

func TestUpdateLandRecord(t *testing.T) {
	router := setupLandConstructionTest()

	record := LandRecord{
		PlotNumber: "LĐ-001",
		Owner:      "Updated Owner",
		Area:       150.0,
		Address:    "123 Updated St",
		Type:       "Đất ở",
		Status:     "Đã cấp GCN",
	}
	jsonBody, _ := json.Marshal(record)

	req, _ := http.NewRequest("PUT", "/land/1", bytes.NewBuffer(jsonBody))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Authorization", "Bearer test_token_12345")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
}

func TestDeleteLandRecord(t *testing.T) {
	router := setupLandConstructionTest()

	req, _ := http.NewRequest("DELETE", "/land/1", nil)
	req.Header.Set("Authorization", "Bearer test_token_12345")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
}

func TestDeleteLandRecord_NotFound(t *testing.T) {
	router := setupLandConstructionTest()

	req, _ := http.NewRequest("DELETE", "/land/999", nil)
	req.Header.Set("Authorization", "Bearer test_token_12345")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusNotFound, w.Code)
}

func TestGetPermits(t *testing.T) {
	router := setupLandConstructionTest()

	req, _ := http.NewRequest("GET", "/permits", nil)
	req.Header.Set("Authorization", "Bearer test_token_12345")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
}

func TestGetPermits_WithStatusFilter(t *testing.T) {
	router := setupLandConstructionTest()

	req, _ := http.NewRequest("GET", "/permits?status=Đã duyệt", nil)
	req.Header.Set("Authorization", "Bearer test_token_12345")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
}

func TestGetPermitStatistics(t *testing.T) {
	router := setupLandConstructionTest()

	req, _ := http.NewRequest("GET", "/permits/statistics", nil)
	req.Header.Set("Authorization", "Bearer test_token_12345")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
}

func TestGetPermit(t *testing.T) {
	router := setupLandConstructionTest()

	req, _ := http.NewRequest("GET", "/permits/1", nil)
	req.Header.Set("Authorization", "Bearer test_token_12345")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
}

func TestGetPermit_NotFound(t *testing.T) {
	router := setupLandConstructionTest()

	req, _ := http.NewRequest("GET", "/permits/999", nil)
	req.Header.Set("Authorization", "Bearer test_token_12345")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusNotFound, w.Code)
}

func TestCreatePermit(t *testing.T) {
	router := setupLandConstructionTest()

	permit := ConstructionPermit{
		Applicant:     "Test Applicant",
		Address:       "123 Test St",
		PermitType:    "Xây dựng mới",
		Area:          150,
		Status:        "Chờ duyệt",
		SubmittedDate: "2026-03-08",
	}
	jsonBody, _ := json.Marshal(permit)

	req, _ := http.NewRequest("POST", "/permits", bytes.NewBuffer(jsonBody))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Authorization", "Bearer test_token_12345")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusCreated, w.Code)
}

func TestUpdatePermit(t *testing.T) {
	router := setupLandConstructionTest()

	permit := ConstructionPermit{
		Applicant:     "Updated Applicant",
		Address:       "123 Updated St",
		PermitType:    "Cải tạo",
		Area:          100,
		Status:        "Đã duyệt",
		SubmittedDate: "2026-03-08",
	}
	jsonBody, _ := json.Marshal(permit)

	req, _ := http.NewRequest("PUT", "/permits/1", bytes.NewBuffer(jsonBody))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Authorization", "Bearer test_token_12345")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
}

func TestDeletePermit(t *testing.T) {
	router := setupLandConstructionTest()

	req, _ := http.NewRequest("DELETE", "/permits/1", nil)
	req.Header.Set("Authorization", "Bearer test_token_12345")
	w := httptest.NewRecorder()
	router.ServeHTTP(w, req)

	assert.Equal(t, http.StatusOK, w.Code)
}

func TestLandRecord_Entity(t *testing.T) {
	record := LandRecord{
		ID:         "1",
		PlotNumber: "LĐ-001",
		Owner:      "Nguyễn Văn A",
		Area:       150.5,
		Address:    "123 Đường Láng",
		Type:       "Đất ở",
		Status:     "Đã cấp GCN",
	}

	assert.Equal(t, "1", record.ID)
	assert.Equal(t, "LĐ-001", record.PlotNumber)
	assert.Equal(t, 150.5, record.Area)
}

func TestConstructionPermit_Entity(t *testing.T) {
	permit := ConstructionPermit{
		ID:            "1",
		Applicant:     "Nguyễn Văn A",
		Address:       "123 Đường Láng",
		PermitType:    "Xây dựng mới",
		Area:          150.0,
		Status:        "Đã duyệt",
		SubmittedDate: "2026-01-15",
	}

	assert.Equal(t, "1", permit.ID)
	assert.Equal(t, "Nguyễn Văn A", permit.Applicant)
	assert.Equal(t, 150.0, permit.Area)
}
