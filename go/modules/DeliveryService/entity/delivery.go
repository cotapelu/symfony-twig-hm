package entity

import "time"

type DeliveryPerson struct {
	ID              string    `json:"id"`
	UserID          string    `json:"user_id"`
	Name            string    `json:"name"`
	Phone           string    `json:"phone"`
	VehicleType     string    `json:"vehicle_type"`
	Status          string    `json:"status"`
	IsOnline        bool      `json:"is_online"`
	CurrentLat      float64   `json:"current_latitude"`
	CurrentLng      float64   `json:"current_longitude"`
	Rating          float64   `json:"rating"`
	TotalDeliveries int       `json:"total_deliveries"`
	AcceptRadius    float64   `json:"accept_radius_km"`
	CreatedAt       time.Time `json:"created_at"`
	UpdatedAt       time.Time `json:"updated_at"`
}

type DeliveryOrder struct {
	ID               string    `json:"id"`
	OrderID          string    `json:"order_id"`
	DeliveryPersonID string    `json:"delivery_person_id"`
	RestaurantLat    float64   `json:"restaurant_latitude"`
	RestaurantLng    float64   `json:"restaurant_longitude"`
	RestaurantAddr   string    `json:"restaurant_address"`
	CustomerLat      float64   `json:"customer_latitude"`
	CustomerLng      float64   `json:"customer_longitude"`
	CustomerAddr     string    `json:"customer_address"`
	Status           string    `json:"status"`
	Distance         float64   `json:"distance_km"`
	EstimatedTime    int       `json:"estimated_minutes"`
	StartedAt        time.Time `json:"started_at"`
	DeliveredAt      time.Time `json:"delivered_at"`
	CreatedAt        time.Time `json:"created_at"`
	UpdatedAt        time.Time `json:"updated_at"`
}

type Location struct {
	Latitude  float64 `json:"latitude"`
	Longitude float64 `json:"longitude"`
}

type DeliveryAssignment struct {
	DeliveryOrderID  string  `json:"delivery_order_id"`
	DeliveryPersonID string  `json:"delivery_person_id"`
	Score            float64 `json:"score"`
	Reason           string  `json:"reason"`
}
