<?php

declare(strict_types=1);

namespace App\Ecommerce\Repository;

use App\Ecommerce\Entity\EcommerceOrder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EcommerceOrder>
 */
class EcommerceOrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EcommerceOrder::class);
    }

    public function save(EcommerceOrder $order): void
    {
        $this->getEntityManager()->persist($order);
        $this->getEntityManager()->flush();
    }

    public function findById(string $id): ?EcommerceOrder
    {
        return $this->findOneBy(['id' => $id]);
    }

    public function findByUser(string $userId, int $limit = 20): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.userId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByStatus(string $status, int $limit = 100): array
    {
        return $this->findBy(['status' => $status], ['createdAt' => 'DESC'], $limit);
    }

    public function updateStatus(string $orderId, string $status): void
    {
        $this->createQueryBuilder('o')
            ->update()
            ->set('o.status', ':status')
            ->set('o.updatedAt', ':now')
            ->where('o.id = :id')
            ->setParameter('status', $status)
            ->setParameter('now', new \DateTime())
            ->setParameter('id', $orderId)
            ->getQuery()
            ->execute();
    }

    public function countByStatus(string $status): int
    {
        return $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->where('o.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
