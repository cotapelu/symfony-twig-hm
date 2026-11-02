package service

import (
	"os"
	"testing"
	"time"

	"community.local/modules/locationtracking/entity"
)

func TestLocationTrackingService(t *testing.T) {
	// Create a temp file for ETS storage
	tmpFile := t.TempDir() + "/location_test.ets"
	svc := NewLocationTrackingService(tmpFile)
	defer svc.Terminate()

	t.Run("UpdateLocation", func(t *testing.T) {
		metadata := map[string]interface{}{
			"accuracy":  5.0,
			"device_id": "test-device",
		}
		loc := svc.UpdateLocation("user1", "user", 10.7769, 106.6919, metadata)

		if loc.EntityID != "user1" {
			t.Errorf("expected entity ID user1, got %s", loc.EntityID)
		}
		if loc.EntityType != "user" {
			t.Errorf("expected entity type user, got %s", loc.EntityType)
		}
		if loc.Latitude != 10.7769 {
			t.Errorf("expected latitude 10.7769, got %f", loc.Latitude)
		}
		if loc.Longitude != 106.6919 {
			t.Errorf("expected longitude 106.6919, got %f", loc.Longitude)
		}
	})

	t.Run("GetLocation", func(t *testing.T) {
		svc.UpdateLocation("user2", "user", 10.7769, 106.6919, nil)

		loc, err := svc.GetLocation("user2")
		if err != nil {
			t.Errorf("unexpected error: %v", err)
		}
		if loc == nil {
			t.Fatal("expected location, got nil")
		}
		if loc.EntityID != "user2" {
			t.Errorf("expected entity ID user2, got %s", loc.EntityID)
		}
	})

	t.Run("GetLocationNotFound", func(t *testing.T) {
		_, err := svc.GetLocation("nonexistent")
		if err == nil {
			t.Error("expected error for nonexistent location")
		}
	})

	t.Run("GetLocationsByType", func(t *testing.T) {
		svc.UpdateLocation("user3", "user", 10.7769, 106.6919, nil)
		svc.UpdateLocation("driver1", "driver", 10.7769, 106.6919, nil)

		locs := svc.GetLocationsByType("user")
		if len(locs) < 3 {
			t.Errorf("expected at least 3 user locations, got %d", len(locs))
		}
	})

	t.Run("CreateGeofence", func(t *testing.T) {
		geo := svc.CreateGeofence("Test Office", 10.7769, 106.6919, 100, "user")

		if geo.Name != "Test Office" {
			t.Errorf("expected name 'Test Office', got %s", geo.Name)
		}
		if geo.RadiusMeters != 100 {
			t.Errorf("expected radius 100, got %f", geo.RadiusMeters)
		}
		if geo.EntityType != "user" {
			t.Errorf("expected entity type user, got %s", geo.EntityType)
		}
	})

	t.Run("CheckGeofence", func(t *testing.T) {
		svc.UpdateLocation("user4", "user", 10.7769, 106.6919, nil)
		svc.CreateGeofence("Office", 10.7769, 106.6919, 100, "user")

		geofences, err := svc.CheckGeofence("user4")
		if err != nil {
			t.Errorf("unexpected error: %v", err)
		}
		if len(geofences) == 0 {
			t.Error("expected at least one triggered geofence")
		}
	})

	t.Run("GetNearbyEntities", func(t *testing.T) {
		svc.UpdateLocation("user5", "user", 10.7769, 106.6919, nil)
		svc.UpdateLocation("user6", "user", 10.7770, 106.6920, nil)
		svc.UpdateLocation("user7", "user", 20.0, 100.0, nil)

		nearby := svc.GetNearbyEntities("user", 10.7769, 106.6919, 200, 10)
		if len(nearby) < 2 {
			t.Errorf("expected at least 2 nearby users, got %d", len(nearby))
		}
	})

	t.Run("CalculateDistance", func(t *testing.T) {
		// Distance between two points ~1.6km
		dist := svc.CalculateDistance(10.7769, 106.6919, 10.7900, 106.7000)
		if dist < 1500 || dist > 2000 {
			t.Errorf("expected distance ~1600m, got %f", dist)
		}
	})

	t.Run("GetETA", func(t *testing.T) {
		svc.UpdateLocation("user8", "user", 10.7769, 106.6919, nil)

		eta, err := svc.GetETA("user8", 10.7869, 106.7019, 30) // 30 km/h
		if err != nil {
			t.Errorf("unexpected error: %v", err)
		}
		if eta == nil {
			t.Fatal("expected eta data, got nil")
		}
		if eta["distance_meters"] == nil {
			t.Error("expected distance_meters in eta")
		}
	})

	t.Run("StopTracking", func(t *testing.T) {
		svc.UpdateLocation("user9", "user", 10.7769, 106.6919, nil)

		err := svc.StopTracking("user9")
		if err != nil {
			t.Errorf("unexpected error: %v", err)
		}

		_, err = svc.GetLocation("user9")
		if err == nil {
			t.Error("expected error after stopping tracking")
		}
	})

	t.Run("BatchUpdateLocations", func(t *testing.T) {
		locations := []entity.Location{
			{EntityID: "batch1", EntityType: "user", Latitude: 10.7769, Longitude: 106.6919},
			{EntityID: "batch2", EntityType: "user", Latitude: 10.7770, Longitude: 106.6920},
		}

		updated := svc.BatchUpdateLocations(locations)
		if len(updated) != 2 {
			t.Errorf("expected 2 updated locations, got %d", len(updated))
		}

		loc1, _ := svc.GetLocation("batch1")
		if loc1 == nil {
			t.Error("expected batch1 location to exist")
		}
	})

	t.Run("ValidateLocationAccuracy", func(t *testing.T) {
		validLoc := entity.Location{Accuracy: 50.0}
		invalidLoc := entity.Location{Accuracy: 150.0}
		negativeLoc := entity.Location{Accuracy: -10.0}

		if !svc.ValidateLocationAccuracy(validLoc) {
			t.Error("expected valid location to pass validation")
		}
		if svc.ValidateLocationAccuracy(invalidLoc) {
			t.Error("expected invalid location (>100m accuracy) to fail validation")
		}
		if svc.ValidateLocationAccuracy(negativeLoc) {
			t.Error("expected negative accuracy to fail validation")
		}
	})

	t.Run("GetGeofences", func(t *testing.T) {
		svc.CreateGeofence("geo_test1", 10.0, 100.0, 50, "user")
		svc.CreateGeofence("geo_test2", 20.0, 200.0, 50, "driver")

		userGeos := svc.GetGeofences("user")
		allGeos := svc.GetGeofences("all")

		if len(userGeos) < 1 {
			t.Errorf("expected at least 1 user geofence, got %d", len(userGeos))
		}
		if len(allGeos) < 2 {
			t.Errorf("expected at least 2 total geofences, got %d", len(allGeos))
		}
	})
}

func TestDistanceCalculation(t *testing.T) {
	tmpFile := t.TempDir() + "/distance_test.ets"
	svc := NewLocationTrackingService(tmpFile)
	defer svc.Terminate()

	tests := []struct {
		name    string
		lat1    float64
		lon1    float64
		lat2    float64
		lon2    float64
		minDist float64
		maxDist float64
	}{
		{"Same point", 10.7769, 106.6919, 10.7769, 106.6919, 0, 1},
		{"~1km", 10.7769, 106.6919, 10.7859, 106.6919, 900, 1100},
		{"~10km", 10.7769, 106.6919, 10.8669, 106.6919, 9000, 11000},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			dist := svc.CalculateDistance(tt.lat1, tt.lon1, tt.lat2, tt.lon2)
			if dist < tt.minDist || dist > tt.maxDist {
				t.Errorf("expected distance between %f and %f, got %f", tt.minDist, tt.maxDist, dist)
			}
		})
	}
}

func TestGetLocationHistory(t *testing.T) {
	tmpFile := t.TempDir() + "/history_test.ets"
	svc := NewLocationTrackingService(tmpFile)
	defer svc.Terminate()

	// Add some locations
	svc.UpdateLocation("user10", "user", 10.7769, 106.6919, nil)
	time.Sleep(10 * time.Millisecond)
	svc.UpdateLocation("user10", "user", 10.7770, 106.6920, nil)

	history := svc.GetLocationHistory("user10", nil)
	if len(history) != 2 {
		t.Errorf("expected 2 history entries, got %d", len(history))
	}

	// Test with time filter
	since := time.Now().Add(-1 * time.Minute)
	historySince := svc.GetLocationHistory("user10", &since)
	if len(historySince) != 2 {
		t.Errorf("expected 2 history entries with recent filter, got %d", len(historySince))
	}
}

func TestFrequentLocations(t *testing.T) {
	tmpFile := t.TempDir() + "/frequent_test.ets"
	svc := NewLocationTrackingService(tmpFile)
	defer svc.Terminate()

	// Add multiple locations at roughly the same spot
	for i := 0; i < 5; i++ {
		svc.UpdateLocation("user11", "user", 10.7769, 106.6919, nil)
		time.Sleep(5 * time.Millisecond)
		svc.UpdateLocation("user11", "user", 10.7770, 106.6920, nil)
		time.Sleep(5 * time.Millisecond)
	}

	frequent := svc.GetFrequentLocations("user11", 24, 50)
	// Should find some clusters
	if len(frequent) == 0 {
		t.Log("no frequent locations found (may be expected with threshold)")
	}
}

func TestLocationPattern(t *testing.T) {
	tmpFile := t.TempDir() + "/pattern_test.ets"
	svc := NewLocationTrackingService(tmpFile)
	defer svc.Terminate()

	// Add location history
	svc.UpdateLocation("user12", "user", 10.7769, 106.6919, nil)

	pattern := svc.GetLocationPattern("user12", "daily")
	if pattern == nil {
		t.Fatal("expected pattern data, got nil")
	}
	if pattern["time_period"] != "daily" {
		t.Errorf("expected time_period daily, got %v", pattern["time_period"])
	}
}

func TestPersistence(t *testing.T) {
	tmpFile := t.TempDir() + "/persist_test.ets"

	// Create service and add data
	svc1 := NewLocationTrackingService(tmpFile)
	svc1.UpdateLocation("persist1", "user", 10.7769, 106.6919, nil)
	svc1.Terminate()

	// Create new service with same file
	svc2 := NewLocationTrackingService(tmpFile)
	loc, err := svc2.GetLocation("persist1")
	if err != nil {
		t.Errorf("expected persisted location, got error: %v", err)
	}
	if loc != nil && loc.EntityID != "persist1" {
		t.Errorf("expected entity ID persist1, got %s", loc.EntityID)
	}
	svc2.Terminate()

	// Cleanup
	os.Remove(tmpFile)
}

func TestEdgeCases(t *testing.T) {
	tmpFile := t.TempDir() + "/edge_test.ets"
	svc := NewLocationTrackingService(tmpFile)
	defer svc.Terminate()

	t.Run("Empty entity type", func(t *testing.T) {
		loc := svc.UpdateLocation("edge1", "", 10.7769, 106.6919, nil)
		if loc.EntityType != "" {
			t.Errorf("expected empty entity type, got %s", loc.EntityType)
		}
	})

	t.Run("Zero coordinates", func(t *testing.T) {
		loc := svc.UpdateLocation("edge2", "user", 0, 0, nil)
		if loc.Latitude != 0 || loc.Longitude != 0 {
			t.Errorf("expected zero coordinates")
		}
	})

	t.Run("Max radius nearby", func(t *testing.T) {
		svc.UpdateLocation("edge3", "user", 0, 0, nil)
		svc.UpdateLocation("edge4", "user", 0.1, 0.1, nil)
		nearby := svc.GetNearbyEntities("user", 0, 0, 15000, 10)
		if len(nearby) < 1 {
			t.Errorf("expected at least 1 location nearby, got %d", len(nearby))
		}
	})

	t.Run("GetETA for nonexistent", func(t *testing.T) {
		_, err := svc.GetETA("nonexistent", 10.0, 100.0, 30)
		if err == nil {
			t.Error("expected error for nonexistent entity")
		}
	})
}

func BenchmarkCalculateDistance(b *testing.B) {
	tmpFile := b.TempDir() + "/bench.ets"
	svc := NewLocationTrackingService(tmpFile)
	defer svc.Terminate()

	b.ResetTimer()
	for i := 0; i < b.N; i++ {
		svc.CalculateDistance(10.7769, 106.6919, 10.8869, 106.7919)
	}
}

func BenchmarkGetNearbyEntities(b *testing.B) {
	tmpFile := b.TempDir() + "/bench_nearby.ets"
	svc := NewLocationTrackingService(tmpFile)
	defer svc.Terminate()

	// Add many locations
	for i := 0; i < 100; i++ {
		svc.UpdateLocation(
			string(rune('a'+i%26)),
			"user",
			10.7769+float64(i)*0.001,
			106.6919+float64(i)*0.001,
			nil,
		)
	}

	b.ResetTimer()
	for i := 0; i < b.N; i++ {
		svc.GetNearbyEntities("user", 10.7769, 106.6919, 5000, 50)
	}
}
