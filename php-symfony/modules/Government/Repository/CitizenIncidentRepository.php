<?php

declare(strict_types=1);

namespace App\Government\Repository;

use App\Government\Entity\CitizenIncident;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CitizenIncidentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CitizenIncident::class);
    }

    public function save(CitizenIncident $incident): void
    {
        $this->getEntityManager()->persist($incident);
        $this->getEntityManager()->flush();
    }

    public function delete(CitizenIncident $incident): void
    {
        $this->getEntityManager()->remove($incident);
        $this->getEntityManager()->flush();
    }

    public function findByCitizenId(int $citizenId): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.citizenId = :citizenId')
            ->setParameter('citizenId', $citizenId)
            ->orderBy('i.incidentDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.status = :status')
            ->setParameter('status', $status)
            ->orderBy('i.incidentDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByDateRange(\DateTime $startDate, \DateTime $endDate): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.incidentDate >= :startDate')
            ->andWhere('i.incidentDate <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('i.incidentDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countByStatus(string $status): int
    {
        return (int) $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->where('i.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }
}