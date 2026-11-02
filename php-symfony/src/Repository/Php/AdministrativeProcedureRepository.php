<?php

declare(strict_types=1);

namespace App\Repository\Php;

use App\Entity\Php\AdministrativeProcedure;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Repository\Common\CommonRepositoryTrait;

/**
 * @extends ServiceEntityRepository<AdministrativeProcedure>
 */
class AdministrativeProcedureRepository extends ServiceEntityRepository
{
    use CommonRepositoryTrait;
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdministrativeProcedure::class);
    }




    public function findByCode(string $code): ?AdministrativeProcedure
    {
        return $this->findOneBy(['code' => $code]);
    }

    public function findByCategory(string $category): array
    {
        return $this->findBy(['category' => $category]);
    }

    public function findByStatus(string $status): array
    {
        return $this->findBy(['status' => $status]);
    }

    public function findActiveProcedures(): array
    {
        return $this->findBy(['status' => 'ACTIVE']);
    }

    public function findInactiveProcedures(): array
    {
        return $this->findBy(['status' => 'INACTIVE']);
    }

    public function countActive(): int
    {
        return $this->count(['status' => 'ACTIVE']);
    }

    public function countByCategory(string $category): int
    {
        return $this->count(['category' => $category]);
    }
}
