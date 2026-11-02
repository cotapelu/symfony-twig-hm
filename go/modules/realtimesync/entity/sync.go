package entity

import "time"

type SyncSession struct {
	ID        string     `json:"id"`
	UserID    string     `json:"user_id"`
	OwnerID   string     `json:"owner_id"`
	Devices   []Device   `json:"devices"`
	Items     []string   `json:"items"`
	CreatedAt time.Time  `json:"created_at"`
	EndedAt   *time.Time `json:"ended_at,omitempty"`
}

type Device struct {
	DeviceID   string    `json:"device_id"`
	DeviceType string    `json:"device_type"`
	Online     bool      `json:"online"`
	LastSeen   time.Time `json:"last_seen"`
}

type SyncItem struct {
	ID        string                 `json:"id"`
	SessionID string                 `json:"session_id"`
	Type      string                 `json:"type"`
	Data      map[string]interface{} `json:"data"`
	Version   int                    `json:"version"`
	UpdatedAt time.Time              `json:"updated_at"`
	UpdatedBy *string                `json:"updated_by,omitempty"`
}

type PendingChange struct {
	ID        string                 `json:"id"`
	SessionID string                 `json:"session_id"`
	ItemID    string                 `json:"item_id"`
	Data      map[string]interface{} `json:"data"`
	Timestamp time.Time              `json:"timestamp"`
}
