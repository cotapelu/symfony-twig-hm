package kafkasevents

import (
	"encoding/json"
	"errors"
	"testing"

	"github.com/stretchr/testify/assert"
)

func TestSimpleConsumer_RegisterHandler(t *testing.T) {
	c := NewSimpleConsumer()
	handler := func(event map[string]interface{}) error { return nil }
	c.RegisterHandler("test.event", handler)
	assert.Contains(t, c.GetEventTypes(), "test.event")
}

func TestSimpleConsumer_Consume(t *testing.T) {
	c := NewSimpleConsumer()

	t.Run("invalid JSON", func(t *testing.T) {
		err := c.Consume([]byte("{invalid json"))
		assert.Error(t, err)
	})

	t.Run("missing event_type", func(t *testing.T) {
		data := map[string]interface{}{"payload": "something"}
		bytes, _ := json.Marshal(data)
		err := c.Consume(bytes)
		assert.Error(t, err)
		assert.Contains(t, err.Error(), "missing event_type")
	})

	t.Run("handler not found", func(t *testing.T) {
		event := map[string]interface{}{"event_type": "unknown.event", "payload": "test"}
		bytes, _ := json.Marshal(event)
		// Should not panic, just log and return nil
		err := c.Consume(bytes)
		assert.NoError(t, err)
	})

	t.Run("handler called successfully", func(t *testing.T) {
		called := false
		handler := func(event map[string]interface{}) error {
			called = true
			assert.Equal(t, "user.created", event["event_type"])
			payload := event["payload"].(map[string]interface{})
			assert.Equal(t, "user1", payload["user_id"])
			return nil
		}
		c.RegisterHandler("user.created", handler)

		event := map[string]interface{}{
			"event_type": "user.created",
			"payload": map[string]interface{}{
				"user_id": "user1",
			},
		}
		bytes, _ := json.Marshal(event)
		err := c.Consume(bytes)
		assert.NoError(t, err)
		assert.True(t, called)
	})

	t.Run("handler returns error", func(t *testing.T) {
		customErr := errors.New("handler error")
		handler := func(event map[string]interface{}) error {
			return customErr
		}
		c.RegisterHandler("order.failed", handler)

		event := map[string]interface{}{
			"event_type": "order.failed",
			"payload":    map[string]interface{}{},
		}
		bytes, _ := json.Marshal(event)
		err := c.Consume(bytes)
		assert.Error(t, err)
		assert.Equal(t, customErr, err)
	})
}

func TestSimpleConsumer_Close(t *testing.T) {
	c := NewSimpleConsumer()
	// No-op; ensure no panic
	err := c.Close()
	assert.NoError(t, err)
}

func TestSimpleConsumer_GetEventTypes(t *testing.T) {
	c := NewSimpleConsumer()
	c.RegisterHandler("type1", func(event map[string]interface{}) error { return nil })
	c.RegisterHandler("type2", func(event map[string]interface{}) error { return nil })
	types := c.GetEventTypes()
	assert.Contains(t, types, "type1")
	assert.Contains(t, types, "type2")
	assert.Len(t, types, 2)
}
