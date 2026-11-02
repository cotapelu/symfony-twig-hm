<?php

declare(strict_types=1);

namespace App\Insurance\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Insurance\Entity\Policy;
use App\Insurance\Entity\Claim;

class PolicyRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, Policy::class); }
    
    public function findAll(int $limit = 50, int $offset = 0): array {
        return $this->createQueryBuilder('p')
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }
    
    public function countAll(): int {
        return (int)$this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
    
    public function findByUser(string $userId, int $limit = 50, int $offset = 0): array {
        return $this->createQueryBuilder('p')
            ->andWhere('p.userId = :uid')
            ->setParameter('uid', $userId)
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }
    
    public function countByUser(string $userId): int {
        return (int)$this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.userId = :uid')
            ->setParameter('uid', $userId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findByUserAndType(string $userId, string $type, int $limit = 50, int $offset = 0): array {
        return $this->createQueryBuilder('p')
            ->andWhere('p.userId = :uid')
            ->andWhere('p.type = :type')
            ->setParameter('uid', $userId)
            ->setParameter('type', $type)
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function countByUserAndType(string $userId, string $type): int {
        return (int)$this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.userId = :uid')
            ->andWhere('p.type = :type')
            ->setParameter('uid', $userId)
            ->setParameter('type', $type)
            ->getQuery()
            ->getSingleScalarResult();
    }
    
    public function findActiveByUser(string $userId): array { 
        return $this->createQueryBuilder('p')->andWhere('p.userId = :uid')->andWhere('p.status = :status')->setParameter('uid', $userId)->setParameter('status', 'active')->getQuery()->getResult(); 
    }
    
    public function findExpiringSoon(): array { 
        return $this->createQueryBuilder('p')->andWhere('p.status = :status')->andWhere('p.endDate BETWEEN NOW() AND NOW() + INTERVAL \'30\' DAY')->setParameter('status', 'active')->getQuery()->getResult(); 
    }

    public function findByPolicyNumber(string $policyNumber): ?Policy
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.policyNumber = :num')
            ->setParameter('num', $policyNumber)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function save(Policy $policy): void
    {
        $em = $this->getEntityManager();
        $em->persist($policy);
        $em->flush();
    }
}

