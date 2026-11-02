package audit

import (
	"testing"
	"time"

	"github.com/stretchr/testify/assert"
)

func TestNewAuditLogger(t *testing.T) {
	logger, err := NewAuditLogger("/tmp/test_audit.log")
	assert.NoError(t, err)
	assert.NotNil(t, logger)
}

func TestLogCreate(t *testing.T) {
	logger, _ := NewAuditLogger("/tmp/test_audit_create.log")
	
	logger.LogCreate("user123", "product", "prod456", map[string]interface{}{
		"name":  "Test Product",
		"price": 99.99,
	})
	
	assert.Len(t, logger.entries, 1)
	assert.Equal(t, ActionCreate, logger.entries[0].Action)
	assert.Equal(t, "user123", logger.entries[0].UserID)
	assert.Equal(t, "product", logger.entries[0].ResourceType)
}

func TestLogUpdate(t *testing.T) {
	logger, _ := NewAuditLogger("/tmp/test_audit_update.log")
	
	logger.LogUpdate("user456", "user", "user789", map[string]interface{}{
		"email": "test@example.com",
	})
	
	assert.Len(t, logger.entries, 1)
	assert.Equal(t, ActionUpdate, logger.entries[0].Action)
}

func TestLogDelete(t *testing.T) {
	logger, _ := NewAuditLogger("/tmp/test_audit_delete.log")
	
	logger.LogDelete("user123", "order", "order999")
	
	assert.Len(t, logger.entries, 1)
	assert.Equal(t, ActionDelete, logger.entries[0].Action)
}

func TestLogLogin(t *testing.T) {
	logger, _ := NewAuditLogger("/tmp/test_audit_login.log")
	
	logger.LogLogin("user123", "192.168.1.1", "Mozilla/5.0")
	
	assert.Len(t, logger.entries, 1)
	assert.Equal(t, ActionLogin, logger.entries[0].Action)
	assert.Equal(t, "192.168.1.1", logger.entries[0].IPAddress)
}

func TestLogView(t *testing.T) {
	logger, _ := NewAuditLogger("/tmp/test_audit_view.log")
	
	logger.LogView("user123", "document", "doc456")
	
	assert.Len(t, logger.entries, 1)
	assert.Equal(t, ActionView, logger.entries[0].Action)
}

func TestQuery(t *testing.T) {
	logger, _ := NewAuditLogger("/tmp/test_audit_query.log")
	
	logger.LogCreate("user1", "product", "p1", nil)
	logger.LogUpdate("user2", "product", "p2", nil)
	logger.LogDelete("user1", "order", "o1")
	
	// Query by user
	results := logger.Query("user1", "", nil, nil, nil, 0)
	assert.Len(t, results, 2)
	
	// Query by resource type
	results = logger.Query("", "product", nil, nil, nil, 0)
	assert.Len(t, results, 2)
	
	// Query by action
	results = logger.Query("", "", []ActionType{ActionCreate}, nil, nil, 0)
	assert.Len(t, results, 1)
}

func TestQueryByTimeRange(t *testing.T) {
	logger, _ := NewAuditLogger("/tmp/test_audit_time.log")
	
	// Wait a bit to ensure timestamp difference
	time.Sleep(10 * time.Millisecond)
	
	logger.LogCreate("user1", "product", "p1", nil)
	time.Sleep(10 * time.Millisecond)
	logger.LogCreate("user1", "product", "p2", nil)
	
	start := time.Now().Add(-100 * time.Millisecond)
	end := time.Now().Add(100 * time.Millisecond)
	
	results := logger.Query("", "product", nil, &start, &end, 0)
	assert.GreaterOrEqual(t, len(results), 2)
}

func TestGetEntriesByResource(t *testing.T) {
	logger, _ := NewAuditLogger("/tmp/test_audit_resource.log")
	
	logger.LogCreate("user1", "product", "p1", nil)
	logger.LogUpdate("user1", "product", "p1", nil)
	logger.LogDelete("user1", "product", "p1")
	logger.LogCreate("user1", "order", "o1", nil)
	
	results := logger.GetEntriesByResource("product", "p1")
	assert.Len(t, results, 3)
}

func TestExport(t *testing.T) {
	logger, _ := NewAuditLogger("/tmp/test_audit_export.log")
	
	logger.LogCreate("user1", "product", "p1", map[string]interface{}{
		"name": "Test",
	})
	
	data, err := logger.Export(nil, nil)
	assert.NoError(t, err)
	assert.Contains(t, string(data), "CREATE")
	assert.Contains(t, string(data), "product")
}

func TestAuditEntryFields(t *testing.T) {
	entry := AuditEntry{
		ID:           "test-id",
		Timestamp:    time.Now(),
		UserID:       "user123",
		Action:       ActionCreate,
		ResourceType: "product",
		ResourceID:   "prod456",
		Description:  "Created product",
	}
	
	assert.Equal(t, "test-id", entry.ID)
	assert.False(t, entry.Timestamp.IsZero())
	assert.Equal(t, ActionCreate, entry.Action)
}