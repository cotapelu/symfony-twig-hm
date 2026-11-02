package repository

import (
	"context"
	"sync"

	"community.local/modules/marketplace/entity"
)

type MarketplaceRepository interface {
	CreateProduct(ctx context.Context, product *entity.Product) error
	GetProduct(ctx context.Context, id string) (*entity.Product, error)
	UpdateProduct(ctx context.Context, product *entity.Product) error
	DeleteProduct(ctx context.Context, id string) error
	ListProducts(ctx context.Context, filter entity.ListProductsRequest) ([]*entity.Product, error)
	GetProductsBySeller(ctx context.Context, sellerID string) ([]*entity.Product, error)

	CreateBid(ctx context.Context, bid *entity.Bid) error
	GetBidsByProduct(ctx context.Context, productID string) ([]*entity.Bid, error)
	GetHighestBid(ctx context.Context, productID string) (*entity.Bid, error)

	CreateTransaction(ctx context.Context, txn *entity.Transaction) error
	GetTransaction(ctx context.Context, id string) (*entity.Transaction, error)
	GetTransactionsByBuyer(ctx context.Context, buyerID string) ([]*entity.Transaction, error)
	GetTransactionsBySeller(ctx context.Context, sellerID string) ([]*entity.Transaction, error)
}

type InMemoryMarketplaceRepository struct {
	mu           sync.RWMutex
	products     map[string]*entity.Product
	bids         map[string][]*entity.Bid
	transactions map[string]*entity.Transaction
}

func NewInMemoryMarketplaceRepository() *InMemoryMarketplaceRepository {
	return &InMemoryMarketplaceRepository{
		products:     make(map[string]*entity.Product),
		bids:         make(map[string][]*entity.Bid),
		transactions: make(map[string]*entity.Transaction),
	}
}

func (r *InMemoryMarketplaceRepository) CreateProduct(ctx context.Context, product *entity.Product) error {
	r.mu.Lock()
	defer r.mu.Unlock()
	r.products[product.ID] = product
	return nil
}

func (r *InMemoryMarketplaceRepository) GetProduct(ctx context.Context, id string) (*entity.Product, error) {
	r.mu.RLock()
	defer r.mu.RUnlock()
	return r.products[id], nil
}

func (r *InMemoryMarketplaceRepository) UpdateProduct(ctx context.Context, product *entity.Product) error {
	r.mu.Lock()
	defer r.mu.Unlock()
	r.products[product.ID] = product
	return nil
}

func (r *InMemoryMarketplaceRepository) DeleteProduct(ctx context.Context, id string) error {
	r.mu.Lock()
	defer r.mu.Unlock()
	delete(r.products, id)
	return nil
}

func (r *InMemoryMarketplaceRepository) ListProducts(ctx context.Context, filter entity.ListProductsRequest) ([]*entity.Product, error) {
	r.mu.RLock()
	defer r.mu.RUnlock()
	var results []*entity.Product
	for _, p := range r.products {
		if filter.Category != "" && p.Category != filter.Category {
			continue
		}
		if filter.Condition != "" && p.Condition != filter.Condition {
			continue
		}
		if filter.SellerID != "" && p.SellerID != filter.SellerID {
			continue
		}
		if filter.MinPrice > 0 && p.Price < filter.MinPrice {
			continue
		}
		if filter.MaxPrice > 0 && p.Price > filter.MaxPrice {
			continue
		}
		if filter.Status != "" && p.Status != filter.Status {
			continue
		}
		results = append(results, p)
	}
	return results, nil
}

func (r *InMemoryMarketplaceRepository) GetProductsBySeller(ctx context.Context, sellerID string) ([]*entity.Product, error) {
	r.mu.RLock()
	defer r.mu.RUnlock()
	var results []*entity.Product
	for _, p := range r.products {
		if p.SellerID == sellerID {
			results = append(results, p)
		}
	}
	return results, nil
}

func (r *InMemoryMarketplaceRepository) CreateBid(ctx context.Context, bid *entity.Bid) error {
	r.mu.Lock()
	defer r.mu.Unlock()
	r.bids[bid.ProductID] = append(r.bids[bid.ProductID], bid)
	return nil
}

func (r *InMemoryMarketplaceRepository) GetBidsByProduct(ctx context.Context, productID string) ([]*entity.Bid, error) {
	r.mu.RLock()
	defer r.mu.RUnlock()
	return r.bids[productID], nil
}

func (r *InMemoryMarketplaceRepository) GetHighestBid(ctx context.Context, productID string) (*entity.Bid, error) {
	r.mu.RLock()
	defer r.mu.RUnlock()
	bids := r.bids[productID]
	if len(bids) == 0 {
		return nil, nil
	}
	var highest *entity.Bid
	for _, b := range bids {
		if highest == nil || b.Amount > highest.Amount {
			highest = b
		}
	}
	return highest, nil
}

func (r *InMemoryMarketplaceRepository) CreateTransaction(ctx context.Context, txn *entity.Transaction) error {
	r.mu.Lock()
	defer r.mu.Unlock()
	r.transactions[txn.ID] = txn
	return nil
}

func (r *InMemoryMarketplaceRepository) GetTransaction(ctx context.Context, id string) (*entity.Transaction, error) {
	r.mu.RLock()
	defer r.mu.RUnlock()
	return r.transactions[id], nil
}

func (r *InMemoryMarketplaceRepository) GetTransactionsByBuyer(ctx context.Context, buyerID string) ([]*entity.Transaction, error) {
	r.mu.RLock()
	defer r.mu.RUnlock()
	var results []*entity.Transaction
	for _, t := range r.transactions {
		if t.BuyerID == buyerID {
			results = append(results, t)
		}
	}
	return results, nil
}

func (r *InMemoryMarketplaceRepository) GetTransactionsBySeller(ctx context.Context, sellerID string) ([]*entity.Transaction, error) {
	r.mu.RLock()
	defer r.mu.RUnlock()
	var results []*entity.Transaction
	for _, t := range r.transactions {
		if t.SellerID == sellerID {
			results = append(results, t)
		}
	}
	return results, nil
}
