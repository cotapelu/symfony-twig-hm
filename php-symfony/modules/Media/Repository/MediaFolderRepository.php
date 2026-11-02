<?php declare(strict_types=1);

namespace App\Media\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Media\Entity\MediaFolder;

class MediaFolderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MediaFolder::class);
    }

    public function save(MediaFolder $folder): void
    {
        $this->getEntityManager()->persist($folder);
        $this->getEntityManager()->flush();
    }

    public function delete(MediaFolder $folder): void
    {
        $this->getEntityManager()->remove($folder);
        $this->getEntityManager()->flush();
    }

    public function findByUser(int $userId, ?int $parentId = null): array
    {
        $qb = $this->createQueryBuilder('f')
            ->andWhere('f.userId = :uid')
            ->setParameter('uid', $userId)
            ->orderBy('f.createdAt', 'DESC');

        if ($parentId !== null) {
            $qb->andWhere('f.parentId = :pid')
               ->setParameter('pid', $parentId);
        }

        return $qb->getQuery()->getResult();
    }

    public function countByUser(int $userId, ?int $parentId = null): int
    {
        $qb = $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->andWhere('f.userId = :uid')
            ->setParameter('uid', $userId);

        if ($parentId !== null) {
            $qb->andWhere('f.parentId = :pid')
               ->setParameter('pid', $parentId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
