<?php

declare(strict_types=1);

namespace App\CustomerService\Event;

use App\Shared\DomainEvent\DomainEvent;
use DateTimeImmutable;

class TicketStatusChangedEvent extends DomainEvent
{
    public function __construct(
        public readonly string $ticketId,
        public readonly string $status,
        public readonly string $userId,
        public readonly ?DateTimeImmutable $changedAt = null,
    ) {
        parent::__construct();
    }

    public function eventType(): string
    {
        return 'ticket.status_changed';
    }

    protected function eventData(): array
    {
        return [
            'ticketId' => $this->ticketId,
            'status' => $this->status,
            'userId' => $this->userId,
        ];
    }
}
