<?php declare(strict_types=1);

namespace App\Tests\Integration;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Health\Entity\HealthProfile;
use App\Health\Service\HealthService;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class HealthIntegrationTest extends TestCase
{
    private HealthService $service;

    protected function setUp(): void
    {
        $this->service = new HealthService();
    }

    public function testFullHealthProfileLifecycle(): void
    {
        $profile = $this->service->createProfile(1);
        $this->assertNotNull($profile->getId());
        
        $this->service->updateProfile($profile->getId(), [
            'height' => 175,
            'weight' => 70,
            'age' => 30,
            'blood_type' => 'A+'
        ]);
        
        $retrieved = $this->service->getProfile($profile->getId());
        $this->assertNotNull($retrieved);
        $this->assertEquals(175, $retrieved->getHeight());
        $this->assertEquals(70, $retrieved->getWeight());
    }

    public function testBMI(): void
    {
        $profile = $this->service->createProfile(1);
        $this->service->updateProfile($profile->getId(), [
            'height' => 170,
            'weight' => 70
        ]);
        
        $bmi = $this->service->calculateBMI($profile->getId());
        $this->assertEquals(24.2, $bmi);
    }

    public function testHealthSummary(): void
    {
        $profile = $this->service->createProfile(1);
        $this->service->updateProfile($profile->getId(), [
            'height' => 170,
            'weight' => 70
        ]);
        $this->service->addAllergy($profile->getId(), 'peanuts');
        $this->service->addCondition($profile->getId(), 'diabetes');
        
        $summary = $this->service->getHealthSummary(1);
        
        $this->assertArrayHasKey('bmi', $summary);
        $this->assertArrayHasKey('bmi_category', $summary);
        $this->assertEquals(1, $summary['allergies_count']);
        $this->assertEquals(1, $summary['conditions_count']);
    }

    public function testAddMedications(): void
    {
        $profile = $this->service->createProfile(1);
        $this->service->addMedication($profile->getId(), 'insulin');
        $this->service->addMedication($profile->getId(), 'metformin');
        
        $retrieved = $this->service->getProfile($profile->getId());
        $this->assertCount(2, $retrieved->getMedications());
    }

    public function testProfileDeletion(): void
    {
        $profile = $this->service->createProfile(1);
        $id = $profile->getId();
        
        $this->service->deleteProfile($id);
        $result = $this->service->getProfile($id);
        
        $this->assertNull($result);
    }

    public function testUserProfileRetrieval(): void
    {
        $profile = $this->service->createProfile(1);
        $found = $this->service->getUserProfile(1);
        
        $this->assertNotNull($found);
        $this->assertEquals(1, $found->getUserId());
    }
}
