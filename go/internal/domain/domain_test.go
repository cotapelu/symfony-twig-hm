package domain

import (
	"testing"

	"github.com/stretchr/testify/assert"
)

func TestEventType_String(t *testing.T) {
	// Ensure constants have expected string values
	assert.Equal(t, "MessageSent", string(EventMessageSent))
	assert.Equal(t, "TypingStart", string(EventTypingStart))
	assert.Equal(t, "TypingStop", string(EventTypingStop))
	assert.Equal(t, "PresenceChange", string(EventPresenceChange))
	assert.Equal(t, "NotificationSend", string(EventNotificationSend))
	assert.Equal(t, "DeliveryConfirm", string(EventDeliveryConfirm))
	assert.Equal(t, "PaymentRequested", string(EventPaymentRequested))
	assert.Equal(t, "PaymentCompleted", string(EventPaymentCompleted))
}

func TestDomainEvent_Basic(t *testing.T) {
	evt := DomainEvent{
		ID:        "evt-1",
		Type:      EventMessageSent,
		Version:   "1.0",
		Data:      map[string]interface{}{"body": "hello"},
		Timestamp: int64(1234567890),
		Metadata:  nil,
	}
	assert.Equal(t, "evt-1", evt.ID)
	assert.Equal(t, EventMessageSent, evt.Type)
	assert.Equal(t, "1.0", evt.Version)
	assert.Equal(t, int64(1234567890), evt.Timestamp)
	assert.Nil(t, evt.Metadata)
}
