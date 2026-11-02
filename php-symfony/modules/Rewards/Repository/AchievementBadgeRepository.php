<?php

declare(strict_types=1);

namespace App\Rewards\Repository;

use App\Rewards\Entity\AchievementBadge;
use App\User\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AchievementBadgeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AchievementBadge::class);
    }

    public function save(AchievementBadge $badge): void
    {
        $this->getEntityManager()->persist($badge);
        $this->getEntityManager()->flush();
    }

    public function findByUser(string $userId): array
    {
        return $this->findBy(['user' => $userId], ['earnedAt' => 'DESC']);
    }

    public function findByType(string $badgeType): array
    {
        return $this->findBy(['type' => $badgeType]);
    }
}
