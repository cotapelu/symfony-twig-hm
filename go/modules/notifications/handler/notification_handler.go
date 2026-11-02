package handler

import (
	"context"
	"encoding/json"
	"net/http"
	"strconv"

	"community.local/modules/notifications/service"
)

// NotificationMiddleware handles authentication for notifications
func NotificationMiddleware(next http.HandlerFunc) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		// Require authentication
		userID := r.Header.Get("X-User-ID")
		if userID == "" {
			w.Header().Set("Content-Type", "application/json")
			w.WriteHeader(http.StatusUnauthorized)
			json.NewEncoder(w).Encode(map[string]string{"error": "authentication required"})
			return
		}
		
		// Check admin role if needed
		userRoles := r.Header.Get("X-User-Roles")
		isAdmin := false
		if userRoles != "" {
			// Parse roles - format: "role1,role2"
			roles := splitRoles(userRoles)
			for _, role := range roles {
				if role == "admin" || role == "ROLE_ADMIN" {
					isAdmin = true
					break
				}
			}
		}
		
		// Add to context for downstream use
		ctx := context.WithValue(r.Context(), "userID", userID)
		ctx = context.WithValue(ctx, "isAdmin", isAdmin)
		
		// Call next handler with updated context
		next.ServeHTTP(w, r.WithContext(ctx))
	}
}

func splitRoles(rolesStr string) []string {
	// Simple split by comma
	result := make([]string, 0)
	start := 0
	for i := 0; i <= len(rolesStr); i++ {
		if i == len(rolesStr) || rolesStr[i] == ',' {
			if i > start {
				result = append(result, rolesStr[start:i])
			}
			start = i + 1
		}
	}
	return result
}

// NotificationService defines the service methods required by NotificationHandler.
type NotificationService interface {
	Subscribe(context.Context, string, string, string, string) (*service.DeviceSubscription, error)
	Unsubscribe(context.Context, string) (*service.DeviceSubscription, error)
	GetUserSubscriptions(context.Context, string) ([]*service.DeviceSubscription, error)
	SendNotification(context.Context, string, string, string, map[string]interface{}) (*service.Notification, error)
	SendBroadcast(context.Context, []string, string, string, map[string]interface{}) ([]*service.Notification, error)
	GetUserNotifications(context.Context, string, string, int) ([]*service.Notification, error)
	MarkNotificationRead(context.Context, string) error
	ProcessScheduledNotifications(context.Context) error
	RetryFailedNotifications(context.Context) error
}

type NotificationHandler struct {
	notificationService NotificationService
}

func NewNotificationHandler(notificationService NotificationService) *NotificationHandler {
	return &NotificationHandler{
		notificationService: notificationService,
	}
}

func (h *NotificationHandler) Subscribe(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "application/json")

	if r.Method != http.MethodPost {
		w.WriteHeader(http.StatusMethodNotAllowed)
		json.NewEncoder(w).Encode(map[string]string{"error": "method not allowed"})
		return
	}

	var req struct {
		UserID      string `json:"user_id"`
		DeviceToken  string `json:"device_token"`
		DeviceType   string `json:"device_type"`
		DeviceName   string `json:"device_name,omitempty"`
	}

	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"error": "invalid request body"})
		return
	}

	if req.UserID == "" || req.DeviceToken == "" || req.DeviceType == "" {
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"error": "user_id, device_token, and device_type are required"})
		return
	}

	subscription, err := h.notificationService.Subscribe(r.Context(), req.UserID, req.DeviceToken, req.DeviceType, req.DeviceName)
	if err != nil {
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"error": err.Error()})
		return
	}

	w.WriteHeader(http.StatusCreated)
	json.NewEncoder(w).Encode(subscription)
}

func (h *NotificationHandler) Unsubscribe(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "application/json")

	subscriptionID := r.URL.Path[len("/api/notifications/unsubscribe/"):]
	if subscriptionID == "" {
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"error": "subscription_id is required"})
		return
	}

	subscription, err := h.notificationService.Unsubscribe(r.Context(), subscriptionID)
	if err != nil {
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"error": err.Error()})
		return
	}

	if subscription == nil {
		w.WriteHeader(http.StatusNotFound)
		json.NewEncoder(w).Encode(map[string]string{"error": "subscription not found"})
		return
	}

	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(subscription)
}

func (h *NotificationHandler) GetUserSubscriptions(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "application/json")

	userID := r.URL.Query().Get("user_id")
	if userID == "" {
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"error": "user_id is required"})
		return
	}

	subscriptions, err := h.notificationService.GetUserSubscriptions(r.Context(), userID)
	if err != nil {
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"error": err.Error()})
		return
	}

	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(map[string]interface{}{
		"subscriptions": subscriptions,
		"total":        len(subscriptions),
	})
}

func (h *NotificationHandler) SendNotification(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "application/json")

	if r.Method != http.MethodPost {
		w.WriteHeader(http.StatusMethodNotAllowed)
		json.NewEncoder(w).Encode(map[string]string{"error": "method not allowed"})
		return
	}

	var req struct {
		UserID string                 `json:"user_id"`
		Title  string                 `json:"title"`
		Body   string                 `json:"body"`
		Data   map[string]interface{} `json:"data,omitempty"`
	}

	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"error": "invalid request body"})
		return
	}

	if req.UserID == "" || req.Title == "" || req.Body == "" {
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"error": "user_id, title, and body are required"})
		return
	}

	notification, err := h.notificationService.SendNotification(r.Context(), req.UserID, req.Title, req.Body, req.Data)
	if err != nil {
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"error": err.Error()})
		return
	}

	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(notification)
}

func (h *NotificationHandler) SendBroadcast(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "application/json")

	if r.Method != http.MethodPost {
		w.WriteHeader(http.StatusMethodNotAllowed)
		json.NewEncoder(w).Encode(map[string]string{"error": "method not allowed"})
		return
	}

	var req struct {
		UserIDs []string              `json:"user_ids"`
		Title   string                 `json:"title"`
		Body    string                 `json:"body"`
		Data    map[string]interface{} `json:"data,omitempty"`
	}

	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"error": "invalid request body"})
		return
	}

	if len(req.UserIDs) == 0 || req.Title == "" || req.Body == "" {
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"error": "user_ids, title, and body are required"})
		return
	}

	notifications, err := h.notificationService.SendBroadcast(r.Context(), req.UserIDs, req.Title, req.Body, req.Data)
	if err != nil {
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"error": err.Error()})
		return
	}

	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(map[string]interface{}{
		"notifications": notifications,
		"total":        len(notifications),
	})
}

func (h *NotificationHandler) GetUserNotifications(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "application/json")

	userID := r.URL.Query().Get("user_id")
	status := r.URL.Query().Get("status")
	limitStr := r.URL.Query().Get("limit")
	limit := 20
	if l, err := strconv.Atoi(limitStr); err == nil && limitStr != "" {
		limit = l
	}

	if userID == "" {
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"error": "user_id is required"})
		return
	}

	notifications, err := h.notificationService.GetUserNotifications(r.Context(), userID, status, limit)
	if err != nil {
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"error": err.Error()})
		return
	}

	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(map[string]interface{}{
		"notifications": notifications,
		"total":         len(notifications),
	})
}

func (h *NotificationHandler) MarkNotificationRead(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "application/json")

	if r.Method != http.MethodPost {
		w.WriteHeader(http.StatusMethodNotAllowed)
		json.NewEncoder(w).Encode(map[string]string{"error": "method not allowed"})
		return
	}

	notificationID := r.URL.Path[len("/api/notifications/mark-read/"):]
	if notificationID == "" {
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"error": "notification_id is required"})
		return
	}

	err := h.notificationService.MarkNotificationRead(r.Context(), notificationID)
	if err != nil {
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"error": err.Error()})
		return
	}

	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(map[string]string{"message": "notification marked as read"})
}

func (h *NotificationHandler) ProcessScheduled(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "application/json")

	if r.Method != http.MethodPost {
		w.WriteHeader(http.StatusMethodNotAllowed)
		json.NewEncoder(w).Encode(map[string]string{"error": "method not allowed"})
		return
	}

	err := h.notificationService.ProcessScheduledNotifications(r.Context())
	if err != nil {
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"error": err.Error()})
		return
	}

	err = h.notificationService.RetryFailedNotifications(r.Context())
	if err != nil {
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"error": err.Error()})
		return
	}

	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(map[string]string{
		"message": "notifications processed successfully",
	})
}

func (h *NotificationHandler) SetupRoutes(mux *http.ServeMux) {
	mux.HandleFunc("/api/notifications/subscribe", NotificationMiddleware(h.Subscribe))
	mux.HandleFunc("/api/notifications/subscriptions", h.GetUserSubscriptions)
	mux.HandleFunc("/api/notifications/send", NotificationMiddleware(h.SendNotification))
	mux.HandleFunc("/api/notifications/broadcast", NotificationMiddleware(h.SendBroadcast))
	mux.HandleFunc("/notifications/user", h.GetUserNotifications)
	mux.HandleFunc("/api/notifications/mark-read/", NotificationMiddleware(h.MarkNotificationRead))
	mux.HandleFunc("/api/notifications/process-scheduled", h.ProcessScheduled)
}