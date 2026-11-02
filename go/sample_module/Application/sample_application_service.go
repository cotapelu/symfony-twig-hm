// Sample Application Service (Use Case)
package application

import (
	"errors"

	"example.com/module/sample/domain"
	"example.com/module/sample/contracts"
)

// SampleApplicationService contains business logic and orchestrates domain objects
type SampleApplicationService struct {
	domainService *domain.SampleDomainService
	repository    contracts.SampleRepositoryInterface
}

// NewSampleApplicationService creates a new SampleApplicationService
func NewSampleApplicationService(domainService *domain.SampleDomainService, repository contracts.SampleRepositoryInterface) *SampleApplicationService {
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
	entity := &domain.SampleEntity{
		Name:  name,
		Value: value,
	}

	// Persist via repository
	return s.repository.Save(entity)
}

// GetSampleById retrieves a sample entity by ID
func (s *SampleApplicationService) GetSampleById(id int) (*domain.SampleEntity, error) {
	return s.repository.FindById(id)
}