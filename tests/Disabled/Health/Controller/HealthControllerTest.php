<?php declare(strict_types=1);

namespace App\Tests\Unit\Health\Controller;

use App\Health\Controller\HealthController;
use App\Health\Entity\HealthProfile;
use App\Health\Service\HealthService;
use PHPUnit\Framework\TestCase;

class HealthControllerTest extends TestCase
{
    private HealthController $controller;
    private HealthService $service;

    protected function setUp(): void
    {
        $this->service = $this->createMock(HealthService::class);
        $reflection = new \ReflectionClass(HealthController::class);
        $controller = $reflection->newInstanceWithoutConstructor();
        $reflectionProperty = $reflection->getProperty('service');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($controller, $this->service);
        $this->controller = $controller;
    }

    private function createProfile(int $userId = 1): HealthProfile
    {
        $profile = new HealthProfile($userId);
        $profile->setId(1);
        return $profile;
    }

    public function testCreateProfileSuccess(): void
    {
        $profile = $this->createProfile();
        
        $this->service->expects($this->once())
            ->method('createProfile')
            ->with(123)
            ->willReturn($profile);

        $request = ['user_id' => 123];

        $result = $this->controller->createProfile($request);

        $this->assertEquals(201, $result['status']);
        $this->assertArrayHasKey('data', $result);
    }

    public function testGetProfileFound(): void
    {
        $profile = $this->createProfile();
        
        $this->service->expects($this->once())
            ->method('getProfile')
            ->with(1)
            ->willReturn($profile);

        $request = ['params' => ['id' => 1]];

        $result = $this->controller->getProfile($request);

        $this->assertEquals(200, $result['status']);
    }

    public function testGetProfileNotFound(): void
    {
        $this->service->expects($this->once())
            ->method('getProfile')
            ->with(999)
            ->willReturn(null);

        $request = ['params' => ['id' => 999]];

        $result = $this->controller->getProfile($request);

        $this->assertEquals(404, $result['status']);
    }

    public function testGetUserProfileFound(): void
    {
        $profile = $this->createProfile();
        
        $this->service->expects($this->once())
            ->method('getUserProfile')
            ->with(123)
            ->willReturn($profile);

        $request = ['params' => ['user_id' => 123]];

        $result = $this->controller->getUserProfile($request);

        $this->assertEquals(200, $result['status']);
    }

    public function testGetUserProfileNotFound(): void
    {
        $this->service->expects($this->once())
            ->method('getUserProfile')
            ->with(999)
            ->willReturn(null);

        $request = ['params' => ['user_id' => 999]];

        $result = $this->controller->getUserProfile($request);

        $this->assertEquals(404, $result['status']);
    }

    public function testUpdateProfileSuccess(): void
    {
        $profile = $this->createProfile();
        
        $this->service->expects($this->once())
            ->method('updateProfile')
            ->with(1, ['height' => 175])
            ->willReturn($profile);

        $request = [
            'params' => ['id' => 1],
            'body' => ['height' => 175]
        ];

        $result = $this->controller->updateProfile($request);

        $this->assertEquals(200, $result['status']);
    }

    public function testUpdateProfileNotFound(): void
    {
        $this->service->expects($this->once())
            ->method('updateProfile')
            ->with(999, [])
            ->willReturn(null);

        $request = [
            'params' => ['id' => 999],
            'body' => []
        ];

        $result = $this->controller->updateProfile($request);

        $this->assertEquals(404, $result['status']);
    }

    public function testAddAllergySuccess(): void
    {
        $profile = $this->createProfile();
        
        $this->service->expects($this->once())
            ->method('addAllergy')
            ->with(1, 'peanuts')
            ->willReturn($profile);

        $request = [
            'params' => ['id' => 1],
            'body' => ['allergy' => 'peanuts']
        ];

        $result = $this->controller->addAllergy($request);

        $this->assertEquals(200, $result['status']);
    }

    public function testAddConditionSuccess(): void
    {
        $profile = $this->createProfile();
        
        $this->service->expects($this->once())
            ->method('addCondition')
            ->with(1, 'diabetes')
            ->willReturn($profile);

        $request = [
            'params' => ['id' => 1],
            'body' => ['condition' => 'diabetes']
        ];

        $result = $this->controller->addCondition($request);

        $this->assertEquals(200, $result['status']);
    }

    public function testAddMedicationSuccess(): void
    {
        $profile = $this->createProfile();
        
        $this->service->expects($this->once())
            ->method('addMedication')
            ->with(1, 'insulin')
            ->willReturn($profile);

        $request = [
            'params' => ['id' => 1],
            'body' => ['medication' => 'insulin']
        ];

        $result = $this->controller->addMedication($request);

        $this->assertEquals(200, $result['status']);
    }

    public function testCalculateBMISuccess(): void
    {
        $this->service->expects($this->once())
            ->method('calculateBMI')
            ->with(1)
            ->willReturn(24.2);

        $request = ['params' => ['id' => 1]];

        $result = $this->controller->calculateBMI($request);

        $this->assertEquals(200, $result['status']);
        $this->assertEquals(24.2, $result['data']['bmi']);
    }

    public function testCalculateBMINotFound(): void
    {
        $this->service->expects($this->once())
            ->method('calculateBMI')
            ->with(999)
            ->willReturn(null);

        $request = ['params' => ['id' => 999]];

        $result = $this->controller->calculateBMI($request);

        $this->assertEquals(404, $result['status']);
    }

    public function testGetHealthSummarySuccess(): void
    {
        $profile = $this->createProfile();
        
        $this->service->expects($this->once())
            ->method('getHealthSummary')
            ->with(123)
            ->willReturn([
                'profile' => $profile,
                'bmi' => 24.2,
                'bmi_category' => 'Normal',
                'allergies_count' => 1,
                'conditions_count' => 0,
                'medications_count' => 0
            ]);

        $request = ['params' => ['user_id' => 123]];

        $result = $this->controller->getHealthSummary($request);

        $this->assertEquals(200, $result['status']);
    }

    public function testGetHealthSummaryNotFound(): void
    {
        $this->service->expects($this->once())
            ->method('getHealthSummary')
            ->with(999)
            ->willReturn(['error' => 'Profile not found']);

        $request = ['params' => ['user_id' => 999]];

        $result = $this->controller->getHealthSummary($request);

        $this->assertEquals(404, $result['status']);
    }
}
