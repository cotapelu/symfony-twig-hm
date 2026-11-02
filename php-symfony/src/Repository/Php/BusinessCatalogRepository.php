<?php

declare(strict_types=1);

namespace App\Repository\Php;

use App\Entity\Php\BusinessCatalog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class BusinessCatalogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BusinessCatalog::class);
    }

    public function findByBusinessId(string $businessId): array
    {
        return $this->findBy(['businessId' => $businessId]);
    }

    public function findByCategory(string $category): array
    {
        return $this->findBy(['category' => $category]);
    }

    public function findActiveByBusinessId(string $businessId): array
    {
        return $this->findBy(['businessId' => $businessId, 'isActive' => true]);
    }

    public function searchByName(string $query): array
    {
        return $this->createQueryBuilder('bc')
            ->andWhere('bc.name LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->getQuery()
            ->getResult();
    }
}