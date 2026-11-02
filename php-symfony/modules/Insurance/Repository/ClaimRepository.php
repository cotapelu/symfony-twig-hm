<?php declare(strict_types=1);
namespace App\Insurance\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Insurance\Entity\Claim;

class ClaimRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $registry) { 
        parent::__construct($registry, Claim::class); 
    }
    
    public function findAll(int $limit = 50, int $offset = 0): array {
        return $this->createQueryBuilder('c')
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }
    
    public function countAll(): int {
        return (int)$this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
    
    public function findByPolicy(string $policyId, int $limit = 50, int $offset = 0): array {
        return $this->createQueryBuilder('c')
            ->andWhere('c.policyId = :pid')
            ->setParameter('pid', $policyId)
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }
    
    public function countByPolicy(string $policyId): int {
        return (int)$this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.policyId = :pid')
            ->setParameter('pid', $policyId)
            ->getQuery()
        ->getSingleScalarResult();
    }
    
    public function findByClaimNumber(string $claimNumber): ?Claim
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.claimNumber = :cn')
            ->setParameter('cn', $claimNumber)
            ->getQuery()
            ->getOneOrNullResult();
    }
     
    public function findByStatus(string $status, int $limit = 50, int $offset = 0): array {
        return $this->createQueryBuilder('c')
            ->andWhere('c.status = :status')
            ->setParameter('status', $status)
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function findByUserId(string $userId, int $limit = 50, int $offset = 0): array
    {
        return $this->createQueryBuilder('c')
            ->join('c.policy', 'p')
            ->andWhere('p.userId = :uid')
            ->setParameter('uid', $userId)
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function countByUserId(string $userId): int
    {
        return (int)$this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->join('c.policy', 'p')
            ->andWhere('p.userId = :uid')
            ->setParameter('uid', $userId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function save(Claim $claim): void
    {
        $em = $this->getEntityManager();
        $em->persist($claim);
        $em->flush();
    }
}
