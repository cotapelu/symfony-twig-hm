// Sample Interface defining contracts between layers
package contracts

import (
	"example.com/module/sample/domain"
)

// SampleRepositoryInterface defines the contract for repository operations
type SampleRepositoryInterface interface {
	Save(entity *domain.SampleEntity) error
	FindById(id int) (*domain.SampleEntity, error)
	FindAll() ([]*domain.SampleEntity, error)
	DeleteById(id int) error
}