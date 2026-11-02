package entity

import "time"

type UserLocation struct {
	ID        string    `json:"id"`
	UserID    string    `json:"user_id"`
	Location  Location  `json:"location"`
	UpdatedAt time.Time `json:"updated_at"`
}

type NearbyUser struct {
	UserID   string   `json:"user_id"`
	Username string   `json:"username"`
	Location Location `json:"location"`
	Distance float64  `json:"distance_km"`
}

type Location struct {
	Latitude  float64 `json:"latitude"`
	Longitude float64 `json:"longitude"`
}

type Geofence struct {
	ID        string            `json:"id"`
	Name      string            `json:"name"`
	Points    []Location        `json:"points"`
	Radius    float64           `json:"radius_km"`
	CreatedAt time.Time         `json:"created_at"`
	Metadata  map[string]string `json:"metadata,omitempty"`
}

type LocationUpdateEvent struct {
	EventType string    `json:"event_type"`
	UserID    string    `json:"user_id"`
	Latitude  float64   `json:"latitude"`
	Longitude float64   `json:"longitude"`
	Timestamp time.Time `json:"timestamp"`
}

type GeofenceTriggeredEvent struct {
	EventType    string    `json:"event_type"`
	UserID       string    `json:"user_id"`
	GeofenceID   string    `json:"geofence_id"`
	GeofenceName string    `json:"geofence_name"`
	Timestamp    time.Time `json:"timestamp"`
}
