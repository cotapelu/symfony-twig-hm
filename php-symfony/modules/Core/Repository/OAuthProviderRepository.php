<?php

declare(strict_types=1);

namespace App\Core\Repository;

use App\Core\Entity\OAuthProvider;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class OAuthProviderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OAuthProvider::class);
    }

    public function save(OAuthProvider $provider): void
    {
        $this->getEntityManager()->persist($provider);
        $this->getEntityManager()->flush();
    }

    public function delete(OAuthProvider $provider): void
    {
        $this->getEntityManager()->remove($provider);
        $this->getEntityManager()->flush();
    }

    public function findByUserId(int $userId): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.userId = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getResult();
    }

    public function findByProvider(string $provider): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.provider = :provider')
            ->setParameter('provider', $provider)
            ->getQuery()
            ->getResult();
    }

    public function findByProviderAndUserId(string $provider, int $userId): ?OAuthProvider
    {
        return $this->createQueryBuilder('p')
            ->where('p.provider = :provider')
            ->andWhere('p.userId = :userId')
            ->setParameter('provider', $provider)
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByProviderId(string $provider, string $providerId): ?OAuthProvider
    {
        return $this->createQueryBuilder('p')
            ->where('p.provider = :provider')
            ->andWhere('p.providerId = :providerId')
            ->setParameter('provider', $provider)
            ->setParameter('providerId', $providerId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}