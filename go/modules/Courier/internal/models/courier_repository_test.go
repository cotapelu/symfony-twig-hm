package courier

import (
	"database/sql"
	"testing"
	"time"

	_ "github.com/mattn/go-sqlite3"
	"github.com/stretchr/testify/assert"

	"community.local/testing/helper"
)

func setupCourierDB(t *testing.T) *sql.DB {
	t.Helper()
	db := helper.SetupTestDB(t)
	_, _ = db.Exec("PRAGMA foreign_keys = ON")

	_, err := db.Exec(`
		CREATE TABLE couriers (
			id INTEGER PRIMARY KEY AUTOINCREMENT,
			user_id TEXT NOT NULL,
			name TEXT NOT NULL,
			phone TEXT NOT NULL,
			email TEXT NOT NULL,
			vehicle_type TEXT NOT NULL,
			vehicle_number TEXT NOT NULL,
			status TEXT NOT NULL DEFAULT 'available',
			latitude REAL NOT NULL,
			longitude REAL NOT NULL,
			rating REAL NOT NULL DEFAULT 0,
			total_deliveries INTEGER NOT NULL DEFAULT 0,
			available_balance REAL NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
		);
	`)
	if err != nil {
		t.Fatalf("create couriers failed: %v", err)
	}
	_, err = db.Exec(`
		CREATE TABLE courier_orders (
			id INTEGER PRIMARY KEY AUTOINCREMENT,
			order_id TEXT UNIQUE NOT NULL,
			courier_id INTEGER NOT NULL REFERENCES couriers(id),
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
			distance REAL NOT NULL,
			fee REAL NOT NULL,
			status TEXT NOT NULL,
			assigned_at DATETIME,
			picked_up_at DATETIME,
			delivered_at DATETIME,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
		);
	`)
	if err != nil {
		t.Fatalf("create orders failed: %v", err)
	}
	_, err = db.Exec(`
		CREATE TABLE courier_earnings (
			id INTEGER PRIMARY KEY AUTOINCREMENT,
			courier_id INTEGER NOT NULL REFERENCES couriers(id),
			order_id TEXT NOT NULL,
			amount REAL NOT NULL,
			type TEXT NOT NULL,
			withdrawn BOOLEAN NOT NULL DEFAULT FALSE,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
		);
	`)
	if err != nil {
		t.Fatalf("create earnings failed: %v", err)
	}
	return db
}

// Helper to create a courier and return its ID
func createTestCourier(t *testing.T, db *sql.DB) int64 {
	repo := NewCourierRepository(db)
	c := &Courier{
		UserID:           "c_" + time.Now().Format("20060102150405"),
		Name:             "Test Courier",
		Status:           "available",
		Latitude:         10,
		Longitude:        106,
	}
	err := repo.Create(c)
	assert.NoError(t, err)
	return c.ID
}

// CourierRepository tests

func TestCourierRepository_CreateAndGet(t *testing.T) {
	db := setupCourierDB(t)
	defer db.Close()
	repo := NewCourierRepository(db)

	c := &Courier{
		UserID:           "user123",
		Name:             "John Doe",
		Phone:            "0123456789",
		Email:            "john@example.com",
		VehicleType:      "motorcycle",
		VehicleNumber:    "123-456",
		Status:           "available",
		Latitude:         10.0,
		Longitude:        106.0,
		Rating:           4.5,
		TotalDeliveries:  0,
		AvailableBalance: 0,
		CreatedAt:        time.Now(),
	}
	err := repo.Create(c)
	assert.NoError(t, err)
	assert.NotZero(t, c.ID)

	fetched, err := repo.GetByID(c.ID)
	assert.NoError(t, err)
	assert.Equal(t, c.ID, fetched.ID)
	assert.Equal(t, "user123", fetched.UserID)
}

func TestCourierRepository_GetAvailable(t *testing.T) {
	db := setupCourierDB(t)
	defer db.Close()
	repo := NewCourierRepository(db)

	c1 := &Courier{UserID: "u1", Name: "C1", Status: "available", Rating: 4.0, Latitude: 10, Longitude: 106}
	c2 := &Courier{UserID: "u2", Name: "C2", Status: "available", Rating: 4.5, Latitude: 10.1, Longitude: 106.1}
	c3 := &Courier{UserID: "u3", Name: "C3", Status: "offline", Rating: 5.0, Latitude: 10.2, Longitude: 106.2}
	for _, c := range []*Courier{c1, c2, c3} {
		assert.NoError(t, repo.Create(c))
	}

	available, err := repo.GetAvailable()
	assert.NoError(t, err)
	assert.Len(t, available, 2)
	assert.Equal(t, "C2", available[0].Name)
	assert.Equal(t, "C1", available[1].Name)
}

func TestCourierRepository_UpdateLocation(t *testing.T) {
	db := setupCourierDB(t)
	defer db.Close()
	repo := NewCourierRepository(db)

	c := &Courier{UserID: "u1", Name: "C1", Status: "available", Latitude: 10, Longitude: 106}
	assert.NoError(t, repo.Create(c))

	err := repo.UpdateLocation(c.ID, 10.5, 106.5)
	assert.NoError(t, err)

	updated, _ := repo.GetByID(c.ID)
	assert.Equal(t, 10.5, updated.Latitude)
	assert.Equal(t, 106.5, updated.Longitude)
}

func TestCourierRepository_UpdateStatus(t *testing.T) {
	db := setupCourierDB(t)
	defer db.Close()
	repo := NewCourierRepository(db)

	c := &Courier{UserID: "u1", Name: "C1", Status: "available", Latitude: 10, Longitude: 106}
	assert.NoError(t, repo.Create(c))

	err := repo.UpdateStatus(c.ID, "busy")
	assert.NoError(t, err)

	updated, _ := repo.GetByID(c.ID)
	assert.Equal(t, "busy", updated.Status)
}

func TestCourierRepository_IncrementDeliveries(t *testing.T) {
	db := setupCourierDB(t)
	defer db.Close()
	repo := NewCourierRepository(db)

	c := &Courier{UserID: "u1", Name: "C1", Status: "available", Latitude: 10, Longitude: 106, TotalDeliveries: 5}
	assert.NoError(t, repo.Create(c))

	err := repo.IncrementDeliveries(c.ID)
	assert.NoError(t, err)

	updated, _ := repo.GetByID(c.ID)
	assert.Equal(t, 6, updated.TotalDeliveries)
}

// OrderRepository tests

func TestOrderRepository_CreateAndGet(t *testing.T) {
	db := setupCourierDB(t)
	defer db.Close()
	courierID := createTestCourier(t, db)

	orderRepo := NewOrderRepository(db)
	o := &CourierOrder{
		OrderID:         "ORD-001",
		CourierID:       courierID,
		OrderType:       "delivery",
		PickupAddress:   "123 Main",
		PickupLat:       10.0,
		PickupLng:       106.0,
		DeliveryAddress: "456 Side",
		DeliveryLat:     10.1,
		DeliveryLng:     106.1,
		CustomerName:    "Alice",
		CustomerPhone:   "0987654321",
		PackageDesc:     "Small box",
		PackageWeight:   1.5,
		Distance:        5.0,
		Fee:             20000,
		Status:          "assigned",
	}
	err := orderRepo.Create(o)
	assert.NoError(t, err)
	assert.NotZero(t, o.ID)
	assert.NotNil(t, o.CreatedAt)

	fetched, err := orderRepo.GetByID(o.ID)
	assert.NoError(t, err)
	assert.Equal(t, o.OrderID, fetched.OrderID)
	assert.Equal(t, "delivery", fetched.OrderType)
}

func TestOrderRepository_GetByCourier(t *testing.T) {
	db := setupCourierDB(t)
	defer db.Close()
	courierID := createTestCourier(t, db)

	orderRepo := NewOrderRepository(db)
	o1 := &CourierOrder{OrderID: "ORD-001", CourierID: courierID, OrderType: "delivery", PickupAddress: "A", DeliveryAddress: "B", CustomerName: "X", CustomerPhone: "123", PackageWeight: 1, Distance: 5, Fee: 100, Status: "assigned"}
	o2 := &CourierOrder{OrderID: "ORD-002", CourierID: courierID, OrderType: "pickup", PickupAddress: "C", DeliveryAddress: "D", CustomerName: "Y", CustomerPhone: "456", PackageWeight: 2, Distance: 10, Fee: 200, Status: "picked_up"}
	for _, o := range []*CourierOrder{o1, o2} {
		assert.NoError(t, orderRepo.Create(o))
	}

	orders, err := orderRepo.GetByCourier(courierID)
	assert.NoError(t, err)
	assert.Len(t, orders, 2)
}

func TestOrderRepository_CountByCourier(t *testing.T) {
	db := setupCourierDB(t)
	defer db.Close()
	courierID := createTestCourier(t, db)

	orderRepo := NewOrderRepository(db)
	o := &CourierOrder{OrderID: "ORD-001", CourierID: courierID, OrderType: "delivery", PickupAddress: "A", DeliveryAddress: "B", CustomerName: "X", CustomerPhone: "123", PackageWeight: 1, Distance: 5, Fee: 100, Status: "assigned"}
	assert.NoError(t, orderRepo.Create(o))

	count, err := orderRepo.CountByCourier(courierID)
	assert.NoError(t, err)
	assert.Equal(t, 1, count)
}

func TestOrderRepository_UpdateStatus(t *testing.T) {
	db := setupCourierDB(t)
	defer db.Close()
	courierID := createTestCourier(t, db)

	orderRepo := NewOrderRepository(db)
	o := &CourierOrder{OrderID: "ORD-001", CourierID: courierID, OrderType: "delivery", PickupAddress: "A", DeliveryAddress: "B", CustomerName: "X", CustomerPhone: "123", PackageWeight: 1, Distance: 5, Fee: 100, Status: "assigned"}
	assert.NoError(t, orderRepo.Create(o))

	err := orderRepo.UpdateStatus(o.ID, "picked_up")
	assert.NoError(t, err)

	updated, _ := orderRepo.GetByID(o.ID)
	assert.Equal(t, "picked_up", updated.Status)
	assert.NotNil(t, updated.PickedUpAt)

	err = orderRepo.UpdateStatus(o.ID, "delivered")
	assert.NoError(t, err)
	updated2, _ := orderRepo.GetByID(o.ID)
	assert.Equal(t, "delivered", updated2.Status)
	assert.NotNil(t, updated2.DeliveredAt)
}

func TestOrderRepository_GetActiveByCourier(t *testing.T) {
	db := setupCourierDB(t)
	defer db.Close()
	courierID := createTestCourier(t, db)

	orderRepo := NewOrderRepository(db)
	o1 := &CourierOrder{OrderID: "ORD-1", CourierID: courierID, OrderType: "delivery", PickupAddress: "A", DeliveryAddress: "B", CustomerName: "X", CustomerPhone: "123", PackageWeight: 1, Distance: 5, Fee: 100, Status: "assigned"}
	o2 := &CourierOrder{OrderID: "ORD-2", CourierID: courierID, OrderType: "delivery", PickupAddress: "C", DeliveryAddress: "D", CustomerName: "Y", CustomerPhone: "456", PackageWeight: 2, Distance: 10, Fee: 200, Status: "picked_up"}
	o3 := &CourierOrder{OrderID: "ORD-3", CourierID: courierID, OrderType: "delivery", PickupAddress: "E", DeliveryAddress: "F", CustomerName: "Z", CustomerPhone: "789", PackageWeight: 3, Distance: 15, Fee: 300, Status: "delivered"}
	for _, o := range []*CourierOrder{o1, o2, o3} {
		assert.NoError(t, orderRepo.Create(o))
	}

	active, err := orderRepo.GetActiveByCourier(courierID)
	assert.NoError(t, err)
	assert.Len(t, active, 2)
}

func TestOrderRepository_GetByCourierWithPagination(t *testing.T) {
	db := setupCourierDB(t)
	defer db.Close()
	courierID := createTestCourier(t, db)

	orderRepo := NewOrderRepository(db)
	for i := 0; i < 5; i++ {
		o := &CourierOrder{
			OrderID:         "ORD-" + string(rune('A'+i)),
			CourierID:       courierID,
			OrderType:       "delivery",
			PickupAddress:   "A",
			DeliveryAddress: "B",
			CustomerName:    "C",
			CustomerPhone:   "123",
			PackageWeight:   1,
			Distance:        5,
			Fee:             100,
			Status:          "assigned",
		}
		assert.NoError(t, orderRepo.Create(o))
	}

	page1, err := orderRepo.GetByCourierWithPagination(courierID, 2, 0)
	assert.NoError(t, err)
	assert.Len(t, page1, 2)

	page2, err := orderRepo.GetByCourierWithPagination(courierID, 2, 2)
	assert.NoError(t, err)
	assert.Len(t, page2, 2)
}

func TestOrderRepository_UpdateCourierID(t *testing.T) {
	db := setupCourierDB(t)
	defer db.Close()
	courierID1 := createTestCourier(t, db)
	courierID2 := createTestCourier(t, db)

	orderRepo := NewOrderRepository(db)
	o := &CourierOrder{OrderID: "ORD-001", CourierID: courierID1, OrderType: "delivery", PickupAddress: "A", DeliveryAddress: "B", CustomerName: "X", CustomerPhone: "123", PackageWeight: 1, Distance: 5, Fee: 100, Status: "assigned"}
	assert.NoError(t, orderRepo.Create(o))

	err := orderRepo.UpdateCourierID(o.ID, courierID2)
	assert.NoError(t, err)

	updated, _ := orderRepo.GetByID(o.ID)
	assert.Equal(t, courierID2, updated.CourierID)
}
