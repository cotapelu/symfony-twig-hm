<?php

declare(strict_types=1);

namespace App\Call\DomainEvent;

use App\Shared\DomainEvent\DomainEvent;

class CallStarted extends DomainEvent
{
    public function __construct(
        private string $callId,
        private string $initiatorId,
        private array $participants,
    ) {
        parent::__construct([
            'aggregateId' => $callId,
            'aggregateType' => 'Call',
        ]);
    }

    public function eventType(): string
    {
        return 'call.started';
    }

    protected function eventData(): array
    {
        return [
            'callId' => $this->callId,
            'initiatorId' => $this->initiatorId,
            'participants' => $this->participants,
            'participantCount' => count($this->participants),
        ];
    }

    public function callId(): string
    {
        return $this->callId;
    }

    public function initiatorId(): string
    {
        return $this->initiatorId;
    }

    public function participants(): array
    {
        return $this->participants;
    }
}