<?php

declare(strict_types=1);

namespace App\Business\Repository;

use App\Business\Entity\BusinessCatalog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class BusinessCatalogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BusinessCatalog::class);
    }

    public function save(BusinessCatalog $catalog): void
    {
        $this->getEntityManager()->persist($catalog);
        $this->getEntityManager()->flush();
    }

    public function delete(BusinessCatalog $catalog): void
    {
        $this->getEntityManager()->remove($catalog);
        $this->getEntityManager()->flush();
    }

    public function findByBusinessId(int $businessId): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.businessId = :businessId')
            ->setParameter('businessId', $businessId)
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByCategory(string $category): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.category = :category')
            ->setParameter('category', $category)
            ->getQuery()
            ->getResult();
    }

    public function findByName(string $name): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.name LIKE :name')
            ->setParameter('name', "%{$name}%")
            ->getQuery()
            ->getResult();
    }

    public function findByPriceRange(float $minPrice, float $maxPrice): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.price >= :minPrice')
            ->andWhere('c.price <= :maxPrice')
            ->setParameter('minPrice', $minPrice)
            ->setParameter('maxPrice', $maxPrice)
            ->getQuery()
            ->getResult();
    }

    public function findAvailableItems(): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.available = true')
            ->getQuery()
            ->getResult();
    }
}