<?php

declare(strict_types=1);

namespace App\Loyalty\Repository;

use App\Loyalty\Entity\LoyaltyProgram;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LoyaltyProgram>
 */
class LoyaltyProgramRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LoyaltyProgram::class);
    }

    public function save(LoyaltyProgram $program): void
    {
        $this->getEntityManager()->persist($program);
        $this->getEntityManager()->flush();
    }

    /**
     * @return LoyaltyProgram|null
     */
    public function findById(int $id): ?LoyaltyProgram
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * @return array<int, LoyaltyProgram>
     */
    public function findAll(int $limit = 50, int $offset = 0): array
    {
        $query = $this->createQueryBuilder('p')
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery();
        $result = $query->getResult();
        /** @var array<int, LoyaltyProgram> $result */
        return $result;
    }

    public function countAll(): int
    {
        return (int)$this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return array<int, LoyaltyProgram>
     */
    public function findByActive(int $limit = 50, int $offset = 0): array
    {
        $query = $this->createQueryBuilder('p')
            ->where('p.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery();
        $result = $query->getResult();
        /** @var array<int, LoyaltyProgram> $result */
        return $result;
    }

    public function countActive(): int
    {
        return (int)$this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.isActive = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
