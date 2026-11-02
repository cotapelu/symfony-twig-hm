<?php

declare(strict_types=1);

namespace App\PharmacyDelivery\Repository;

use App\Repository\Common\CommonRepositoryTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PharmacyRepository extends ServiceEntityRepository
{
    use CommonRepositoryTrait;
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, App\PharmacyDelivery\Entity\Pharmacy::class);
    }

    public function findNearbyPharmacies(float $lat, float $lng, float $radiusKm = 5): array
    {
        $qb = $this->createQueryBuilder('p');
        $qb->select('p', '((:lat - p.latitude) * (:lat - p.latitude) + (:lng - p.longitude) * (:lng - p.longitude)) as distance')
           ->setParameter('lat', $lat)
           ->setParameter('lng', $lng)
           ->having('distance < :radius')
           ->setParameter('radius', $radiusKm * $radiusKm / 111) 
           ->orderBy('distance', 'ASC')
           ->andWhere('p.status = :status')
           ->setParameter('status', 'active');
        return $qb->getQuery()->getResult();
    }

    public function findOpenPharmacies(float $lat, float $lng, float $radiusKm = 5): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.status = :status')
            ->setParameter('status', 'active')
            ->andWhere('p.is24Hours = :is24Hours OR (p.openingTime <= CURRENT_TIME() AND p.closingTime >= CURRENT_TIME())')
            ->setParameter('is24Hours', true)
            ->getQuery()->getResult();
    }
}
