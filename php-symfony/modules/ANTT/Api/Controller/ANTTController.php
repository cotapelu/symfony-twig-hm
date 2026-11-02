<?php

declare(strict_types=1);

namespace App\ANTT\Api\Controller;

use App\ANTT\Application\Service\IncidentService;
use App\ANTT\Application\DTO\CreateIncidentRequest;
use App\ANTT\Application\DTO\UpdateIncidentRequest;
use App\ANTT\Application\DTO\IncidentStatistics;
use App\ANTT\Domain\Entity\Incident;
use App\Shared\Controller\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use InvalidArgumentException;
use DateTimeImmutable;

#[Route('/api/antt')]
class ANTTController extends BaseController
{
    public function __construct(
        private readonly IncidentService $incidentService,
        private readonly ValidatorInterface $validator
    ) {}

    private function requireAuthOr401(Request $request): JsonResponse|array
    {
        try {
            $user = $this->authenticateUser($request);
            return $user;
        } catch (InvalidArgumentException $e) {
            return new JsonResponse(['error' => 'Authentication required'], 401);
        }
    }

    private function checkPermission(array $user, string $perm): void
    {
        // Simplified check – in real app use proper roles/permissions
        if (!isset($user['roles']) || !in_array($perm, $user['roles'], true)) {
            throw new InvalidArgumentException('Insufficient permissions');
        }
    }

    private function authenticateUser(Request $request): array
    {
        $user = $this->getUser();
        if (!$user) {
            throw new InvalidArgumentException('Unauthorized');
        }
        return [
            'id' => $user->getUserIdentifier(),
            'roles' => $user->getRoles()
        ];
    }

    private function logAction(string $action, array $context = []): void
    {
        // Simple stub – in real app would use structured logging
        error_log(json_encode(['action' => $action, 'context' => $context]));
    }

    private function handleException(string $context, \Exception $e): JsonResponse
    {
        $this->logAction('error_' . $context, ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        return $this->json(['error' => 'An error occurred'], 500);
    }

    private function createCreateIncidentDto(array $data): CreateIncidentRequest
    {
        $dto = new CreateIncidentRequest();
        $dto->title = $data['title'] ?? '';
        $dto->description = $data['description'] ?? null;
        $dto->incidentType = $data['incident_type'] ?? '';
        $dto->severity = $data['severity'] ?? 'medium';
        $dto->wardCode = $data['ward_code'] ?? null;
        $dto->locationAddress = $data['location'] ?? null;
        $dto->latitude = isset($data['latitude']) ? (float)$data['latitude'] : null;
        $dto->longitude = isset($data['longitude']) ? (float)$data['longitude'] : null;
        $dto->reporterName = $data['reporter_name'] ?? null;
        $dto->reporterPhone = $data['reporter_phone'] ?? null;
        $dto->reporterAnonymous = $data['reporter_anonymous'] ?? false;
        return $dto;
    }

    private function createUpdateIncidentDto(array $data): UpdateIncidentRequest
    {
        $dto = new UpdateIncidentRequest();
        $dto->title = $data['title'] ?? null;
        $dto->description = $data['description'] ?? null;
        $dto->incidentType = $data['incident_type'] ?? null;
        $dto->severity = $data['severity'] ?? null;
        $dto->status = $data['status'] ?? null;
        return $dto;
    }

    private function validateDto(object $dto): void
    {
        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }
            throw new InvalidArgumentException('Validation failed: '.json_encode($errors));
        }
    }

    private function handle(callable $action): JsonResponse
    {
        try { return $action(); }
        catch (InvalidArgumentException $e) { return $this->json(['error'=>$e->getMessage()], 400); }
        catch (\Exception $e) { return $this->handleException('controller', $e); }
    }

    // ==================== Endpoints ====================

    #[Route('/incidents', methods: ['POST'])]
    public function createIncident(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->createIncidentAction($request));
    }

    private function createIncidentAction(Request $request): JsonResponse
    {
        $userOr401 = $this->requireAuthOr401($request);
        if ($userOr401 instanceof JsonResponse) return $userOr401;
        $user = $userOr401;
        $this->checkPermission($user, 'incident:create');

        $dataOr400 = $this->decodeJsonOr400($request);
        if ($dataOr400 instanceof JsonResponse) return $dataOr400;
        $data = $dataOr400;

        $dto = $this->createCreateIncidentDto($data);
        $this->validateDto($dto);

        $incident = $this->incidentService->createIncident($dto, $user['id']);

        $this->logAction('create_incident_success', [
            'incident_id' => $incident->getId(),
            'incident_number' => $incident->getIncidentNumber(),
            'user_id' => $user['id'] ?? 'unknown'
        ]);

        return $this->json([
            'incident' => $incident->toArray(),
            'message' => 'Incident created successfully'
        ], 201);
    }

    #[Route('/incidents', methods: ['GET'])]
    public function listIncidents(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->listIncidentsAction($request));
    }

    private function listIncidentsAction(Request $request): JsonResponse
    {
        $userOr401 = $this->requireAuthOr401($request);
        if ($userOr401 instanceof JsonResponse) return $userOr401;
        $user = $userOr401;
        $this->checkPermission($user, 'incident:read');

        $page = max(1, (int)($request->query->get('page', 1)));
        $limit = max(1, min(100, (int)($request->query->get('limit', 20))));
        $offset = ($page - 1) * $limit;

        $incidents = $this->incidentService->listIncidents($limit, $offset);
        $total = $this->incidentService->countIncidents();

        return $this->json([
            'incidents' => array_map(fn($i) => $i->toArray(), $incidents),
            'meta' => ['total'=>$total, 'limit'=>$limit, 'offset'=>$offset, 'count'=>count($incidents)]
        ]);
    }

    #[Route('/incidents/{id}', methods: ['GET'])]
    public function getIncident(int $id, Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->getIncidentAction($id, $request));
    }

    private function getIncidentAction(int $id, Request $request): JsonResponse
    {
        if ($id <= 0) throw new InvalidArgumentException('Incident ID must be positive');
        $userOr401 = $this->requireAuthOr401($request);
        if ($userOr401 instanceof JsonResponse) return $userOr401;
        $user = $userOr401;
        $this->checkPermission($user, 'incident:read');

        $incident = $this->incidentService->getIncident($id);
        if (!$incident) return $this->json(['error' => 'Incident not found'], 404);

        return $this->json(['incident' => $incident->toArray()]);
    }

    #[Route('/incidents/{id}', methods: ['PUT'])]
    public function updateIncident(int $id, Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->updateIncidentAction($id, $request));
    }

    private function updateIncidentAction(int $id, Request $request): JsonResponse
    {
        if ($id <= 0) throw new InvalidArgumentException('Incident ID must be positive');
        $userOr401 = $this->requireAuthOr401($request);
        if ($userOr401 instanceof JsonResponse) return $userOr401;
        $user = $userOr401;
        $this->checkPermission($user, 'incident:update');

        $dataOr400 = $this->decodeJsonOr400($request);
        if ($dataOr400 instanceof JsonResponse) return $dataOr400;
        $data = $dataOr400;

        $dto = $this->createUpdateIncidentDto($data);
        $this->validateDto($dto);

        $incident = $this->incidentService->updateIncident($id, $dto, $user['id']);
        if (!$incident) return $this->json(['error' => 'Incident not found'], 404);

        $this->logAction('update_incident_success', ['incident_id'=>$id, 'user_id'=>$user['id']]);
        return $this->json(['incident' => $incident->toArray(), 'message' => 'Incident updated']);
    }

    #[Route('/incidents/{id}/assign', methods: ['POST'])]
    public function assignIncident(int $id, Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->assignIncidentAction($id, $request));
    }

    private function assignIncidentAction(int $id, Request $request): JsonResponse
    {
        if ($id <= 0) throw new InvalidArgumentException('Incident ID must be positive');
        $userOr401 = $this->requireAuthOr401($request);
        if ($userOr401 instanceof JsonResponse) return $userOr401;
        $user = $userOr401;
        $this->checkPermission($user, 'incident:assign');

        $dataOr400 = $this->decodeJsonOr400($request);
        if ($dataOr400 instanceof JsonResponse) return $dataOr400;
        $data = $dataOr400;
        $assigneeId = $data['assigneeId'] ?? null;
        if (!$assigneeId) throw new InvalidArgumentException('assigneeId is required');

        $incident = $this->incidentService->assignIncident($id, $assigneeId);
        if (!$incident) return $this->json(['error' => 'Incident not found or not authorized'], 404);

        $this->logAction('assign_incident_success', ['incident_id'=>$id, 'assignee_id'=>$assigneeId, 'user_id'=>$user['id']]);
        return $this->json(['incident' => $incident->toArray(), 'message' => 'Incident assigned']);
    }

    #[Route('/incidents/{id}/resolve', methods: ['POST'])]
    public function resolveIncident(int $id, Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->resolveIncidentAction($id, $request));
    }

    private function resolveIncidentAction(int $id, Request $request): JsonResponse
    {
        if ($id <= 0) throw new InvalidArgumentException('Incident ID must be positive');
        $userOr401 = $this->requireAuthOr401($request);
        if ($userOr401 instanceof JsonResponse) return $userOr401;
        $user = $userOr401;
        $this->checkPermission($user, 'incident:resolve');

        $dataOr400 = $this->decodeJsonOr400($request);
        if ($dataOr400 instanceof JsonResponse) return $dataOr400;
        $data = $dataOr400;
        $resolution = $data['resolution'] ?? null;
        if (!$resolution) throw new InvalidArgumentException('resolution is required');

        $incident = $this->incidentService->resolveIncident($id, $resolution);
        if (!$incident) return $this->json(['error' => 'Incident not found or cannot be resolved'], 404);

        $this->logAction('resolve_incident_success', ['incident_id'=>$id, 'user_id'=>$user['id']]);
        return $this->json(['incident' => $incident->toArray(), 'message' => 'Incident resolved']);
    }

    #[Route('/incidents/{id}/close', methods: ['POST'])]
    public function closeIncident(int $id, Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->closeIncidentAction($id, $request));
    }

    private function closeIncidentAction(int $id, Request $request): JsonResponse
    {
        if ($id <= 0) throw new InvalidArgumentException('Incident ID must be positive');
        $userOr401 = $this->requireAuthOr401($request);
        if ($userOr401 instanceof JsonResponse) return $userOr401;
        $user = $userOr401;
        $this->checkPermission($user, 'incident:close');

        $incident = $this->incidentService->closeIncident($id);
        if (!$incident) return $this->json(['error' => 'Incident not found or cannot be closed'], 404);

        $this->logAction('close_incident_success', ['incident_id'=>$id, 'user_id'=>$user['id']]);
        return $this->json(['incident' => $incident->toArray(), 'message' => 'Incident closed']);
    }

    #[Route('/incidents/{id}/escalate', methods: ['POST'])]
    public function escalateIncident(int $id, Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->escalateIncidentAction($id, $request));
    }

    private function escalateIncidentAction(int $id, Request $request): JsonResponse
    {
        if ($id <= 0) throw new InvalidArgumentException('Incident ID must be positive');
        $userOr401 = $this->requireAuthOr401($request);
        if ($userOr401 instanceof JsonResponse) return $userOr401;
        $user = $userOr401;
        $this->checkPermission($user, 'incident:escalate');

        $dataOr400 = $this->decodeJsonOr400($request);
        if ($dataOr400 instanceof JsonResponse) return $dataOr400;
        $data = $dataOr400;
        $level = $data['level'] ?? null;
        if (!$level) throw new InvalidArgumentException('escalation level is required');

        $incident = $this->incidentService->escalateIncident($id);
        if (!$incident) return $this->json(['error' => 'Incident not found or cannot be escalated'], 404);

        $this->logAction('escalate_incident_success', ['incident_id'=>$id, 'level'=>$level, 'user_id'=>$user['id']]);
        return $this->json(['incident' => $incident->toArray(), 'message' => 'Incident escalated']);
    }

    #[Route('/statistics', methods: ['GET'])]
    public function getStatistics(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->getStatisticsAction($request));
    }

    private function getStatisticsAction(Request $request): JsonResponse
    {
        $userOr401 = $this->requireAuthOr401($request);
        if ($userOr401 instanceof JsonResponse) return $userOr401;
        $user = $userOr401;
        $this->checkPermission($user, 'statistics:read');

        $startDate = $request->query->get('startDate');
        $endDate = $request->query->get('endDate');
        $stats = $this->incidentService->getStatistics(
            $startDate ? new DateTimeImmutable($startDate) : null,
            $endDate ? new DateTimeImmutable($endDate) : null
        );
        return $this->json(['statistics' => $stats->toArray()]);
    }

    #[Route('/incidents/open', methods: ['GET'])]
    public function getOpenIncidents(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->getOpenIncidentsAction($request));
    }

    private function getOpenIncidentsAction(Request $request): JsonResponse
    {
        $userOr401 = $this->requireAuthOr401($request);
        if ($userOr401 instanceof JsonResponse) return $userOr401;
        $user = $userOr401;
        $this->checkPermission($user, 'incident:read');

        $incidents = $this->incidentService->getOpenIncidents();
        return $this->json(['incidents' => array_map(fn($i) => $i->toArray(), $incidents)]);
    }
}
