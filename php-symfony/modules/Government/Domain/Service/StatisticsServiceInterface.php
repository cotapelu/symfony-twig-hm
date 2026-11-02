<?php

declare(strict_types=1);

namespace App\Government\Domain\Service;

interface StatisticsServiceInterface
{
    public function getCitizenStatistics(array $filters = []): array;
    public function getDocumentStatistics(array $filters = []): array;
    public function getProcedureStatistics(array $filters = []): array;
    public function getOfficerStatistics(array $filters = []): array;
    public function getDashboardStatistics(array $filters = []): array;
}