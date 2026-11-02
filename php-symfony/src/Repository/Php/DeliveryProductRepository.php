<?php

declare(strict_types=1);

namespace App\Repository\Php;

use App\Entity\Php\DeliveryProduct;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Repository\Common\CommonRepositoryTrait;

/**
 * @extends ServiceEntityRepository<DeliveryProduct>
 */
class DeliveryProductRepository extends ServiceEntityRepository
{
    use CommonRepositoryTrait;
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DeliveryProduct::class);
    }




    public function findByStoreId(string $storeId): array
    {
        return $this->findBy(['storeId' => $storeId]);
    }

    public function findByCategory(string $category): array
    {
        return $this->findBy(['category' => $category]);
    }

    public function findByStatus(string $status): array
    {
        return $this->findBy(['status' => $status]);
    }

    public function findActiveProducts(): array
    {
        return $this->findBy(['status' => 'ACTIVE']);
    }

    public function findInactiveProducts(): array
    {
        return $this->findBy(['status' => 'INACTIVE']);
    }

    public function countByStoreId(string $storeId): int
    {
        return $this->count(['storeId' => $storeId]);
    }

    public function countByCategory(string $category): int
    {
        return $this->count(['category' => $category]);
    }
}
