<?php

declare(strict_types=1);

namespace Platform\Eventing\Outbox;

use InvalidArgumentException;
use Platform\Eventing\Exception\EventingException;

class OutboxProcessor
{
    private const DEFAULT_LIMIT = 100;
    private const MAX_LIMIT = 1000;

    public function processOutbox(): int
    {
        // TODO: Replace with actual persistence-based processing when repository is available
        return 0;
    }

    public function addToOutbox(string $eventType, array $payload, string $aggregateId): void
    {
        $this->validateEventType($eventType);
        $this->validatePayload($payload);
        $this->validateAggregateId($aggregateId);

        // TODO: Replace with actual persistence when repository is available
    }

    public function getPendingMessages(int $limit): array
    {
        if ($limit <= 0) {
            throw new InvalidArgumentException('Limit must be a positive integer');
        }

        if ($limit > self::MAX_LIMIT) {
            throw new InvalidArgumentException(sprintf(
                'Limit exceeds maximum of %d',
                self::MAX_LIMIT
            ));
        }

        // TODO: Replace with actual persistence-based retrieval when repository is available
        return [];
    }

    private function validateEventType(string $eventType): void
    {
        if (empty($eventType)) {
            throw new InvalidArgumentException('Event type cannot be empty');
        }

        if (!preg_match('/^[a-zA-Z0-9\\_.]+$/', $eventType)) {
            throw new InvalidArgumentException('Event type contains invalid characters');
        }
    }

    private function validatePayload(array $payload): void
    {
        if (empty($payload)) {
            throw new InvalidArgumentException('Event payload cannot be empty');
        }
    }

    private function validateAggregateId(string $aggregateId): void
    {
        if (empty($aggregateId)) {
            throw new InvalidArgumentException('Aggregate ID cannot be empty');
        }
    }
}