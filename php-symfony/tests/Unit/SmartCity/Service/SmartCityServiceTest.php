<?php

declare(strict_types=1);

namespace App\Tests\Unit\SmartCity\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use PHPUnit\Framework\TestCase;
use App\SmartCity\Domain\Service\SmartCityService;
use App\SmartCity\Domain\Entity\IoTSensor;

#[AllowMockObjectsWithoutExpectations]
class SmartCityServiceTest extends TestCase
{
    private SmartCityService $service;

    protected function setUp(): void
    {
        $this->service = new SmartCityService();
    }

    // ================= Ward Management =================

    public function testCreateWardSuccess(): void
    {
        $ward = $this->service->createWard('WARD-001', 'Central', 10.0, 20.0, 5000);
        $this->assertSame('WARD-001', $ward->getWardCode());
        $this->assertSame('Central', $ward->getName());
        $this->assertSame(5000, $ward->getPopulation());
        $this->assertSame(['latitude' => 10.0, 'longitude' => 20.0], $ward->getLocation());
    }

    public function testCreateWardThrowsIfCodeInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Ward code is required');
        $this->service->createWard('', 'Name', 0, 0, 0);
    }

    public function testCreateWardThrowsIfCodeTooLong(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Ward code too long');
        $this->service->createWard(str_repeat('a', 21), 'Name', 0, 0, 0);
    }

    public function testCreateWardThrowsIfNameInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Ward name is required');
        $this->service->createWard('W01', '', 0, 0, 0);
    }

    public function testCreateWardThrowsIfLatitudeOutOfRange(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid latitude');
        $this->service->createWard('W01', 'Name', 100, 0, 0);
    }

    public function testCreateWardThrowsIfLongitudeOutOfRange(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid longitude');
        $this->service->createWard('W01', 'Name', 0, 200, 0);
    }

    public function testCreateWardThrowsIfPopulationNegative(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Population cannot be negative');
        $this->service->createWard('W01', 'Name', 0, 0, -1);
    }

    public function testCreateWardThrowsIfDuplicateCode(): void
    {
        $this->service->createWard('WARD-001', 'Central', 10.0, 20.0);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Ward code already exists');
        $this->service->createWard('WARD-001', 'Other', 30.0, 40.0);
    }

    public function testGetWardReturnsNullIfNotFound(): void
    {
        $result = $this->service->getWard('NONEXISTENT');
        $this->assertNull($result);
    }

    public function testGetWardReturnsWardIfExists(): void
    {
        $ward = $this->service->createWard('W001', 'Test Ward', 1.0, 2.0);
        $found = $this->service->getWard('W001');
        $this->assertSame($ward, $found);
    }

    public function testGetAllWards(): void
    {
        $this->service->createWard('W1', 'Ward 1', 1, 1);
        $this->service->createWard('W2', 'Ward 2', 2, 2);
        $all = $this->service->getAllWards();
        $this->assertCount(2, $all);
    }

    // ================= Sensor Management =================

    public function testAddSensorToWardSuccess(): void
    {
        $ward = $this->service->createWard('W-SEN', 'Sensor Ward', 0, 0);
        $sensor = new IoTSensor('S1', 'weather', ' Main St', 1.0, 2.0);

        $result = $this->service->addSensorToWard('W-SEN', $sensor);
        $this->assertTrue($result);
        $this->assertCount(1, $ward->getSensors());
        $this->assertArrayHasKey('S1', $ward->getSensors());
    }

    public function testAddSensorToWardFailsIfWardNotFound(): void
    {
        $sensor = new IoTSensor('S1', 'weather', 'Main St', 1.0, 2.0);
        $result = $this->service->addSensorToWard('NONEXISTENT', $sensor);
        $this->assertFalse($result);
    }

    public function testValidateSensorThrowsIfInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Sensor ID is required');
        $service = new \ReflectionClass($this->service);
        $method = $service->getMethod('validateSensor');
        $method->setAccessible(true);
        $sensor = new IoTSensor('', 'type', 'loc', 0, 0);
        $method->invoke($this->service, $sensor);
    }

    public function testGetWardSensors(): void
    {
        $ward = $this->service->createWard('W-SEN2', 'Sensor Ward 2', 0, 0);
        $sensor1 = new IoTSensor('S1', 'weather', 'Main St', 1.0, 2.0);
        $sensor2 = new IoTSensor('S2', 'air_quality', '2nd St', 3.0, 4.0);
        $this->service->addSensorToWard('W-SEN2', $sensor1);
        $this->service->addSensorToWard('W-SEN2', $sensor2);

        $sensors = $this->service->getWardSensors('W-SEN2');
        $this->assertCount(2, $sensors);
    }

    public function testGetSensorData(): void
    {
        $ward = $this->service->createWard('W-SDATA', 'Sensor Data Ward', 0, 0);
        $sensor = new IoTSensor('S1', 'weather', 'Main St', 1.0, 2.0);
        $sensor->updateReading(23.5);
        $this->service->addSensorToWard('W-SDATA', $sensor);

        $data = $this->service->getSensorData('W-SDATA', 'S1');
        $this->assertNotNull($data);
        $this->assertSame('S1', $data['id']);
        $this->assertSame(23.5, $data['lastReading']);
    }

    public function testGetSensorDataReturnsNullIfNotFound(): void
    {
        $ward = $this->service->createWard('W-NOSENSOR', 'NoSensor Ward', 0, 0);
        $data = $this->service->getSensorData('W-NOSENSOR', 'NONEXISTENT');
        $this->assertNull($data);
    }

    public function testUpdateSensorReading(): void
    {
        $ward = $this->service->createWard('W-UPDATE', 'Update Ward', 0, 0);
        $sensor = new IoTSensor('S1', 'weather', 'Main St', 1.0, 2.0);
        $this->service->addSensorToWard('W-UPDATE', $sensor);

        $result = $this->service->updateSensorReading('W-UPDATE', 'S1', 42.0);
        $this->assertTrue($result);
        $this->assertSame(42.0, $sensor->getLastReading());
    }

    public function testUpdateSensorReadingReturnsFalseIfWardNotFound(): void
    {
        $sensor = new IoTSensor('S1', 'weather', 'Main St', 1.0, 2.0);
        $result = $this->service->updateSensorReading('NONEXISTENT', 'S1', 42.0);
        $this->assertFalse($result);
    }

    // ================= Analytics =================

    public function testGetWardAnalytics(): void
    {
        $ward = $this->service->createWard('W-ANALYTICS', 'Analytics Ward', 0, 0, 1000);
        $sensor = new IoTSensor('S1', 'weather', 'Main St', 1.0, 2.0);
        $sensor->updateReading(25.0);
        $this->service->addSensorToWard('W-ANALYTICS', $sensor);

        $analytics = $this->service->getWardAnalytics('W-ANALYTICS');
        $this->assertSame('W-ANALYTICS', $analytics['wardCode']);
        $this->assertSame('Analytics Ward', $analytics['name']);
        $this->assertSame(1000, $analytics['population']);
        $this->assertCount(1, $analytics['sensors']);
        $this->assertArrayHasKey('S1', $analytics['sensors']);
    }

    public function testGetWardAnalyticsEmptyIfWardNotFound(): void
    {
        $analytics = $this->service->getWardAnalytics('NONEXISTENT');
        $this->assertSame([], $analytics);
    }

    public function testGetPredictiveAnalytics(): void
    {
        $ward = $this->service->createWard('W-PRED', 'Predictive Ward', 0, 0);
        $sensor = new IoTSensor('traffic1', 'traffic', 'Main', 0, 0);
        $sensor->updateReading(80.0);
        $this->service->addSensorToWard('W-PRED', $sensor);

        $predictions = $this->service->getPredictiveAnalytics('W-PRED');
        $this->assertArrayHasKey('trafficPrediction', $predictions);
        $this->assertArrayHasKey('energyPrediction', $predictions);
        $this->assertArrayHasKey('crowdPrediction', $predictions);
        $this->assertArrayHasKey('incidentRisk', $predictions);
    }

    public function testGetMapData(): void
    {
        $ward = $this->service->createWard('W-MAP', 'Map Ward', 5.0, 10.0);
        $sensor = new IoTSensor('S1', 'weather', 'Loc', 5.1, 10.1);
        $sensor->updateReading(22.0);
        $this->service->addSensorToWard('W-MAP', $sensor);

        $map = $this->service->getMapData('W-MAP');
        $this->assertSame(['latitude' => 5.0, 'longitude' => 10.0], $map['center']);
        $this->assertCount(1, $map['markers']);
        $this->assertSame('S1', $map['markers'][0]['id']);
    }

    public function testGetMapDataEmptyIfWardNotFound(): void
    {
        $map = $this->service->getMapData('NONEXISTENT');
        $this->assertSame([], $map);
    }
}
