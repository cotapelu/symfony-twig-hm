<?php

declare(strict_types=1);

namespace App\Repository\Php;

use App\Entity\Php\CitizenIncident;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CitizenIncidentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CitizenIncident::class);
    }

    public function findByCitizenId(string $citizenId): array
    {
        return $this->findBy(['citizenId' => $citizenId]);
    }

    public function findByStatus(string $status): array
    {
        return $this->findBy(['status' => $status]);
    }

    public function findByIncidentType(string $incidentType): array
    {
        return $this->findBy(['incidentType' => $incidentType]);
    }

    public function findActiveIncidents(): array
    {
        return $this->findBy(['status' => ['REPORTED', 'INVESTIGATING', 'PENDING']]);
    }
}