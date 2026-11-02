<?php

declare(strict_types=1);

namespace App\Conversation\Repository;

use App\Conversation\Entity\Group;
use App\User\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Group>
 */
class GroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Group::class);
    }

    public function findUserGroups(User $user, int $page = 1, int $limit = 20): array
    {
        return $this->createQueryBuilder('g')
            ->innerJoin('g.members', 'm')
            ->where('m.id = :userId')
            ->andWhere('g.isActive = :active')
            ->setParameter('userId', $user->getId())
            ->setParameter('active', true)
            ->orderBy('g.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findPublicGroups(int $page = 1, int $limit = 20): array
    {
        return $this->createQueryBuilder('g')
            ->where('g.isActive = :active')
            ->andWhere('g.isPrivate = :private')
            ->setParameter('active', true)
            ->setParameter('private', false)
            ->orderBy('g.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function isUserMember(Group $group, User $user): bool
    {
        return $this->createQueryBuilder('g')
            ->select('COUNT(g.id)')
            ->innerJoin('g.members', 'm')
            ->where('g.id = :groupId')
            ->andWhere('m.id = :userId')
            ->setParameter('groupId', $group->getId())
            ->setParameter('userId', $user->getId())
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    public function countMembers(Group $group): int
    {
        return $this->createQueryBuilder('g')
            ->select('COUNT(m.id)')
            ->innerJoin('g.members', 'm')
            ->where('g.id = :groupId')
            ->setParameter('groupId', $group->getId())
            ->getQuery()
            ->getSingleScalarResult();
    }
}
