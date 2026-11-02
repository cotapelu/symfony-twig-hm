<?php

declare(strict_types=1);

namespace App\RideHailing\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\RideHailing\Entity\Driver;

class DriverRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Driver::class);
    }

    public function findAvailableDrivers(
        float $latitude,
        float $longitude,
        float $radiusKm = 5.0,
        ?string $vehicleType = null
    ): array {
        $queryBuilder = $this->createQueryBuilder('d')
            ->andWhere('d.status = :status')
            ->andWhere('d.isActive = :active')
            ->andWhere('d.isVerified = :verified')
            ->setParameter('status', Driver::STATUS_ONLINE)
            ->setParameter('active', true)
            ->setParameter('verified', true);

        if ($vehicleType) {
            $queryBuilder->andWhere('d.vehicleType = :vehicleType')
                ->setParameter('vehicleType', $vehicleType);
        }

        $haversineFormula = '(6371 * acos(cos(radians(:latitude)) * cos(radians(d.currentLatitude)) * cos(radians(d.currentLongitude) - radians(:longitude)) + sin(radians(:latitude)) * sin(radians(d.currentLatitude))))';

        $queryBuilder->andWhere($haversineFormula . ' < :radius')
            ->setParameter('latitude', $latitude)
            ->setParameter('longitude', $longitude)
            ->setParameter('radius', $radiusKm)
            ->orderBy($haversineFormula, 'ASC')
            ->addOrderBy('d.rating', 'DESC');

        return $queryBuilder->getQuery()->getResult();
    }

    public function findByUserId(string $userId): ?Driver
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.userId = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOnlineDrivers(): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.status = :status')
            ->andWhere('d.isActive = :active')
            ->setParameter('status', Driver::STATUS_ONLINE)
            ->setParameter('active', true)
            ->getQuery()
            ->getResult();
    }

    public function save(Driver $driver): void
    {
        $this->getEntityManager()->persist($driver);
        $this->getEntityManager()->flush();
    }
}
