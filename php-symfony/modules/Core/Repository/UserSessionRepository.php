<?php

declare(strict_types=1);

namespace App\Core\Repository;

use App\Core\Entity\UserSession;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserSessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserSession::class);
    }

    public function save(UserSession $session): void
    {
        $this->getEntityManager()->persist($session);
        $this->getEntityManager()->flush();
    }

    public function delete(UserSession $session): void
    {
        $this->getEntityManager()->remove($session);
        $this->getEntityManager()->flush();
    }

    public function findByUserId(int $userId): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.userId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('s.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByToken(string $token): ?UserSession
    {
        return $this->createQueryBuilder('s')
            ->where('s.token = :token')
            ->setParameter('token', $token)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findActiveSession(int $userId): ?UserSession
    {
        return $this->createQueryBuilder('s')
            ->where('s.userId = :userId')
            ->andWhere('s.expiresAt > :now')
            ->andWhere('s.isActive = true')
            ->setParameter('userId', $userId)
            ->setParameter('now', new \DateTime())
            ->orderBy('s.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function deactivateSession(string $token): void
    {
        $this->createQueryBuilder('s')
            ->update()
            ->set('s.isActive', 'false')
            ->where('s.token = :token')
            ->setParameter('token', $token)
            ->getQuery()
            ->execute();
    }

    public function deactivateAllSessions(int $userId): void
    {
        $this->createQueryBuilder('s')
            ->update()
            ->set('s.isActive', 'false')
            ->where('s.userId = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->execute();
    }

    public function deleteExpiredSessions(): int
    {
        return $this->createQueryBuilder('s')
            ->delete()
            ->where('s.expiresAt < :now')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->execute();
    }
}