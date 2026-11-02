<?php declare(strict_types=1);

namespace App\Tests\Integration\Government;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Tests\Integration\DatabaseIntegrationTestCase;
use App\Government\Domain\Entity\Ward;
use Doctrine\ORM\EntityManagerInterface;

#[AllowMockObjectsWithoutExpectations]
class WardIntegrationDbTest extends DatabaseIntegrationTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        parent::setUp();
        $this->em = self::getEntityManager();
    }

    public function testCreateWardAndPersist(): void
    {
        $ward = new Ward(
            name: 'Downtown',
            code: 'DT-001',
            district: 'Central District',
            province: 'Metro Province',
            population: 50000,
            areaSquareKm: 12.5
        );
        $this->em->persist($ward);
        $this->em->flush();

        $this->assertNotNull($ward->getId());
        $this->assertSame('Downtown', $ward->getName());
        $this->assertSame('DT-001', $ward->getCode());
        $this->assertSame(50000, $ward->getPopulation());
        $this->assertSame(12.5, $ward->getAreaSquareKm());
    }

    public function testFindWardById(): void
    {
        $ward = new Ward(
            name: 'Uptown',
            code: 'UT-002',
            district: 'North District',
            province: 'Metro Province',
            population: 30000,
            areaSquareKm: 8.0
        );
        $this->em->persist($ward);
        $this->em->flush();

        $id = $ward->getId();
        $repo = $this->em->getRepository(Ward::class);
        $found = $repo->find($id);
        $this->assertNotNull($found);
        $this->assertSame('Uptown', $found->getName());
        $this->assertSame('UT-002', $found->getCode());
    }

    public function testUpdateWard(): void
    {
        $ward = new Ward(
            name: 'Original Ward',
            code: 'OW-003',
            district: 'West District',
            province: 'Metro Province',
            population: 10000,
            areaSquareKm: 5.0
        );
        $this->em->persist($ward);
        $this->em->flush();

        $ward->setPopulation(15000);
        $ward->setAreaSquareKm(6.2);
        $ward->setAssignedOfficerId('officer-123');
        $this->em->flush();

        $repo = $this->em->getRepository(Ward::class);
        $updated = $repo->find($ward->getId());
        $this->assertSame(15000, $updated->getPopulation());
        $this->assertSame(6.2, $updated->getAreaSquareKm());
        $this->assertSame('officer-123', $updated->getAssignedOfficerId());
    }

    public function testDeleteWard(): void
    {
        $ward = new Ward(
            name: 'ToDelete',
            code: 'TD-004',
            district: 'East District',
            province: 'Metro Province',
            population: 500,
            areaSquareKm: 1.0
        );
        $this->em->persist($ward);
        $this->em->flush();

        $id = $ward->getId();
        $this->em->remove($ward);
        $this->em->flush();

        $repo = $this->em->getRepository(Ward::class);
        $this->assertNull($repo->find($id));
    }
}
