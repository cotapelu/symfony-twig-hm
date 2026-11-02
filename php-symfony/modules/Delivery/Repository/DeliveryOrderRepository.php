<?php

declare(strict_types=1);

namespace App\Delivery\Repository;

use App\Delivery\Entity\DeliveryOrder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DeliveryOrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DeliveryOrder::class);
    }

    public function save(DeliveryOrder $order): void
    {
        $this->getEntityManager()->persist($order);
        $this->getEntityManager()->flush();
    }

    public function delete(DeliveryOrder $order): void
    {
        $this->getEntityManager()->remove($order);
        $this->getEntityManager()->flush();
    }

    public function findByCustomerId(int $customerId): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.customerId = :customerId')
            ->setParameter('customerId', $customerId)
            ->orderBy('o.orderDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByStoreId(int $storeId): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.storeId = :storeId')
            ->setParameter('storeId', $storeId)
            ->orderBy('o.orderDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.status = :status')
            ->setParameter('status', $status)
            ->orderBy('o.orderDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByDateRange(\DateTime $startDate, \DateTime $endDate): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.orderDate >= :startDate')
            ->andWhere('o.orderDate <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('o.orderDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countByStatus(string $status): int
    {
        return (int) $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->where('o.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findPendingOrders(): array
    {
        return $this->findByStatus('pending');
    }

    public function findCompletedOrders(): array
    {
        return $this->findByStatus('completed');
    }
}