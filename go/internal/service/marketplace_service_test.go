package service

import (
	"context"
	"errors"
	"testing"
	"time"

	"community.local/internal/models"
)

type mockMarketplaceRepo struct {
	items  []*models.MarketplaceItem
	orders []*models.MarketplaceOrder
}

func (m *mockMarketplaceRepo) CreateItem(ctx context.Context, item *models.MarketplaceItem) error {
	item.ID = "item-" + time.Now().Format("20060102150405")
	item.CreatedAt = time.Now()
	item.UpdatedAt = time.Now()
	m.items = append(m.items, item)
	return nil
}
func (m *mockMarketplaceRepo) GetItem(ctx context.Context, id string) (*models.MarketplaceItem, error) {
	for _, it := range m.items {
		if it.ID == id {
			return it, nil
		}
	}
	return nil, errors.New("not found")
}
func (m *mockMarketplaceRepo) GetItemsBySeller(ctx context.Context, sellerID string) ([]*models.MarketplaceItem, error) {
	var res []*models.MarketplaceItem
	for _, it := range m.items {
		if it.SellerID == sellerID {
			res = append(res, it)
		}
	}
	return res, nil
}
func (m *mockMarketplaceRepo) GetItemsByCategory(ctx context.Context, category string, limit, offset int) ([]*models.MarketplaceItem, error) {
	var res []*models.MarketplaceItem
	for _, it := range m.items {
		if it.Category == category {
			res = append(res, it)
		}
	}
	return res, nil
}
func (m *mockMarketplaceRepo) UpdateStatus(ctx context.Context, id string, status string) error {
	// Update item or order status based on ID prefix? For simplicity, update both if found
	for _, it := range m.items {
		if it.ID == id {
			it.Status = status
			it.UpdatedAt = time.Now()
			return nil
		}
	}
	for _, ord := range m.orders {
		if ord.ID == id {
			ord.Status = status
			ord.UpdatedAt = time.Now()
			return nil
		}
	}
	return errors.New("not found")
}
func (m *mockMarketplaceRepo) CreateOrder(ctx context.Context, order *models.MarketplaceOrder) error {
	order.ID = "order-" + time.Now().Format("20060102150405")
	order.CreatedAt = time.Now()
	order.UpdatedAt = time.Now()
	m.orders = append(m.orders, order)
	return nil
}
func (m *mockMarketplaceRepo) GetOrder(ctx context.Context, id string) (*models.MarketplaceOrder, error) {
	for _, o := range m.orders {
		if o.ID == id {
			return o, nil
		}
	}
	return nil, errors.New("not found")
}
func (m *mockMarketplaceRepo) GetOrdersByBuyer(ctx context.Context, buyerID string) ([]*models.MarketplaceOrder, error) {
	var res []*models.MarketplaceOrder
	for _, o := range m.orders {
		if o.BuyerID == buyerID {
			res = append(res, o)
		}
	}
	return res, nil
}
func (m *mockMarketplaceRepo) GetOrdersBySeller(ctx context.Context, sellerID string) ([]*models.MarketplaceOrder, error) {
	// Not needed for tests, return empty
	return nil, nil
}

func TestMarketplace_CreateItem(t *testing.T) {
	repo := &mockMarketplaceRepo{}
	svc := NewMarketplaceService(repo)
	ctx := context.Background()
	item, err := svc.CreateItem(ctx, "seller-1", "Electronics", "Phone", 1000)
	if err != nil {
		t.Fatalf("CreateItem failed: %v", err)
	}
	if item.SellerID != "seller-1" {
		t.Errorf("Wrong seller")
	}
	if item.Status != "ACTIVE" {
		t.Errorf("Expected ACTIVE status")
	}
}

func TestMarketplace_CreateOrder(t *testing.T) {
	repo := &mockMarketplaceRepo{}
	svc := NewMarketplaceService(repo)
	ctx := context.Background()
	order, err := svc.CreateOrder(ctx, "buyer-1", "item-1")
	if err != nil {
		t.Fatalf("CreateOrder failed: %v", err)
	}
	if order.BuyerID != "buyer-1" {
		t.Errorf("Wrong buyer")
	}
	if order.Status != "PENDING" {
		t.Errorf("Expected PENDING")
	}
}

func TestMarketplace_CancelOrder(t *testing.T) {
	repo := &mockMarketplaceRepo{
		orders: []*models.MarketplaceOrder{
			{ID: "o1", BuyerID: "b1", ItemID: "i1", Status: "PENDING"},
		},
	}
	svc := NewMarketplaceService(repo)
	ctx := context.Background()
	err := svc.CancelOrder(ctx, "o1")
	if err != nil {
		t.Fatalf("CancelOrder failed: %v", err)
	}
	if repo.orders[0].Status != "CANCELLED" {
		t.Errorf("Expected status CANCELLED, got %s", repo.orders[0].Status)
	}
}

func TestMarketplace_GetItem_Success(t *testing.T) {
	repo := &mockMarketplaceRepo{
		items: []*models.MarketplaceItem{
			{ID: "item-1", SellerID: "seller-1", Category: "Electronics", Title: "Phone", Price: 100, Status: "ACTIVE"},
		},
	}
	svc := NewMarketplaceService(repo)
	ctx := context.Background()

	item, err := svc.GetItem(ctx, "item-1")
	if err != nil {
		t.Fatalf("GetItem failed: %v", err)
	}
	if item.ID != "item-1" {
		t.Errorf("Expected item-1, got %s", item.ID)
	}
}

func TestMarketplace_GetItem_NotFound(t *testing.T) {
	repo := &mockMarketplaceRepo{}
	svc := NewMarketplaceService(repo)
	ctx := context.Background()

	_, err := svc.GetItem(ctx, "nonexistent")
	if err == nil {
		t.Fatal("Expected error, got nil")
	}
}

func TestMarketplace_GetItemsBySeller_Success(t *testing.T) {
	repo := &mockMarketplaceRepo{
		items: []*models.MarketplaceItem{
			{ID: "item-1", SellerID: "seller-1", Price: 100, Status: "ACTIVE"},
			{ID: "item-2", SellerID: "seller-1", Price: 200, Status: "ACTIVE"},
			{ID: "item-3", SellerID: "seller-2", Price: 300, Status: "ACTIVE"},
		},
	}
	svc := NewMarketplaceService(repo)
	ctx := context.Background()

	items, err := svc.GetItemsBySeller(ctx, "seller-1")
	if err != nil {
		t.Fatalf("GetItemsBySeller failed: %v", err)
	}
	if len(items) != 2 {
		t.Errorf("Expected 2 items, got %d", len(items))
	}
}

func TestMarketplace_GetItemsBySeller_EmptyResult(t *testing.T) {
	repo := &mockMarketplaceRepo{
		items: []*models.MarketplaceItem{
			{ID: "item-1", SellerID: "seller-1", Status: "ACTIVE"},
		},
	}
	svc := NewMarketplaceService(repo)
	ctx := context.Background()

	items, err := svc.GetItemsBySeller(ctx, "seller-2")
	if err != nil {
		t.Fatalf("GetItemsBySeller failed: %v", err)
	}
	if len(items) != 0 {
		t.Errorf("Expected 0 items, got %d", len(items))
	}
}

func TestMarketplace_GetItemsBySeller_Validation(t *testing.T) {
	repo := &mockMarketplaceRepo{}
	svc := NewMarketplaceService(repo)
	ctx := context.Background()

	_, err := svc.GetItemsBySeller(ctx, "")
	if err == nil {
		t.Fatal("Expected error for empty sellerID")
	}
}

func TestMarketplace_GetOrdersByBuyer_Success(t *testing.T) {
	repo := &mockMarketplaceRepo{
		orders: []*models.MarketplaceOrder{
			{ID: "order-1", BuyerID: "buyer-1", ItemID: "item-1", Status: "PENDING"},
			{ID: "order-2", BuyerID: "buyer-1", ItemID: "item-2", Status: "COMPLETED"},
			{ID: "order-3", BuyerID: "buyer-2", ItemID: "item-3", Status: "PENDING"},
		},
	}
	svc := NewMarketplaceService(repo)
	ctx := context.Background()

	orders, err := svc.GetOrdersByBuyer(ctx, "buyer-1")
	if err != nil {
		t.Fatalf("GetOrdersByBuyer failed: %v", err)
	}
	if len(orders) != 2 {
		t.Errorf("Expected 2 orders, got %d", len(orders))
	}
}

func TestMarketplace_GetOrdersByBuyer_EmptyResult(t *testing.T) {
	repo := &mockMarketplaceRepo{
		orders: []*models.MarketplaceOrder{
			{ID: "order-1", BuyerID: "buyer-1"},
		},
	}
	svc := NewMarketplaceService(repo)
	ctx := context.Background()

	orders, err := svc.GetOrdersByBuyer(ctx, "buyer-2")
	if err != nil {
		t.Fatalf("GetOrdersByBuyer failed: %v", err)
	}
	if len(orders) != 0 {
		t.Errorf("Expected 0 orders, got %d", len(orders))
	}
}

func TestMarketplace_GetOrdersByBuyer_Validation(t *testing.T) {
	repo := &mockMarketplaceRepo{}
	svc := NewMarketplaceService(repo)
	ctx := context.Background()

	_, err := svc.GetOrdersByBuyer(ctx, "")
	if err == nil {
		t.Fatal("Expected error for empty buyerID")
	}
}

func TestMarketplace_GetOrder_Success(t *testing.T) {
	repo := &mockMarketplaceRepo{
		orders: []*models.MarketplaceOrder{
			{ID: "order-1", BuyerID: "buyer-1", ItemID: "item-1", Status: "PENDING"},
		},
	}
	svc := NewMarketplaceService(repo)
	ctx := context.Background()

	order, err := svc.GetOrder(ctx, "order-1")
	if err != nil {
		t.Fatalf("GetOrder failed: %v", err)
	}
	if order.ID != "order-1" {
		t.Errorf("Expected order-1, got %s", order.ID)
	}
	if order.BuyerID != "buyer-1" {
		t.Errorf("Wrong buyer")
	}
}

func TestMarketplace_GetOrder_NotFound(t *testing.T) {
	repo := &mockMarketplaceRepo{}
	svc := NewMarketplaceService(repo)
	ctx := context.Background()

	_, err := svc.GetOrder(ctx, "nonexistent")
	if err == nil {
		t.Fatal("Expected error, got nil")
	}
}
