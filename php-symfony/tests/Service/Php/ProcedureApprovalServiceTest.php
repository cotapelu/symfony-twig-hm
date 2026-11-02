<?php declare(strict_types=1);

namespace App\Tests\Service\Php;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Php\ProcedureApproval;
use App\Repository\Php\ProcedureApprovalRepository;
use App\Service\Php\ProcedureApprovalService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ProcedureApprovalServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private ProcedureApprovalRepository&MockObject $repository;
    private ProcedureApprovalService $service;

    private string $approvalId;
    private ProcedureApproval $approval;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(ProcedureApprovalRepository::class);
        $this->service = new ProcedureApprovalService($this->em, $this->repository);

        $this->approvalId = bin2hex(random_bytes(16));
        $this->approval = new ProcedureApproval(
            $this->approvalId,
            'app-1',
            'APPROVED'
        );
    }

    public function testCreateApprovalSuccess(): void
    {
        $applicationId = 'app-123';
        $decision = 'REJECTED';
        $officerId = 'officer-1';
        $notes = 'Not eligible';

        $captured = null;
        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (ProcedureApproval $a) use (&$captured, $applicationId, $decision, $officerId, $notes) {
                $captured = $a;
                return $captured !== null;
            }));
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->createApproval($applicationId, $decision, $officerId, $notes);
        $this->assertInstanceOf(ProcedureApproval::class, $result);
        $this->assertSame($applicationId, $result->getApplicationId());
        $this->assertSame($decision, $result->getDecision());
        $this->assertSame($officerId, $result->getOfficerId());
        $this->assertSame($notes, $result->getNotes());
        $this->assertSame($captured, $result);
    }

    public function testCreateApprovalValidationEmptyApplicationId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Application ID is required');
        $this->service->createApproval('', 'DECIDE');
    }

    public function testCreateApprovalValidationEmptyDecision(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Decision is required');
        $this->service->createApproval('app', '');
    }

    public function testGetApprovalSuccess(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->approvalId)
            ->willReturn($this->approval);

        $result = $this->service->getApproval($this->approvalId);
        $this->assertSame($this->approval, $result);
    }

    public function testGetApprovalNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->approvalId)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('ProcedureApproval not found');
        $this->service->getApproval($this->approvalId);
    }

    public function testUpdateApprovalSuccess(): void
    {
        $newAppId = 'app-2';
        $newDecision = 'PENDING';
        $newOfficerId = 'officer-2';
        $newNotes = 'Re-evaluate';

        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->approvalId)
            ->willReturn($this->approval);
        $this->em->expects($this->once())->method('flush');

        $data = [
            'applicationId' => $newAppId,
            'decision' => $newDecision,
            'officerId' => $newOfficerId,
            'notes' => $newNotes,
        ];
        $result = $this->service->updateApproval($this->approvalId, $data);
        $this->assertSame($this->approval, $result);
        $this->assertSame($newAppId, $result->getApplicationId());
        $this->assertSame($newDecision, $result->getDecision());
        $this->assertSame($newOfficerId, $result->getOfficerId());
        $this->assertSame($newNotes, $result->getNotes());
    }

    public function testUpdateApprovalValidationEmptyApplicationId(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->approvalId)
            ->willReturn($this->approval);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Application ID cannot be empty');
        $this->service->updateApproval($this->approvalId, ['applicationId' => '']);
    }

    public function testUpdateApprovalValidationEmptyDecision(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->approvalId)
            ->willReturn($this->approval);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Decision cannot be empty');
        $this->service->updateApproval($this->approvalId, ['decision' => '']);
    }

    public function testUpdateApprovalNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->approvalId)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->service->updateApproval($this->approvalId, []);
    }

    public function testDeleteApprovalSuccess(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->approvalId)
            ->willReturn($this->approval);
        $this->em->expects($this->once())->method('remove')->with($this->approval);
        $this->em->expects($this->once())->method('flush');

        $this->service->deleteApproval($this->approvalId);
    }

    public function testDeleteApprovalNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with($this->approvalId)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->service->deleteApproval($this->approvalId);
    }

    public function testFindByApplicationId(): void
    {
        $applicationId = 'app-123';
        $results = [$this->approval];
        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['applicationId' => $applicationId])
            ->willReturn($results);

        $result = $this->service->findByApplicationId($applicationId);
        $this->assertSame($results, $result);
    }

    public function testFindByOfficerId(): void
    {
        $officerId = 'officer-1';
        $results = [$this->approval];
        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['officerId' => $officerId])
            ->willReturn($results);

        $result = $this->service->findByOfficerId($officerId);
        $this->assertSame($results, $result);
    }
}
