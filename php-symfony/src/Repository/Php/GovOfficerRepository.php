<?php

declare(strict_types=1);

namespace App\Repository\Php;

use App\Entity\Php\GovOfficer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Repository\Common\CommonRepositoryTrait;

/**
 * @extends ServiceEntityRepository<GovOfficer>
 */
class GovOfficerRepository extends ServiceEntityRepository
{
    use CommonRepositoryTrait;
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GovOfficer::class);
    }




    public function findByUserId(string $userId): ?GovOfficer
    {
        return $this->findOneBy(['userId' => $userId]);
    }

    public function findByDepartment(string $department): array
    {
        return $this->findBy(['department' => $department]);
    }

    public function findByPosition(string $position): array
    {
        return $this->findBy(['position' => $position]);
    }

    public function findByStatus(string $status): array
    {
        return $this->findBy(['status' => $status]);
    }

    public function findActiveOfficers(): array
    {
        return $this->findBy(['status' => 'ACTIVE']);
    }

    public function findInactiveOfficers(): array
    {
        return $this->findBy(['status' => 'INACTIVE']);
    }

    public function countActive(): int
    {
        return $this->count(['status' => 'ACTIVE']);
    }

    public function countByDepartment(string $department): int
    {
        return $this->count(['department' => $department]);
    }
}
