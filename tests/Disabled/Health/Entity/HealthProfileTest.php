<?php declare(strict_types=1);

namespace App\Tests\Unit\Health\Entity;

use App\Health\Entity\HealthProfile;
use PHPUnit\Framework\TestCase;

class HealthProfileTest extends TestCase
{
    public function testConstructorSetsUserId(): void
    {
        $profile = new HealthProfile(123);
        
        $this->assertNull($profile->getId());
        $this->assertEquals(123, $profile->getUserId());
    }

    public function testSetHeight(): void
    {
        $profile = new HealthProfile(123);
        $profile->setHeight(175.5);
        
        $this->assertEquals(175.5, $profile->getHeight());
    }

    public function testSetWeight(): void
    {
        $profile = new HealthProfile(123);
        $profile->setWeight(70.5);
        
        $this->assertEquals(70.5, $profile->getWeight());
    }

    public function testSetAge(): void
    {
        $profile = new HealthProfile(123);
        $profile->setAge(30);
        
        $this->assertEquals(30, $profile->getAge());
    }

    public function testSetBloodType(): void
    {
        $profile = new HealthProfile(123);
        $profile->setBloodType('A+');
        
        $this->assertEquals('A+', $profile->getBloodType());
    }

    public function testSetGender(): void
    {
        $profile = new HealthProfile(123);
        $profile->setGender('male');
        
        $this->assertEquals('male', $profile->getGender());
    }

    public function testAddAllergy(): void
    {
        $profile = new HealthProfile(123);
        $profile->addAllergy('peanuts');
        
        $this->assertCount(1, $profile->getAllergies());
        $this->assertEquals('peanuts', $profile->getAllergies()[0]);
    }

    public function testAddCondition(): void
    {
        $profile = new HealthProfile(123);
        $profile->addCondition('diabetes');
        
        $this->assertCount(1, $profile->getConditions());
        $this->assertEquals('diabetes', $profile->getConditions()[0]);
    }

    public function testAddMedication(): void
    {
        $profile = new HealthProfile(123);
        $profile->addMedication('insulin');
        
        $this->assertCount(1, $profile->getMedications());
        $this->assertEquals('insulin', $profile->getMedications()[0]);
    }

    public function testGetBMIWithValidData(): void
    {
        $profile = new HealthProfile(123);
        $profile->setHeight(170);
        $profile->setWeight(70);
        
        $this->assertEquals(24.2, $profile->getBMI());
    }

    public function testGetBMIWithMissingHeight(): void
    {
        $profile = new HealthProfile(123);
        $profile->setWeight(70);
        
        $this->assertNull($profile->getBMI());
    }

    public function testGetBMIWithMissingWeight(): void
    {
        $profile = new HealthProfile(123);
        $profile->setHeight(170);
        
        $this->assertNull($profile->getBMI());
    }

    public function testSetId(): void
    {
        $profile = new HealthProfile(123);
        $profile->setId(42);
        
        $this->assertEquals(42, $profile->getId());
    }

    public function testMultipleAllergies(): void
    {
        $profile = new HealthProfile(123);
        $profile->addAllergy('peanuts');
        $profile->addAllergy('shellfish');
        
        $this->assertCount(2, $profile->getAllergies());
    }
}
