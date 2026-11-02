<?php

declare(strict_types=1);

namespace App\Tests\Integration\SecurityEmergency;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Tests\Integration\DatabaseIntegrationTestCase;
use App\SecurityEmergency\Entity\SecurityIncident;

#[AllowMockObjectsWithoutExpectations]
class SecurityEmergencyIntegrationTest extends DatabaseIntegrationTestCase
{
    public function testCreateSecurityIncident(): void
    {
        $incident = new SecurityIncident(
            type: 'fire',
            status: 'open',
            reporterId: 'user-123',
            location: '123 Main St',
            description: 'Small fire reported'
        );

        $em = self::getEntityManager();
        $em->persist($incident);
        $em->flush();

        $this->assertNotNull($incident->getId());
        $this->assertSame('fire', $incident->getType());
        $this->assertSame('open', $incident->getStatus());
    }

    public function testFindById(): void
    {
        $incident = new SecurityIncident(
            type: 'medical',
            status: 'closed',
            reporterId: 'user-med'
        );
        $em = self::getEntityManager();
        $em->persist($incident);
        $em->flush();

        $found = $em->find(SecurityIncident::class, $incident->getId());
        $this->assertNotNull($found);
        $this->assertSame('medical', $found->getType());
    }

    public function testFindByStatus(): void
    {
        $em = self::getEntityManager();

        $inc1 = new SecurityIncident('theft', 'open', 'user1');
        $inc2 = new SecurityIncident('vandalism', 'open', 'user2');
        $inc3 = new SecurityIncident('fire', 'closed', 'user3');

        foreach ([$inc1, $inc2, $inc3] as $inc) {
            $em->persist($inc);
        }
        $em->flush();

        $qb = $em->createQueryBuilder();
        $qb->select('i')
            ->from(SecurityIncident::class, 'i')
            ->where('i.status = :status')
            ->setParameter('status', 'open');
        $open = $qb->getQuery()->getResult();

        $this->assertCount(2, $open);
        foreach ($open as $inc) {
            $this->assertSame('open', $inc->getStatus());
        }
    }

    public function testUpdateIncident(): void
    {
        $incident = new SecurityIncident('noise', 'open', 'user4');
        $em = self::getEntityManager();
        $em->persist($incident);
        $em->flush();

        $incident->setStatus('closed');
        $em->flush();

        $updated = $em->find(SecurityIncident::class, $incident->getId());
        $this->assertSame('closed', $updated->getStatus());
    }

    public function testDeleteIncident(): void
    {
        $incident = new SecurityIncident('fight', 'open', 'user5');
        $em = self::getEntityManager();
        $em->persist($incident);
        $em->flush();

        $id = $incident->getId();
        $em->remove($incident);
        $em->flush();

        $deleted = $em->find(SecurityIncident::class, $id);
        $this->assertNull($deleted);
    }
}
