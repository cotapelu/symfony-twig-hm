<?php

declare(strict_types=1);

namespace App\User\Repository;

use App\User\Entity\RefreshToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class RefreshTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RefreshToken::class);
    }

    public function save(RefreshToken $token): void
    {
        $this->getEntityManager()->persist($token);
        $this->getEntityManager()->flush();
    }

    public function delete(RefreshToken $token): void
    {
        $this->getEntityManager()->remove($token);
        $this->getEntityManager()->flush();
    }

    public function findByUserId(int $userId): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.userId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByToken(string $token): ?RefreshToken
    {
        return $this->createQueryBuilder('t')
            ->where('t.token = :token')
            ->setParameter('token', $token)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findValidToken(int $userId): ?RefreshToken
    {
        return $this->createQueryBuilder('t')
            ->where('t.userId = :userId')
            ->andWhere('t.expiresAt > :now')
            ->setParameter('userId', $userId)
            ->setParameter('now', new \DateTime())
            ->orderBy('t.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function revokeToken(string $token): void
    {
        $this->createQueryBuilder('t')
            ->update()
            ->set('t.revoked', 'true')
            ->where('t.token = :token')
            ->setParameter('token', $token)
            ->getQuery()
            ->execute();
    }

    public function revokeAllTokens(int $userId): void
    {
        $this->createQueryBuilder('t')
            ->update()
            ->set('t.revoked', 'true')
            ->where('t.userId = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->execute();
    }

    public function deleteExpiredTokens(): int
    {
        return $this->createQueryBuilder('t')
            ->delete()
            ->where('t.expiresAt < :now')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->execute();
    }
}