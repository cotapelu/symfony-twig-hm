package DeliveryService

import (
	"community.local/modules/DeliveryService/handler"
	"community.local/modules/DeliveryService/service"
)

// NewHandler creates a new DeliveryHandler with file-based persistence
func NewHandler() *handler.DeliveryHandler {
	svc := service.NewDeliveryService("/tmp/delivery.json")
	return handler.NewDeliveryHandler(svc)
}
