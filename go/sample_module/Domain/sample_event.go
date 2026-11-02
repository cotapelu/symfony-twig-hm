// Sample Domain Event
package domain

import (
	"time"
)

// SampleEvent represents a domain event
type SampleEvent struct {
	EntityID   int
	Name       string
	Value      int
	OccurredOn time.Time
}

// NewSampleEvent creates a new SampleEvent
func NewSampleEvent(entityID int, name string, value int) *SampleEvent {
	return &SampleEvent{
		EntityID:   entityID,
		Name:       name,
		Value:      value,
		OccurredOn: time.Now(),
	}
}