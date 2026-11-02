<?php

declare(strict_types=1);

namespace App\FoodDelivery\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\FoodDelivery\Entity\DeliveryPerson;

class DeliveryPersonRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DeliveryPerson::class);
    }

    public function findAvailableDeliveryPersons(): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.status = :status')
            ->andWhere('d.isActive = :active')
            ->setParameter('status', DeliveryPerson::STATUS_ONLINE)
            ->setParameter('active', true)
            ->getQuery()
            ->getResult();
    }

    public function findNearbyDeliveryPersons(float $latitude, float $longitude, float $radiusKm = 5.0): array
    {
        $haversineFormula = '(6371 * acos(cos(radians(:latitude)) * cos(radians(d.currentLatitude)) * cos(radians(d.currentLongitude) - radians(:longitude)) + sin(radians(:latitude)) * sin(radians(d.currentLatitude))))';

        return $this->createQueryBuilder('d')
            ->andWhere('d.status = :status')
            ->andWhere('d.isActive = :active')
            ->setParameter('status', DeliveryPerson::STATUS_ONLINE)
            ->setParameter('active', true)
            ->andWhere($haversineFormula . ' < :radius')
            ->setParameter('latitude', $latitude)
            ->setParameter('longitude', $longitude)
            ->setParameter('radius', $radiusKm)
            ->orderBy($haversineFormula, 'ASC')
            ->addOrderBy('d.rating', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByUserId(string $userId): ?DeliveryPerson
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.userId = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function save(DeliveryPerson $deliveryPerson): void
    {
        $this->getEntityManager()->persist($deliveryPerson);
        $this->getEntityManager()->flush();
    }
}
