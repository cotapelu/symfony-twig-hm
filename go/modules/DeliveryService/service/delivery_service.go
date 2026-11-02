package service

import (
	"crypto/rand"
	"encoding/hex"
	"encoding/json"
	"fmt"
	"log"
	"math"
	"os"
	"path/filepath"
	"sync"
	"time"

	"community.local/modules/DeliveryService/entity"
)

type DeliveryService struct {
	mu              sync.RWMutex
	deliveryPersons map[string]*entity.DeliveryPerson
	deliveryOrders  map[string]*entity.DeliveryOrder
	etsFile         string
}

func NewDeliveryService(etsFile string) *DeliveryService {
	s := &DeliveryService{
		deliveryPersons: make(map[string]*entity.DeliveryPerson),
		deliveryOrders:  make(map[string]*entity.DeliveryOrder),
		etsFile:         etsFile,
	}
	s.ensureDataDirectory()
	s.restoreFromETS()
	return s
}

func (s *DeliveryService) ensureDataDirectory() {
	dir := filepath.Dir(s.etsFile)
	if dir != "" && dir != "." {
		os.MkdirAll(dir, 0755)
	}
}

func (s *DeliveryService) restoreFromETS() {
	if !exists(s.etsFile) {
		return
	}
	data, err := os.ReadFile(s.etsFile)
	if err != nil {
		return
	}
	var saved struct {
		DeliveryPersons map[string]entity.DeliveryPerson `json:"delivery_persons"`
		DeliveryOrders  map[string]entity.DeliveryOrder  `json:"delivery_orders"`
	}
	if err := json.Unmarshal(data, &saved); err != nil {
		return
	}
	s.deliveryPersons = make(map[string]*entity.DeliveryPerson)
	s.deliveryOrders = make(map[string]*entity.DeliveryOrder)
	for k, v := range saved.DeliveryPersons {
		vp := v
		s.deliveryPersons[k] = &vp
	}
	for k, v := range saved.DeliveryOrders {
		vp := v
		s.deliveryOrders[k] = &vp
	}
}

func (s *DeliveryService) persistToETS() {
	data, _ := json.MarshalIndent(map[string]interface{}{
		"delivery_persons": s.deliveryPersons,
		"delivery_orders":  s.deliveryOrders,
	}, "", "  ")
	os.WriteFile(s.etsFile, data, 0644)
}

func (s *DeliveryService) SetOnline(userID string, lat, lng float64) (*entity.DeliveryPerson, error) {
	s.mu.Lock()
	defer s.mu.Unlock()

	dp, exists := s.deliveryPersons[userID]
	if !exists {
		dp = &entity.DeliveryPerson{
			ID:           generateID(),
			UserID:       userID,
			Status:       "available",
			IsOnline:     true,
			CurrentLat:   lat,
			CurrentLng:   lng,
			Rating:       5.0,
			AcceptRadius: 5.0,
			CreatedAt:    time.Now(),
			UpdatedAt:    time.Now(),
		}
		s.deliveryPersons[userID] = dp
	} else {
		dp.IsOnline = true
		dp.CurrentLat = lat
		dp.CurrentLng = lng
		dp.UpdatedAt = time.Now()
	}

	s.persistToETS()
	log.Printf("Delivery person %s online at (%f, %f)", userID, lat, lng)
	return dp, nil
}

func (s *DeliveryService) SetOffline(userID string) error {
	s.mu.Lock()
	defer s.mu.Unlock()

	if dp, exists := s.deliveryPersons[userID]; exists {
		dp.IsOnline = false
		dp.Status = "offline"
		dp.UpdatedAt = time.Now()
		s.persistToETS()
		log.Printf("Delivery person %s offline", userID)
	}
	return nil
}

func (s *DeliveryService) UpdateLocation(userID string, lat, lng float64) error {
	s.mu.Lock()
	defer s.mu.Unlock()

	if dp, exists := s.deliveryPersons[userID]; exists {
		dp.CurrentLat = lat
		dp.CurrentLng = lng
		dp.UpdatedAt = time.Now()
		s.persistToETS()
	}
	return nil
}

func (s *DeliveryService) FindNearbyDeliveryPersons(lat, lng, radiusKm float64) []*entity.DeliveryPerson {
	s.mu.RLock()
	defer s.mu.RUnlock()

	return s.findNearbyLocked(lat, lng, radiusKm)
}

func (s *DeliveryService) findNearbyLocked(lat, lng, radiusKm float64) []*entity.DeliveryPerson {
	var nearby []*entity.DeliveryPerson
	for _, dp := range s.deliveryPersons {
		if !dp.IsOnline || dp.Status != "available" {
			continue
		}
		distance := calculateDistance(lat, lng, dp.CurrentLat, dp.CurrentLng)
		if distance <= radiusKm && distance <= dp.AcceptRadius {
			dpCopy := *dp
			nearby = append(nearby, &dpCopy)
		}
	}
	return nearby
}

func (s *DeliveryService) AssignOrder(orderID string, restaurantLat, restaurantLng float64, customerLat, customerLng float64, customerAddr, restaurantAddr string) (*entity.DeliveryOrder, error) {
	s.mu.Lock()
	defer s.mu.Unlock()

	nearby := s.findNearbyLocked(restaurantLat, restaurantLng, 10.0)
	if len(nearby) == 0 {
		return nil, fmt.Errorf("no delivery persons available")
	}

	var bestDP *entity.DeliveryPerson
	bestScore := -1.0
	for _, dp := range nearby {
		score := s.calculateScore(dp, restaurantLat, restaurantLng)
		if score > bestScore {
			bestScore = score
			bestDP = dp
		}
	}

	if bestDP == nil {
		return nil, fmt.Errorf("no suitable delivery person found")
	}

	distance := calculateDistance(restaurantLat, restaurantLng, customerLat, customerLng)
	estimatedTime := int(distance * 2)

	order := &entity.DeliveryOrder{
		ID:               generateID(),
		OrderID:          orderID,
		DeliveryPersonID: bestDP.UserID,
		RestaurantLat:    restaurantLat,
		RestaurantLng:    restaurantLng,
		RestaurantAddr:   restaurantAddr,
		CustomerLat:      customerLat,
		CustomerLng:      customerLng,
		CustomerAddr:     customerAddr,
		Status:           "assigned",
		Distance:         distance,
		EstimatedTime:    estimatedTime,
		CreatedAt:        time.Now(),
		UpdatedAt:        time.Now(),
	}

	bestDP.Status = "busy"
	s.deliveryOrders[order.ID] = order
	s.persistToETS()

	log.Printf("Order %s assigned to delivery person %s (distance: %.2f km)", orderID, bestDP.UserID, distance)
	return order, nil
}

func (s *DeliveryService) StartDelivery(orderID string) (*entity.DeliveryOrder, error) {
	s.mu.Lock()
	defer s.mu.Unlock()

	order, exists := s.deliveryOrders[orderID]
	if !exists {
		return nil, fmt.Errorf("order not found")
	}

	order.Status = "picked_up"
	order.StartedAt = time.Now()
	order.UpdatedAt = time.Now()
	s.persistToETS()

	log.Printf("Delivery %s started", orderID)
	return order, nil
}

func (s *DeliveryService) CompleteDelivery(orderID string) (*entity.DeliveryOrder, error) {
	s.mu.Lock()
	defer s.mu.Unlock()

	order, exists := s.deliveryOrders[orderID]
	if !exists {
		return nil, fmt.Errorf("order not found")
	}

	order.Status = "delivered"
	order.DeliveredAt = time.Now()
	order.UpdatedAt = time.Now()

	if dp, exists := s.deliveryPersons[order.DeliveryPersonID]; exists {
		dp.Status = "available"
		dp.TotalDeliveries++
		dp.UpdatedAt = time.Now()
	}

	s.persistToETS()
	log.Printf("Delivery %s completed", orderID)
	return order, nil
}

func (s *DeliveryService) GetDeliveryOrder(orderID string) (*entity.DeliveryOrder, error) {
	s.mu.RLock()
	defer s.mu.RUnlock()

	if order, exists := s.deliveryOrders[orderID]; exists {
		return order, nil
	}
	return nil, fmt.Errorf("order not found")
}

func (s *DeliveryService) GetActiveDeliveries(deliveryPersonID string) []*entity.DeliveryOrder {
	s.mu.RLock()
	defer s.mu.RUnlock()

	var active []*entity.DeliveryOrder
	for _, order := range s.deliveryOrders {
		if order.DeliveryPersonID == deliveryPersonID && order.Status != "delivered" && order.Status != "cancelled" {
			active = append(active, order)
		}
	}
	return active
}

func (s *DeliveryService) calculateScore(dp *entity.DeliveryPerson, restaurantLat, restaurantLng float64) float64 {
	distance := calculateDistance(restaurantLat, restaurantLng, dp.CurrentLat, dp.CurrentLng)
	ratingScore := dp.Rating * 20
	availabilityScore := 100.0
	return availabilityScore + ratingScore - (distance * 10)
}

func calculateDistance(lat1, lon1, lat2, lon2 float64) float64 {
	const earthRadius = 6371
	lat1Rad := lat1 * math.Pi / 180
	lat2Rad := lat2 * math.Pi / 180
	deltaLat := (lat2 - lat1) * math.Pi / 180
	deltaLon := (lon2 - lon1) * math.Pi / 180

	a := math.Sin(deltaLat/2)*math.Sin(deltaLat/2) +
		math.Cos(lat1Rad)*math.Cos(lat2Rad)*
			math.Sin(deltaLon/2)*math.Sin(deltaLon/2)
	c := 2 * math.Atan2(math.Sqrt(a), math.Sqrt(1-a))
	return earthRadius * c
}

func generateID() string {
	bytes := make([]byte, 4)
	rand.Read(bytes)
	return fmt.Sprintf("DEL_%s-%d", hex.EncodeToString(bytes), time.Now().UnixNano())
}

func exists(path string) bool {
	_, err := os.Stat(path)
	return err == nil
}

func (s *DeliveryService) Terminate() {
	s.persistToETS()
	log.Println("DeliveryService terminating")
}
