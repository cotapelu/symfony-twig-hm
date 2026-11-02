<?php

declare(strict_types=1);

namespace App\Delivery\Repository;

use App\Delivery\Entity\DeliveryStore;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DeliveryStoreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DeliveryStore::class);
    }

    public function save(DeliveryStore $store): void
    {
        $this->getEntityManager()->persist($store);
        $this->getEntityManager()->flush();
    }

    public function delete(DeliveryStore $store): void
    {
        $this->getEntityManager()->remove($store);
        $this->getEntityManager()->flush();
    }

    public function findByOwnerId(int $ownerId): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.ownerId = :ownerId')
            ->setParameter('ownerId', $ownerId)
            ->orderBy('s.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByLocation(string $location): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.location LIKE :location')
            ->setParameter('location', "%{$location}%")
            ->getQuery()
            ->getResult();
    }

    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getResult();
    }

    public function findActiveStores(): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.status = :status')
            ->setParameter('status', 'active')
            ->getQuery()
            ->getResult();
    }
}