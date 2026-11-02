package helpers

import (
	"testing"

	"github.com/stretchr/testify/assert"
)

func TestNewPagination(t *testing.T) {
	// Default values when invalid
	p := NewPagination(0, 0)
	assert.Equal(t, DefaultPage, p.Page)
	assert.Equal(t, DefaultLimit, p.Limit)
	assert.Equal(t, 0, p.Offset) // (1-1)*20 = 0

	// Valid inputs
	p = NewPagination(2, 50)
	assert.Equal(t, 2, p.Page)
	assert.Equal(t, 50, p.Limit)
	assert.Equal(t, 50, p.Offset) // (2-1)*50

	// Limit exceeds MaxLimit
	p = NewPagination(1, 200)
	assert.Equal(t, DefaultLimit, p.Limit) // falls back to default
}

func TestCalculateTotalPages(t *testing.T) {
	assert.Equal(t, 0, CalculateTotalPages(0, 20))
	assert.Equal(t, 0, CalculateTotalPages(100, 0))
	assert.Equal(t, 1, CalculateTotalPages(5, 10))
	assert.Equal(t, 2, CalculateTotalPages(20, 10))
	assert.Equal(t, 3, CalculateTotalPages(25, 10))
}

func TestBuildPaginatedResult(t *testing.T) {
	data := []string{"a", "b"}
	result := BuildPaginatedResult(data, 2, 10, 25)
	assert.Equal(t, data, result.Data)
	assert.Equal(t, 2, result.Pagination.CurrentPage)
	assert.Equal(t, 10, result.Pagination.PerPage)
	assert.Equal(t, 25, result.Pagination.TotalItems)
	assert.Equal(t, 3, result.Pagination.TotalPages)
}

func TestApplyPagination(t *testing.T) {
	// When query has no LIMIT
	query := "SELECT * FROM users"
	params := NewPagination(2, 10) // calculates offset=10
	newQuery, args := ApplyPagination(query, params)
	assert.Equal(t, "SELECT * FROM users LIMIT $1 OFFSET $2", newQuery)
	assert.Equal(t, []interface{}{10, 10}, args)

	// When query already has LIMIT (naive detection - suffix "LIMIT ")
	query2 := "SELECT * FROM users LIMIT "
	newQuery2, args2 := ApplyPagination(query2, &PaginationParams{Page: 1, Limit: 20})
	assert.Equal(t, query2, newQuery2) // unchanged
	assert.Nil(t, args2)

	// When query already has LIMIT without trailing space
	query3 := "SELECT * FROM users LIMIT 5"
	newQuery3, args3 := ApplyPagination(query3, &PaginationParams{Page: 1, Limit: 20})
	// Since the check looks for exact suffix "LIMIT ", this will not match and will append
	assert.NotEqual(t, query3, newQuery3)
	assert.NotNil(t, args3)
}
