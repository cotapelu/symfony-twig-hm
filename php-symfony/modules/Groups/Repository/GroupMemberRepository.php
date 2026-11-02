<?php

namespace App\Groups\Repository;

use App\Groups\Entity\GroupMember;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class GroupMemberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GroupMember::class);
    }

    public function findByGroup(string $groupId): array
    {
        return $this->createQueryBuilder('gm')
            ->innerJoin('gm.user', 'u')
            ->where('gm.group = :groupId')
            ->andWhere('gm.isActive = :isActive')
            ->andWhere('gm.isBanned = :isNotBanned')
            ->setParameter('groupId', $groupId)
            ->setParameter('isActive', true)
            ->setParameter('isNotBanned', false)
            ->orderBy('gm.role', 'DESC')
            ->addOrderBy('gm.joinedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByUser(string $userId): array
    {
        return $this->createQueryBuilder('gm')
            ->innerJoin('gm.group', 'g')
            ->where('gm.user = :userId')
            ->andWhere('gm.isActive = :isActive')
            ->andWhere('gm.isBanned = :isNotBanned')
            ->setParameter('userId', $userId)
            ->setParameter('isActive', true)
            ->setParameter('isNotBanned', false)
            ->orderBy('gm.joinedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByGroupAndUser(string $groupId, string $userId): ?GroupMember
    {
        return $this->createQueryBuilder('gm')
            ->where('gm.group = :groupId')
            ->andWhere('gm.user = :userId')
            ->setParameter('groupId', $groupId)
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findMembersByRole(string $groupId, string $role): array
    {
        return $this->createQueryBuilder('gm')
            ->innerJoin('gm.user', 'u')
            ->where('gm.group = :groupId')
            ->andWhere('gm.role = :role')
            ->andWhere('gm.isActive = :isActive')
            ->andWhere('gm.isBanned = :isNotBanned')
            ->setParameter('groupId', $groupId)
            ->setParameter('role', $role)
            ->setParameter('isActive', true)
            ->setParameter('isNotBanned', false)
            ->getQuery()
            ->getResult();
    }

    public function countActiveMembers(string $groupId): int
    {
        return $this->createQueryBuilder('gm')
            ->select('COUNT(gm.id)')
            ->where('gm.group = :groupId')
            ->andWhere('gm.isActive = :isActive')
            ->andWhere('gm.isBanned = :isNotBanned')
            ->setParameter('groupId', $groupId)
            ->setParameter('isActive', true)
            ->setParameter('isNotBanned', false)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function updateLastActive(string $memberId): void
    {
        $this->createQueryBuilder('gm')
            ->update()
            ->set('gm.lastActiveAt', ':now')
            ->where('gm.id = :memberId')
            ->setParameter('now', new \DateTime())
            ->setParameter('memberId', $memberId)
            ->getQuery()
            ->execute();
    }
}