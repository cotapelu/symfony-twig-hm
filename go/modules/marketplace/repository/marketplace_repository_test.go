package repository

import (
	"context"
	"testing"
	"time"

	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"

	"community.local/modules/marketplace/entity"
)

func newRepo() *InMemoryMarketplaceRepository {
	return NewInMemoryMarketplaceRepository()
}

func newNow() time.Time {
	return time.Now().Truncate(time.Microsecond) // avoid monotonic time issues
}

func TestInMemoryMarketplaceRepository_CreateProduct(t *testing.T) {
	repo := newRepo()
	now := newNow()
	product := &entity.Product{
		ID:        "prod-1",
		Title:     "Test Product",
		Price:     100,
		Currency:  "USD",
		SellerID:  "user-1",
		Status:    "active",
		CreatedAt: now,
		UpdatedAt: now,
	}

	err := repo.CreateProduct(context.Background(), product)
	require.NoError(t, err)

	retrieved, err := repo.GetProduct(context.Background(), "prod-1")
	require.NoError(t, err)
	assert.Equal(t, product, retrieved)
}

func TestInMemoryMarketplaceRepository_GetProduct_NotFound(t *testing.T) {
	repo := newRepo()
	retrieved, err := repo.GetProduct(context.Background(), "nonexistent")
	require.NoError(t, err)
	assert.Nil(t, retrieved)
}

func TestInMemoryMarketplaceRepository_UpdateProduct(t *testing.T) {
	repo := newRepo()
	now := newNow()
	product := &entity.Product{
		ID:        "prod-1",
		Title:     "Original",
		Price:     50,
		Currency:  "USD",
		SellerID:  "user-1",
		Status:    "active",
		CreatedAt: now,
		UpdatedAt: now,
	}
	require.NoError(t, repo.CreateProduct(context.Background(), product))

	updated := *product
	updated.Title = "Updated"
	updated.Price = 75
	updated.UpdatedAt = now.Add(time.Hour)
	err := repo.UpdateProduct(context.Background(), &updated)
	require.NoError(t, err)

	retrieved, err := repo.GetProduct(context.Background(), "prod-1")
	require.NoError(t, err)
	assert.Equal(t, &updated, retrieved)
}

func TestInMemoryMarketplaceRepository_DeleteProduct(t *testing.T) {
	repo := newRepo()
	now := newNow()
	product := &entity.Product{
		ID:        "prod-1",
		Title:     "ToDelete",
		Price:     100,
		Currency:  "USD",
		SellerID:  "user-1",
		Status:    "active",
		CreatedAt: now,
		UpdatedAt: now,
	}
	require.NoError(t, repo.CreateProduct(context.Background(), product))

	err := repo.DeleteProduct(context.Background(), "prod-1")
	require.NoError(t, err)

	retrieved, err := repo.GetProduct(context.Background(), "prod-1")
	require.NoError(t, err)
	assert.Nil(t, retrieved)
}

func TestInMemoryMarketplaceRepository_ListProducts(t *testing.T) {
	repo := newRepo()
	now := newNow()

	products := []*entity.Product{
		{ID: "p1", Title: "P1", Price: 100, Currency: "USD", SellerID: "u1", Status: "active", Category: "electronics", Condition: "new", CreatedAt: now, UpdatedAt: now},
		{ID: "p2", Title: "P2", Price: 200, Currency: "USD", SellerID: "u2", Status: "active", Category: "clothing", Condition: "used", CreatedAt: now, UpdatedAt: now},
		{ID: "p3", Title: "P3", Price: 150, Currency: "USD", SellerID: "u1", Status: "sold", Category: "electronics", Condition: "new", CreatedAt: now, UpdatedAt: now},
	}
	for _, p := range products {
		require.NoError(t, repo.CreateProduct(context.Background(), p))
	}

	// No filter -> all
	all, err := repo.ListProducts(context.Background(), entity.ListProductsRequest{})
	require.NoError(t, err)
	assert.ElementsMatch(t, products, all)

	// Filter by category = electronics
	electronics, err := repo.ListProducts(context.Background(), entity.ListProductsRequest{Category: "electronics"})
	require.NoError(t, err)
	expectedElectronics := []*entity.Product{products[0], products[2]}
	assert.ElementsMatch(t, expectedElectronics, electronics)

	// Filter by sellerID = u1
	bySeller, err := repo.ListProducts(context.Background(), entity.ListProductsRequest{SellerID: "u1"})
	require.NoError(t, err)
	expectedSeller := []*entity.Product{products[0], products[2]}
	assert.ElementsMatch(t, expectedSeller, bySeller) // note: both belong to u1 actually P1 and P3? p3 seller u1 yes.
	// Actually p3 seller u1, so both.

	// Filter by price range
	byPrice, err := repo.ListProducts(context.Background(), entity.ListProductsRequest{MinPrice: 120, MaxPrice: 180})
	require.NoError(t, err)
	// Expected: only product with price 150
	assert.ElementsMatch(t, []*entity.Product{products[2]}, byPrice)

	// Filter by status = sold
	sold, err := repo.ListProducts(context.Background(), entity.ListProductsRequest{Status: "sold"})
	require.NoError(t, err)
	assert.ElementsMatch(t, []*entity.Product{products[2]}, sold)

	// Filter by condition = new
	newItems, err := repo.ListProducts(context.Background(), entity.ListProductsRequest{Condition: "new"})
	require.NoError(t, err)
	assert.ElementsMatch(t, []*entity.Product{products[0], products[2]}, newItems)

	// Combined: category=electronics, sellerID=u1, condition=new -> both p1 and p3? p1 and p3 satisfy.
	combined, err := repo.ListProducts(context.Background(), entity.ListProductsRequest{
		Category:  "electronics",
		SellerID:  "u1",
		Condition: "new",
	})
	require.NoError(t, err)
	assert.ElementsMatch(t, []*entity.Product{products[0], products[2]}, combined)
}

func TestInMemoryMarketplaceRepository_GetProductsBySeller(t *testing.T) {
	repo := newRepo()
	now := newNow()
	products := []*entity.Product{
		{ID: "p1", SellerID: "u1", Title: "P1", Price: 100, Currency: "USD", Status: "active", CreatedAt: now, UpdatedAt: now},
		{ID: "p2", SellerID: "u2", Title: "P2", Price: 200, Currency: "USD", Status: "active", CreatedAt: now, UpdatedAt: now},
		{ID: "p3", SellerID: "u1", Title: "P3", Price: 150, Currency: "USD", Status: "active", CreatedAt: now, UpdatedAt: now},
	}
	for _, p := range products {
		require.NoError(t, repo.CreateProduct(context.Background(), p))
	}

	u1Products, err := repo.GetProductsBySeller(context.Background(), "u1")
	require.NoError(t, err)
	assert.ElementsMatch(t, []*entity.Product{products[0], products[2]}, u1Products)

	u2Products, err := repo.GetProductsBySeller(context.Background(), "u2")
	require.NoError(t, err)
	assert.ElementsMatch(t, []*entity.Product{products[1]}, u2Products)

	// Seller with no products
	empty, err := repo.GetProductsBySeller(context.Background(), "u3")
	require.NoError(t, err)
	assert.Empty(t, empty)
}

func TestInMemoryMarketplaceRepository_CreateBid(t *testing.T) {
	repo := newRepo()
	now := newNow()
	bid := &entity.Bid{
		ID:        "bid-1",
		ProductID: "prod-1",
		BidderID:  "user-2",
		Amount:    150,
		Currency:  "USD",
		Status:    "pending",
		CreatedAt: now,
		UpdatedAt: now,
	}
	err := repo.CreateBid(context.Background(), bid)
	require.NoError(t, err)

	bids, err := repo.GetBidsByProduct(context.Background(), "prod-1")
	require.NoError(t, err)
	assert.Equal(t, []*entity.Bid{bid}, bids)
}

func TestInMemoryMarketplaceRepository_GetBidsByProduct(t *testing.T) {
	repo := newRepo()
	now := newNow()
	bids := []*entity.Bid{
		{ID: "b1", ProductID: "p1", BidderID: "u1", Amount: 100, Currency: "USD", Status: "active", CreatedAt: now, UpdatedAt: now},
		{ID: "b2", ProductID: "p1", BidderID: "u2", Amount: 150, Currency: "USD", Status: "active", CreatedAt: now, UpdatedAt: now},
		{ID: "b3", ProductID: "p2", BidderID: "u1", Amount: 120, Currency: "USD", Status: "active", CreatedAt: now, UpdatedAt: now},
	}
	for _, b := range bids {
		require.NoError(t, repo.CreateBid(context.Background(), b))
	}

	p1Bids, err := repo.GetBidsByProduct(context.Background(), "p1")
	require.NoError(t, err)
	assert.ElementsMatch(t, []*entity.Bid{bids[0], bids[1]}, p1Bids)

	p2Bids, err := repo.GetBidsByProduct(context.Background(), "p2")
	require.NoError(t, err)
	assert.ElementsMatch(t, []*entity.Bid{bids[2]}, p2Bids)

	// No bids for product
	empty, err := repo.GetBidsByProduct(context.Background(), "nonexistent")
	require.NoError(t, err)
	assert.Empty(t, empty)
}

func TestInMemoryMarketplaceRepository_GetHighestBid(t *testing.T) {
	repo := newRepo()
	now := newNow()
	bids := []*entity.Bid{
		{ID: "b1", ProductID: "p1", BidderID: "u1", Amount: 100, Currency: "USD", Status: "active", CreatedAt: now, UpdatedAt: now},
		{ID: "b2", ProductID: "p1", BidderID: "u2", Amount: 150, Currency: "USD", Status: "active", CreatedAt: now, UpdatedAt: now},
		{ID: "b3", ProductID: "p1", BidderID: "u3", Amount: 120, Currency: "USD", Status: "active", CreatedAt: now, UpdatedAt: now},
	}
	for _, b := range bids {
		require.NoError(t, repo.CreateBid(context.Background(), b))
	}

	highest, err := repo.GetHighestBid(context.Background(), "p1")
	require.NoError(t, err)
	assert.Equal(t, bids[1], highest) // b2 amount 150

	// No bids
	none, err := repo.GetHighestBid(context.Background(), "nonexistent")
	require.NoError(t, err)
	assert.Nil(t, none)
}

func TestInMemoryMarketplaceRepository_CreateTransaction(t *testing.T) {
	repo := newRepo()
	now := newNow()
	txn := &entity.Transaction{
		ID:        "txn-1",
		ProductID: "prod-1",
		SellerID:  "user-1",
		BuyerID:   "user-2",
		Price:     200,
		Currency:  "USD",
		Status:    "completed",
		CreatedAt: now,
	}
	err := repo.CreateTransaction(context.Background(), txn)
	require.NoError(t, err)

	retrieved, err := repo.GetTransaction(context.Background(), "txn-1")
	require.NoError(t, err)
	assert.Equal(t, txn, retrieved)
}

func TestInMemoryMarketplaceRepository_GetTransaction_NotFound(t *testing.T) {
	repo := newRepo()
	retrieved, err := repo.GetTransaction(context.Background(), "nonexistent")
	require.NoError(t, err)
	assert.Nil(t, retrieved)
}

func TestInMemoryMarketplaceRepository_GetTransactionsByBuyer(t *testing.T) {
	repo := newRepo()
	now := newNow()
	txns := []*entity.Transaction{
		{ID: "t1", ProductID: "p1", SellerID: "u1", BuyerID: "u2", Price: 100, Currency: "USD", Status: "completed", CreatedAt: now},
		{ID: "t2", ProductID: "p2", SellerID: "u2", BuyerID: "u2", Price: 200, Currency: "USD", Status: "completed", CreatedAt: now},
		{ID: "t3", ProductID: "p3", SellerID: "u1", BuyerID: "u3", Price: 150, Currency: "USD", Status: "pending", CreatedAt: now},
	}
	for _, txn := range txns {
		require.NoError(t, repo.CreateTransaction(context.Background(), txn))
	}

	u2Txns, err := repo.GetTransactionsByBuyer(context.Background(), "u2")
	require.NoError(t, err)
	assert.ElementsMatch(t, []*entity.Transaction{txns[0], txns[1]}, u2Txns)

	u3Txns, err := repo.GetTransactionsByBuyer(context.Background(), "u3")
	require.NoError(t, err)
	assert.ElementsMatch(t, []*entity.Transaction{txns[2]}, u3Txns)

	// Buyer with no transactions
	empty, err := repo.GetTransactionsByBuyer(context.Background(), "uX")
	require.NoError(t, err)
	assert.Empty(t, empty)
}

func TestInMemoryMarketplaceRepository_GetTransactionsBySeller(t *testing.T) {
	repo := newRepo()
	now := newNow()
	txns := []*entity.Transaction{
		{ID: "t1", ProductID: "p1", SellerID: "u1", BuyerID: "u2", Price: 100, Currency: "USD", Status: "completed", CreatedAt: now},
		{ID: "t2", ProductID: "p2", SellerID: "u2", BuyerID: "u1", Price: 200, Currency: "USD", Status: "completed", CreatedAt: now},
		{ID: "t3", ProductID: "p3", SellerID: "u1", BuyerID: "u3", Price: 150, Currency: "USD", Status: "pending", CreatedAt: now},
	}
	for _, txn := range txns {
		require.NoError(t, repo.CreateTransaction(context.Background(), txn))
	}

	u1Txns, err := repo.GetTransactionsBySeller(context.Background(), "u1")
	require.NoError(t, err)
	assert.ElementsMatch(t, []*entity.Transaction{txns[0], txns[2]}, u1Txns)

	u2Txns, err := repo.GetTransactionsBySeller(context.Background(), "u2")
	require.NoError(t, err)
	assert.ElementsMatch(t, []*entity.Transaction{txns[1]}, u2Txns)

	empty, err := repo.GetTransactionsBySeller(context.Background(), "uX")
	require.NoError(t, err)
	assert.Empty(t, empty)
}
