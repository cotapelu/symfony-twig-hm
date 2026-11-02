// Sample Repository implementing data access
package infrastructure

import (
	"errors"

	"community.local/modules/SampleModule/Domain"
)

// SampleRepository implements the repository contract for SampleEntity
type SampleRepository struct {
	// In a real implementation, this would use a database connection
	storage map[int]*Domain.SampleEntity
	nextID  int
}

// NewSampleRepository creates a new SampleRepository
func NewSampleRepository() *SampleRepository {
	return &SampleRepository{
		storage: make(map[int]*Domain.SampleEntity),
		nextID:  1,
	}
}

// Save persists a SampleEntity
func (r *SampleRepository) Save(entity *Domain.SampleEntity) error {
	if entity == nil {
		return errors.New("entity cannot be nil")
	}
	if entity.ID == 0 {
		// New entity
		entity.ID = r.nextID
		r.nextID++
	}
	r.storage[entity.ID] = entity
	return nil
}

// FindById retrieves a SampleEntity by ID
func (r *SampleRepository) FindById(id int) (*Domain.SampleEntity, error) {
	if entity, exists := r.storage[id]; exists {
		return entity, nil
	}
	return nil, errors.New("entity not found")
}

// FindAll retrieves all SampleEntities
func (r *SampleRepository) FindAll() ([]*Domain.SampleEntity, error) {
	entities := make([]*Domain.SampleEntity, 0, len(r.storage))
	for _, entity := range r.storage {
		entities = append(entities, entity)
	}
	return entities, nil
}

// DeleteById removes a SampleEntity by ID
func (r *SampleRepository) DeleteById(id int) error {
	if _, exists := r.storage[id]; !exists {
		return errors.New("entity not found")
	}
	delete(r.storage, id)
	return nil
}