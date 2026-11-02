// Package audit provides audit trail functionality for tracking data changes
package audit

import (
	"encoding/json"
	"fmt"
	"os"
	"sync"
	"time"

	"github.com/google/uuid"
)

// ActionType represents the type of action being audited
type ActionType string

const (
	ActionCreate  ActionType = "CREATE"
	ActionUpdate  ActionType = "UPDATE"
	ActionDelete  ActionType = "DELETE"
	ActionLogin   ActionType = "LOGIN"
	ActionLogout  ActionType = "LOGOUT"
	ActionView    ActionType = "VIEW"
	ActionExport  ActionType = "EXPORT"
)

// AuditEntry represents a single audit log entry
type AuditEntry struct {
	ID          string                 `json:"id"`
	Timestamp   time.Time              `json:"timestamp"`
	UserID      string                 `json:"user_id"`
	Action      ActionType             `json:"action"`
	ResourceType string                `json:"resource_type"`
	ResourceID  string                 `json:"resource_id"`
	Description string                 `json:"description"`
	Changes     map[string]interface{} `json:"changes,omitempty"`
	Metadata    map[string]interface{} `json:"metadata,omitempty"`
	IPAddress   string                 `json:"ip_address,omitempty"`
	UserAgent   string                 `json:"user_agent,omitempty"`
}

// AuditLogger handles audit trail logging
type AuditLogger struct {
	mu       sync.Mutex
	filePath string
	entries  []*AuditEntry
}

// NewAuditLogger creates a new audit logger
func NewAuditLogger(filePath string) (*AuditLogger, error) {
	logger := &AuditLogger{
		filePath: filePath,
		entries:  make([]*AuditEntry, 0),
	}
	
	// Ensure directory exists
	dir := filePath[:len(filePath)-len(getFileName(filePath))]
	if dir != "" && dir != "." {
		os.MkdirAll(dir, 0755)
	}
	
	return logger, nil
}

func getFileName(path string) string {
	for i := len(path) - 1; i >= 0; i-- {
		if path[i] == '/' {
			return path[i+1:]
		}
	}
	return path
}

// LogCreate logs a create action
func (al *AuditLogger) LogCreate(userID, resourceType, resourceID string, changes map[string]interface{}) {
	al.logEntry(AuditEntry{
		ID:           uuid.New().String(),
		Timestamp:    time.Now(),
		UserID:       userID,
		Action:       ActionCreate,
		ResourceType: resourceType,
		ResourceID:   resourceID,
		Description:  fmt.Sprintf("Created %s with ID %s", resourceType, resourceID),
		Changes:      changes,
	})
}

// LogUpdate logs an update action
func (al *AuditLogger) LogUpdate(userID, resourceType, resourceID string, changes map[string]interface{}) {
	al.logEntry(AuditEntry{
		ID:           uuid.New().String(),
		Timestamp:    time.Now(),
		UserID:       userID,
		Action:       ActionUpdate,
		ResourceType: resourceType,
		ResourceID:   resourceID,
		Description:  fmt.Sprintf("Updated %s with ID %s", resourceType, resourceID),
		Changes:      changes,
	})
}

// LogDelete logs a delete action
func (al *AuditLogger) LogDelete(userID, resourceType, resourceID string) {
	al.logEntry(AuditEntry{
		ID:           uuid.New().String(),
		Timestamp:    time.Now(),
		UserID:       userID,
		Action:       ActionDelete,
		ResourceType: resourceType,
		ResourceID:   resourceID,
		Description:  fmt.Sprintf("Deleted %s with ID %s", resourceType, resourceID),
	})
}

// LogLogin logs a login action
func (al *AuditLogger) LogLogin(userID, ipAddress, userAgent string) {
	al.logEntry(AuditEntry{
		ID:           uuid.New().String(),
		Timestamp:    time.Now(),
		UserID:       userID,
		Action:       ActionLogin,
		ResourceType: "session",
		Description:  fmt.Sprintf("User %s logged in", userID),
		IPAddress:    ipAddress,
		UserAgent:    userAgent,
	})
}

// LogView logs a view action
func (al *AuditLogger) LogView(userID, resourceType, resourceID string) {
	al.logEntry(AuditEntry{
		ID:           uuid.New().String(),
		Timestamp:    time.Now(),
		UserID:       userID,
		Action:       ActionView,
		ResourceType: resourceType,
		ResourceID:   resourceID,
		Description:  fmt.Sprintf("Viewed %s with ID %s", resourceType, resourceID),
	})
}

// logEntry adds an entry to the audit log
func (al *AuditLogger) logEntry(entry AuditEntry) {
	al.mu.Lock()
	defer al.mu.Unlock()
	
	al.entries = append(al.entries, &entry)
	
	// Write to file asynchronously
	go al.writeToFile(entry)
}

// writeToFile writes entry to the audit log file
func (al *AuditLogger) writeToFile(entry AuditEntry) {
	al.mu.Lock()
	defer al.mu.Unlock()
	
	file, err := os.OpenFile(al.filePath, os.O_APPEND|os.O_CREATE|os.O_WRONLY, 0644)
	if err != nil {
		return
	}
	defer file.Close()
	
	data, err := json.Marshal(entry)
	if err != nil {
		return
	}
	
	file.Write(data)
	file.WriteString("\n")
}

// Query retrieves audit entries based on criteria
func (al *AuditLogger) Query(userID, resourceType string, actions []ActionType, 
	startTime, endTime *time.Time, limit int) []*AuditEntry {
	al.mu.Lock()
	defer al.mu.Unlock()
	
	var results []*AuditEntry
	
	for _, entry := range al.entries {
		// Filter by user ID
		if userID != "" && entry.UserID != userID {
			continue
		}
		
		// Filter by resource type
		if resourceType != "" && entry.ResourceType != resourceType {
			continue
		}
		
		// Filter by action
		if len(actions) > 0 {
			found := false
			for _, a := range actions {
				if entry.Action == a {
					found = true
					break
				}
			}
			if !found {
				continue
			}
		}
		
		// Filter by time range
		if startTime != nil && entry.Timestamp.Before(*startTime) {
			continue
		}
		if endTime != nil && entry.Timestamp.After(*endTime) {
			continue
		}
		
		results = append(results, entry)
		
		// Apply limit
		if limit > 0 && len(results) >= limit {
			break
		}
	}
	
	return results
}

// GetEntriesByResource retrieves all entries for a specific resource
func (al *AuditLogger) GetEntriesByResource(resourceType, resourceID string) []*AuditEntry {
	al.mu.Lock()
	defer al.mu.Unlock()
	
	var results []*AuditEntry
	
	for _, entry := range al.entries {
		if entry.ResourceType == resourceType && entry.ResourceID == resourceID {
			results = append(results, entry)
		}
	}
	
	return results
}

// Export exports audit entries to JSON
func (al *AuditLogger) Export(startTime, endTime *time.Time) ([]byte, error) {
	al.mu.Lock()
	defer al.mu.Unlock()
	
	var entries []*AuditEntry
	
	for _, entry := range al.entries {
		if startTime != nil && entry.Timestamp.Before(*startTime) {
			continue
		}
		if endTime != nil && entry.Timestamp.After(*endTime) {
			continue
		}
		entries = append(entries, entry)
	}
	
	return json.MarshalIndent(entries, "", "  ")
}