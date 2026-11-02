<?php

declare(strict_types=1);

namespace App\Repository\Common;

use App\Entity\Common\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Repository\Common\CommonRepositoryTrait;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    use CommonRepositoryTrait;
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }




    public function findByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => $email]);
    }

    public function findByPhone(string $phone): ?User
    {
        return $this->findOneBy(['phone' => $phone]);
    }

    public function findActiveUsers(): array
    {
        return $this->findBy(['status' => 'ACTIVE']);
    }

    public function findInactiveUsers(): array
    {
        return $this->findBy(['status' => 'INACTIVE']);
    }

    public function findSuspendedUsers(): array
    {
        return $this->findBy(['status' => 'SUSPENDED']);
    }

    public function countActiveUsers(): int
    {
        return $this->count(['status' => 'ACTIVE']);
    }

    public function countInactiveUsers(): int
    {
        return $this->count(['status' => 'INACTIVE']);
    }

    public function countSuspendedUsers(): int
    {
        return $this->count(['status' => 'SUSPENDED']);
    }
}
