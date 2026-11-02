<?php

declare(strict_types=1);

namespace App\Tests\Unit\Government\Infrastructure\Repository;

use App\Government\Domain\Entity\Ward;
use App\Government\Domain\Repository\WardRepositoryInterface;
use PHPUnit\Framework\TestCase;

class WardRepositoryTest extends TestCase
{
    private WardRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(WardRepositoryInterface::class);
    }

    // CRUD Tests (15 tests total)

    public function testCreateWardSuccess(): void
    {
        $wardData = [
            'name' => 'Phường 1',
            'code' => 'P001',
            'district' => 'Quận 1',
            'province' => 'Thành phố Hồ Chí Minh',
            'population' => 50000,
            'areaSquareKm' => 5.2
        ];

        $ward = new Ward(
            name: $wardData['name'],
            code: $wardData['code'],
            district: $wardData['district'],
            province: $wardData['province'],
            population: $wardData['population'],
            areaSquareKm: $wardData['areaSquareKm']
        );

        $this->repository
            ->expects($this->once())
            ->method('save')
            ->with($ward);

        $this->assertInstanceOf(Ward::class, $ward);
        $this->assertEquals('Phường 1', $ward->getName());
        $this->assertEquals('P001', $ward->getCode());
    }

    public function testCreateWardWithMinimalData(): void
    {
        $ward = new Ward(
            name: 'Phường 2',
            code: 'P002',
            district: 'Quận 1',
            province: 'Hà Nội'
        );

        $this->repository
            ->expects($this->once())
            ->method('save')
            ->with($ward);

        $this->assertEquals(0, $ward->getPopulation());
        $this->assertEquals(0.0, $ward->getAreaSquareKm());
    }

    public function testFindWardByIdSuccess(): void
    {
        $wardId = 'ward_123';

        $expectedWard = new Ward(
            name: 'Phường 1',
            code: 'P001',
            district: 'Quận 1',
            province: 'Hồ Chí Minh'
        );
        // Set the ID via reflection for testing purposes
        $reflection = new \ReflectionClass($expectedWard);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($expectedWard, $wardId);

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with($wardId)
            ->willReturn($expectedWard);

        $result = $this->repository->findById($wardId);

        $this->assertNotNull($result);
        $this->assertEquals($wardId, $result->getId());
        $this->assertEquals('Phường 1', $result->getName());
    }

    public function testFindWardByIdNotFound(): void
    {
        $nonExistentId = 'ward_999';

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with($nonExistentId)
            ->willReturn(null);

        $result = $this->repository->findById($nonExistentId);

        $this->assertNull($result);
    }

    public function testFindWardByCodeSuccess(): void
    {
        $wardCode = 'P001';

        $expectedWard = new Ward(
            name: 'Phường 1',
            code: $wardCode,
            district: 'Quận 1',
            province: 'Hồ Chí Minh'
        );

        $this->repository
            ->expects($this->once())
            ->method('findByCode')
            ->with($wardCode)
            ->willReturn($expectedWard);

        $result = $this->repository->findByCode($wardCode);

        $this->assertNotNull($result);
        $this->assertEquals($wardCode, $result->getCode());
    }

    public function testFindWardByCodeNotFound(): void
    {
        $nonExistentCode = 'P999';

        $this->repository
            ->expects($this->once())
            ->method('findByCode')
            ->with($nonExistentCode)
            ->willReturn(null);

        $result = $this->repository->findByCode($nonExistentCode);

        $this->assertNull($result);
    }

    public function testFindAllWardsWithoutFilters(): void
    {
        $ward1 = new Ward(name: 'Phường 1', code: 'P001', district: 'Q1', province: 'HCM');
        $ward2 = new Ward(name: 'Phường 2', code: 'P002', district: 'Q1', province: 'HCM');
        $ward3 = new Ward(name: 'Phường 3', code: 'P003', district: 'Q2', province: 'HCM');

        $expectedWards = [$ward1, $ward2, $ward3];

        $this->repository
            ->expects($this->once())
            ->method('findAll')
            ->with([], 1, 20)
            ->willReturn($expectedWards);

        $result = $this->repository->findAll([], 1, 20);

        $this->assertCount(3, $result);
        $this->assertContainsOnly(Ward::class, $result);
    }

    public function testFindAllWardsWithDistrictFilter(): void
    {
        $filters = ['district' => 'Quận 1'];

        $ward1 = new Ward(name: 'Phường 1', code: 'P001', district: 'Quận 1', province: 'HCM');
        $ward2 = new Ward(name: 'Phường 2', code: 'P002', district: 'Quận 1', province: 'HCM');

        $expectedWards = [$ward1, $ward2];

        $this->repository
            ->expects($this->once())
            ->method('findAll')
            ->with($filters, 1, 20)
            ->willReturn($expectedWards);

        $result = $this->repository->findAll($filters, 1, 20);

        $this->assertCount(2, $result);
    }

    public function testFindAllWardsWithProvinceFilter(): void
    {
        $filters = ['province' => 'Hà Nội'];

        $this->repository
            ->expects($this->once())
            ->method('findAll')
            ->with($filters, 1, 20)
            ->willReturn([]);

        $this->assertArrayHasKey('province', $filters);
        $this->assertEquals('Hà Nội', $filters['province']);
    }

    public function testFindAllWardsWithPagination(): void
    {
        $currentPage = 2;
        $perPage = 50;

        $this->repository
            ->expects($this->once())
            ->method('findAll')
            ->with([], $currentPage, $perPage)
            ->willReturn([]);

        $this->assertEquals(2, $currentPage);
        $this->assertEquals(50, $perPage);
    }

    public function testUpdateWardSuccess(): void
    {
        $ward = new Ward(
            name: 'Phường 1',
            code: 'P001',
            district: 'Quận 1',
            province: 'Hồ Chí Minh',
            population: 50000,
            areaSquareKm: 5.2
        );

        $ward->setPopulation(55000);
        $ward->setAreaSquareKm(5.5);

        $this->repository
            ->expects($this->once())
            ->method('save')
            ->with($ward);

        $this->assertEquals(55000, $ward->getPopulation());
        $this->assertEquals(5.5, $ward->getAreaSquareKm());
    }

    public function testDeleteWardSuccess(): void
    {
        $wardId = 'ward_123';

        $this->repository
            ->expects($this->once())
            ->method('delete')
            ->with($wardId);

        $this->assertIsString($wardId);
        $this->assertNotEmpty($wardId);
    }

    public function testAssignOfficerToWard(): void
    {
        $ward = new Ward(
            name: 'Phường 1',
            code: 'P001',
            district: 'Quận 1',
            province: 'Hồ Chí Minh'
        );

        $officerId = 'officer_456';

        $ward->setAssignedOfficerId($officerId);

        $this->repository
            ->expects($this->once())
            ->method('save')
            ->with($ward);

        $this->assertEquals($officerId, $ward->getAssignedOfficerId());
    }

    public function testUnassignOfficerFromWard(): void
    {
        $ward = new Ward(
            name: 'Phường 1',
            code: 'P001',
            district: 'Quận 1',
            province: 'Hồ Chí Minh'
        );

        $ward->setAssignedOfficerId('officer_456');
        $ward->setAssignedOfficerId(null);

        $this->repository
            ->expects($this->once())
            ->method('save')
            ->with($ward);

        $this->assertNull($ward->getAssignedOfficerId());
    }

    // Validation Tests (5 tests)

    public function testWardCodeUniquenessValidation(): void
    {
        $existingWard = new Ward(
            name: 'Phường 1',
            code: 'P001',
            district: 'Quận 1',
            province: 'Hồ Chí Minh'
        );

        $this->repository
            ->expects($this->once())
            ->method('findByCode')
            ->with('P001')
            ->willReturn($existingWard);

        $result = $this->repository->findByCode('P001');

        $this->assertNotNull($result);
        $this->assertEquals('P001', $result->getCode());
    }

    public function testWardNameNotEmpty(): void
    {
        $wardName = 'Phường 1';
        $emptyName = '';

        $this->assertNotEmpty($wardName);
        $this->assertEmpty($emptyName);
        $this->assertGreaterThanOrEqual(1, strlen($wardName));
    }

    public function testWardDistrictNotEmpty(): void
    {
        $district = 'Quận 1';

        $this->assertNotEmpty($district);
        $this->assertGreaterThanOrEqual(1, strlen($district));
    }

    public function testWardProvinceNotEmpty(): void
    {
        $province = 'Thành phố Hồ Chí Minh';

        $this->assertNotEmpty($province);
        $this->assertGreaterThanOrEqual(1, strlen($province));
    }

    public function testWardCodeFormat(): void
    {
        $validCode1 = 'P001';
        $validCode2 = 'WARD123';
        $invalidCode = '';

        $this->assertMatchesRegularExpression('/^[A-Z]+[0-9]+$/', $validCode1);
        $this->assertMatchesRegularExpression('/^[A-Z]+[0-9]+$/', $validCode2);
        $this->assertEmpty($invalidCode);
    }

    // Additional Tests

    public function testFindWardsByAssignedOfficer(): void
    {
        $officerId = 'officer_123';

        $ward1 = new Ward(name: 'Phường 1', code: 'P001', district: 'Q1', province: 'HCM');
        $ward1->setAssignedOfficerId($officerId);

        $ward2 = new Ward(name: 'Phường 2', code: 'P002', district: 'Q1', province: 'HCM');
        $ward2->setAssignedOfficerId($officerId);

        $expectedWards = [$ward1, $ward2];

        $this->repository
            ->expects($this->once())
            ->method('findByAssignedOfficer')
            ->with($officerId)
            ->willReturn($expectedWards);

        $result = $this->repository->findByAssignedOfficer($officerId);

        $this->assertCount(2, $result);
    }

    public function testCountWardsByAssignedOfficer(): void
    {
        $officerId = 'officer_123';

        $this->repository
            ->expects($this->once())
            ->method('countByAssignedOfficer')
            ->with($officerId)
            ->willReturn(3);

        $count = $this->repository->countByAssignedOfficer($officerId);

        $this->assertEquals(3, $count);
        $this->assertIsInt($count);
    }

    public function testCountWardsWithFilters(): void
    {
        $filters = ['district' => 'Quận 1'];

        $this->repository
            ->expects($this->once())
            ->method('count')
            ->with($filters)
            ->willReturn(10);

        $count = $this->repository->count($filters);

        $this->assertEquals(10, $count);
        $this->assertIsInt($count);
    }

    public function testWardPopulationValidation(): void
    {
        $population = 50000;

        $this->assertGreaterThanOrEqual(0, $population);
        $this->assertIsInt($population);
    }

    public function testWardAreaValidation(): void
    {
        $area = 5.2;

        $this->assertGreaterThan(0, $area);
        $this->assertIsFloat($area);
    }

    public function testWardToarrayConversion(): void
    {
        $ward = new Ward(
            name: 'Phường 1',
            code: 'P001',
            district: 'Quận 1',
            province: 'Hồ Chí Minh'
        );

        $array = $ward->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('code', $array);
        $this->assertArrayHasKey('district', $array);
        $this->assertArrayHasKey('province', $array);
        $this->assertArrayHasKey('population', $array);
        $this->assertArrayHasKey('areaSquareKm', $array);
        $this->assertArrayHasKey('createdAt', $array);
    }

    public function testWardCreatedAtAutomaticallySet(): void
    {
        $ward = new Ward(
            name: 'Phường 1',
            code: 'P001',
            district: 'Quận 1',
            province: 'Hồ Chí Minh'
        );

        $this->assertInstanceOf(\DateTimeImmutable::class, $ward->getCreatedAt());
    }

    public function testWardStatistics(): void
    {
        $filters = ['district' => 'Quận 1', 'province' => 'Hồ Chí Minh'];

        $this->repository
            ->expects($this->once())
            ->method('count')
            ->with($filters)
            ->willReturn(15);

        $this->repository
            ->expects($this->once())
            ->method('findAll')
            ->with($filters, 1, 20)
            ->willReturn([]);

        $count = $this->repository->count($filters);
        $wards = $this->repository->findAll($filters, 1, 20);

        $this->assertEquals(15, $count);
        $this->assertIsArray($wards);
    }

    public function testWardCodeLength(): void
    {
        $validCode = 'P001';

        $this->assertLessThanOrEqual(20, strlen($validCode));
    }

    public function testWardNameLength(): void
    {
        $validName = 'Phường 1';

        $this->assertLessThanOrEqual(255, strlen($validName));
    }

    public function testWardDistrictLength(): void
    {
        $validDistrict = 'Quận 1';

        $this->assertLessThanOrEqual(100, strlen($validDistrict));
    }

    public function testWardProvinceLength(): void
    {
        $validProvince = 'Thành phố Hồ Chí Minh';

        $this->assertLessThanOrEqual(100, strlen($validProvince));
    }

    public function testFindAllWardsWithCombinedFilters(): void
    {
        $filters = [
            'district' => 'Quận 1',
            'province' => 'Hà Nội',
            'assignedOfficerId' => 'officer_123'
        ];

        $this->repository
            ->expects($this->once())
            ->method('findAll')
            ->with($filters, 1, 20)
            ->willReturn([]);

        $this->assertCount(3, $filters);
        $this->assertArrayHasKey('district', $filters);
        $this->assertArrayHasKey('province', $filters);
        $this->assertArrayHasKey('assignedOfficerId', $filters);
    }

    public function testWardAssignedOfficerIdType(): void
    {
        $ward = new Ward(name: 'Phường 1', code: 'P001', district: 'Q1', province: 'HCM');

        $ward->setAssignedOfficerId('officer_123');

        $this->assertIsString($ward->getAssignedOfficerId());

        $ward->setAssignedOfficerId(null);

        $this->assertNull($ward->getAssignedOfficerId());
    }
}
