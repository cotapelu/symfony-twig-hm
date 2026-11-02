<?php

declare(strict_types=1);

namespace App\Loyalty\Repository;

use App\Loyalty\Entity\LoyaltyMember;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LoyaltyMember>
 */
class LoyaltyMemberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LoyaltyMember::class);
    }

    public function save(LoyaltyMember $member): void
    {
        $this->getEntityManager()->persist($member);
        $this->getEntityManager()->flush();
    }

    public function findById(int $id): ?LoyaltyMember
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * @return array<int, LoyaltyMember>
     */
    public function findByUserId(int $userId, int $limit = 50, int $offset = 0): array
    {
        $query = $this->createQueryBuilder('m')
            ->where('m.userId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('m.joinedAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery();
        $result = $query->getResult();
        /** @var array<int, LoyaltyMember> $result */
        return $result;
    }

    /**
     * @return LoyaltyMember|null
     */
    public function findByUserAndProgram(int $userId, int $programId): ?LoyaltyMember
    {
        $result = $this->findOneBy(['userId' => $userId, 'programId' => $programId]);
        /** @var LoyaltyMember|null $result */
        return $result;
    }

    public function countByUserId(int $userId): int
    {
        return (int)$this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('m.userId = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return array<int, LoyaltyMember>
     */
    public function findByProgram(int $programId, int $limit = 50, int $offset = 0): array
    {
        $query = $this->createQueryBuilder('m')
            ->where('m.programId = :programId')
            ->setParameter('programId', $programId)
            ->orderBy('m.points', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery();
        $result = $query->getResult();
        /** @var array<int, LoyaltyMember> $result */
        return $result;
    }

    /**
     * @return array<int, LoyaltyMember>
     */
    public function findByTier(string $tier, int $limit = 50, int $offset = 0): array
    {
        $query = $this->createQueryBuilder('m')
            ->where('m.tier = :tier')
            ->setParameter('tier', $tier)
            ->orderBy('m.points', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery();
        $result = $query->getResult();
        /** @var array<int, LoyaltyMember> $result */
        return $result;
    }
}
