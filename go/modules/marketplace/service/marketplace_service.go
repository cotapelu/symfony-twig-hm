package service

import (
	"encoding/json"
	"fmt"
	"math"
	"os"
	"path/filepath"
	"sort"
	"strings"
	"sync"
	"time"

	"community.local/modules/marketplace/entity"
)

type MarketplaceService struct {
	mu           sync.RWMutex
	dataDir      string
	products     map[string]entity.Product
	bids         map[string]entity.Bid
	transactions map[string]entity.Transaction
	nextProdID   int
	nextBidID    int
	nextTxID     int
}

func NewMarketplaceService(dataDir string) (*MarketplaceService, error) {
	s := &MarketplaceService{
		dataDir:      dataDir,
		products:     make(map[string]entity.Product),
		bids:         make(map[string]entity.Bid),
		transactions: make(map[string]entity.Transaction),
		nextProdID:   1,
		nextBidID:    1,
		nextTxID:     1,
	}

	s.ensureDataDirectory()
	s.restoreFromFile()

	return s, nil
}

func (s *MarketplaceService) ensureDataDirectory() {
	dir := filepath.Dir(s.dataDir)
	if dir != "" && dir != "." {
		os.MkdirAll(dir, 0755)
	}
}

func (s *MarketplaceService) restoreFromFile() {
	data, err := os.ReadFile(s.dataDir)
	if err != nil {
		if os.IsNotExist(err) {
			return
		}
		return
	}

	var saved struct {
		Products     map[string]entity.Product     `json:"products"`
		Bids         map[string]entity.Bid         `json:"bids"`
		Transactions map[string]entity.Transaction `json:"transactions"`
		NextProdID   int                           `json:"next_prod_id"`
		NextBidID    int                           `json:"next_bid_id"`
		NextTxID     int                           `json:"next_tx_id"`
	}

	if err := json.Unmarshal(data, &saved); err != nil {
		return
	}

	s.products = saved.Products
	s.bids = saved.Bids
	s.transactions = saved.Transactions
	s.nextProdID = saved.NextProdID
	s.nextBidID = saved.NextBidID
	s.nextTxID = saved.NextTxID
}

func (s *MarketplaceService) persistToFile() {
	data, err := json.MarshalIndent(map[string]interface{}{
		"products":     s.products,
		"bids":         s.bids,
		"transactions": s.transactions,
		"next_prod_id": s.nextProdID,
		"next_bid_id":  s.nextBidID,
		"next_tx_id":   s.nextTxID,
	}, "", "  ")
	if err != nil {
		return
	}
	os.WriteFile(s.dataDir, data, 0644)
}

func (s *MarketplaceService) CreateProduct(req *entity.CreateProductRequest, sellerID string) (*entity.Product, error) {
	s.mu.Lock()
	defer s.mu.Unlock()

	product := &entity.Product{
		ID:          fmt.Sprintf("prod_%d", s.nextProdID),
		Title:       req.Title,
		Description: req.Description,
		Price:       req.Price,
		Currency:    req.Currency,
		Category:    req.Category,
		Condition:   req.Condition,
		SellerID:    sellerID,
		Status:      "active",
		Images:      req.Images,
		Tags:        req.Tags,
		ViewCount:   0,
		CreatedAt:   time.Now(),
		UpdatedAt:   time.Now(),
	}

	s.products[product.ID] = *product
	s.nextProdID++

	s.persistToFile()
	return product, nil
}

func (s *MarketplaceService) GetProduct(id string) (*entity.Product, error) {
	s.mu.Lock()
	defer s.mu.Unlock()

	product, exists := s.products[id]
	if !exists {
		return nil, fmt.Errorf("product not found")
	}

	product.ViewCount++
	s.products[id] = product

	return &product, nil
}

func (s *MarketplaceService) UpdateProduct(id string, req *entity.UpdateProductRequest) (*entity.Product, error) {
	s.mu.Lock()
	defer s.mu.Unlock()

	product, exists := s.products[id]
	if !exists {
		return nil, fmt.Errorf("product not found")
	}

	// Ownership check
	if req.SellerID != "" && product.SellerID != req.SellerID {
		return nil, fmt.Errorf("not authorized to update this product")
	}

	if product.Status != "active" {
		return nil, fmt.Errorf("cannot update product that is not active")
	}

	if req.Title != "" {
		product.Title = req.Title
	}
	if req.Description != "" {
		product.Description = req.Description
	}
	if req.Price > 0 {
		product.Price = req.Price
	}
	if req.Category != "" {
		product.Category = req.Category
	}
	if req.Condition != "" {
		product.Condition = req.Condition
	}
	if len(req.Images) > 0 {
		product.Images = req.Images
	}
	if len(req.Tags) > 0 {
		product.Tags = req.Tags
	}

	product.UpdatedAt = time.Now()
	s.products[id] = product

	s.persistToFile()
	return &product, nil
}

func (s *MarketplaceService) DeleteProduct(id string, sellerID string) error {
	s.mu.Lock()
	defer s.mu.Unlock()

	product, exists := s.products[id]
	if !exists {
		return fmt.Errorf("product not found")
	}

	// Ownership check
	if sellerID != "" && product.SellerID != sellerID {
		return fmt.Errorf("not authorized to delete this product")
	}

	if product.Status == "sold" {
		return fmt.Errorf("cannot delete sold product")
	}

	delete(s.products, id)
	s.persistToFile()
	return nil
}

func (s *MarketplaceService) ListProducts(req *entity.ListProductsRequest) ([]entity.Product, error) {
	s.mu.RLock()
	defer s.mu.RUnlock()

	products := make([]entity.Product, 0)
	for _, product := range s.products {
		if req.Status != "" && product.Status != req.Status {
			continue
		}
		if req.Category != "" && product.Category != req.Category {
			continue
		}
		if req.Condition != "" && product.Condition != req.Condition {
			continue
		}
		if req.SellerID != "" && product.SellerID != req.SellerID {
			continue
		}
		if req.MinPrice > 0 && product.Price < req.MinPrice {
			continue
		}
		if req.MaxPrice > 0 && product.Price > req.MaxPrice {
			continue
		}
		if req.Search != "" {
			searchLower := strings.ToLower(req.Search)
			titleMatch := strings.Contains(strings.ToLower(product.Title), searchLower)
			descMatch := strings.Contains(strings.ToLower(product.Description), searchLower)
			tagMatch := false
			for _, tag := range product.Tags {
				if strings.Contains(strings.ToLower(tag), searchLower) {
					tagMatch = true
					break
				}
			}
			if !titleMatch && !descMatch && !tagMatch {
				continue
			}
		}
		products = append(products, product)
	}

	limit := req.Limit
	if limit <= 0 || limit > 100 {
		limit = 20
	}
	offset := req.Offset
	if offset < 0 {
		offset = 0
	}

	if offset >= len(products) {
		return []entity.Product{}, nil
	}

	end := offset + limit
	if end > len(products) {
		end = len(products)
	}

	return products[offset:end], nil
}

func (s *MarketplaceService) PlaceBid(req *entity.PlaceBidRequest, bidderID string) (*entity.Bid, error) {
	// Input validation
	if req.Amount <= 0 {
		return nil, fmt.Errorf("bid amount must be positive")
	}
	if req.Amount > 1000000000 {
		return nil, fmt.Errorf("bid amount exceeds maximum limit")
	}

	s.mu.Lock()
	defer s.mu.Unlock()

	product, exists := s.products[req.ProductID]
	if !exists {
		return nil, fmt.Errorf("product not found")
	}

	if product.Status != "active" {
		return nil, fmt.Errorf("product is not available for bidding")
	}

	if product.SellerID == bidderID {
		return nil, fmt.Errorf("cannot bid on own product")
	}

	highestBid := s.getHighestBidLocked(req.ProductID)
	if highestBid > 0 && req.Amount <= highestBid {
		return nil, fmt.Errorf("bid must be higher than current highest bid of %.2f", highestBid)
	}

	if req.Amount < product.Price {
		return nil, fmt.Errorf("bid must be at least the starting price of %.2f", product.Price)
	}

	bid := &entity.Bid{
		ID:        fmt.Sprintf("bid_%d", s.nextBidID),
		ProductID: req.ProductID,
		BidderID:  bidderID,
		Amount:    req.Amount,
		Currency:  req.Currency,
		Status:    "active",
		CreatedAt: time.Now(),
		UpdatedAt: time.Now(),
	}

	s.bids[bid.ID] = *bid
	s.nextBidID++

	s.persistToFile()
	return bid, nil
}

func (s *MarketplaceService) GetBidsForProduct(productID string) ([]entity.Bid, error) {
	s.mu.RLock()
	defer s.mu.RUnlock()

	var bids []entity.Bid
	for _, bid := range s.bids {
		if bid.ProductID == productID {
			bids = append(bids, bid)
		}
	}

	sort.Slice(bids, func(i, j int) bool {
		return bids[i].Amount > bids[j].Amount
	})

	return bids, nil
}

func (s *MarketplaceService) GetHighestBid(productID string) float64 {
	bids, _ := s.GetBidsForProduct(productID)
	if len(bids) > 0 {
		return bids[0].Amount
	}
	return 0
}

func (s *MarketplaceService) getHighestBidLocked(productID string) float64 {
	var bids []entity.Bid
	for _, bid := range s.bids {
		if bid.ProductID == productID {
			bids = append(bids, bid)
		}
	}

	if len(bids) == 0 {
		return 0
	}

	highest := bids[0].Amount
	for i := 1; i < len(bids); i++ {
		if bids[i].Amount > highest {
			highest = bids[i].Amount
		}
	}
	return highest
}

func (s *MarketplaceService) getBidsForProductLocked(productID string) []entity.Bid {
	var bids []entity.Bid
	for _, bid := range s.bids {
		if bid.ProductID == productID {
			bids = append(bids, bid)
		}
	}

	sort.Slice(bids, func(i, j int) bool {
		return bids[i].Amount > bids[j].Amount
	})

	return bids
}

func (s *MarketplaceService) AcceptBid(bidID string, sellerID string) (*entity.Transaction, error) {
	s.mu.Lock()
	defer s.mu.Unlock()

	bid, exists := s.bids[bidID]
	if !exists {
		return nil, fmt.Errorf("bid not found")
	}

	if bid.Status != "active" {
		return nil, fmt.Errorf("bid is not active")
	}

	product, exists := s.products[bid.ProductID]
	if !exists {
		return nil, fmt.Errorf("product not found")
	}

	if product.SellerID != sellerID {
		return nil, fmt.Errorf("only seller can accept bid")
	}

	now := time.Now()
	product.Status = "sold"
	product.SoldAt = &now
	s.products[product.ID] = product

	bid.Status = "accepted"
	s.bids[bid.ID] = bid

	otherBids := s.getBidsForProductLocked(bid.ProductID)
	for _, otherBid := range otherBids {
		if otherBid.ID != bidID && otherBid.Status == "active" {
			otherBid.Status = "rejected"
			s.bids[otherBid.ID] = otherBid
		}
	}

	tx := &entity.Transaction{
		ID:        fmt.Sprintf("tx_%d", s.nextTxID),
		ProductID: bid.ProductID,
		SellerID:  sellerID,
		BuyerID:   bid.BidderID,
		Price:     bid.Amount,
		Currency:  bid.Currency,
		Status:    "pending",
		CreatedAt: now,
	}

	s.transactions[tx.ID] = *tx
	s.nextTxID++

	s.persistToFile()
	return tx, nil
}

func (s *MarketplaceService) BuyNow(productID, buyerID string) (*entity.Transaction, error) {
	s.mu.Lock()
	defer s.mu.Unlock()

	product, exists := s.products[productID]
	if !exists {
		return nil, fmt.Errorf("product not found")
	}

	if product.Status != "active" {
		return nil, fmt.Errorf("product is not available")
	}

	if product.SellerID == buyerID {
		return nil, fmt.Errorf("cannot buy own product")
	}

	now := time.Now()
	product.Status = "sold"
	product.SoldAt = &now
	s.products[product.ID] = product

	tx := &entity.Transaction{
		ID:        fmt.Sprintf("tx_%d", s.nextTxID),
		ProductID: productID,
		SellerID:  product.SellerID,
		BuyerID:   buyerID,
		Price:     product.Price,
		Currency:  product.Currency,
		Status:    "pending",
		CreatedAt: now,
	}

	s.transactions[tx.ID] = *tx
	s.nextTxID++

	s.persistToFile()
	return tx, nil
}

func (s *MarketplaceService) CompleteTransaction(txID string) (*entity.Transaction, error) {
	s.mu.Lock()
	defer s.mu.Unlock()

	tx, exists := s.transactions[txID]
	if !exists {
		return nil, fmt.Errorf("transaction not found")
	}

	if tx.Status != "pending" {
		return nil, fmt.Errorf("transaction is not pending")
	}

	now := time.Now()
	tx.Status = "completed"
	tx.CompletedAt = &now
	s.transactions[txID] = tx

	s.persistToFile()
	return &tx, nil
}

func (s *MarketplaceService) GetTransaction(txID string) (*entity.Transaction, error) {
	s.mu.RLock()
	defer s.mu.RUnlock()

	tx, exists := s.transactions[txID]
	if !exists {
		return nil, fmt.Errorf("transaction not found")
	}
	return &tx, nil
}

func (s *MarketplaceService) GetUserTransactions(userID string) ([]entity.Transaction, error) {
	s.mu.RLock()
	defer s.mu.RUnlock()

	var txs []entity.Transaction
	for _, tx := range s.transactions {
		if tx.SellerID == userID || tx.BuyerID == userID {
			txs = append(txs, tx)
		}
	}

	sort.Slice(txs, func(i, j int) bool {
		return txs[i].CreatedAt.After(txs[j].CreatedAt)
	})

	return txs, nil
}

func (s *MarketplaceService) GetUserProducts(userID string) ([]entity.Product, error) {
	s.mu.RLock()
	defer s.mu.RUnlock()

	var products []entity.Product
	for _, product := range s.products {
		if product.SellerID == userID {
			products = append(products, product)
		}
	}

	sort.Slice(products, func(i, j int) bool {
		return products[i].CreatedAt.After(products[j].CreatedAt)
	})

	return products, nil
}

func (s *MarketplaceService) GetCategories() ([]string, error) {
	s.mu.RLock()
	defer s.mu.RUnlock()

	categorySet := make(map[string]bool)
	for _, product := range s.products {
		if product.Category != "" {
			categorySet[product.Category] = true
		}
	}

	categories := make([]string, 0)
	for cat := range categorySet {
		categories = append(categories, cat)
	}

	sort.Strings(categories)
	return categories, nil
}
func (s *MarketplaceService) GetBid(bidID string) (*entity.Bid, error) {
	s.mu.RLock()
	defer s.mu.RUnlock()

	bid, exists := s.bids[bidID]
	if !exists {
		return nil, fmt.Errorf("bid not found")
	}
	return &bid, nil
}

func (s *MarketplaceService) roundToTwoDecimals(value float64) float64 {
	return math.Round(value*100) / 100
}
