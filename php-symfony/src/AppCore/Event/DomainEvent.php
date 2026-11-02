<?php

declare(strict_types=1);

namespace App\AppCore\Event;

class DomainEvent extends Event
{
    public function __construct(string $name, array $data = [])
    {
        parent::__construct($name, $data);
    }

    public function toPayload(): array
    {
        return [
            'event_type' => $this->getName(),
            'data' => $this->getData(),
            'timestamp' => $this->getTimestamp()->format(\DateTime::ATOM),
            'correlation_id' => $this->getCorrelationId(),
            'event_version' => '1.0',
        ];
    }
}