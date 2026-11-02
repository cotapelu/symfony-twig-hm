package handler

import (
	"net/http"
	"strconv"

	"community.local/modules/marketplace/entity"
	"community.local/modules/marketplace/service"
	"community.local/internal/idempotency"

	"github.com/gin-gonic/gin"
)

func getCurrentUserID(c *gin.Context) (string, bool) {
	userID, exists := c.Get("user_id")
	if !exists {
		return "", false
	}
	return userID.(string), true
}

func requireAuth(c *gin.Context) (string, bool) {
	userID, ok := getCurrentUserID(c)
	if !ok {
		c.JSON(401, gin.H{"error": "authentication required"})
		return "", false
	}
	return userID, true
}

func isAdmin(c *gin.Context) bool {
	if roles, ok := c.Get("user_roles"); ok {
		for _, r := range roles.([]string) {
			if r == "ROLE_ADMIN" || r == "admin" {
				return true
			}
		}
	}
	return false
}

type MarketplaceHandler struct {
	svc *service.MarketplaceService
}

func NewMarketplaceHandler(svc *service.MarketplaceService) *MarketplaceHandler {
	return &MarketplaceHandler{svc: svc}
}

func (h *MarketplaceHandler) RegisterRoutes(router *gin.Engine) {
	marketplace := router.Group("/api/marketplace")
	{
		marketplace.POST("/products", h.CreateProduct)
		marketplace.GET("/products", h.ListProducts)
		marketplace.GET("/products/:id", h.GetProduct)
		marketplace.PUT("/products/:id", h.UpdateProduct)
		marketplace.DELETE("/products/:id", h.DeleteProduct)
		marketplace.POST("/bids", h.PlaceBid)
		marketplace.GET("/products/:id/bids", h.GetBidsForProduct)
		marketplace.POST("/bids/:id/accept", h.AcceptBid)
		marketplace.POST("/products/:id/buy", h.BuyNow)
		marketplace.POST("/transactions/:id/complete", h.CompleteTransaction)
		marketplace.GET("/transactions/:id", h.GetTransaction)
		marketplace.GET("/transactions", h.GetUserTransactions)
		marketplace.GET("/my-products", h.GetUserProducts)
		marketplace.GET("/categories", h.GetCategories)
	}
}

func (h *MarketplaceHandler) CreateProduct(c *gin.Context) {
	// Require authentication
	userID, ok := requireAuth(c)
	if !ok {
		return
	}

	var req entity.CreateProductRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	// Use authenticated user as seller
	product, err := h.svc.CreateProduct(&req, userID)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusCreated, product)
}

func (h *MarketplaceHandler) GetProduct(c *gin.Context) {
	id := c.Param("id")

	product, err := h.svc.GetProduct(id)
	if err != nil {
		c.JSON(http.StatusNotFound, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusOK, product)
}

func (h *MarketplaceHandler) UpdateProduct(c *gin.Context) {
	id := c.Param("id")

	// Require authentication and check ownership
	userID, ok := requireAuth(c)
	if !ok {
		return
	}

	// Get product to check ownership
	product, err := h.svc.GetProduct(id)
	if err != nil {
		c.JSON(http.StatusNotFound, gin.H{"error": "product not found"})
		return
	}
	if product.SellerID != userID && !isAdmin(c) {
		c.JSON(403, gin.H{"error": "access denied: you can only update your own products"})
		return
	}

	var req entity.UpdateProductRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	product, err = h.svc.UpdateProduct(id, &req)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusOK, product)
}

func (h *MarketplaceHandler) DeleteProduct(c *gin.Context) {
	id := c.Param("id")

	// Require authentication and check ownership
	userID, ok := requireAuth(c)
	if !ok {
		return
	}

	// Get product to check ownership
	product, err := h.svc.GetProduct(id)
	if err != nil {
		c.JSON(http.StatusNotFound, gin.H{"error": "product not found"})
		return
	}
	if product.SellerID != userID && !isAdmin(c) {
		c.JSON(403, gin.H{"error": "access denied: you can only delete your own products"})
		return
	}

	// If admin, bypass ownership check in service by passing empty sellerID
	deleteSellerID := userID
	if isAdmin(c) {
	    deleteSellerID = ""
	}
	if err := h.svc.DeleteProduct(id, deleteSellerID); err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusOK, gin.H{"message": "product deleted"})
}

func (h *MarketplaceHandler) ListProducts(c *gin.Context) {
	req := &entity.ListProductsRequest{
		Category:  c.Query("category"),
		MinPrice:  0,
		MaxPrice:  0,
		Condition: c.Query("condition"),
		SellerID:  c.Query("seller_id"),
		Search:    c.Query("search"),
		Status:    c.Query("status"),
		Limit:     20,
		Offset:    0,
	}

	if minPrice := c.Query("min_price"); minPrice != "" {
		if val, err := strconv.ParseFloat(minPrice, 64); err == nil {
			req.MinPrice = val
		}
	}
	if maxPrice := c.Query("max_price"); maxPrice != "" {
		if val, err := strconv.ParseFloat(maxPrice, 64); err == nil {
			req.MaxPrice = val
		}
	}
	if limit := c.Query("limit"); limit != "" {
		if val, err := strconv.Atoi(limit); err == nil {
			req.Limit = val
		}
	}
	if offset := c.Query("offset"); offset != "" {
		if val, err := strconv.Atoi(offset); err == nil {
			req.Offset = val
		}
	}

	products, err := h.svc.ListProducts(req)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusOK, products)
}

func (h *MarketplaceHandler) PlaceBid(c *gin.Context) {
	// Require authentication
	userID, ok := requireAuth(c)
	if !ok {
		return
	}

	var req entity.PlaceBidRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	// Idempotency check
	idemKey := c.GetHeader("Idempotency-Key")
	if idemKey == "" {
		c.JSON(http.StatusBadRequest, gin.H{"error": "Idempotency-Key header required"})
		return
	}
	if cached, ok := idempotency.Get(idemKey); ok {
		c.JSON(http.StatusOK, cached)
		return
	}

	// User cannot bid on own product
	product, err := h.svc.GetProduct(req.ProductID)
	if err == nil && product.SellerID == userID {
		c.JSON(403, gin.H{"error": "cannot bid on your own product"})
		return
	}

	bid, err := h.svc.PlaceBid(&req, userID)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	resp := gin.H{"bid": bid}
	idempotency.Set(idemKey, resp)

	c.JSON(http.StatusCreated, resp)
}

func (h *MarketplaceHandler) GetBidsForProduct(c *gin.Context) {
	productID := c.Param("id")

	bids, err := h.svc.GetBidsForProduct(productID)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusOK, bids)
}

func (h *MarketplaceHandler) AcceptBid(c *gin.Context) {
	// Require authentication
	userID, ok := requireAuth(c)
	if !ok {
		return
	}

	bidID := c.Param("id")

	// Idempotency check
	idemKey := c.GetHeader("Idempotency-Key")
	if idemKey == "" {
		c.JSON(http.StatusBadRequest, gin.H{"error": "Idempotency-Key header required"})
		return
	}
	if cached, ok := idempotency.Get(idemKey); ok {
		c.JSON(http.StatusOK, cached)
		return
	}

	// Get bid to check product ownership
	bid, err := h.svc.GetBid(bidID)
	if err != nil {
		c.JSON(http.StatusNotFound, gin.H{"error": "bid not found"})
		return
	}
	product, err := h.svc.GetProduct(bid.ProductID)
	if err != nil {
		c.JSON(http.StatusNotFound, gin.H{"error": "product not found"})
		return
	}
	if product.SellerID != userID && !isAdmin(c) {
		c.JSON(403, gin.H{"error": "only the seller can accept bids"})
		return
	}

	tx, err := h.svc.AcceptBid(bidID, userID)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	resp := gin.H{"transaction": tx}
	idempotency.Set(idemKey, resp)

	c.JSON(http.StatusOK, resp)
}

func (h *MarketplaceHandler) BuyNow(c *gin.Context) {
	// Require authentication
	userID, ok := requireAuth(c)
	if !ok {
		return
	}

	productID := c.Param("id")

	// Idempotency check
	idemKey := c.GetHeader("Idempotency-Key")
	if idemKey == "" {
		c.JSON(http.StatusBadRequest, gin.H{"error": "Idempotency-Key header required"})
		return
	}
	if cached, ok := idempotency.Get(idemKey); ok {
		c.JSON(http.StatusOK, cached)
		return
	}

	tx, err := h.svc.BuyNow(productID, userID)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	resp := gin.H{"transaction": tx}
	idempotency.Set(idemKey, resp)

	c.JSON(http.StatusOK, resp)
}

func (h *MarketplaceHandler) CompleteTransaction(c *gin.Context) {
	txID := c.Param("id")

	tx, err := h.svc.CompleteTransaction(txID)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusOK, tx)
}

func (h *MarketplaceHandler) GetTransaction(c *gin.Context) {
	txID := c.Param("id")

	tx, err := h.svc.GetTransaction(txID)
	if err != nil {
		c.JSON(http.StatusNotFound, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusOK, tx)
}

func (h *MarketplaceHandler) GetUserTransactions(c *gin.Context) {
	// Require authentication
	userID, ok := requireAuth(c)
	if !ok {
		return
	}

	txs, err := h.svc.GetUserTransactions(userID)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusOK, txs)
}

func (h *MarketplaceHandler) GetUserProducts(c *gin.Context) {
	// Require authentication
	userID, ok := requireAuth(c)
	if !ok {
		return
	}

	products, err := h.svc.GetUserProducts(userID)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusOK, products)
}

func (h *MarketplaceHandler) GetCategories(c *gin.Context) {
	categories, err := h.svc.GetCategories()
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusOK, categories)
}
