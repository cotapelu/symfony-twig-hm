<?php

declare(strict_types=1);

namespace App\Notification\Repository;

use App\Notification\Entity\NotificationChannel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class NotificationChannelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NotificationChannel::class);
    }

    public function save(NotificationChannel $channel): void
    {
        $this->getEntityManager()->persist($channel);
        $this->getEntityManager()->flush();
    }

    public function delete(NotificationChannel $channel): void
    {
        $this->getEntityManager()->remove($channel);
        $this->getEntityManager()->flush();
    }

    public function findByUserId(int $userId): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.userId = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getResult();
    }

    public function findByType(string $type): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.type = :type')
            ->setParameter('type', $type)
            ->getQuery()
            ->getResult();
    }

    public function findByUserIdAndType(int $userId, string $type): ?NotificationChannel
    {
        return $this->createQueryBuilder('c')
            ->where('c.userId = :userId')
            ->andWhere('c.type = :type')
            ->setParameter('userId', $userId)
            ->setParameter('type', $type)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findActiveChannels(int $userId): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.userId = :userId')
            ->andWhere('c.isEnabled = true')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getResult();
    }

    public function enableChannel(int $userId, string $type): void
    {
        $channel = $this->findByUserIdAndType($userId, $type);
        if ($channel) {
            $channel->setIsEnabled(true);
            $this->save($channel);
        }
    }

    public function disableChannel(int $userId, string $type): void
    {
        $channel = $this->findByUserIdAndType($userId, $type);
        if ($channel) {
            $channel->setIsEnabled(false);
            $this->save($channel);
        }
    }
}