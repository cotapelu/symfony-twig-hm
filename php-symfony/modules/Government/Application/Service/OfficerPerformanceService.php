<?php

declare(strict_types=1);

namespace App\Government\Application\Service;

use App\Government\Domain\Service\OfficerPerformanceServiceInterface;
use App\Government\Domain\Entity\OfficerPerformance;
use App\Government\Domain\Repository\OfficerPerformanceRepositoryInterface;

class OfficerPerformanceService implements OfficerPerformanceServiceInterface
{
    private OfficerPerformanceRepositoryInterface $officerPerformanceRepository;

    public function __construct(OfficerPerformanceRepositoryInterface $officerPerformanceRepository)
    {
        $this->officerPerformanceRepository = $officerPerformanceRepository;
    }

    public function recordTaskProcessed(string $officerId, int $processingTimeSeconds, ?int $satisfactionScore = null): void
    {
        // Record tasks processed metric
        $currentMonth = (new \DateTimeImmutable())->format('Y-m');
        $existing = $this->officerPerformanceRepository->findByOfficerIdAndMetricTypeAndTimePeriod(
            $officerId,
            OfficerPerformance::METRIC_TASKS_PROCESSED,
            $currentMonth
        );

        if ($existing) {
            $newValue = $existing->getMetricValue() + 1;
            $existing->setMetricValue($newValue);
            $this->officerPerformanceRepository->save($existing);
        } else {
            $performance = new OfficerPerformance(
                $officerId,
                OfficerPerformance::METRIC_TASKS_PROCESSED,
                1,
                $currentMonth
            );
            $this->officerPerformanceRepository->save($performance);
        }

        // Record average processing time metric (we'll keep a running average)
        // For simplicity, we'll just record the latest processing time as the metric value
        // In a real system, we would compute the average over the period.
        $perfTime = new OfficerPerformance(
            $officerId,
            OfficerPerformance::METRIC_AVG_PROCESSING_TIME,
            (float) $processingTimeSeconds,
            $currentMonth
        );
        $this->officerPerformanceRepository->save($perfTime);

        // Record citizen satisfaction if provided
        if ($satisfactionScore !== null) {
            $perfSat = new OfficerPerformance(
                $officerId,
                OfficerPerformance::METRIC_CITIZEN_SATISFACTION,
                (float) $satisfactionScore,
                $currentMonth
            );
            $this->officerPerformanceRepository->save($perfSat);
        }
    }

    public function getOfficerMetrics(string $officerId, string $timePeriod): array
    {
        $metrics = [];
        $performances = $this->officerPerformanceRepository->findByOfficerId($officerId);

        foreach ($performances as $performance) {
            if ($performance->getTimePeriod() === $timePeriod) {
                $metrics[$performance->getMetricType()] = $performance->getMetricValue();
            }
        }

        // Ensure all metric types are present
        if (!isset($metrics[OfficerPerformance::METRIC_TASKS_PROCESSED])) {
            $metrics[OfficerPerformance::METRIC_TASKS_PROCESSED] = 0;
        }
        if (!isset($metrics[OfficerPerformance::METRIC_AVG_PROCESSING_TIME])) {
            $metrics[OfficerPerformance::METRIC_AVG_PROCESSING_TIME] = 0.0;
        }
        if (!isset($metrics[OfficerPerformance::METRIC_CITIZEN_SATISFACTION])) {
            $metrics[OfficerPerformance::METRIC_CITIZEN_SATISFACTION] = 0.0;
        }

        return $metrics;
    }

    public function getOfficerPerformance(string $officerId, string $metricType, string $timePeriod): float|null
    {
        $performance = $this->officerPerformanceRepository->findByOfficerIdAndMetricTypeAndTimePeriod(
            $officerId,
            $metricType,
            $timePeriod
        );

        return $performance ? $performance->getMetricValue() : null;
    }

    public function getWardPerformance(string $wardId, string $metricType, string $timePeriod): array
    {
        // This would require joining with officer data to get officers in a ward.
        // For simplicity, we return an empty array.
        // In a real system, we would query officers by ward and then get their performance.
        return [];
    }
}