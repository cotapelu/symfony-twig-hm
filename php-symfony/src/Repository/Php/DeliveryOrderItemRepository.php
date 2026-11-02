<?php

declare(strict_types=1);

namespace App\Repository\Php;

use App\Entity\Php\DeliveryOrderItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Repository\Common\CommonRepositoryTrait;

/**
 * @extends ServiceEntityRepository<DeliveryOrderItem>
 */
class DeliveryOrderItemRepository extends ServiceEntityRepository
{
    use CommonRepositoryTrait;
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DeliveryOrderItem::class);
    }




    public function findByOrderId(string $orderId): array
    {
        return $this->findBy(['orderId' => $orderId]);
    }

    public function findByProductId(string $productId): array
    {
        return $this->findBy(['productId' => $productId]);
    }

    public function countByOrderId(string $orderId): int
    {
        return $this->count(['orderId' => $orderId]);
    }

    public function deleteByOrderId(string $orderId): int
    {
        return $this->createQueryBuilder('i')
            ->delete()
            ->where('i.orderId = :orderId')
            ->setParameter('orderId', $orderId)
            ->getQuery()
            ->execute();
    }
}
