<?php declare(strict_types=1);

namespace App\Tests\Unit\Health\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Health\Service\HealthService;
use App\Health\Entity\HealthProfile;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class HealthServiceTest extends TestCase
{
    private HealthService $service;

    protected function setUp(): void
    {
        $this->service = new HealthService();
    }

    public function testCreateProfileCreatesNewProfileWithIncrementalId(): void
    {
        $profile1 = $this->service->createProfile(100);
        $this->assertInstanceOf(HealthProfile::class, $profile1);
        $this->assertSame(100, $profile1->getUserId());
        $this->assertSame(1, $profile1->getId());

        $profile2 = $this->service->createProfile(101);
        $this->assertSame(2, $profile2->getId());
    }

    public function testGetProfileReturnsNullWhenNotFound(): void
    {
        $this->assertNull($this->service->getProfile(999));
    }

    public function testGetProfileReturnsExistingProfile(): void
    {
        $profile = $this->service->createProfile(1);
        $found = $this->service->getProfile($profile->getId());
        $this->assertSame($profile, $found);
    }

    public function testGetUserProfileReturnsNullWhenNotFound(): void
    {
        $this->assertNull($this->service->getUserProfile(999));
    }

    public function testGetUserProfileReturnsProfileForUserId(): void
    {
        $profile = $this->service->createProfile(42);
        $found = $this->service->getUserProfile(42);
        $this->assertSame($profile, $found);
    }

    public function testUpdateProfileUpdatesProvidedFields(): void
    {
        $profile = $this->service->createProfile(1);
        $updated = $this->service->updateProfile($profile->getId(), [
            'height' => 180.5,
            'weight' => 75.0,
            'age' => 30,
            'blood_type' => 'A+',
            'gender' => 'male',
        ]);

        $this->assertSame($profile, $updated);
        $this->assertSame(180.5, $profile->getHeight());
        $this->assertSame(75.0, $profile->getWeight());
        $this->assertSame(30, $profile->getAge());
        $this->assertSame('A+', $profile->getBloodType());
        $this->assertSame('male', $profile->getGender());
    }

    public function testUpdateProfileIgnoresInvalidFields(): void
    {
        $profile = $this->service->createProfile(1);
        $this->service->updateProfile($profile->getId(), [
            'invalid_field' => 'value',
            'height' => 170,
        ]);
        $this->assertSame(170.0, $profile->getHeight()); // valid field updated
    }

    public function testUpdateProfileReturnsNullWhenProfileNotFound(): void
    {
        $result = $this->service->updateProfile(999, ['height' => 180]);
        $this->assertNull($result);
    }

    public function testAddAllergyAddsToProfile(): void
    {
        $profile = $this->service->createProfile(1);
        $result = $this->service->addAllergy($profile->getId(), 'Peanuts');
        $this->assertSame($profile, $result);
        $this->assertSame(['Peanuts'], $profile->getAllergies());
    }

    public function testAddAllergyReturnsNullWhenProfileNotFound(): void
    {
        $this->assertNull($this->service->addAllergy(999, 'Peanuts'));
    }

    public function testAddConditionAddsToProfile(): void
    {
        $profile = $this->service->createProfile(1);
        $result = $this->service->addCondition($profile->getId(), 'Diabetes');
        $this->assertSame($profile, $result);
        $this->assertSame(['Diabetes'], $profile->getConditions());
    }

    public function testAddMedicationAddsToProfile(): void
    {
        $profile = $this->service->createProfile(1);
        $result = $this->service->addMedication($profile->getId(), 'Aspirin');
        $this->assertSame($profile, $result);
        $this->assertSame(['Aspirin'], $profile->getMedications());
    }

    public function testCalculateBMIReturnsNullWhenProfileNotFound(): void
    {
        $this->assertNull($this->service->calculateBMI(999));
    }

    public function testCalculateBMIReturnsNullWhenNoHeightOrWeight(): void
    {
        $profile = $this->service->createProfile(1);
        $this->assertNull($this->service->calculateBMI($profile->getId()));
    }

    public function testCalculateBMIReturnsValidValue(): void
    {
        $profile = $this->service->createProfile(1);
        // Height in cm, weight in kg. Set height=180, weight=80 -> BMI = weight(kg)/(height/100)^2 = 80/(1.8^2)=24.69, rounded to 1 decimal = 24.7
        $this->service->updateProfile($profile->getId(), [
            'height' => 180.0,
            'weight' => 80.0,
        ]);
        $bmi = $this->service->calculateBMI($profile->getId());
        $this->assertIsFloat($bmi);
        $this->assertSame(24.7, $bmi);
    }

    public function testGetHealthSummaryReturnsErrorWhenProfileNotFound(): void
    {
        $summary = $this->service->getHealthSummary(999);
        $this->assertArrayHasKey('error', $summary);
        $this->assertSame('Profile not found', $summary['error']);
    }

    public function testGetHealthSummaryReturnsSummaryWithAllCounts(): void
    {
        $profile = $this->service->createProfile(1);
        $this->service->updateProfile($profile->getId(), [
            'height' => 170,
            'weight' => 65,
        ]);
        $this->service->addAllergy($profile->getId(), 'Pollen');
        $this->service->addAllergy($profile->getId(), 'Dust');
        $this->service->addCondition($profile->getId(), 'Asthma');
        $this->service->addMedication($profile->getId(), 'Inhaler');

        $summary = $this->service->getHealthSummary(1);
        $this->assertArrayHasKey('profile', $summary);
        $this->assertArrayHasKey('bmi', $summary);
        $this->assertArrayHasKey('bmi_category', $summary);
        $this->assertArrayHasKey('allergies_count', $summary);
        $this->assertArrayHasKey('conditions_count', $summary);
        $this->assertArrayHasKey('medications_count', $summary);
        $this->assertSame(2, $summary['allergies_count']);
        $this->assertSame(1, $summary['conditions_count']);
        $this->assertSame(1, $summary['medications_count']);
        $this->assertSame('Normal', $summary['bmi_category']); // BMI around 22.5
    }

    public function testDeleteProfileReturnsTrueWhenFound(): void
    {
        $profile = $this->service->createProfile(1);
        $result = $this->service->deleteProfile($profile->getId());
        $this->assertTrue($result);
        $this->assertNull($this->service->getProfile($profile->getId()));
    }

    public function testDeleteProfileReturnsFalseWhenNotFound(): void
    {
        $this->assertFalse($this->service->deleteProfile(999));
    }
}
