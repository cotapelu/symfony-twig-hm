package repository

import (
	"context"
	"sync"

	"community.local/modules/DeliveryService/entity"
)

type DeliveryRepository interface {
	CreateDeliveryPerson(ctx context.Context, person *entity.DeliveryPerson) error
	GetDeliveryPerson(ctx context.Context, id string) (*entity.DeliveryPerson, error)
	UpdateDeliveryPerson(ctx context.Context, person *entity.DeliveryPerson) error
	GetAllDeliveryPersons(ctx context.Context) ([]*entity.DeliveryPerson, error)
	GetOnlineDeliveryPersons(ctx context.Context) ([]*entity.DeliveryPerson, error)

	CreateDeliveryOrder(ctx context.Context, order *entity.DeliveryOrder) error
	GetDeliveryOrder(ctx context.Context, id string) (*entity.DeliveryOrder, error)
	UpdateDeliveryOrder(ctx context.Context, order *entity.DeliveryOrder) error
	GetOrdersByStatus(ctx context.Context, status string) ([]*entity.DeliveryOrder, error)
	GetOrdersByDeliveryPerson(ctx context.Context, personID string) ([]*entity.DeliveryOrder, error)
}

type InMemoryDeliveryRepository struct {
	mu              sync.RWMutex
	deliveryPersons map[string]*entity.DeliveryPerson
	deliveryOrders  map[string]*entity.DeliveryOrder
}

func NewInMemoryDeliveryRepository() *InMemoryDeliveryRepository {
	return &InMemoryDeliveryRepository{
		deliveryPersons: make(map[string]*entity.DeliveryPerson),
		deliveryOrders:  make(map[string]*entity.DeliveryOrder),
	}
}

func (r *InMemoryDeliveryRepository) CreateDeliveryPerson(ctx context.Context, person *entity.DeliveryPerson) error {
	r.mu.Lock()
	defer r.mu.Unlock()
	r.deliveryPersons[person.ID] = person
	return nil
}

func (r *InMemoryDeliveryRepository) GetDeliveryPerson(ctx context.Context, id string) (*entity.DeliveryPerson, error) {
	r.mu.RLock()
	defer r.mu.RUnlock()
	return r.deliveryPersons[id], nil
}

func (r *InMemoryDeliveryRepository) UpdateDeliveryPerson(ctx context.Context, person *entity.DeliveryPerson) error {
	r.mu.Lock()
	defer r.mu.Unlock()
	r.deliveryPersons[person.ID] = person
	return nil
}

func (r *InMemoryDeliveryRepository) GetAllDeliveryPersons(ctx context.Context) ([]*entity.DeliveryPerson, error) {
	r.mu.RLock()
	defer r.mu.RUnlock()
	persons := make([]*entity.DeliveryPerson, 0, len(r.deliveryPersons))
	for _, p := range r.deliveryPersons {
		persons = append(persons, p)
	}
	return persons, nil
}

func (r *InMemoryDeliveryRepository) GetOnlineDeliveryPersons(ctx context.Context) ([]*entity.DeliveryPerson, error) {
	r.mu.RLock()
	defer r.mu.RUnlock()
	var persons []*entity.DeliveryPerson
	for _, p := range r.deliveryPersons {
		if p.IsOnline {
			persons = append(persons, p)
		}
	}
	return persons, nil
}

func (r *InMemoryDeliveryRepository) CreateDeliveryOrder(ctx context.Context, order *entity.DeliveryOrder) error {
	r.mu.Lock()
	defer r.mu.Unlock()
	r.deliveryOrders[order.ID] = order
	return nil
}

func (r *InMemoryDeliveryRepository) GetDeliveryOrder(ctx context.Context, id string) (*entity.DeliveryOrder, error) {
	r.mu.RLock()
	defer r.mu.RUnlock()
	return r.deliveryOrders[id], nil
}

func (r *InMemoryDeliveryRepository) UpdateDeliveryOrder(ctx context.Context, order *entity.DeliveryOrder) error {
	r.mu.Lock()
	defer r.mu.Unlock()
	r.deliveryOrders[order.ID] = order
	return nil
}

func (r *InMemoryDeliveryRepository) GetOrdersByStatus(ctx context.Context, status string) ([]*entity.DeliveryOrder, error) {
	r.mu.RLock()
	defer r.mu.RUnlock()
	var orders []*entity.DeliveryOrder
	for _, o := range r.deliveryOrders {
		if o.Status == status {
			orders = append(orders, o)
		}
	}
	return orders, nil
}

func (r *InMemoryDeliveryRepository) GetOrdersByDeliveryPerson(ctx context.Context, personID string) ([]*entity.DeliveryOrder, error) {
	r.mu.RLock()
	defer r.mu.RUnlock()
	var orders []*entity.DeliveryOrder
	for _, o := range r.deliveryOrders {
		if o.DeliveryPersonID == personID {
			orders = append(orders, o)
		}
	}
	return orders, nil
}
