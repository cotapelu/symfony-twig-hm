<?php

declare(strict_types=1);

namespace App\ANTT\Application\Service;

use App\ANTT\Domain\Entity\Incident;
use App\ANTT\Domain\Entity\Patrol;
use App\ANTT\Domain\Entity\SecurityCase;
use App\ANTT\Domain\Repository\IncidentRepositoryInterface;
use App\ANTT\Domain\Repository\PatrolRepositoryInterface;
use App\ANTT\Domain\Repository\SecurityCaseRepositoryInterface;
use App\Government\Domain\Entity\Officer;
use App\Government\Domain\Entity\Ward;
use App\Government\Domain\Repository\OfficerRepositoryInterface;
use App\Government\Domain\Repository\WardRepositoryInterface;
use App\Government\Domain\ValueObject\OfficerId;

class GovernmentIntegrationService
{
    public function __construct(
        private readonly IncidentRepositoryInterface $incidentRepository,
        private readonly PatrolRepositoryInterface $patrolRepository,
        private readonly SecurityCaseRepositoryInterface $securityCaseRepository,
        private readonly OfficerRepositoryInterface $officerRepository,
        private readonly WardRepositoryInterface $wardRepository
    ) {}

    public function getIncidentsForOfficer(string $officerId): array
    {
        $officer = $this->officerRepository->findById(new OfficerId($officerId));
        if (!$officer) {
            return [];
        }

        $wards = $this->wardRepository->findByAssignedOfficer($officerId);
        if (empty($wards)) {
            return [];
        }

        $incidents = [];
        foreach ($wards as $ward) {
            $incidents = array_merge(
                $incidents,
                $this->incidentRepository->findByWard($ward->getId())
            );
        }

        return $incidents;
    }

    public function getPatrolsForOfficer(string $officerId): array
    {
        return $this->patrolRepository->findByOfficer($officerId);
    }

    public function getSecurityCasesForOfficer(string $officerId): array
    {
        return $this->securityCaseRepository->findByInvestigationOfficer($officerId);
    }

    public function getWardStatistics(string $wardCode): array
    {
        $incidentsByStatus = $this->incidentRepository->countByWardCode($wardCode);
        
        return [
            'incidents' => $incidentsByStatus,
            'total_incidents' => array_sum($incidentsByStatus),
            'open_incidents' => ($incidentsByStatus['pending'] ?? 0) + ($incidentsByStatus['assigned'] ?? 0),
        ];
    }

    public function linkIncidentToWard(string $incidentId, string $wardCode): bool
    {
        $incident = $this->incidentRepository->findById($incidentId);
        if (!$incident) {
            return false;
        }

        $ward = $this->wardRepository->findByCode($wardCode);
        if (!$ward) {
            return false;
        }

        $incident->setWardCode($wardCode);
        $this->incidentRepository->save($incident);

        return true;
    }

    public function linkCaseToWard(string $caseId, string $wardCode): bool
    {
        $case = $this->securityCaseRepository->findById($caseId);
        if (!$case) {
            return false;
        }

        $ward = $this->wardRepository->findByCode($wardCode);
        if (!$ward) {
            return false;
        }

        $case->setLocation($case->getLocationAddress() ?? '', $wardCode);
        $this->securityCaseRepository->save($case);

        return true;
    }

    public function assignIncidentToOfficer(string $incidentId, string $officerId): bool
    {
        $incident = $this->incidentRepository->findById($incidentId);
        if (!$incident) {
            return false;
        }

        $officer = $this->officerRepository->findById(new OfficerId($officerId));
        if (!$officer) {
            return false;
        }

        $incident->assignTo($officerId, $officer->getDepartment()->getName());
        $this->incidentRepository->save($incident);

        return true;
    }

    public function createSecurityCaseFromIncident(string $incidentId): ?SecurityCase
    {
        $incident = $this->incidentRepository->findById($incidentId);
        if (!$incident || $incident->getSeverity() !== 'high') {
            return null;
        }

        $case = new SecurityCase();
        $case->setTitle('Investigation: ' . $incident->getTitle());
        $case->setCaseType('investigation');
        $case->setDescription('Case created from incident: ' . $incident->getDescription());
        $case->setLocation($incident->getLocationAddress() ?? '', $incident->getWardCode());
        $case->setPriority('high');

        $this->securityCaseRepository->save($case);

        return $case;
    }

    public function getDashboardData(string $officerId): array
    {
        $officer = $this->officerRepository->findById(new OfficerId($officerId));
        if (!$officer) {
            return [];
        }

        $wards = $this->wardRepository->findByAssignedOfficer($officerId);
        $ward = $wards[0] ?? null;
        $wardCode = $ward?->getCode();

        $incidents = $this->getIncidentsForOfficer($officerId);
        $patrols = $this->getPatrolsForOfficer($officerId);
        $cases = $this->getSecurityCasesForOfficer($officerId);

        return [
            'officer' => [
                'id' => $officer->getId()->getValue(),
                'name' => $officer->getFullName(),
                'department' => $officer->getDepartment()->getName(),
                'position' => $officer->getPosition()->getName(),
            ],
            'ward' => $ward ? [
                'code' => $ward->getCode(),
                'name' => $ward->getName(),
                'population' => $ward->getPopulation(),
            ] : null,
            'statistics' => $wardCode ? $this->getWardStatistics($wardCode) : [],
            'assigned_incidents_count' => count($incidents),
            'assigned_patrols_count' => count($patrols),
            'assigned_cases_count' => count($cases),
        ];
    }
}
