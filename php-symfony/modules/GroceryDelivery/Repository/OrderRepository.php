<?php

declare(strict_types=1);

namespace App\GroceryDelivery\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\GroceryDelivery\Entity\Order;

class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    public function findByCustomer(string $cid): array
    {
        return $this->createQueryBuilder('o')->andWhere('o.customerId = :cid')->setParameter('cid', $cid)
            ->orderBy('o.createdAt', 'DESC')->getQuery()->getResult();
    }

    public function findActiveByCustomer(string $cid): ?Order
    {
        $r = $this->createQueryBuilder('o')->andWhere('o.customerId = :cid')->setParameter('cid', $cid)
            ->andWhere('o.status IN (:st)')->setParameter('st', [Order::STATUS_PENDING, Order::STATUS_CONFIRMED, Order::STATUS_PREPARING, Order::STATUS_READY, Order::STATUS_PICKED_UP])
            ->getQuery()->getResult();
        return $r[0] ?? null;
    }

    public function findByStore(int $sid): array
    {
        return $this->createQueryBuilder('o')->andWhere('o.store = :id')->setParameter('id', $sid)
            ->orderBy('o.createdAt', 'DESC')->getQuery()->getResult();
    }

    public function findPendingDelivery(): array
    {
        return $this->createQueryBuilder('o')->andWhere('o.status = :st')->setParameter('st', Order::STATUS_READY)
            ->orderBy('o.readyAt', 'ASC')->getQuery()->getResult();
    }

    public function save(Order $order): void { $this->getEntityManager()->persist($order); $this->getEntityManager()->flush(); }
}
