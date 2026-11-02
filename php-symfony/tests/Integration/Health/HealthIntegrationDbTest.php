<?php

declare(strict_types=1);

namespace App\Tests\Integration\Health;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Tests\Integration\DatabaseIntegrationTestCase;
use App\Health\Entity\HealthProfile;

#[AllowMockObjectsWithoutExpectations]
class HealthIntegrationDbTest extends DatabaseIntegrationTestCase
{
    public function testCreateHealthProfile(): void
    {
        $profile = new HealthProfile(userId: 123);
        $profile->setHeight(175.5)
            ->setWeight(70.2)
            ->setAge(30)
            ->setBloodType('A+')
            ->setGender('male')
            ->addAllergy('Penicillin')
            ->addCondition('Asthma')
            ->addMedication('Albuterol');

        $em = self::getEntityManager();
        $em->persist($profile);
        $em->flush();

        $this->assertNotNull($profile->getId());
        $this->assertSame(123, $profile->getUserId());
        $this->assertSame(175.5, $profile->getHeight());
        $this->assertSame(70.2, $profile->getWeight());
        $this->assertSame(30, $profile->getAge());
        $this->assertSame('A+', $profile->getBloodType());
        $this->assertSame('male', $profile->getGender());
        $this->assertSame(['Penicillin'], $profile->getAllergies());
        $this->assertSame(['Asthma'], $profile->getConditions());
        $this->assertSame(['Albuterol'], $profile->getMedications());
        $this->assertSame(22.8, $profile->getBMI()); // approx
    }

    public function testUpdateHealthProfile(): void
    {
        $profile = new HealthProfile(userId: 456);
        $em = self::getEntityManager();
        $em->persist($profile);
        $em->flush();

        $profile->setWeight(80.0)
            ->addAllergy('Sulfa')
            ->addCondition('Hypertension');
        $em->flush();

        $found = $em->getRepository(HealthProfile::class)->find($profile->getId());
        $this->assertSame(80.0, $found->getWeight());
        $this->assertSame(['Sulfa'], $found->getAllergies());
        $this->assertSame(['Hypertension'], $found->getConditions());
    }

    public function testDeleteHealthProfile(): void
    {
        $profile = new HealthProfile(userId: 789);
        $em = self::getEntityManager();
        $em->persist($profile);
        $em->flush();

        $id = $profile->getId();
        $em->remove($profile);
        $em->flush();

        $found = $em->getRepository(HealthProfile::class)->find($id);
        $this->assertNull($found);
    }

    public function testHealthProfileIdIsAutoIncrement(): void
    {
        $profile = new HealthProfile(userId: 1);
        $em = self::getEntityManager();
        $em->persist($profile);
        $em->flush();

        $this->assertIsInt($profile->getId());
        $this->assertGreaterThan(0, $profile->getId());
    }

    public function testBMICalculation(): void
    {
        $profile = new HealthProfile(userId: 1);
        $profile->setHeight(180)  // cm
                ->setWeight(81);   // kg -> BMI = 81/(1.8^2)=25.0

        $this->assertSame(25.0, $profile->getBMI());
    }

    public function testInvalidBloodTypeThrows(): void
    {
        $profile = new HealthProfile(userId: 1);
        $this->expectException(\InvalidArgumentException::class);
        $profile->setBloodType('Invalid');
    }

    public function testAddAllergyCaseInsensitive(): void
    {
        $profile = new HealthProfile(userId: 1);
        $profile->addAllergy('penicillin');
        $profile->addAllergy('Penicillin'); // duplicate, case-insensitive

        $this->assertCount(1, $profile->getAllergies());
        $this->assertSame('penicillin', $profile->getAllergies()[0]); // stored as first added
    }

    public function testAddConditionAndMedication(): void
    {
        $profile = new HealthProfile(userId: 1);
        $profile->addCondition('diabetes')
                ->addMedication('Metformin');

        $this->assertSame(['diabetes'], $profile->getConditions());
        $this->assertSame(['Metformin'], $profile->getMedications());
    }
}
