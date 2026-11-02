<?php

declare(strict_types=1);

namespace App\Poll\DomainEvent;

use App\Shared\DomainEvent\DomainEvent;

class PollCreated extends DomainEvent
{
    public function __construct(
        private string $pollId,
        private string $creatorId,
        private string $question,
        private array $options,
    ) {
        parent::__construct([
            'aggregateId' => $pollId,
            'aggregateType' => 'Poll',
        ]);
    }

    public function eventType(): string
    {
        return 'poll.created';
    }

    protected function eventData(): array
    {
        return [
            'pollId' => $this->pollId,
            'creatorId' => $this->creatorId,
            'question' => $this->question,
            'options' => $this->options,
            'optionCount' => count($this->options),
        ];
    }

    public function pollId(): string
    {
        return $this->pollId;
    }

    public function creatorId(): string
    {
        return $this->creatorId;
    }

    public function question(): string
    {
        return $this->question;
    }
}