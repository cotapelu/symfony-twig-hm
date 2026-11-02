<?php

declare(strict_types=1);

namespace App\SecurityEmergency\Repository;

use App\SecurityEmergency\Entity\SecurityIncident;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SecurityIncidentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SecurityIncident::class);
    }

    public function save(SecurityIncident $incident): void
    {
        $this->getEntityManager()->persist($incident);
        $this->getEntityManager()->flush();
    }

    public function delete(SecurityIncident $incident): void
    {
        $this->getEntityManager()->remove($incident);
        $this->getEntityManager()->flush();
    }

    public function findById(string $id): ?SecurityIncident
    {
        return $this->find($id);
    }

    public function findAllByStatus(string $status, int $limit = 50, int $offset = 0): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.status = :status')
            ->setParameter('status', $status)
            ->orderBy('i.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function findAllByType(string $type, int $limit = 50, int $offset = 0): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.type = :type')
            ->setParameter('type', $type)
            ->orderBy('i.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function findByReporter(string $reporterId, int $limit = 50, int $offset = 0): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.reporterId = :reporterId')
            ->setParameter('reporterId', $reporterId)
            ->orderBy('i.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }
}
