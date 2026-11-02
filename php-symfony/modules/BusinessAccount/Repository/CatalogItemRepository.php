<?php

declare(strict_types=1);

namespace App\BusinessAccount\Repository;

use App\BusinessAccount\Entity\CatalogItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CatalogItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CatalogItem::class);
    }

    public function findByBusiness(int $businessId): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.business = :businessId')
            ->setParameter('businessId', $businessId)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findActiveByBusiness(int $businessId): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.business = :businessId')
            ->setParameter('businessId', $businessId)
            ->andWhere('c.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByCategory(int $businessId, string $category): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.business = :businessId')
            ->setParameter('businessId', $businessId)
            ->andWhere('c.category = :category')
            ->setParameter('category', $category)
            ->andWhere('c.isActive = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->getResult();
    }

    public function searchInBusiness(int $businessId, string $search): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.business = :businessId')
            ->setParameter('businessId', $businessId)
            ->andWhere('c.isActive = :active')
            ->setParameter('active', true)
            ->andWhere('c.name LIKE :search OR c.description LIKE :search')
            ->setParameter('search', '%' . $search . '%')
            ->orderBy('c.price', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
