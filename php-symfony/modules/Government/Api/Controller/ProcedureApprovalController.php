<?php

declare(strict_types=1);

namespace App\Government\Api\Controller;

use App\Government\Application\Service\ProcedureApprovalService;
use App\Government\Domain\Entity\ProcedureApproval;
use App\Shared\Controller\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/government/procedure-approvals')]
class ProcedureApprovalController extends BaseController
{
    public function __construct(
        private ProcedureApprovalService $procedureApprovalService
    ) {
    }

    #[Route('/{id}/submit', methods: ['POST'])]
    public function submitForReview(string $id): JsonResponse
    {
        try {
            $procedureApproval = $this->procedureApprovalService->submitForReview($id);

            return $this->json([
                'success' => true,
                'message' => 'Procedure submitted for review',
                'data' => $procedureApproval->toArray()
            ], 200);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    #[Route('/{id}/approve', methods: ['POST'])]
    public function approve(string $id, Request $request): JsonResponse
    {
        $dataOr400 = $this->decodeJsonOr400($request);
        if ($dataOr400 instanceof JsonResponse) return $dataOr400;
        $data = $dataOr400;
        if (empty($data['approvedBy'])) {
            return $this->json(['success'=>false,'error'=>'Approved by is required'], 400);
        }
        try {
            $procedureApproval = $this->procedureApprovalService->approveProcedure($id, $data['approvedBy'], $data['comments'] ?? null);
            return $this->json(['success'=>true,'message'=>'Procedure approved successfully','data'=>$procedureApproval->toArray()], 200);
        } catch (\Exception $e) {
            return $this->json(['success'=>false,'error'=>$e->getMessage()], 400);
        }
    }

    #[Route('/{id}/reject', methods: ['POST'])]
    public function reject(string $id, Request $request): JsonResponse
    {
        $dataOr400 = $this->decodeJsonOr400($request);
        if ($dataOr400 instanceof JsonResponse) return $dataOr400;
        $data = $dataOr400;
        if (empty($data['reason'])) {
            return $this->json(['success'=>false,'error'=>'Rejection reason is required'], 400);
        }
        try {
            $procedureApproval = $this->procedureApprovalService->rejectProcedure($id, $data['rejectedBy'] ?? null, $data['reason']);
            return $this->json(['success'=>true,'message'=>'Procedure rejected','data'=>$procedureApproval->toArray()], 200);
        } catch (\Exception $e) {
            return $this->json(['success'=>false,'error'=>$e->getMessage()], 400);
        }
    }

    #[Route('/{id}/activate', methods: ['POST'])]
    public function activate(string $id): JsonResponse
    {
        try {
            $procedureApproval = $this->procedureApprovalService->activateProcedure($id);

            return $this->json([
                'success' => true,
                'message' => 'Procedure activated successfully',
                'data' => $procedureApproval->toArray()
            ], 200);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    #[Route('/pending', methods: ['GET'])]
    public function getPendingApprovals(Request $request): JsonResponse
    {
        $page = (int) $request->query->get('page', 1);
        $perPage = (int) $request->query->get('perPage', 20);

        $procedureApprovals = $this->procedureApprovalService->getPendingApprovals($page, $perPage);

        return $this->json([
            'success' => true,
            'data' => array_map(fn($pa) => $pa->toArray(), $procedureApprovals),
            'pagination' => [
                'page' => $page,
                'perPage' => $perPage,
                'total' => $this->procedureApprovalService->count(['status' => ProcedureApproval::STATUS_PENDING])
            ]
        ], 200);
    }

    #[Route('/by-status/{status}', methods: ['GET'])]
    public function getApprovalsByStatus(string $status, Request $request): JsonResponse
    {
        $page = (int) $request->query->get('page', 1);
        $perPage = (int) $request->query->get('perPage', 20);

        $procedureApprovals = $this->procedureApprovalService->getApprovalsByStatus($status, $page, $perPage);

        return $this->json([
            'success' => true,
            'data' => array_map(fn($pa) => $pa->toArray(), $procedureApprovals),
            'pagination' => [
                'page' => $page,
                'perPage' => $perPage,
                'total' => $this->procedureApprovalService->count(['status' => $status])
            ]
        ], 200);
    }

    #[Route('/{id}/history', methods: ['GET'])]
    public function getProcedureApprovalHistory(string $id): JsonResponse
    {
        try {
            $history = $this->procedureApprovalService->getProcedureApprovalHistory($id);

            return $this->json([
                'success' => true,
                'data' => $history
            ], 200);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 404);
        }
    }

    #[Route('/statistics', methods: ['GET'])]
    public function getProcedureApprovalStatistics(): JsonResponse
    {
        try {
            $stats = $this->procedureApprovalService->getApprovalStatistics();

            return $this->json([
                'success' => true,
                'data' => $stats
            ], 200);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}