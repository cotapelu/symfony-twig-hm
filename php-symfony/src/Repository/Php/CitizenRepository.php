<?php

declare(strict_types=1);

namespace App\Repository\Php;

use App\Entity\Php\Citizen;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CitizenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Citizen::class);
    }

    public function findByIdentityNumber(string $identityNumber): ?Citizen
    {
        return $this->findOneBy(['identityNumber' => $identityNumber]);
    }

    public function findByHouseholdId(string $householdId): array
    {
        return $this->findBy(['householdId' => $householdId]);
    }

    public function findActiveCitizens(): array
    {
        return $this->findBy(['status' => 'ACTIVE']);
    }

    public function searchByName(string $query, int $limit = 20): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.name LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByStatus(string $status): array
    {
        return $this->findBy(['status' => $status]);
    }
}