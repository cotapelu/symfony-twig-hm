<?php

namespace App\SmartCity\Domain\Entity;

use App\Shared\Domain\Entity\BaseEntity;

class SmartWard extends BaseEntity
{
    private string $wardCode;
    private string $name;
    private float $latitude;
    private float $longitude;
    private int $population;
    private array $sensors = [];
    private array $analytics = [];
    private string $status = 'active';

    public function __construct(
        string $wardCode,
        string $name,
        float $latitude,
        float $longitude,
        int $population = 0
    ) {
        $this->wardCode = $wardCode;
        $this->name = $name;
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->population = $population;
    }

    public function addSensor(IoTSensor $sensor): void
    {
        $this->sensors[$sensor->getId()] = $sensor;
    }

    public function removeSensor(string $sensorId): void
    {
        unset($this->sensors[$sensorId]);
    }

    public function getSensors(): array
    {
        return $this->sensors;
    }

    public function updateAnalytics(array $analytics): void
    {
        $this->analytics = array_merge($this->analytics, $analytics);
    }

    public function getAnalytics(): array
    {
        return $this->analytics;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getWardCode(): string
    {
        return $this->wardCode;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLocation(): array
    {
        return [
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }

    public function getPopulation(): int
    {
        return $this->population;
    }
}

class IoTSensor
{
    private string $id;
    private string $type;
    private string $location;
    private float $latitude;
    private float $longitude;
    private string $status = 'active';
    private ?float $lastReading = null;
    private ?\DateTime $lastUpdated = null;
    private array $readings = [];

    public function __construct(
        string $id,
        string $type,
        string $location,
        float $latitude,
        float $longitude
    ) {
        $this->id = $id;
        $this->type = $type;
        $this->location = $location;
        $this->latitude = $latitude;
        $this->longitude = $longitude;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function updateReading(float $value): void
    {
        $this->lastReading = $value;
        $this->lastUpdated = new \DateTime();
        
        $this->readings[] = [
            'value' => $value,
            'timestamp' => $this->lastUpdated->format('c'),
        ];
        
        if (count($this->readings) > 1000) {
            array_shift($this->readings);
        }
    }

    public function getLastReading(): ?float
    {
        return $this->lastReading;
    }

    public function getReadings(int $limit = 100): array
    {
        return array_slice($this->readings, -$limit);
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getLocation(): array
    {
        return [
            'location' => $this->location,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }
}
