<?php

declare(strict_types=1);

namespace App\Government\Application\Service;

use App\Government\Contracts\DTO\ProcedureRequestResponse;
use App\Government\Domain\Entity\ProcedureRequest;
use App\Government\Domain\Repository\ProcedureRequestRepositoryInterface;
use App\Government\Domain\Repository\AdministrativeProcedureRepositoryInterface;

class ProcedureRequestService
{
    public function __construct(
        private ProcedureRequestRepositoryInterface $requestRepository,
        private AdministrativeProcedureRepositoryInterface $procedureRepository
    ) {
    }

    public function createRequest(string $citizenId, string $procedureId): ProcedureRequestResponse
    {
        $procedure = $this->procedureRepository->findById($procedureId);
        if (!$procedure) {
            throw new \InvalidArgumentException('Procedure not found');
        }

        if ($procedure->getStatus() !== 'active') {
            throw new \InvalidArgumentException('Procedure is not active');
        }

        $request = new ProcedureRequest(
            $citizenId,
            $procedureId,
            $procedure->getProcessingTimeDays()
        );

        $this->requestRepository->save($request);

        return ProcedureRequestResponse::fromEntity($request);
    }

    public function getRequestById(string $id): ?ProcedureRequestResponse
    {
        $request = $this->requestRepository->findById($id);
        if (!$request) {
            return null;
        }

        return ProcedureRequestResponse::fromEntity($request);
    }

    public function listRequestsByCitizen(string $citizenId, array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $requests = $this->requestRepository->findByCitizen($citizenId, $filters, $page, $perPage);
        return array_map(
            fn(ProcedureRequest $r) => ProcedureRequestResponse::fromEntity($r),
            $requests
        );
    }

    public function listRequestsByOfficer(string $officerId, array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $requests = $this->requestRepository->findByOfficer($officerId, $filters, $page, $perPage);
        return array_map(
            fn(ProcedureRequest $r) => ProcedureRequestResponse::fromEntity($r),
            $requests
        );
    }

    public function assignOfficer(string $requestId, string $officerId): void
    {
        $request = $this->requestRepository->findById($requestId);
        if (!$request) {
            throw new \InvalidArgumentException('Request not found');
        }

        $request->assignOfficer($officerId);
        $request->transitionStatus(ProcedureRequest::STATUS_PROCESSING);
        $this->requestRepository->save($request);
    }

    public function transitionStatus(string $requestId, string $newStatus): void
    {
        $request = $this->requestRepository->findById($requestId);
        if (!$request) {
            throw new \InvalidArgumentException('Request not found');
        }

        $request->transitionStatus($newStatus);
        $this->requestRepository->save($request);
    }

    public function getOverdueRequests(): array
    {
        $requests = $this->requestRepository->findOverdue();
        return array_map(
            fn(ProcedureRequest $r) => ProcedureRequestResponse::fromEntity($r),
            $requests
        );
    }

    public function getDueSoonRequests(int $days = 3): array
    {
        $requests = $this->requestRepository->findDueWithinDays($days);
        return array_map(
            fn(ProcedureRequest $r) => ProcedureRequestResponse::fromEntity($r),
            $requests
        );
    }

    public function getStatistics(): array
    {
        return [
            'total' => $this->requestRepository->count(),
            'submitted' => $this->requestRepository->countByStatus(ProcedureRequest::STATUS_SUBMITTED),
            'processing' => $this->requestRepository->countByStatus(ProcedureRequest::STATUS_PROCESSING),
            'pending_docs' => $this->requestRepository->countByStatus(ProcedureRequest::STATUS_PENDING_DOCS),
            'approved' => $this->requestRepository->countByStatus(ProcedureRequest::STATUS_APPROVED),
            'rejected' => $this->requestRepository->countByStatus(ProcedureRequest::STATUS_REJECTED),
            'completed' => $this->requestRepository->countByStatus(ProcedureRequest::STATUS_COMPLETED),
            'overdue' => $this->requestRepository->countOverdue(),
        ];
    }
}
