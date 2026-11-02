package kafkasevents

import (
	"encoding/json"
	"fmt"
	"log"
)

type SimpleConsumer struct {
	handlers map[string]func(event map[string]interface{}) error
}

func NewSimpleConsumer() *SimpleConsumer {
	return &SimpleConsumer{
		handlers: make(map[string]func(event map[string]interface{}) error),
	}
}

func (c *SimpleConsumer) RegisterHandler(eventType string, handler func(event map[string]interface{}) error) {
	c.handlers[eventType] = handler
}

func (c *SimpleConsumer) Consume(eventData []byte) error {
	var event map[string]interface{}
	if err := json.Unmarshal(eventData, &event); err != nil {
		return err
	}

	eventType, ok := event["event_type"].(string)
	if !ok {
		return fmt.Errorf("missing event_type in event")
	}

	handler, exists := c.handlers[eventType]
	if !exists {
		log.Printf("No handler for event type: %s", eventType)
		return nil
	}

	return handler(event)
}

func (c *SimpleConsumer) Close() error {
	return nil
}

func (c *SimpleConsumer) GetEventTypes() []string {
	types := make([]string, 0, len(c.handlers))
	for eventType := range c.handlers {
		types = append(types, eventType)
	}
	return types
}
