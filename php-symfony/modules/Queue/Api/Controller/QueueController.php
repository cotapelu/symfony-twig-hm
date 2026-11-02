<?php

declare(strict_types=1);

namespace App\Queue\Api\Controller;

use App\Shared\Controller\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use App\Queue\Application\Service\QueueService;
use App\Queue\Application\DTO\CreateQueueRequest;
use App\Queue\Application\DTO\UpdateQueueRequest;
use App\Queue\Application\DTO\JoinQueueRequest;
use App\Queue\Application\DTO\CallNextTicketRequest;
use App\Queue\Application\DTO\CompleteTicketRequest;
use App\Queue\Application\DTO\CancelTicketRequest;
use InvalidArgumentException;
use DateTimeImmutable;

#[Route('/api/v1/queues', name: 'queue_')]
#[IsGranted('ROLE_USER')]
class QueueController extends BaseController
{
    private const ALLOWED_SERVICE_TYPES = ['Cấp CCCD', 'Hộ khẩu', 'Đăng ký khai sinh', 'Đăng ký kết hôn', 'Xác nhận tình trạng', 'Khác'];
    private const ALLOWED_STATUSES = ['waiting', 'called', 'in_progress', 'completed', 'cancelled', 'skipped'];
    private const MAX_DESCRIPTION_LENGTH = 1000;
    private const MAX_COUNTER_COUNT = 50;

    public function __construct(
        private QueueService $queueService,
        private ?TokenStorageInterface $tokenStorage = null
    ) {}

    private function getAuthenticatedUserId(): ?string
    {
        if ($this->tokenStorage === null) return null;
        $token = $this->tokenStorage->getToken();
        if ($token === null) return null;
        $user = $token->getUser();
        if ($user === null || !is_object($user)) return null;
        return method_exists($user, 'getId') ? $user->getId() : null;
    }

    private function requireAuthOr401(): JsonResponse|string
    {
        $user = $this->getAuthenticatedUserId();
        if ($user === null) return new JsonResponse(['error' => 'Authentication required'], 401);
        return $user;
    }

    private function requireAuth(): string
    {
        $user = $this->requireAuthOr401();
        if ($user instanceof JsonResponse) throw new InvalidArgumentException('Authentication required');
        return (string)$user;
    }

    private function getJsonData(Request $request): array
    {
        $dataOr400 = $this->decodeJsonOr400($request);
        if ($dataOr400 instanceof JsonResponse) throw new InvalidArgumentException('Invalid JSON');
        return $dataOr400 ?? [];
    }

    private function logAction(string $action, array $context = []): void
    {
        error_log(json_encode(['timestamp'=>(new DateTimeImmutable())->format('Y-m-d H:i:s'), 'action'=>$action, 'userId'=>$this->getAuthenticatedUserId(), 'context'=>$context], JSON_UNESCAPED_UNICODE));
    }

    private function incrementMetric(string $metric, array $tags = []): void
    {
        error_log(json_encode(['timestamp'=>(new DateTimeImmutable())->format('Y-m-d H:i:s'), 'type'=>'metric', 'name'=>$metric, 'tags'=>$tags, 'value'=>1]));
    }

    private function sanitizeString(?string $value): string
    {
        return htmlspecialchars(trim($value ?? ''), ENT_QUOTES, 'UTF-8');
    }

    private function validateServiceTypes(array $types): array
    {
        $invalid = array_diff($types, self::ALLOWED_SERVICE_TYPES);
        return $invalid ? ['Invalid service types: '.implode(', ', $invalid)] : [];
    }

    private function validateCreateQueueInput(array $data): array
    {
        $errors = [];
        if (empty($data['name'])) $errors[] = 'Queue name is required';
        if (empty($data['wardCode'])) $errors[] = 'Ward code is required';
        if (empty($data['serviceTypes']) || !is_array($data['serviceTypes'])) {
            $errors[] = 'At least one service type is required';
        } else {
            $errors = array_merge($errors, $this->validateServiceTypes($data['serviceTypes']));
        }
        if (!empty($data['description']) && strlen($data['description']) > self::MAX_DESCRIPTION_LENGTH) {
            $errors[] = 'Description too long';
        }
        if (isset($data['counterCount']) && ($data['counterCount'] < 1 || $data['counterCount'] > self::MAX_COUNTER_COUNT)) {
            $errors[] = 'Invalid counter count (1-'.self::MAX_COUNTER_COUNT.')';
        }
        return $errors;
    }

    private function validateJoinQueueInput(array $data): array
    {
        $errors = [];
        if (empty($data['serviceType'])) $errors[] = 'Service type is required';
        if (empty($data['citizenName'])) $errors[] = 'Citizen name is required';
        if (isset($data['priority']) && ($data['priority'] < 0 || $data['priority'] > 10)) {
            $errors[] = 'Priority must be between 0 and 10';
        }
        if (!empty($data['citizenPhone']) && !preg_match('/^[0-9+\-\s]{8,15}$/', $data['citizenPhone'])) {
            $errors[] = 'Invalid phone number format';
        }
        return $errors;
    }

    private function handle(callable $action): JsonResponse
    {
        try { return $action(); }
        catch (InvalidArgumentException $e) { return $this->json(['error'=>$e->getMessage()], 400); }
        catch (\Exception $e) { return $this->handleException('controller', $e); }
    }

    private function handleException(string $ctx, \Exception $e): JsonResponse
    {
        error_log("QueueController::{$ctx} error: ".$e->getMessage());
        $this->logAction("{$ctx}_error", ['error'=>$e->getMessage()]);
        $this->incrementMetric("{$ctx}_errors");
        return $this->json(['error' => 'Internal server error'], 500);
    }

    // ==================== Endpoints ====================

    #[Route('', methods: ['POST'])]
    public function createQueue(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->createQueueAction($request));
    }

    private function createQueueAction(Request $request): JsonResponse
    {
        $userId = $this->requireAuth();
        $data = $this->getJsonData($request);
        $errors = $this->validateCreateQueueInput($data);
        if (!empty($errors)) return $this->json(['success'=>false, 'error'=>implode(', ', $errors)], 400);

        $dto = new CreateQueueRequest(trim($data['name']), trim($data['wardCode']), $data['serviceTypes'], $data['departmentName'] ?? null, $data['description'] ?? null, $data['counterCount'] ?? 1, $data['operatingHours'] ?? null);

        $queue = $this->queueService->createQueue($this->sanitizeString($dto->name), $this->sanitizeString($dto->wardCode), $dto->serviceTypes, $dto->departmentName ? $this->sanitizeString($dto->departmentName) : null, $dto->description ? $this->sanitizeString($dto->description) : null, $dto->counterCount, $dto->operatingHours);

        $this->logAction('queue.created', ['queueId'=>$queue->getId(), 'wardCode'=>$dto->wardCode]); $this->incrementMetric('queue.created', ['wardCode'=>$dto->wardCode]);

        return $this->json(['success'=>true, 'queue'=>$queue->toArray()], 201);
    }

    #[Route('', methods: ['GET'])]
    public function listQueues(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->listQueuesAction($request));
    }

    private function listQueuesAction(Request $request): JsonResponse
    {
        $this->requireAuth();
        $page = max(1, (int)($request->query->get('page', 1)));
        $limit = max(1, min(100, (int)($request->query->get('limit', 20))));
        $wardCode = $request->query->get('wardCode');
        $serviceType = $request->query->get('serviceType');
        $status = $request->query->get('status');

        $result = $this->queueService->listQueues($page, $limit, $wardCode, $serviceType, $status);
        $queues = $result['data'] ?? [];
        $total = $result['total'] ?? count($queues);

        return $this->json([
            'success' => true,
            'queues' => array_map(fn($q) => $q->toArray(), $queues),
            'pagination' => ['page'=>$page, 'limit'=>$limit, 'total'=>$total, 'totalPages'=>(int)ceil($total/$limit)]
        ]);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function getQueue(int $id, Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->getQueueAction($id));
    }

    private function getQueueAction(int $id): JsonResponse
    {
        if ($id <= 0) throw new InvalidArgumentException('Queue ID must be positive');
        $queue = $this->queueService->getQueue($id);
        if (!$queue) return $this->json(['error' => 'Queue not found'], 404);
        return $this->json(['success'=>true, 'queue'=>$queue->toArray()]);
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function updateQueue(int $id, Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->updateQueueAction($id, $request));
    }

    private function updateQueueAction(int $id, Request $request): JsonResponse
    {
        if ($id <= 0) throw new InvalidArgumentException('Queue ID must be positive');
        $userId = $this->requireAuth();
        $data = $this->getJsonData($request);

        $dto = new UpdateQueueRequest(
            $data['name'] ?? null,
            $data['description'] ?? null,
            $data['counterCount'] ?? null,
            $data['status'] ?? null,
            $data['operatingHours'] ?? null
        );

        $queue = $this->queueService->updateQueue($id, $dto, $userId);
        if (!$queue) return $this->json(['error' => 'Queue not found or update failed'], 404);

        $this->logAction('queue.updated', ['queueId'=>$id, 'userId'=>$userId]);
        $this->incrementMetric('queue.updated');

        return $this->json(['success'=>true, 'queue'=>$queue->toArray()]);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function deleteQueue(int $id, Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->deleteQueueAction($id, $request));
    }

    private function deleteQueueAction(int $id, Request $request): JsonResponse
    {
        if ($id <= 0) throw new InvalidArgumentException('Queue ID must be positive');
        $userId = $this->requireAuth();
        $deleted = $this->queueService->deleteQueue($id, $userId);
        if (!$deleted) return $this->json(['error' => 'Queue not found or cannot be deleted'], 404);
        $this->logAction('queue.deleted', ['queueId'=>$id, 'userId'=>$userId]);
        $this->incrementMetric('queue.deleted');
        return $this->json(['success'=>true]);
    }

    #[Route('/{id}/join', methods: ['POST'])]
    public function joinQueue(int $id, Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->joinQueueAction($id, $request));
    }

    private function joinQueueAction(int $id, Request $request): JsonResponse
    {
        if ($id <= 0) throw new InvalidArgumentException('Queue ID must be positive');
        $userId = $this->requireAuth();
        $data = $this->getJsonData($request);

        $errors = $this->validateJoinQueueInput($data);
        if (!empty($errors)) return $this->json(['success'=>false, 'error'=>implode(', ', $errors)], 400);

        $dto = new JoinQueueRequest(
            $data['serviceType'],
            $this->sanitizeString($data['citizenName']),
            $data['priority'] ?? 0,
            $data['citizenPhone'] ?? null,
            $data['notes'] ?? null
        );

        $ticket = $this->queueService->joinQueue($id, $userId, $dto);

        $this->logAction('queue.joined', ['queueId'=>$id, 'userId'=>$userId, 'ticketId'=>$ticket->getId()]);
        $this->incrementMetric('queue.joined', ['queueId'=>$id]);

        return $this->json(['success'=>true, 'ticket'=>$ticket->toArray()], 201);
    }

    #[Route('/{id}/leave', methods: ['POST'])]
    public function leaveQueue(int $id, Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->leaveQueueAction($id, $request));
    }

    private function leaveQueueAction(int $id, Request $request): JsonResponse
    {
        if ($id <= 0) throw new InvalidArgumentException('Queue ID must be positive');
        $userId = $this->requireAuth();
        $ticket = $this->queueService->leaveQueue($id, $userId);
        if (!$ticket) return $this->json(['error' => 'Not in queue or ticket not found'], 404);
        $this->logAction('queue.left', ['queueId'=>$id, 'userId'=>$userId, 'ticketId'=>$ticket->getId()]);
        $this->incrementMetric('queue.left');
        return $this->json(['success'=>true, 'ticket'=>$ticket->toArray()]);
    }

    #[Route('/{id}/status', methods: ['GET'])]
    public function getQueueStatus(int $id, Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->getQueueStatusAction($id));
    }

    private function getQueueStatusAction(int $id): JsonResponse
    {
        if ($id <= 0) throw new InvalidArgumentException('Queue ID must be positive');
        $status = $this->queueService->getQueueStatus($id);
        if (!$status) return $this->json(['error' => 'Queue not found'], 404);
        return $this->json(['success'=>true, 'status'=>$status]);
    }

    #[Route('/{id}/call-next', methods: ['POST'])]
    public function callNextTicket(int $id, Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->callNextTicketAction($id, $request));
    }

    private function callNextTicketAction(int $id, Request $request): JsonResponse
    {
        if ($id <= 0) throw new InvalidArgumentException('Queue ID must be positive');
        $userId = $this->requireAuth();
        $data = $this->getJsonData($request);

        $dto = new CallNextTicketRequest($data['counterNumber'] ?? null, $data['notes'] ?? null);
        $ticket = $this->queueService->callNextTicket($id, $userId, $dto);
        if (!$ticket) return $this->json(['error' => 'No waiting tickets or operation failed'], 400);

        $this->logAction('queue.ticket.called', ['queueId'=>$id, 'ticketId'=>$ticket->getId(), 'userId'=>$userId]);
        $this->incrementMetric('queue.ticket.called');

        return $this->json(['success'=>true, 'ticket'=>$ticket->toArray()]);
    }

    #[Route('/tickets/{ticketId}/complete', methods: ['POST'])]
    public function completeTicket(string $ticketId, Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->completeTicketAction($ticketId, $request));
    }

    private function completeTicketAction(string $ticketId, Request $request): JsonResponse
    {
        if (empty($ticketId)) throw new InvalidArgumentException('Ticket ID is required');
        $userId = $this->requireAuth();
        $data = $this->getJsonData($request);

        $dto = new CompleteTicketRequest($data['notes'] ?? null);
        $ticket = $this->queueService->completeTicket($ticketId, $userId, $dto);
        if (!$ticket) return $this->json(['error' => 'Ticket not found or cannot be completed'], 404);

        $this->logAction('queue.ticket.completed', ['ticketId'=>$ticketId, 'userId'=>$userId]);
        $this->incrementMetric('queue.ticket.completed');

        return $this->json(['success'=>true, 'ticket'=>$ticket->toArray()]);
    }

    #[Route('/tickets/{ticketId}/cancel', methods: ['POST'])]
    public function cancelTicket(string $ticketId, Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->cancelTicketAction($ticketId, $request));
    }

    private function cancelTicketAction(string $ticketId, Request $request): JsonResponse
    {
        if (empty($ticketId)) throw new InvalidArgumentException('Ticket ID is required');
        $userId = $this->requireAuth();
        $data = $this->getJsonData($request);

        $dto = new CancelTicketRequest($data['reason'] ?? null);
        $ticket = $this->queueService->cancelTicket($ticketId, $userId, $dto);
        if (!$ticket) return $this->json(['error' => 'Ticket not found or cannot be cancelled'], 404);

        $this->logAction('queue.ticket.cancelled', ['ticketId'=>$ticketId, 'userId'=>$userId]);
        $this->incrementMetric('queue.ticket.cancelled');

        return $this->json(['success'=>true, 'ticket'=>$ticket->toArray()]);
    }

    #[Route('/citizen/status', methods: ['GET'])]
    public function getCitizenQueueStatus(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->getCitizenQueueStatusAction($request));
    }

    private function getCitizenQueueStatusAction(Request $request): JsonResponse
    {
        $userId = $this->requireAuth();
        $status = $this->queueService->getCitizenQueueStatus($userId);
        return $this->json(['success'=>true, 'status'=>$status]);
    }
}
