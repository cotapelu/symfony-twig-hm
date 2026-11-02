<?php

declare(strict_types=1);

namespace App\FoodDelivery\Repository;

use App\Repository\Common\CommonRepositoryTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\FoodDelivery\Entity\Order;

class OrderRepository extends ServiceEntityRepository
{
    use CommonRepositoryTrait;
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    public function findByCustomer(string $customerId, int $limit = 50, int $offset = 0): array
    {
        return $this->createQueryBuilder('o')
            ->leftJoin('o.restaurant', 'r')
            ->leftJoin('o.deliveryPerson', 'd')
            ->leftJoin('o.payment', 'p')
            ->addSelect('r', 'd', 'p')
            ->andWhere('o.customerId = :customerId')
            ->setParameter('customerId', $customerId)
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function countByCustomer(string $customerId): int
    {
        return (int)$this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->andWhere('o.customerId = :customerId')
            ->setParameter('customerId', $customerId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findByRestaurant(int $restaurantId): array
    {
        return $this->createQueryBuilder('o')
            ->leftJoin('o.deliveryPerson', 'd')
            ->leftJoin('o.payment', 'p')
            ->addSelect('d', 'p')
            ->andWhere('o.restaurant = :restaurantId')
            ->setParameter('restaurantId', $restaurantId)
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findActiveOrdersByRestaurant(int $restaurantId): array
    {
        return $this->createQueryBuilder('o')
            ->leftJoin('o.deliveryPerson', 'd')
            ->leftJoin('o.payment', 'p')
            ->addSelect('d', 'p')
            ->andWhere('o.restaurant = :restaurantId')
            ->setParameter('restaurantId', $restaurantId)
            ->andWhere('o.status IN (:statuses)')
            ->setParameter('statuses', [Order::STATUS_PENDING, Order::STATUS_CONFIRMED, Order::STATUS_PREPARING, Order::STATUS_READY])
            ->orderBy('o.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByDeliveryPerson(int $deliveryPersonId): array
    {
        return $this->createQueryBuilder('o')
            ->leftJoin('o.restaurant', 'r')
            ->leftJoin('o.payment', 'p')
            ->addSelect('r', 'p')
            ->andWhere('o.deliveryPerson = :deliveryPersonId')
            ->setParameter('deliveryPersonId', $deliveryPersonId)
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findPendingDeliveryOrders(): array
    {
        return $this->createQueryBuilder('o')
            ->leftJoin('o.restaurant', 'r')
            ->leftJoin('o.deliveryPerson', 'd')
            ->leftJoin('o.payment', 'p')
            ->addSelect('r', 'd', 'p')
            ->andWhere('o.status = :status')
            ->setParameter('status', Order::STATUS_READY)
            ->orderBy('o.readyAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findActiveOrderByCustomer(string $customerId): ?Order
    {
        $result = $this->createQueryBuilder('o')
            ->andWhere('o.customerId = :customerId')
            ->setParameter('customerId', $customerId)
            ->andWhere('o.status IN (:statuses)')
            ->setParameter('statuses', [Order::STATUS_PENDING, Order::STATUS_CONFIRMED, Order::STATUS_PREPARING, Order::STATUS_READY, Order::STATUS_PICKED_UP])
            ->getQuery()
            ->getResult();

        return $result[0] ?? null;
    }

    public function save(Order $order): void
    {
        $this->getEntityManager()->persist($order);
        $this->getEntityManager()->flush();
    }

    public function findByIdempotencyKey(string $key): ?Order
    {
        return $this->findOneBy(['idempotencyKey' => $key]);
    }
}
