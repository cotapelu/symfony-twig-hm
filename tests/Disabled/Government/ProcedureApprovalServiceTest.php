<?php

declare(strict_types=1);

namespace App\Tests\Unit\Government;

use PHPUnit\Framework\TestCase;
use App\Service\Php\ProcedureApprovalService;
use App\Entity\Php\ProcedureApproval;
use App\Repository\Php\ProcedureApprovalRepository;
use Doctrine\ORM\EntityManagerInterface;

class ProcedureApprovalServiceTest extends TestCase
{
    private EntityManagerInterface $em;
    private ProcedureApprovalRepository $repository;
    private ProcedureApprovalService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(ProcedureApprovalRepository::class);
        $this->service = new ProcedureApprovalService($this->em, $this->repository);
    }

    public function testCreateApproval_Success(): void
    {
        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->createApproval(
            'app-123',
            'APPROVED',
            'officer-456',
            'All documents verified'
        );

        $this->assertInstanceOf(ProcedureApproval::class, $result);
        $this->assertEquals('app-123', $result->getApplicationId());
        $this->assertEquals('APPROVED', $result->getDecision());
        $this->assertEquals('officer-456', $result->getOfficerId());
    }

    public function testCreateApproval_EmptyFields_Throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->createApproval('', 'APPROVED');
    }

    public function testGetApproval_Exists(): void
    {
        $approval = new ProcedureApproval('id-1', 'app-1', 'APPROVED');
        $this->repository->expects($this->once())->method('find')->with('id-1')->willReturn($approval);
        $result = $this->service->getApproval('id-1');
        $this->assertSame($approval, $result);
    }

    public function testGetApproval_NotFound_Throws(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->repository->expects($this->once())->method('find')->with('missing')->willReturn(null);
        $this->service->getApproval('missing');
    }

    public function testUpdateApproval_Success(): void
    {
        $approval = new ProcedureApproval('id-1', 'app-1', 'PENDING');
        $this->repository->expects($this->once())->method('find')->willReturn($approval);
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->updateApproval('id-1', [
            'decision' => 'REJECTED',
            'notes' => 'Incomplete',
        ]);

        $this->assertEquals('REJECTED', $result->getDecision());
        $this->assertEquals('Incomplete', $result->getNotes());
    }

    public function testUpdateApproval_NotFound_Throws(): void
    {
        $this->repository->expects($this->once())->method('find')->with('missing')->willReturn(null);
        $this->expectException(\RuntimeException::class);
        $this->service->updateApproval('missing', ['decision' => 'NEW']);
    }

    public function testDeleteApproval_Success(): void
    {
        $approval = new ProcedureApproval('id-1', 'app-1', 'APPROVED');
        $this->repository->expects($this->once())->method('find')->with('id-1')->willReturn($approval);
        $this->em->expects($this->once())->method('remove')->with($approval);
        $this->em->expects($this->once())->method('flush');

        $this->service->deleteApproval('id-1');
    }

    public function testFindByApplicationId_ReturnsArray(): void
    {
        $approvals = [new ProcedureApproval('id-1', 'app-123', 'APPROVED')];
        $this->repository->expects($this->once())->method('findBy')->with(['applicationId' => 'app-123'])->willReturn($approvals);
        $result = $this->service->findByApplicationId('app-123');
        $this->assertCount(1, $result);
    }

    public function testFindByOfficerId_ReturnsArray(): void
    {
        $approvals = [new ProcedureApproval('id-1', 'app-1', 'APPROVED', 'officer-456')];
        $this->repository->expects($this->once())->method('findBy')->with(['officerId' => 'officer-456'])->willReturn($approvals);
        $result = $this->service->findByOfficerId('officer-456');
        $this->assertCount(1, $result);
    }
}
