<?php

declare(strict_types=1);

namespace App\Repository\Common;

use App\Entity\Common\MediaFile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Repository\Common\CommonRepositoryTrait;

/**
 * @extends ServiceEntityRepository<MediaFile>
 */
class MediaFileRepository extends ServiceEntityRepository
{
    use CommonRepositoryTrait;
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MediaFile::class);
    }




    public function findByUserId(string $userId): array
    {
        return $this->findBy(['userId' => $userId], ['createdAt' => 'DESC']);
    }

    public function findByFolderId(string $folderId): array
    {
        return $this->findBy(['folderId' => $folderId], ['createdAt' => 'DESC']);
    }

    public function findByType(string $type): array
    {
        return $this->findBy(['type' => $type]);
    }

    public function findByMimeType(string $mimeType): array
    {
        return $this->findBy(['mimeType' => $mimeType]);
    }

    public function findActiveFiles(): array
    {
        return $this->findBy(['status' => 'ACTIVE']);
    }

    public function findInactiveFiles(): array
    {
        return $this->findBy(['status' => 'INACTIVE']);
    }

    public function findRecentFiles(int $limit = 100): array
    {
        return $this->findBy([], ['createdAt' => 'DESC'], $limit);
    }

    public function deleteByUserId(string $userId): int
    {
        return $this->createQueryBuilder('f')
            ->delete()
            ->where('f.userId = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->execute();
    }

    public function deleteByFolderId(string $folderId): int
    {
        return $this->createQueryBuilder('f')
            ->delete()
            ->where('f.folderId = :folderId')
            ->setParameter('folderId', $folderId)
            ->getQuery()
            ->execute();
    }

    public function countByUserId(string $userId): int
    {
        return $this->count(['userId' => $userId]);
    }

    public function countByFolderId(string $folderId): int
    {
        return $this->count(['folderId' => $folderId]);
    }

    public function getTotalSizeByUserId(string $userId): int
    {
        $result = $this->createQueryBuilder('f')
            ->select('SUM(f.size)')
            ->where('f.userId = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (int) $result : 0;
    }
}
