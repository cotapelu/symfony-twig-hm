<?php

declare(strict_types=1);

namespace App\Delivery\Repository;

use App\Delivery\Entity\DeliveryPerson;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DeliveryPersonRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DeliveryPerson::class);
    }

    public function save(DeliveryPerson $person): void
    {
        $this->getEntityManager()->persist($person);
        $this->getEntityManager()->flush();
    }

    public function delete(DeliveryPerson $person): void
    {
        $this->getEntityManager()->remove($person);
        $this->getEntityManager()->flush();
    }

    public function findByUserId(int $userId): ?DeliveryPerson
    {
        return $this->createQueryBuilder('p')
            ->where('p.userId = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByStoreId(int $storeId): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.storeId = :storeId')
            ->setParameter('storeId', $storeId)
            ->getQuery()
            ->getResult();
    }

    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getResult();
    }

    public function findAvailableDeliveryPersons(): array
    {
        return $this->findByStatus('available');
    }

    public function findByLocation(string $location): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.currentLocation LIKE :location')
            ->setParameter('location', "%{$location}%")
            ->getQuery()
            ->getResult();
    }
}