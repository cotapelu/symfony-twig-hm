<?php

declare(strict_types=1);

namespace App\RideHailing\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\RideHailing\Entity\Ride;

class RideRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ride::class);
    }

    public function findActiveRidesByRider(string $riderId): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.riderId = :riderId')
            ->andWhere('r.status IN (:statuses)')
            ->setParameter('riderId', $riderId)
            ->setParameter('statuses', [
                Ride::STATUS_REQUESTED,
                Ride::STATUS_ACCEPTED,
                Ride::STATUS_ARRIVED,
                Ride::STATUS_IN_PROGRESS
            ])
            ->orderBy('r.requestedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findActiveRidesByDriver(string $driverId): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.driver = :driverId')
            ->andWhere('r.status IN (:statuses)')
            ->setParameter('driverId', $driverId)
            ->setParameter('statuses', [
                Ride::STATUS_ACCEPTED,
                Ride::STATUS_ARRIVED,
                Ride::STATUS_IN_PROGRESS
            ])
            ->getQuery()
            ->getResult();
    }

    public function findCompletedRidesByUser(string $userId): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('(r.riderId = :userId OR r.driver = :userId)')
            ->andWhere('r.status = :status')
            ->setParameter('userId', $userId)
            ->setParameter('status', Ride::STATUS_COMPLETED)
            ->orderBy('r.completedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findPendingRequests(): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.status = :status')
            ->setParameter('status', Ride::STATUS_REQUESTED)
            ->orderBy('r.requestedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
