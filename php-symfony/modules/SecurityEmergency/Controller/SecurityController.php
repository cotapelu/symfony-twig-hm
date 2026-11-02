<?php

declare(strict_types=1);

namespace App\SecurityEmergency\Controller;

use App\SecurityEmergency\Service\SecurityEmergencyService;
use App\SecurityEmergency\Entity\SecurityIncident;
use App\Shared\Controller\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use InvalidArgumentException;

#[Route('/api/security')]
class SecurityController extends BaseController
{
    private const ALLOWED_INCIDENT_TYPES = ['Trộm cắp', 'Tai nạn giao thông', 'Hỏa hoạn', 'Vi phạm trật tự', 'Khác'];
    private const ALLOWED_STATUSES = ['Đang điều tra', 'Đang xử lý', 'Đã xử lý'];
    private const MAX_STRING_LENGTH = 500;

    public function __construct(
        private readonly SecurityEmergencyService $securityService
    ) {}

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

    private function validateIncidentInput(array $data): array
    {
        $errors = [];
        
        if (empty($data['type']) || !in_array($data['type'], self::ALLOWED_INCIDENT_TYPES)) {
            $errors[] = 'Invalid incident type';
        }
        if (empty($data['location'])) {
            $errors[] = 'Location is required';
        }
        if (isset($data['description']) && strlen($data['description']) > self::MAX_STRING_LENGTH) {
            $errors[] = 'Description too long (max 500 chars)';
        }
        if (!empty($data['date']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['date'])) {
            $errors[] = 'Invalid date format (use YYYY-MM-DD)';
        }
        if (!empty($data['status']) && !in_array($data['status'], self::ALLOWED_STATUSES)) {
            $errors[] = 'Invalid status';
        }
        
        return $errors;
    }

    private function handle(callable $action, string $context = ''): JsonResponse
    {
        try {
            return $action();
        } catch (InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            if ($context) {
                return $this->handleException($context, $e);
            }
            error_log("SecurityController error: ".$e->getMessage());
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/incidents', methods: ['GET'])]
    public function listIncidents(Request $request): JsonResponse
    {
        try {
            $status = $request->query->get('status');
            $limit = (int)($request->query->get('limit', 50));
            $offset = (int)($request->query->get('offset', 0));
            
            if ($status) {
                if (!in_array($status, self::ALLOWED_STATUSES)) {
                    throw new InvalidArgumentException('Invalid status filter');
                }
                $incidents = $this->securityService->getIncidentsByStatus($status, $limit, $offset);
            } else {
                $incidents = $this->securityService->getAllIncidents($limit, $offset);
            }
            
            return $this->json([
                'items' => array_map(fn($i) => $i->toArray(), $incidents),
                'total' => count($incidents)
            ]);
        } catch (InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            return $this->handleException('list_incidents', $e);
        }
    }

    #[Route('/incidents/statistics', methods: ['GET'])]
    public function incidentStatistics(): JsonResponse
    {
        try {
            // Count by status (simplified)
            $all = $this->securityService->getAllIncidents(1000);
            $total = count($all);
            $resolved = count(array_filter($all, fn($i) => $i->getStatus() === 'Đã xử lý'));
            $pending = $total - $resolved;
            
            // Approximate patrol count (static for now)
            $patrolTeams = 2;

            return $this->json([
                'total' => $total,
                'resolved' => $resolved,
                'pending' => $pending,
                'patrolTeams' => $patrolTeams
            ]);
        } catch (\Exception $e) {
            return $this->handleException('statistics', $e);
        }
    }

    #[Route('/incidents/{id}', methods: ['GET'])]
    public function getIncident(string $id): JsonResponse
    {
        try {
            $incident = $this->securityService->getIncident($id);
            if (!$incident) {
                return $this->json(['error' => 'Incident not found'], 404);
            }
            return $this->json($incident->toArray());
        } catch (\Exception $e) {
            return $this->handleException('get_incident', $e);
        }
    }

    #[Route('/incidents', methods: ['POST'])]
    public function createIncident(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->createIncidentAction($request), 'create_incident');
    }

    private function createIncidentAction(Request $request): JsonResponse
    {
        if (!$user = $this->getUser()) {
            return $this->json(['error' => 'Authentication required'], 401);
        }
        $reporterId = $user->getId();
        $dataOr400 = $this->decodeJsonOr400($request);
        if ($dataOr400 instanceof JsonResponse) {
            return $dataOr400;
        }
        $data = $dataOr400;
        $errors = $this->validateIncidentInput($data);
        if (!empty($errors)) {
            return $this->json(['errors' => $errors], 400);
        }
        $incident = $this->securityService->createIncident(
            $data['type'],
            $reporterId,
            $data['location'] ?? null,
            $data['description'] ?? null,
            $data['status'] ?? 'Đang điều tra'
        );
        $this->logAction('security.incident.created', ['incidentId' => $incident->getId(), 'type' => $data['type']]);
        $this->incrementMetric('security.incident.created', ['type' => $data['type']]);
        return $this->json($incident->toArray(), 201);
    }

    #[Route('/incidents/{id}/status', methods: ['PUT'])]
    public function updateIncidentStatus(string $id, Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->updateIncidentStatusAction($id, $request), 'update_incident');
    }

    private function updateIncidentStatusAction(string $id, Request $request): JsonResponse
    {
        if (!$user = $this->getUser()) {
            return $this->json(['error' => 'Authentication required'], 401);
        }
        if (!$incident = $this->loadIncidentOr404($id)) {
            return $this->json(['error' => 'Incident not found'], 404);
        }
        $this->ensureAccessAllowed($incident, $user);
        $data = $this->getJsonDataOr400($request);
        if ($data instanceof JsonResponse) {
            return $data;
        }
        $this->validateStatusData($data);
        $updated = $this->securityService->updateIncidentStatus($id, $data['status']);
        if (!$updated) {
            return $this->json(['error' => 'Incident not found'], 404);
        }
        return $this->json($updated->toArray());
    }

    private function loadIncidentOr404(string $id): ?SecurityIncident
    {
        return $this->securityService->getIncident($id);
    }

    private function ensureAccessAllowed(SecurityIncident $incident, $user): void
    {
        if ($incident->getReporterId() !== $user->getId() && !$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Access denied');
        }
    }

    private function getJsonDataOr400(Request $request): array|JsonResponse
    {
        $dataOr400 = $this->decodeJsonOr400($request);
        return $dataOr400 instanceof JsonResponse ? $dataOr400 : $dataOr400;
    }

    private function validateStatusData(array $data): void
    {
        if (empty($data['status']) || !in_array($data['status'], self::ALLOWED_STATUSES)) {
            throw new InvalidArgumentException('Valid status is required');
        }
    }

    #[Route('/incidents/{id}', methods: ['DELETE'])]
    public function deleteIncident(string $id): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user) {
                return $this->json(['error' => 'Authentication required'], 401);
            }
            $incident = $this->securityService->getIncident($id);
            if (!$incident) {
                return $this->json(['error' => 'Incident not found'], 404);
            }
            if ($incident->getReporterId() !== $user->getId() && !$this->isGranted('ROLE_ADMIN')) {
                return $this->json(['error' => 'Access denied'], 403);
            }

            $success = $this->securityService->deleteIncident($id);
            if (!$success) {
                return $this->json(['error' => 'Incident not found'], 404);
            }
            return $this->json(null, 204);
        } catch (\Exception $e) {
            return $this->handleException('delete_incident', $e);
        }
    }

    #[Route('/patrols', methods: ['GET'])]
    public function listPatrols(): JsonResponse
    {
        // TODO: Patrols similarly need persistence; for now, return empty array
        return $this->json(['items' => [], 'total' => 0]);
    }

    #[Route('/emergency/contacts', methods: ['GET'])]
    public function emergencyContacts(): JsonResponse
    {
        $contacts = [
            ['id' => '1', 'service' => 'Công an', 'phone' => '113', 'available' => '24/7'],
            ['id' => '2', 'service' => 'Cứu hỏa', 'phone' => '114', 'available' => '24/7'],
            ['id' => '3', 'service' => 'Cấp cứu', 'phone' => '115', 'available' => '24/7'],
            ['id' => '4', 'service' => 'Bảo vệ phường', 'phone' => '0123 456 789', 'available' => '24/7'],
        ];
        return $this->json($contacts);
    }

    #[Route('/emergency/call', methods: ['POST'])]
    public function makeEmergencyCall(Request $request): JsonResponse
    {
        try {
            $dataOr400 = $this->decodeJsonOr400($request);
            if ($dataOr400 instanceof JsonResponse) {
                return $dataOr400;
            }
            $data = $dataOr400;
            
            
            $validServices = ['Công an', 'Cứu hỏa', 'Cấp cứu', 'Bảo vệ phường'];
            if (!empty($data['service']) && !in_array($data['service'], $validServices)) {
                throw new InvalidArgumentException('Invalid emergency service');
            }
            
            return $this->json([
                'success' => true,
                'message' => 'Emergency call initiated',
                'service' => $data['service'] ?? 'Unknown',
                'timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s')
            ]);
        } catch (InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            return $this->handleException('make_emergency_call', $e);
        }
    }

    private function handleException(string $context, \Exception $e): JsonResponse
    {
        error_log("SecurityController::{$context} error: " . $e->getMessage());
        return $this->json(['error' => 'Internal server error'], 500);
    }
}
