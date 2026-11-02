<?php

declare(strict_types=1);

namespace App\FoodDelivery\Repository;

use App\FoodDelivery\Entity\OrderItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class OrderItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrderItem::class);
    }

    public function save(OrderItem $item): void
    {
        $this->getEntityManager()->persist($item);
        $this->getEntityManager()->flush();
    }

    public function findById(int $id): ?OrderItem
    {
        return $this->find($id);
    }

    public function findByOrder(int $orderId): array
    {
        return $this->createQueryBuilder('oi')
            ->andWhere('oi.order = :orderId')
            ->setParameter('orderId', $orderId)
            ->orderBy('oi.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByProduct(int $productId): array
    {
        return $this->createQueryBuilder('oi')
            ->andWhere('oi.product = :productId')
            ->setParameter('productId', $productId)
            ->getQuery()
            ->getResult();
    }

    public function delete(OrderItem $item): void
    {
        $this->getEntityManager()->remove($item);
        $this->getEntityManager()->flush();
    }
}
