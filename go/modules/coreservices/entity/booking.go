package entity

import "time"

type Booking struct {
	ID              string    `json:"id"`
	ServiceType     string    `json:"service_type"`
	UserID          string    `json:"user_id"`
	ProviderID      string    `json:"provider_id"`
	PickupLocation  Location  `json:"pickup_location"`
	DropoffLocation Location  `json:"dropoff_location"`
	Status          string    `json:"status"`
	ScheduledAt     time.Time `json:"scheduled_at"`
	CreatedAt       time.Time `json:"created_at"`
	UpdatedAt       time.Time `json:"updated_at"`
}

type ServiceProvider struct {
	ID          string    `json:"id"`
	Name        string    `json:"name"`
	ServiceType string    `json:"service_type"`
	Location    Location  `json:"location"`
	Rating      float64   `json:"rating"`
	IsActive    bool      `json:"is_active"`
	CreatedAt   time.Time `json:"created_at"`
}

type Location struct {
	Latitude  float64 `json:"latitude"`
	Longitude float64 `json:"longitude"`
	Address   string  `json:"address,omitempty"`
}
