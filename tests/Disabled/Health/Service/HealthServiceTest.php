<?php declare(strict_types=1);

namespace App\Tests\Unit\Health\Service;

use App\Health\Entity\HealthProfile;
use App\Health\Service\HealthService;
use PHPUnit\Framework\TestCase;

class HealthServiceTest extends TestCase
{
    private HealthService $service;

    protected function setUp(): void
    {
        $this->service = new HealthService();
    }

    public function testCreateProfile(): void
    {
        $profile = $this->service->createProfile(123);
        
        $this->assertInstanceOf(HealthProfile::class, $profile);
        $this->assertEquals(123, $profile->getUserId());
        $this->assertNotNull($profile->getId());
    }

    public function testGetProfile(): void
    {
        $created = $this->service->createProfile(123);
        
        $result = $this->service->getProfile($created->getId());
        
        $this->assertSame($created, $result);
    }

    public function testGetProfileNotFound(): void
    {
        $result = $this->service->getProfile(999);
        
        $this->assertNull($result);
    }

    public function testGetUserProfile(): void
    {
        $created = $this->service->createProfile(123);
        
        $result = $this->service->getUserProfile(123);
        
        $this->assertSame($created, $result);
    }

    public function testGetUserProfileNotFound(): void
    {
        $result = $this->service->getUserProfile(999);
        
        $this->assertNull($result);
    }

    public function testUpdateProfile(): void
    {
        $profile = $this->service->createProfile(123);
        
        $result = $this->service->updateProfile($profile->getId(), [
            'height' => 175,
            'weight' => 70,
            'age' => 30,
            'blood_type' => 'A+',
            'gender' => 'male'
        ]);
        
        $this->assertNotNull($result);
        $this->assertEquals(175, $result->getHeight());
        $this->assertEquals(70, $result->getWeight());
        $this->assertEquals(30, $result->getAge());
        $this->assertEquals('A+', $result->getBloodType());
        $this->assertEquals('male', $result->getGender());
    }

    public function testUpdateProfileNotFound(): void
    {
        $result = $this->service->updateProfile(999, ['height' => 175]);
        
        $this->assertNull($result);
    }

    public function testAddAllergy(): void
    {
        $profile = $this->service->createProfile(123);
        
        $result = $this->service->addAllergy($profile->getId(), 'peanuts');
        
        $this->assertNotNull($result);
        $this->assertCount(1, $result->getAllergies());
    }

    public function testAddCondition(): void
    {
        $profile = $this->service->createProfile(123);
        
        $result = $this->service->addCondition($profile->getId(), 'diabetes');
        
        $this->assertNotNull($result);
        $this->assertCount(1, $result->getConditions());
    }

    public function testAddMedication(): void
    {
        $profile = $this->service->createProfile(123);
        
        $result = $this->service->addMedication($profile->getId(), 'insulin');
        
        $this->assertNotNull($result);
        $this->assertCount(1, $result->getMedications());
    }

    public function testCalculateBMI(): void
    {
        $profile = $this->service->createProfile(123);
        $this->service->updateProfile($profile->getId(), [
            'height' => 170,
            'weight' => 70
        ]);
        
        $bmi = $this->service->calculateBMI($profile->getId());
        
        $this->assertEquals(24.2, $bmi);
    }

    public function testCalculateBMINotFound(): void
    {
        $bmi = $this->service->calculateBMI(999);
        
        $this->assertNull($bmi);
    }

    public function testGetHealthSummary(): void
    {
        $profile = $this->service->createProfile(123);
        $this->service->updateProfile($profile->getId(), [
            'height' => 170,
            'weight' => 70
        ]);
        $this->service->addAllergy($profile->getId(), 'peanuts');
        
        $summary = $this->service->getHealthSummary(123);
        
        $this->assertArrayHasKey('profile', $summary);
        $this->assertArrayHasKey('bmi', $summary);
        $this->assertArrayHasKey('bmi_category', $summary);
        $this->assertEquals(24.2, $summary['bmi']);
        $this->assertEquals('Normal', $summary['bmi_category']);
    }

    public function testGetHealthSummaryNotFound(): void
    {
        $summary = $this->service->getHealthSummary(999);
        
        $this->assertEquals(['error' => 'Profile not found'], $summary);
    }

    public function testDeleteProfile(): void
    {
        $profile = $this->service->createProfile(123);
        
        $result = $this->service->deleteProfile($profile->getId());
        
        $this->assertTrue($result);
        $this->assertNull($this->service->getProfile($profile->getId()));
    }

    public function testDeleteProfileNotFound(): void
    {
        $result = $this->service->deleteProfile(999);
        
        $this->assertFalse($result);
    }

    public function testBMICategories(): void
    {
        $profile = $this->service->createProfile(123);
        
        $this->service->updateProfile($profile->getId(), ['height' => 170, 'weight' => 50]);
        $summary = $this->service->getHealthSummary(123);
        $this->assertEquals('Underweight', $summary['bmi_category']);
        
        $this->service->updateProfile($profile->getId(), ['weight' => 70]);
        $summary = $this->service->getHealthSummary(123);
        $this->assertEquals('Normal', $summary['bmi_category']);
        
        $this->service->updateProfile($profile->getId(), ['weight' => 85]);
        $summary = $this->service->getHealthSummary(123);
        $this->assertEquals('Overweight', $summary['bmi_category']);
        
        $this->service->updateProfile($profile->getId(), ['weight' => 100]);
        $summary = $this->service->getHealthSummary(123);
        $this->assertEquals('Obese', $summary['bmi_category']);
    }
}
