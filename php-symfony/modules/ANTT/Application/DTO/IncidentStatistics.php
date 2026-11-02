<?php

declare(strict_types=1);

namespace App\ANTT\Application\DTO;

class IncidentStatistics
{
    public function __construct(
        public readonly int $total,
        public readonly int $open,
        public readonly int $resolved,
        public readonly array $byStatus,
        public readonly array $byType
    ) {}

    public function toArray(): array
    {
        return [
            'total' => $this->total,
            'open' => $this->open,
            'resolved' => $this->resolved,
            'resolution_rate' => $this->total > 0 
                ? round(($this->resolved / $this->total) * 100, 2) 
                : 0,
            'by_status' => $this->byStatus,
            'by_type' => $this->byType,
        ];
    }
}
