<?php

declare(strict_types=1);

namespace App\Notifications\Infrastructure\Repository;

use Doctrine\ORM\EntityRepository;
use App\Notifications\Domain\Entity\PushNotification;
use App\Notifications\Domain\Entity\PushSubscription;
use App\Notifications\Domain\Repository\PushNotificationRepositoryInterface;
use App\Notifications\Domain\Repository\PushSubscriptionRepositoryInterface;

class PushNotificationRepository extends EntityRepository implements PushNotificationRepositoryInterface
{
    public function findById(string $id): ?PushNotification
    {
        return $this->find($id);
    }

    public function findByUserId(string $userId, ?string $status = null, ?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('n')
            ->where('n.userId = :userId')
            ->setParameter('userId', $userId);

        if ($status !== null) {
            $qb->andWhere('n.status = :status')
               ->setParameter('status', $status);
        }

        $qb->orderBy('n.createdAt', 'DESC');

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    public function findPendingScheduled(\DateTimeInterface $now): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.status = :status')
            ->andWhere('n.scheduledAt IS NOT NULL')
            ->andWhere('n.scheduledAt <= :now')
            ->setParameter('status', PushNotification::STATUS_PENDING)
            ->setParameter('now', $now)
            ->orderBy('n.scheduledAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findFailedRetryable(): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.status = :status')
            ->andWhere('n.retryCount < n.maxRetries')
            ->setParameter('status', PushNotification::STATUS_FAILED)
            ->orderBy('n.failedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function save(PushNotification $notification): void
    {
        $em = $this->getEntityManager();
        if (!$em->contains($notification)) {
            $em->persist($notification);
        }
        $em->flush();
    }

    public function remove(PushNotification $notification): void
    {
        $em = $this->getEntityManager();
        $em->remove($notification);
        $em->flush();
    }
}

class PushSubscriptionRepository extends EntityRepository implements PushSubscriptionRepositoryInterface
{
    public function findById(string $id): ?PushSubscription
    {
        return $this->find($id);
    }

    public function findByUserId(string $userId): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.userId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('s.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByUserAndToken(string $userId, string $token): ?PushSubscription
    {
        return $this->createQueryBuilder('s')
            ->where('s.userId = :userId')
            ->andWhere('s.deviceToken = :token')
            ->setParameter('userId', $userId)
            ->setParameter('token', $token)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function save(PushSubscription $subscription): void
    {
        $em = $this->getEntityManager();
        if (!$em->contains($subscription)) {
            $em->persist($subscription);
        }
        $em->flush();
    }

    public function remove(PushSubscription $subscription): void
    {
        $em = $this->getEntityManager();
        $em->remove($subscription);
        $em->flush();
    }
}
