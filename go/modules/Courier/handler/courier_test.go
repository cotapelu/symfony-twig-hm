package handler

import (
	"bytes"
	"database/sql"
	"encoding/json"
	"fmt"
	"net/http"
	"net/http/httptest"
	"testing"
	"time"

	_ "github.com/mattn/go-sqlite3"
	"github.com/gin-gonic/gin"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"

)

// ==================== Schema Setup ====================

func createTestDB(t *testing.T) *sql.DB {
	t.Helper()
	db, err := sql.Open("sqlite3", ":memory:")
	require.NoError(t, err)

	// Create couriers table
	_, err = db.Exec(`
		CREATE TABLE couriers (
			id INTEGER PRIMARY KEY AUTOINCREMENT,
			user_id TEXT NOT NULL,
			name TEXT NOT NULL,
			phone TEXT NOT NULL,
			email TEXT,
			vehicle_type TEXT NOT NULL,
			vehicle_number TEXT NOT NULL,
			status TEXT NOT NULL,
			latitude REAL,
			longitude REAL,
			rating REAL DEFAULT 5.0,
			total_deliveries INTEGER DEFAULT 0,
			available_balance REAL DEFAULT 0,
			created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
		);
	`)
	require.NoError(t, err)

	// Create courier_orders table
	_, err = db.Exec(`
		CREATE TABLE courier_orders (
			id INTEGER PRIMARY KEY AUTOINCREMENT,
			order_id TEXT NOT NULL,
			courier_id INTEGER,
			order_type TEXT NOT NULL,
			pickup_address TEXT NOT NULL,
			pickup_lat REAL NOT NULL,
			pickup_lng REAL NOT NULL,
			delivery_address TEXT NOT NULL,
			delivery_lat REAL NOT NULL,
			delivery_lng REAL NOT NULL,
			customer_name TEXT NOT NULL,
			customer_phone TEXT NOT NULL,
			package_desc TEXT,
			package_weight REAL NOT NULL,
			distance REAL,
			fee REAL,
			status TEXT NOT NULL,
			assigned_at TIMESTAMP,
			picked_up_at TIMESTAMP,
			delivered_at TIMESTAMP,
			created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
		);
	`)
	require.NoError(t, err)

	return db
}

// ==================== Test Helpers ====================

type testContext struct {
	t      *testing.T
	db     *sql.DB
	handler *CourierHandler
	router *gin.Engine
}

func newTestContext(t *testing.T) *testContext {
	t.Helper()
	db := createTestDB(t)
	h := NewCourierHandler(db)
	r := gin.New()
	// Auth middleware that reads X-User-ID and optionally X-User-Roles
	r.Use(func(c *gin.Context) {
		userID := c.GetHeader("X-User-ID")
		if userID == "" {
			c.JSON(http.StatusUnauthorized, gin.H{"error": "authentication required"})
			c.Abort()
			return
		}
		c.Set("user_id", userID)
		rolesHeader := c.GetHeader("X-User-Roles")
		if rolesHeader != "" {
			var roles []string
			json.Unmarshal([]byte(rolesHeader), &roles)
			c.Set("user_roles", roles)
		}
		c.Next()
	})
	// Register routes
	h.RegisterRoutes(r)
	return &testContext{t: t, db: db, handler: h, router: r}
}

func (tc *testContext) close() {
	tc.db.Close()
}

func (tc *testContext) do(method, path string, body interface{}, headers map[string]string) *httptest.ResponseRecorder {
	var reqBody []byte
	if body != nil {
		jsonBytes, _ := json.Marshal(body)
		reqBody = jsonBytes
	}
	req := httptest.NewRequest(method, path, bytes.NewReader(reqBody))
	req.Header.Set("Content-Type", "application/json")
	for k, v := range headers {
		req.Header.Set(k, v)
	}
	w := httptest.NewRecorder()
	tc.router.ServeHTTP(w, req)
	return w
}

// ==================== Tests ====================

func TestCourierHandler_RegisterCourier(t *testing.T) {
	gin.SetMode(gin.TestMode)
	tc := newTestContext(t)
	defer tc.close()

	// Success
	w := tc.do(http.MethodPost, "/api/courier/register", map[string]interface{}{
		"user_id":        "user1",
		"name":           "John Doe",
		"phone":          "0123456789",
		"email":          "john@example.com",
		"vehicle_type":   "motorcycle",
		"vehicle_number": "123AB",
	}, map[string]string{"X-User-ID": "user1"})
	assert.Equal(t, http.StatusCreated, w.Code)
	var resp map[string]interface{}
	json.NewDecoder(w.Body).Decode(&resp)
	assert.Contains(t, resp, "courier")
	courier := resp["courier"].(map[string]interface{})
	assert.Equal(t, "user1", courier["user_id"])
	assert.Equal(t, "available", courier["status"])

	// Missing required fields
	w = tc.do(http.MethodPost, "/api/courier/register", map[string]interface{}{
		"user_id": "user2",
		"name":    "Jane",
		// missing phone, vehicle_type, vehicle_number
	}, map[string]string{"X-User-ID": "any"})
	assert.Equal(t, http.StatusBadRequest, w.Code)

	// Unauthorized
	req := httptest.NewRequest(http.MethodPost, "/api/courier/register", bytes.NewReader([]byte("{}")))
	req.Header.Set("Content-Type", "application/json")
	w2 := httptest.NewRecorder()
	tc.router.ServeHTTP(w2, req)
	assert.Equal(t, http.StatusUnauthorized, w2.Code)
}

func TestCourierHandler_GetCourier(t *testing.T) {
	gin.SetMode(gin.TestMode)
	tc := newTestContext(t)
	defer tc.close()

	// Create a courier first via DB
	_, err := tc.db.Exec(`
		INSERT INTO couriers (user_id, name, phone, email, vehicle_type, vehicle_number, status, latitude, longitude, rating, total_deliveries, available_balance)
		VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
		"user1", "Courier One", "0123456789", "c1@example.com", "bike", "BIKE123", "available", 10.0, 106.0, 5.0, 0, 0.0,
	)
	require.NoError(t, err)
	courierID := getLastInsertRowID(t, tc.db)

	// Success: own courier
	w := tc.do(http.MethodGet, fmt.Sprintf("/api/courier/%d", courierID), nil, map[string]string{"X-User-ID": "user1"})
	assert.Equal(t, http.StatusOK, w.Code)

	// Not found (with auth)
	w = tc.do(http.MethodGet, "/api/courier/999999", nil, map[string]string{"X-User-ID": "any"})
	assert.Equal(t, http.StatusNotFound, w.Code)

	// Access another courier (should be forbidden)
	w = tc.do(http.MethodGet, fmt.Sprintf("/api/courier/%d", courierID), nil, map[string]string{"X-User-ID": "other_user"})
	assert.Equal(t, http.StatusForbidden, w.Code)

	// Admin can access any courier
	adminHeaders := map[string]string{
		"X-User-ID":   "admin",
		"X-User-Roles": `["admin"]`,
	}
	w = tc.do(http.MethodGet, fmt.Sprintf("/api/courier/%d", courierID), nil, adminHeaders)
	assert.Equal(t, http.StatusOK, w.Code)
}

func TestCourierHandler_GetAvailableCouriers(t *testing.T) {
	gin.SetMode(gin.TestMode)
	tc := newTestContext(t)
	defer tc.close()

	// Insert couriers: some available, some not
	insertCourier := func(userID, status string, rating float64) int64 {
		_, err := tc.db.Exec(`
			INSERT INTO couriers (user_id, name, phone, email, vehicle_type, vehicle_number, status, latitude, longitude, rating, total_deliveries, available_balance)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
			userID, "Name", "0123456789", "email@example.com", "bike", "BIKE1", status, 10.0, 106.0, rating, 0, 0.0,
		)
		require.NoError(t, err)
		return getLastInsertRowID(t, tc.db)
	}
	insertCourier("u1", "available", 4.5)
	insertCourier("u2", "available", 5.0)
	insertCourier("u3", "busy", 3.2)
	insertCourier("u4", "available", 4.8)

	// Success, should return only available
	w := tc.do(http.MethodGet, "/api/courier/available", nil, map[string]string{"X-User-ID": "any"})
	assert.Equal(t, http.StatusOK, w.Code)
	var resp map[string]interface{}
	json.NewDecoder(w.Body).Decode(&resp)
	couriers := resp["couriers"].([]interface{})
	assert.Len(t, couriers, 3) // three available
}

func TestCourierHandler_UpdateLocation(t *testing.T) {
	gin.SetMode(gin.TestMode)
	tc := newTestContext(t)
	defer tc.close()

	// Insert courier
	_, err := tc.db.Exec(`
		INSERT INTO couriers (user_id, name, phone, email, vehicle_type, vehicle_number, status, latitude, longitude, rating, total_deliveries, available_balance)
		VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
		"user1", "Courier One", "0123456789", "c1@example.com", "bike", "BIKE123", "available", 0, 0, 5.0, 0, 0.0,
	)
	require.NoError(t, err)
	courierID := getLastInsertRowID(t, tc.db)

	// Invalid payload
	w := tc.do(http.MethodPut, fmt.Sprintf("/api/courier/%d/location", courierID), map[string]interface{}{
		"latitude": 10.0,
		// missing longitude
	}, map[string]string{"X-User-ID": "user1"})
	assert.Equal(t, http.StatusBadRequest, w.Code)

	// Success
	w = tc.do(http.MethodPut, fmt.Sprintf("/api/courier/%d/location", courierID), map[string]interface{}{
		"latitude":  10.7769,
		"longitude": 106.7009,
	}, map[string]string{"X-User-ID": "user1"})
	assert.Equal(t, http.StatusOK, w.Code)

	// Verify in DB
	var lat, lng float64
	err = tc.db.QueryRow("SELECT latitude, longitude FROM couriers WHERE id = ?", courierID).Scan(&lat, &lng)
	require.NoError(t, err)
	assert.Equal(t, 10.7769, lat)
	assert.Equal(t, 106.7009, lng)

	// Another user tries to update -> forbidden
	w = tc.do(http.MethodPut, fmt.Sprintf("/api/courier/%d/location", courierID), map[string]interface{}{
		"latitude":  11.0,
		"longitude": 107.0,
	}, map[string]string{"X-User-ID": "other"})
	assert.Equal(t, http.StatusForbidden, w.Code)

	// Admin can update
	adminHeaders := map[string]string{
		"X-User-ID":   "admin",
		"X-User-Roles": `["admin"]`,
	}
	w = tc.do(http.MethodPut, fmt.Sprintf("/api/courier/%d/location", courierID), map[string]interface{}{
		"latitude":  12.0,
		"longitude": 108.0,
	}, adminHeaders)
	assert.Equal(t, http.StatusOK, w.Code)
}

func TestCourierHandler_UpdateStatus(t *testing.T) {
	gin.SetMode(gin.TestMode)
	tc := newTestContext(t)
	defer tc.close()

	_, err := tc.db.Exec(`
		INSERT INTO couriers (user_id, name, phone, email, vehicle_type, vehicle_number, status, latitude, longitude, rating, total_deliveries, available_balance)
		VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
		"user1", "Courier One", "0123456789", "c1@example.com", "bike", "BIKE123", "available", 0, 0, 5.0, 0, 0.0,
	)
	require.NoError(t, err)
	courierID := getLastInsertRowID(t, tc.db)

	// Missing status
	w := tc.do(http.MethodPut, fmt.Sprintf("/api/courier/%d/status", courierID), map[string]interface{}{}, map[string]string{"X-User-ID": "user1"})
	assert.Equal(t, http.StatusBadRequest, w.Code)

	// Success
	w = tc.do(http.MethodPut, fmt.Sprintf("/api/courier/%d/status", courierID), map[string]interface{}{
		"status": "busy",
	}, map[string]string{"X-User-ID": "user1"})
	assert.Equal(t, http.StatusOK, w.Code)

	// Verify
	var status string
	err = tc.db.QueryRow("SELECT status FROM couriers WHERE id = ?", courierID).Scan(&status)
	require.NoError(t, err)
	assert.Equal(t, "busy", status)

	// Forbidden for other user
	w = tc.do(http.MethodPut, fmt.Sprintf("/api/courier/%d/status", courierID), map[string]interface{}{
		"status": "available",
	}, map[string]string{"X-User-ID": "other"})
	assert.Equal(t, http.StatusForbidden, w.Code)
}

func TestCourierHandler_CreateOrder(t *testing.T) {
	gin.SetMode(gin.TestMode)
	tc := newTestContext(t)
	defer tc.close()

	// No courier exists yet - order creation doesn't require courier ID, but later assignment does
	// Just test success and validation

	w := tc.do(http.MethodPost, "/api/courier/orders", map[string]interface{}{
		"order_id":         "ORD001",
		"pickup_address":   "123 Main St",
		"pickup_lat":       10.0,
		"pickup_lng":       106.0,
		"delivery_address": "456 Side St",
		"delivery_lat":     10.1,
		"delivery_lng":     106.1,
		"customer_name":    "Alice",
		"customer_phone":   "0987654321",
		"package_weight":   2.5,
		// package_desc optional
	}, map[string]string{"X-User-ID": "any"})
	assert.Equal(t, http.StatusCreated, w.Code)
	var resp map[string]interface{}
	json.NewDecoder(w.Body).Decode(&resp)
	assert.Contains(t, resp, "order")
	assert.Contains(t, resp, "estimated_fee")
	order := resp["order"].(map[string]interface{})
	assert.Equal(t, "ORD001", order["order_id"])
	assert.Equal(t, "pending", order["status"])

	// Missing required field
	w = tc.do(http.MethodPost, "/api/courier/orders", map[string]interface{}{
		"order_id": "ORD002",
		// missing pickup_address, etc.
	}, map[string]string{"X-User-ID": "any"})
	assert.Equal(t, http.StatusBadRequest, w.Code)
}

func TestCourierHandler_GetOrder(t *testing.T) {
	gin.SetMode(gin.TestMode)
	tc := newTestContext(t)
	defer tc.close()

	// Insert a courier and an order
	_, err := tc.db.Exec(`
		INSERT INTO couriers (user_id, name, phone, email, vehicle_type, vehicle_number, status, latitude, longitude, rating, total_deliveries, available_balance)
		VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
		"user1", "Courier One", "0123456789", "c1@example.com", "bike", "BIKE123", "available", 0, 0, 5.0, 0, 0.0,
	)
	require.NoError(t, err)
	courierID := getLastInsertRowID(t, tc.db)

	_, err = tc.db.Exec(`
		INSERT INTO courier_orders (order_id, courier_id, order_type, pickup_address, pickup_lat, pickup_lng, delivery_address, delivery_lat, delivery_lng, customer_name, customer_phone, package_desc, package_weight, distance, fee, status)
		VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
		"ORD001", courierID, "standard", "PickupAddr", 10.0, 106.0, "DeliveryAddr", 10.1, 106.1, "Alice", "0987654321", "Package", 2.5, 5.0, 15000.0, "pending",
	)
	require.NoError(t, err)
	orderID := getLastInsertRowID(t, tc.db)

	// Get order by numeric ID path
	path := fmt.Sprintf("/api/courier/orders/%d", orderID)
	w := tc.do(http.MethodGet, path, nil, map[string]string{"X-User-ID": "user1"})
	assert.Equal(t, http.StatusOK, w.Code)

	// Not found
	w = tc.do(http.MethodGet, "/api/courier/orders/999999", nil, map[string]string{"X-User-ID": "user1"})
	assert.Equal(t, http.StatusNotFound, w.Code)

	// Forbidden: order belongs to courierID, user is not that courier nor admin
	// The order is assigned to courierID, and we are requesting as user2 (not the courier)
	w = tc.do(http.MethodGet, path, nil, map[string]string{"X-User-ID": "user2"})
	assert.Equal(t, http.StatusForbidden, w.Code)

	// Admin can access
	adminHeaders := map[string]string{
		"X-User-ID":   "admin",
		"X-User-Roles": `["admin"]`,
	}
	w = tc.do(http.MethodGet, path, nil, adminHeaders)
	assert.Equal(t, http.StatusOK, w.Code)
}

func TestCourierHandler_AssignOrder(t *testing.T) {
	gin.SetMode(gin.TestMode)
	tc := newTestContext(t)
	defer tc.close()

	// Create courier and order (unassigned: courier_id NULL)
	_, err := tc.db.Exec(`
		INSERT INTO couriers (user_id, name, phone, email, vehicle_type, vehicle_number, status, latitude, longitude, rating, total_deliveries, available_balance)
		VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
		"courier_user", "Courier One", "0123456789", "c1@example.com", "bike", "BIKE123", "available", 0, 0, 5.0, 0, 0.0,
	)
	require.NoError(t, err)
	courierID := getLastInsertRowID(t, tc.db)

	_, err = tc.db.Exec(`
		INSERT INTO courier_orders (order_id, courier_id, order_type, pickup_address, pickup_lat, pickup_lng, delivery_address, delivery_lat, delivery_lng, customer_name, customer_phone, package_desc, package_weight, distance, fee, status)
		VALUES (?, NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
		"ORD001", "standard", "PickupAddr", 10.0, 106.0, "DeliveryAddr", 10.1, 106.1, "Alice", "0987654321", "Package", 2.5, 5.0, 15000.0, "pending",
	)
	require.NoError(t, err)
	orderID := getLastInsertRowID(t, tc.db)

	// Missing body (admin required)
	adminHeaders := map[string]string{
		"X-User-ID":   "admin",
		"X-User-Roles": `["admin"]`,
	}
	w := tc.do(http.MethodPost, fmt.Sprintf("/api/courier/orders/%d/assign", orderID), nil, adminHeaders)
	assert.Equal(t, http.StatusBadRequest, w.Code)

	// Invalid courier_id number
	w = tc.do(http.MethodPost, fmt.Sprintf("/api/courier/orders/%d/assign", orderID), map[string]interface{}{
		"courier_id": "not_a_number",
	}, adminHeaders)
	assert.Equal(t, http.StatusBadRequest, w.Code)

	// Success assign
	w = tc.do(http.MethodPost, fmt.Sprintf("/api/courier/orders/%d/assign", orderID), map[string]interface{}{
		"courier_id": courierID,
	}, adminHeaders)
	assert.Equal(t, http.StatusOK, w.Code)

	// Verify order assigned
	var orderCourierID *int64
	err = tc.db.QueryRow("SELECT courier_id FROM courier_orders WHERE id = ?", orderID).Scan(&orderCourierID)
	require.NoError(t, err)
	require.NotNil(t, orderCourierID)
	assert.Equal(t, courierID, *orderCourierID)

	// Non-admin cannot assign
	w = tc.do(http.MethodPost, fmt.Sprintf("/api/courier/orders/%d/assign", orderID), map[string]interface{}{
		"courier_id": courierID,
	}, map[string]string{"X-User-ID": "user"})
	assert.Equal(t, http.StatusForbidden, w.Code)
}

func TestCourierHandler_PickupAndDeliver(t *testing.T) {
	gin.SetMode(gin.TestMode)
	tc := newTestContext(t)
	defer tc.close()

	// Create courier and order assigned
	_, err := tc.db.Exec(`
		INSERT INTO couriers (user_id, name, phone, email, vehicle_type, vehicle_number, status, latitude, longitude, rating, total_deliveries, available_balance)
		VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
		"courier_user", "Courier One", "0123456789", "c1@example.com", "bike", "BIKE123", "busy", 0, 0, 5.0, 0, 0.0,
	)
	require.NoError(t, err)
	courierID := getLastInsertRowID(t, tc.db)

	_, err = tc.db.Exec(`
		INSERT INTO courier_orders (order_id, courier_id, order_type, pickup_address, pickup_lat, pickup_lng, delivery_address, delivery_lat, delivery_lng, customer_name, customer_phone, package_desc, package_weight, distance, fee, status, assigned_at)
		VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
		"ORD001", courierID, "standard", "PickupAddr", 10.0, 106.0, "DeliveryAddr", 10.1, 106.1, "Alice", "0987654321", "Package", 2.5, 5.0, 15000.0, "assigned", timeNow(),
	)
	require.NoError(t, err)
	orderID := getLastInsertRowID(t, tc.db)

	// Pickup: must be the assigned courier
	w := tc.do(http.MethodPost, fmt.Sprintf("/api/courier/orders/%d/pickup", orderID), nil, map[string]string{"X-User-ID": "other"})
	assert.Equal(t, http.StatusForbidden, w.Code)

	w = tc.do(http.MethodPost, fmt.Sprintf("/api/courier/orders/%d/pickup", orderID), nil, map[string]string{"X-User-ID": "courier_user"})
	assert.Equal(t, http.StatusOK, w.Code)

	// Verify status changed to picked_up
	var status string
	err = tc.db.QueryRow("SELECT status FROM courier_orders WHERE id = ?", orderID).Scan(&status)
	require.NoError(t, err)
	assert.Equal(t, "picked_up", status)

	// Deliver
	w = tc.do(http.MethodPost, fmt.Sprintf("/api/courier/orders/%d/deliver", orderID), nil, map[string]string{"X-User-ID": "courier_user"})
	assert.Equal(t, http.StatusOK, w.Code)

	// Verify delivered
	err = tc.db.QueryRow("SELECT status FROM courier_orders WHERE id = ?", orderID).Scan(&status)
	require.NoError(t, err)
	assert.Equal(t, "delivered", status)

	// Verify courier total_deliveries incremented
	var deliveries int
	err = tc.db.QueryRow("SELECT total_deliveries FROM couriers WHERE id = ?", courierID).Scan(&deliveries)
	require.NoError(t, err)
	assert.Equal(t, 1, deliveries)
}

func TestCourierHandler_GetCourierOrders(t *testing.T) {
	gin.SetMode(gin.TestMode)
	tc := newTestContext(t)
	defer tc.close()

	// Create courier
	_, err := tc.db.Exec(`
		INSERT INTO couriers (user_id, name, phone, email, vehicle_type, vehicle_number, status, latitude, longitude, rating, total_deliveries, available_balance)
		VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
		"courier_user", "Courier One", "0123456789", "c1@example.com", "bike", "BIKE123", "available", 0, 0, 5.0, 0, 0.0,
	)
	require.NoError(t, err)
	courierID := getLastInsertRowID(t, tc.db)

	// Create 5 orders for this courier
	for i := 0; i < 5; i++ {
		_, err = tc.db.Exec(`
			INSERT INTO courier_orders (order_id, courier_id, order_type, pickup_address, pickup_lat, pickup_lng, delivery_address, delivery_lat, delivery_lng, customer_name, customer_phone, package_desc, package_weight, distance, fee, status)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
			fmt.Sprintf("ORD%03d", i+1), courierID, "standard", "Addr", 10.0, 106.0, "Addr2", 10.1, 106.1, "Cust", "Phone", "Pkg", 2.5, 5.0, 15000.0, "pending",
		)
		require.NoError(t, err)
	}

	// Pagination: page 1 limit 2
	w := tc.do(http.MethodGet, fmt.Sprintf("/api/courier/orders/courier/%d?page=1&limit=2", courierID), nil, map[string]string{"X-User-ID": "courier_user"})
	assert.Equal(t, http.StatusOK, w.Code)
	var resp map[string]interface{}
	json.NewDecoder(w.Body).Decode(&resp)
	data := resp["data"].([]interface{})
	assert.Len(t, data, 2)
	pagination := resp["pagination"].(map[string]interface{})
	assert.Equal(t, 1, int(pagination["current_page"].(float64)))
	assert.Equal(t, 2, int(pagination["per_page"].(float64)))
	assert.Equal(t, 5, int(pagination["total_items"].(float64)))
	assert.Equal(t, 3, int(pagination["total_pages"].(float64)))

	// Invalid pagination parameters (should default to safe values)
	w = tc.do(http.MethodGet, fmt.Sprintf("/api/courier/orders/courier/%d?page=0&limit=200", courierID), nil, map[string]string{"X-User-ID": "courier_user"})
	assert.Equal(t, http.StatusOK, w.Code)
}

func TestCourierHandler_GetActiveOrders(t *testing.T) {
	gin.SetMode(gin.TestMode)
	tc := newTestContext(t)
	defer tc.close()

	_, err := tc.db.Exec(`
		INSERT INTO couriers (user_id, name, phone, email, vehicle_type, vehicle_number, status, latitude, longitude, rating, total_deliveries, available_balance)
		VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
		"courier_user", "Courier One", "0123456789", "c1@example.com", "bike", "BIKE123", "available", 0, 0, 5.0, 0, 0.0,
	)
	require.NoError(t, err)
	courierID := getLastInsertRowID(t, tc.db)

	// Create orders: one assigned, one picked_up, one delivered, one pending
	orders := []struct{ idStr, status string }{
		{"ORD001", "assigned"},
		{"ORD002", "picked_up"},
		{"ORD003", "delivered"},
		{"ORD004", "pending"},
	}
	for _, o := range orders {
		_, err = tc.db.Exec(`
			INSERT INTO courier_orders (order_id, courier_id, order_type, pickup_address, pickup_lat, pickup_lng, delivery_address, delivery_lat, delivery_lng, customer_name, customer_phone, package_desc, package_weight, distance, fee, status)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
			o.idStr, courierID, "standard", "Addr", 10.0, 106.0, "Addr2", 10.1, 106.1, "Cust", "Phone", "Pkg", 2.5, 5.0, 15000.0, o.status,
		)
		require.NoError(t, err)
	}

	// Get active orders (assigned, picked_up)
	w := tc.do(http.MethodGet, fmt.Sprintf("/api/courier/orders/courier/%d/active", courierID), nil, map[string]string{"X-User-ID": "courier_user"})
	assert.Equal(t, http.StatusOK, w.Code)
	var resp map[string]interface{}
	json.NewDecoder(w.Body).Decode(&resp)
	activeOrders := resp["orders"].([]interface{})
	assert.Len(t, activeOrders, 2) // assigned and picked_up
}

func TestCourierHandler_GetAvailableCouriers_Unauthenticated(t *testing.T) {
	gin.SetMode(gin.TestMode)
	tc := newTestContext(t)
	defer tc.close()

	// No auth header
	req := httptest.NewRequest(http.MethodGet, "/api/courier/available", nil)
	w := httptest.NewRecorder()
	tc.router.ServeHTTP(w, req)
	assert.Equal(t, http.StatusUnauthorized, w.Code)
}

func TestCourierHandler_UpdateLocation_InvalidPath(t *testing.T) {
	gin.SetMode(gin.TestMode)
	tc := newTestContext(t)
	defer tc.close()

	// Invalid courier ID (non-numeric in path)
	req := httptest.NewRequest(http.MethodPut, "/api/courier/abc/location", bytes.NewReader([]byte(`{"latitude":10,"longitude":106}`)))
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("X-User-ID", "user1")
	w := httptest.NewRecorder()
	tc.router.ServeHTTP(w, req)
	assert.Equal(t, http.StatusBadRequest, w.Code)
}

// ==================== Helpers ====================

func getLastInsertRowID(t *testing.T, db *sql.DB) int64 {
	t.Helper()
	var id int64
	err := db.QueryRow("SELECT last_insert_rowid()").Scan(&id)
	require.NoError(t, err)
	return id
}

// Minimal time.Now function for use in tests; we can replace with controlled clock if needed.
func timeNow() string {
	return timeNowImpl()
}

// Overridable for tests
var timeNowImpl = func() string {
	return time.Now().Format(time.RFC3339)
}
