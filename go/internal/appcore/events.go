package appcore

// Event interface
type Event interface {
    Name() string
}

// EventHandler interface
type EventHandler interface {
    Handle(event Event) error
}

// EventDispatcher manages event subscriptions and dispatching
type EventDispatcher struct {
    handlers map[string][]EventHandler
}

// NewEventDispatcher creates a new event dispatcher
func NewEventDispatcher() *EventDispatcher {
    return &EventDispatcher{
        handlers: make(map[string][]EventHandler),
    }
}

// Subscribe adds an event handler for a specific event
func (ed *EventDispatcher) Subscribe(eventName string, handler EventHandler) {
    ed.handlers[eventName] = append(ed.handlers[eventName], handler)
}

// Dispatch sends an event to all subscribed handlers
func (ed *EventDispatcher) Dispatch(event Event) error {
    if handlers, ok := ed.handlers[event.Name()]; ok {
        for _, handler := range handlers {
            if err := handler.Handle(event); err != nil {
                return err
            }
        }
    }
    return nil
}