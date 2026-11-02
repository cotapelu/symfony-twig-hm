<?php

declare(strict_types=1);

namespace App\PharmacyDelivery\Repository;

use App\Repository\Common\CommonRepositoryTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MedicineRepository extends ServiceEntityRepository
{
    use CommonRepositoryTrait;
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, App\PharmacyDelivery\Entity\Medicine::class);
    }

    public function findByPharmacy(int $pharmacyId): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.pharmacy = :pharmacyId')
            ->setParameter('pharmacyId', $pharmacyId)
            ->andWhere('m.isAvailable = :available')
            ->setParameter('available', true)
            ->orderBy('m.name', 'ASC')
            ->getQuery()->getResult();
    }

    public function searchMedicines(string $query, ?int $pharmacyId = null): array
    {
        $qb = $this->createQueryBuilder('m')
            ->andWhere('m.isAvailable = :available')
            ->setParameter('available', true)
            ->andWhere('m.name LIKE :query OR m.genericName LIKE :query OR m.sku LIKE :query')
            ->setParameter('query', '%' . $query . '%');
        
        if ($pharmacyId) {
            $qb->andWhere('m.pharmacy = :pharmacyId')
               ->setParameter('pharmacyId', $pharmacyId);
        }
        
        return $qb->getQuery()->getResult();
    }

    public function findByCategory(string $category, ?int $pharmacyId = null): array
    {
        $qb = $this->createQueryBuilder('m')
            ->andWhere('m.category = :category')
            ->setParameter('category', $category)
            ->andWhere('m.isAvailable = :available')
            ->setParameter('available', true);
        
        if ($pharmacyId) {
            $qb->andWhere('m.pharmacy = :pharmacyId')
               ->setParameter('pharmacyId', $pharmacyId);
        }
        
        return $qb->getQuery()->getResult();
    }
}
