package models

// Crypto represents a cryptocurrency or crypto asset
type Crypto struct {
	ID          string  `json:"id"`
	Name        string  `json:"name"`
	Symbol      string  `json:"symbol"`
	Price       float64 `json:"price"`
	MarketCap   float64 `json:"market_cap"`
	IsActive    bool    `json:"is_active"`
}
