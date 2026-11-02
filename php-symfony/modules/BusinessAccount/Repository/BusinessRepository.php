<?php

declare(strict_types=1);

namespace App\BusinessAccount\Repository;

use App\BusinessAccount\Entity\Business;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class BusinessRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Business::class);
    }

    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.status = :status')
            ->setParameter('status', $status)
            ->orderBy('b.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByCategory(int $categoryId): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.category = :categoryId')
            ->setParameter('categoryId', $categoryId)
            ->andWhere('b.status = :active')
            ->setParameter('active', 'active')
            ->getQuery()
            ->getResult();
    }

    public function findVerifiedBusinesses(): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.isVerified = :verified')
            ->setParameter('verified', true)
            ->andWhere('b.status = :active')
            ->setParameter('active', 'active')
            ->orderBy('b.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function searchByName(string $name): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.name LIKE :name')
            ->setParameter('name', '%' . $name . '%')
            ->andWhere('b.status = :active')
            ->setParameter('active', 'active')
            ->orderBy('b.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
