<?php

declare(strict_types=1);

namespace App\Government\Domain\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use App\Government\Domain\ValueObject\OfficerId;

/**
 * @ORM\Entity
 * @ORM\Table(name: "gov_officer_performance")
 */
class OfficerPerformance
{
    public const METRIC_TASKS_PROCESSED = 'tasks_processed';
    public const METRIC_AVG_PROCESSING_TIME = 'avg_processing_time';
    public const METRIC_CITIZEN_SATISFACTION = 'citizen_satisfaction';

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $officerId;

    #[ORM\Column(type: 'string', length: 50)]
    private string $metricType;

    #[ORM\Column(type: 'float')]
    private float $metricValue;

    #[ORM\Column(type: 'string', length: 20)]
    private string $timePeriod; // e.g., '2026-03' for monthly, '2026-W10' for weekly

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $recordedAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct(
        string $officerId,
        string $metricType,
        float $metricValue,
        string $timePeriod
    ) {
        $this->id = (new \Ramsey\Uuid\Uuid())->toString();
        $this->officerId = $officerId;
        $this->metricType = $metricType;
        $this->metricValue = $metricValue;
        $this->timePeriod = $timePeriod;
        $this->recordedAt = new DateTimeImmutable();
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getOfficerId(): string
    {
        return $this->officerId;
    }

    public function getMetricType(): string
    {
        return $this->metricType;
    }

    public function getMetricValue(): float
    {
        return $this->metricValue;
    }

    public function getTimePeriod(): string
    {
        return $this->timePeriod;
    }

    public function getRecordedAt(): DateTimeImmutable
    {
        return $this->recordedAt;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setMetricValue(float $metricValue): void
    {
        $this->metricValue = $metricValue;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'officerId' => $this->officerId,
            'metricType' => $this->metricType,
            'metricValue' => $this->metricValue,
            'timePeriod' => $this->timePeriod,
            'recordedAt' => $this->recordedAt->format('Y-m-d H:i:s'),
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt->format('Y-m-d H:i:s')
        ];
    }
}