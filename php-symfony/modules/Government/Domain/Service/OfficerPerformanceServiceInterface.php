<?php

declare(strict_types=1);

namespace App\Government\Domain\Service;

interface OfficerPerformanceServiceInterface
{
    public function recordTaskProcessed(string $officerId, int $processingTimeSeconds, ?int $satisfactionScore = null): void;
    public function getOfficerMetrics(string $officerId, string $timePeriod): array;
    public function getOfficerPerformance(string $officerId, string $metricType, string $timePeriod): float|null;
    public function getWardPerformance(string $wardId, string $metricType, string $timePeriod): array;
}