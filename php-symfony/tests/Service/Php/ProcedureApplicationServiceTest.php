<?php declare(strict_types=1);

namespace App\Tests\Service\Php;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Php\ProcedureApplication;
use App\Repository\Php\ProcedureApplicationRepository;
use App\Service\Php\ProcedureApplicationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ProcedureApplicationServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private ProcedureApplicationRepository&MockObject $repository;
    private ProcedureApplicationService $service;

    private string $applicationId;
    private ProcedureApplication $application;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(ProcedureApplicationRepository::class);
        $this->service = new ProcedureApplicationService($this->em, $this->repository);

        $this->applicationId = bin2hex(random_bytes(16));
        $this->application = new ProcedureApplication(
            $this->applicationId,
            'citizen-1',
            'procedure-1'
        );
    }

    public function testCreateApplicationSuccess(): void
    {
        $citizenId = 'citizen-123';
        $procedureId = 'proc-123';
        $data = ['details' => 'some info'];
        $status = 'APPROVED';

        $captured = null;
        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (ProcedureApplication $app) use (&$captured, $citizenId, $procedureId, $data, $status) {
                $captured = $app;
                return $captured !== null;
            }));
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->createApplication($citizenId, $procedureId, $data, $status);
        $this->assertInstanceOf(ProcedureApplication::class, $result);
        $this->assertSame($citizenId, $result->getCitizenId());
        $this->assertSame($procedureId, $result->getProcedureId());
        $this->assertSame($data, $result->getData());
        $this->assertSame($status, $result->getStatus());
        $this->assertSame($captured, $result);
    }

    public function testCreateApplicationValidationEmptyCitizenId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Citizen ID is required');
        $this->service->createApplication('', 'proc');
    }

    public function testCreateApplicationValidationEmptyProcedureId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Procedure ID is required');
        $this->service->createApplication('cid', '');
    }

    public function testCreateApplicationValidationEmptyStatus(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Status cannot be empty');
        $this->service->createApplication('cid', 'pid', null, '');
    }

    public function testGetApplicationSuccess(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->applicationId)
            ->willReturn($this->application);

        $result = $this->service->getApplication($this->applicationId);
        $this->assertSame($this->application, $result);
    }

    public function testGetApplicationNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->applicationId)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('ProcedureApplication not found');
        $this->service->getApplication($this->applicationId);
    }

    public function testUpdateApplicationSuccess(): void
    {
        $newCitizenId = 'citizen-2';
        $newProcedureId = 'proc-2';
        $newStatus = 'REJECTED';
        $newData = ['reason' => 'missing docs'];

        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->applicationId)
            ->willReturn($this->application);
        $this->em->expects($this->once())->method('flush');

        $data = [
            'citizenId' => $newCitizenId,
            'procedureId' => $newProcedureId,
            'status' => $newStatus,
            'data' => $newData,
        ];
        $result = $this->service->updateApplication($this->applicationId, $data);
        $this->assertSame($this->application, $result);
        $this->assertSame($newCitizenId, $result->getCitizenId());
        $this->assertSame($newProcedureId, $result->getProcedureId());
        $this->assertSame($newStatus, $result->getStatus());
        $this->assertSame($newData, $result->getData());
    }

    public function testUpdateApplicationValidationEmptyCitizenId(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->applicationId)
            ->willReturn($this->application);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Citizen ID cannot be empty');
        $this->service->updateApplication($this->applicationId, ['citizenId' => '']);
    }

    public function testUpdateApplicationValidationEmptyProcedureId(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->applicationId)
            ->willReturn($this->application);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Procedure ID cannot be empty');
        $this->service->updateApplication($this->applicationId, ['procedureId' => '']);
    }

    public function testUpdateApplicationValidationEmptyStatus(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->applicationId)
            ->willReturn($this->application);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Status cannot be empty');
        $this->service->updateApplication($this->applicationId, ['status' => '']);
    }

    public function testUpdateApplicationNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->applicationId)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->service->updateApplication($this->applicationId, []);
    }

    public function testDeleteApplicationSuccess(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->applicationId)
            ->willReturn($this->application);
        $this->em->expects($this->once())->method('remove')->with($this->application);
        $this->em->expects($this->once())->method('flush');

        $this->service->deleteApplication($this->applicationId);
    }

    public function testDeleteApplicationNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->applicationId)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->service->deleteApplication($this->applicationId);
    }

    public function testFindByCitizenId(): void
    {
        $citizenId = 'citizen-123';
        $results = [$this->application];
        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['citizenId' => $citizenId])
            ->willReturn($results);

        $result = $this->service->findByCitizenId($citizenId);
        $this->assertSame($results, $result);
    }

    public function testFindByProcedureId(): void
    {
        $procedureId = 'proc-123';
        $results = [$this->application];
        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['procedureId' => $procedureId])
            ->willReturn($results);

        $result = $this->service->findByProcedureId($procedureId);
        $this->assertSame($results, $result);
    }

    public function testFindByStatus(): void
    {
        $status = 'PENDING';
        $results = [$this->application];
        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['status' => $status])
            ->willReturn($results);

        $result = $this->service->findByStatus($status);
        $this->assertSame($results, $result);
    }
}
