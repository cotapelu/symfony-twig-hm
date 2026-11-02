package eventbus

import (
	"encoding/json"
	"fmt"
	"log"
	"sync"

	"community.local/internal/domain"
)

type EventHandler func(event *domain.DomainEvent) error

type EventBus struct {
	handlers map[domain.EventType][]EventHandler
	mu       sync.RWMutex
}

func NewEventBus() *EventBus {
	return &EventBus{
		handlers: make(map[domain.EventType][]EventHandler),
	}
}

func (eb *EventBus) Subscribe(eventType domain.EventType, handler EventHandler) {
	eb.mu.Lock()
	defer eb.mu.Unlock()

	eb.handlers[eventType] = append(eb.handlers[eventType], handler)
	log.Printf("Subscribed to event: %s", eventType)
}

func (eb *EventBus) Publish(event *domain.DomainEvent) error {
	eb.mu.RLock()
	handlers := eb.handlers[event.Type]
	eb.mu.RUnlock()

	if len(handlers) == 0 {
		log.Printf("No handlers for event: %s", event.Type)
		return nil
	}

	eventJSON, _ := json.Marshal(event)
	log.Printf("Publishing event: %s, data: %s", event.Type, string(eventJSON))

	var wg sync.WaitGroup
	errChan := make(chan error, len(handlers))

	for _, handler := range handlers {
		wg.Add(1)
		go func(h EventHandler) {
			defer wg.Done()
			defer func() {
				if r := recover(); r != nil {
					errChan <- fmt.Errorf("handler panicked: %v", r)
				}
			}()
			if err := h(event); err != nil {
				errChan <- err
			}
		}(handler)
	}

	wg.Wait()
	close(errChan)

	var errs []error
	for err := range errChan {
		if err != nil {
			errs = append(errs, err)
		}
	}

	if len(errs) > 0 {
		return fmt.Errorf("handler errors: %v", errs)
	}
	return nil
}

func (eb *EventBus) PublishAsync(event *domain.DomainEvent) {
	go func() {
		defer func() {
			if r := recover(); r != nil {
				log.Printf("Panic in PublishAsync: %v", r)
			}
		}()
		if err := eb.Publish(event); err != nil {
			log.Printf("Error publishing event: %v", err)
		}
	}()
}
