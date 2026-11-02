<?php

declare(strict_types=1);

namespace App\RideHailing\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\RideHailing\Entity\Vehicle;

class VehicleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Vehicle::class);
    }

    public function findByDriver(int $driverId): array
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.driver = :driverId')
            ->setParameter('driverId', $driverId)
            ->orderBy('v.isDefault', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findDefaultByDriver(int $driverId): ?Vehicle
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.driver = :driverId')
            ->andWhere('v.isDefault = :default')
            ->setParameter('driverId', $driverId)
            ->setParameter('default', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function save(Vehicle $vehicle): void
    {
        $this->getEntityManager()->persist($vehicle);
        $this->getEntityManager()->flush();
    }
}
