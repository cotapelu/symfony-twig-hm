<?php

namespace App\SmartCity\Domain\Service;

use App\SmartCity\Domain\Entity\SmartWard;
use App\SmartCity\Domain\Entity\IoTSensor;
use InvalidArgumentException;

class SmartCityService
{
    private const MAX_WARD_CODE_LENGTH = 20;
    private const MAX_WARD_NAME_LENGTH = 255;
    private const MAX_LOCATION_LENGTH = 500;
    private const MAX_POPULATION = 10000000;
    private const MAX_SENSOR_ID_LENGTH = 50;
    private const MAX_SENSOR_TYPE_LENGTH = 100;
    private const MAX_READINGS_HISTORY = 1000;
    private const ALLOWED_SENSOR_TYPES = ['traffic', 'energy', 'weather', 'air_quality', 'noise', 'water', 'security', 'other'];
    private const ALLOWED_STATUSES = ['active', 'inactive', 'maintenance', 'error'];

    private array $wards = [];
    private array $aiPredictions = [];

    public function createWard(string $wardCode, string $name, float $lat, float $lng, int $population = 0): SmartWard
    {
        $this->validateWardCode($wardCode);
        $this->validateWardName($name);
        $this->validateCoordinates($lat, $lng);
        $this->validatePopulation($population);

        if (isset($this->wards[$wardCode])) {
            throw new InvalidArgumentException('Ward code already exists');
        }

        $ward = new SmartWard($wardCode, $name, $lat, $lng, $population);
        $this->wards[$wardCode] = $ward;
        return $ward;
    }

    public function getWard(string $wardCode): ?SmartWard
    {
        $this->validateWardCode($wardCode);
        return $this->wards[$wardCode] ?? null;
    }

    public function getAllWards(): array
    {
        return $this->wards;
    }

    public function addSensorToWard(string $wardCode, IoTSensor $sensor): bool
    {
        $this->validateWardCode($wardCode);
        $this->validateSensor($sensor);

        $ward = $this->getWard($wardCode);
        if (!$ward) {
            return false;
        }
        $ward->addSensor($sensor);
        return true;
    }

    public function getWardSensors(string $wardCode): array
    {
        $this->validateWardCode($wardCode);
        $ward = $this->getWard($wardCode);
        return $ward ? $ward->getSensors() : [];
    }

    public function getSensorData(string $wardCode, string $sensorId): ?array
    {
        $this->validateWardCode($wardCode);
        $this->validateSensorId($sensorId);

        $sensors = $this->getWardSensors($wardCode);
        $sensor = $sensors[$sensorId] ?? null;
        
        if (!$sensor) {
            return null;
        }
        
        return [
            'id' => $sensor->getId(),
            'type' => $sensor->getType(),
            'location' => $sensor->getLocation(),
            'status' => $sensor->getStatus(),
            'lastReading' => $sensor->getLastReading(),
            'readings' => $sensor->getReadings(100),
        ];
    }

    public function updateSensorReading(string $wardCode, string $sensorId, float $value): bool
    {
        $this->validateWardCode($wardCode);
        $this->validateSensorId($sensorId);
        $this->validateSensorReading($value);

        $sensors = $this->getWardSensors($wardCode);
        $sensor = $sensors[$sensorId] ?? null;
        
        if (!$sensor) {
            return false;
        }
        
        $sensor->updateReading($value);
        return true;
    }

    public function getWardAnalytics(string $wardCode): array
    {
        $this->validateWardCode($wardCode);
        
        $ward = $this->getWard($wardCode);
        if (!$ward) {
            return [];
        }
        
        $sensors = $ward->getSensors();
        $sensorStats = $this->computeSensorStats($sensors);
        
        return [
            'wardCode' => $wardCode,
            'name' => $ward->getName(),
            'population' => $ward->getPopulation(),
            'location' => $ward->getLocation(),
            'sensorCount' => count($sensors),
            'sensors' => $sensorStats,
            'analytics' => $ward->getAnalytics(),
        ];
    }

    private function computeSensorStats(array $sensors): array
    {
        $sensorStats = [];
        foreach ($sensors as $sensor) {
            $readings = $sensor->getReadings(self::MAX_READINGS_HISTORY);
            $values = array_column($readings, 'value');
            
            $sensorStats[$sensor->getId()] = [
                'type' => $sensor->getType(),
                'status' => $sensor->getStatus(),
                'readingCount' => count($readings),
                'avgValue' => count($values) > 0 ? array_sum($values) / count($values) : 0,
                'minValue' => count($values) > 0 ? min($values) : 0,
                'maxValue' => count($values) > 0 ? max($values) : 0,
            ];
        }
        return $sensorStats;
    }

    public function getPredictiveAnalytics(string $wardCode): array
    {
        $this->validateWardCode($wardCode);
        
        $key = "prediction_{$wardCode}";
        if (!isset($this->aiPredictions[$key])) {
            $this->aiPredictions[$key] = $this->buildPredictiveAnalytics($wardCode);
        }
        
        return $this->aiPredictions[$key];
    }

    private function buildPredictiveAnalytics(string $wardCode): array
    {
        $ward = $this->getWard($wardCode);
        $sensors = $ward ? $ward->getSensors() : [];
        
        return [
            'trafficPrediction' => $this->predictTraffic($wardCode, $sensors),
            'energyPrediction' => $this->predictEnergyUsage($wardCode, $sensors),
            'crowdPrediction' => $this->predictCrowdDensity($wardCode, $sensors),
            'incidentRisk' => $this->calculateIncidentRisk($wardCode, $sensors),
        ];
    }

    private function predictTraffic(string $wardCode, array $sensors): array
    {
        $trafficSensors = array_filter($sensors, fn($s) => $s->getType() === 'traffic');
        
        if (empty($trafficSensors)) {
            return [
                'peakHours' => ['07:00-09:00', '17:00-19:00'],
                'congestionLevel' => 'unknown',
                'recommendedActions' => ['Install traffic sensors for accurate predictions'],
            ];
        }

        $avgReading = 0;
        $count = 0;
        foreach ($trafficSensors as $sensor) {
            $readings = $sensor->getReadings(100);
            foreach ($readings as $r) {
                $avgReading += $r['value'];
                $count++;
            }
        }
        $avgReading = $count > 0 ? $avgReading / $count : 0;

        return [
            'peakHours' => ['07:00-09:00', '17:00-19:00'],
            'congestionLevel' => $avgReading > 70 ? 'high' : ($avgReading > 40 ? 'medium' : 'low'),
            'recommendedActions' => $avgReading > 70 ? ['Adjust traffic lights', 'Deploy traffic officers'] : [],
        ];
    }

    private function predictEnergyUsage(string $wardCode, array $sensors): array
    {
        $energySensors = array_filter($sensors, fn($s) => $s->getType() === 'energy');
        
        if (empty($energySensors)) {
            return [
                'currentUsage' => 0,
                'predictedPeak' => 0,
                'optimizationSuggestions' => ['Install energy sensors for accurate predictions'],
            ];
        }

        $avgUsage = $this->computeAverageReading($energySensors);
        $predictedPeak = $avgUsage * 1.3;

        return [
            'currentUsage' => round($avgUsage, 2),
            'predictedPeak' => round($predictedPeak, 2),
            'optimizationSuggestions' => $avgUsage > 1500 ? [
                'Reduce street light brightness 22:00-05:00',
                'Schedule heavy equipment usage off-peak',
            ] : [],
        ];
    }

    private function computeAverageReading(array $sensors): float
    {
        $total = 0;
        $count = 0;
        foreach ($sensors as $sensor) {
            $readings = $sensor->getReadings(100);
            foreach ($readings as $r) {
                $total += $r['value'];
                $count++;
            }
        }
        return $count > 0 ? $total / $count : 0;
    }

    private function predictCrowdDensity(string $wardCode, array $sensors): array
    {
        return [
            'currentDensity' => 'unknown',
            'predictedHighDensity' => ['08:00-10:00', '18:00-20:00'],
            'riskLevel' => 'unknown',
        ];
    }

    private function calculateIncidentRisk(string $wardCode, array $sensors): array
    {
        return [
            'overallRisk' => 'low',
            'factors' => [
                'weather' => 'favorable',
                'traffic' => 'normal',
                'timeOfDay' => 'low-risk',
            ],
            'recommendedMonitoring' => ['park', 'market', 'school-zone'],
        ];
    }

    public function getMapData(string $wardCode): array
    {
        $this->validateWardCode($wardCode);
        
        $ward = $this->getWard($wardCode);
        if (!$ward) {
            return [];
        }
        
        $sensors = $ward->getSensors();
        $sensorMarkers = $this->buildSensorMarkers($sensors);
        
        return [
            'center' => $ward->getLocation(),
            'zoom' => 15,
            'markers' => $sensorMarkers,
            'wardInfo' => [
                'name' => $ward->getName(),
                'population' => $ward->getPopulation(),
            ],
        ];
    }

    private function buildSensorMarkers(array $sensors): array
    {
        $markers = [];
        foreach ($sensors as $sensor) {
            $location = $sensor->getLocation();
            $markers[] = [
                'id' => $sensor->getId(),
                'type' => $sensor->getType(),
                'lat' => $location['latitude'],
                'lng' => $location['longitude'],
                'status' => $sensor->getStatus(),
                'lastReading' => $sensor->getLastReading(),
            ];
        }
        return $markers;
    }

    private function validateWardCode(string $code): void
    {
        if (empty($code)) {
            throw new InvalidArgumentException('Ward code is required');
        }
        if (strlen($code) > self::MAX_WARD_CODE_LENGTH) {
            throw new InvalidArgumentException('Ward code too long (max ' . self::MAX_WARD_CODE_LENGTH . ' chars)');
        }
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $code)) {
            throw new InvalidArgumentException('Invalid ward code format');
        }
    }

    private function validateWardName(string $name): void
    {
        if (empty($name)) {
            throw new InvalidArgumentException('Ward name is required');
        }
        if (strlen($name) > self::MAX_WARD_NAME_LENGTH) {
            throw new InvalidArgumentException('Ward name too long (max ' . self::MAX_WARD_NAME_LENGTH . ' chars)');
        }
    }

    private function validateCoordinates(float $lat, float $lng): void
    {
        if ($lat < -90 || $lat > 90) {
            throw new InvalidArgumentException('Invalid latitude (must be between -90 and 90)');
        }
        if ($lng < -180 || $lng > 180) {
            throw new InvalidArgumentException('Invalid longitude (must be between -180 and 180)');
        }
    }

    private function validatePopulation(int $population): void
    {
        if ($population < 0) {
            throw new InvalidArgumentException('Population cannot be negative');
        }
        if ($population > self::MAX_POPULATION) {
            throw new InvalidArgumentException('Population exceeds maximum allowed');
        }
    }

    private function validateSensor(IoTSensor $sensor): void
    {
        $this->validateSensorId($sensor->getId());
        $this->validateSensorType($sensor->getType());
        
        $location = $sensor->getLocation();
        if (isset($location['latitude']) && isset($location['longitude'])) {
            $this->validateCoordinates($location['latitude'], $location['longitude']);
        }
    }

    private function validateSensorId(string $id): void
    {
        if (empty($id)) {
            throw new InvalidArgumentException('Sensor ID is required');
        }
        if (strlen($id) > self::MAX_SENSOR_ID_LENGTH) {
            throw new InvalidArgumentException('Sensor ID too long');
        }
    }

    private function validateSensorType(string $type): void
    {
        if (empty($type)) {
            throw new InvalidArgumentException('Sensor type is required');
        }
        if (!in_array($type, self::ALLOWED_SENSOR_TYPES)) {
            throw new InvalidArgumentException('Invalid sensor type');
        }
    }

    private function validateSensorReading(float $value): void
    {
        if (!is_finite($value)) {
            throw new InvalidArgumentException('Invalid sensor reading value');
        }
    }

    public function getMetrics(): array
    {
        return [
            'airQuality' => [
                'pm25' => 35,
                'pm10' => 50,
                'aqi' => 42,
            ],
            'traffic' => [
                'congestionLevel' => 'moderate',
                'averageSpeed' => 35,
            ],
            'energyUsage' => [
                'total' => 1200,
                'bySource' => [
                    'solar' => 300,
                    'wind' => 200,
                    'grid' => 700,
                ],
            ],
        ];
    }

    public function getIncidents(): array
    {
        return [
            [
                'id' => 1,
                'type' => 'traffic',
                'description' => 'Minor accident on Main St',
                'status' => 'resolved',
                'timestamp' => (new \DateTime('-1 hour'))->format(\DateTimeInterface::ISO8601),
            ],
            [
                'id' => 2,
                'type' => 'environment',
                'description' => 'Air quality index above normal',
                'status' => 'active',
                'timestamp' => (new \DateTime())->format(\DateTimeInterface::ISO8601),
            ],
        ];
    }

    public function getSensors(): array
    {
        return [
            [
                'id' => 'sensor-001',
                'type' => 'temperature',
                'value' => 22.5,
                'unit' => '°C',
                'location' => 'Station A',
            ],
            [
                'id' => 'sensor-002',
                'type' => 'humidity',
                'value' => 65,
                'unit' => '%',
                'location' => 'Station B',
            ],
        ];
    }
}
