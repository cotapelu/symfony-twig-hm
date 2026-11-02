package infrastructure

import (
	"testing"
	"time"

	"github.com/stretchr/testify/assert"

	"community.local/modules/SampleModule/Domain"
	"community.local/modules/SampleModule/Contracts"
)

var _ Contracts.SampleRepositoryInterface = (*SampleRepository)(nil)

func TestSampleRepository_Save(t *testing.T) {
	repo := NewSampleRepository()

	t.Run("saves new entity with auto-increment ID", func(t *testing.T) {
		entity := &Domain.SampleEntity{
			Name:        "Test",
			Value:       100,
			Description: "Desc",
			CreatedAt:   time.Now(),
		}
		err := repo.Save(entity)
		assert.NoError(t, err)
		assert.NotZero(t, entity.ID, "ID should be set")
		assert.Equal(t, 1, repo.nextID-1, "nextID should have incremented")
	})

	t.Run("updates existing entity", func(t *testing.T) {
		// First save an entity
		entity := &Domain.SampleEntity{Name: "A", Value: 1}
		repo.Save(entity)
		id := entity.ID

		// Update
		entity.Value = 999
		err := repo.Save(entity)
		assert.NoError(t, err)
		assert.Equal(t, id, entity.ID, "ID should not change")

		// Retrieve to confirm
		fetched, err := repo.FindById(id)
		assert.NoError(t, err)
		assert.Equal(t, 999, fetched.Value)
	})

	t.Run("fails if entity is nil", func(t *testing.T) {
		err := repo.Save(nil)
		assert.Error(t, err)
		assert.Contains(t, err.Error(), "entity cannot be nil")
	})
}

func TestSampleRepository_FindById(t *testing.T) {
	repo := NewSampleRepository()

	t.Run("returns entity when exists", func(t *testing.T) {
		entity := &Domain.SampleEntity{Name: "X", Value: 5}
		repo.Save(entity)

		fetched, err := repo.FindById(entity.ID)
		assert.NoError(t, err)
		assert.Equal(t, entity, fetched)
	})

	t.Run("returns error when not found", func(t *testing.T) {
		_, err := repo.FindById(999)
		assert.Error(t, err)
		assert.Contains(t, err.Error(), "entity not found")
	})
}

func TestSampleRepository_FindAll(t *testing.T) {
	t.Run("returns all entities", func(t *testing.T) {
		repo := NewSampleRepository()
		e1 := &Domain.SampleEntity{Name: "A", Value: 1}
		e2 := &Domain.SampleEntity{Name: "B", Value: 2}
		e3 := &Domain.SampleEntity{Name: "C", Value: 3}
		repo.Save(e1)
		repo.Save(e2)
		repo.Save(e3)

		all, err := repo.FindAll()
		assert.NoError(t, err)
		assert.Len(t, all, 3)
		// Ensure they contain the expected IDs (order not guaranteed)
		ids := make(map[int]bool)
		for _, e := range all {
			ids[e.ID] = true
		}
		assert.Contains(t, ids, e1.ID)
		assert.Contains(t, ids, e2.ID)
		assert.Contains(t, ids, e3.ID)
	})

	t.Run("empty repository returns empty slice", func(t *testing.T) {
		repo := NewSampleRepository()
		all, err := repo.FindAll()
		assert.NoError(t, err)
		assert.Empty(t, all)
	})
}

func TestSampleRepository_DeleteById(t *testing.T) {
	repo := NewSampleRepository()

	t.Run("deletes existing entity", func(t *testing.T) {
		entity := &Domain.SampleEntity{Name: "Del", Value: 0}
		repo.Save(entity)
		id := entity.ID

		err := repo.DeleteById(id)
		assert.NoError(t, err)

		_, err = repo.FindById(id)
		assert.Error(t, err)
	})

	t.Run("returns error when deleting non-existent", func(t *testing.T) {
		err := repo.DeleteById(12345)
		assert.Error(t, err)
		assert.Contains(t, err.Error(), "entity not found")
	})
}
