package coreservices

import (
	"community.local/modules/coreservices/handler"
	"community.local/modules/coreservices/service"
)

// NewHandler creates a new ServicesHandler with in-memory service
func NewHandler() *handler.ServicesHandler {
	svc := service.NewServicesService(nil)
	return handler.NewServicesHandler(svc)
}
