<?php

declare(strict_types=1);

namespace App\Government\Domain\Service;

interface BulkExportServiceInterface
{
    public function exportCitizensToCSV(array $filters = []): string;
    public function exportCitizensToJSON(array $filters = []): string;
}