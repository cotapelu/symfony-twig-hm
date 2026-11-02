package helpers

import (
	"math"
)

// PaginationParams represents pagination parameters
type PaginationParams struct {
	Page     int `json:"page"`
	Limit    int `json:"limit"`
	Offset   int `json:"-"`
}

// PaginationMeta represents pagination metadata
type PaginationMeta struct {
	CurrentPage int `json:"current_page"`
	PerPage     int `json:"per_page"`
	TotalItems  int `json:"total_items"`
	TotalPages  int `json:"total_pages"`
}

// PaginatedResult represents a paginated response
type PaginatedResult struct {
	Data       interface{}    `json:"data"`
	Pagination PaginationMeta `json:"pagination"`
}

const (
	DefaultPage = 1
	DefaultLimit = 20
	MaxLimit = 100
)

// NewPagination creates pagination params with defaults
func NewPagination(page, limit int) *PaginationParams {
	if page < 1 {
		page = DefaultPage
	}
	if limit < 1 || limit > MaxLimit {
		limit = DefaultLimit
	}
	
	return &PaginationParams{
		Page:   page,
		Limit:  limit,
		Offset: (page - 1) * limit,
	}
}

// CalculateTotalPages calculates total pages from count and limit
func CalculateTotalPages(count, limit int) int {
	if count == 0 || limit == 0 {
		return 0
	}
	return int(math.Ceil(float64(count) / float64(limit)))
}

// BuildPaginatedResult builds a paginated response
func BuildPaginatedResult(data interface{}, page, limit, total int) *PaginatedResult {
	return &PaginatedResult{
		Data: data,
		Pagination: PaginationMeta{
			CurrentPage: page,
			PerPage:     limit,
			TotalItems:  total,
			TotalPages:  CalculateTotalPages(total, limit),
		},
	}
}

// ApplyPagination adds LIMIT and OFFSET to query
func ApplyPagination(query string, params *PaginationParams) (string, []interface{}) {
	if params == nil {
		return query, nil
	}
	
	// Check if query already has LIMIT
	hasLimit := false
	// Simple check - in production, use proper SQL parser
	if len(query) > 6 && query[len(query)-6:] == "LIMIT " {
		hasLimit = true
	}
	
	if !hasLimit {
		query += " LIMIT $1 OFFSET $2"
		return query, []interface{}{params.Limit, params.Offset}
	}
	
	return query, nil
}