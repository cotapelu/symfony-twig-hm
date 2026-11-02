package distance

import (
	"math"
	"testing"
)

func TestHaversine(t *testing.T) {
	tests := []struct {
		name     string
		lat1, lon1, lat2, lon2 float64
		want     float64
		epsilon  float64
	}{
		{
			name:    "same point",
			lat1:    0,
			lon1:    0,
			lat2:    0,
			lon2:    0,
			want:    0,
			epsilon: 1e-10,
		},

		{
			name:    "half earth circumference approx",
			lat1:    0,
			lon1:    0,
			lat2:    0,
			lon2:    180,
			want:    math.Pi * EarthRadiusKm, // half circumference
			epsilon: 0.1,
		},

		{
			name:    "known distance: Paris to London approx 344 km",
			lat1:    48.8566, // Paris lat
			lon1:    2.3522,  // Paris lon
			lat2:    51.5074, // London lat
			lon2:    -0.1278, // London lon
			want:    344.0,   // approximate
			epsilon: 5.0,     // within 5 km due to rounding
		},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			got := Haversine(tt.lat1, tt.lon1, tt.lat2, tt.lon2)
			if math.Abs(got-tt.want) > tt.epsilon {
				t.Errorf("Haversine() = %v, want %v (±%v)", got, tt.want, tt.epsilon)
			}
		})
	}
}

func TestHaversineMeters(t *testing.T) {
	km := Haversine(0, 0, 1, 0)
	m := HaversineMeters(0, 0, 1, 0)
	// 1 degree latitude ≈ 111 km
	expectedM := km * 1000
	if math.Abs(m-expectedM) > 0.001 {
		t.Errorf("HaversineMeters() = %v, expected %v (from km*1000)", m, expectedM)
	}
}

func TestCalculate(t *testing.T) {
	// Calculate just delegates to Haversine
	got := Calculate(0, 0, 1, 1)
	want := Haversine(0, 0, 1, 1)
	if math.Abs(got-want) > 1e-10 {
		t.Errorf("Calculate() = %v, want %v", got, want)
	}
}
