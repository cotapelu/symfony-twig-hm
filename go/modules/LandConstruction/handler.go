package LandConstruction

import (
	"net/http"
	"strconv"

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

type LandRecord struct {
	ID         string  `json:"id"`
	PlotNumber string  `json:"plotNumber"`
	Owner      string  `json:"owner"`
	Area       float64 `json:"area"`
	Address    string  `json:"address"`
	Type       string  `json:"type"`
	Status     string  `json:"status"`
}

type ConstructionPermit struct {
	ID            string  `json:"id"`
	Applicant     string  `json:"applicant"`
	Address       string  `json:"address"`
	PermitType    string  `json:"permitType"`
	Area          float64 `json:"area"`
	Status        string  `json:"status"`
	SubmittedDate string  `json:"submittedDate"`
}

var landRecords = []LandRecord{
	{ID: "1", PlotNumber: "LĐ-001", Owner: "Nguyễn Văn A", Area: 150.5, Address: "123 Đường Láng", Type: "Đất ở", Status: "Đã cấp GCN"},
	{ID: "2", PlotNumber: "LĐ-002", Owner: "Trần Thị B", Area: 200.0, Address: "456 Nguyễn Trãi", Type: "Đất nông nghiệp", Status: "Đã cấp GCN"},
	{ID: "3", PlotNumber: "LĐ-003", Owner: "Lê Văn C", Area: 120.0, Address: "789 Lý Thường Kiệt", Type: "Đất ở", Status: "Chờ cấp"},
	{ID: "4", PlotNumber: "LĐ-004", Owner: "Phạm Thị D", Area: 180.0, Address: "321 Hoàng Quốc Việt", Type: "Đất ở", Status: "Đã cấp GCN"},
	{ID: "5", PlotNumber: "LĐ-005", Owner: "Hoàng Văn E", Area: 250.0, Address: "654 Cầu Giấy", Type: "Đất phi nông nghiệp", Status: "Đã cấp GCN"},
}

var permits = []ConstructionPermit{
	{ID: "1", Applicant: "Nguyễn Văn A", Address: "123 Đường Láng", PermitType: "Xây dựng mới", Area: 150, Status: "Đã duyệt", SubmittedDate: "2026-01-15"},
	{ID: "2", Applicant: "Trần Thị B", Address: "456 Nguyễn Trãi", PermitType: "Cải tạo", Area: 80, Status: "Đang xử lý", SubmittedDate: "2026-02-01"},
	{ID: "3", Applicant: "Lê Văn C", Address: "789 Lý Thường Kiệt", PermitType: "Xây dựng mới", Area: 200, Status: "Chờ duyệt", SubmittedDate: "2026-02-10"},
}

var idCounter = 6

func RegisterRoutes(r *gin.Engine) {
	authorized := r.Group("/land")
	authorized.Use(AuthMiddleware())
	{
		authorized.GET("", GetLandRecords)
		authorized.GET("/statistics", GetLandStatistics)
		authorized.GET("/:id", GetLandRecord)
		authorized.POST("", CreateLandRecord)
		authorized.PUT("/:id", UpdateLandRecord)
		authorized.DELETE("/:id", DeleteLandRecord)
	}

	permits := r.Group("/permits")
	permits.Use(AuthMiddleware())
	{
		permits.GET("", GetPermits)
		permits.GET("/statistics", GetPermitStatistics)
		permits.GET("/:id", GetPermit)
		permits.POST("", CreatePermit)
		permits.PUT("/:id", UpdatePermit)
		permits.DELETE("/:id", DeletePermit)
	}
}

func AuthMiddleware() gin.HandlerFunc {
	return func(c *gin.Context) {
		authHeader := c.GetHeader("Authorization")
		if authHeader == "" {
			c.JSON(http.StatusUnauthorized, gin.H{"error": "Authorization header required"})
			c.Abort()
			return
		}
		if len(authHeader) < 7 || authHeader[:7] != "Bearer " {
			c.JSON(http.StatusUnauthorized, gin.H{"error": "Invalid authorization header format"})
			c.Abort()
			return
		}
		token := authHeader[7:]
		if len(token) < 10 {
			c.JSON(http.StatusUnauthorized, gin.H{"error": "Invalid token"})
			c.Abort()
			return
		}
		c.Set("user_id", token)
		c.Next()
	}
}

func GetLandRecords(c *gin.Context) {
	// Require authentication
	userID, ok := requireAuth(c)
	if !ok {
		return
	}

	filter := c.Query("filter")
	search := c.Query("search")

	filtered := landRecords
	if filter != "all" {
		filtered = filterByType(filtered, filter)
	}
	if search != "" {
		filtered = searchLand(filtered, search)
	}

	// Non-admin can only see their own records
	if !isAdmin(c) {
		var ownRecords []LandRecord
		for _, r := range filtered {
			if r.Owner == userID {
				ownRecords = append(ownRecords, r)
			}
		}
		filtered = ownRecords
	}

	c.JSON(http.StatusOK, gin.H{
		"items": filtered,
		"total": len(filtered),
	})
}

func GetLandStatistics(c *gin.Context) {
	// Require authentication - admin only
	if _, ok := requireAuth(c); !ok {
		return
	}
	if !isAdmin(c) {
		c.JSON(403, gin.H{"error": "access denied: only admin can view statistics"})
		return
	}

	total := len(landRecords)
	certified := 0
	pending := 0

	for _, r := range landRecords {
		if r.Status == "Đã cấp GCN" {
			certified++
		} else {
			pending++
		}
	}

	c.JSON(http.StatusOK, gin.H{
		"totalLandPlots":     total,
		"certificatesIssued": certified,
		"pending":            pending,
	})
}

func GetLandRecord(c *gin.Context) {
	// Require authentication
	userID, ok := requireAuth(c)
	if !ok {
		return
	}

	id := c.Param("id")
	for _, r := range landRecords {
		if r.ID == id {
			// Check ownership: only owner or admin can view
			if r.Owner != userID && !isAdmin(c) {
				c.JSON(403, gin.H{"error": "access denied: you can only view your own land records"})
				return
			}
			c.JSON(http.StatusOK, r)
			return
		}
	}
	c.JSON(http.StatusNotFound, gin.H{"error": "Land record not found"})
}

func CreateLandRecord(c *gin.Context) {
	var record LandRecord
	if err := c.ShouldBindJSON(&record); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	if err := validateLandRecord(&record); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	idCounter++
	record.ID = strconv.Itoa(idCounter)

	landRecords = append(landRecords, record)
	c.JSON(http.StatusCreated, record)
}

func UpdateLandRecord(c *gin.Context) {
	// Require authentication
	userID, ok := requireAuth(c)
	if !ok {
		return
	}

	id := c.Param("id")
	var record LandRecord

	if err := c.ShouldBindJSON(&record); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	for i, r := range landRecords {
		if r.ID == id {
			// Check ownership: only owner or admin can update
			if r.Owner != userID && !isAdmin(c) {
				c.JSON(403, gin.H{"error": "access denied: you can only update your own land records"})
				return
			}
			record.ID = id
			landRecords[i] = record
			c.JSON(http.StatusOK, record)
			return
		}
	}
	c.JSON(http.StatusNotFound, gin.H{"error": "Land record not found"})
}

func DeleteLandRecord(c *gin.Context) {
	// Require authentication
	userID, ok := requireAuth(c)
	if !ok {
		return
	}

	id := c.Param("id")
	for i, r := range landRecords {
		if r.ID == id {
			// Check ownership: only owner or admin can delete
			if r.Owner != userID && !isAdmin(c) {
				c.JSON(403, gin.H{"error": "access denied: you can only delete your own land records"})
				return
			}
			landRecords = append(landRecords[:i], landRecords[i+1:]...)
			c.JSON(http.StatusOK, gin.H{"message": "Deleted successfully"})
			return
		}
	}
	c.JSON(http.StatusNotFound, gin.H{"error": "Land record not found"})
}

func GetPermits(c *gin.Context) {
	// Require authentication
	userID, ok := requireAuth(c)
	if !ok {
		return
	}

	status := c.Query("status")

	var filtered []ConstructionPermit
	if status != "" {
		filtered = filterPermitsByStatus(status)
	} else {
		filtered = permits
	}

	// Non-admin can only see their own permits
	if !isAdmin(c) {
		var ownPermits []ConstructionPermit
		for _, p := range filtered {
			if p.Applicant == userID {
				ownPermits = append(ownPermits, p)
			}
		}
		filtered = ownPermits
	}

	c.JSON(http.StatusOK, gin.H{
		"items": filtered,
		"total": len(filtered),
	})
}

func GetPermitStatistics(c *gin.Context) {
	// Require authentication - admin only
	if _, ok := requireAuth(c); !ok {
		return
	}
	if !isAdmin(c) {
		c.JSON(403, gin.H{"error": "access denied: only admin can view statistics"})
		return
	}

	total := len(permits)
	approved := 0
	pending := 0
	processing := 0

	for _, p := range permits {
		switch p.Status {
		case "Đã duyệt":
			approved++
		case "Chờ duyệt":
			pending++
		case "Đang xử lý":
			processing++
		}
	}

	c.JSON(http.StatusOK, gin.H{
		"totalPermits": total,
		"approved":     approved,
		"pending":      pending,
		"processing":   processing,
	})
}

func GetPermit(c *gin.Context) {
	// Require authentication
	userID, ok := requireAuth(c)
	if !ok {
		return
	}

	id := c.Param("id")
	for _, p := range permits {
		if p.ID == id {
			// Check ownership: only applicant or admin can view
			if p.Applicant != userID && !isAdmin(c) {
				c.JSON(403, gin.H{"error": "access denied: you can only view your own permits"})
				return
			}
			c.JSON(http.StatusOK, p)
			return
		}
	}
	c.JSON(http.StatusNotFound, gin.H{"error": "Permit not found"})
}

func CreatePermit(c *gin.Context) {
	var permit ConstructionPermit
	if err := c.ShouldBindJSON(&permit); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	if err := validateConstructionPermit(&permit); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	idCounter++
	permit.ID = strconv.Itoa(idCounter)

	permits = append(permits, permit)
	c.JSON(http.StatusCreated, permit)
}

func UpdatePermit(c *gin.Context) {
	id := c.Param("id")
	var permit ConstructionPermit

	if err := c.ShouldBindJSON(&permit); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	for i, p := range permits {
		if p.ID == id {
			permit.ID = id
			permits[i] = permit
			c.JSON(http.StatusOK, permit)
			return
		}
	}
	c.JSON(http.StatusNotFound, gin.H{"error": "Permit not found"})
}

func DeletePermit(c *gin.Context) {
	// Require authentication
	userID, ok := requireAuth(c)
	if !ok {
		return
	}

	id := c.Param("id")
	for i, p := range permits {
		if p.ID == id {
			// Check ownership: only applicant or admin can delete
			if p.Applicant != userID && !isAdmin(c) {
				c.JSON(403, gin.H{"error": "access denied: you can only delete your own permits"})
				return
			}
			permits = append(permits[:i], permits[i+1:]...)
			c.JSON(http.StatusOK, gin.H{"message": "Deleted successfully"})
			return
		}
	}
	c.JSON(http.StatusNotFound, gin.H{"error": "Permit not found"})
}

func filterByType(records []LandRecord, filterType string) []LandRecord {
	var result []LandRecord
	for _, r := range records {
		if r.Type == filterType {
			result = append(result, r)
		}
	}
	return result
}

func searchLand(records []LandRecord, query string) []LandRecord {
	var result []LandRecord
	lowerQuery := toLower(query)
	for _, r := range records {
		if contains(toLower(r.Owner), lowerQuery) ||
			contains(toLower(r.PlotNumber), lowerQuery) ||
			contains(toLower(r.Address), lowerQuery) {
			result = append(result, r)
		}
	}
	return result
}

func filterPermitsByStatus(status string) []ConstructionPermit {
	var result []ConstructionPermit
	for _, p := range permits {
		if p.Status == status {
			result = append(result, p)
		}
	}
	return result
}

func toLower(s string) string {
	result := make([]byte, len(s))
	for i := 0; i < len(s); i++ {
		c := s[i]
		if c >= 'A' && c <= 'Z' {
			c += 32
		}
		result[i] = c
	}
	return string(result)
}

func contains(s, substr string) bool {
	return len(s) >= len(substr) && (s == substr || len(substr) == 0 ||
		(containsHelper(s, substr)))
}

func containsHelper(s, substr string) bool {
	for i := 0; i <= len(s)-len(substr); i++ {
		if s[i:i+len(substr)] == substr {
			return true
		}
	}
	return false
}

func validateLandRecord(record *LandRecord) error {
	if record.PlotNumber == "" {
		return &ValidationError{Field: "plotNumber", Message: "Plot number is required"}
	}
	if len(record.PlotNumber) > 50 {
		return &ValidationError{Field: "plotNumber", Message: "Plot number cannot exceed 50 characters"}
	}
	if record.Owner == "" {
		return &ValidationError{Field: "owner", Message: "Owner is required"}
	}
	if len(record.Owner) > 255 {
		return &ValidationError{Field: "owner", Message: "Owner cannot exceed 255 characters"}
	}
	if record.Area <= 0 {
		return &ValidationError{Field: "area", Message: "Area must be greater than 0"}
	}
	if record.Area > 1000000 {
		return &ValidationError{Field: "area", Message: "Area cannot exceed 1,000,000 sqm"}
	}
	if record.Address == "" {
		return &ValidationError{Field: "address", Message: "Address is required"}
	}
	if len(record.Address) > 500 {
		return &ValidationError{Field: "address", Message: "Address cannot exceed 500 characters"}
	}
	if record.Type == "" {
		return &ValidationError{Field: "type", Message: "Type is required"}
	}
	return nil
}

func validateConstructionPermit(permit *ConstructionPermit) error {
	if permit.Applicant == "" {
		return &ValidationError{Field: "applicant", Message: "Applicant is required"}
	}
	if len(permit.Applicant) > 255 {
		return &ValidationError{Field: "applicant", Message: "Applicant cannot exceed 255 characters"}
	}
	if permit.Address == "" {
		return &ValidationError{Field: "address", Message: "Address is required"}
	}
	if len(permit.Address) > 500 {
		return &ValidationError{Field: "address", Message: "Address cannot exceed 500 characters"}
	}
	if permit.PermitType == "" {
		return &ValidationError{Field: "permitType", Message: "Permit type is required"}
	}
	if permit.Area <= 0 {
		return &ValidationError{Field: "area", Message: "Area must be greater than 0"}
	}
	if permit.Area > 100000 {
		return &ValidationError{Field: "area", Message: "Area cannot exceed 100,000 sqm"}
	}
	return nil
}

type ValidationError struct {
	Field   string
	Message string
}

func (e *ValidationError) Error() string {
	return e.Field + ": " + e.Message
}
