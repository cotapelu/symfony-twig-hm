package entity

import "time"

type Location struct {
	EntityID   string                 `json:"entity_id"`
	EntityType string                 `json:"entity_type"`
	Latitude   float64                `json:"latitude"`
	Longitude  float64                `json:"longitude"`
	Accuracy   float64                `json:"accuracy"`
	Altitude   *float64               `json:"altitude,omitempty"`
	Speed      *float64               `json:"speed,omitempty"`
	Heading    *float64               `json:"heading,omitempty"`
	Timestamp  time.Time              `json:"timestamp"`
	UpdatedAt  time.Time              `json:"updated_at"`
	Metadata   map[string]interface{} `json:"metadata,omitempty"`
}

type HistoryEntry struct {
	EntityID  string    `json:"entity_id"`
	Latitude  float64   `json:"latitude"`
	Longitude float64   `json:"longitude"`
	Timestamp time.Time `json:"timestamp"`
}

type Geofence struct {
	ID           string    `json:"id"`
	Name         string    `json:"name"`
	Latitude     float64   `json:"latitude"`
	Longitude    float64   `json:"longitude"`
	RadiusMeters float64   `json:"radius_meters"`
	EntityType   string    `json:"entity_type"`
	CreatedAt    time.Time `json:"created_at"`
}

type LocationCluster struct {
	Latitude     float64   `json:"latitude"`
	Longitude    float64   `json:"longitude"`
	VisitCount   int       `json:"visit_count"`
	LastVisited  time.Time `json:"last_visited"`
}

