package models

type Order struct {
	ID           string
	UserID       string
	Status       string
	TotalAmount  float64
	Items        []OrderItem
	CreatedAt    string
	UpdatedAt    string
}

type OrderItem struct {
	ProductID string
	Quantity  int
	Price     float64
}
