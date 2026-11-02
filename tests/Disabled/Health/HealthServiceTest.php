<?php

declare(strict_types=1);

namespace App\Tests\Unit\Health;

use App\Health\Service\HealthService;
use App\Health\Entity\HealthProfile;
use PHPUnit\Framework\TestCase;

class HealthServiceTest extends TestCase
{
    private HealthService $service;

    protected function setUp(): void
    {
        $this->service = new HealthService();
    }

    public function testCreateProfileReturnsHealthProfile(): void
    {
        $profile = $this->service->createProfile(123);
        $this->assertInstanceOf(HealthProfile::class, $profile);
        $this->assertSame(123, $profile->getUserId());
    }

    public function testGetProfileReturnsNullWhenNotFound(): void
    {
        $result = $this->service->getProfile(999);
        $this->assertNull($result);
    }

    public function testGetProfileReturnsProfileAfterCreate(): void
    {
        $profile = $this->service->createProfile(1);
        $found = $this->service->getProfile($profile->getId());
        $this->assertSame($profile, $found);
    }

    public function testGetUserProfileReturnsNullWhenNotFound(): void
    {
        $this->assertNull($this->service->getUserProfile(999));
    }

    public function testGetUserProfileFindsProfileByUserId(): void
    {
        $profile = $this->service->createProfile(42);
        $found = $this->service->getUserProfile(42);
        $this->assertSame($profile, $found);
    }

    public function testUpdateProfileModifiesFields(): void
    {
        $profile = $this->service->createProfile(1);
        $id = $profile->getId();

        $updated = $this->service->updateProfile($id, [
            'height' => 170.5,
            'weight' => 65.0,
            'age' => 30,
            'blood_type' => 'A+',
            'gender' => 'female',
        ]);

        $this->assertNotNull($updated);
        $this->assertEquals(170.5, $updated->getHeight());
        $this->assertEquals(65.0, $updated->getWeight());
        $this->assertEquals(30, $updated->getAge());
        $this->assertEquals('A+', $updated->getBloodType());
        $this->assertEquals('female', $updated->getGender());
    }

    public function testUpdateProfileReturnsNullIfNotFound(): void
    {
        $result = $this->service->updateProfile(999, ['height' => 180]);
        $this->assertNull($result);
    }

    public function testAddAllergyAddsToProfile(): void
    {
        $profile = $this->service->createProfile(1);
        $id = $profile->getId();

        $updated = $this->service->addAllergy($id, 'Peanuts');
        $this->assertContains('Peanuts', $updated->getAllergies());
    }

    public function testAddConditionAddsToProfile(): void
    {
        $profile = $this->service->createProfile(1);
        $id = $profile->getId();

        $updated = $this->service->addCondition($id, 'Asthma');
        $this->assertContains('Asthma', $updated->getConditions());
    }

    public function testAddMedicationAddsToProfile(): void
    {
        $profile = $this->service->createProfile(1);
        $id = $profile->getId();

        $updated = $this->service->addMedication($id, 'Aspirin');
        $this->assertContains('Aspirin', $updated->getMedications());
    }

    public function testCalculateBMIReturnsNullIfProfileNotFound(): void
    {
        $this->assertNull($this->service->calculateBMI(999));
    }

    public function testCalculateBMICalculatesCorrectly(): void
    {
        $profile = $this->service->createProfile(1);
        $id = $profile->getId();
        // Set height in cm, weight in kg: BMI = weight / ((height/100)^2)
        $this->service->updateProfile($id, ['height' => 175.0, 'weight' => 70]);
        $bmi = $this->service->calculateBMI($id);
        $expected = 70 / ((175/100) * (175/100)); // approx 22.86
        $this->assertEqualsWithDelta($expected, $bmi, 0.1);
    }

    public function testGetHealthSummaryIncludesExpectedKeys(): void
    {
        $profile = $this->service->createProfile(1);
        $id = $profile->getId();
        $this->service->addAllergy($id, 'Pollens');
        $this->service->addCondition($id, 'Diabetes');
        $this->service->addMedication($id, 'Insulin');

        $summary = $this->service->getHealthSummary(1);
        $this->assertArrayHasKey('profile', $summary);
        $this->assertArrayHasKey('bmi', $summary);
        $this->assertArrayHasKey('bmi_category', $summary);
        $this->assertArrayHasKey('allergies_count', $summary);
        $this->assertArrayHasKey('conditions_count', $summary);
        $this->assertArrayHasKey('medications_count', $summary);
        $this->assertCount(1, $summary['profile']->getAllergies());
        $this->assertCount(1, $summary['profile']->getConditions());
        $this->assertCount(1, $summary['profile']->getMedications());
    }

    public function testGetHealthSummaryReturnsErrorIfNotFound(): void
    {
        $summary = $this->service->getHealthSummary(999);
        $this->assertArrayHasKey('error', $summary);
    }

    public function testDeleteProfileRemovesProfile(): void
    {
        $profile = $this->service->createProfile(1);
        $id = $profile->getId();
        $this->assertTrue($this->service->deleteProfile($id));
        $this->assertNull($this->service->getProfile($id));
    }

    public function testDeleteProfileReturnsFalseIfNotFound(): void
    {
        $this->assertFalse($this->service->deleteProfile(999));
    }
}
