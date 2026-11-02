<?php

declare(strict_types=1);

namespace App\Repository\Common;

use App\Entity\Common\Notification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Repository\Common\CommonRepositoryTrait;

/**
 * @extends ServiceEntityRepository<Notification>
 */
class NotificationRepository extends ServiceEntityRepository
{
    use CommonRepositoryTrait;
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }




    public function findByUserId(string $userId): array
    {
        return $this->findBy(['userId' => $userId], ['createdAt' => 'DESC']);
    }

    public function findByType(string $type): array
    {
        return $this->findBy(['type' => $type]);
    }

    public function findByStatus(string $status): array
    {
        return $this->findBy(['status' => $status]);
    }

    public function findUnreadNotifications(string $userId): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.userId = :userId')
            ->andWhere('n.readAt IS NULL')
            ->orderBy('n.createdAt', 'DESC')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getResult();
    }

    public function findReadNotifications(string $userId): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.userId = :userId')
            ->andWhere('n.readAt IS NOT NULL')
            ->orderBy('n.readAt', 'DESC')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getResult();
    }

    public function countUnreadNotifications(string $userId): int
    {
        return $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->where('n.userId = :userId')
            ->andWhere('n.readAt IS NULL')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function markAsRead(string $id): void
    {
        $this->createQueryBuilder('n')
            ->update()
            ->set('n.readAt', ':now')
            ->where('n.id = :id')
            ->setParameter('now', new \DateTime())
            ->setParameter('id', $id)
            ->getQuery()
            ->execute();
    }

    public function markAllAsRead(string $userId): int
    {
        return $this->createQueryBuilder('n')
            ->update()
            ->set('n.readAt', ':now')
            ->where('n.userId = :userId')
            ->andWhere('n.readAt IS NULL')
            ->setParameter('now', new \DateTime())
            ->setParameter('userId', $userId)
            ->getQuery()
            ->execute();
    }
}
