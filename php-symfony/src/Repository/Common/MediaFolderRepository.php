<?php declare(strict_types=1);

namespace App\Repository\Common;

use App\Entity\Common\MediaFolder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MediaFolder>
 */
class MediaFolderRepository extends ServiceEntityRepository
{
    use FindByUserTrait;
    use CommonRepositoryTrait;
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MediaFolder::class);
    }




    public function findRootFolders(?string $userId = null): array
    {
        $qb = $this->createQueryBuilder('f')
            ->where('f.parentId IS NULL');

        if ($userId !== null) {
            $qb->andWhere('f.userId = :userId')
                ->setParameter('userId', $userId);
        }

        return $qb->getQuery()->getResult();
    }

    public function findByParentId(string $parentId): array
    {
        return $this->findBy(['parentId' => $parentId]);
    }

    public function findByPath(string $path): ?MediaFolder
    {
        return $this->findOneBy(['path' => $path]);
    }
}