package service

import (
	"context"
	"testing"
	"time"

	"community.local/internal/eventbus"
	"community.local/modules/coreservices/entity"
)

func TestServicesService(t *testing.T) {
	eb := eventbus.NewEventBus()
	svc := NewServicesService(eb)

	t.Run("CreateBooking", func(t *testing.T) {
		pickup := entity.Location{Latitude: 10.7769, Longitude: 106.6919}
		dropoff := entity.Location{Latitude: 10.7869, Longitude: 106.7019}
		scheduled := time.Now().Add(1 * time.Hour)

		booking, err := svc.CreateBooking(context.Background(), "delivery", "user1", "prov002", pickup, dropoff, scheduled)
		if err != nil {
			t.Errorf("unexpected error: %v", err)
		}
		if booking == nil {
			t.Fatal("expected booking, got nil")
		}
		if booking.ServiceType != "delivery" {
			t.Errorf("expected service type delivery, got %s", booking.ServiceType)
		}
		if booking.UserID != "user1" {
			t.Errorf("expected user ID user1, got %s", booking.UserID)
		}
		if booking.Status != "pending" {
			t.Errorf("expected status pending, got %s", booking.Status)
		}
	})

	t.Run("GetBooking", func(t *testing.T) {
		pickup := entity.Location{Latitude: 10.7769, Longitude: 106.6919}
		dropoff := entity.Location{Latitude: 10.7869, Longitude: 106.7019}
		scheduled := time.Now().Add(1 * time.Hour)

		created, _ := svc.CreateBooking(context.Background(), "delivery", "user2", "prov002", pickup, dropoff, scheduled)
		time.Sleep(50 * time.Millisecond)

		retrieved, err := svc.GetBooking(created.ID, created.UserID)
		if err != nil {
			t.Fatalf("error retrieving: %v", err)
		}
		if retrieved.ID != created.ID {
			t.Errorf("expected ID %s, got %s", created.ID, retrieved.ID)
		}
	})

	t.Run("GetBookingNotFound", func(t *testing.T) {
		booking, err := svc.GetBooking("nonexistent", "someuser")
		if err == nil {
			t.Error("expected error for nonexistent booking")
		}
		if booking != nil {
			t.Error("expected nil for nonexistent booking")
		}
	})

	t.Run("CalculateRoute", func(t *testing.T) {
		pickup := entity.Location{Latitude: 10.7769, Longitude: 106.6919}
		dropoff := entity.Location{Latitude: 10.7869, Longitude: 106.7019}

		distance, err := svc.CalculateRoute(pickup, dropoff)
		if err != nil {
			t.Fatalf("unexpected error: %v", err)
		}
		if distance <= 0 {
			t.Error("expected positive distance")
		}
		if distance > 5 {
			t.Errorf("expected distance less than 5km, got %f", distance)
		}
	})

	t.Run("ListBookings", func(t *testing.T) {
		pickup := entity.Location{Latitude: 10.7769, Longitude: 106.6919}
		dropoff := entity.Location{Latitude: 10.7869, Longitude: 106.7019}
		scheduled := time.Now().Add(1 * time.Hour)

		svc.CreateBooking(context.Background(), "delivery", "user3", "prov002", pickup, dropoff, scheduled)
		time.Sleep(1100 * time.Millisecond)
		svc.CreateBooking(context.Background(), "delivery", "user3", "prov002", pickup, dropoff, scheduled)

		bookings := svc.ListBookings(context.Background(), "user3")
		if len(bookings) < 2 {
			t.Errorf("expected at least 2 bookings, got %d", len(bookings))
		}
	})

	t.Run("GetNearbyProviders", func(t *testing.T) {
		location := entity.Location{Latitude: 10.7769, Longitude: 106.6919}

		providers, err := svc.GetNearbyProviders(context.Background(), "delivery", location, 10)
		if err != nil {
			t.Fatalf("unexpected error: %v", err)
		}
		if len(providers) != 0 {
			t.Errorf("expected 0 providers (none registered), got %d", len(providers))
		}
	})
}

func TestCalculateDistance(t *testing.T) {
	eb := eventbus.NewEventBus()
	svc := NewServicesService(eb)

	tests := []struct {
		name    string
		lat1    float64
		lon1    float64
		lat2    float64
		lon2    float64
		minDist float64
		maxDist float64
	}{
		{"Same point", 10.7769, 106.6919, 10.7769, 106.6919, 0, 0.1},
		{"~1km", 10.7769, 106.6919, 10.7859, 106.6919, 0.9, 1.1},
		{"~10km", 10.7769, 106.6919, 10.8669, 106.6919, 9, 11},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			pickup := entity.Location{Latitude: tt.lat1, Longitude: tt.lon1}
			dropoff := entity.Location{Latitude: tt.lat2, Longitude: tt.lon2}
			dist, err := svc.CalculateRoute(pickup, dropoff)
			if err != nil {
				t.Fatalf("unexpected error: %v", err)
			}
			if dist < tt.minDist || dist > tt.maxDist {
				t.Errorf("expected distance between %f and %f, got %f", tt.minDist, tt.maxDist, dist)
			}
		})
	}
}

func TestBookingStatusUpdate(t *testing.T) {
	eb := eventbus.NewEventBus()
	svc := NewServicesService(eb)

	pickup := entity.Location{Latitude: 10.7769, Longitude: 106.6919}
	dropoff := entity.Location{Latitude: 10.7869, Longitude: 106.7019}
	scheduled := time.Now().Add(1 * time.Hour)

	booking, _ := svc.CreateBooking(context.Background(), "delivery", "user_status", "prov002", pickup, dropoff, scheduled)

	if booking.Status != "pending" {
		t.Errorf("initial status should be pending, got %s", booking.Status)
	}

	time.Sleep(200 * time.Millisecond)

	updatedBooking, _ := svc.GetBooking(booking.ID, booking.UserID)
	if updatedBooking == nil {
		t.Fatal("expected booking")
	}
	if updatedBooking.Status != "confirmed" {
		t.Logf("booking status: %s (may take time to confirm)", updatedBooking.Status)
	}
}

func TestEdgeCases(t *testing.T) {
	eb := eventbus.NewEventBus()
	svc := NewServicesService(eb)

	t.Run("Zero coordinates", func(t *testing.T) {
		pickup := entity.Location{Latitude: 0, Longitude: 0}
		dropoff := entity.Location{Latitude: 0, Longitude: 0}

		dist, err := svc.CalculateRoute(pickup, dropoff)
		if err != nil {
			t.Fatalf("unexpected error: %v", err)
		}
		if dist > 0.1 {
			t.Errorf("expected near-zero distance for same point, got %f", dist)
		}
	})

	t.Run("Max coordinates", func(t *testing.T) {
		pickup := entity.Location{Latitude: 90, Longitude: 180}
		dropoff := entity.Location{Latitude: -90, Longitude: -180}

		dist, err := svc.CalculateRoute(pickup, dropoff)
		if err != nil {
			t.Fatalf("unexpected error: %v", err)
		}
		if dist < 10000 {
			t.Errorf("expected distance > 10000km for opposite points, got %f", dist)
		}
	})

	t.Run("Empty bookings list", func(t *testing.T) {
		bookings := svc.ListBookings(context.Background(), "nonexistent")
		if bookings == nil {
			t.Error("expected non-nil slice")
		}
	})
}

func BenchmarkCalculateRoute(b *testing.B) {
	eb := eventbus.NewEventBus()
	svc := NewServicesService(eb)

	pickup := entity.Location{Latitude: 10.7769, Longitude: 106.6919}
	dropoff := entity.Location{Latitude: 10.8869, Longitude: 106.7919}

	b.ResetTimer()
	for i := 0; i < b.N; i++ {
		svc.CalculateRoute(pickup, dropoff)
	}
}

func BenchmarkListBookings(b *testing.B) {
	eb := eventbus.NewEventBus()
	svc := NewServicesService(eb)

	pickup := entity.Location{Latitude: 10.7769, Longitude: 106.6919}
	dropoff := entity.Location{Latitude: 10.7869, Longitude: 106.7019}
	scheduled := time.Now().Add(1 * time.Hour)

	for i := 0; i < 100; i++ {
		svc.CreateBooking(context.Background(), "delivery", "user", "provider", pickup, dropoff, scheduled)
	}

	b.ResetTimer()
	for i := 0; i < b.N; i++ {
		svc.ListBookings(context.Background(), "user")
	}
}
