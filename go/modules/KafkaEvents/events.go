package kafkasevents

import (
	"context"
)

// Producer defines the interface for Kafka event producers
type Producer interface {
	Produce(ctx context.Context, key string, eventData []byte) error
	Close() error
}

// Consumer defines the interface for Kafka event consumers
type Consumer interface {
	Subscribe(ctx context.Context, topics []string, handler func(event interface{}) error) error
	Close() error
}
