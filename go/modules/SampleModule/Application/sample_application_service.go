// Sample Application Service (Use Case)
package application

import (
	"errors"

	"community.local/modules/SampleModule/Domain"
	"community.local/modules/SampleModule/Contracts"
)

// SampleApplicationService contains business logic and orchestrates domain objects
type SampleApplicationService struct {
	domainService *Domain.SampleDomainService
	repository    Contracts.SampleRepositoryInterface
}

// NewSampleApplicationService creates a new SampleApplicationService
func NewSampleApplicationService(domainService *Domain.SampleDomainService, repository Contracts.SampleRepositoryInterface) *SampleApplicationService {
	return &SampleApplicationService{
		domainService: domainService,
		repository:    repository,
	}
}

// CreateSample validates input and creates a sample entity via the repository
func (s *SampleApplicationService) CreateSample(name string, value int) error {
	// Business logic validation
	if name == "" {
		return errors.New("name cannot be empty")
	}
	if value < 0 {
		return errors.New("value must be non-negative")
	}

	// Create domain entity
	entity := &Domain.SampleEntity{
		Name:  name,
		Value: value,
	}

	// Persist via repository
	return s.repository.Save(entity)
}

// GetSampleById retrieves a sample entity by ID
func (s *SampleApplicationService) GetSampleById(id int) (*Domain.SampleEntity, error) {
	return s.repository.FindById(id)
}
