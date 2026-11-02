<?php

declare(strict_types=1);

namespace App\Government\Domain\Repository;

use App\Government\Domain\Entity\OfficerPerformance;

interface OfficerPerformanceRepositoryInterface
{
    public function findById(string $id): ?OfficerPerformance;
    public function findByOfficerIdAndMetricTypeAndTimePeriod(string $officerId, string $metricType, string $timePeriod): ?OfficerPerformance;
    public function findByOfficerId(string $officerId, int $page = 1, int $perPage = 20): array;
    public function findByMetricType(string $metricType, int $page = 1, int $perPage = 20): array;
    public function save(OfficerPerformance $performance): void;
    public function delete(OfficerPerformance $performance): void;
    public function count(array $filters = []): int;
}