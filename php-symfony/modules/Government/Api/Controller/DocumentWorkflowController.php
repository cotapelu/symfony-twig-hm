<?php

declare(strict_types=1);

namespace App\Government\Api\Controller;

use App\Government\Application\Service\DocumentWorkflowService;
use App\Government\Domain\Entity\DocumentWorkflow;
use App\Shared\Controller\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/government/document-workflows')]
class DocumentWorkflowController extends BaseController
{
    public function __construct(
        private DocumentWorkflowService $documentWorkflowService
    ) {
    }

    #[Route('/{id}/submit', methods: ['POST'])]
    public function submitForReview(string $id): JsonResponse
    {
        try {
            $documentWorkflow = $this->documentWorkflowService->submitForReview($id);

            return $this->json([
                'success' => true,
                'message' => 'Document submitted for review',
                'data' => $documentWorkflow->toArray()
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
            $documentWorkflow = $this->documentWorkflowService->approveDocument($id, $data['approvedBy'], $data['comments'] ?? null);
            return $this->json(['success'=>true,'message'=>'Document approved successfully','data'=>$documentWorkflow->toArray()], 200);
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
            $documentWorkflow = $this->documentWorkflowService->rejectDocument($id, $data['rejectedBy'] ?? null, $data['reason']);
            return $this->json(['success'=>true,'message'=>'Document rejected','data'=>$documentWorkflow->toArray()], 200);
        } catch (\Exception $e) {
            return $this->json(['success'=>false,'error'=>$e->getMessage()], 400);
        }
    }

    #[Route('/{id}/publish', methods: ['POST'])]
    public function publish(string $id): JsonResponse
    {
        try {
            $documentWorkflow = $this->documentWorkflowService->publishDocument($id);

            return $this->json([
                'success' => true,
                'message' => 'Document published successfully',
                'data' => $documentWorkflow->toArray()
            ], 200);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    #[Route('/{id}/archive', methods: ['POST'])]
    public function archive(string $id): JsonResponse
    {
        try {
            $documentWorkflow = $this->documentWorkflowService->archiveDocument($id);

            return $this->json([
                'success' => true,
                'message' => 'Document archived successfully',
                'data' => $documentWorkflow->toArray()
            ], 200);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    #[Route('/pending', methods: ['GET'])]
    public function getPendingDocuments(Request $request): JsonResponse
    {
        $page = (int) $request->query->get('page', 1);
        $perPage = (int) $request->query->get('perPage', 20);

        $documentWorkflows = $this->documentWorkflowService->getPendingDocuments($page, $perPage);

        return $this->json([
            'success' => true,
            'data' => array_map(fn($dw) => $dw->toArray(), $documentWorkflows),
            'pagination' => [
                'page' => $page,
                'perPage' => $perPage,
                'total' => $this->documentWorkflowService->count(['status' => DocumentWorkflow::STATUS_PENDING])
            ]
        ], 200);
    }

    #[Route('/by-status/{status}', methods: ['GET'])]
    public function getDocumentsByStatus(string $status, Request $request): JsonResponse
    {
        $page = (int) $request->query->get('page', 1);
        $perPage = (int) $request->query->get('perPage', 20);

        $documentWorkflows = $this->documentWorkflowService->getDocumentsByStatus($status, $page, $perPage);

        return $this->json([
            'success' => true,
            'data' => array_map(fn($dw) => $dw->toArray(), $documentWorkflows),
            'pagination' => [
                'page' => $page,
                'perPage' => $perPage,
                'total' => $this->documentWorkflowService->count(['status' => $status])
            ]
        ], 200);
    }

    #[Route('/{id}/history', methods: ['GET'])]
    public function getDocumentHistory(string $id): JsonResponse
    {
        try {
            $history = $this->documentWorkflowService->getDocumentHistory($id);

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
    public function getDocumentStatistics(): JsonResponse
    {
        try {
            $stats = $this->documentWorkflowService->getDocumentStatistics();

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