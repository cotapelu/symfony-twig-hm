<?php

declare(strict_types=1);

namespace App\Investment\Repository;

use App\Investment\Entity\Investment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class InvestmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Investment::class);
    }

    public function findByUser(string $userId): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.userId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('i.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByUserAndType(string $userId, string $type): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.userId = :userId')
            ->andWhere('i.investmentType = :type')
            ->setParameter('userId', $userId)
            ->setParameter('type', $type)
            ->orderBy('i.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findActiveByUser(string $userId): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.userId = :userId')
            ->andWhere('i.status = :status')
            ->setParameter('userId', $userId)
            ->setParameter('status', 'active')
            ->getQuery()
            ->getResult();
    }

    public function findMatureByUser(string $userId): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.userId = :userId')
            ->andWhere('i.status = :status')
            ->setParameter('userId', $userId)
            ->setParameter('status', 'matured')
            ->getQuery()
            ->getResult();
    }

    public function getTotalPrincipalByUser(string $userId): float
    {
        $result = $this->createQueryBuilder('i')
            ->select('SUM(i.principal) as total')
            ->andWhere('i.userId = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleResult();

        return (float) ($result['total'] ?? 0);
    }

    public function getTotalCurrentValueByUser(string $userId): float
    {
        $result = $this->createQueryBuilder('i')
            ->select('SUM(i.currentValue) as total')
            ->andWhere('i.userId = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleResult();

        return (float) ($result['total'] ?? 0);
    }

    public function save(Investment $investment): void
    {
        $this->getEntityManager()->persist($investment);
        $this->getEntityManager()->flush();
    }

    public function delete(Investment $investment): void
    {
        $this->getEntityManager()->remove($investment);
        $this->getEntityManager()->flush();
    }
}
