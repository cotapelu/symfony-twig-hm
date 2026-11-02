package locationtracking

import (
	"community.local/modules/locationtracking/handler"
	"community.local/modules/locationtracking/service"
)

// NewHandler creates a new LocationTrackingHandler with file-based persistence
func NewHandler() *handler.LocationTrackingHandler {
	svc := service.NewLocationTrackingService("/tmp/location_tracking.json")
	return handler.NewLocationTrackingHandler(svc)
}
