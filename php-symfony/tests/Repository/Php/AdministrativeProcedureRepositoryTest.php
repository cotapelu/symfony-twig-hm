<?php declare(strict_types=1);

namespace App\Tests\Repository\Php;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Repository\Php\AdministrativeProcedureRepository;
use App\Entity\Php\AdministrativeProcedure;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class AdministrativeProcedureRepositoryTest extends TestCase
{
    private AdministrativeProcedureRepository&MockObject $repo;

    protected function setUp(): void
    {
        $this->repo = $this->getMockBuilder(AdministrativeProcedureRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findBy', 'findOneBy', 'count'])
            ->getMock();
    }

    public function testFindByCode(): void
    {
        $code = 'ADM-001';
        $expected = $this->createMock(AdministrativeProcedure::class);

        $this->repo->expects($this->once())
            ->method('findOneBy')
            ->with(['code' => $code])
            ->willReturn($expected);

        $result = $this->repo->findByCode($code);
        $this->assertSame($expected, $result);
    }

    public function testFindByCategory(): void
    {
        $category = 'BUILDING';
        $expected = [$this->createMock(AdministrativeProcedure::class)];

        $this->repo->expects($this->once())
            ->method('findBy')
            ->with(['category' => $category])
            ->willReturn($expected);

        $result = $this->repo->findByCategory($category);
        $this->assertSame($expected, $result);
    }

    public function testFindActiveProcedures(): void
    {
        $expected = [$this->createMock(AdministrativeProcedure::class)];

        $this->repo->expects($this->once())
            ->method('findBy')
            ->with(['status' => 'ACTIVE'])
            ->willReturn($expected);

        $result = $this->repo->findActiveProcedures();
        $this->assertSame($expected, $result);
    }

    public function testFindInactiveProcedures(): void
    {
        $expected = [$this->createMock(AdministrativeProcedure::class)];

        $this->repo->expects($this->once())
            ->method('findBy')
            ->with(['status' => 'INACTIVE'])
            ->willReturn($expected);

        $result = $this->repo->findInactiveProcedures();
        $this->assertSame($expected, $result);
    }

    public function testCountActive(): void
    {
        $this->repo->expects($this->once())
            ->method('count')
            ->with(['status' => 'ACTIVE'])
            ->willReturn(5);

        $result = $this->repo->countActive();
        $this->assertSame(5, $result);
    }

    public function testCountByCategory(): void
    {
        $category = 'BUILDING';
        $this->repo->expects($this->once())
            ->method('count')
            ->with(['category' => $category])
            ->willReturn(10);

        $result = $this->repo->countByCategory($category);
        $this->assertSame(10, $result);
    }
}
