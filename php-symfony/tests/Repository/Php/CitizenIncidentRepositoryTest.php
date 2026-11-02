<?php declare(strict_types=1);

namespace App\Tests\Repository\Php;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Repository\Php\CitizenIncidentRepository;
use App\Entity\Php\CitizenIncident;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class CitizenIncidentRepositoryTest extends TestCase
{
    private CitizenIncidentRepository&MockObject $repo;

    protected function setUp(): void
    {
        $this->repo = $this->getMockBuilder(CitizenIncidentRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findBy', 'findOneBy', 'count'])
            ->getMock();
    }

    public function testFindByCitizenId(): void
    {
        $citizenId = 'cit-123';
        $expected = [$this->createMock(CitizenIncident::class)];

        $this->repo->expects($this->once())
            ->method('findBy')
            ->with(['citizenId' => $citizenId])
            ->willReturn($expected);

        $result = $this->repo->findByCitizenId($citizenId);
        $this->assertSame($expected, $result);
    }

    public function testFindByStatus(): void
    {
        $status = 'INVESTIGATING';
        $expected = [$this->createMock(CitizenIncident::class)];

        $this->repo->expects($this->once())
            ->method('findBy')
            ->with(['status' => $status])
            ->willReturn($expected);

        $result = $this->repo->findByStatus($status);
        $this->assertSame($expected, $result);
    }

    public function testFindByIncidentType(): void
    {
        $type = 'THEFT';
        $expected = [$this->createMock(CitizenIncident::class)];

        $this->repo->expects($this->once())
            ->method('findBy')
            ->with(['incidentType' => $type])
            ->willReturn($expected);

        $result = $this->repo->findByIncidentType($type);
        $this->assertSame($expected, $result);
    }

    public function testFindActiveIncidents(): void
    {
        $expected = [$this->createMock(CitizenIncident::class)];
        $statuses = ['REPORTED', 'INVESTIGATING', 'PENDING'];

        $this->repo->expects($this->once())
            ->method('findBy')
            ->with(['status' => $statuses])
            ->willReturn($expected);

        $result = $this->repo->findActiveIncidents();
        $this->assertSame($expected, $result);
    }
}
