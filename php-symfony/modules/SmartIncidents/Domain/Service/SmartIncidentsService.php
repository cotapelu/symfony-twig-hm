<?php

declare(strict_types=1);

namespace App\SmartIncidents\Domain\Service;

use App\SmartIncidents\Domain\Entity\SmartIncident;
use App\SmartIncidents\Domain\Entity\SmartIncidentAttachment;
use App\SmartIncidents\Domain\Entity\SmartIncidentMessage;
use App\SmartIncidents\Domain\Entity\SmartIncidentActivity;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

class SmartIncidentsService
{
    private const MAX_TITLE_LENGTH = 255;
    private const MAX_DESCRIPTION_LENGTH = 5000;
    private const MAX_ADDRESS_LENGTH = 500;
    private const MAX_PHONE_LENGTH = 20;
    private const MAX_MESSAGE_LENGTH = 2000;
    private const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB

    private const ALLOWED_INCIDENT_TYPES = ['infrastructure', 'safety', 'environmental', 'traffic', 'utilities', 'other'];
    private const ALLOWED_SEVERITIES = ['low', 'medium', 'high', 'critical'];
    private const ALLOWED_STATUSES = ['reported', 'assigned', 'in_progress', 'awaiting_info', 'resolved', 'closed', 'escalated'];
    private const ALLOWED_FILE_TYPES = ['image', 'video', 'audio', 'document'];
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'video/mp4', 'video/webm',
        'audio/mpeg', 'audio/wav', 'audio/ogg',
        'application/pdf', 'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];

    public function __construct(
        private EntityManagerInterface $em
    ) {}

    public function createIncident(array $data): SmartIncident
    {
        $this->validateIncidentData($data);

        $incident = new SmartIncident();
        $incident->setTitle($data['title']);
        $incident->setDescription($data['description'] ?? null);
        $incident->setIncidentType($data['incident_type']);
        $incident->setSeverity($data['severity'] ?? 'medium');

        $this->applyReporterDetails($incident, $data);
        $this->applyLocation($incident, $data);
        if (!empty($data['ward_code'])) {
            $incident->setWardCode($data['ward_code']);
        }

        $this->em->persist($incident);
        $this->em->flush();

        $this->logActivity($incident, 'created', 'Incident reported', $data['reporter_name'] ?? null);

        return $incident;
    }

    private function applyReporterDetails(SmartIncident $incident, array $data): void
    {
        if (!empty($data['reporter_name'])) {
            $incident->setReporterName($data['reporter_name']);
        }
        if (!empty($data['reporter_phone'])) {
            $incident->setReporterPhone($data['reporter_phone']);
        }
        if (!empty($data['reporter_anonymous'])) {
            $incident->setReporterAnonymous(true);
        }
    }

    private function applyLocation(SmartIncident $incident, array $data): void
    {
        $incident->setLocation(
            $data['location_address'] ?? null,
            $data['latitude'] ?? null,
            $data['longitude'] ?? null
        );
    }

    public function getIncident(string $incidentNumber): ?SmartIncident
    {
        return $this->em->getRepository(SmartIncident::class)->findOneBy(['incidentNumber' => $incidentNumber]);
    }

    public function getIncidentById(int $id): ?SmartIncident
    {
        return $this->em->find(SmartIncident::class, $id);
    }

    public function getAllIncidents(array $filters = []): array
    {
        $qb = $this->em->getRepository(SmartIncident::class)->createQueryBuilder('i');

        if (!empty($filters['status'])) {
            $qb->andWhere('i.status = :status')->setParameter('status', $filters['status']);
        }
        if (!empty($filters['severity'])) {
            $qb->andWhere('i.severity = :severity')->setParameter('severity', $filters['severity']);
        }
        if (!empty($filters['ward_code'])) {
            $qb->andWhere('i.wardCode = :wardCode')->setParameter('wardCode', $filters['ward_code']);
        }
        if (!empty($filters['incident_type'])) {
            $qb->andWhere('i.incidentType = :type')->setParameter('type', $filters['incident_type']);
        }

        $qb->orderBy('i.reportedAt', 'DESC');

        if (!empty($filters['limit'])) {
            $qb->setMaxResults((int) $filters['limit']);
        }

        return $qb->getQuery()->getResult();
    }

    public function updateIncidentStatus(SmartIncident $incident, string $status, ?string $performedBy = null): SmartIncident
    {
        if (!in_array($status, self::ALLOWED_STATUSES)) {
            throw new InvalidArgumentException('Invalid status: ' . $status);
        }

        $oldStatus = $incident->getStatus();
        $incident->setStatus($status);

        $this->em->persist($incident);
        $this->em->flush();

        $this->logActivity($incident, 'status_changed', "Status changed from {$oldStatus} to {$status}", $performedBy);

        return $incident;
    }

    public function assignIncident(SmartIncident $incident, string $officerId, ?string $team = null, ?string $performedBy = null): SmartIncident
    {
        $incident->assignTo($officerId, $team);

        $this->em->persist($incident);
        $this->em->flush();

        $this->logActivity($incident, 'assigned', "Assigned to officer: {$officerId}" . ($team ? ", team: {$team}" : ''), $performedBy);

        return $incident;
    }

    public function resolveIncident(SmartIncident $incident, string $summary, ?string $performedBy = null): SmartIncident
    {
        $incident->resolve($summary);

        $this->em->persist($incident);
        $this->em->flush();

        $this->logActivity($incident, 'resolved', "Incident resolved: {$summary}", $performedBy);

        return $incident;
    }

    public function addAttachment(SmartIncident $incident, array $data): SmartIncidentAttachment
    {
        $this->validateAttachmentData($data);

        $attachment = new SmartIncidentAttachment(
            $incident,
            $data['file_type'],
            $data['file_name'],
            $data['file_url'],
            $data['file_size']
        );

        $attachment->setMimeType($data['mime_type'] ?? null);
        $attachment->setDescription($data['description'] ?? null);

        if (!empty($data['uploaded_by'])) {
            $attachment->setUploadedBy($data['uploaded_by'], $data['uploaded_by_role'] ?? null);
        }

        $incident->addAttachment($attachment);

        $this->em->persist($attachment);
        $this->em->flush();

        return $attachment;
    }

    public function addMessage(SmartIncident $incident, array $data): SmartIncidentMessage
    {
        $this->validateMessageData($data);

        $message = new SmartIncidentMessage($incident, $data['sender_type'], $data['content']);
        $message->setSenderId($data['sender_id']);
        $message->setSenderName($data['sender_name']);

        if (!empty($data['attachment_url'])) {
            $message->setAttachmentUrl($data['attachment_url']);
        }

        $incident->addMessage($message);

        $this->em->persist($message);
        $this->em->flush();

        return $message;
    }

    public function getMessages(SmartIncident $incident): array
    {
        return $incident->getMessages()->toArray();
    }

    public function getActivityLog(SmartIncident $incident): array
    {
        return $incident->getActivityLog()->toArray();
    }

    public function incrementViewCount(SmartIncident $incident): void
    {
        $incident->incrementViewCount();
        $this->em->persist($incident);
        $this->em->flush();
    }

    public function upvoteIncident(SmartIncident $incident): void
    {
        $incident->incrementUpvote();
        $this->em->persist($incident);
        $this->em->flush();
    }

    private function logActivity(SmartIncident $incident, string $type, string $description, ?string $performedBy = null): void
    {
        $performedById = $performedBy ?? 'system';
        $performedByName = $performedBy ?? 'System';

        $activity = new SmartIncidentActivity($incident, $type, $description, $performedById, $performedByName);
        $incident->addActivity($activity);
        $this->em->persist($activity);
        $this->em->flush();
    }

    private function validateIncidentData(array $data): void
    {
        $this->validateTitle($data);
        $this->validateDescription($data);
        $this->validateIncidentType($data);
        $this->validateSeverity($data);
        $this->validateReporterPhone($data);
        $this->validateLatitude($data);
        $this->validateLongitude($data);
        $this->validateLocationAddress($data);
    }

    private function validateTitle(array $data): void
    {
        if (empty($data['title']) || !is_string($data['title'])) {
            throw new InvalidArgumentException('Title is required');
        }
        if (strlen($data['title']) > self::MAX_TITLE_LENGTH) {
            throw new InvalidArgumentException('Title too long (max ' . self::MAX_TITLE_LENGTH . ' chars)');
        }
    }

    private function validateDescription(array $data): void
    {
        if (!empty($data['description']) && strlen($data['description']) > self::MAX_DESCRIPTION_LENGTH) {
            throw new InvalidArgumentException('Description too long (max ' . self::MAX_DESCRIPTION_LENGTH . ' chars)');
        }
    }

    private function validateIncidentType(array $data): void
    {
        if (empty($data['incident_type']) || !is_string($data['incident_type'])) {
            throw new InvalidArgumentException('Incident type is required');
        }
        if (!in_array($data['incident_type'], self::ALLOWED_INCIDENT_TYPES)) {
            throw new InvalidArgumentException('Invalid incident type');
        }
    }

    private function validateSeverity(array $data): void
    {
        if (!empty($data['severity']) && !in_array($data['severity'], self::ALLOWED_SEVERITIES)) {
            throw new InvalidArgumentException('Invalid severity');
        }
    }

    private function validateReporterPhone(array $data): void
    {
        if (!empty($data['reporter_phone']) && strlen($data['reporter_phone']) > self::MAX_PHONE_LENGTH) {
            throw new InvalidArgumentException('Phone number too long');
        }
    }

    private function validateLatitude(array $data): void
    {
        if (!empty($data['latitude'])) {
            $lat = (float) $data['latitude'];
            if ($lat < -90 || $lat > 90) {
                throw new InvalidArgumentException('Invalid latitude');
            }
        }
    }

    private function validateLongitude(array $data): void
    {
        if (!empty($data['longitude'])) {
            $lng = (float) $data['longitude'];
            if ($lng < -180 || $lng > 180) {
                throw new InvalidArgumentException('Invalid longitude');
            }
        }
    }

    private function validateLocationAddress(array $data): void
    {
        if (!empty($data['location_address']) && strlen($data['location_address']) > self::MAX_ADDRESS_LENGTH) {
            throw new InvalidArgumentException('Address too long');
        }
    }

    private function validateAttachmentData(array $data): void
    {
        $this->validateFileType($data);
        $this->validateFileName($data);
        $this->validateFileUrl($data);
        $this->validateFileSize($data);
        $this->validateFileSizeRange($data);
        $this->validateMimeType($data);
    }

    private function validateFileType(array $data): void
    {
        if (empty($data['file_type']) || !in_array($data['file_type'], self::ALLOWED_FILE_TYPES)) {
            throw new InvalidArgumentException('Invalid file type');
        }
    }

    private function validateFileName(array $data): void
    {
        if (empty($data['file_name']) || !is_string($data['file_name'])) {
            throw new InvalidArgumentException('File name is required');
        }
    }

    private function validateFileUrl(array $data): void
    {
        if (empty($data['file_url']) || !is_string($data['file_url'])) {
            throw new InvalidArgumentException('File URL is required');
        }
    }

    private function validateFileSize(array $data): void
    {
        if (empty($data['file_size']) || !is_int($data['file_size'])) {
            throw new InvalidArgumentException('File size is required');
        }
    }

    private function validateFileSizeRange(array $data): void
    {
        if ($data['file_size'] < 0 || $data['file_size'] > self::MAX_FILE_SIZE) {
            throw new InvalidArgumentException('File size exceeds maximum allowed (10MB)');
        }
    }

    private function validateMimeType(array $data): void
    {
        if (!empty($data['mime_type']) && !in_array($data['mime_type'], self::ALLOWED_MIME_TYPES)) {
            throw new InvalidArgumentException('Invalid MIME type');
        }
    }

    private function validateMessageData(array $data): void
    {
        if (empty($data['sender_type']) || !is_string($data['sender_type'])) {
            throw new InvalidArgumentException('Sender type is required');
        }
        if (empty($data['sender_id']) || !is_string($data['sender_id'])) {
            throw new InvalidArgumentException('Sender ID is required');
        }
        if (empty($data['sender_name']) || !is_string($data['sender_name'])) {
            throw new InvalidArgumentException('Sender name is required');
        }
        if (empty($data['content']) || !is_string($data['content'])) {
            throw new InvalidArgumentException('Message content is required');
        }
        if (strlen($data['content']) > self::MAX_MESSAGE_LENGTH) {
            throw new InvalidArgumentException('Message too long (max ' . self::MAX_MESSAGE_LENGTH . ' chars)');
        }
    }
}
