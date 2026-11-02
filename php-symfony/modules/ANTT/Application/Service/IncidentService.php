<?php

declare(strict_types=1);

namespace App\ANTT\Application\Service;

use App\ANTT\Domain\Entity\Incident;
use App\ANTT\Domain\Repository\IncidentRepositoryInterface;
use App\ANTT\Application\DTO\CreateIncidentRequest;
use App\ANTT\Application\DTO\UpdateIncidentRequest;
use App\ANTT\Application\DTO\IncidentStatistics;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

/**
 * Service for managing incident-related operations
 */
class IncidentService
{
    public function __construct(
        private readonly IncidentRepositoryInterface $incidentRepository,
        private readonly ?LoggerInterface $logger = null
    ) {}

    public function createIncident(CreateIncidentRequest $request, string $reporterId): Incident
    {
        $this->validateCreateIncidentRequest($request);

        $incident = new Incident();
        $incident->setTitle($request->title);
        $incident->setDescription($request->description);
        $incident->setIncidentType($request->incidentType);
        $incident->setSeverity($request->severity ?? 'medium');
        $incident->setWardCode($request->wardCode);
        $incident->setLocation($request->locationAddress, $request->latitude, $request->longitude);
        $incident->setReporterId($reporterId);

        if (!$request->reporterAnonymous) {
            $incident->setReporterName($request->reporterName);
            $incident->setReporterPhone($request->reporterPhone);
        }

        $this->incidentRepository->save($incident);

        $this->logger?->info('Incident created', [
            'incident_id' => $incident->getId(),
            'type' => $incident->getIncidentType(),
            'severity' => $incident->getSeverity(),
            'reporter_id' => $reporterId,
        ]);

        return $incident;
    }

    public function updateIncident(int $id, UpdateIncidentRequest $request): ?Incident
    {
        $this->validateId($id);
        
        $incident = $this->incidentRepository->find($id);
        if (!$incident) {
            return null;
        }

        if ($request->title !== null) {
            $incident->setTitle($request->title);
        }
        if ($request->description !== null) {
            $incident->setDescription($request->description);
        }
        if ($request->incidentType !== null) {
            $incident->setIncidentType($request->incidentType);
        }
        if ($request->severity !== null) {
            $incident->setSeverity($request->severity);
        }
        if ($request->status !== null) {
            $oldStatus = $incident->getStatus();
            $incident->setStatus($request->status);
            $this->logger?->info('Incident status changed', [
                'incident_id' => $incident->getId(),
                'old_status' => $oldStatus,
                'new_status' => $request->status,
            ]);
        }

        $this->incidentRepository->save($incident);

        $this->logger?->info('Incident updated', [
            'incident_id' => $incident->getId(),
            'updated_fields' => array_keys(array_filter([
                'title' => $request->title !== null,
                'description' => $request->description !== null,
                'incidentType' => $request->incidentType !== null,
                'severity' => $request->severity !== null,
                'status' => $request->status !== null,
            ])),
        ]);

        return $incident;
    }

    public function assignIncident(int $id, string $officerId, ?string $team = null): ?Incident
    {
        $this->validateId($id);
        $this->validateStringNotEmpty($officerId, 'Officer ID');
        
        $incident = $this->incidentRepository->find($id);
        if (!$incident) {
            return null;
        }

        $incident->assignTo($officerId, $team);
        $this->incidentRepository->save($incident);

        $this->logger?->info('Incident assigned', [
            'incident_id' => $incident->getId(),
            'officer_id' => $officerId,
            'team' => $team,
        ]);

        return $incident;
    }

    public function resolveIncident(int $id, string $summary): ?Incident
    {
        $this->validateId($id);
        $this->validateStringNotEmpty($summary, 'Resolution summary');
        
        $incident = $this->incidentRepository->find($id);
        if (!$incident) {
            return null;
        }

        $incident->resolve($summary);
        $this->incidentRepository->save($incident);

        return $incident;
    }

    public function closeIncident(int $id): ?Incident
    {
        $this->validateId($id);
        
        $incident = $this->incidentRepository->find($id);
        if (!$incident) {
            return null;
        }

        $incident->close();
        $this->incidentRepository->save($incident);

        return $incident;
    }

    public function escalateIncident(int $id): ?Incident
    {
        $this->validateId($id);
        
        $incident = $this->incidentRepository->find($id);
        if (!$incident) {
            return null;
        }

        $incident->escalate();
        $this->incidentRepository->save($incident);

        return $incident;
    }

    public function getIncident(int $id): ?Incident
    {
        $this->validateId($id);
        
        return $this->incidentRepository->find($id);
    }

    public function getIncidentByNumber(string $number): ?Incident
    {
        $this->validateStringNotEmpty($number, 'Incident number');
        
        return $this->incidentRepository->findByIncidentNumber($number);
    }

    public function getOpenIncidents(int $limit = 50, int $offset = 0): array
    {
        return $this->incidentRepository->findOpenIncidents($limit, $offset);
    }

    public function getOpenIncidentsByReporter(string $reporterId): array
    {
        $this->validateStringNotEmpty($reporterId, 'Reporter ID');
        $allOpen = $this->incidentRepository->findOpenIncidents();
        return array_filter($allOpen, fn($i) => $i->getReporterId() === $reporterId);
    }

    public function getOpenIncidentsByOfficer(string $officerId): array
    {
        $this->validateStringNotEmpty($officerId, 'Officer ID');
        $assigned = $this->incidentRepository->findByAssignedOfficer($officerId);
        return array_filter($assigned, fn($i) => $i->isOpen());
    }

    public function getRecentIncidents(int $limit = 10): array
    {
        $this->validateLimit($limit);

        return $this->incidentRepository->findRecent($limit);
    }

    // Methods expected by ANTTController
    public function listIncidents(int $limit, int $offset): array
    {
        // Use open incidents with pagination
        return $this->incidentRepository->findOpenIncidents($limit, $offset);
    }

    public function countIncidents(): int
    {
        return $this->incidentRepository->count();
    }

    public function getIncidentsByWard(string $wardCode): array
    {
        $this->validateStringNotEmpty($wardCode, 'Ward code');
        
        return $this->incidentRepository->findByWardCode($wardCode);
    }

    public function getIncidentsByOfficer(string $officerId): array
    {
        $this->validateStringNotEmpty($officerId, 'Officer ID');

        return $this->incidentRepository->findByAssignedOfficer($officerId);
    }

    public function getIncidentsByReporter(string $reporterId): array
    {
        $this->validateStringNotEmpty($reporterId, 'Reporter ID');

        return $this->incidentRepository->findByReporterId($reporterId);
    }

    public function searchIncidents(array $criteria): array
    {
        if (!is_array($criteria)) {
            throw new InvalidArgumentException('Search criteria must be an array');
        }
        
        return $this->incidentRepository->search($criteria);
    }

    public function getStatistics(?string $wardCode = null): IncidentStatistics
    {
        // Validate wardCode if provided
        if ($wardCode !== null) {
            $this->validateStringNotEmpty($wardCode, 'Ward code');
        }

        // Get status counts - filtered by wardCode if provided
        if ($wardCode !== null) {
            $byStatus = $this->incidentRepository->countByWardCode($wardCode);
            $byType = $this->incidentRepository->countByTypeWithWard($wardCode);
        } else {
            $byStatus = $this->incidentRepository->countByStatus();
            $byType = $this->incidentRepository->countByType();
        }

        $total = array_sum($byStatus);
        $open = ($byStatus['reported'] ?? 0) + ($byStatus['assigned'] ?? 0) + ($byStatus['in_progress'] ?? 0);
        $resolved = ($byStatus['resolved'] ?? 0) + ($byStatus['closed'] ?? 0);

        return new IncidentStatistics(
            total: $total,
            open: $open,
            resolved: $resolved,
            byStatus: $byStatus,
            byType: $byType
        );
    }

    /**
     * Validate that an ID is a positive integer
     *
     * @param int $id The ID to validate
     * @throws InvalidArgumentException If ID is not valid
     */
    private function validateId(int $id): void
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('ID must be a positive integer');
        }
    }

    /**
     * Validate that a string is not empty
     *
     * @param string $value The string to validate
     * @param string $fieldName The field name for error messages
     * @throws InvalidArgumentException If string is empty
     */
    private function validateStringNotEmpty(string $value, string $fieldName): void
    {
        if (trim($value) === '') {
            throw new InvalidArgumentException("$fieldName must not be empty");
        }
    }

    /**
     * Validate a CreateIncidentRequest
     *
     * @param CreateIncidentRequest $request The request to validate
     * @throws InvalidArgumentException If validation fails
     */
    private function validateCreateIncidentRequest(CreateIncidentRequest $request): void
    {
        $this->validateStringNotEmpty($request->title, 'Title');
        $this->validateStringNotEmpty($request->incidentType, 'Incident type');
        
        // Validate location coordinates if provided
        if ($request->latitude !== null && ($request->latitude < -90 || $request->latitude > 90)) {
            throw new InvalidArgumentException('Latitude must be between -90 and 90 degrees');
        }
        
        if ($request->longitude !== null && ($request->longitude < -180 || $request->longitude > 180)) {
            throw new InvalidArgumentException('Longitude must be between -180 and 180 degrees');
        }
        
        // Validate reporter phone if provided and not anonymous
        if (!$request->reporterAnonymous && $request->reporterPhone !== null) {
            // Simple phone validation - adjust as needed for your region
            if (!preg_match('/^[\d\s\-\(\)\+]+$/', $request->reporterPhone)) {
                throw new InvalidArgumentException('Reporter phone number contains invalid characters');
            }
        }
    }

    /**
     * Validate a limit value
     *
     * @param int $limit The limit to validate
     * @throws InvalidArgumentException If limit is not valid
     */
    private function validateLimit(int $limit): void
    {
        if ($limit <= 0) {
            throw new InvalidArgumentException('Limit must be a positive integer');
        }
        
        // Optional: set a reasonable maximum to prevent excessive resource usage
        if ($limit > 1000) {
            throw new InvalidArgumentException('Limit cannot exceed 1000');
        }
    }
}
