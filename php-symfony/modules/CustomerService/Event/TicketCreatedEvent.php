<?php

declare(strict_types=1);

namespace App\CustomerService\Event;

use App\Shared\DomainEvent\DomainEvent;
use DateTimeImmutable;

class TicketCreatedEvent extends DomainEvent
{
    public function __construct(
        public readonly string $ticketId,
        public readonly string $userId,
        public readonly string $subject,
        public readonly string $priority,
        public readonly ?DateTimeImmutable $createdAt = null,
    ) {
        parent::__construct();
    }

    public function eventType(): string
    {
        return 'ticket.created';
    }

    protected function eventData(): array
    {
        return [
            'ticketId' => $this->ticketId,
            'userId' => $this->userId,
            'subject' => $this->subject,
            'priority' => $this->priority,
        ];
    }
}
