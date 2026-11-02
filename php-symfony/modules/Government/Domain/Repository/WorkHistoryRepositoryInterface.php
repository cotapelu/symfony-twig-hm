<?php

declare(strict_types=1);

namespace App\Government\Domain\Repository;

use App\Government\Domain\Entity\WorkHistory;
use App\Government\Domain\ValueObject\OfficerId;

interface WorkHistoryRepositoryInterface
{
    public function findById(string $id): ?WorkHistory;
    
    public function findByOfficerId(OfficerId $officerId): array;
    
    public function findCurrentByOfficerId(OfficerId $officerId): ?WorkHistory;
    
    public function save(WorkHistory $workHistory): void;
    
    public function delete(string $id): void;
}
