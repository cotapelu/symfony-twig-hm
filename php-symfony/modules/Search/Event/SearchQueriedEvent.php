<?php

declare(strict_types=1);

namespace App\Search\Event;

use App\Shared\DomainEvent\DomainEvent;

class SearchQueriedEvent extends DomainEvent
{
    public function __construct(
        private readonly string $userId,
        private readonly string $query,
        private readonly int $resultCount,
        private readonly array $entityTypes,
    ) {
        parent::__construct();
    }

    public function eventType(): string
    {
        return 'SearchQueried';
    }

    protected function eventData(): array
    {
        return [
            'user_id' => $this->userId,
            'query' => $this->query,
            'result_count' => $this->resultCount,
            'entity_types' => $this->entityTypes,
        ];
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    public function getResultCount(): int
    {
        return $this->resultCount;
    }

    public function getEntityTypes(): array
    {
        return $this->entityTypes;
    }
}
