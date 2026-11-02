<?php

declare(strict_types=1);

namespace App\Tests\Integration\Citizen;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Tests\Integration\DatabaseIntegrationTestCase;
use App\Citizen\Domain\Entity\AdministrativeProcedure;

#[AllowMockObjectsWithoutExpectations]
class AdministrativeProcedureIntegrationDbTest extends DatabaseIntegrationTestCase
{
    public function testCreateAndRetrieveAdministrativeProcedure(): void
    {
        $proc = new AdministrativeProcedure(
            name: 'Building Permit',
            code: 'BP-001',
            description: 'Apply for building permit',
            category: 'Construction',
            competentAuthority: 'City Planning Department',
            processingDays: 30
        );
        $proc->setRequiredDocuments(['ID', 'Property Deed'])
            ->setFees(['amount' => '100.00', 'currency' => 'USD'])
            ->setIsOnlineAvailable(true)
            ->setIsActive(true)
            ->setLegalBasis('City Ordinance 123');

        $em = self::getEntityManager();
        $em->persist($proc);
        $em->flush();

        $this->assertNotNull($proc->getId());

        $found = $em->getRepository(AdministrativeProcedure::class)->find($proc->getId());

        $this->assertNotNull($found);
        $this->assertSame('Building Permit', $found->getName());
        $this->assertSame('BP-001', $found->getCode());
        $this->assertSame('Apply for building permit', $found->getDescription());
        $this->assertSame('Construction', $found->getCategory());
        $this->assertSame('City Planning Department', $found->getCompetentAuthority());
        $this->assertSame(30, $found->getProcessingDays());
        $this->assertSame(['ID', 'Property Deed'], $found->getRequiredDocuments());
        $this->assertSame(['amount' => '100.00', 'currency' => 'USD'], $found->getFees());
        $this->assertTrue($found->isOnlineAvailable());
        $this->assertTrue($found->isActive());
        $this->assertSame('City Ordinance 123', $found->getLegalBasis());
    }

    public function testUpdateAdministrativeProcedure(): void
    {
        $proc = new AdministrativeProcedure(
            name: 'Business License',
            code: 'BL-001',
            description: 'License to operate a business',
            category: 'Business',
            competentAuthority: 'Commerce Department',
            processingDays: 15
        );

        $em = self::getEntityManager();
        $em->persist($proc);
        $em->flush();

        $proc->setCategory('Commercial')
            ->setProcessingDays(20)
            ->setIsActive(false);
        $em->flush();

        $found = $em->getRepository(AdministrativeProcedure::class)->find($proc->getId());
        $this->assertSame('Commercial', $found->getCategory());
        $this->assertSame(20, $found->getProcessingDays());
        $this->assertFalse($found->isActive());
    }

    public function testDeleteAdministrativeProcedure(): void
    {
        $proc = new AdministrativeProcedure(
            name: 'Health Permit',
            code: 'HP-001',
            description: 'Permit for food service',
            category: 'Health',
            competentAuthority: 'Health Department',
            processingDays: 10
        );

        $em = self::getEntityManager();
        $em->persist($proc);
        $em->flush();

        $id = $proc->getId();
        $em->remove($proc);
        $em->flush();

        $found = $em->getRepository(AdministrativeProcedure::class)->find($id);
        $this->assertNull($found);
    }

    public function testAdministrativeProcedureTimestamps(): void
    {
        $before = new \DateTimeImmutable();
        sleep(1); // ensure slight time difference

        $proc = new AdministrativeProcedure(
            name: 'Parking Permit',
            code: 'PP-001',
            description: 'Permit for parking',
            category: 'Transport',
            competentAuthority: 'Traffic Department',
            processingDays: 5
        );

        $em = self::getEntityManager();
        $em->persist($proc);
        $em->flush();

        $this->assertInstanceOf(\DateTimeInterface::class, $proc->getCreatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $proc->getUpdatedAt());
        // The createdAt should be >= $before and <= now
        $this->assertGreaterThanOrEqual($before->getTimestamp(), $proc->getCreatedAt()->getTimestamp());
    }
}
