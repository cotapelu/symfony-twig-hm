package handler

import (
    "encoding/json"
    "fmt"
    "net/http"
    "testing"

    "github.com/gin-gonic/gin"
    "github.com/stretchr/testify/assert"
    "github.com/stretchr/testify/require"
)

// Note: Helpers (createTestDB, getLastInsertRowID, testContext, newTestContext) are defined in courier_test.go.

func TestDeliverOrder_Success(t *testing.T) {
    gin.SetMode(gin.TestMode)
    tc := newTestContext(t)
    defer tc.close()

    // Insert courier
    _, err := tc.db.Exec(`
        INSERT INTO couriers (user_id, name, phone, email, vehicle_type, vehicle_number, status, latitude, longitude, rating, total_deliveries, available_balance)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
        "courier_user", "Test Courier", "0123456789", "courier@example.com", "bike", "BIKE123", "available", 10.0, 106.0, 5.0, 0, 0.0,
    )
    require.NoError(t, err)
    courierID := getLastInsertRowID(t, tc.db)

    // Insert order (include distance and fee)
    _, err = tc.db.Exec(`
        INSERT INTO courier_orders (order_id, courier_id, order_type, pickup_address, pickup_lat, pickup_lng, delivery_address, delivery_lat, delivery_lng, customer_name, customer_phone, package_desc, package_weight, distance, fee, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
        "order123", courierID, "pickup", "123 Pickup St", 10.0, 106.0, "456 Delivery Ave", 11.0, 107.0, "Customer", "0987654321", "Package", 1.5, 5.0, 15000.0, "picked_up",
    )
    require.NoError(t, err)
    orderID := getLastInsertRowID(t, tc.db)

    w := tc.do(http.MethodPost, fmt.Sprintf("/api/courier/orders/%d/deliver", orderID), nil, map[string]string{"X-User-ID": "courier_user"})
    assert.Equal(t, http.StatusOK, w.Code)
    var resp map[string]interface{}
    json.NewDecoder(w.Body).Decode(&resp)
    assert.Equal(t, "order delivered", resp["message"])

    var status string
    err = tc.db.QueryRow("SELECT status FROM courier_orders WHERE id = ?", orderID).Scan(&status)
    require.NoError(t, err)
    assert.Equal(t, "delivered", status)
}

func TestDeliverOrder_OrderNotFound(t *testing.T) {
    gin.SetMode(gin.TestMode)
    tc := newTestContext(t)
    defer tc.close()

    _, err := tc.db.Exec(`
        INSERT INTO couriers (user_id, name, phone, email, vehicle_type, vehicle_number, status, latitude, longitude, rating, total_deliveries, available_balance)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
        "courier_user", "Test Courier", "0123456789", "courier@example.com", "bike", "BIKE123", "available", 10.0, 106.0, 5.0, 0, 0.0,
    )
    require.NoError(t, err)

    w := tc.do(http.MethodPost, "/api/courier/orders/999/deliver", nil, map[string]string{"X-User-ID": "courier_user"})
    assert.Equal(t, http.StatusNotFound, w.Code)
}

func TestDeliverOrder_Unauthorized(t *testing.T) {
    gin.SetMode(gin.TestMode)
    tc := newTestContext(t)
    defer tc.close()

    _, err := tc.db.Exec(`
        INSERT INTO couriers (user_id, name, phone, email, vehicle_type, vehicle_number, status, latitude, longitude, rating, total_deliveries, available_balance)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
        "courier_user", "Test Courier", "0123456789", "courier@example.com", "bike", "BIKE123", "available", 10.0, 106.0, 5.0, 0, 0.0,
    )
    require.NoError(t, err)
    courierID := getLastInsertRowID(t, tc.db)

    _, err = tc.db.Exec(`
        INSERT INTO courier_orders (order_id, courier_id, order_type, pickup_address, pickup_lat, pickup_lng, delivery_address, delivery_lat, delivery_lng, customer_name, customer_phone, package_desc, package_weight, distance, fee, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
        "order123", courierID, "pickup", "123 Pickup St", 10.0, 106.0, "456 Delivery Ave", 11.0, 107.0, "Customer", "0987654321", "Package", 1.5, 5.0, 15000.0, "picked_up",
    )
    require.NoError(t, err)

    w := tc.do(http.MethodPost, "/api/courier/orders/1/deliver", nil, map[string]string{"X-User-ID": "other_user"})
    assert.Equal(t, http.StatusForbidden, w.Code)
}

func TestDeliverOrder_InvalidOrderID(t *testing.T) {
    gin.SetMode(gin.TestMode)
    tc := newTestContext(t)
    defer tc.close()

    w := tc.do(http.MethodPost, "/api/courier/orders/abc/deliver", nil, map[string]string{"X-User-ID": "any"})
    assert.Equal(t, http.StatusBadRequest, w.Code)
}

// ==================== Tests: GetActiveOrders ====================

func TestGetActiveOrders_Success(t *testing.T) {
    gin.SetMode(gin.TestMode)
    tc := newTestContext(t)
    defer tc.close()

    _, err := tc.db.Exec(`
        INSERT INTO couriers (user_id, name, phone, email, vehicle_type, vehicle_number, status, latitude, longitude, rating, total_deliveries, available_balance)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
        "courier_user", "Test Courier", "0123456789", "courier@example.com", "bike", "BIKE123", "available", 10.0, 106.0, 5.0, 0, 0.0,
    )
    require.NoError(t, err)
    courierID := getLastInsertRowID(t, tc.db)

    // Insert 3 active orders (status='assigned')
    for i := 0; i < 3; i++ {
        _, err = tc.db.Exec(`
            INSERT INTO courier_orders (order_id, courier_id, order_type, pickup_address, pickup_lat, pickup_lng, delivery_address, delivery_lat, delivery_lng, customer_name, customer_phone, package_desc, package_weight, distance, fee, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
            fmt.Sprintf("order%d", i), courierID, "standard", "123 Pickup St", 10.0, 106.0, "456 Delivery Ave", 11.0, 107.0, "Customer", "0987654321", "Package", 1.5, 5.0, 15000.0, "assigned",
        )
        require.NoError(t, err)
    }

    w := tc.do(http.MethodGet, fmt.Sprintf("/api/courier/orders/courier/%d/active", courierID), nil, map[string]string{"X-User-ID": "courier_user"})
    assert.Equal(t, http.StatusOK, w.Code)
    var resp map[string]interface{}
    json.NewDecoder(w.Body).Decode(&resp)
    orders, ok := resp["orders"].([]interface{})
    require.True(t, ok)
    assert.Len(t, orders, 3)
}

func TestGetActiveOrders_CourierNotFound(t *testing.T) {
    gin.SetMode(gin.TestMode)
    tc := newTestContext(t)
    defer tc.close()

    w := tc.do(http.MethodGet, "/api/courier/orders/courier/999/active", nil, map[string]string{"X-User-ID": "any"})
    assert.Equal(t, http.StatusNotFound, w.Code)
}

func TestGetActiveOrders_Unauthorized(t *testing.T) {
    gin.SetMode(gin.TestMode)
    tc := newTestContext(t)
    defer tc.close()

    _, err := tc.db.Exec(`
        INSERT INTO couriers (user_id, name, phone, email, vehicle_type, vehicle_number, status, latitude, longitude, rating, total_deliveries, available_balance)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
        "courier_user", "Test Courier", "0123456789", "courier@example.com", "bike", "BIKE123", "available", 10.0, 106.0, 5.0, 0, 0.0,
    )
    require.NoError(t, err)
    courierID := getLastInsertRowID(t, tc.db)

    w := tc.do(http.MethodGet, fmt.Sprintf("/api/courier/orders/courier/%d/active", courierID), nil, map[string]string{"X-User-ID": "other_user"})
    assert.Equal(t, http.StatusForbidden, w.Code)
}
