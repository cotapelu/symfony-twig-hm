<?php declare(strict_types=1);

namespace App\Tests\Unit\Population\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Population\Service\PopulationService;
use App\Population\Repository\PopulationRecordRepository;
use App\Population\Entity\PopulationRecord;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;

#[AllowMockObjectsWithoutExpectations]
class PopulationServiceTest extends TestCase
{
    private PopulationRecordRepository&MockObject $repository;
    private PopulationService $service;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(PopulationRecordRepository::class);
        $this->service = new PopulationService($this->repository);
    }

    public function testGetPopulationListValidatesPageAndLimit(): void
    {
        $this->repository->expects($this->once())
            ->method('findPaginated')
            ->with(1, 20, [], '')
            ->willReturn([]);

        $this->service->getPopulationList(1, 20, 'all', '');
    }

    public function testGetPopulationListThrowsForPageLessThanOne(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Page must be greater than 0');
        $this->service->getPopulationList(0, 20);
    }

    public function testGetPopulationListThrowsForLimitLessThanOne(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Limit must be greater than 0');
        $this->service->getPopulationList(1, 0);
    }

    public function testGetPopulationListThrowsForLimitExceeding100(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Limit cannot exceed 100');
        $this->service->getPopulationList(1, 101);
    }

    public function testGetPopulationListAppliesFilter(): void
    {
        $this->repository->expects($this->once())
            ->method('findPaginated')
            ->with(
                $this->isInt(),
                $this->isInt(),
                $this->callback(fn($filters) => isset($filters['type']) && $filters['type'] === 'citizen'),
                $this->isString()
            )
            ->willReturn([]);

        $this->service->getPopulationList(1, 20, 'citizen', '');
    }

    public function testGetPopulationListPassesSearch(): void
    {
        $this->repository->expects($this->once())
            ->method('findPaginated')
            ->with(
                $this->isInt(),
                $this->isInt(),
                $this->isArray(),
                'john'
            )
            ->willReturn([]);

        $this->service->getPopulationList(1, 20, 'all', 'john');
    }

    public function testGetStatisticsReturnsZeroWhenNoRecords(): void
    {
        $this->repository->expects($this->once())
            ->method('findAll')
            ->willReturn([]);

        $stats = $this->service->getStatistics();
        $this->assertSame(0, $stats['total']);
        $this->assertSame(0, $stats['male']);
        $this->assertSame(0, $stats['female']);
        $this->assertSame(0, $stats['urban']);
        $this->assertSame(0, $stats['rural']);
        $this->assertSame(0.0, $stats['growth']);
    }

    public function testGetStatisticsCountsGenderAndUrbanRuralCorrectly(): void
    {
        $record1 = $this->createMock(PopulationRecord::class);
        $record1->method('getGender')->willReturn('Nam');
        $record1->method('getAddress')->willReturn('123 Thành phố HCM, Quận 1');

        $record2 = $this->createMock(PopulationRecord::class);
        $record2->method('getGender')->willReturn('Nữ');
        $record2->method('getAddress')->willReturn('456 Rural Area');

        $record3 = $this->createMock(PopulationRecord::class);
        $record3->method('getGender')->willReturn('Nam');
        $record3->method('getAddress')->willReturn('789 Quận 2');

        $this->repository->expects($this->once())
            ->method('findAll')
            ->willReturn([$record1, $record2, $record3]);

        $stats = $this->service->getStatistics();
        $this->assertSame(3, $stats['total']);
        $this->assertSame(2, $stats['male']);
        $this->assertSame(1, $stats['female']);
        $this->assertSame(2, $stats['urban']); // record1 and record3 considered urban
        $this->assertSame(1, $stats['rural']);
        $this->assertSame(0.0, $stats['growth']);
    }

    public function testGetAgeDistributionWithNoRecords(): void
    {
        $this->repository->expects($this->once())
            ->method('findAll')
            ->willReturn([]);

        $distribution = $this->service->getAgeDistribution();
        $this->assertCount(4, $distribution);
        $this->assertSame(0, $distribution[0]['count']);
        $this->assertSame(0, $distribution[0]['percentage']);
    }

    public function testGetAgeDistributionWithVariousAges(): void
    {
        $ages = [10, 25, 45, 70];
        $records = [];
        foreach ($ages as $age) {
            $birthDate = (new \DateTime())->modify("-{$age} years");
            $record = $this->createMock(PopulationRecord::class);
            $record->method('getBirthDate')->willReturn($birthDate);
            $records[] = $record;
        }

        $this->repository->expects($this->once())
            ->method('findAll')
            ->willReturn($records);

        $distribution = $this->service->getAgeDistribution();
        $this->assertCount(4, $distribution);
        $this->assertSame(1, $distribution[0]['count']);
        $this->assertSame(1, $distribution[1]['count']);
        $this->assertSame(1, $distribution[2]['count']);
        $this->assertSame(1, $distribution[3]['count']);
        $this->assertSame(25.0, $distribution[0]['percentage']);
        $this->assertSame(25.0, $distribution[1]['percentage']);
        $this->assertSame(25.0, $distribution[2]['percentage']);
        $this->assertSame(25.0, $distribution[3]['percentage']);
    }

    public function testGetByIdFound(): void
    {
        $id = '123';
        $record = $this->createMock(PopulationRecord::class);
        $record->method('toArray')->willReturn(['id'=>$id, 'name'=>'John']);

        $this->repository->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($record);

        $result = $this->service->getById($id);
        $this->assertSame(['id'=>$id, 'name'=>'John'], $result);
    }

    public function testGetByIdNotFound(): void
    {
        $id = 'nonexistent';
        $this->repository->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn(null);

        $result = $this->service->getById($id);
        $this->assertNull($result);
    }

    public function testGetByHouseholdWithEmptyId(): void
    {
        $result = $this->service->getByHousehold('');
        $this->assertSame([], $result);
    }

    public function testGetByHouseholdReturnsList(): void
    {
        $householdId = 'HH001';
        $record1 = $this->createMock(PopulationRecord::class);
        $record1->method('toArray')->willReturn(['id' => '1']);
        $record2 = $this->createMock(PopulationRecord::class);
        $record2->method('toArray')->willReturn(['id' => '2']);

        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['householdId' => $householdId])
            ->willReturn([$record1, $record2]);

        $result = $this->service->getByHousehold($householdId);
        $this->assertSame([['id' => '1'], ['id' => '2']], $result);
    }

    public function testSearchWithEmptyQuery(): void
    {
        $result = $this->service->search('');
        $this->assertSame([], $result);
    }

    public function testSearchReturnsResults(): void
    {
        $query = 'john';
        $records = [$this->createMock(PopulationRecord::class), $this->createMock(PopulationRecord::class)];
        $this->repository->expects($this->once())
            ->method('search')
            ->with($query)
            ->willReturn($records);

        $result = $this->service->search($query);
        $this->assertCount(2, $result);
    }

    public function testCreateSuccess(): void
    {
        $data = [
            'name' => 'Alice',
            'birthDate' => '1990-05-15',
            'gender' => 'Nữ',
            'address' => '123 Main St',
            'householdId' => 'HH001',
            'status' => 'Thường trú',
            'type' => 'Công dân',
            'citizenId' => 'CID12345'
        ];
        $userId = 42;

        $this->repository->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(PopulationRecord::class));

        $result = $this->service->create($data, $userId);
        $this->assertIsArray($result);
        $this->assertSame('Alice', $result['name']);
    }

    public function testUpdateSuccess(): void
    {
        $id = 'existing-id';
        $record = $this->createMock(PopulationRecord::class);
        $record->method('getId')->willReturn($id);
        $record->expects($this->once())->method('setName')->with('New Name');
        $record->expects($this->once())->method('setAddress')->with('New Address');
        $record->expects($this->once())->method('setUpdatedAt')->with($this->isInstanceOf(\DateTime::class));
        $record->method('toArray')->willReturn(['id'=>$id, 'name'=>'New Name', 'address'=>'New Address']);

        $this->repository->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($record);
        $this->repository->expects($this->once())
            ->method('save')
            ->with($record);

        $data = ['name' => 'New Name', 'address' => 'New Address'];
        $result = $this->service->update($id, $data, 1);
        $this->assertIsArray($result);
        $this->assertSame('New Name', $result['name']);
    }

    public function testDeleteSuccess(): void
    {
        $id = 'existing-id';
        $record = $this->createMock(PopulationRecord::class);
        $this->repository->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($record);
        $this->repository->expects($this->once())
            ->method('remove')
            ->with($record);

        $result = $this->service->delete($id, 1);
        $this->assertTrue($result);
    }

    public function testCreateThrowsWhenNameEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Name is required');
        $this->service->create(['name' => ''], 1);
    }
}
