package notificationsrealtime

import (
	"community.local/modules/notificationsrealtime/handler"
	"community.local/modules/notificationsrealtime/service"
)

// NewHandler creates a new NotificationHandler with file-based persistence
func NewHandler() *handler.NotificationHandler {
	svc := service.NewNotificationService("/tmp/notifications.json")
	return handler.NewNotificationHandler(svc)
}
