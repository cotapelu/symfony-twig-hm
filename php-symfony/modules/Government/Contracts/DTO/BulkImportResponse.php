<?php

declare(strict_types=1);

namespace App\Government\Contracts\DTO;

class BulkImportResponse
{
    public function __construct(
        public readonly int $successCount,
        public readonly int $failedCount,
        public readonly int $totalCount,
        public readonly array $importedIds,
        public readonly array $errors
    ) {
    }

    public function toArray(): array
    {
        return [
            'successCount' => $this->successCount,
            'failedCount' => $this->failedCount,
            'totalCount' => $this->totalCount,
            'importedIds' => $this->importedIds,
            'errors' => $this->errors,
            'successRate' => $this->totalCount > 0 
                ? round(($this->successCount / $this->totalCount) * 100, 2) 
                : 0
        ];
    }
}
