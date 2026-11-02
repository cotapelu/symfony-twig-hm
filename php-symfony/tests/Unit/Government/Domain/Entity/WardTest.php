<?php declare(strict_types=1);

namespace App\Tests\Unit\Government\Domain\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Government\Domain\Entity\Ward;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class WardTest extends TestCase
{
    public function testConstructor(): void
    {
        $ward = new Ward(
            name: 'Ward 1',
            code: 'W001',
            district: 'District 1',
            province: 'Province A',
            population: 1000,
            areaSquareKm: 5.5
        );

        $this->assertIsString($ward->getId());
        $this->assertSame('Ward 1', $ward->getName());
        $this->assertSame('W001', $ward->getCode());
        $this->assertSame('District 1', $ward->getDistrict());
        $this->assertSame('Province A', $ward->getProvince());
        $this->assertNull($ward->getAssignedOfficerId());
        $this->assertSame(1000, $ward->getPopulation());
        $this->assertSame(5.5, $ward->getAreaSquareKm());
        $this->assertInstanceOf(\DateTimeImmutable::class, $ward->getCreatedAt());
        $this->assertNull($ward->getUpdatedAt());
    }

    public function testSetters(): void
    {
        $ward = new Ward(
            name: 'Ward 2',
            code: 'W002',
            district: 'District 2',
            province: 'Province B'
        );

        $ward->setAssignedOfficerId('off-123');
        $this->assertSame('off-123', $ward->getAssignedOfficerId());

        $ward->setPopulation(2000);
        $this->assertSame(2000, $ward->getPopulation());

        $ward->setAreaSquareKm(10.0);
        $this->assertSame(10.0, $ward->getAreaSquareKm());
    }

    public function testUpdatedAtIsSetOnSetters(): void
    {
        $ward = new Ward(
            name: 'Ward 3',
            code: 'W003',
            district: 'District 3',
            province: 'Province C'
        );
        // Wait a tiny bit to ensure time difference (microsleep) not needed; just assert not null and after createdAt
        $ward->setPopulation(3000);
        $updated = $ward->getUpdatedAt();
        $this->assertInstanceOf(\DateTimeImmutable::class, $updated);
        $this->assertGreaterThan($ward->getCreatedAt(), $updated);
    }
}
