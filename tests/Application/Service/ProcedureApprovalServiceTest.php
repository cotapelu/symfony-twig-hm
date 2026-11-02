<?php

declare(strict_types=1);

namespace App\Tests\Application\Service;

use App\Application\Service\ProcedureApprovalService;
use App\Domain\Entity\ProcedureApproval;
use App\Domain\Repository\ProcedureApprovalRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProcedureApprovalServiceTest extends TestCase
{
    private ProcedureApprovalRepositoryInterface&MockObject $procedureApprovalRepository;
    private ProcedureApprovalService $service;

    protected function setUp(): void
    {
        $this->procedureApprovalRepository = $this->createMock(ProcedureApprovalRepositoryInterface::class);
        $this->service = new ProcedureApprovalService($this->procedureApprovalRepository);
    }

    public function testSubmitForReview(): void
    {
        $procedureId = '123e4567-e89b-12d3-a456-426614174000';
        $expectedApproval = new ProcedureApproval(
            $procedureId,
            ProcedureApproval::STATUS_PENDING,
            null,
            'Submitted for review'
        );

        $this->procedureApprovalRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->identicalTo($expectedApproval));

        $this->procedureApprovalRepository
            ->expects($this->once())
            ->method('findById')
            ->with($this->equalTo($procedureId))
            ->willReturn(null);

        $approval = $this->service->submitForReview($procedureId);

        $this->assertInstanceOf(ProcedureApproval::class, $approval);
        $this->assertEquals($procedureId, $approval->getProcedureId());
        $this->assertEquals(ProcedureApproval::STATUS_PENDING, $approval->getStatus());
    }

    public function testApproveProcedure(): void
    {
        $procedureId = '123e4567-e89b-12d3-a456-426614174000';
        $approvedBy = 'officer123';
        $comments = 'Looks good';
        $expectedApproval = new ProcedureApproval(
            $procedureId,
            ProcedureApproval::STATUS_APPROVED,
            $approvedBy,
            $comments
        );

        $this->procedureApprovalRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->identicalTo($expectedApproval));

        $this->procedureApprovalRepository
            ->expects($this->once())
            ->method('findById')
            ->with($this->equalTo($procedureId))
            ->willReturn(null);

        $approval = $this->service->approveProcedure($procedureId, $approvedBy, $comments);

        $this->assertInstanceOf(ProcedureApproval::class, $approval);
        $this->assertEquals($procedureId, $approval->getProcedureId());
        $this->assertEquals(ProcedureApproval::STATUS_APPROVED, $approval->getStatus());
        $this->assertEquals($approvedBy, $approval->getProcessedBy());
        $this->assertEquals($comments, $approval->getComments());
    }

    public function testRejectProcedure(): void
    {
        $procedureId = '123e4567-e89b-12d3-a456-426614174000';
        $rejectedBy = 'officer123';
        $reason = 'Missing documents';
        $expectedApproval = new ProcedureApproval(
            $procedureId,
            ProcedureApproval::STATUS_REJECTED,
            $rejectedBy,
            $reason
        );

        $this->procedureApprovalRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->identicalTo($expectedApproval));

        $this->procedureApprovalRepository
            ->expects($this->once())
            ->method('findById')
            ->with($this->equalTo($procedureId))
            ->willReturn(null);

        $approval = $this->service->rejectProcedure($procedureId, $rejectedBy, $reason);

        $this->assertInstanceOf(ProcedureApproval::class, $approval);
        $this->assertEquals($procedureId, $approval->getProcedureId());
        $this->assertEquals(ProcedureApproval::STATUS_REJECTED, $approval->getStatus());
        $this->assertEquals($rejectedBy, $approval->getProcessedBy());
        $this->assertEquals($reason, $approval->getComments());
    }

    public function testActivateProcedure(): void
    {
        $procedureId = '123e4567-e89b-12d3-a456-426614174000';
        $expectedApproval = new ProcedureApproval(
            $procedureId,
            ProcedureApproval::STATUS_ACTIVE,
            null,
            'Activated'
        );

        $this->procedureApprovalRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->identicalTo($expectedApproval));

        $this->procedureApprovalRepository
            ->expects($this->once())
            ->method('findById')
            ->with($this->equalTo($procedureId))
            ->willReturn(null);

        $approval = $this->service->activateProcedure($procedureId);

        $this->assertInstanceOf(ProcedureApproval::class, $approval);
        $this->assertEquals($procedureId, $approval->getProcedureId());
        $this->assertEquals(ProcedureApproval::STATUS_ACTIVE, $approval->getStatus());
    }

    public function testGetPendingApprovals(): void
    {
        $expectedApprovals = [
            new ProcedureApproval('proc1', ProcedureApproval::STATUS_PENDING),
            new ProcedureApproval('proc2', ProcedureApproval::STATUS_PENDING)
        ];

        $this->procedureApprovalRepository
            ->expects($this->once())
            ->method('findByStatus')
            ->with(
                $this->equalTo(ProcedureApproval::STATUS_PENDING),
                $this->equalTo(1),
                $this->equalTo(20)
            )
            ->willReturn($expectedApprovals);

        $approvals = $this->service->getPendingApprovals(1, 20);

        $this->assertEquals($expectedApprovals, $approvals);
    }

    public function testGetApprovalsByStatus(): void
    {
        $expectedApprovals = [
            new ProcedureApproval('proc1', ProcedureApproval::STATUS_APPROVED),
            new ProcedureApproval('proc2', ProcedureApproval::STATUS_APPROVED)
        ];

        $this->procedureApprovalRepository
            ->expects($this->once())
            ->method('findByStatus')
            ->with(
                $this->equalTo(ProcedureApproval::STATUS_APPROVED),
                $this->equalTo(1),
                $this->equalTo(20)
            )
            ->willReturn($expectedApprovals);

        $approvals = $this->service->getApprovalsByStatus(ProcedureApproval::STATUS_APPROVED, 1, 20);

        $this->assertEquals($expectedApprovals, $approvals);
    }

    public function testGetApprovalStatistics(): void
    {
        $this->procedureApprovalRepository
            ->expects($this->exactly(4))
            ->method('count')
            ->withConsecutive(
                [$this->equalTo(['status' => ProcedureApproval::STATUS_PENDING])],
                [$this->equalTo(['status' => ProcedureApproval::STATUS_APPROVED])],
                [$this->equalTo(['status' => ProcedureApproval::STATUS_REJECTED])],
                [$this->equalTo(['status' => ProcedureApproval::STATUS_ACTIVE])]
            )
            ->willReturnOnConsecutiveCalls(10, 5, 2, 8);

        $stats = $this->service->getApprovalStatistics();

        $this->assertEquals([
            'pending' => 10,
            'approved' => 5,
            'rejected' => 2,
            'active' => 8
        ], $stats);
    }
}