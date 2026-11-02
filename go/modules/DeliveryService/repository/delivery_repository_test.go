package repository

import (
	"context"
	"testing"
	"time"

	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"

	"community.local/modules/DeliveryService/entity"
)

func newRepo() *InMemoryDeliveryRepository {
	return NewInMemoryDeliveryRepository()
}

func newNow() time.Time {
	return time.Now().Truncate(time.Microsecond)
}

func TestInMemoryDeliveryRepository_CreateDeliveryPerson(t *testing.T) {
	repo := newRepo()
	now := newNow()
	person := &entity.DeliveryPerson{
		ID:              "person-1",
		UserID:          "user-1",
		Name:            "John Doe",
		Phone:           "1234567890",
		VehicleType:     "motorcycle",
		Status:          "available",
		IsOnline:        true,
		CurrentLat:      10.0,
		CurrentLng:      20.0,
		Rating:          4.5,
		TotalDeliveries: 100,
		AcceptRadius:    5.0,
		CreatedAt:       now,
		UpdatedAt:       now,
	}
	err := repo.CreateDeliveryPerson(context.Background(), person)
	require.NoError(t, err)

	retrieved, err := repo.GetDeliveryPerson(context.Background(), "person-1")
	require.NoError(t, err)
	assert.Equal(t, person, retrieved)
}

func TestInMemoryDeliveryRepository_GetDeliveryPerson_NotFound(t *testing.T) {
	repo := newRepo()
	retrieved, err := repo.GetDeliveryPerson(context.Background(), "nonexistent")
	require.NoError(t, err)
	assert.Nil(t, retrieved)
}

func TestInMemoryDeliveryRepository_UpdateDeliveryPerson(t *testing.T) {
	repo := newRepo()
	now := newNow()
	person := &entity.DeliveryPerson{
		ID:              "person-1",
		UserID:          "user-1",
		Name:            "John Doe",
		Phone:           "123",
		VehicleType:     "motorcycle",
		Status:          "available",
		IsOnline:        true,
		CurrentLat:      10.0,
		CurrentLng:      20.0,
		Rating:          4.5,
		TotalDeliveries: 100,
		AcceptRadius:    5.0,
		CreatedAt:       now,
		UpdatedAt:       now,
	}
	require.NoError(t, repo.CreateDeliveryPerson(context.Background(), person))

	updated := *person
	updated.Name = "Jane Doe"
	updated.Phone = "9876543210"
	updated.UpdatedAt = now.Add(time.Hour)
	err := repo.UpdateDeliveryPerson(context.Background(), &updated)
	require.NoError(t, err)

	retrieved, err := repo.GetDeliveryPerson(context.Background(), "person-1")
	require.NoError(t, err)
	assert.Equal(t, &updated, retrieved)
}

func TestInMemoryDeliveryRepository_GetAllDeliveryPersons(t *testing.T) {
	repo := newRepo()
	now := newNow()
	persons := []*entity.DeliveryPerson{
		{ID: "p1", UserID: "u1", Name: "John", VehicleType: "bike", Status: "available", IsOnline: true, CreatedAt: now, UpdatedAt: now},
		{ID: "p2", UserID: "u2", Name: "Jane", VehicleType: "car", Status: "busy", IsOnline: false, CreatedAt: now, UpdatedAt: now},
	}
	for _, p := range persons {
		require.NoError(t, repo.CreateDeliveryPerson(context.Background(), p))
	}
	all, err := repo.GetAllDeliveryPersons(context.Background())
	require.NoError(t, err)
	assert.ElementsMatch(t, persons, all)
}

func TestInMemoryDeliveryRepository_GetOnlineDeliveryPersons(t *testing.T) {
	repo := newRepo()
	now := newNow()
	persons := []*entity.DeliveryPerson{
		{ID: "p1", UserID: "u1", Name: "John", IsOnline: true, CreatedAt: now, UpdatedAt: now},
		{ID: "p2", UserID: "u2", Name: "Jane", IsOnline: false, CreatedAt: now, UpdatedAt: now},
		{ID: "p3", UserID: "u3", Name: "Bob", IsOnline: true, CreatedAt: now, UpdatedAt: now},
	}
	for _, p := range persons {
		require.NoError(t, repo.CreateDeliveryPerson(context.Background(), p))
	}
	online, err := repo.GetOnlineDeliveryPersons(context.Background())
	require.NoError(t, err)
	expected := []*entity.DeliveryPerson{persons[0], persons[2]}
	assert.ElementsMatch(t, expected, online)
}

func TestInMemoryDeliveryRepository_CreateDeliveryOrder(t *testing.T) {
	repo := newRepo()
	now := newNow()
	order := &entity.DeliveryOrder{
		ID:                 "order-1",
		OrderID:            "ord-1",
		DeliveryPersonID:   "person-1",
		RestaurantLat:      10.0,
		RestaurantLng:      20.0,
		RestaurantAddr:     "123 Main St",
		CustomerLat:        30.0,
		CustomerLng:        40.0,
		CustomerAddr:       "456 Elm St",
		Status:             "pending",
		Distance:           5.5,
		EstimatedTime:      30,
		CreatedAt:          now,
		UpdatedAt:          now,
	}
	err := repo.CreateDeliveryOrder(context.Background(), order)
	require.NoError(t, err)

	retrieved, err := repo.GetDeliveryOrder(context.Background(), "order-1")
	require.NoError(t, err)
	assert.Equal(t, order, retrieved)
}

func TestInMemoryDeliveryRepository_GetDeliveryOrder_NotFound(t *testing.T) {
	repo := newRepo()
	retrieved, err := repo.GetDeliveryOrder(context.Background(), "nonexistent")
	require.NoError(t, err)
	assert.Nil(t, retrieved)
}

func TestInMemoryDeliveryRepository_UpdateDeliveryOrder(t *testing.T) {
	repo := newRepo()
	now := newNow()
	order := &entity.DeliveryOrder{
		ID:                 "order-1",
		OrderID:            "ord-1",
		DeliveryPersonID:   "person-1",
		RestaurantLat:      10.0,
		RestaurantLng:      20.0,
		RestaurantAddr:     "123 Main St",
		CustomerLat:        30.0,
		CustomerLng:        40.0,
		CustomerAddr:       "456 Elm St",
		Status:             "pending",
		Distance:           5.5,
		EstimatedTime:      30,
		CreatedAt:          now,
		UpdatedAt:          now,
	}
	require.NoError(t, repo.CreateDeliveryOrder(context.Background(), order))

	updated := *order
	updated.Status = "assigned"
	updated.UpdatedAt = now.Add(time.Hour)
	err := repo.UpdateDeliveryOrder(context.Background(), &updated)
	require.NoError(t, err)

	retrieved, err := repo.GetDeliveryOrder(context.Background(), "order-1")
	require.NoError(t, err)
	assert.Equal(t, &updated, retrieved)
}

func TestInMemoryDeliveryRepository_GetOrdersByStatus(t *testing.T) {
	repo := newRepo()
	now := newNow()
	orders := []*entity.DeliveryOrder{
		{ID: "o1", OrderID: "ord1", DeliveryPersonID: "p1", Status: "pending", CreatedAt: now, UpdatedAt: now},
		{ID: "o2", OrderID: "ord2", DeliveryPersonID: "p1", Status: "assigned", CreatedAt: now, UpdatedAt: now},
		{ID: "o3", OrderID: "ord3", DeliveryPersonID: "p2", Status: "pending", CreatedAt: now, UpdatedAt: now},
	}
	for _, o := range orders {
		require.NoError(t, repo.CreateDeliveryOrder(context.Background(), o))
	}
	pending, err := repo.GetOrdersByStatus(context.Background(), "pending")
	require.NoError(t, err)
	assert.ElementsMatch(t, []*entity.DeliveryOrder{orders[0], orders[2]}, pending)
}

func TestInMemoryDeliveryRepository_GetOrdersByDeliveryPerson(t *testing.T) {
	repo := newRepo()
	now := newNow()
	orders := []*entity.DeliveryOrder{
		{ID: "o1", OrderID: "ord1", DeliveryPersonID: "p1", Status: "pending", CreatedAt: now, UpdatedAt: now},
		{ID: "o2", OrderID: "ord2", DeliveryPersonID: "p1", Status: "assigned", CreatedAt: now, UpdatedAt: now},
		{ID: "o3", OrderID: "ord3", DeliveryPersonID: "p2", Status: "pending", CreatedAt: now, UpdatedAt: now},
	}
	for _, o := range orders {
		require.NoError(t, repo.CreateDeliveryOrder(context.Background(), o))
	}
	p1Orders, err := repo.GetOrdersByDeliveryPerson(context.Background(), "p1")
	require.NoError(t, err)
	assert.ElementsMatch(t, []*entity.DeliveryOrder{orders[0], orders[1]}, p1Orders)
	p2Orders, err := repo.GetOrdersByDeliveryPerson(context.Background(), "p2")
	require.NoError(t, err)
	assert.ElementsMatch(t, []*entity.DeliveryOrder{orders[2]}, p2Orders)
	empty, err := repo.GetOrdersByDeliveryPerson(context.Background(), "pX")
	require.NoError(t, err)
	assert.Empty(t, empty)
}
