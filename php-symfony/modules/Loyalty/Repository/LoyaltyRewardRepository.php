<?php

declare(strict_types=1);

namespace App\Loyalty\Repository;

use App\Loyalty\Entity\LoyaltyReward;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LoyaltyReward>
 */
class LoyaltyRewardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LoyaltyReward::class);
    }

    public function save(LoyaltyReward $reward): void
    {
        $this->getEntityManager()->persist($reward);
        $this->getEntityManager()->flush();
    }

    /**
     * @return LoyaltyReward|null
     */
    public function findById(int $id): ?LoyaltyReward
    {
        $result = $this->findOneBy(['id' => $id]);
        /** @var LoyaltyReward|null $result */
        return $result;
    }

    /**
     * @return array<int, LoyaltyReward>
     */
    public function findByProgram(int $programId, int $limit = 50, int $offset = 0): array
    {
        $query = $this->createQueryBuilder('r')
            ->where('r.programId = :programId')
            ->andWhere('r.isActive = :active')
            ->setParameter('programId', $programId)
            ->setParameter('active', true)
            ->orderBy('r.pointsCost', 'ASC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery();
        $result = $query->getResult();
        /** @var array<int, LoyaltyReward> $result */
        return $result;
    }

    public function countByProgram(int $programId): int
    {
        return (int)$this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.programId = :programId')
            ->andWhere('r.isActive = :active')
            ->setParameter('programId', $programId)
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return array<int, LoyaltyReward>
     */
    /**
     * @return array<int, LoyaltyReward>
     */
    public function findByPointsRange(int $minPoints, int $maxPoints, int $limit = 50, int $offset = 0): array
    {
        $query = $this->createQueryBuilder('r')
            ->where('r.pointsCost >= :min')
            ->andWhere('r.pointsCost <= :max')
            ->andWhere('r.isActive = :active')
            ->andWhere('r.stock = -1 OR r.stock > 0')
            ->setParameter('min', $minPoints)
            ->setParameter('max', $maxPoints)
            ->setParameter('active', true)
            ->orderBy('r.pointsCost', 'ASC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery();
        $result = $query->getResult();
        /** @var array<int, LoyaltyReward> $result */
        return $result;
    }
}
