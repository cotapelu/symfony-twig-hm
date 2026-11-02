<?php

declare(strict_types=1);

namespace App\Government\Application\Service;

use App\Government\Domain\Entity\Document;
use App\Government\Domain\Entity\DocumentWorkflow;
use App\Government\Domain\Repository\DocumentRepositoryInterface;
use App\Government\Domain\Repository\DocumentWorkflowRepositoryInterface;
use App\Government\Domain\Service\DocumentWorkflowServiceInterface;

class DocumentWorkflowService implements DocumentWorkflowServiceInterface
{
    public function __construct(
        private DocumentRepositoryInterface $documentRepository,
        private DocumentWorkflowRepositoryInterface $documentWorkflowRepository
    ) {
    }

    public function submitForReview(string $documentId): DocumentWorkflow
    {
        $document = $this->documentRepository->findById($documentId);
        if (!$document) {
            throw new \InvalidArgumentException('Document not found');
        }

        $document->submitForReview();
        $this->documentRepository->save($document);

        $workflow = new DocumentWorkflow(
            $documentId,
            DocumentWorkflow::STATUS_PENDING,
            null,
            'Submitted for review'
        );

        $this->documentWorkflowRepository->save($workflow);

        return $workflow;
    }

    public function approveDocument(string $documentId, ?string $approvedBy = null, ?string $comments = null): DocumentWorkflow
    {
        $document = $this->documentRepository->findById($documentId);
        if (!$document) {
            throw new \InvalidArgumentException('Document not found');
        }

        $document->approve($approvedBy, $comments);
        $this->documentRepository->save($document);

        $workflow = new DocumentWorkflow(
            $documentId,
            DocumentWorkflow::STATUS_APPROVED,
            $approvedBy,
            $comments ?? 'Approved'
        );

        $this->documentWorkflowRepository->save($workflow);

        return $workflow;
    }

    public function rejectDocument(string $documentId, ?string $rejectedBy = null, ?string $reason = null): DocumentWorkflow
    {
        $document = $this->documentRepository->findById($documentId);
        if (!$document) {
            throw new \InvalidArgumentException('Document not found');
        }

        $document->reject($rejectedBy, $reason);
        $this->documentRepository->save($document);

        $workflow = new DocumentWorkflow(
            $documentId,
            DocumentWorkflow::STATUS_REJECTED,
            $rejectedBy,
            $reason ?? 'Rejected'
        );

        $this->documentWorkflowRepository->save($workflow);

        return $workflow;
    }

    public function publishDocument(string $documentId): DocumentWorkflow
    {
        $document = $this->documentRepository->findById($documentId);
        if (!$document) {
            throw new \InvalidArgumentException('Document not found');
        }

        $document->publish();
        $this->documentRepository->save($document);

        $workflow = new DocumentWorkflow(
            $documentId,
            DocumentWorkflow::STATUS_PUBLISHED,
            null,
            'Published'
        );

        $this->documentWorkflowRepository->save($workflow);

        return $workflow;
    }

    public function archiveDocument(string $documentId): DocumentWorkflow
    {
        $document = $this->documentRepository->findById($documentId);
        if (!$document) {
            throw new \InvalidArgumentException('Document not found');
        }

        $document->archive();
        $this->documentRepository->save($document);

        $workflow = new DocumentWorkflow(
            $documentId,
            DocumentWorkflow::STATUS_ARCHIVED,
            null,
            'Archived'
        );

        $this->documentWorkflowRepository->save($workflow);

        return $workflow;
    }

    public function getDocumentHistory(string $documentId): array
    {
        // We need to find workflows by documentId. Let's add a method to the repository for this.
        // For now, we'll return an empty array and later implement the repository method.
        return [];
    }

    public function getPendingDocuments(int $page = 1, int $perPage = 20): array
    {
        return $this->documentWorkflowRepository->findByStatus(DocumentWorkflow::STATUS_PENDING, $page, $perPage);
    }

    public function getDocumentsByStatus(string $status, int $page = 1, int $perPage = 20): array
    {
        return $this->documentWorkflowRepository->findByStatus($status, $page, $perPage);
    }

    public function getDocumentStatistics(): array
    {
        return [
            'pending' => $this->documentWorkflowRepository->count(['status' => DocumentWorkflow::STATUS_PENDING]),
            'approved' => $this->documentWorkflowRepository->count(['status' => DocumentWorkflow::STATUS_APPROVED]),
            'rejected' => $this->documentWorkflowRepository->count(['status' => DocumentWorkflow::STATUS_REJECTED]),
            'published' => $this->documentWorkflowRepository->count(['status' => DocumentWorkflow::STATUS_PUBLISHED]),
            'archived' => $this->documentWorkflowRepository->count(['status' => DocumentWorkflow::STATUS_ARCHIVED]),
        ];
    }
}