<?php

declare(strict_types=1);

namespace App\Government\Api\Controller;

use App\Government\Application\Service\DocumentService;
use App\Government\Contracts\DTO\CreateDocumentRequest;
use App\Shared\Controller\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class DocumentController extends BaseController
{
    public function __construct(
        private DocumentService $documentService
    ) {
    }
    
    #[Route('/api/government/documents', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $dataOr400 = $this->decodeJsonOr400($request);
        if ($dataOr400 instanceof JsonResponse) {
            return $dataOr400;
        }
        $data = $dataOr400;

        $createRequest = new CreateDocumentRequest(
            title: $data['title'],
            type: $data['type'],
            content: $data['content'],
            issuedBy: $data['issuedBy'],
            documentNumber: $data['documentNumber'] ?? null,
            issuedDate: $data['issuedDate'] ?? null,
            effectiveDate: $data['effectiveDate'] ?? null,
            signedBy: $data['signedBy'] ?? null,
            summary: $data['summary'] ?? null,
            tags: $data['tags'] ?? []
        );
        
        $response = $this->documentService->createDocument($createRequest);
        
        return $this->json([
            'success' => true,
            'data' => $response,
        ], 201);
    }
    
    #[Route('/api/government/documents', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $page = (int) $request->query->get('page', 1);
        $perPage = (int) $request->query->get('perPage', 20);
        $type = $request->query->get('type');
        $status = $request->query->get('status');
        
        $filters = [];
        if ($type) {
            $filters['type'] = $type;
        }
        if ($status) {
            $filters['status'] = $status;
        }
        
        $documents = $this->documentService->listDocuments($filters, $page, $perPage);
        
        return $this->json([
            'success' => true,
            'data' => $documents,
            'pagination' => [
                'page' => $page,
                'perPage' => $perPage,
            ],
        ]);
    }
    
    #[Route('/api/government/documents/search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $keyword = $request->query->get('q', '');
        $limit = (int) $request->query->get('limit', 20);
        
        if (empty($keyword)) {
            return $this->json([
                'success' => false,
                'error' => 'Search keyword is required',
            ], 400);
        }
        
        $documents = $this->documentService->searchDocuments($keyword, $limit);
        
        return $this->json([
            'success' => true,
            'data' => $documents,
        ]);
    }
    
    #[Route('/api/government/documents/{id}', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        $document = $this->documentService->getDocumentById($id);
        
        if (!$document) {
            return $this->json([
                'success' => false,
                'error' => 'Document not found',
            ], 404);
        }
        
        return $this->json([
            'success' => true,
            'data' => $document,
        ]);
    }
    
    #[Route('/api/government/documents/{id}/publish', methods: ['POST'])]
    public function publish(string $id): JsonResponse
    {
        $result = $this->documentService->publishDocument($id);
        
        if (!$result) {
            return $this->json([
                'success' => false,
                'error' => 'Document not found',
            ], 404);
        }
        
        return $this->json([
            'success' => true,
            'message' => 'Document published successfully',
        ]);
    }
    
    #[Route('/api/government/documents/{id}/archive', methods: ['POST'])]
    public function archive(string $id): JsonResponse
    {
        $result = $this->documentService->archiveDocument($id);
        
        if (!$result) {
            return $this->json([
                'success' => false,
                'error' => 'Document not found',
            ], 404);
        }
        
        return $this->json([
            'success' => true,
            'message' => 'Document archived successfully',
        ]);
    }
    
    #[Route('/api/government/documents/statistics', methods: ['GET'])]
    public function statistics(): JsonResponse
    {
        $stats = $this->documentService->getStatistics();
        
        return $this->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
