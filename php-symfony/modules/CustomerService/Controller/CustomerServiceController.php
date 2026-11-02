<?php

declare(strict_types=1);

namespace App\CustomerService\Controller;

use App\CustomerService\Service\CustomerServiceService;
use App\Shared\Controller\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use InvalidArgumentException;
use DateTimeImmutable;

#[Route('/api/support')]
class CustomerServiceController extends BaseController
{
    private CustomerServiceService $service;

    public function __construct(CustomerServiceService $service)
    {
        $this->service = $service;
    }

    private function requireAuth(): string
    {
        $user = $this->getUser();
        if (!$user) throw new InvalidArgumentException('Authentication required');
        return (string)$user->getId();
    }

    private function getJsonData(Request $request): array
    {
        $dataOr400 = $this->decodeJsonOr400($request);
        if ($dataOr400 instanceof JsonResponse) throw new InvalidArgumentException('Invalid JSON');
        return $dataOr400 ?? [];
    }

    private function handle(callable $action): JsonResponse
    {
        try { return $action(); }
        catch (InvalidArgumentException $e) { return $this->json(['error'=>$e->getMessage()], 400); }
        catch (\Exception $e) { return $this->handleException('controller', $e); }
    }

    private function handleException(string $ctx, \Exception $e): JsonResponse
    {
        error_log("CustomerServiceController::{$ctx} error: ".$e->getMessage());
        $this->logAction("{$ctx}_error", ['error'=>$e->getMessage()]);
        $this->incrementMetric("{$ctx}_errors");
        return $this->json(['error' => 'Internal server error'], 500);
    }

    private function logAction(string $action, array $context = []): void
    {
        error_log(json_encode(['timestamp'=>(new DateTimeImmutable())->format('Y-m-d H:i:s'), 'action'=>$action, 'context'=>$context]));
    }

    private function incrementMetric(string $metric): void
    {
        error_log(json_encode(['timestamp'=>(new DateTimeImmutable())->format('Y-m-d H:i:s'), 'type'=>'metric', 'name'=>$metric, 'value'=>1]));
    }

    // ==================== Endpoints ====================

    #[Route('/tickets', name: 'customer_service_create_ticket', methods: ['POST'])]
    public function createTicket(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->createTicketAction($request));
    }

    private function createTicketAction(Request $request): JsonResponse
    {
        $userId = (int) $this->requireAuth();
        $data = $this->getJsonData($request);
        $this->validateTicketData($data);

        $ticket = $this->service->createTicket($userId, trim($data['subject']), $data['category'] ?? 'general');
        if (isset($data['description'])) $ticket->setDescription($data['description']);
        if (isset($data['priority'])) $ticket->setPriority($data['priority']);
        if (isset($data['order_id'])) $ticket->setOrderId((int)$data['order_id']);

        $this->logAction('ticket_created', ['ticket_id'=>$ticket->getId(), 'user_id'=>$userId]);
        $this->incrementMetric('tickets_created');

        return $this->json(['data'=>$ticket], JsonResponse::HTTP_CREATED);
    }

    #[Route('/tickets/{id}', name: 'customer_service_get_ticket', methods: ['GET'])]
    public function getTicket(int $id, Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->getTicketAction($id));
    }

    private function getTicketAction(int $id): JsonResponse
    {
        if ($id <= 0) throw new InvalidArgumentException('Ticket ID must be positive');
        $userId = (int) $this->requireAuth();
        $ticket = $this->service->getTicket($id);
        if (!$ticket) return $this->json(['error' => 'Ticket not found'], 404);
        if ($ticket->getUserId() !== $userId) return $this->json(['error' => 'Access denied'], 403);
        return $this->json(['data'=>$ticket]);
    }

    #[Route('/tickets/user/{userId}', name: 'customer_service_get_user_tickets', methods: ['GET'])]
    public function getUserTickets(int $userId, Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->getUserTicketsAction($userId));
    }

    private function getUserTicketsAction(int $userId): JsonResponse
    {
        if ($userId <= 0) throw new InvalidArgumentException('User ID must be positive');
        $currentUserId = (int) $this->requireAuth();
        if ($userId !== $currentUserId) return $this->json(['error' => 'Access denied'], 403);
        $tickets = $this->service->getUserTickets($userId);
        return $this->json(['data'=>array_values($tickets)]);
    }

    #[Route('/tickets', name: 'customer_service_get_all_tickets', methods: ['GET'])]
    public function getAllTickets(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->getAllTicketsAction($request));
    }

    private function getAllTicketsAction(Request $request): JsonResponse
    {
        $filters = $this->extractTicketFilters($request);
        $tickets = $this->service->getAllTickets($filters);
        return $this->json(['data'=>array_values($tickets)]);
    }

    #[Route('/tickets/{id}/status', name: 'customer_service_update_ticket_status', methods: ['PUT'])]
    public function updateStatus(int $id, Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->updateStatusAction($id, $request));
    }

    private function updateStatusAction(int $id, Request $request): JsonResponse
    {
        if ($id <= 0) throw new InvalidArgumentException('Ticket ID must be positive');
        $this->requireAuth();
        $data = $this->getJsonData($request);
        $this->validateStatusData($data);
        $ticket = $this->service->updateStatus($id, $data['status']);
        if (!$ticket) return $this->json(['error' => 'Ticket not found'], 404);
        $this->logAction('ticket_status_updated', ['ticket_id'=>$id, 'status'=>$data['status']]);
        return $this->json(['data'=>$ticket]);
    }

    #[Route('/tickets/{id}/assign', name: 'customer_service_assign_ticket', methods: ['PUT'])]
    public function assignTicket(int $id, Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->assignTicketAction($id, $request));
    }

    private function assignTicketAction(int $id, Request $request): JsonResponse
    {
        if ($id <= 0) throw new InvalidArgumentException('Ticket ID must be positive');
        $this->requireAuth();
        $data = $this->getJsonData($request);
        $this->validateAssigneeData($data);
        $ticket = $this->service->assignTicket($id, (int)$data['assigned_to']);
        if (!$ticket) return $this->json(['error' => 'Ticket not found'], 404);
        $this->logAction('ticket_assigned', ['ticket_id'=>$id, 'assigned_to'=>$data['assigned_to']]);
        return $this->json(['data'=>$ticket]);
    }

    #[Route('/tickets/{id}/priority', name: 'customer_service_set_ticket_priority', methods: ['PUT'])]
    public function setPriority(int $id, Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->setPriorityAction($id, $request));
    }

    private function setPriorityAction(int $id, Request $request): JsonResponse
    {
        if ($id <= 0) throw new InvalidArgumentException('Ticket ID must be positive');
        $this->requireAuth();
        $data = $this->getJsonData($request);
        $this->validatePriorityData($data);
        $ticket = $this->service->setPriority($id, $data['priority'] ?? 'medium');
        if (!$ticket) return $this->json(['error' => 'Ticket not found'], 404);
        return $this->json(['data'=>$ticket]);
    }

    #[Route('/tickets/open', name: 'customer_service_get_open_tickets', methods: ['GET'])]
    public function getOpenTickets(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->getOpenTicketsAction($request));
    }

    private function getOpenTicketsAction(Request $request): JsonResponse
    {
        $tickets = $this->service->getOpenTickets();
        return $this->json(['data'=>array_values($tickets)]);
    }

    #[Route('/tickets/agent/{agentId}', name: 'customer_service_get_assigned_tickets', methods: ['GET'])]
    public function getAssignedTickets(int $agentId, Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->getAssignedTicketsAction($agentId));
    }

    private function getAssignedTicketsAction(int $agentId): JsonResponse
    {
        if ($agentId <= 0) throw new InvalidArgumentException('Agent ID must be positive');
        $tickets = $this->service->getAssignedTickets($agentId);
        return $this->json(['data'=>array_values($tickets)]);
    }

    #[Route('/stats', name: 'customer_service_get_ticket_stats', methods: ['GET'])]
    public function getTicketStats(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->getTicketStatsAction($request));
    }

    private function getTicketStatsAction(Request $request): JsonResponse
    {
        $stats = $this->service->getTicketStats();
        return $this->json(['data'=>$stats]);
    }

    // ==================== Validation Helpers ====================

    private function validateTicketData(array $data): void
    {
        if (empty(trim($data['subject'] ?? ''))) throw new InvalidArgumentException('Subject is required');
        if (strlen($data['subject']) > 255) throw new InvalidArgumentException('Subject must not exceed 255 characters');
        if (isset($data['description']) && strlen($data['description']) > 5000) throw new InvalidArgumentException('Description must not exceed 5000 characters');
        if (isset($data['category']) && !in_array($data['category'], ['general','technical','billing','feature_request'])) {
            throw new InvalidArgumentException('Invalid category. Valid categories: general, technical, billing, feature_request');
        }
        if (isset($data['priority']) && !in_array($data['priority'], ['low','medium','high','urgent'])) {
            throw new InvalidArgumentException('Invalid priority. Valid priorities: low, medium, high, urgent');
        }
    }

    private function validateStatusData(array $data): void
    {
        if (empty(trim($data['status'] ?? ''))) throw new InvalidArgumentException('Status is required');
        if (!in_array($data['status'], ['open','in_progress','waiting','resolved','closed'])) {
            throw new InvalidArgumentException('Invalid status. Valid statuses: open, in_progress, waiting, resolved, closed');
        }
    }

    private function validateAssigneeData(array $data): void
    {
        if (!isset($data['assigned_to']) || !is_numeric($data['assigned_to']) || (int)$data['assigned_to'] < 1) {
            throw new InvalidArgumentException('Valid assignee ID is required');
        }
    }

    private function validatePriorityData(array $data): void
    {
        if (isset($data['priority']) && !in_array($data['priority'], ['low','medium','high','urgent'])) {
            throw new InvalidArgumentException('Invalid priority. Valid priorities: low, medium, high, urgent');
        }
    }

    private function extractTicketFilters(Request $request): array
    {
        $filters = [];
        if ($request->query->has('status')) {
            $status = $request->query->get('status');
            if (in_array($status, ['open','in_progress','waiting','resolved','closed'])) $filters['status'] = $status;
        }
        if ($request->query->has('priority')) {
            $priority = $request->query->get('priority');
            if (in_array($priority, ['low','medium','high','urgent'])) $filters['priority'] = $priority;
        }
        if ($request->query->has('category')) {
            $category = $request->query->get('category');
            if (in_array($category, ['general','technical','billing','feature_request'])) $filters['category'] = $category;
        }
        return $filters;
    }

    private function validatePositiveInt(int $value, string $fieldName, int $min = 1): void
    {
        if ($value < $min) throw new InvalidArgumentException("$fieldName must be at least $min");
    }
}
