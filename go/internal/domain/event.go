package domain

type EventType string

const (
	EventMessageSent      EventType = "MessageSent"
	EventTypingStart      EventType = "TypingStart"
	EventTypingStop       EventType = "TypingStop"
	EventPresenceChange   EventType = "PresenceChange"
	EventNotificationSend EventType = "NotificationSend"
	EventDeliveryConfirm  EventType = "DeliveryConfirm"
	EventPaymentRequested EventType = "PaymentRequested"
	EventPaymentCompleted EventType = "PaymentCompleted"
)

type DomainEvent struct {
	ID        string                 `json:"id"`
	Type      EventType              `json:"type"`
	Version   string                 `json:"version"`
	Data      interface{}            `json:"data"`
	Timestamp int64                  `json:"timestamp"`
	Metadata  map[string]interface{} `json:"metadata,omitempty"`
}
