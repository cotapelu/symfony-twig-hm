<?php declare(strict_types=1);

namespace App\Media\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Media\Entity\MediaFile;

class MediaFileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MediaFile::class);
    }

    public function save(MediaFile $file): void
    {
        $this->getEntityManager()->persist($file);
        $this->getEntityManager()->flush();
    }

    public function delete(MediaFile $file): void
    {
        $this->getEntityManager()->remove($file);
        $this->getEntityManager()->flush();
    }

    public function findByUser(int $userId, ?int $folderId = null, int $limit = 50, int $offset = 0): array
    {
        $qb = $this->createQueryBuilder('f')
            ->andWhere('f.userId = :uid')
            ->setParameter('uid', $userId)
            ->orderBy('f.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        if ($folderId !== null) {
            $qb->andWhere('f.folderId = :fid')
               ->setParameter('fid', $folderId);
        }

        return $qb->getQuery()->getResult();
    }

    public function countByUser(int $userId, ?int $folderId = null): int
    {
        $qb = $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->andWhere('f.userId = :uid')
            ->setParameter('uid', $userId);

        if ($folderId !== null) {
            $qb->andWhere('f.folderId = :fid')
               ->setParameter('fid', $folderId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
