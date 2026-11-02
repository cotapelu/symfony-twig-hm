<?php

declare(strict_types=1);

namespace App\PharmacyDelivery\Repository;

use App\Repository\Common\CommonRepositoryTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DeliveryPersonRepository extends ServiceEntityRepository
{
    use CommonRepositoryTrait;
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, App\PharmacyDelivery\Entity\DeliveryPerson::class);
    }

    public function findAvailableDeliveryPersons(): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.status = :status')
            ->setParameter('status', 'available')
            ->orderBy('d.rating', 'DESC')
            ->getQuery()->getResult();
    }

    public function findNearbyAvailableDeliveryPersons(float $lat, float $lng, float $radiusKm = 3): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.status = :status')
            ->setParameter('status', 'available')
            ->orderBy('d.rating', 'DESC')
            ->getQuery()->getResult();
    }
}
