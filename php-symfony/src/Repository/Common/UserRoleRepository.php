<?php

declare(strict_types=1);

namespace App\Repository\Common;

use App\Entity\Common\UserRole;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserRole>
 */
class UserRoleRepository extends ServiceEntityRepository
{
    use FindByUserTrait;
    use CommonRepositoryTrait;
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserRole::class);
    }





    public function findByRole(string $role): array
    {
        return $this->findBy(['role' => $role]);
    }

    public function findByUserIdAndRole(string $userId, string $role): ?UserRole
    {
        return $this->findOneBy(['userId' => $userId, 'role' => $role]);
    }

    public function findActiveRoles(): array
    {
        return $this->findBy(['status' => 'ACTIVE']);
    }

    public function findInactiveRoles(): array
    {
        return $this->findBy(['status' => 'INACTIVE']);
    }

    public function deleteByUserId(string $userId): int
    {
        return $this->createQueryBuilder('r')
            ->delete()
            ->where('r.userId = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->execute();
    }

    public function deleteByUserIdAndRole(string $userId, string $role): int
    {
        return $this->createQueryBuilder('r')
            ->delete()
            ->where('r.userId = :userId')
            ->andWhere('r.role = :role')
            ->setParameter('userId', $userId)
            ->setParameter('role', $role)
            ->getQuery()
            ->execute();
    }
}
