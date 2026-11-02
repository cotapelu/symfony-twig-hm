<?php

declare(strict_types=1);

namespace App\GroceryDelivery\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\GroceryDelivery\Entity\Product;

class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function findByStore(int $storeId): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.store = :id')->setParameter('id', $storeId)
            ->andWhere('p.status = :status')->setParameter('status', Product::STATUS_ACTIVE)
            ->orderBy('p.category', 'ASC')->getQuery()->getResult();
    }

    public function findPopular(int $storeId): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.store = :id')->setParameter('id', $storeId)
            ->andWhere('p.isPopular = true')
            ->andWhere('p.status = :status')->setParameter('status', Product::STATUS_ACTIVE)
            ->getQuery()->getResult();
    }

    public function getCategories(int $storeId): array
    {
        return $this->createQueryBuilder('p')->select('DISTINCT p.category')
            ->andWhere('p.store = :id')->setParameter('id', $storeId)
            ->andWhere('p.status = :status')->setParameter('status', Product::STATUS_ACTIVE)
            ->getQuery()->getResult();
    }

    public function save(Product $product): void { $this->getEntityManager()->persist($product); $this->getEntityManager()->flush(); }
}
