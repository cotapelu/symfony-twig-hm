<?php

declare(strict_types=1);

namespace App\FoodDelivery\Repository;

use App\Repository\Common\CommonRepositoryTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\FoodDelivery\Entity\Product;

class ProductRepository extends ServiceEntityRepository
{
    use CommonRepositoryTrait;
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function findByRestaurant(int $restaurantId): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.restaurant = :restaurantId')
            ->setParameter('restaurantId', $restaurantId)
            ->andWhere('p.status = :status')
            ->setParameter('status', Product::STATUS_ACTIVE)
            ->orderBy('p.category', 'ASC')
            ->addOrderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findPopularByRestaurant(int $restaurantId): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.restaurant = :restaurantId')
            ->setParameter('restaurantId', $restaurantId)
            ->andWhere('p.isPopular = :popular')
            ->andWhere('p.status = :status')
            ->setParameter('popular', true)
            ->setParameter('status', Product::STATUS_ACTIVE)
            ->getQuery()
            ->getResult();
    }

    public function findByCategory(int $restaurantId, string $category): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.restaurant = :restaurantId')
            ->setParameter('restaurantId', $restaurantId)
            ->andWhere('p.category = :category')
            ->andWhere('p.status = :status')
            ->setParameter('category', $category)
            ->setParameter('status', Product::STATUS_ACTIVE)
            ->getQuery()
            ->getResult();
    }

    public function getCategoriesByRestaurant(int $restaurantId): array
    {
        return $this->createQueryBuilder('p')
            ->select('DISTINCT p.category')
            ->andWhere('p.restaurant = :restaurantId')
            ->setParameter('restaurantId', $restaurantId)
            ->andWhere('p.status = :status')
            ->setParameter('status', Product::STATUS_ACTIVE)
            ->getQuery()
            ->getResult();
    }

    public function save(Product $product): void
    {
        $this->getEntityManager()->persist($product);
        $this->getEntityManager()->flush();
    }
}
