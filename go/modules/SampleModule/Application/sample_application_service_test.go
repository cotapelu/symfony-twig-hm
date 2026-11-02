package application

import (
	"errors"
	"testing"

	"github.com/stretchr/testify/assert"

	"community.local/modules/SampleModule/Contracts"
	"community.local/modules/SampleModule/Domain"
)

// Ensure mockSampleRepository implements Contracts.SampleRepositoryInterface at compile time
var _ Contracts.SampleRepositoryInterface = (*mockSampleRepository)(nil)

// mockSampleRepository implements Contracts.SampleRepositoryInterface for testing.
type mockSampleRepository struct {
	storage map[int]*Domain.SampleEntity
	nextID   int
}

func (m *mockSampleRepository) Save(entity *Domain.SampleEntity) error {
	if entity == nil {
		return errors.New("entity cannot be nil")
	}
	if entity.ID == 0 {
		entity.ID = m.nextID
		m.nextID++
	}
	m.storage[entity.ID] = entity
	return nil
}

func (m *mockSampleRepository) FindById(id int) (*Domain.SampleEntity, error) {
	if e, ok := m.storage[id]; ok {
		return e, nil
	}
	return nil, errors.New("entity not found")
}

func (m *mockSampleRepository) FindAll() ([]*Domain.SampleEntity, error) {
	entities := make([]*Domain.SampleEntity, 0, len(m.storage))
	for _, e := range m.storage {
		entities = append(entities, e)
	}
	return entities, nil
}

func (m *mockSampleRepository) DeleteById(id int) error {
	if _, exists := m.storage[id]; !exists {
		return errors.New("entity not found")
	}
	delete(m.storage, id)
	return nil
}

func newMockRepository() *mockSampleRepository {
	return &mockSampleRepository{
		storage: make(map[int]*Domain.SampleEntity),
		nextID:  1,
	}
}

// mockSampleDomainService implements the domain service (if any). For now, we may not need it.
type mockSampleDomainService struct{}

func TestSampleApplicationService_CreateSample(t *testing.T) {
	repo := newMockRepository()
	// Domain service is currently unused in Create; pass nil
	svc := NewSampleApplicationService(nil, repo)

	t.Run("success", func(t *testing.T) {
		err := svc.CreateSample("My Sample", 42)
		assert.NoError(t, err)

		// Verify persisted
		entities, _ := repo.FindAll()
		assert.Len(t, entities, 1)
		assert.Equal(t, "My Sample", entities[0].Name)
		assert.Equal(t, 42, entities[0].Value)
	})

	t.Run("failure - empty name", func(t *testing.T) {
		err := svc.CreateSample("", 10)
		assert.Error(t, err)
		assert.Contains(t, err.Error(), "name cannot be empty")
	})

	t.Run("failure - negative value", func(t *testing.T) {
		err := svc.CreateSample("Valid", -5)
		assert.Error(t, err)
		assert.Contains(t, err.Error(), "value must be non-negative")
	})
}

func TestSampleApplicationService_GetSampleById(t *testing.T) {
	repo := newMockRepository()
	// Pre-populate
	entity := &Domain.SampleEntity{Name: "Existing", Value: 7}
	repo.Save(entity)

	svc := NewSampleApplicationService(nil, repo)

	t.Run("found", func(t *testing.T) {
		fetched, err := svc.GetSampleById(entity.ID)
		assert.NoError(t, err)
		assert.Equal(t, entity, fetched)
	})

	t.Run("not found", func(t *testing.T) {
		_, err := svc.GetSampleById(999)
		assert.Error(t, err)
		assert.Contains(t, err.Error(), "entity not found")
	})
}
