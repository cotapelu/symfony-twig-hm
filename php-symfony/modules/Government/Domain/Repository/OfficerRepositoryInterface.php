<?php

declare(strict_types=1);

namespace App\Government\Domain\Repository;

use App\Government\Domain\Entity\Officer;
use App\Government\Domain\ValueObject\OfficerId;

interface OfficerRepositoryInterface
{
    public function findById(OfficerId $id): ?Officer;
    
    public function findByEmail(string $email): ?Officer;
    
    public function findByIdentityNumber(string $identityNumber): ?Officer;
    
    public function findAll(array $filters = [], int $page = 1, int $perPage = 20): array;
    
    public function save(Officer $officer): void;
    
    public function delete(OfficerId $id): void;
    
    public function count(array $filters = []): int;
}
