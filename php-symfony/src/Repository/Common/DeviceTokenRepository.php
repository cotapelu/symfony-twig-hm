<?php

declare(strict_types=1);

namespace App\Repository\Common;

use App\Entity\Common\DeviceToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DeviceToken>
 */
class DeviceTokenRepository extends ServiceEntityRepository
{
    use FindByUserTrait;
    use CommonRepositoryTrait;
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DeviceToken::class);
    }




    public function findByToken(string $token): ?DeviceToken
    {
        return $this->findOneBy(['token' => $token]);
    }

    public function findByType(string $type): array
    {
        return $this->findBy(['type' => $type]);
    }

    public function findActiveTokens(): array
    {
        return $this->findBy(['status' => 'ACTIVE']);
    }

    public function findInactiveTokens(): array
    {
        return $this->findBy(['status' => 'INACTIVE']);
    }

    public function deleteByUserId(string $userId): int
    {
        return $this->createQueryBuilder('t')
            ->delete()
            ->where('t.userId = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->execute();
    }

    public function deleteByToken(string $token): int
    {
        return $this->createQueryBuilder('t')
            ->delete()
            ->where('t.token = :token')
            ->setParameter('token', $token)
            ->getQuery()
            ->execute();
    }
}
