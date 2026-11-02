<?php

declare(strict_types=1);

namespace App\Government\Domain\Service;

use App\Government\Domain\Entity\ProcedureApproval;

interface ProcedureApprovalServiceInterface
{
    public function submitForReview(string $procedureId): ProcedureApproval;
    public function approveProcedure(string $procedureId, ?string $approvedBy = null, ?string $comments = null): ProcedureApproval;
    public function rejectProcedure(string $procedureId, ?string $rejectedBy = null, ?string $reason = null): ProcedureApproval;
    public function activateProcedure(string $procedureId): ProcedureApproval;
    public function getProcedureApprovalHistory(string $procedureId): array;
    public function getPendingApprovals(int $page = 1, int $perPage = 20): array;
    public function getApprovalsByStatus(string $status, int $page = 1, int $perPage = 20): array;
    public function getApprovalStatistics(): array;
}