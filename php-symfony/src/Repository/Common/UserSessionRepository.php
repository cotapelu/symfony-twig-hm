<?php declare(strict_types=1);

namespace App\Repository\Common;

use App\Entity\Common\UserSession;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Repository\Common\CommonRepositoryTrait;

/**
 * @extends ServiceEntityRepository<UserSession>
 */
class UserSessionRepository extends ServiceEntityRepository
{
    use CommonRepositoryTrait;
    use FindByUserTrait;
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserSession::class);
    }




    public function findByTokenHash(string $tokenHash): ?UserSession
    {
        return $this->findOneBy(['tokenHash' => $tokenHash]);
    }

    public function findActiveByUserId(string $userId): array
    {
        return $this->findBy(['userId' => $userId, 'status' => 'ACTIVE']);
    }

    public function findExpiredSessions(\DateTime $before): array
    {
        return $this->findBy(['status' => 'ACTIVE']);
    }

    public function countActiveByUserId(string $userId): int
    {
        return $this->count(['userId' => $userId, 'status' => 'ACTIVE']);
    }
}