<?php

declare(strict_types=1);

namespace App\Government\Domain\Repository;

use App\Government\Domain\Entity\Document;

interface DocumentRepositoryInterface
{
    public function save(Document $document): void;
    
    public function findById(string $id): ?Document;
    
    public function findAll(array $filters = [], int $page = 1, int $perPage = 20): array;
    
    public function count(array $filters = []): int;
    
    public function delete(string $id): bool;
    
    public function searchByTitle(string $keyword, int $limit = 20): array;
    
    public function findByType(string $type, int $page = 1, int $perPage = 20): array;
    
    public function findByStatus(string $status, int $page = 1, int $perPage = 20): array;
}
