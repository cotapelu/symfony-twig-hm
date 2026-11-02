<?php

declare(strict_types=1);

namespace App\PharmacyDelivery\Repository;

use App\Repository\Common\CommonRepositoryTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class OrderRepository extends ServiceEntityRepository
{
    use CommonRepositoryTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, App\PharmacyDelivery\Entity\Order::class);
    }

    public function findByCustomer(string $customerId): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.customerId = :customerId')
            ->setParameter('customerId', $customerId)
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()->getResult();
    }

    public function findByPharmacy(int $pharmacyId): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.pharmacy = :pharmacyId')
            ->setParameter('pharmacyId', $pharmacyId)
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()->getResult();
    }

    public function findPendingOrdersForPharmacy(int $pharmacyId): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.pharmacy = :pharmacyId')
            ->setParameter('pharmacyId', $pharmacyId)
            ->andWhere('o.status IN (:statuses)')
            ->setParameter('statuses', ['pending', 'confirmed', 'preparing'])
            ->orderBy('o.createdAt', 'ASC')
            ->getQuery()->getResult();
    }

    public function findActiveOrdersForDeliveryPerson(string $deliveryPersonId): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.deliveryPerson = :dpId')
            ->setParameter('dpId', $deliveryPersonId)
            ->andWhere('o.status IN (:statuses)')
            ->setParameter('statuses', ['picked_up'])
            ->getQuery()->getResult();
    }
}