<?php

declare(strict_types=1);

namespace App\MiniPrograms\Repository;

use App\MiniPrograms\Entity\MiniProgram;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MiniProgramRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MiniProgram::class);
    }

    public function save(MiniProgram $program): void
    {
        $this->getEntityManager()->persist($program);
        $this->getEntityManager()->flush();
    }

    public function findByAppId(string $appId): ?MiniProgram
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.appId = :appId')
            ->andWhere('m.isActive = true')
            ->setParameter('appId', $appId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findAllActive(int $limit = 50): array
    {
        return $this->findBy(['isActive' => true], ['createdAt' => 'DESC'], $limit);
    }

    public function searchPrograms(string $query, int $limit = 20): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.isActive = true')
            ->andWhere('(m.name LIKE :query OR m.description LIKE :query)')
            ->setParameter('query', '%'.$query.'%')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByDeveloper(string $developerId, int $limit = 50): array
    {
        return $this->findBy(['developerId' => $developerId], ['createdAt' => 'DESC'], $limit);
    }

    public function incrementInstallCount(string $appId): int
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'UPDATE mini_programs SET install_count = install_count + 1 WHERE app_id = :appId AND is_active = 1';
        return $conn->executeStatement($sql, ['appId' => $appId]);
    }
}
