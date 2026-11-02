<?php

declare(strict_types=1);

namespace App\GroceryDelivery\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\GroceryDelivery\Entity\Store;

class StoreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Store::class);
    }

    public function findActiveStores(): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.status = :status')->setParameter('status', Store::STATUS_ACTIVE)
            ->orderBy('s.rating', 'DESC')->getQuery()->getResult();
    }

    public function findNearbyStores(float $lat, float $lng, float $radius = 5.0): array
    {
        $formula = '(6371 * acos(cos(radians(:lat)) * cos(s.lat) * cos(s.lng - radians(:lng)) + sin(radians(:lat)) * sin(s.lat)))';
        return $this->createQueryBuilder('s')
            ->andWhere('s.status = :status')->setParameter('status', Store::STATUS_ACTIVE)
            ->andWhere($formula . ' < :radius')->setParameter('lat', $lat)->setParameter('lng', $lng)->setParameter('radius', $radius)
            ->orderBy($formula, 'ASC')->addOrderBy('s.rating', 'DESC')->getQuery()->getResult();
    }

    public function findByCategory(string $category): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.category = :cat')->setParameter('cat', $category)
            ->andWhere('s.status = :status')->setParameter('status', Store::STATUS_ACTIVE)
            ->orderBy('s.rating', 'DESC')->getQuery()->getResult();
    }

    public function save(Store $store): void { $this->getEntityManager()->persist($store); $this->getEntityManager()->flush(); }
}
