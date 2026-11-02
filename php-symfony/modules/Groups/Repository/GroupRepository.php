<?php

namespace App\Groups\Repository;

use App\Groups\Entity\Group;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class GroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Group::class);
    }

    public function findByUser(string $userId): array
    {
        return $this->createQueryBuilder('g')
            ->innerJoin('g.members', 'gm')
            ->where('gm.user = :userId')
            ->andWhere('gm.isActive = :isActive')
            ->andWhere('gm.isBanned = :isNotBanned')
            ->setParameter('userId', $userId)
            ->setParameter('isActive', true)
            ->setParameter('isNotBanned', false)
            ->orderBy('g.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findPublicGroups(int $limit = 50, int $offset = 0): array
    {
        return $this->createQueryBuilder('g')
            ->where('g.isPublic = :isPublic')
            ->andWhere('g.isActive = :isActive')
            ->setParameter('isPublic', true)
            ->setParameter('isActive', true)
            ->orderBy('g.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function findCommunities(int $limit = 50, int $offset = 0): array
    {
        return $this->createQueryBuilder('g')
            ->where('g.isCommunity = :isCommunity')
            ->andWhere('g.isActive = :isActive')
            ->setParameter('isCommunity', true)
            ->setParameter('isActive', true)
            ->orderBy('g.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function findChannels(int $limit = 50, int $offset = 0): array
    {
        return $this->createQueryBuilder('g')
            ->where('g.isChannel = :isChannel')
            ->andWhere('g.isActive = :isActive')
            ->setParameter('isChannel', true)
            ->setParameter('isActive', true)
            ->orderBy('g.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function searchGroups(string $query, int $limit = 20): array
    {
        return $this->createQueryBuilder('g')
            ->where('g.isActive = :isActive')
            ->andWhere(
                $this->createQueryBuilder('g')->expr()->orX(
                    'g.name LIKE :query',
                    'g.description LIKE :query'
                )
            )
            ->setParameter('isActive', true)
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('g.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}