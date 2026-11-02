<?php

declare(strict_types=1);

namespace App\User\Repository;

use App\Mapper\UserProfileMapper;
use App\User\Entity\UserProfile;
use App\User\Entity\UserProfile as UserProfileEntity;
use Doctrine\ORM\EntityManagerInterface;

class UserProfileRepositoryImpl implements UserProfileRepositoryInterface
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function save(UserProfile $profile): UserProfile
    {
        $entity = UserProfileMapper::toDoctrine($profile);

        $existing = $this->entityManager->find(UserProfileEntity::class, $profile->getUserId());
        if ($existing) {
            // Update existing
            $existing->setDisplayName($entity->getDisplayName());
            $existing->setAvatarUrl($entity->getAvatarUrl());
            $existing->setStatus($entity->getStatus());
            $existing->setBio($entity->getBio());
            $existing->setTimezone($entity->getTimezone());
            $existing->setLastSeen($entity->getLastSeen());
            $existing->setPreferences($entity->getPreferences());
        } else {
            // Create new
            $this->entityManager->persist($entity);
        }

        $this->entityManager->flush();

        // Convert back to domain entity for return
        return UserProfileMapper::toDomain($entity);
    }

    /**
     * Legacy: find by integer ID (not applicable, returns null).
     * The entity uses user_id (string) as primary key.
     */
    public function findById(int $id): ?UserProfile
    {
        return null;
    }

    public function findByUserId(string $userId): ?UserProfile
    {
        $entity = $this->entityManager->find(UserProfileEntity::class, $userId);
        return $entity ? UserProfileMapper::toDomain($entity) : null;
    }

    public function findByIds(array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('u')
           ->from(UserProfileEntity::class, 'u')
           ->where('u.userId IN (:userIds)')
           ->setParameter('userIds', $userIds);

        $entities = $qb->getQuery()->getResult();

        return array_map([UserProfileMapper::class, 'toDomain'], $entities);
    }

    public function findOnlineUsers(): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('u')
           ->from(UserProfileEntity::class, 'u')
           ->where('u.status = :status')
           ->setParameter('status', 'online');

        $entities = $qb->getQuery()->getResult();

        return array_map([UserProfileMapper::class, 'toDomain'], $entities);
    }

    public function updateLastSeen(int $userId): void
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->update(UserProfileEntity::class, 'u')
           ->set('u.lastSeen', ':lastSeen')
           ->where('u.userId = :userId')
           ->setParameter('lastSeen', new \DateTime())
           ->setParameter('userId', $userId)
           ->getQuery()
           ->execute();
    }

    public function delete(string $userId): bool
    {
        $entity = $this->entityManager->find(UserProfileEntity::class, $userId);
        if ($entity) {
            $this->entityManager->remove($entity);
            $this->entityManager->flush();
            return true;
        }
        return false;
    }

    public function searchByDisplayName(string $query, int $limit = 10): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('u')
           ->from(UserProfileEntity::class, 'u')
           ->where('u.displayName LIKE :query')
           ->setParameter('query', '%' . $query . '%')
           ->setMaxResults($limit);

        $entities = $qb->getQuery()->getResult();

        return array_map([UserProfileMapper::class, 'toDomain'], $entities);
    }
}
