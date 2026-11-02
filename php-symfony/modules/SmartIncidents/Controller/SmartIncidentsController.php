<?php

declare(strict_types=1);

namespace App\SmartIncidents\Controller;

use App\SmartIncidents\Domain\Service\SmartIncidentsService;
use App\SmartIncidents\Domain\Entity\SmartIncident;
use App\Shared\Controller\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

#[Route('/api/smart-incidents')]
class SmartIncidentsController extends BaseController
{
    private const ALLOWED_INCIDENT_TYPES = ['infrastructure', 'safety', 'environmental', 'traffic', 'utilities', 'other'];
    private const ALLOWED_SEVERITIES = ['low', 'medium', 'high', 'critical'];
    private const ALLOWED_STATUSES = ['reported', 'assigned', 'in_progress', 'awaiting_info', 'resolved', 'closed', 'escalated'];
    private const MAX_TITLE_LENGTH = 255;
    private const MAX_DESCRIPTION_LENGTH = 5000;
    private const MAX_LIMIT = 100;

    public function __construct(
        private SmartIncidentsService $smartIncidentsService,
        private ?TokenStorageInterface $tokenStorage = null,
        ?AuthorizationCheckerInterface $authChecker = null
    ) {
        parent::__construct();
        $this->authChecker = $authChecker;
    }

    private function logAction(string $action, array $context = []): void
    {
        $logData = [
            'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ISO8601),
            'action' => $action,
            'context' => $context,
        ];
        error_log(json_encode($logData, JSON_UNESCAPED_UNICODE));
    }

    private function incrementMetric(string $metric, array $tags = []): void
    {
        $metricData = [
            'metric' => $metric,
            'tags' => $tags,
            'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ISO8601),
        ];
        error_log(json_encode($metricData));
    }

    private function sanitizeString(?string $value): string
    {
        return htmlspecialchars(trim($value ?? ''), ENT_QUOTES, 'UTF-8');
    }

    private function isAuthenticated(): bool
    {
        if ($this->tokenStorage === null || $this->authChecker === null) {
            return false;
        }
        return $this->tokenStorage->getToken() !== null 
            && $this->authChecker->isGranted('IS_AUTHENTICATED_REMEMBERED');
    }

    private function isAdmin(): bool
    {
        if (!$this->isAuthenticated()) {
            return false;
        }
        return $this->authChecker->isGranted('ROLE_ADMIN');
    }

    private function isOfficer(): bool
    {
        if (!$this->isAuthenticated()) {
            return false;
        }
        return $this->authChecker->isGranted('ROLE_OFFICER') || $this->authChecker->isGranted('ROLE_ADMIN');
    }

    #[Route('', methods: ['GET'])]
    public function getAllIncidents(Request $request): JsonResponse
    {
        if (!$this->isAuthenticated()) {
            return $this->json(['error' => 'Authentication required'], 401);
        }

        $filters = [
            'status' => $request->query->get('status'),
            'severity' => $request->query->get('severity'),
            'ward_code' => $request->query->get('ward_code'),
            'incident_type' => $request->query->get('type'),
            'limit' => min($request->query->getInt('limit', 20), self::MAX_LIMIT),
        ];

        $incidents = $this->smartIncidentsService->getAllIncidents($filters);
        $result = array_map(fn(SmartIncident $i) => $i->toArray(), $incidents);

        return $this->json(['incidents' => $result, 'total' => count($result)]);
    }

    #[Route('/{incidentNumber}', methods: ['GET'])]
    public function getIncident(string $incidentNumber): JsonResponse
    {
        if (!$this->isAuthenticated()) {
            return $this->json(['error' => 'Authentication required'], 401);
        }

        $incident = $this->smartIncidentsService->getIncident($incidentNumber);
        if (!$incident) {
            return $this->json(['error' => 'Incident not found'], 404);
        }

        $this->smartIncidentsService->incrementViewCount($incident);

        return $this->json($incident->toArray());
    }

    #[Route('', methods: ['POST'])]
    public function createIncident(Request $request): JsonResponse
    {
        if (!$this->isAuthenticated()) {
            return $this->json(['error' => 'Authentication required'], 401);
        }

        $dataOr400 = $this->decodeJsonOr400($request);
        if ($dataOr400 instanceof JsonResponse) {
            return $dataOr400;
        }
        $data = $dataOr400;

        $error = $this->validateCreateIncidentInput($data);
        if ($error) {
            return $this->json(['error' => $error], 400);
        }

        try {
            $incident = $this->smartIncidentsService->createIncident($data);
            return $this->json($incident->toArray(), 201);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/{incidentNumber}/status', methods: ['PUT'])]
    public function updateStatus(Request $request, string $incidentNumber): JsonResponse
    {
        if (!$this->isOfficer()) {
            return $this->json(['error' => 'Officer or Admin access required'], 403);
        }

        $incident = $this->smartIncidentsService->getIncident($incidentNumber);
        if (!$incident) {
            return $this->json(['error' => 'Incident not found'], 404);
        }

        $dataOr400 = $this->decodeJsonOr400($request);
        if ($dataOr400 instanceof JsonResponse) {
            return $dataOr400;
        }
        $data = $dataOr400;
        if (empty($data['status'])) {
            return $this->json(['error' => 'Status is required'], 400);
        }

        if (!in_array($data['status'], self::ALLOWED_STATUSES)) {
            return $this->json(['error' => 'Invalid status'], 400);
        }

        try {
            $incident = $this->smartIncidentsService->updateIncidentStatus($incident, $data['status']);
            return $this->json($incident->toArray());
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/{incidentNumber}/assign', methods: ['PUT'])]
    public function assignIncident(Request $request, string $incidentNumber): JsonResponse
    {
        if (!$this->isOfficer()) {
            return $this->json(['error' => 'Officer or Admin access required'], 403);
        }

        $incident = $this->smartIncidentsService->getIncident($incidentNumber);
        if (!$incident) {
            return $this->json(['error' => 'Incident not found'], 404);
        }

        $dataOr400 = $this->decodeJsonOr400($request);
        if ($dataOr400 instanceof JsonResponse) {
            return $dataOr400;
        }
        $data = $dataOr400;
        if (empty($data['officer_id'])) {
            return $this->json(['error' => 'Officer ID is required'], 400);
        }

        try {
            $incident = $this->smartIncidentsService->assignIncident(
                $incident,
                $data['officer_id'],
                $data['team'] ?? null
            );
            return $this->json($incident->toArray());
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/{incidentNumber}/resolve', methods: ['PUT'])]
    public function resolveIncident(Request $request, string $incidentNumber): JsonResponse
    {
        if (!$this->isOfficer()) {
            return $this->json(['error' => 'Officer or Admin access required'], 403);
        }

        $incident = $this->smartIncidentsService->getIncident($incidentNumber);
        if (!$incident) {
            return $this->json(['error' => 'Incident not found'], 404);
        }

        $dataOr400 = $this->decodeJsonOr400($request);
        if ($dataOr400 instanceof JsonResponse) {
            return $dataOr400;
        }
        $data = $dataOr400;
        if (empty($data['summary'])) {
            return $this->json(['error' => 'Resolution summary is required'], 400);
        }

        try {
            $incident = $this->smartIncidentsService->resolveIncident($incident, $data['summary']);
            return $this->json($incident->toArray());
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/{incidentNumber}/upvote', methods: ['POST'])]
    public function upvote(string $incidentNumber): JsonResponse
    {
        if (!$this->isAuthenticated()) {
            return $this->json(['error' => 'Authentication required'], 401);
        }

        $incident = $this->smartIncidentsService->getIncident($incidentNumber);
        if (!$incident) {
            return $this->json(['error' => 'Incident not found'], 404);
        }

        $this->smartIncidentsService->upvoteIncident($incident);

        return $this->json(['message' => 'Upvote recorded', 'upvote_count' => $incident->getUpvoteCount()]);
    }

    #[Route('/{incidentNumber}/messages', methods: ['GET'])]
    public function getMessages(string $incidentNumber): JsonResponse
    {
        if (!$this->isAuthenticated()) {
            return $this->json(['error' => 'Authentication required'], 401);
        }

        $incident = $this->smartIncidentsService->getIncident($incidentNumber);
        if (!$incident) {
            return $this->json(['error' => 'Incident not found'], 404);
        }

        $messages = $this->smartIncidentsService->getMessages($incident);
        $result = array_map(fn($m) => $m->toArray(), $messages);

        return $this->json(['messages' => $result]);
    }

    #[Route('/{incidentNumber}/messages', methods: ['POST'])]
    public function addMessage(Request $request, string $incidentNumber): JsonResponse
    {
        if (!$this->isAuthenticated()) {
            return $this->json(['error' => 'Authentication required'], 401);
        }

        $incident = $this->smartIncidentsService->getIncident($incidentNumber);
        if (!$incident) {
            return $this->json(['error' => 'Incident not found'], 404);
        }

        $dataOr400 = $this->decodeJsonOr400($request);
        if ($dataOr400 instanceof JsonResponse) {
            return $dataOr400;
        }
        $data = $dataOr400;

        $error = $this->validateMessageInput($data);
        if ($error) {
            return $this->json(['error' => $error], 400);
        }

        try {
            $message = $this->smartIncidentsService->addMessage($incident, $data);
            return $this->json($message->toArray(), 201);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/{incidentNumber}/activity', methods: ['GET'])]
    public function getActivityLog(string $incidentNumber): JsonResponse
    {
        if (!$this->isAuthenticated()) {
            return $this->json(['error' => 'Authentication required'], 401);
        }

        $incident = $this->smartIncidentsService->getIncident($incidentNumber);
        if (!$incident) {
            return $this->json(['error' => 'Incident not found'], 404);
        }

        $activities = $this->smartIncidentsService->getActivityLog($incident);
        $result = array_map(fn($a) => $a->toArray(), $activities);

        return $this->json(['activities' => $result]);
    }

    #[Route('/{incidentNumber}/attachments', methods: ['POST'])]
    public function addAttachment(Request $request, string $incidentNumber): JsonResponse
    {
        if (!$this->isAuthenticated()) {
            return $this->json(['error' => 'Authentication required'], 401);
        }

        $incident = $this->smartIncidentsService->getIncident($incidentNumber);
        if (!$incident) {
            return $this->json(['error' => 'Incident not found'], 404);
        }

        $dataOr400 = $this->decodeJsonOr400($request);
        if ($dataOr400 instanceof JsonResponse) {
            return $dataOr400;
        }
        $data = $dataOr400;

        $error = $this->validateAttachmentInput($data);
        if ($error) {
            return $this->json(['error' => $error], 400);
        }

        try {
            $attachment = $this->smartIncidentsService->addAttachment($incident, $data);
            return $this->json($attachment->toArray(), 201);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    private function validateCreateIncidentInput(array $data): ?string
    {
        return $this->validateTitle($data)
            ?? $this->validateDescription($data)
            ?? $this->validateIncidentType($data)
            ?? $this->validateSeverity($data)
            ?? $this->validateLocation($data);
    }

    private function validateTitle(array $data): ?string
    {
        if (empty($data['title']) || !is_string($data['title'])) {
            return 'Title is required';
        }
        if (strlen($data['title']) > self::MAX_TITLE_LENGTH) {
            return 'Title too long (max ' . self::MAX_TITLE_LENGTH . ' chars)';
        }
        return null;
    }

    private function validateDescription(array $data): ?string
    {
        if (!empty($data['description']) && strlen($data['description']) > self::MAX_DESCRIPTION_LENGTH) {
            return 'Description too long (max ' . self::MAX_DESCRIPTION_LENGTH . ' chars)';
        }
        return null;
    }

    private function validateIncidentType(array $data): ?string
    {
        if (empty($data['incident_type']) || !is_string($data['incident_type'])) {
            return 'Incident type is required';
        }
        if (!in_array($data['incident_type'], self::ALLOWED_INCIDENT_TYPES)) {
            return 'Invalid incident type. Allowed: ' . implode(', ', self::ALLOWED_INCIDENT_TYPES);
        }
        return null;
    }

    private function validateSeverity(array $data): ?string
    {
        if (!empty($data['severity']) && !in_array($data['severity'], self::ALLOWED_SEVERITIES)) {
            return 'Invalid severity. Allowed: ' . implode(', ', self::ALLOWED_SEVERITIES);
        }
        return null;
    }

    private function validateLocation(array $data): ?string
    {
        if (!empty($data['latitude'])) {
            $lat = (float) $data['latitude'];
            if ($lat < -90 || $lat > 90) {
                return 'Latitude must be between -90 and 90';
            }
        }
        if (!empty($data['longitude'])) {
            $lng = (float) $data['longitude'];
            if ($lng < -180 || $lng > 180) {
                return 'Longitude must be between -180 and 180';
            }
        }
        return null;
    }

    private function validateMessageInput(array $data): ?string
    {
        if (empty($data['sender_type']) || !is_string($data['sender_type'])) {
            return 'Sender type is required';
        }
        if (empty($data['sender_id']) || !is_string($data['sender_id'])) {
            return 'Sender ID is required';
        }
        if (empty($data['sender_name']) || !is_string($data['sender_name'])) {
            return 'Sender name is required';
        }
        if (empty($data['content']) || !is_string($data['content'])) {
            return 'Message content is required';
        }
        if (strlen($data['content']) > 2000) {
            return 'Message too long (max 2000 chars)';
        }
        return null;
    }

    private function validateAttachmentInput(array $data): ?string
    {
        if ($error = $this->validateFileType($data)) return $error;
        if ($error = $this->validateFileName($data)) return $error;
        if ($error = $this->validateFileUrl($data)) return $error;
        if ($error = $this->validateFileSizePresence($data)) return $error;
        if ($error = $this->validateFileSizeRange($data)) return $error;
        return null;
    }

    private function validateFileType(array $data): ?string
    {
        if (empty($data['file_type']) || !is_string($data['file_type'])) {
            return 'File type is required';
        }
        return null;
    }

    private function validateFileName(array $data): ?string
    {
        if (empty($data['file_name']) || !is_string($data['file_name'])) {
            return 'File name is required';
        }
        return null;
    }

    private function validateFileUrl(array $data): ?string
    {
        if (empty($data['file_url']) || !is_string($data['file_url'])) {
            return 'File URL is required';
        }
        return null;
    }

    private function validateFileSizePresence(array $data): ?string
    {
        if (empty($data['file_size']) || !is_int($data['file_size'])) {
            return 'File size is required';
        }
        return null;
    }

    private function validateFileSizeRange(array $data): ?string
    {
        if ($data['file_size'] < 0 || $data['file_size'] > 10 * 1024 * 1024) {
            return 'File size exceeds maximum allowed (10MB)';
        }
        return null;
    }
}
