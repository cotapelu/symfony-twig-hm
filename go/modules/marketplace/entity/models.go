package entity

import "time"

type Product struct {
	ID          string     `json:"id"`
	Title       string     `json:"title"`
	Description string     `json:"description"`
	Price       float64    `json:"price"`
	Currency    string     `json:"currency"`
	Category    string     `json:"category"`
	Condition   string     `json:"condition"`
	SellerID    string     `json:"seller_id"`
	Status      string     `json:"status"`
	Images      []string   `json:"images"`
	Tags        []string   `json:"tags"`
	ViewCount   int        `json:"view_count"`
	CreatedAt   time.Time  `json:"created_at"`
	UpdatedAt   time.Time  `json:"updated_at"`
	SoldAt      *time.Time `json:"sold_at,omitempty"`
}

type Bid struct {
	ID        string    `json:"id"`
	ProductID string    `json:"product_id"`
	BidderID  string    `json:"bidder_id"`
	Amount    float64   `json:"amount"`
	Currency  string    `json:"currency"`
	Status    string    `json:"status"`
	CreatedAt time.Time `json:"created_at"`
	UpdatedAt time.Time `json:"updated_at"`
}

type Transaction struct {
	ID          string     `json:"id"`
	ProductID   string     `json:"product_id"`
	SellerID    string     `json:"seller_id"`
	BuyerID     string     `json:"buyer_id"`
	Price       float64    `json:"price"`
	Currency    string     `json:"currency"`
	Status      string     `json:"status"`
	PaymentID   string     `json:"payment_id,omitempty"`
	CreatedAt   time.Time  `json:"created_at"`
	CompletedAt *time.Time `json:"completed_at,omitempty"`
}

type CreateProductRequest struct {
	Title       string   `json:"title"`
	Description string   `json:"description"`
	Price       float64  `json:"price"`
	Currency    string   `json:"currency"`
	Category    string   `json:"category"`
	Condition   string   `json:"condition"`
	Images      []string `json:"images"`
	Tags        []string `json:"tags"`
}

type UpdateProductRequest struct {
	SellerID    string   `json:"seller_id"`
	Title       string   `json:"title,omitempty"`
	Description string   `json:"description,omitempty"`
	Price       float64  `json:"price,omitempty"`
	Category    string   `json:"category,omitempty"`
	Condition   string   `json:"condition,omitempty"`
	Images      []string `json:"images,omitempty"`
	Tags        []string `json:"tags,omitempty"`
}

type PlaceBidRequest struct {
	ProductID string  `json:"product_id"`
	Amount    float64 `json:"amount"`
	Currency  string  `json:"currency"`
}

type ListProductsRequest struct {
	Category  string  `json:"category,omitempty"`
	MinPrice  float64 `json:"min_price,omitempty"`
	MaxPrice  float64 `json:"max_price,omitempty"`
	Condition string  `json:"condition,omitempty"`
	SellerID  string  `json:"seller_id,omitempty"`
	Search    string  `json:"search,omitempty"`
	Status    string  `json:"status,omitempty"`
	Limit     int     `json:"limit,omitempty"`
	Offset    int     `json:"offset,omitempty"`
}

type ProductResponse struct {
	ID          string     `json:"id"`
	Title       string     `json:"title"`
	Description string     `json:"description"`
	Price       float64    `json:"price"`
	Currency    string     `json:"currency"`
	Category    string     `json:"category"`
	Condition   string     `json:"condition"`
	SellerID    string     `json:"seller_id"`
	Status      string     `json:"status"`
	Images      []string   `json:"images"`
	Tags        []string   `json:"tags"`
	ViewCount   int        `json:"view_count"`
	CreatedAt   time.Time  `json:"created_at"`
	UpdatedAt   time.Time  `json:"updated_at"`
	SoldAt      *time.Time `json:"sold_at,omitempty"`
}

type BidResponse struct {
	ID        string    `json:"id"`
	ProductID string    `json:"product_id"`
	BidderID  string    `json:"bidder_id"`
	Amount    float64   `json:"amount"`
	Currency  string    `json:"currency"`
	Status    string    `json:"status"`
	CreatedAt time.Time `json:"created_at"`
	UpdatedAt time.Time `json:"updated_at"`
}
