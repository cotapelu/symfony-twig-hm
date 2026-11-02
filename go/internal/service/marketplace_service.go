package service

import (
	"context"
	"errors"
	"time"

	"community.local/internal/models"
)

type MarketplaceService struct {
	repo models.MarketplaceRepository
}

func NewMarketplaceService(repo models.MarketplaceRepository) *MarketplaceService {
	return &MarketplaceService{repo: repo}
}

// Item operations
func (s *MarketplaceService) CreateItem(ctx context.Context, sellerID, category, title string, price float64) (*models.MarketplaceItem, error) {
	if sellerID == "" || category == "" || title == "" || price <= 0 {
		return nil, errors.New("invalid item data")
	}
	now := time.Now()
	item := &models.MarketplaceItem{
		SellerID:  sellerID,
		Category:  category,
		Title:     title,
		Price:     price,
		Status:    "ACTIVE",
		CreatedAt: now,
		UpdatedAt: now,
	}
	err := s.repo.CreateItem(ctx, item)
	if err != nil {
		return nil, err
	}
	return item, nil
}

func (s *MarketplaceService) GetItem(ctx context.Context, id string) (*models.MarketplaceItem, error) {
	item, err := s.repo.GetItem(ctx, id)
	if err != nil {
		return nil, err
	}
	if item == nil {
		return nil, errors.New("item not found")
	}
	return item, nil
}

func (s *MarketplaceService) GetItemsBySeller(ctx context.Context, sellerID string) ([]*models.MarketplaceItem, error) {
	if sellerID == "" {
		return nil, errors.New("seller ID required")
	}
	items, err := s.repo.GetItemsBySeller(ctx, sellerID)
	if err != nil {
		return nil, err
	}
	return items, nil
}

// Order operations
func (s *MarketplaceService) CreateOrder(ctx context.Context, buyerID, itemID string) (*models.MarketplaceOrder, error) {
	if buyerID == "" || itemID == "" {
		return nil, errors.New("buyer ID and item ID required")
	}
	order := &models.MarketplaceOrder{
		BuyerID:  buyerID,
		ItemID:   itemID,
		Status:   "PENDING",
		CreatedAt: time.Now(),
		UpdatedAt: time.Now(),
	}
	err := s.repo.CreateOrder(ctx, order)
	if err != nil {
		return nil, err
	}
	return order, nil
}

func (s *MarketplaceService) GetOrder(ctx context.Context, id string) (*models.MarketplaceOrder, error) {
	order, err := s.repo.GetOrder(ctx, id)
	if err != nil {
		return nil, err
	}
	if order == nil {
		return nil, errors.New("order not found")
	}
	return order, nil
}

func (s *MarketplaceService) GetOrdersByBuyer(ctx context.Context, buyerID string) ([]*models.MarketplaceOrder, error) {
	if buyerID == "" {
		return nil, errors.New("buyer ID required")
	}
	orders, err := s.repo.GetOrdersByBuyer(ctx, buyerID)
	if err != nil {
		return nil, err
	}
	return orders, nil
}

func (s *MarketplaceService) CancelOrder(ctx context.Context, id string) error {
	return s.repo.UpdateStatus(ctx, id, "CANCELLED")
}
