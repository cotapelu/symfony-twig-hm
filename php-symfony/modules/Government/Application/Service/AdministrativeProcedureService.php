<?php

declare(strict_types=1);

namespace App\Government\Application\Service;

use App\Government\Contracts\DTO\CreateProcedureRequest;
use App\Government\Contracts\DTO\CreateProcedureApplicationRequest;
use App\Government\Contracts\DTO\ProcedureResponse;
use App\Government\Contracts\DTO\ProcedureApplicationResponse;
use App\Government\Domain\Entity\AdministrativeProcedure;
use App\Government\Domain\Entity\ProcedureApplication;
use App\Government\Domain\Repository\AdministrativeProcedureRepositoryInterface;
use App\Government\Domain\Repository\ProcedureApplicationRepositoryInterface;
use Ramsey\Uuid\Uuid;

class AdministrativeProcedureService
{
    public function __construct(
        private AdministrativeProcedureRepositoryInterface $procedureRepository,
        private ProcedureApplicationRepositoryInterface $applicationRepository
    ) {
    }

    public function createProcedure(CreateProcedureRequest $request): ProcedureResponse
    {
        $procedure = new AdministrativeProcedure(
            $request->name,
            $request->code,
            $request->category,
            $request->description,
            $request->requiredDocuments,
            $request->processingTimeDays,
            $request->fee
        );

        $this->procedureRepository->save($procedure);

        return ProcedureResponse::fromEntity($procedure);
    }

    public function getProcedureById(string $id): ?ProcedureResponse
    {
        $procedure = $this->procedureRepository->findById($id);
        
        if (!$procedure) {
            return null;
        }

        return ProcedureResponse::fromEntity($procedure);
    }

    public function listProcedures(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $procedures = $this->procedureRepository->findAll($filters, $page, $perPage);
        
        return array_map(
            fn(AdministrativeProcedure $p) => ProcedureResponse::fromEntity($p),
            $procedures
        );
    }

    public function activateProcedure(string $id): bool
    {
        $procedure = $this->procedureRepository->findById($id);
        
        if (!$procedure) {
            return false;
        }

        $procedure->activate();
        $this->procedureRepository->save($procedure);
        
        return true;
    }

    public function deactivateProcedure(string $id): bool
    {
        $procedure = $this->procedureRepository->findById($id);
        
        if (!$procedure) {
            return false;
        }

        $procedure->deactivate();
        $this->procedureRepository->save($procedure);
        
        return true;
    }

    public function submitApplication(CreateProcedureApplicationRequest $request): ProcedureApplicationResponse
    {
        $application = new ProcedureApplication(
            $request->citizenId,
            $request->procedureId,
            $request->documents,
            $request->submittedBy,
            $request->notes
        );

        $this->applicationRepository->save($application);

        return ProcedureApplicationResponse::fromEntity($application);
    }

    public function getApplicationById(string $id): ?ProcedureApplicationResponse
    {
        $application = $this->applicationRepository->findById($id);
        
        if (!$application) {
            return null;
        }

        return ProcedureApplicationResponse::fromEntity($application);
    }

    public function listApplications(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $applications = $this->applicationRepository->findAll($filters, $page, $perPage);
        
        return array_map(
            fn(ProcedureApplication $a) => ProcedureApplicationResponse::fromEntity($a),
            $applications
        );
    }

    public function startProcessing(string $applicationId, string $processedBy): bool
    {
        $application = $this->applicationRepository->findById($applicationId);
        
        if (!$application) {
            return false;
        }

        $application->startProcessing($processedBy);
        $this->applicationRepository->save($application);
        
        return true;
    }

    public function approve(string $applicationId, string $result): bool
    {
        $application = $this->applicationRepository->findById($applicationId);
        
        if (!$application) {
            return false;
        }

        $application->approve($result);
        $this->applicationRepository->save($application);
        
        return true;
    }

    public function reject(string $applicationId, string $result): bool
    {
        $application = $this->applicationRepository->findById($applicationId);
        
        if (!$application) {
            return false;
        }

        $application->reject($result);
        $this->applicationRepository->save($application);
        
        return true;
    }

    public function complete(string $applicationId): bool
    {
        $application = $this->applicationRepository->findById($applicationId);
        
        if (!$application) {
            return false;
        }

        $application->complete();
        $this->applicationRepository->save($application);
        
        return true;
    }

    public function getStatistics(): array
    {
        $totalProcedures = $this->procedureRepository->count(['status' => 'active']);
        $totalApplications = $this->applicationRepository->count([]);
        $pendingApplications = $this->applicationRepository->count([
            'status' => ProcedureApplication::STATUS_SUBMITTED
        ]);
        $processingApplications = $this->applicationRepository->count([
            'status' => ProcedureApplication::STATUS_PROCESSING
        ]);
        $approvedApplications = $this->applicationRepository->count([
            'status' => ProcedureApplication::STATUS_APPROVED
        ]);
        $rejectedApplications = $this->applicationRepository->count([
            'status' => ProcedureApplication::STATUS_REJECTED
        ]);

        return [
            'totalProcedures' => $totalProcedures,
            'totalApplications' => $totalApplications,
            'pending' => $pendingApplications,
            'processing' => $processingApplications,
            'approved' => $approvedApplications,
            'rejected' => $rejectedApplications,
            'completionRate' => $totalApplications > 0 
                ? round(($approvedApplications / $totalApplications) * 100, 2) 
                : 0,
        ];
    }
}
