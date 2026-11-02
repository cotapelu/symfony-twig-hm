<?php declare(strict_types=1);

namespace App\Tests\Service\Php;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Php\CitizenIncident;
use App\Repository\Php\CitizenIncidentRepository;
use App\Service\Php\CitizenIncidentService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class CitizenIncidentServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private CitizenIncidentRepository&MockObject $repository;
    private CitizenIncidentService $service;

    private string $incidentId;
    private CitizenIncident $incident;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(CitizenIncidentRepository::class);
        $this->service = new CitizenIncidentService($this->em, $this->repository);

        $this->incidentId = bin2hex(random_bytes(16));
        $this->incident = new CitizenIncident(
            $this->incidentId,
            'citizen-1',
            'COMPLAINT'
        );
    }

    public function testCreateIncidentSuccess(): void
    {
        $citizenId = 'citizen-123';
        $type = 'ACCIDENT';
        $data = ['location' => '123 St', 'severity' => 'high'];
        $status = 'OPEN';

        $captured = null;
        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (CitizenIncident $inc) use (&$captured, $citizenId, $type, $data, $status) {
                $captured = $inc;
                return $captured !== null;
            }));
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->createIncident($citizenId, $type, $data, $status);
        $this->assertInstanceOf(CitizenIncident::class, $result);
        $this->assertSame($citizenId, $result->getCitizenId());
        $this->assertSame($type, $result->getType());
        $this->assertSame($data, $result->getData());
        $this->assertSame($status, $result->getStatus());
        $this->assertSame($captured, $result);
    }

    public function testCreateIncidentValidationEmptyCitizenId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Citizen ID is required');
        $this->service->createIncident('', 'type');
    }

    public function testCreateIncidentValidationEmptyType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Incident type is required');
        $this->service->createIncident('cid', '');
    }

    public function testCreateIncidentValidationEmptyStatus(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Status cannot be empty');
        $this->service->createIncident('cid', 'type', null, '');
    }

    public function testGetIncidentSuccess(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->incidentId)
            ->willReturn($this->incident);

        $result = $this->service->getIncident($this->incidentId);
        $this->assertSame($this->incident, $result);
    }

    public function testGetIncidentNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->incidentId)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('CitizenIncident not found');
        $this->service->getIncident($this->incidentId);
    }

    public function testUpdateIncidentSuccess(): void
    {
        $newCitizenId = 'citizen-2';
        $newType = 'THEFT';
        $newStatus = 'CLOSED';
        $newData = ['place' => 'market'];

        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->incidentId)
            ->willReturn($this->incident);
        $this->em->expects($this->once())->method('flush');

        $data = [
            'citizenId' => $newCitizenId,
            'type' => $newType,
            'status' => $newStatus,
            'data' => $newData,
        ];
        $result = $this->service->updateIncident($this->incidentId, $data);
        $this->assertSame($this->incident, $result);
        $this->assertSame($newCitizenId, $result->getCitizenId());
        $this->assertSame($newType, $result->getType());
        $this->assertSame($newStatus, $result->getStatus());
        $this->assertSame($newData, $result->getData());
    }

    public function testUpdateIncidentValidationEmptyCitizenId(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->incidentId)
            ->willReturn($this->incident);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Citizen ID cannot be empty');
        $this->service->updateIncident($this->incidentId, ['citizenId' => '']);
    }

    public function testUpdateIncidentValidationEmptyType(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->incidentId)
            ->willReturn($this->incident);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Incident type cannot be empty');
        $this->service->updateIncident($this->incidentId, ['type' => '']);
    }

    public function testUpdateIncidentValidationEmptyStatus(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->incidentId)
            ->willReturn($this->incident);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Status cannot be empty');
        $this->service->updateIncident($this->incidentId, ['status' => '']);
    }

    public function testUpdateIncidentNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->incidentId)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->service->updateIncident($this->incidentId, []);
    }

    public function testDeleteIncidentSuccess(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->incidentId)
            ->willReturn($this->incident);
        $this->em->expects($this->once())->method('remove')->with($this->incident);
        $this->em->expects($this->once())->method('flush');

        $this->service->deleteIncident($this->incidentId);
    }

    public function testDeleteIncidentNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->incidentId)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->service->deleteIncident($this->incidentId);
    }

    public function testFindByCitizenId(): void
    {
        $citizenId = 'citizen-123';
        $results = [$this->incident];
        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['citizenId' => $citizenId])
            ->willReturn($results);

        $result = $this->service->findByCitizenId($citizenId);
        $this->assertSame($results, $result);
    }

    public function testFindByType(): void
    {
        $type = 'COMPLAINT';
        $results = [$this->incident];
        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['type' => $type])
            ->willReturn($results);

        $result = $this->service->findByType($type);
        $this->assertSame($results, $result);
    }

    public function testFindByStatus(): void
    {
        $status = 'RESOLVED';
        $results = [$this->incident];
        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['status' => $status])
            ->willReturn($results);

        $result = $this->service->findByStatus($status);
        $this->assertSame($results, $result);
    }
}
