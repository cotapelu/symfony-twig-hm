package Courier

import (
	"database/sql"

	"community.local/modules/Courier/handler"
	"github.com/gin-gonic/gin"
)

// Handler wrapped with custom routes to avoid conflicts
type HandlerWrapper struct {
	inner *handler.CourierHandler
}

func NewHandler(db *sql.DB) *HandlerWrapper {
	return &HandlerWrapper{
		inner: handler.NewCourierHandler(db),
	}
}

func (w *HandlerWrapper) RegisterRoutes(router *gin.Engine) {
	courier := router.Group("/api/courier")
	{
		courier.POST("/register", w.inner.RegisterCourier)
		courier.GET("/:id", w.inner.GetCourier)
		courier.GET("/available", w.inner.GetAvailableCouriers)
		courier.PUT("/:id/location", w.inner.UpdateLocation)
		courier.PUT("/:id/status", w.inner.UpdateStatus)

		// Fixed: use /orders/courier/:courier_id pattern to avoid conflict with /api/courier/:id
		courier.POST("/orders", w.inner.CreateOrder)
		courier.GET("/orders/:id", w.inner.GetOrder)
		courier.POST("/orders/:id/assign", w.inner.AssignOrder)
		courier.POST("/orders/:id/pickup", w.inner.PickupOrder)
		courier.POST("/orders/:id/deliver", w.inner.DeliverOrder)
		courier.GET("/orders/courier/:courier_id", w.inner.GetCourierOrders)
		courier.GET("/orders/courier/:courier_id/active", w.inner.GetActiveOrders)
	}
}
