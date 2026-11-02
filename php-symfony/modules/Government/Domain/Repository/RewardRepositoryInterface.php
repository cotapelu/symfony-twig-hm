<?php

declare(strict_types=1);

namespace App\Government\Domain\Repository;

use App\Government\Domain\Entity\Reward;
use App\Government\Domain\ValueObject\OfficerId;

interface RewardRepositoryInterface
{
    public function findById(string $id): ?Reward;
    
    public function findByOfficerId(OfficerId $officerId, ?string $type = null): array;
    
    public function findAll(array $filters = [], int $page = 1, int $perPage = 20): array;
    
    public function save(Reward $reward): void;
    
    public function delete(string $id): void;
    
    public function countByOfficerAndType(OfficerId $officerId, string $type): int;
}
