<?php

declare(strict_types=1);

namespace App\Repository\Php;

use App\Entity\Php\DeliveryOrder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DeliveryOrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DeliveryOrder::class);
    }

    public function findByStoreId(string $storeId): array
    {
        return $this->findBy(['storeId' => $storeId]);
    }

    public function findByCustomerId(string $customerId): array
    {
        return $this->findBy(['customerId' => $customerId]);
    }

    public function findByStatus(string $status): array
    {
        return $this->findBy(['status' => $status]);
    }

    public function findByDeliveryPersonId(string $deliveryPersonId): array
    {
        return $this->findBy(['deliveryPersonId' => $deliveryPersonId]);
    }

    public function findActiveOrders(): array
    {
        return $this->findBy(['status' => ['PENDING', 'CONFIRMED', 'PREPARING', 'DELIVERING']]);
    }
}