// Sample Entity representing a domain object
package domain

import (
	"time"
)

// SampleEntity represents a domain object with business rules
type SampleEntity struct {
	ID        int
	Name      string
	Value     int
	Description string
	CreatedAt time.Time
}

// NewSampleEntity creates a new SampleEntity with validation
func NewSampleEntity(name string, value int, description string) (*SampleEntity, error) {
	// Business rules validation
	if name == "" {
		return nil, ErrNameEmpty
	}
	if value < 0 {
		return nil, ErrValueNegative
	}

	return &SampleEntity{
		Name:       name,
		Value:      value,
		Description: description,
		CreatedAt:  time.Now(),
	}, nil
}

// Update modifies the entity with validation
func (e *SampleEntity) Update(name string, value int, description string) error {
	// Business rules for update
	if name == "" {
		return ErrNameEmpty
	}
	if value < 0 {
		return ErrValueNegative
	}

	e.Name = name
	e.Value = value
	e.Description = description
	return nil
}

// Domain errors
var (
	ErrNameEmpty   = errors.New("name cannot be empty")
	ErrValueNegative = errors.New("value must be non-negative")
)