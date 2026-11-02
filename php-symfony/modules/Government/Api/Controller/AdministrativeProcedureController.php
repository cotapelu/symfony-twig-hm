<?php

declare(strict_types=1);

namespace App\Government\Api\Controller;

use App\Government\Application\Service\AdministrativeProcedureService;
use App\Government\Contracts\DTO\CreateProcedureRequest;
use App\Government\Contracts\DTO\CreateProcedureApplicationRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Shared\Controller\BaseController;

#[Route('/api/government/procedures')]
class AdministrativeProcedureController extends BaseController
{
    public function __construct(
        private AdministrativeProcedureService $procedureService
    ) {
        parent::__construct();
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $dataOr400 = $this->decodeJsonOr400($request);
        if ($dataOr400 instanceof JsonResponse) {
            return $dataOr400;
        }
        $data = $dataOr400;

        $createRequest = new CreateProcedureRequest(
            name: $data['name'],
            code: $data['code'],
            category: $data['category'],
            description: $data['description'] ?? '',
            requiredDocuments: $data['requiredDocuments'] ?? [],
            processingTimeDays: $data['processingTimeDays'] ?? 1,
            fee: $data['fee'] ?? 0.0
        );

        $procedure = $this->procedureService->createProcedure($createRequest);

        return new JsonResponse($procedure->toArray(), Response::HTTP_CREATED);
    }

    #[Route('', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $filters = [];
        
        if ($request->query->has('category')) {
            $filters['category'] = $request->query->get('category');
        }
        if ($request->query->has('status')) {
            $filters['status'] = $request->query->get('status');
        }
        if ($request->query->has('search')) {
            $filters['search'] = $request->query->get('search');
        }

        $page = (int) $request->query->get('page', 1);
        $perPage = (int) $request->query->get('perPage', 20);

        $procedures = $this->procedureService->listProcedures($filters, $page, $perPage);
        $procedureArrays = array_map(fn($p) => $p->toArray(), $procedures);

        return new JsonResponse([
            'data' => $procedureArrays,
            'page' => $page,
            'perPage' => $perPage
        ]);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function get(string $id): JsonResponse
    {
        $procedure = $this->procedureService->getProcedureById($id);

        if (!$procedure) {
            return new JsonResponse(['error' => 'Procedure not found'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($procedure->toArray());
    }

    #[Route('/{id}/activate', methods: ['POST'])]
    public function activate(string $id): JsonResponse
    {
        $success = $this->procedureService->activateProcedure($id);
        
        if (!$success) {
            return new JsonResponse(['error' => 'Procedure not found'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(['message' => 'Procedure activated successfully']);
    }

    #[Route('/{id}/deactivate', methods: ['POST'])]
    public function deactivate(string $id): JsonResponse
    {
        $success = $this->procedureService->deactivateProcedure($id);
        
        if (!$success) {
            return new JsonResponse(['error' => 'Procedure not found'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(['message' => 'Procedure deactivated successfully']);
    }

    #[Route('/applications', methods: ['POST'])]
    public function submitApplication(Request $request): JsonResponse
    {
        $dataOr400 = $this->decodeJsonOr400($request);
        if ($dataOr400 instanceof JsonResponse) {
            return $dataOr400;
        }
        $data = $dataOr400;

        $applicationRequest = new CreateProcedureApplicationRequest(
            citizenId: $data['citizenId'],
            procedureId: $data['procedureId'],
            documents: $data['documents'] ?? [],
            submittedBy: $data['submittedBy'],
            notes: $data['notes'] ?? null
        );

        $application = $this->procedureService->submitApplication($applicationRequest);

        return new JsonResponse($application->toArray(), Response::HTTP_CREATED);
    }

    #[Route('/applications', methods: ['GET'])]
    public function listApplications(Request $request): JsonResponse
    {
        $filters = [];
        
        if ($request->query->has('citizenId')) {
            $filters['citizenId'] = $request->query->get('citizenId');
        }
        if ($request->query->has('procedureId')) {
            $filters['procedureId'] = $request->query->get('procedureId');
        }
        if ($request->query->has('status')) {
            $filters['status'] = $request->query->get('status');
        }

        $page = (int) $request->query->get('page', 1);
        $perPage = (int) $request->query->get('perPage', 20);

        $applications = $this->procedureService->listApplications($filters, $page, $perPage);
        $applicationArrays = array_map(fn($a) => $a->toArray(), $applications);

        return new JsonResponse([
            'data' => $applicationArrays,
            'page' => $page,
            'perPage' => $perPage
        ]);
    }

    #[Route('/applications/{id}', methods: ['GET'])]
    public function getApplication(string $id): JsonResponse
    {
        $application = $this->procedureService->getApplicationById($id);

        if (!$application) {
            return new JsonResponse(['error' => 'Application not found'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($application->toArray());
    }

    #[Route('/applications/{id}/start', methods: ['POST'])]
    public function startProcessing(string $id, Request $request): JsonResponse
    {
        $dataOr400 = $this->decodeJsonOr400($request);
        if ($dataOr400 instanceof JsonResponse) {
            return $dataOr400;
        }
        $data = $dataOr400;
        $processedBy = $data['processedBy'] ?? 'system';

        $success = $this->procedureService->startProcessing($id, $processedBy);
        
        if (!$success) {
            return new JsonResponse(['error' => 'Application not found'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(['message' => 'Processing started']);
    }

    #[Route('/applications/{id}/approve', methods: ['POST'])]
    public function approve(string $id, Request $request): JsonResponse
    {
        $dataOr400 = $this->decodeJsonOr400($request);
        if ($dataOr400 instanceof JsonResponse) {
            return $dataOr400;
        }
        $data = $dataOr400;
        $result = $data['result'] ?? 'Approved';

        $success = $this->procedureService->approve($id, $result);
        
        if (!$success) {
            return new JsonResponse(['error' => 'Application not found'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(['message' => 'Application approved']);
    }

    #[Route('/applications/{id}/reject', methods: ['POST'])]
    public function reject(string $id, Request $request): JsonResponse
    {
        $dataOr400 = $this->decodeJsonOr400($request);
        if ($dataOr400 instanceof JsonResponse) {
            return $dataOr400;
        }
        $data = $dataOr400;
        $result = $data['result'] ?? 'Rejected';

        $success = $this->procedureService->reject($id, $result);
        
        if (!$success) {
            return new JsonResponse(['error' => 'Application not found'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(['message' => 'Application rejected']);
    }

    #[Route('/applications/{id}/complete', methods: ['POST'])]
    public function complete(string $id): JsonResponse
    {
        $success = $this->procedureService->complete($id);
        
        if (!$success) {
            return new JsonResponse(['error' => 'Application not found'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(['message' => 'Application completed']);
    }

    #[Route('/statistics', methods: ['GET'])]
    public function statistics(): JsonResponse
    {
        $stats = $this->procedureService->getStatistics();
        return new JsonResponse($stats);
    }
}
