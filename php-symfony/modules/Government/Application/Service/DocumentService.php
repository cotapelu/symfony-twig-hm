<?php

declare(strict_types=1);

namespace App\Government\Application\Service;

use App\Government\Contracts\DTO\CreateDocumentRequest;
use App\Government\Contracts\DTO\DocumentResponse;
use App\Government\Domain\Entity\Document;
use App\Government\Domain\Repository\DocumentRepositoryInterface;

class DocumentService
{
    public function __construct(
        private DocumentRepositoryInterface $documentRepository
    ) {
    }
    
    public function createDocument(CreateDocumentRequest $request): DocumentResponse
    {
        $document = new Document(
            $request->title,
            $request->type,
            $request->content,
            $request->issuedBy,
            $request->documentNumber,
            $request->issuedDate ? new \DateTimeImmutable($request->issuedDate) : null,
            $request->effectiveDate ? new \DateTimeImmutable($request->effectiveDate) : null,
            $request->signedBy,
            $request->summary
        );
        
        foreach ($request->tags as $tag) {
            $document->addTag($tag);
        }
        
        $this->documentRepository->save($document);
        
        return DocumentResponse::fromEntity($document);
    }
    
    public function getDocumentById(string $id): ?DocumentResponse
    {
        $document = $this->documentRepository->findById($id);
        
        if (!$document) {
            return null;
        }
        
        return DocumentResponse::fromEntity($document);
    }
    
    public function listDocuments(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $documents = $this->documentRepository->findAll($filters, $page, $perPage);
        
        return array_map(
            fn(Document $d) => DocumentResponse::fromEntity($d),
            $documents
        );
    }
    
    public function searchDocuments(string $keyword, int $limit = 20): array
    {
        $documents = $this->documentRepository->searchByTitle($keyword, $limit);
        
        return array_map(
            fn(Document $d) => DocumentResponse::fromEntity($d),
            $documents
        );
    }
    
    public function publishDocument(string $id): bool
    {
        $document = $this->documentRepository->findById($id);
        
        if (!$document) {
            return false;
        }
        
        $document->publish();
        $this->documentRepository->save($document);
        
        return true;
    }
    
    public function archiveDocument(string $id): bool
    {
        $document = $this->documentRepository->findById($id);
        
        if (!$document) {
            return false;
        }
        
        $document->archive();
        $this->documentRepository->save($document);
        
        return true;
    }
    
    public function updateDocument(string $id, array $data): ?DocumentResponse
    {
        $document = $this->documentRepository->findById($id);
        
        if (!$document) {
            return null;
        }
        
        if (isset($data['title'])) {
            $document->setTitle($data['title']);
        }
        if (isset($data['content'])) {
            $document->setContent($data['content']);
        }
        if (isset($data['summary'])) {
            $document->setSummary($data['summary']);
        }
        if (isset($data['tags']) && is_array($data['tags'])) {
            foreach ($data['tags'] as $tag) {
                $document->addTag($tag);
            }
        }
        
        $this->documentRepository->save($document);
        
        return DocumentResponse::fromEntity($document);
    }
    
    public function addAttachment(string $id, string $fileName, string $filePath, string $mimeType): bool
    {
        $document = $this->documentRepository->findById($id);
        
        if (!$document) {
            return false;
        }
        
        $document->addAttachment($fileName, $filePath, $mimeType);
        $this->documentRepository->save($document);
        
        return true;
    }
    
    public function getStatistics(): array
    {
        $totalDocuments = $this->documentRepository->count([]);
        $publishedDocuments = $this->documentRepository->count(['status' => Document::STATUS_PUBLISHED]);
        $draftDocuments = $this->documentRepository->count(['status' => Document::STATUS_DRAFT]);
        $archivedDocuments = $this->documentRepository->count(['status' => Document::STATUS_ARCHIVED]);
        
        return [
            'totalDocuments' => $totalDocuments,
            'published' => $publishedDocuments,
            'drafts' => $draftDocuments,
            'archived' => $archivedDocuments,
        ];
    }

    // Workflow methods
    public function submitForReview(string $id): DocumentResponse
    {
        $document = $this->documentRepository->findById($id);
        
        if (!$document) {
            throw new \InvalidArgumentException('Document not found');
        }
        
        $document->submitForReview();
        $this->documentRepository->save($document);
        
        return DocumentResponse::fromEntity($document);
    }

    public function reviewDocument(string $id, string $status, ?string $comments = null, ?string $reviewerId = null): DocumentResponse
    {
        $document = $this->documentRepository->findById($id);
        
        if (!$document) {
            throw new \InvalidArgumentException('Document not found');
        }

        if ($status === 'approved') {
            $document->approve($reviewerId, $comments);
        } else {
            $document->reject($reviewerId, $comments);
        }
        
        $this->documentRepository->save($document);
        
        return DocumentResponse::fromEntity($document);
    }

    public function approveDocument(string $id, ?string $approvedBy = null, ?string $comments = null): DocumentResponse
    {
        $document = $this->documentRepository->findById($id);
        
        if (!$document) {
            throw new \InvalidArgumentException('Document not found');
        }
        
        $document->approve($approvedBy, $comments);
        $this->documentRepository->save($document);
        
        return DocumentResponse::fromEntity($document);
    }

    public function rejectDocument(string $id, string $reason, ?string $rejectedBy = null): DocumentResponse
    {
        $document = $this->documentRepository->findById($id);
        
        if (!$document) {
            throw new \InvalidArgumentException('Document not found');
        }
        
        $document->reject($rejectedBy, $reason);
        $this->documentRepository->save($document);
        
        return DocumentResponse::fromEntity($document);
    }

    public function getPendingDocuments(int $page = 1, int $perPage = 20): array
    {
        return $this->documentRepository->findByStatus(Document::STATUS_PENDING, $page, $perPage);
    }

    public function getDocumentsByStatus(string $status, int $page = 1, int $perPage = 20): array
    {
        $documents = $this->documentRepository->findByStatus($status, $page, $perPage);
        
        return array_map(
            fn(Document $d) => DocumentResponse::fromEntity($d),
            $documents
        );
    }

    public function getDocumentStatistics(): array
    {
        return [
            'total' => $this->documentRepository->count([]),
            'draft' => $this->documentRepository->count(['status' => Document::STATUS_DRAFT]),
            'pending' => $this->documentRepository->count(['status' => Document::STATUS_PENDING]),
            'approved' => $this->documentRepository->count(['status' => Document::STATUS_APPROVED]),
            'rejected' => $this->documentRepository->count(['status' => Document::STATUS_REJECTED]),
            'published' => $this->documentRepository->count(['status' => Document::STATUS_PUBLISHED]),
            'archived' => $this->documentRepository->count(['status' => Document::STATUS_ARCHIVED]),
        ];
    }

    public function getDocumentHistory(string $id): array
    {
        $document = $this->documentRepository->findById($id);
        
        if (!$document) {
            throw new \InvalidArgumentException('Document not found');
        }
        
        return $document->getHistory();
    }
}
