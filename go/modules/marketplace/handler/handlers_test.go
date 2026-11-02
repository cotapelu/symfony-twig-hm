package handler

import (
    "encoding/json"
    "net/http"
    "net/http/httptest"
    "os"
    "path/filepath"
    "strings"
    "testing"

    "community.local/modules/marketplace/entity"
    "community.local/modules/marketplace/service"
    "github.com/gin-gonic/gin"
    "github.com/stretchr/testify/assert"
    "github.com/stretchr/testify/require"
)

// ==================== Test Helpers ====================

func newTestMarketplaceService(t *testing.T) (*service.MarketplaceService, func()) {
    t.Helper()
    dir, err := os.MkdirTemp("", "marketplace-test-*")
    require.NoError(t, err)
    dataPath := filepath.Join(dir, "data.json")
    svc, err := service.NewMarketplaceService(dataPath)
    require.NoError(t, err)
    cleanup := func() {
        os.RemoveAll(dir)
    }
    return svc, cleanup
}

func newTestRouter(svc *service.MarketplaceService, middleware ...gin.HandlerFunc) *gin.Engine {
    gin.SetMode(gin.TestMode)
    router := gin.New()
    for _, m := range middleware {
        router.Use(m)
    }
    handler := NewMarketplaceHandler(svc)
    handler.RegisterRoutes(router)
    return router
}

func authMiddleware() gin.HandlerFunc {
    return func(c *gin.Context) {
        userID := c.GetHeader("X-User-ID")
        if userID == "" {
            c.JSON(http.StatusUnauthorized, gin.H{"error": "authentication required"})
            c.Abort()
            return
        }
        c.Set("user_id", userID)
        roles := []string{"USER"}
        if c.GetHeader("X-User-Role") == "admin" {
            roles = []string{"admin"}
        }
        c.Set("user_roles", roles)
    }
}

func doRequest(t *testing.T, router *gin.Engine, method, url, body string, headers map[string]string) *httptest.ResponseRecorder {
    t.Helper()
    var req *http.Request
    if body != "" {
        req = httptest.NewRequest(method, url, strings.NewReader(body))
        req.Header.Set("Content-Type", "application/json")
    } else {
        req = httptest.NewRequest(method, url, nil)
    }
    for k, v := range headers {
        req.Header.Set(k, v)
    }
    w := httptest.NewRecorder()
    router.ServeHTTP(w, req)
    return w
}

func createProduct(t *testing.T, svc *service.MarketplaceService, sellerID string) *entity.Product {
    req := &entity.CreateProductRequest{
        Title:       "Test Product",
        Description: "A test product",
        Price:       10.0,
        Currency:    "USD",
        Category:    "testcat",
        Condition:   "new",
    }
    prod, err := svc.CreateProduct(req, sellerID)
    require.NoError(t, err)
    return prod
}

func createBid(t *testing.T, svc *service.MarketplaceService, productID, bidderID string, amount float64) *entity.Bid {
    req := &entity.PlaceBidRequest{
        ProductID: productID,
        Amount:    amount,
        Currency:  "USD",
    }
    bid, err := svc.PlaceBid(req, bidderID)
    require.NoError(t, err)
    return bid
}

// ==================== Tests ====================

func TestCreateProduct(t *testing.T) {
    t.Run("success", func(t *testing.T) {
        svc, cleanup := newTestMarketplaceService(t)
        defer cleanup()
        router := newTestRouter(svc, authMiddleware())
        body := `{"title":"New Product","description":"New Desc","price":19.99,"currency":"USD","category":"electronics","condition":"new"}`
        w := doRequest(t, router, http.MethodPost, "/api/marketplace/products", body, map[string]string{"X-User-ID": "user1"})
        assert.Equal(t, http.StatusCreated, w.Code)
        var resp map[string]interface{}
        json.NewDecoder(w.Body).Decode(&resp)
        assert.Contains(t, resp, "id")
        assert.Equal(t, "New Product", resp["title"])
        assert.Equal(t, "user1", resp["seller_id"])
    })

    t.Run("unauthorized", func(t *testing.T) {
        svc, cleanup := newTestMarketplaceService(t)
        defer cleanup()
        router := newTestRouter(svc) // no auth
        body := `{"title":"Prod","price":10,"currency":"USD","category":"cat","condition":"new"}`
        w := doRequest(t, router, http.MethodPost, "/api/marketplace/products", body, nil)
        assert.Equal(t, http.StatusUnauthorized, w.Code)
    })

    t.Run("invalid_json", func(t *testing.T) {
        svc, cleanup := newTestMarketplaceService(t)
        defer cleanup()
        router := newTestRouter(svc, authMiddleware())
        body := `{"title":"Prod", invalid}`
        w := doRequest(t, router, http.MethodPost, "/api/marketplace/products", body, map[string]string{"X-User-ID": "user1"})
        assert.Equal(t, http.StatusBadRequest, w.Code)
    })

    t.Run("missing_required_fields", func(t *testing.T) {
        svc, cleanup := newTestMarketplaceService(t)
        defer cleanup()
        router := newTestRouter(svc, authMiddleware())
        body := `{"title":"Prod"}`
        w := doRequest(t, router, http.MethodPost, "/api/marketplace/products", body, map[string]string{"X-User-ID": "user1"})
        assert.Equal(t, http.StatusCreated, w.Code)
        var resp map[string]interface{}
        json.NewDecoder(w.Body).Decode(&resp)
        assert.Equal(t, "Prod", resp["title"])
    })
}

func TestGetProduct(t *testing.T) {
    t.Run("success", func(t *testing.T) {
        svc, cleanup := newTestMarketplaceService(t)
        defer cleanup()
        prod := createProduct(t, svc, "seller1")
        router := newTestRouter(svc)
        w := doRequest(t, router, http.MethodGet, "/api/marketplace/products/"+prod.ID, "", nil)
        assert.Equal(t, http.StatusOK, w.Code)
        var resp map[string]interface{}
        json.NewDecoder(w.Body).Decode(&resp)
        assert.Equal(t, prod.ID, resp["id"])
        assert.Equal(t, "Test Product", resp["title"])
    })

    t.Run("not_found", func(t *testing.T) {
        svc, cleanup := newTestMarketplaceService(t)
        defer cleanup()
        router := newTestRouter(svc)
        w := doRequest(t, router, http.MethodGet, "/api/marketplace/products/nonexistent", "", nil)
        assert.Equal(t, http.StatusNotFound, w.Code)
    })
}

func TestUpdateProduct(t *testing.T) {
    t.Run("success_owner", func(t *testing.T) {
        svc, cleanup := newTestMarketplaceService(t)
        defer cleanup()
        prod := createProduct(t, svc, "owner1")
        router := newTestRouter(svc, authMiddleware())
        body := `{"title":"Updated Title","price":25.5,"currency":"USD"}`
        w := doRequest(t, router, http.MethodPut, "/api/marketplace/products/"+prod.ID, body, map[string]string{"X-User-ID": "owner1"})
        assert.Equal(t, http.StatusOK, w.Code)
        var resp map[string]interface{}
        json.NewDecoder(w.Body).Decode(&resp)
        assert.Equal(t, "Updated Title", resp["title"])
        assert.Equal(t, 25.5, resp["price"])
    })

    t.Run("success_admin", func(t *testing.T) {
        svc, cleanup := newTestMarketplaceService(t)
        defer cleanup()
        prod := createProduct(t, svc, "otheruser")
        router := newTestRouter(svc, authMiddleware())
        body := `{"title":"Admin Updated"}`
        w := doRequest(t, router, http.MethodPut, "/api/marketplace/products/"+prod.ID, body, map[string]string{"X-User-ID": "admin", "X-User-Role": "admin"})
        assert.Equal(t, http.StatusOK, w.Code)
    })

    t.Run("unauthorized", func(t *testing.T) {
        svc, cleanup := newTestMarketplaceService(t)
        defer cleanup()
        prod := createProduct(t, svc, "owner1")
        router := newTestRouter(svc) // no auth
        body := `{"title":"Hacked"}`
        w := doRequest(t, router, http.MethodPut, "/api/marketplace/products/"+prod.ID, body, nil)
        assert.Equal(t, http.StatusUnauthorized, w.Code)
    })

    t.Run("forbidden_non_owner", func(t *testing.T) {
        svc, cleanup := newTestMarketplaceService(t)
        defer cleanup()
        prod := createProduct(t, svc, "owner1")
        router := newTestRouter(svc, authMiddleware())
        body := `{"title":"Hacked"}`
        w := doRequest(t, router, http.MethodPut, "/api/marketplace/products/"+prod.ID, body, map[string]string{"X-User-ID": "attacker"})
        assert.Equal(t, http.StatusForbidden, w.Code)
    })

    t.Run("product_not_found", func(t *testing.T) {
        svc, cleanup := newTestMarketplaceService(t)
        defer cleanup()
        router := newTestRouter(svc, authMiddleware())
        body := `{"title":"Update"}`
        w := doRequest(t, router, http.MethodPut, "/api/marketplace/products/missing", body, map[string]string{"X-User-ID": "user1"})
        assert.Equal(t, http.StatusNotFound, w.Code)
    })

    t.Run("invalid_json", func(t *testing.T) {
        svc, cleanup := newTestMarketplaceService(t)
        defer cleanup()
        prod := createProduct(t, svc, "owner1")
        router := newTestRouter(svc, authMiddleware())
        body := `{"title": invalid}`
        w := doRequest(t, router, http.MethodPut, "/api/marketplace/products/"+prod.ID, body, map[string]string{"X-User-ID": "owner1"})
        assert.Equal(t, http.StatusBadRequest, w.Code)
    })

    t.Run("product_not_active", func(t *testing.T) {
        svc, cleanup := newTestMarketplaceService(t)
        defer cleanup()
        seller := "seller1"
        prod := createProduct(t, svc, seller)
        // Mark product as sold by buying it
        buyer := "buyer1"
        _, err := svc.BuyNow(prod.ID, buyer)
        require.NoError(t, err)
        router := newTestRouter(svc, authMiddleware())
        body := `{"price": 20}`
        w := doRequest(t, router, http.MethodPut, "/api/marketplace/products/"+prod.ID, body, map[string]string{"X-User-ID": seller})
        assert.Equal(t, http.StatusInternalServerError, w.Code)
    })
}

func TestDeleteProduct(t *testing.T) {
    t.Run("success_owner", func(t *testing.T) {
        svc, cleanup := newTestMarketplaceService(t)
        defer cleanup()
        prod := createProduct(t, svc, "owner1")
        router := newTestRouter(svc, authMiddleware())
        w := doRequest(t, router, http.MethodDelete, "/api/marketplace/products/"+prod.ID, "", map[string]string{"X-User-ID": "owner1"})
        assert.Equal(t, http.StatusOK, w.Code)
    })

    t.Run("success_admin", func(t *testing.T) {
        svc, cleanup := newTestMarketplaceService(t)
        defer cleanup()
        prod := createProduct(t, svc, "otheruser")
        router := newTestRouter(svc, authMiddleware())
        w := doRequest(t, router, http.MethodDelete, "/api/marketplace/products/"+prod.ID, "", map[string]string{"X-User-ID": "admin", "X-User-Role": "admin"})
        assert.Equal(t, http.StatusOK, w.Code)
    })

    t.Run("unauthorized", func(t *testing.T) {
        svc, cleanup := newTestMarketplaceService(t)
        defer cleanup()
        prod := createProduct(t, svc, "owner1")
        router := newTestRouter(svc) // no auth
        w := doRequest(t, router, http.MethodDelete, "/api/marketplace/products/"+prod.ID, "", nil)
        assert.Equal(t, http.StatusUnauthorized, w.Code)
    })

    t.Run("forbidden_non_owner", func(t *testing.T) {
        svc, cleanup := newTestMarketplaceService(t)
        defer cleanup()
        prod := createProduct(t, svc, "owner1")
        router := newTestRouter(svc, authMiddleware())
        w := doRequest(t, router, http.MethodDelete, "/api/marketplace/products/"+prod.ID, "", map[string]string{"X-User-ID": "attacker"})
        assert.Equal(t, http.StatusForbidden, w.Code)
    })

    t.Run("product_not_found", func(t *testing.T) {
        svc, cleanup := newTestMarketplaceService(t)
        defer cleanup()
        router := newTestRouter(svc, authMiddleware())
        w := doRequest(t, router, http.MethodDelete, "/api/marketplace/products/missing", "", map[string]string{"X-User-ID": "user1"})
        assert.Equal(t, http.StatusNotFound, w.Code)
    })

    t.Run("product_sold_cannot_delete", func(t *testing.T) {
        svc, cleanup := newTestMarketplaceService(t)
        defer cleanup()
        seller := "seller1"
        prod := createProduct(t, svc, seller)
        buyer := "buyer1"
        _, err := svc.BuyNow(prod.ID, buyer)
        require.NoError(t, err)
        router := newTestRouter(svc, authMiddleware())
        w := doRequest(t, router, http.MethodDelete, "/api/marketplace/products/"+prod.ID, "", map[string]string{"X-User-ID": seller})
        assert.Equal(t, http.StatusInternalServerError, w.Code)
    })
}

func TestListProducts(t *testing.T) {
    t.Run("success_default", func(t *testing.T) {
        svc, cleanup := newTestMarketplaceService(t)
        defer cleanup()
        createProduct(t, svc, "user1")
        createProduct(t, svc, "user2")
        router := newTestRouter(svc)
        w := doRequest(t, router, http.MethodGet, "/api/marketplace/products", "", nil)
        assert.Equal(t, http.StatusOK, w.Code)
        var products []map[string]interface{}
        json.NewDecoder(w.Body).Decode(&products)
        assert.Len(t, products, 2)
    })

    t.Run("success_with_filters", func(t *testing.T) {
        svc, cleanup := newTestMarketplaceService(t)
        defer cleanup()
        // Create product that matches filter
        reqMatch := &entity.CreateProductRequest{
            Title:       "Electronics Item",
            Description: "Expensive",
            Price:       150.0,
            Currency:    "USD",
            Category:    "electronics",
            Condition:   "new",
        }
        prodMatch, _ := svc.CreateProduct(reqMatch, "user1")
        // Create product that does NOT match
        reqNoMatch := &entity.CreateProductRequest{
            Title:       "Cheap Item",
            Description: "Cheap",
            Price:       5.0,
            Currency:    "USD",
            Category:    "testcat",
            Condition:   "new",
        }
        svc.CreateProduct(reqNoMatch, "user2")
        router := newTestRouter(svc)
        url := "/api/marketplace/products?category=electronics&min_price=100"
        w := doRequest(t, router, http.MethodGet, url, "", nil)
        assert.Equal(t, http.StatusOK, w.Code)
        var products []map[string]interface{}
        json.NewDecoder(w.Body).Decode(&products)
        assert.Len(t, products, 1)
        assert.Equal(t, prodMatch.ID, products[0]["id"])
    })
}

func TestPlaceBid(t *testing.T) {
    t.Run("success", func(t *testing.T) {
        svc, cleanup := newTestMarketplaceService(t)
        defer cleanup()
        seller := "seller1"
        prod := createProduct(t, svc, seller)
        router := newTestRouter(svc, authMiddleware())
        body := `{"product_id":"` + prod.ID + `","amount":150.0,"currency":"USD"}`
        key := t.Name() + "-key"
        w := doRequest(t, router, http.MethodPost, "/api/marketplace/bids", body, map[string]string{"X-User-ID": "bidder1", "Idempotency-Key": key})
        assert.Equal(t, http.StatusCreated, w.Code)
        var resp map[string]interface{}
        json.NewDecoder(w.Body).Decode(&resp)
        assert.Contains(t, resp, "bid")
        bid := resp["bid"].(map[string]interface{})
        assert.Equal(t, prod.ID, bid["product_id"])
        assert.Equal(t, 150.0, bid["amount"])
    })

    t.Run("unauthorized", func(t *testing.T) {
        svc, cleanup := newTestMarketplaceService(t)
        defer cleanup()
        router := newTestRouter(svc) // no auth
        body := `{"product_id":"p1","amount":100,"currency":"USD"}`
        w := doRequest(t, router, http.MethodPost, "/api/marketplace/bids", body, map[string]string{"Idempotency-Key": "key"})
        assert.Equal(t, http.StatusUnauthorized, w.Code)
    })

    t.Run("invalid_json", func(t *testing.T) {
        svc, cleanup := newTestMarketplaceService(t)
        defer cleanup()
        router := newTestRouter(svc, authMiddleware())
        body := `{invalid}`
        w := doRequest(t, router, http.MethodPost, "/api/marketplace/bids", body, map[string]string{"X-User-ID": "user1", "Idempotency-Key": "key"})
        assert.Equal(t, http.StatusBadRequest, w.Code)
    })

    t.Run("missing_required_fields", func(t *testing.T) {
        svc, cleanup := newTestMarketplaceService(t)
        defer cleanup()
        router := newTestRouter(svc, authMiddleware())
        body := `{"product_id":"p1"}`
        w := doRequest(t, router, http.MethodPost, "/api/marketplace/bids", body, map[string]string{"X-User-ID": "user1", "Idempotency-Key": "key"})
        assert.Equal(t, http.StatusInternalServerError, w.Code)
    })

    t.Run("missing_idempotency_key", func(t *testing.T) {
        svc, cleanup := newTestMarketplaceService(t)
        defer cleanup()
        prod := createProduct(t, svc, "seller1")
        router := newTestRouter(svc, authMiddleware())
        body := `{"product_id":"` + prod.ID + `","amount":100,"currency":"USD"}`
        w := doRequest(t, router, http.MethodPost, "/api/marketplace/bids", body, map[string]string{"X-User-ID": "bidder1"})
        assert.Equal(t, http.StatusBadRequest, w.Code)
    })

    t.Run("duplicate_idempotency", func(t *testing.T) {
        svc, cleanup := newTestMarketplaceService(t)
        defer cleanup()
        prod := createProduct(t, svc, "seller1")
        router := newTestRouter(svc, authMiddleware())
        body := `{"product_id":"` + prod.ID + `","amount":200,"currency":"USD"}`
        key := t.Name() + "-dup-key"
        // First call
        w1 := doRequest(t, router, http.MethodPost, "/api/marketplace/bids", body, map[string]string{"X-User-ID": "bidder1", "Idempotency-Key": key})
        assert.Equal(t, http.StatusCreated, w1.Code)
        var resp1 map[string]interface{}
        json.NewDecoder(w1.Body).Decode(&resp1)
        bidID := resp1["bid"].(map[string]interface{})["id"]
        // Second call same key
        w2 := doRequest(t, router, http.MethodPost, "/api/marketplace/bids", body, map[string]string{"X-User-ID": "bidder1", "Idempotency-Key": key})
        assert.Equal(t, http.StatusOK, w2.Code)
        var resp2 map[string]interface{}
        json.NewDecoder(w2.Body).Decode(&resp2)
        assert.Equal(t, bidID, resp2["bid"].(map[string]interface{})["id"])
    })

    t.Run("product_not_found", func(t *testing.T) {
        svc, cleanup := newTestMarketplaceService(t)
        defer cleanup()
        router := newTestRouter(svc, authMiddleware())
        body := `{"product_id":"nonexistent","amount":100,"currency":"USD"}`
        w := doRequest(t, router, http.MethodPost, "/api/marketplace/bids", body, map[string]string{"X-User-ID": "user1", "Idempotency-Key": "key"})
        assert.Equal(t, http.StatusInternalServerError, w.Code)
    })

    t.Run("bid_on_own_product", func(t *testing.T) {
        svc, cleanup := newTestMarketplaceService(t)
        defer cleanup()
        prod := createProduct(t, svc, "user1")
        router := newTestRouter(svc, authMiddleware())
        body := `{"product_id":"` + prod.ID + `","amount":100,"currency":"USD"}`
        w := doRequest(t, router, http.MethodPost, "/api/marketplace/bids", body, map[string]string{"X-User-ID": "user1", "Idempotency-Key": "key"})
        assert.Equal(t, http.StatusForbidden, w.Code)
    })

    t.Run("amount_zero", func(t *testing.T) {
        svc, cleanup := newTestMarketplaceService(t)
        defer cleanup()
        prod := createProduct(t, svc, "seller1")
        router := newTestRouter(svc, authMiddleware())
        body := `{"product_id":"` + prod.ID + `","amount":0,"currency":"USD"}`
        w := doRequest(t, router, http.MethodPost, "/api/marketplace/bids", body, map[string]string{"X-User-ID": "bidder1", "Idempotency-Key": "key"})
        assert.Equal(t, http.StatusInternalServerError, w.Code)
    })

    t.Run("amount_too_low", func(t *testing.T) {
        svc, cleanup := newTestMarketplaceService(t)
        defer cleanup()
        prod := createProduct(t, svc, "seller1") // price 10
        router := newTestRouter(svc, authMiddleware())
        body := `{"product_id":"` + prod.ID + `","amount":5,"currency":"USD"}`
        w := doRequest(t, router, http.MethodPost, "/api/marketplace/bids", body, map[string]string{"X-User-ID": "bidder1", "Idempotency-Key": "key"})
        assert.Equal(t, http.StatusInternalServerError, w.Code)
    })

    t.Run("amount_not_higher_than_current", func(t *testing.T) {
        svc, cleanup := newTestMarketplaceService(t)
        defer cleanup()
        prod := createProduct(t, svc, "seller1")
        createBid(t, svc, prod.ID, "bidder1", 20)
        router := newTestRouter(svc, authMiddleware())
        body := `{"product_id":"` + prod.ID + `","amount":15,"currency":"USD"}`
        w := doRequest(t, router, http.MethodPost, "/api/marketplace/bids", body, map[string]string{"X-User-ID": "bidder2", "Idempotency-Key": "key"})
        assert.Equal(t, http.StatusInternalServerError, w.Code)
    })
}

func TestGetBidsForProduct(t *testing.T) {
    t.Run("success_with_bids", func(t *testing.T) {
        svc, cleanup := newTestMarketplaceService(t)
        defer cleanup()
        prod := createProduct(t, svc, "seller1")
        createBid(t, svc, prod.ID, "bidder1", 100)
        createBid(t, svc, prod.ID, "bidder2", 150)
        router := newTestRouter(svc)
        w := doRequest(t, router, http.MethodGet, "/api/marketplace/products/"+prod.ID+"/bids", "", nil)
        assert.Equal(t, http.StatusOK, w.Code)
        var bids []map[string]interface{}
        json.NewDecoder(w.Body).Decode(&bids)
        assert.Len(t, bids, 2)
        // Sorted descending by amount
        assert.Greater(t, bids[0]["amount"].(float64), bids[1]["amount"].(float64))
    })

    t.Run("success_no_bids", func(t *testing.T) {
        svc, cleanup := newTestMarketplaceService(t)
        defer cleanup()
        prod := createProduct(t, svc, "seller1")
        router := newTestRouter(svc)
        w := doRequest(t, router, http.MethodGet, "/api/marketplace/products/"+prod.ID+"/bids", "", nil)
        assert.Equal(t, http.StatusOK, w.Code)
        var bids []map[string]interface{}
        json.NewDecoder(w.Body).Decode(&bids)
        assert.Len(t, bids, 0)
    })
}

func TestAcceptBid(t *testing.T) {
    t.Run("success", func(t *testing.T) {
        svc, cleanup := newTestMarketplaceService(t)
        defer cleanup()
        seller := "seller1"
        prod := createProduct(t, svc, seller)
        bid := createBid(t, svc, prod.ID, "bidder1", 200)
        router := newTestRouter(svc, authMiddleware())
        key := t.Name() + "-key"
        w := doRequest(t, router, http.MethodPost, "/api/marketplace/bids/"+bid.ID+"/accept", "", map[string]string{"X-User-ID": seller, "Idempotency-Key": key})
        assert.Equal(t, http.StatusOK, w.Code)
        var resp map[string]interface{}
        json.NewDecoder(w.Body).Decode(&resp)
        assert.Contains(t, resp, "transaction")
        tx := resp["transaction"].(map[string]interface{})
        assert.Equal(t, prod.ID, tx["product_id"])
    })

    t.Run("unauthorized", func(t *testing.T) {
        svc, cleanup := newTestMarketplaceService(t)
        defer cleanup()
        prod := createProduct(t, svc, "seller1")
        bid := createBid(t, svc, prod.ID, "bidder1", 100)
        router := newTestRouter(svc) // no auth
        w := doRequest(t, router, http.MethodPost, "/api/marketplace/bids/"+bid.ID+"/accept", "", map[string]string{"Idempotency-Key": "key"})
        assert.Equal(t, http.StatusUnauthorized, w.Code)
    })

    t.Run("missing_idempotency_key", func(t *testing.T) {
        svc, cleanup := newTestMarketplaceService(t)
        defer cleanup()
        prod := createProduct(t, svc, "seller1")
        bid := createBid(t, svc, prod.ID, "bidder1", 100)
        router := newTestRouter(svc, authMiddleware())
        w := doRequest(t, router, http.MethodPost, "/api/marketplace/bids/"+bid.ID+"/accept", "", map[string]string{"X-User-ID": "seller1"})
        assert.Equal(t, http.StatusBadRequest, w.Code)
    })

    t.Run("duplicate_idempotency", func(t *testing.T) {
        svc, cleanup := newTestMarketplaceService(t)
        defer cleanup()
        seller := "seller1"
        prod := createProduct(t, svc, seller)
        bid := createBid(t, svc, prod.ID, "bidder1", 200)
        router := newTestRouter(svc, authMiddleware())
        key := t.Name() + "-dup-key"
        w1 := doRequest(t, router, http.MethodPost, "/api/marketplace/bids/"+bid.ID+"/accept", "", map[string]string{"X-User-ID": seller, "Idempotency-Key": key})
        assert.Equal(t, http.StatusOK, w1.Code)
        var resp1 map[string]interface{}
        json.NewDecoder(w1.Body).Decode(&resp1)
        txID1 := resp1["transaction"].(map[string]interface{})["id"]
        w2 := doRequest(t, router, http.MethodPost, "/api/marketplace/bids/"+bid.ID+"/accept", "", map[string]string{"X-User-ID": seller, "Idempotency-Key": key})
        assert.Equal(t, http.StatusOK, w2.Code)
        var resp2 map[string]interface{}
        json.NewDecoder(w2.Body).Decode(&resp2)
        txID2 := resp2["transaction"].(map[string]interface{})["id"]
        assert.Equal(t, txID1, txID2)
    })

    t.Run("bid_not_found", func(t *testing.T) {
        svc, cleanup := newTestMarketplaceService(t)
        defer cleanup()
        router := newTestRouter(svc, authMiddleware())
        w := doRequest(t, router, http.MethodPost, "/api/marketplace/bids/missing/accept", "", map[string]string{"X-User-ID": "seller1", "Idempotency-Key": "key"})
        assert.Equal(t, http.StatusNotFound, w.Code)
    })

    t.Run("product_not_found", func(t *testing.T) {
        svc, cleanup := newTestMarketplaceService(t)
        defer cleanup()
        seller := "seller1"
        prod := createProduct(t, svc, seller)
        bid := createBid(t, svc, prod.ID, "bidder1", 100)
        // Delete product to simulate missing
        err := svc.DeleteProduct(prod.ID, seller)
        require.NoError(t, err)
        router := newTestRouter(svc, authMiddleware())
        w := doRequest(t, router, http.MethodPost, "/api/marketplace/bids/"+bid.ID+"/accept", "", map[string]string{"X-User-ID": seller, "Idempotency-Key": "key"})
        assert.Equal(t, http.StatusNotFound, w.Code)
    })

    t.Run("forbidden_not_seller", func(t *testing.T) {
        svc, cleanup := newTestMarketplaceService(t)
        defer cleanup()
        seller := "seller1"
        prod := createProduct(t, svc, seller)
        bid := createBid(t, svc, prod.ID, "bidder1", 100)
        router := newTestRouter(svc, authMiddleware())
        w := doRequest(t, router, http.MethodPost, "/api/marketplace/bids/"+bid.ID+"/accept", "", map[string]string{"X-User-ID": "otheruser", "Idempotency-Key": "key"})
        assert.Equal(t, http.StatusForbidden, w.Code)
    })

    t.Run("bid_not_active", func(t *testing.T) {
        svc, cleanup := newTestMarketplaceService(t)
        defer cleanup()
        seller := "seller1"
        prod := createProduct(t, svc, seller)
        bid := createBid(t, svc, prod.ID, "bidder1", 100)
        // Accept the bid first
        router1 := newTestRouter(svc, authMiddleware())
        _ = doRequest(t, router1, http.MethodPost, "/api/marketplace/bids/"+bid.ID+"/accept", "", map[string]string{"X-User-ID": seller, "Idempotency-Key": "key1"})
        // Try to accept again
        w := doRequest(t, router1, http.MethodPost, "/api/marketplace/bids/"+bid.ID+"/accept", "", map[string]string{"X-User-ID": seller, "Idempotency-Key": "key2"})
        assert.Equal(t, http.StatusInternalServerError, w.Code)
    })
}

func TestBuyNow(t *testing.T) {
    t.Run("success", func(t *testing.T) {
        svc, cleanup := newTestMarketplaceService(t)
        defer cleanup()
        seller := "seller1"
        buyer := "buyer1"
        prod := createProduct(t, svc, seller)
        router := newTestRouter(svc, authMiddleware())
        key := t.Name() + "-key"
        w := doRequest(t, router, http.MethodPost, "/api/marketplace/products/"+prod.ID+"/buy", "", map[string]string{"X-User-ID": buyer, "Idempotency-Key": key})
        assert.Equal(t, http.StatusOK, w.Code)
        var resp map[string]interface{}
        json.NewDecoder(w.Body).Decode(&resp)
        assert.Contains(t, resp, "transaction")
        tx := resp["transaction"].(map[string]interface{})
        assert.Equal(t, buyer, tx["buyer_id"])
        assert.Equal(t, seller, tx["seller_id"])
    })

    t.Run("unauthorized", func(t *testing.T) {
        svc, cleanup := newTestMarketplaceService(t)
        defer cleanup()
        prod := createProduct(t, svc, "seller1")
        router := newTestRouter(svc) // no auth
        w := doRequest(t, router, http.MethodPost, "/api/marketplace/products/"+prod.ID+"/buy", "", map[string]string{"Idempotency-Key": "key"})
        assert.Equal(t, http.StatusUnauthorized, w.Code)
    })

    t.Run("missing_idempotency_key", func(t *testing.T) {
        svc, cleanup := newTestMarketplaceService(t)
        defer cleanup()
        prod := createProduct(t, svc, "seller1")
        router := newTestRouter(svc, authMiddleware())
        w := doRequest(t, router, http.MethodPost, "/api/marketplace/products/"+prod.ID+"/buy", "", map[string]string{"X-User-ID": "buyer1"})
        assert.Equal(t, http.StatusBadRequest, w.Code)
    })

    t.Run("duplicate_idempotency", func(t *testing.T) {
        svc, cleanup := newTestMarketplaceService(t)
        defer cleanup()
        seller := "seller1"
        buyer := "buyer1"
        prod := createProduct(t, svc, seller)
        router := newTestRouter(svc, authMiddleware())
        key := t.Name() + "-dup-key"
        w1 := doRequest(t, router, http.MethodPost, "/api/marketplace/products/"+prod.ID+"/buy", "", map[string]string{"X-User-ID": buyer, "Idempotency-Key": key})
        assert.Equal(t, http.StatusOK, w1.Code)
        var resp1 map[string]interface{}
        json.NewDecoder(w1.Body).Decode(&resp1)
        txID := resp1["transaction"].(map[string]interface{})["id"]
        w2 := doRequest(t, router, http.MethodPost, "/api/marketplace/products/"+prod.ID+"/buy", "", map[string]string{"X-User-ID": buyer, "Idempotency-Key": key})
        assert.Equal(t, http.StatusOK, w2.Code)
        var resp2 map[string]interface{}
        json.NewDecoder(w2.Body).Decode(&resp2)
        assert.Equal(t, txID, resp2["transaction"].(map[string]interface{})["id"])
    })

    t.Run("product_not_found", func(t *testing.T) {
        svc, cleanup := newTestMarketplaceService(t)
        defer cleanup()
        router := newTestRouter(svc, authMiddleware())
        w := doRequest(t, router, http.MethodPost, "/api/marketplace/products/nonexistent/buy", "", map[string]string{"X-User-ID": "buyer1", "Idempotency-Key": "key"})
        assert.Equal(t, http.StatusInternalServerError, w.Code)
    })

    t.Run("product_not_active", func(t *testing.T) {
        svc, cleanup := newTestMarketplaceService(t)
        defer cleanup()
        seller := "seller1"
        buyer := "buyer1"
        prod := createProduct(t, svc, seller)
        // Mark product as sold via service directly
        _, err := svc.BuyNow(prod.ID, "anotherbuyer")
        require.NoError(t, err)
        router := newTestRouter(svc, authMiddleware())
        w := doRequest(t, router, http.MethodPost, "/api/marketplace/products/"+prod.ID+"/buy", "", map[string]string{"X-User-ID": buyer, "Idempotency-Key": "key"})
        assert.Equal(t, http.StatusInternalServerError, w.Code)
    })

    t.Run("buyer_is_seller", func(t *testing.T) {
        svc, cleanup := newTestMarketplaceService(t)
        defer cleanup()
        user := "user1"
        prod := createProduct(t, svc, user)
        router := newTestRouter(svc, authMiddleware())
        w := doRequest(t, router, http.MethodPost, "/api/marketplace/products/"+prod.ID+"/buy", "", map[string]string{"X-User-ID": user, "Idempotency-Key": "key"})
        assert.Equal(t, http.StatusInternalServerError, w.Code)
    })
}

func TestCompleteTransaction(t *testing.T) {
    t.Run("success", func(t *testing.T) {
        svc, cleanup := newTestMarketplaceService(t)
        defer cleanup()
        buyer := "buyer1"
        prod := createProduct(t, svc, "seller1")
        tx, err := svc.BuyNow(prod.ID, buyer)
        require.NoError(t, err)
        router := newTestRouter(svc) // no auth
        w := doRequest(t, router, http.MethodPost, "/api/marketplace/transactions/"+tx.ID+"/complete", "", nil)
        assert.Equal(t, http.StatusOK, w.Code)
        var resp map[string]interface{}
        json.NewDecoder(w.Body).Decode(&resp)
        assert.Equal(t, "completed", resp["status"])
    })

    t.Run("transaction_not_found", func(t *testing.T) {
        svc, cleanup := newTestMarketplaceService(t)
        defer cleanup()
        router := newTestRouter(svc)
        w := doRequest(t, router, http.MethodPost, "/api/marketplace/transactions/missing/complete", "", nil)
        assert.Equal(t, http.StatusInternalServerError, w.Code)
    })

    t.Run("transaction_not_pending", func(t *testing.T) {
        svc, cleanup := newTestMarketplaceService(t)
        defer cleanup()
        buyer := "buyer1"
        prod := createProduct(t, svc, "seller1")
        tx, _ := svc.BuyNow(prod.ID, buyer)
        router := newTestRouter(svc)
        // Complete once
        _ = doRequest(t, router, http.MethodPost, "/api/marketplace/transactions/"+tx.ID+"/complete", "", nil)
        // Try again
        w := doRequest(t, router, http.MethodPost, "/api/marketplace/transactions/"+tx.ID+"/complete", "", nil)
        assert.Equal(t, http.StatusInternalServerError, w.Code)
    })
}

func TestGetTransaction(t *testing.T) {
    t.Run("success", func(t *testing.T) {
        svc, cleanup := newTestMarketplaceService(t)
        defer cleanup()
        buyer := "buyer1"
        prod := createProduct(t, svc, "seller1")
        tx, _ := svc.BuyNow(prod.ID, buyer)
        router := newTestRouter(svc)
        w := doRequest(t, router, http.MethodGet, "/api/marketplace/transactions/"+tx.ID, "", nil)
        assert.Equal(t, http.StatusOK, w.Code)
    })

    t.Run("not_found", func(t *testing.T) {
        svc, cleanup := newTestMarketplaceService(t)
        defer cleanup()
        router := newTestRouter(svc)
        w := doRequest(t, router, http.MethodGet, "/api/marketplace/transactions/missing", "", nil)
        assert.Equal(t, http.StatusNotFound, w.Code)
    })
}

func TestGetUserTransactions(t *testing.T) {
    t.Run("success", func(t *testing.T) {
        svc, cleanup := newTestMarketplaceService(t)
        defer cleanup()
        user := "user1"
        // As buyer
        prod1 := createProduct(t, svc, "sellerA")
        tx1, _ := svc.BuyNow(prod1.ID, user)
        // As seller
        prod2 := createProduct(t, svc, user)
        tx2, _ := svc.BuyNow(prod2.ID, "buyerB")
        router := newTestRouter(svc, authMiddleware())
        w := doRequest(t, router, http.MethodGet, "/api/marketplace/transactions", "", map[string]string{"X-User-ID": user})
        assert.Equal(t, http.StatusOK, w.Code)
        var txs []map[string]interface{}
        json.NewDecoder(w.Body).Decode(&txs)
        assert.Len(t, txs, 2)
        // Check IDs present
        ids := make(map[string]bool)
        for _, tx := range txs {
            ids[tx["id"].(string)] = true
        }
        assert.True(t, ids[tx1.ID])
        assert.True(t, ids[tx2.ID])
    })

    t.Run("unauthorized", func(t *testing.T) {
        svc, cleanup := newTestMarketplaceService(t)
        defer cleanup()
        router := newTestRouter(svc) // no auth
        w := doRequest(t, router, http.MethodGet, "/api/marketplace/transactions", "", nil)
        assert.Equal(t, http.StatusUnauthorized, w.Code)
    })
}

func TestGetUserProducts(t *testing.T) {
    t.Run("success", func(t *testing.T) {
        svc, cleanup := newTestMarketplaceService(t)
        defer cleanup()
        user := "user1"
        createProduct(t, svc, user)
        createProduct(t, svc, user)
        router := newTestRouter(svc, authMiddleware())
        w := doRequest(t, router, http.MethodGet, "/api/marketplace/my-products", "", map[string]string{"X-User-ID": user})
        assert.Equal(t, http.StatusOK, w.Code)
        var products []map[string]interface{}
        json.NewDecoder(w.Body).Decode(&products)
        assert.Len(t, products, 2)
    })

    t.Run("unauthorized", func(t *testing.T) {
        svc, cleanup := newTestMarketplaceService(t)
        defer cleanup()
        router := newTestRouter(svc)
        w := doRequest(t, router, http.MethodGet, "/api/marketplace/my-products", "", nil)
        assert.Equal(t, http.StatusUnauthorized, w.Code)
    })
}

func TestGetCategories(t *testing.T) {
    t.Run("success", func(t *testing.T) {
        svc, cleanup := newTestMarketplaceService(t)
        defer cleanup()
        // Create products with different categories
        createProduct(t, svc, "user1") // category testcat
        req := &entity.CreateProductRequest{
            Title:       "Electronics Item",
            Description: "Exp",
            Price:       100,
            Currency:    "USD",
            Category:    "electronics",
            Condition:   "new",
        }
        svc.CreateProduct(req, "user2")
        router := newTestRouter(svc)
        w := doRequest(t, router, http.MethodGet, "/api/marketplace/categories", "", nil)
        assert.Equal(t, http.StatusOK, w.Code)
        var categories []string
        json.NewDecoder(w.Body).Decode(&categories)
        assert.Contains(t, categories, "testcat")
        assert.Contains(t, categories, "electronics")
    })

    t.Run("empty_no_products", func(t *testing.T) {
        svc, cleanup := newTestMarketplaceService(t)
        defer cleanup()
        router := newTestRouter(svc)
        w := doRequest(t, router, http.MethodGet, "/api/marketplace/categories", "", nil)
        assert.Equal(t, http.StatusOK, w.Code)
        var categories []string
        json.NewDecoder(w.Body).Decode(&categories)
        assert.Len(t, categories, 0)
    })
}
