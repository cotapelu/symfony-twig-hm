<?php

declare(strict_types=1);

namespace App\Tests\Integration\Citizen;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Tests\Integration\DatabaseIntegrationTestCase;
use App\Citizen\Domain\Entity\Citizen;

#[AllowMockObjectsWithoutExpectations]
class CitizenIntegrationDbTest extends DatabaseIntegrationTestCase
{
    public function testCreateAndRetrieveCitizen(): void
    {
        $identityNumber = '123456789';
        $fullName = 'Nguyen Van A';
        $dateOfBirth = new \DateTime('1990-01-01');
        $gender = 'male';

        $citizen = new Citizen(
            identityNumber: $identityNumber,
            fullName: $fullName,
            dateOfBirth: $dateOfBirth,
            gender: $gender
        );

        $em = self::getEntityManager();
        $em->persist($citizen);
        $em->flush();

        $this->assertNotNull($citizen->getId());
        $this->assertIsString($citizen->getId());

        $repo = $em->getRepository(Citizen::class);
        $found = $repo->find($citizen->getId());

        $this->assertNotNull($found);
        $this->assertSame($identityNumber, $found->getIdentityNumber());
        $this->assertSame($fullName, $found->getFullName());
        $this->assertSame($dateOfBirth->format('Y-m-d'), $found->getDateOfBirth()->format('Y-m-d'));
        $this->assertSame($gender, $found->getGender());
    }

    public function testFindByIdentityNumber(): void
    {
        $identity = '987654321';
        $citizen = new Citizen(
            identityNumber: $identity,
            fullName: 'Tran Thi B',
            dateOfBirth: new \DateTime('1985-05-15'),
            gender: 'female'
        );

        $em = self::getEntityManager();
        $em->persist($citizen);
        $em->flush();

        $repo = $em->getRepository(Citizen::class);
        // Since identityNumber is unique, we can try to find by field via QueryBuilder or repository method.
        // The repository may not have a custom method, so we'll use createQueryBuilder.
        $result = $repo->createQueryBuilder('c')
            ->where('c.identityNumber = :identity')
            ->setParameter('identity', $identity)
            ->getQuery()
            ->getOneOrNullResult();

        $this->assertNotNull($result);
        $this->assertSame($citizen->getId(), $result->getId());
    }

    public function testUpdateCitizen(): void
    {
        $citizen = new Citizen(
            identityNumber: '111222333',
            fullName: 'Original Name',
            dateOfBirth: new \DateTime('2000-01-01'),
            gender: 'male'
        );

        $em = self::getEntityManager();
        $em->persist($citizen);
        $em->flush();

        $id = $citizen->getId();

        // Update fields
        $citizen->setFullName('Updated Name');
        // Assuming setEmail exists
        $reflection = new \ReflectionClass($citizen);
        if ($reflection->hasMethod('setEmail')) {
            $citizen->setEmail('updated@example.com');
        }
        $em->flush();

        $found = $em->getRepository(Citizen::class)->find($id);
        $this->assertNotNull($found);
        $this->assertSame('Updated Name', $found->getFullName());
        // If email field exists and was set
        if (method_exists($found, 'getEmail')) {
            $this->assertSame('updated@example.com', $found->getEmail());
        }
    }
}
