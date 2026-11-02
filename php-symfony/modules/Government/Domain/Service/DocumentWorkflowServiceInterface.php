<?php

declare(strict_types=1);

namespace App\Government\Domain\Service;

use App\Government\Domain\Entity\DocumentWorkflow;

interface DocumentWorkflowServiceInterface
{
    public function submitForReview(string $documentId): DocumentWorkflow;
    public function approveDocument(string $documentId, ?string $approvedBy = null, ?string $comments = null): DocumentWorkflow;
    public function rejectDocument(string $documentId, ?string $rejectedBy = null, ?string $reason = null): DocumentWorkflow;
    public function publishDocument(string $documentId): DocumentWorkflow;
    public function archiveDocument(string $documentId): DocumentWorkflow;
    public function getDocumentHistory(string $documentId): array;
    public function getPendingDocuments(int $page = 1, int $perPage = 20): array;
    public function getDocumentsByStatus(string $status, int $page = 1, int $perPage = 20): array;
    public function getDocumentStatistics(): array;
}