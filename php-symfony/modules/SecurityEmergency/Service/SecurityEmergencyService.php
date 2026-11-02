<?php

declare(strict_types=1);

namespace App\SecurityEmergency\Service;

use App\SecurityEmergency\Entity\SecurityIncident;
use App\SecurityEmergency\Repository\SecurityIncidentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class SecurityEmergencyService
{
    private SecurityIncidentRepository $incidentRepository;
    private EntityManagerInterface $em;
    private LoggerInterface $logger;

    public function __construct(
        SecurityIncidentRepository $incidentRepository,
        EntityManagerInterface $em,
        LoggerInterface $logger
    ) {
        $this->incidentRepository = $incidentRepository;
        $this->em = $em;
        $this->logger = $logger;
    }

    public function createIncident(
        string $type,
        string $reporterId,
        ?string $location = null,
        ?string $description = null,
        string $status = 'Đang điều tra'
    ): SecurityIncident {
        if (!in_array($type, ['Trộm cắp', 'Tai nạn giao thông', 'Hỏa hoạn', 'Vi phạm trật tự', 'Khác'], true)) {
            throw new \InvalidArgumentException('Invalid incident type');
        }
        if (!in_array($status, ['Đang điều tra', 'Đang xử lý', 'Đã xử lý'], true)) {
            throw new \InvalidArgumentException('Invalid incident status');
        }

        $incident = new SecurityIncident($type, $status, $reporterId, $location, $description);
        $this->incidentRepository->save($incident);

        $this->logger->info('Security incident created', [
            'incident_id' => $incident->getId(),
            'type' => $type,
            'reporter_id' => $reporterId,
        ]);

        return $incident;
    }

    public function getIncident(string $id): ?SecurityIncident
    {
        return $this->incidentRepository->findById($id);
    }

    public function updateIncidentStatus(string $id, string $newStatus): ?SecurityIncident
    {
        $incident = $this->incidentRepository->findById($id);
        if (!$incident) {
            return null;
        }
        $incident->setStatus($newStatus);
        $incident->setUpdatedAt(new \DateTime());
        $this->incidentRepository->save($incident);

        $this->logger->info('Security incident status updated', [
            'incident_id' => $id,
            'new_status' => $newStatus,
        ]);

        return $incident;
    }

    public function deleteIncident(string $id): bool
    {
        $incident = $this->incidentRepository->findById($id);
        if (!$incident) {
            return false;
        }
        $this->incidentRepository->delete($incident);
        return true;
    }

    public function getIncidentsByStatus(string $status, int $limit = 50, int $offset = 0): array
    {
        return $this->incidentRepository->findAllByStatus($status, $limit, $offset);
    }

    public function getIncidentsByType(string $type, int $limit = 50, int $offset = 0): array
    {
        return $this->incidentRepository->findAllByType($type, $limit, $offset);
    }

    public function getIncidentsByReporter(string $reporterId, int $limit = 50, int $offset = 0): array
    {
        return $this->incidentRepository->findByReporter($reporterId, $limit, $offset);
    }

    public function getAllIncidents(int $limit = 50, int $offset = 0): array
    {
        return $this->incidentRepository->findBy([], ['createdAt' => 'DESC'], $limit, $offset);
    }

    /**
     * Assign an incident to an officer and optionally set location.
     */
    public function assignIncident(string $id, string $officerId, ?string $location = null): ?SecurityIncident
    {
        $incident = $this->incidentRepository->findById($id);
        if (!$incident) {
            return null;
        }
        $incident->setAssignedOfficerId($officerId);
        if ($location !== null) {
            $incident->setLocation($location);
        }
        $incident->setUpdatedAt(new \DateTime());
        $this->incidentRepository->save($incident);

        $this->logger->info('Incident assigned', [
            'incident_id' => $id,
            'officer_id' => $officerId,
            'location' => $location,
        ]);

        return $incident;
    }

    /**
     * Escalate an incident (currently logs and updates timestamp).
     */
    public function escalateIncident(string $id): ?SecurityIncident
    {
        $incident = $this->incidentRepository->findById($id);
        if (!$incident) {
            return null;
        }
        // For now, escalation just logs and updates timestamp.
        // Could change status or priority in future.
        $incident->setUpdatedAt(new \DateTime());
        $this->incidentRepository->save($incident);

        $this->logger->info('Incident escalated', [
            'incident_id' => $id,
            'current_status' => $incident->getStatus(),
        ]);

        return $incident;
    }
}
