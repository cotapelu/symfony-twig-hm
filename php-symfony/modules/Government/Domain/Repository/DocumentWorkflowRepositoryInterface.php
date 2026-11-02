<?php

declare(strict_types=1);

namespace App\Government\Domain\Repository;

use App\Government\Domain\Entity\DocumentWorkflow;

interface DocumentWorkflowRepositoryInterface
{
    public function findById(string $id): ?DocumentWorkflow;
    public function findByDocumentId(string $documentId): array;
    public function findAll(array $filters = [], int $page = 1, int $perPage = 20): array;
    public function save(DocumentWorkflow $workflow): void;
    public function delete(string $id): bool;
    public function count(array $filters = []): int;
    public function searchByTitle(string $keyword, int $limit = 20): array;
    public function findByType(string $type, int $page = 1, int $perPage = 20): array;
    public function findByStatus(string $status, int $page = 1, int $perPage = 20): array;
}