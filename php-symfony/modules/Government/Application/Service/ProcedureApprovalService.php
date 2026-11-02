<?php

declare(strict_types=1);

namespace App\Government\Application\Service;

use App\Government\Domain\Entity\ProcedureApproval;
use App\Government\Domain\Repository\ProcedureApprovalRepositoryInterface;
use App\Government\Domain\Service\ProcedureApprovalServiceInterface;

class ProcedureApprovalService implements ProcedureApprovalServiceInterface
{
    public function __construct(
        private ProcedureApprovalRepositoryInterface $procedureApprovalRepository
    ) {
    }

    public function submitForReview(string $procedureId): ProcedureApproval
    {
        $approval = new ProcedureApproval(
            $procedureId,
            ProcedureApproval::STATUS_PENDING,
            null,
            'Submitted for review'
        );

        $this->procedureApprovalRepository->save($approval);

        return $approval;
    }

    public function approveProcedure(string $procedureId, ?string $approvedBy = null, ?string $comments = null): ProcedureApproval
    {
        $approval = new ProcedureApproval(
            $procedureId,
            ProcedureApproval::STATUS_APPROVED,
            $approvedBy,
            $comments ?? 'Approved'
        );

        $this->procedureApprovalRepository->save($approval);

        return $approval;
    }

    public function rejectProcedure(string $procedureId, ?string $rejectedBy = null, ?string $reason = null): ProcedureApproval
    {
        $approval = new ProcedureApproval(
            $procedureId,
            ProcedureApproval::STATUS_REJECTED,
            $rejectedBy,
            $reason ?? 'Rejected'
        );

        $this->procedureApprovalRepository->save($approval);

        return $approval;
    }

    public function activateProcedure(string $procedureId): ProcedureApproval
    {
        $approval = new ProcedureApproval(
            $procedureId,
            ProcedureApproval::STATUS_ACTIVE,
            null,
            'Activated'
        );

        $this->procedureApprovalRepository->save($approval);

        return $approval;
    }

    public function getProcedureApprovalHistory(string $procedureId): array
    {
        return $this->procedureApprovalRepository->findByProcedureId($procedureId);
    }

    public function getPendingApprovals(int $page = 1, int $perPage = 20): array
    {
        return $this->procedureApprovalRepository->findByStatus(ProcedureApproval::STATUS_PENDING, $page, $perPage);
    }

    public function getApprovalsByStatus(string $status, int $page = 1, int $perPage = 20): array
    {
        return $this->procedureApprovalRepository->findByStatus($status, $page, $perPage);
    }

    public function getApprovalStatistics(): array
    {
        return [
            'pending' => $this->procedureApprovalRepository->count(['status' => ProcedureApproval::STATUS_PENDING]),
            'approved' => $this->procedureApprovalRepository->count(['status' => ProcedureApproval::STATUS_APPROVED]),
            'rejected' => $this->procedureApprovalRepository->count(['status' => ProcedureApproval::STATUS_REJECTED]),
            'active' => $this->procedureApprovalRepository->count(['status' => ProcedureApproval::STATUS_ACTIVE]),
        ];
    }
}