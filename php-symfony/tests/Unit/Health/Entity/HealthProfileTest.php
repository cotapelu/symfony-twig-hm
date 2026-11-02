<?php declare(strict_types=1);

namespace App\Tests\Unit\Health\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Health\Entity\HealthProfile;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class HealthProfileTest extends TestCase
{
    public function testConstructor(): void
    {
        $hp = new HealthProfile(userId: 123);
        $this->assertNull($hp->getId());
        $this->assertSame(123, $hp->getUserId());
        $this->assertNull($hp->getHeight());
        $this->assertNull($hp->getWeight());
        $this->assertNull($hp->getAge());
        $this->assertNull($hp->getBloodType());
        $this->assertNull($hp->getGender());
        $this->assertSame([], $hp->getAllergies());
        $this->assertSame([], $hp->getConditions());
        $this->assertSame([], $hp->getMedications());
        $this->assertInstanceOf(\DateTime::class, $hp->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $hp->getUpdatedAt());
    }

    public function testSetHeight(): void
    {
        $hp = new HealthProfile(userId: 1);
        $hp->setHeight(175.5);
        $this->assertSame(175.5, $hp->getHeight());
    }

    public function testSetHeightInvalid(): void
    {
        $hp = new HealthProfile(userId: 1);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Height must be positive');
        $hp->setHeight(-10);
    }

    public function testSetWeight(): void
    {
        $hp = new HealthProfile(userId: 1);
        $hp->setWeight(70.2);
        $this->assertSame(70.2, $hp->getWeight());
    }

    public function testSetWeightInvalid(): void
    {
        $hp = new HealthProfile(userId: 1);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Weight must be positive');
        $hp->setWeight(0);
    }

    public function testSetAge(): void
    {
        $hp = new HealthProfile(userId: 1);
        $hp->setAge(30);
        $this->assertSame(30, $hp->getAge());
    }

    public function testSetAgeInvalid(): void
    {
        $hp = new HealthProfile(userId: 1);
        $this->expectException(\InvalidArgumentException::class);
        $hp->setAge(-1);
    }

    public function testSetBloodType(): void
    {
        $hp = new HealthProfile(userId: 1);
        $hp->setBloodType('B+');
        $this->assertSame('B+', $hp->getBloodType());
    }

    public function testSetBloodTypeInvalid(): void
    {
        $hp = new HealthProfile(userId: 1);
        $this->expectException(\InvalidArgumentException::class);
        $hp->setBloodType('X+');
    }

    public function testSetGender(): void
    {
        $hp = new HealthProfile(userId: 1);
        $hp->setGender('female');
        $this->assertSame('female', $hp->getGender());
    }

    public function testSetGenderInvalid(): void
    {
        $hp = new HealthProfile(userId: 1);
        $this->expectException(\InvalidArgumentException::class);
        $hp->setGender('unknown');
    }

    public function testAddAllergy(): void
    {
        $hp = new HealthProfile(userId: 1);
        $hp->addAllergy('Peanuts');
        $this->assertSame(['Peanuts'], $hp->getAllergies());
    }

    public function testAddAllergyDuplicateCaseInsensitive(): void
    {
        $hp = new HealthProfile(userId: 1);
        $hp->addAllergy('Peanuts');
        $hp->addAllergy('peanuts'); // duplicate, case-insensitive
        $hp->addAllergy('  peanuts  '); // duplicate with whitespace (trimmed before add)
        $this->assertSame(['Peanuts'], $hp->getAllergies());
    }

    public function testAddAllergyEmptyThrows(): void
    {
        $hp = new HealthProfile(userId: 1);
        $this->expectException(\InvalidArgumentException::class);
        $hp->addAllergy('');
    }

    public function testAddCondition(): void
    {
        $hp = new HealthProfile(userId: 1);
        $hp->addCondition('Diabetes');
        $this->assertSame(['Diabetes'], $hp->getConditions());
    }

    public function testAddConditionDuplicate(): void
    {
        $hp = new HealthProfile(userId: 1);
        $hp->addCondition('Hypertension');
        $hp->addCondition('hypertension'); // duplicate
        $this->assertCount(1, $hp->getConditions());
    }

    public function testAddMedication(): void
    {
        $hp = new HealthProfile(userId: 1);
        $hp->addMedication('Aspirin');
        $this->assertSame(['Aspirin'], $hp->getMedications());
    }

    public function testAddMedicationDuplicate(): void
    {
        $hp = new HealthProfile(userId: 1);
        $hp->addMedication('Metformin');
        $hp->addMedication('metformin');
        $this->assertCount(1, $hp->getMedications());
    }

    public function testGetBMI(): void
    {
        $hp = new HealthProfile(userId: 1);
        $hp->setHeight(180);
        $hp->setWeight(75);
        $this->assertSame(23.1, $hp->getBMI()); // 75/(1.8^2)=23.148... -> 23.1
    }

    public function testGetBMINullWhenMissing(): void
    {
        $hp = new HealthProfile(userId: 1);
        $hp->setHeight(180);
        $hp->setWeight(null);
        $this->assertNull($hp->getBMI());
        $hp2 = new HealthProfile(userId: 2);
        $hp2->setHeight(null);
        $hp2->setWeight(75);
        $this->assertNull($hp2->getBMI());
    }
}
