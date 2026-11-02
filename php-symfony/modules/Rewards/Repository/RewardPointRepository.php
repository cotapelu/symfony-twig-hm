<?php

declare(strict_types=1);

namespace App\Rewards\Repository;

use App\Rewards\Entity\RewardPoint;
use App\User\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class RewardPointRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RewardPoint::class);
    }

    public function save(RewardPoint $rewardPoint): void
    {
        $this->getEntityManager()->persist($rewardPoint);
        $this->getEntityManager()->flush();
    }

    public function findByUser(string $userId): ?RewardPoint
    {
        return $this->findOneBy(['user' => $userId]);
    }

    public function findTopUsers(int $limit = 100): array
    {
        return $this->createQueryBuilder('r')
            ->orderBy('r.points', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
