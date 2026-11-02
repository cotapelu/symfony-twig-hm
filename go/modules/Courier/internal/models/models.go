package courier

import (
	"database/sql"
	"math"
	"time"

	_ "github.com/lib/pq"
)

type Courier struct {
	ID               int64     `json:"id"`
	UserID           string    `json:"user_id"`
	Name             string    `json:"name"`
	Phone            string    `json:"phone"`
	Email            string    `json:"email"`
	VehicleType      string    `json:"vehicle_type"`
	VehicleNumber    string    `json:"vehicle_number"`
	Status           string    `json:"status"`
	Latitude         float64   `json:"latitude"`
	Longitude        float64   `json:"longitude"`
	Rating           float64   `json:"rating"`
	TotalDeliveries  int       `json:"total_deliveries"`
	AvailableBalance float64   `json:"available_balance"`
	CreatedAt        time.Time `json:"created_at"`
}

type CourierOrder struct {
	ID              int64      `json:"id"`
	OrderID         string     `json:"order_id"`
	CourierID       int64      `json:"courier_id"`
	OrderType       string     `json:"order_type"`
	PickupAddress   string     `json:"pickup_address"`
	PickupLat       float64    `json:"pickup_lat"`
	PickupLng       float64    `json:"pickup_lng"`
	DeliveryAddress string     `json:"delivery_address"`
	DeliveryLat     float64    `json:"delivery_lat"`
	DeliveryLng     float64    `json:"delivery_lng"`
	CustomerName    string     `json:"customer_name"`
	CustomerPhone   string     `json:"customer_phone"`
	PackageDesc     string     `json:"package_desc"`
	PackageWeight   float64    `json:"package_weight"`
	Distance        float64    `json:"distance"`
	Fee             float64    `json:"fee"`
	Status          string     `json:"status"`
	AssignedAt      *time.Time `json:"assigned_at"`
	PickedUpAt      *time.Time `json:"picked_up_at"`
	DeliveredAt     *time.Time `json:"delivered_at"`
	CreatedAt       time.Time  `json:"created_at"`
}

type CourierEarning struct {
	ID        int64     `json:"id"`
	CourierID int64     `json:"courier_id"`
	OrderID   string    `json:"order_id"`
	Amount    float64   `json:"amount"`
	Type      string    `json:"type"`
	Withdrawn bool      `json:"withdrawn"`
	CreatedAt time.Time `json:"created_at"`
}

type CourierRepository struct {
	db *sql.DB
}

func NewCourierRepository(db *sql.DB) *CourierRepository {
	return &CourierRepository{db: db}
}

func (r *CourierRepository) Create(c *Courier) error {
	query := `INSERT INTO couriers (user_id, name, phone, email, vehicle_type, vehicle_number, status, latitude, longitude, rating, total_deliveries, available_balance)
	          VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12) RETURNING id, created_at`
	return r.db.QueryRow(query, c.UserID, c.Name, c.Phone, c.Email, c.VehicleType, c.VehicleNumber, c.Status, c.Latitude, c.Longitude, c.Rating, c.TotalDeliveries, c.AvailableBalance).Scan(&c.ID, &c.CreatedAt)
}

func (r *CourierRepository) GetByID(id int64) (*Courier, error) {
	c := &Courier{}
	query := `SELECT id, user_id, name, phone, email, vehicle_type, vehicle_number, status, latitude, longitude, rating, total_deliveries, available_balance, created_at
	          FROM couriers WHERE id = $1`
	err := r.db.QueryRow(query, id).Scan(&c.ID, &c.UserID, &c.Name, &c.Phone, &c.Email, &c.VehicleType, &c.VehicleNumber, &c.Status, &c.Latitude, &c.Longitude, &c.Rating, &c.TotalDeliveries, &c.AvailableBalance, &c.CreatedAt)
	if err != nil {
		return nil, err
	}
	return c, nil
}

func (r *CourierRepository) GetAvailable() ([]*Courier, error) {
	rows, err := r.db.Query(`SELECT id, user_id, name, phone, email, vehicle_type, vehicle_number, status, latitude, longitude, rating, total_deliveries, available_balance, created_at
	                        FROM couriers WHERE status = 'available' ORDER BY rating DESC`)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var couriers []*Courier
	for rows.Next() {
		c := &Courier{}
		if err := rows.Scan(&c.ID, &c.UserID, &c.Name, &c.Phone, &c.Email, &c.VehicleType, &c.VehicleNumber, &c.Status, &c.Latitude, &c.Longitude, &c.Rating, &c.TotalDeliveries, &c.AvailableBalance, &c.CreatedAt); err != nil {
			return nil, err
		}
		couriers = append(couriers, c)
	}
	return couriers, nil
}

func (r *CourierRepository) UpdateLocation(id int64, lat, lng float64) error {
	query := `UPDATE couriers SET latitude = $1, longitude = $2 WHERE id = $3`
	_, err := r.db.Exec(query, lat, lng, id)
	return err
}

func (r *CourierRepository) UpdateStatus(id int64, status string) error {
	query := `UPDATE couriers SET status = $1 WHERE id = $2`
	_, err := r.db.Exec(query, status, id)
	return err
}

func (r *CourierRepository) IncrementDeliveries(id int64) error {
	query := `UPDATE couriers SET total_deliveries = total_deliveries + 1 WHERE id = $1`
	_, err := r.db.Exec(query, id)
	return err
}

type OrderRepository struct {
	db *sql.DB
}

func NewOrderRepository(db *sql.DB) *OrderRepository {
	return &OrderRepository{db: db}
}

func (r *OrderRepository) Create(o *CourierOrder) error {
	query := `INSERT INTO courier_orders (order_id, courier_id, order_type, pickup_address, pickup_lat, pickup_lng, delivery_address, delivery_lat, delivery_lng, customer_name, customer_phone, package_desc, package_weight, distance, fee, status)
	          VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14, $15, $16) RETURNING id, created_at`
	return r.db.QueryRow(query, o.OrderID, o.CourierID, o.OrderType, o.PickupAddress, o.PickupLat, o.PickupLng, o.DeliveryAddress, o.DeliveryLat, o.DeliveryLng, o.CustomerName, o.CustomerPhone, o.PackageDesc, o.PackageWeight, o.Distance, o.Fee, o.Status).Scan(&o.ID, &o.CreatedAt)
}

func (r *OrderRepository) GetByID(id int64) (*CourierOrder, error) {
	o := &CourierOrder{}
	var cid sql.NullInt64
	query := `SELECT id, order_id, courier_id, order_type, pickup_address, pickup_lat, pickup_lng, delivery_address, delivery_lat, delivery_lng, customer_name, customer_phone, package_desc, package_weight, distance, fee, status, assigned_at, picked_up_at, delivered_at, created_at
	          FROM courier_orders WHERE id = $1`
	err := r.db.QueryRow(query, id).Scan(&o.ID, &o.OrderID, &cid, &o.OrderType, &o.PickupAddress, &o.PickupLat, &o.PickupLng, &o.DeliveryAddress, &o.DeliveryLat, &o.DeliveryLng, &o.CustomerName, &o.CustomerPhone, &o.PackageDesc, &o.PackageWeight, &o.Distance, &o.Fee, &o.Status, &o.AssignedAt, &o.PickedUpAt, &o.DeliveredAt, &o.CreatedAt)
	if err != nil {
		return nil, err
	}
	if cid.Valid {
		o.CourierID = cid.Int64
	} else {
		o.CourierID = 0
	}
	return o, nil
}

func (r *OrderRepository) GetByCourier(courierID int64) ([]*CourierOrder, error) {
	rows, err := r.db.Query(`SELECT id, order_id, courier_id, order_type, pickup_address, pickup_lat, pickup_lng, delivery_address, delivery_lat, delivery_lng, customer_name, customer_phone, package_desc, package_weight, distance, fee, status, assigned_at, picked_up_at, delivered_at, created_at
	                        FROM courier_orders WHERE courier_id = $1 ORDER BY created_at DESC`, courierID)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var orders []*CourierOrder
	for rows.Next() {
		o := &CourierOrder{}
		if err := rows.Scan(&o.ID, &o.OrderID, &o.CourierID, &o.OrderType, &o.PickupAddress, &o.PickupLat, &o.PickupLng, &o.DeliveryAddress, &o.DeliveryLat, &o.DeliveryLng, &o.CustomerName, &o.CustomerPhone, &o.PackageDesc, &o.PackageWeight, &o.Distance, &o.Fee, &o.Status, &o.AssignedAt, &o.PickedUpAt, &o.DeliveredAt, &o.CreatedAt); err != nil {
			return nil, err
		}
		orders = append(orders, o)
	}
	return orders, nil
}

func (r *OrderRepository) GetByCourierWithPagination(courierID int64, limit, offset int) ([]*CourierOrder, error) {
	rows, err := r.db.Query(`SELECT id, order_id, courier_id, order_type, pickup_address, pickup_lat, pickup_lng, delivery_address, delivery_lat, delivery_lng, customer_name, customer_phone, package_desc, package_weight, distance, fee, status, assigned_at, picked_up_at, delivered_at, created_at
	                        FROM courier_orders WHERE courier_id = $1 ORDER BY created_at DESC LIMIT $2 OFFSET $3`, courierID, limit, offset)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var orders []*CourierOrder
	for rows.Next() {
		o := &CourierOrder{}
		if err := rows.Scan(&o.ID, &o.OrderID, &o.CourierID, &o.OrderType, &o.PickupAddress, &o.PickupLat, &o.PickupLng, &o.DeliveryAddress, &o.DeliveryLat, &o.DeliveryLng, &o.CustomerName, &o.CustomerPhone, &o.PackageDesc, &o.PackageWeight, &o.Distance, &o.Fee, &o.Status, &o.AssignedAt, &o.PickedUpAt, &o.DeliveredAt, &o.CreatedAt); err != nil {
			return nil, err
		}
		orders = append(orders, o)
	}
	return orders, nil
}

func (r *OrderRepository) CountByCourier(courierID int64) (int, error) {
	var count int
	err := r.db.QueryRow(`SELECT COUNT(*) FROM courier_orders WHERE courier_id = $1`, courierID).Scan(&count)
	return count, err
}

func (r *OrderRepository) GetActiveByCourier(courierID int64) ([]*CourierOrder, error) {
	rows, err := r.db.Query(`SELECT id, order_id, courier_id, order_type, pickup_address, pickup_lat, pickup_lng, delivery_address, delivery_lat, delivery_lng, customer_name, customer_phone, package_desc, package_weight, distance, fee, status, assigned_at, picked_up_at, delivered_at, created_at
	                        FROM courier_orders WHERE courier_id = $1 AND status IN ('assigned', 'picked_up') ORDER BY created_at ASC`, courierID)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var orders []*CourierOrder
	for rows.Next() {
		o := &CourierOrder{}
		if err := rows.Scan(&o.ID, &o.OrderID, &o.CourierID, &o.OrderType, &o.PickupAddress, &o.PickupLat, &o.PickupLng, &o.DeliveryAddress, &o.DeliveryLat, &o.DeliveryLng, &o.CustomerName, &o.CustomerPhone, &o.PackageDesc, &o.PackageWeight, &o.Distance, &o.Fee, &o.Status, &o.AssignedAt, &o.PickedUpAt, &o.DeliveredAt, &o.CreatedAt); err != nil {
			return nil, err
		}
		orders = append(orders, o)
	}
	return orders, nil
}

func (r *OrderRepository) UpdateStatus(id int64, status string) error {
	query := `UPDATE courier_orders SET status = $1`
	switch status {
	case "picked_up":
		query += ", picked_up_at = CURRENT_TIMESTAMP"
	case "delivered":
		query += ", delivered_at = CURRENT_TIMESTAMP"
	}
	query += " WHERE id = $2"
	_, err := r.db.Exec(query, status, id)
	return err
}

func (r *OrderRepository) UpdateCourierID(orderID, courierID int64) error {
	_, err := r.db.Exec(`UPDATE courier_orders SET courier_id = $1 WHERE id = $2`, courierID, orderID)
	return err
}

type CourierService struct {
	courierRepo *CourierRepository
	orderRepo   *OrderRepository
}

func NewCourierService(db *sql.DB) *CourierService {
	return &CourierService{
		courierRepo: NewCourierRepository(db),
		orderRepo:   NewOrderRepository(db),
	}
}

func (s *CourierService) RegisterCourier(userID, name, phone, email, vehicleType, vehicleNumber string) (*Courier, error) {
	courier := &Courier{
		UserID:           userID,
		Name:             name,
		Phone:            phone,
		Email:            email,
		VehicleType:      vehicleType,
		VehicleNumber:    vehicleNumber,
		Status:           "available",
		Rating:           5.0,
		TotalDeliveries:  0,
		AvailableBalance: 0,
	}
	if err := s.courierRepo.Create(courier); err != nil {
		return nil, err
	}
	return courier, nil
}

func (s *CourierService) GetCourier(id int64) (*Courier, error) {
	return s.courierRepo.GetByID(id)
}

func (s *CourierService) GetAvailableCouriers() ([]*Courier, error) {
	return s.courierRepo.GetAvailable()
}

func (s *CourierService) UpdateLocation(courierID int64, lat, lng float64) error {
	return s.courierRepo.UpdateLocation(courierID, lat, lng)
}

func (s *CourierService) UpdateStatus(courierID int64, status string) error {
	return s.courierRepo.UpdateStatus(courierID, status)
}

func (s *CourierService) CreateOrder(orderID string, pickupAddr string, pickupLat, pickupLng float64, deliveryAddr string, deliveryLat, deliveryLng float64, custName, custPhone, pkgDesc string, weight float64) (*CourierOrder, float64) {
	distance := calculateDistance(pickupLat, pickupLng, deliveryLat, deliveryLng)
	fee := calculateFee(distance, weight, "standard")

	order := &CourierOrder{
		OrderID:         orderID,
		OrderType:       "standard",
		PickupAddress:   pickupAddr,
		PickupLat:       pickupLat,
		PickupLng:       pickupLng,
		DeliveryAddress: deliveryAddr,
		DeliveryLat:     deliveryLat,
		DeliveryLng:     deliveryLng,
		CustomerName:    custName,
		CustomerPhone:   custPhone,
		PackageDesc:     pkgDesc,
		PackageWeight:   weight,
		Distance:        distance,
		Fee:             fee,
		Status:          "pending",
	}
	s.orderRepo.Create(order)
	return order, fee
}

func (s *CourierService) AssignOrder(orderID int64, courierID int64) (*CourierOrder, error) {
	order, err := s.orderRepo.GetByID(orderID)
	if err != nil {
		return nil, err
	}
	order.CourierID = courierID
	order.Status = "assigned"
	now := time.Now()
	order.AssignedAt = &now
	if err := s.orderRepo.UpdateCourierID(orderID, courierID); err != nil {
		return nil, err
	}
	s.orderRepo.UpdateStatus(orderID, "assigned")
	s.courierRepo.UpdateStatus(courierID, "busy")
	return order, nil
}

func (s *CourierService) PickupOrder(orderID int64) error {
	return s.orderRepo.UpdateStatus(orderID, "picked_up")
}

func (s *CourierService) DeliverOrder(orderID int64) error {
	order, _ := s.orderRepo.GetByID(orderID)
	s.orderRepo.UpdateStatus(orderID, "delivered")
	s.courierRepo.IncrementDeliveries(order.CourierID)
	return nil
}

func (s *CourierService) GetCourierOrders(courierID int64) ([]*CourierOrder, error) {
	return s.orderRepo.GetByCourier(courierID)
}

// GetCourierOrdersWithPagination returns paginated orders
func (s *CourierService) GetCourierOrdersWithPagination(courierID int64, page, limit int) (map[string]interface{}, error) {
	if page < 1 {
		page = 1
	}
	if limit < 1 || limit > 100 {
		limit = 20
	}
	offset := (page - 1) * limit

	orders, err := s.orderRepo.GetByCourierWithPagination(courierID, limit, offset)
	if err != nil {
		return nil, err
	}

	total, err := s.orderRepo.CountByCourier(courierID)
	if err != nil {
		return nil, err
	}

	totalPages := (total + limit - 1) / limit

	return map[string]interface{}{
		"data": orders,
		"pagination": map[string]interface{}{
			"current_page": page,
			"per_page":     limit,
			"total_items":  total,
			"total_pages":  totalPages,
		},
	}, nil
}

func (s *CourierService) GetActiveOrders(courierID int64) ([]*CourierOrder, error) {
	return s.orderRepo.GetActiveByCourier(courierID)
}

func (s *CourierService) GetOrderByID(orderID int64) (*CourierOrder, error) {
	return s.orderRepo.GetByID(orderID)
}

func (s *CourierService) CompleteDelivery(courierID int64, orderID int64) error {
	order, _ := s.orderRepo.GetByID(orderID)
	_ = order.Fee * 0.85
	s.orderRepo.UpdateStatus(orderID, "delivered")
	s.courierRepo.IncrementDeliveries(courierID)
	s.courierRepo.UpdateStatus(courierID, "available")
	return nil
}

func calculateDistance(lat1, lng1, lat2, lng2 float64) float64 {
	const earthRadius = 6371.0

	lat1Rad := lat1 * math.Pi / 180
	lat2Rad := lat2 * math.Pi / 180
	deltaLat := (lat2 - lat1) * math.Pi / 180
	deltaLng := (lng2 - lng1) * math.Pi / 180

	a := math.Sin(deltaLat/2)*math.Sin(deltaLat/2) +
		math.Cos(lat1Rad)*math.Cos(lat2Rad)*
			math.Sin(deltaLng/2)*math.Sin(deltaLng/2)
	c := 2 * math.Atan2(math.Sqrt(a), math.Sqrt(1-a))

	return earthRadius * c
}

func calculateFee(distance, weight float64, serviceType string) float64 {
	base := 15000.0
	distFee := distance * 3000
	weightFee := weight * 5000
	if serviceType == "express" {
		base *= 1.5
	}
	return base + distFee + weightFee
}
