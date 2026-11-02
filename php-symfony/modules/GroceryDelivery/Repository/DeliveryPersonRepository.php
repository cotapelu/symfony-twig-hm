<?php

declare(strict_types=1);

namespace App\GroceryDelivery\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\GroceryDelivery\Entity\DeliveryPerson;

class DeliveryPersonRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DeliveryPerson::class);
    }

    public function findNearby(float $lat, float $lng, float $radius = 5.0): array
    {
        $f = '(6371 * acos(cos(radians(:lat)) * cos(d.lat) * cos(d.lng - radians(:lng)) + sin(radians(:lat)) * sin(d.lat)))';
        return $this->createQueryBuilder('d')->andWhere('d.status = :st')->setParameter('st', DeliveryPerson::STATUS_ONLINE)
            ->andWhere('d.isActive = true')->andWhere($f . ' < :r')->setParameter('lat', $lat)->setParameter('lng', $lng)->setParameter('r', $radius)
            ->orderBy($f, 'ASC')->addOrderBy('d.rating', 'DESC')->getQuery()->getResult();
    }

    public function save(DeliveryPerson $dp): void { $this->getEntityManager()->persist($dp); $this->getEntityManager()->flush(); }
}
