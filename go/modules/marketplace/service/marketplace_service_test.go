package service

import (
	"os"
	"testing"

	"community.local/modules/marketplace/entity"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"
)

func TestMarketplaceService_CreateProduct(t *testing.T) {
	tmpFile := t.TempDir() + "/test_marketplace.json"
	defer os.Remove(tmpFile)

	svc, err := NewMarketplaceService(tmpFile)
	require.NoError(t, err)

	req := &entity.CreateProductRequest{
		Title:       "Test Product",
		Description: "Test Description",
		Price:       100.00,
		Currency:    "VND",
		Category:    "electronics",
		Condition:   "new",
		Images:      []string{"img1.jpg"},
		Tags:        []string{"tag1", "tag2"},
	}

	product, err := svc.CreateProduct(req, "seller_123")
	require.NoError(t, err)
	assert.NotEmpty(t, product.ID)
	assert.Equal(t, "Test Product", product.Title)
	assert.Equal(t, "active", product.Status)
	assert.Equal(t, "seller_123", product.SellerID)
}

func TestMarketplaceService_GetProduct(t *testing.T) {
	tmpFile := t.TempDir() + "/test_marketplace.json"
	defer os.Remove(tmpFile)

	svc, err := NewMarketplaceService(tmpFile)
	require.NoError(t, err)

	req := &entity.CreateProductRequest{
		Title:       "Test Product",
		Description: "Test Description",
		Price:       100.00,
		Currency:    "VND",
		Category:    "electronics",
		Condition:   "new",
	}

	created, err := svc.CreateProduct(req, "seller_123")
	require.NoError(t, err)

	product, err := svc.GetProduct(created.ID)
	require.NoError(t, err)
	assert.Equal(t, created.ID, product.ID)
	assert.Equal(t, "Test Product", product.Title)
}

func TestMarketplaceService_GetProduct_NotFound(t *testing.T) {
	tmpFile := t.TempDir() + "/test_marketplace.json"
	defer os.Remove(tmpFile)

	svc, err := NewMarketplaceService(tmpFile)
	require.NoError(t, err)

	_, err = svc.GetProduct("nonexistent")
	assert.Error(t, err)
	assert.Contains(t, err.Error(), "not found")
}

func TestMarketplaceService_UpdateProduct(t *testing.T) {
	tmpFile := t.TempDir() + "/test_marketplace.json"
	defer os.Remove(tmpFile)

	svc, err := NewMarketplaceService(tmpFile)
	require.NoError(t, err)

	req := &entity.CreateProductRequest{
		Title:       "Original Title",
		Description: "Original Description",
		Price:       100.00,
		Currency:    "VND",
		Category:    "electronics",
		Condition:   "new",
	}

	created, err := svc.CreateProduct(req, "seller_123")
	require.NoError(t, err)

	updateReq := &entity.UpdateProductRequest{
		Title:       "Updated Title",
		Description: "Updated Description",
		Price:       150.00,
	}

	updated, err := svc.UpdateProduct(created.ID, updateReq)
	require.NoError(t, err)
	assert.Equal(t, "Updated Title", updated.Title)
	assert.Equal(t, 150.00, updated.Price)
}

func TestMarketplaceService_DeleteProduct(t *testing.T) {
	tmpFile := t.TempDir() + "/test_marketplace.json"
	defer os.Remove(tmpFile)

	svc, err := NewMarketplaceService(tmpFile)
	require.NoError(t, err)

	req := &entity.CreateProductRequest{
		Title:       "Test Product",
		Description: "Test Description",
		Price:       100.00,
		Currency:    "VND",
		Category:    "electronics",
		Condition:   "new",
	}

	created, err := svc.CreateProduct(req, "seller_123")
	require.NoError(t, err)

	err = svc.DeleteProduct(created.ID, "seller_123")
	require.NoError(t, err)

	_, err = svc.GetProduct(created.ID)
	assert.Error(t, err)
}

func TestMarketplaceService_DeleteProduct_Sold(t *testing.T) {
	tmpFile := t.TempDir() + "/test_marketplace.json"
	defer os.Remove(tmpFile)

	svc, err := NewMarketplaceService(tmpFile)
	require.NoError(t, err)

	req := &entity.CreateProductRequest{
		Title:       "Test Product",
		Description: "Test Description",
		Price:       100.00,
		Currency:    "VND",
		Category:    "electronics",
		Condition:   "new",
	}

	created, err := svc.CreateProduct(req, "seller_123")
	require.NoError(t, err)

	_, err = svc.BuyNow(created.ID, "buyer_123")
	require.NoError(t, err)

	err = svc.DeleteProduct(created.ID, "seller_123")
	assert.Error(t, err)
	assert.Contains(t, err.Error(), "sold")
}

func TestMarketplaceService_ListProducts(t *testing.T) {
	tmpFile := t.TempDir() + "/test_marketplace.json"
	defer os.Remove(tmpFile)

	svc, err := NewMarketplaceService(tmpFile)
	require.NoError(t, err)

	products := []entity.CreateProductRequest{
		{Title: "Product 1", Price: 100, Category: "electronics", Condition: "new"},
		{Title: "Product 2", Price: 200, Category: "electronics", Condition: "used"},
		{Title: "Product 3", Price: 300, Category: "clothing", Condition: "new"},
	}

	for i, p := range products {
		_, err := svc.CreateProduct(&p, "seller")
		require.NoError(t, err, "failed to create product %d", i+1)
	}

	listReq := &entity.ListProductsRequest{
		Category: "electronics",
		Limit:    10,
	}

	results, err := svc.ListProducts(listReq)
	require.NoError(t, err)
	assert.Len(t, results, 2)
}

func TestMarketplaceService_ListProducts_Search(t *testing.T) {
	tmpFile := t.TempDir() + "/test_marketplace.json"
	defer os.Remove(tmpFile)

	svc, err := NewMarketplaceService(tmpFile)
	require.NoError(t, err)

	_, err = svc.CreateProduct(&entity.CreateProductRequest{
		Title:       "iPhone 14 Pro",
		Description: "Apple smartphone",
		Price:       1000,
		Category:    "electronics",
		Condition:   "new",
		Tags:        []string{"apple", "smartphone"},
	}, "seller")
	require.NoError(t, err)

	_, err = svc.CreateProduct(&entity.CreateProductRequest{
		Title:       "Samsung Galaxy S23",
		Description: "Samsung smartphone",
		Price:       900,
		Category:    "electronics",
		Condition:   "new",
		Tags:        []string{"samsung", "smartphone"},
	}, "seller")
	require.NoError(t, err)

	results, err := svc.ListProducts(&entity.ListProductsRequest{Search: "iPhone"})
	require.NoError(t, err)
	assert.Len(t, results, 1)
	assert.Equal(t, "iPhone 14 Pro", results[0].Title)
}

func TestMarketplaceService_PlaceBid(t *testing.T) {
	tmpFile := t.TempDir() + "/test_marketplace.json"
	defer os.Remove(tmpFile)

	svc, err := NewMarketplaceService(tmpFile)
	require.NoError(t, err)

	product, err := svc.CreateProduct(&entity.CreateProductRequest{
		Title:     "Test Product",
		Price:     100.00,
		Currency:  "VND",
		Category:  "electronics",
		Condition: "new",
	}, "seller_123")
	require.NoError(t, err)

	bidReq := &entity.PlaceBidRequest{
		ProductID: product.ID,
		Amount:    150.00,
		Currency:  "VND",
	}

	bid, err := svc.PlaceBid(bidReq, "bidder_456")
	require.NoError(t, err)
	assert.NotEmpty(t, bid.ID)
	assert.Equal(t, 150.00, bid.Amount)
	assert.Equal(t, "bidder_456", bid.BidderID)
}

func TestMarketplaceService_PlaceBid_OwnProduct(t *testing.T) {
	tmpFile := t.TempDir() + "/test_marketplace.json"
	defer os.Remove(tmpFile)

	svc, err := NewMarketplaceService(tmpFile)
	require.NoError(t, err)

	product, err := svc.CreateProduct(&entity.CreateProductRequest{
		Title:     "Test Product",
		Price:     100.00,
		Currency:  "VND",
		Category:  "electronics",
		Condition: "new",
	}, "seller_123")
	require.NoError(t, err)

	bidReq := &entity.PlaceBidRequest{
		ProductID: product.ID,
		Amount:    150.00,
		Currency:  "VND",
	}

	_, err = svc.PlaceBid(bidReq, "seller_123")
	assert.Error(t, err)
	assert.Contains(t, err.Error(), "own product")
}

func TestMarketplaceService_PlaceBid_LowAmount(t *testing.T) {
	tmpFile := t.TempDir() + "/test_marketplace.json"
	defer os.Remove(tmpFile)

	svc, err := NewMarketplaceService(tmpFile)
	require.NoError(t, err)

	product, err := svc.CreateProduct(&entity.CreateProductRequest{
		Title:     "Test Product",
		Price:     100.00,
		Currency:  "VND",
		Category:  "electronics",
		Condition: "new",
	}, "seller_123")
	require.NoError(t, err)

	bidReq := &entity.PlaceBidRequest{
		ProductID: product.ID,
		Amount:    50.00,
		Currency:  "VND",
	}

	_, err = svc.PlaceBid(bidReq, "bidder_456")
	assert.Error(t, err)
	assert.Contains(t, err.Error(), "at least the starting price")
}

func TestMarketplaceService_AcceptBid(t *testing.T) {
	tmpFile := t.TempDir() + "/test_marketplace.json"
	defer os.Remove(tmpFile)

	svc, err := NewMarketplaceService(tmpFile)
	require.NoError(t, err)

	product, err := svc.CreateProduct(&entity.CreateProductRequest{
		Title:     "Test Product",
		Price:     100.00,
		Currency:  "VND",
		Category:  "electronics",
		Condition: "new",
	}, "seller_123")
	require.NoError(t, err)

	bidReq := &entity.PlaceBidRequest{
		ProductID: product.ID,
		Amount:    150.00,
		Currency:  "VND",
	}

	bid, err := svc.PlaceBid(bidReq, "bidder_456")
	require.NoError(t, err)

	tx, err := svc.AcceptBid(bid.ID, "seller_123")
	require.NoError(t, err)
	assert.NotEmpty(t, tx.ID)
	assert.Equal(t, "pending", tx.Status)
}

func TestMarketplaceService_BuyNow(t *testing.T) {
	tmpFile := t.TempDir() + "/test_marketplace.json"
	defer os.Remove(tmpFile)

	svc, err := NewMarketplaceService(tmpFile)
	require.NoError(t, err)

	product, err := svc.CreateProduct(&entity.CreateProductRequest{
		Title:     "Test Product",
		Price:     100.00,
		Currency:  "VND",
		Category:  "electronics",
		Condition: "new",
	}, "seller_123")
	require.NoError(t, err)

	tx, err := svc.BuyNow(product.ID, "buyer_456")
	require.NoError(t, err)
	assert.NotEmpty(t, tx.ID)
	assert.Equal(t, 100.00, tx.Price)
	assert.Equal(t, "pending", tx.Status)
}

func TestMarketplaceService_CompleteTransaction(t *testing.T) {
	tmpFile := t.TempDir() + "/test_marketplace.json"
	defer os.Remove(tmpFile)

	svc, err := NewMarketplaceService(tmpFile)
	require.NoError(t, err)

	product, err := svc.CreateProduct(&entity.CreateProductRequest{
		Title:     "Test Product",
		Price:     100.00,
		Currency:  "VND",
		Category:  "electronics",
		Condition: "new",
	}, "seller_123")
	require.NoError(t, err)

	tx, err := svc.BuyNow(product.ID, "buyer_456")
	require.NoError(t, err)

	completed, err := svc.CompleteTransaction(tx.ID)
	require.NoError(t, err)
	assert.Equal(t, "completed", completed.Status)
	assert.NotNil(t, completed.CompletedAt)
}

func TestMarketplaceService_GetUserProducts(t *testing.T) {
	tmpFile := t.TempDir() + "/test_marketplace.json"
	defer os.Remove(tmpFile)

	svc, err := NewMarketplaceService(tmpFile)
	require.NoError(t, err)

	_, err = svc.CreateProduct(&entity.CreateProductRequest{
		Title: "Product 1", Price: 100, Category: "a", Condition: "new",
	}, "user_123")
	require.NoError(t, err)

	_, err = svc.CreateProduct(&entity.CreateProductRequest{
		Title: "Product 2", Price: 200, Category: "b", Condition: "new",
	}, "user_123")
	require.NoError(t, err)

	_, err = svc.CreateProduct(&entity.CreateProductRequest{
		Title: "Product 3", Price: 300, Category: "c", Condition: "new",
	}, "user_456")
	require.NoError(t, err)

	products, err := svc.GetUserProducts("user_123")
	require.NoError(t, err)
	assert.Len(t, products, 2)
}

func TestMarketplaceService_GetCategories(t *testing.T) {
	tmpFile := t.TempDir() + "/test_marketplace.json"
	defer os.Remove(tmpFile)

	svc, err := NewMarketplaceService(tmpFile)
	require.NoError(t, err)

	_, err = svc.CreateProduct(&entity.CreateProductRequest{
		Title: "Product 1", Price: 100, Category: "electronics", Condition: "new",
	}, "seller")
	require.NoError(t, err)

	_, err = svc.CreateProduct(&entity.CreateProductRequest{
		Title: "Product 2", Price: 200, Category: "electronics", Condition: "new",
	}, "seller")
	require.NoError(t, err)

	_, err = svc.CreateProduct(&entity.CreateProductRequest{
		Title: "Product 3", Price: 300, Category: "clothing", Condition: "new",
	}, "seller")
	require.NoError(t, err)

	categories, err := svc.GetCategories()
	require.NoError(t, err)
	assert.Len(t, categories, 2)
	assert.Contains(t, categories, "electronics")
	assert.Contains(t, categories, "clothing")
}
