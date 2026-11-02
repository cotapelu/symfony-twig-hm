// Sample Interface defining contracts between layers
package Contracts

import (
	"community.local/modules/SampleModule/Domain"
)

// SampleRepositoryInterface defines the contract for repository operations
type SampleRepositoryInterface interface {
	Save(entity *Domain.SampleEntity) error
	FindById(id int) (*Domain.SampleEntity, error)
	FindAll() ([]*Domain.SampleEntity, error)
	DeleteById(id int) error
}
