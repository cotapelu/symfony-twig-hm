<?php

declare(strict_types=1);

namespace App\LiveSupport\Controller;

use App\LiveSupport\Entity\Conversation;
use App\LiveSupport\Entity\Message;
use App\LiveSupport\Entity\Agent;
use App\LiveSupport\Service\LiveSupportService;
use App\Shared\Controller\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use InvalidArgumentException;
use DateTimeImmutable;

#[Route('/api/live-support')]
class LiveSupportController extends BaseController
{
    private LiveSupportService $service;

    public function __construct(LiveSupportService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    private function requireAuthOr401(): JsonResponse|string
    {
        $user = $this->getUser();
        if (!$user) return new JsonResponse(['status'=>401, 'error'=>'Authentication required'], 401);
        return (string)$user->getId();
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

    private function logAction(string $action, array $context = []): void
    {
        error_log(json_encode(['timestamp'=>(new DateTimeImmutable())->format('Y-m-d\TH:i:sP'), 'action'=>$action, 'userId'=>$this->getUser()?->getUserIdentifier() ?? 'anonymous', 'module'=>'LiveSupport', 'context'=>$context], JSON_UNESCAPED_UNICODE));
    }

    private function incrementMetric(string $metric, array $tags = []): void
    {
        error_log(json_encode(['metric'=>$metric, 'tags'=>$tags, 'timestamp'=>(new DateTimeImmutable())->format('Y-m-d\TH:i:sP')]));
    }

    private function sanitizeString(?string $value): string
    {
        return $value !== null ? htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8') : '';
    }

    private function handle(callable $action): JsonResponse
    {
        try { return $action(); }
        catch (InvalidArgumentException $e) { return $this->json(['status'=>400, 'error'=>$e->getMessage()], 400); }
        catch (NotFoundHttpException $e) { return $this->json(['status'=>404, 'error'=>$e->getMessage()], 404); }
        catch (AccessDeniedHttpException $e) { return $this->json(['status'=>403, 'error'=>$e->getMessage()], 403); }
        catch (\Throwable $e) { return $this->handleException('controller', $e); }
    }

    private function handleException(string $ctx, \Throwable $e): JsonResponse
    {
        error_log("LiveSupportController::{$ctx} error: ".$e->getMessage());
        $this->logAction("{$ctx}_error", ['error'=>$e->getMessage()]);
        $this->incrementMetric("{$ctx}_errors");
        return $this->json(['status'=>500, 'error'=>'Internal server error'], 500);
    }

    // ==================== Endpoints ====================

    #[Route('/conversations', name: 'live_support_create_conversation', methods: ['POST'])]
    public function createConversation(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->createConversationAction($request));
    }

    private function createConversationAction(Request $request): JsonResponse
    {
        $userId = $this->requireAuth();
        $data = $this->getJsonData($request);
        if (empty($data)) throw new InvalidArgumentException('Invalid JSON payload');

        $topic = $data['topic'] ?? '';
        $participants = $data['participants'] ?? [];

        if (empty(trim($topic))) throw new InvalidArgumentException('Topic is required');

        $conversation = $this->service->createConversation($userId, $topic, $participants);

        $this->logAction('conversation_created', ['conversation_id'=>$conversation->getId(), 'creator'=>$userId]);
        $this->incrementMetric('conversations_created_total');

        return $this->json(['status'=>201, 'data'=>$this->serializeConversation($conversation)], 201);
    }

    #[Route('/conversations/{id}', name: 'live_support_get_conversation', methods: ['GET'])]
    public function getConversation(Request $request, int $id): JsonResponse
    {
        return $this->handle(fn() => $this->getConversationAction($id, $request));
    }

    private function getConversationAction(int $id, Request $request): JsonResponse
    {
        if ($id <= 0) throw new InvalidArgumentException('Conversation ID must be positive');
        $conversation = $this->service->getConversation($id);
        if (!$conversation) throw new NotFoundHttpException('Conversation not found');

        $userId = $this->requireAuth();
        if (!$conversation->hasParticipant($userId) && $conversation->getAssignedAgentId() != $userId && $conversation->getCreatorId() !== $userId) {
            throw new AccessDeniedHttpException('Access denied to this conversation');
        }

        $this->logAction('conversation_viewed', ['conversation_id'=>$id, 'user_id'=>$userId]);
        $this->incrementMetric('conversation_views_total');

        return $this->json(['status'=>200, 'data'=>$this->serializeConversation($conversation)]);
    }

    #[Route('/conversations/active', name: 'live_support_get_active_conversations', methods: ['GET'])]
    public function getActiveConversations(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->getActiveConversationsAction($request));
    }

    private function getActiveConversationsAction(Request $request): JsonResponse
    {
        $userId = $this->requireAuth();
        $agentId = $request->query->get('agent_id');
        if ($agentId !== null) $agentId = (int)$agentId;

        $conversations = $this->service->getActiveConversations($agentId);
        $this->logAction('active_conversations_queried', ['user_id'=>$userId, 'agent_id'=>$agentId]);
        return $this->json(['status'=>200, 'data'=>array_map([$this, 'serializeConversation'], $conversations)]);
    }

    #[Route('/conversations/{id}/join', name: 'live_support_join_conversation', methods: ['POST'])]
    public function joinConversation(Request $request, int $id): JsonResponse
    {
        return $this->handle(fn() => $this->joinConversationAction($id, $request));
    }

    private function joinConversationAction(int $id, Request $request): JsonResponse
    {
        if ($id <= 0) throw new InvalidArgumentException('Conversation ID must be positive');
        $userId = $this->requireAuth();
        $conversation = $this->service->joinConversation($id, $userId);
        $this->logAction('conversation_joined', ['conversation_id'=>$id, 'user_id'=>$userId]);
        $this->incrementMetric('conversation_joins_total');
        return $this->json(['status'=>200, 'data'=>$this->serializeConversation($conversation)]);
    }

    #[Route('/conversations/{id}/leave', name: 'live_support_leave_conversation', methods: ['DELETE'])]
    public function leaveConversation(Request $request, int $id): JsonResponse
    {
        return $this->handle(fn() => $this->leaveConversationAction($id, $request));
    }

    private function leaveConversationAction(int $id, Request $request): JsonResponse
    {
        if ($id <= 0) throw new InvalidArgumentException('Conversation ID must be positive');
        $userId = $this->requireAuth();
        $conversation = $this->service->leaveConversation($id, $userId);
        $this->logAction('conversation_left', ['conversation_id'=>$id, 'user_id'=>$userId]);
        $this->incrementMetric('conversations_left_total');
        return $this->json(['status'=>200, 'data'=>$this->serializeConversation($conversation)]);
    }

    #[Route('/conversations/{id}/end', name: 'live_support_end_conversation', methods: ['POST'])]
    public function endConversation(Request $request, int $id): JsonResponse
    {
        return $this->handle(fn() => $this->endConversationAction($id, $request));
    }

    private function endConversationAction(int $id, Request $request): JsonResponse
    {
        if ($id <= 0) throw new InvalidArgumentException('Conversation ID must be positive');
        $userId = $this->requireAuth();
        $conversation = $this->service->endConversation($id, $userId);
        $this->logAction('conversation_ended', ['conversation_id'=>$id, 'user_id'=>$userId]);
        $this->incrementMetric('conversations_ended_total');
        return $this->json(['status'=>200, 'data'=>$this->serializeConversation($conversation)]);
    }

    #[Route('/conversations/{id}/agent', name: 'live_support_assign_agent', methods: ['PUT'])]
    public function assignAgent(Request $request, int $id): JsonResponse
    {
        return $this->handle(fn() => $this->assignAgentAction($id, $request));
    }

    private function assignAgentAction(int $id, Request $request): JsonResponse
    {
        if ($id <= 0) throw new InvalidArgumentException('Conversation ID must be positive');
        $userId = $this->requireAuth();
        $data = $this->getJsonData($request);
        if (empty($data)) throw new InvalidArgumentException('Invalid JSON payload');
        $agentId = $data['agent_id'] ?? null;
        if ($agentId === null) throw new InvalidArgumentException('Agent ID is required');

        $conversation = $this->service->assignAgent($id, $userId, (int)$agentId);
        $this->logAction('agent_assigned', ['conversation_id'=>$id, 'agent_id'=>$agentId, 'by'=>$userId]);
        $this->incrementMetric('agents_assigned_total');
        return $this->json(['status'=>200, 'data'=>$this->serializeConversation($conversation)]);
    }

    #[Route('/messages', name: 'live_support_send_message', methods: ['POST'])]
    public function sendMessage(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->sendMessageAction($request));
    }

    private function sendMessageAction(Request $request): JsonResponse
    {
        $userId = $this->requireAuth();
        $data = $this->getJsonData($request);
        if (empty($data)) throw new InvalidArgumentException('Invalid JSON payload');

        $conversationId = $data['conversation_id'] ?? null;
        $content = $data['content'] ?? null;

        if ($conversationId === null) throw new InvalidArgumentException('Conversation ID is required');
        if (empty(trim($content ?? ''))) throw new InvalidArgumentException('Message content is required');

        $message = $this->service->sendMessage((int)$conversationId, $userId, $content);
        $this->logAction('message_sent', ['conversation_id'=>$conversationId, 'sender'=>$userId, 'message_id'=>$message->getId()]);
        $this->incrementMetric('messages_sent_total');
        return $this->json(['status'=>201, 'data'=>$this->serializeMessage($message)], 201);
    }

    #[Route('/conversations/{conversationId}/messages', name: 'live_support_get_messages', methods: ['GET'])]
    public function getMessages(Request $request, int $conversationId): JsonResponse
    {
        return $this->handle(fn() => $this->getMessagesAction($request, $conversationId));
    }

    private function getMessagesAction(Request $request, int $conversationId): JsonResponse
    {
        if ($conversationId <= 0) throw new InvalidArgumentException('Conversation ID must be positive');
        $userId = $this->requireAuth();
        $conversation = $this->service->getConversation($conversationId);
        if (!$conversation) throw new NotFoundHttpException('Conversation not found');
        if (!$conversation->hasParticipant($userId) && $conversation->getAssignedAgentId() != $userId && $conversation->getCreatorId() !== $userId) {
            throw new AccessDeniedHttpException('Access denied to messages');
        }

        $limit = max(1, min(100, (int)$request->query->get('limit', 100)));
        $offset = max(0, (int)$request->query->get('offset', 0));
        $sinceParam = $request->query->get('since');
        $since = $sinceParam ? new \DateTime($sinceParam) : null;

        $messages = $this->service->getMessages($conversationId, $limit, $offset, $since);
        $this->logAction('messages_retrieved', ['conversation_id'=>$conversationId, 'user_id'=>$userId, 'count'=>count($messages)]);
        return $this->json(['status'=>200, 'data'=>array_map([$this, 'serializeMessage'], $messages)]);
    }

    #[Route('/agents/register', name: 'live_support_register_agent', methods: ['POST'])]
    public function registerAgent(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->registerAgentAction($request));
    }

    private function registerAgentAction(Request $request): JsonResponse
    {
        $userId = $this->requireAuth();
        $data = $this->getJsonData($request);
        if (empty($data)) throw new InvalidArgumentException('Invalid JSON payload');

        $agentUserId = $data['agent_user_id'] ?? $userId;
        $name = $data['name'] ?? '';
        $skills = $data['skills'] ?? [];

        if (empty(trim($name))) throw new InvalidArgumentException('Agent name is required');

        $agent = $this->service->registerAgent($agentUserId, $name, $skills);
        $this->logAction('agent_registered', ['agent_user_id'=>$agentUserId, 'name'=>$name]);
        $this->incrementMetric('agents_registered_total');
        return $this->json(['status'=>201, 'data'=>$this->serializeAgent($agent)], 201);
    }

    #[Route('/agents/{agentUserId}', name: 'live_support_get_agent', methods: ['GET'])]
    public function getAgent(Request $request, string $agentUserId): JsonResponse
    {
        return $this->handle(fn() => $this->getAgentAction($agentUserId));
    }

    private function getAgentAction(string $agentUserId): JsonResponse
    {
        $userId = $this->requireAuth();
        if (empty($agentUserId)) throw new InvalidArgumentException('Agent user ID is required');
        $agent = $this->service->getAgentByUserId($agentUserId);
        if (!$agent) throw new NotFoundHttpException('Agent not found');
        $this->logAction('agent_viewed', ['agent_user_id'=>$agentUserId]);
        return $this->json(['status'=>200, 'data'=>$this->serializeAgent($agent)]);
    }

    #[Route('/agents/{agentUserId}/status', name: 'live_support_get_agent_status', methods: ['GET'])]
    public function getAgentStatus(Request $request, string $agentUserId): JsonResponse
    {
        return $this->handle(fn() => $this->getAgentStatusAction($agentUserId));
    }

    private function getAgentStatusAction(string $agentUserId): JsonResponse
    {
        $userId = $this->requireAuth();
        if (empty($agentUserId)) throw new InvalidArgumentException('Agent user ID is required');
        $status = $this->service->getAgentStatus($agentUserId);
        if ($status === null) throw new NotFoundHttpException('Agent not found');
        return $this->json(['status'=>200, 'data'=>$status]);
    }

    #[Route('/agents/{agentUserId}/status', name: 'live_support_set_agent_status', methods: ['PUT'])]
    public function setAgentStatus(Request $request, string $agentUserId): JsonResponse
    {
        return $this->handle(fn() => $this->setAgentStatusAction($request, $agentUserId));
    }

    private function setAgentStatusAction(Request $request, string $agentUserId): JsonResponse
    {
        $userId = $this->requireAuth();
        if (empty($agentUserId)) throw new InvalidArgumentException('Agent user ID is required');
        $data = $this->getJsonData($request);
        $status = $data['status'] ?? null;
        $load = $data['current_load'] ?? null;

        if (empty($status)) throw new InvalidArgumentException('Status is required');
        if ($load !== null && $load < 0) throw new InvalidArgumentException('Current load cannot be negative');

        $agent = $this->service->setAgentStatus($agentUserId, $status, $load);
        $this->logAction('agent_status_set', ['agent_user_id'=>$agentUserId, 'status'=>$status, 'load'=>$load]);
        $this->incrementMetric('agent_status_updates_total');
        return $this->json(['status'=>200, 'data'=>$this->serializeAgent($agent)]);
    }

    #[Route('/agents', name: 'live_support_list_agents', methods: ['GET'])]
    public function listAgents(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->listAgentsAction($request));
    }

    private function listAgentsAction(Request $request): JsonResponse
    {
        $userId = $this->requireAuth();
        $agents = $this->service->listAgents();
        $this->logAction('agents_listed');
        return $this->json(['status'=>200, 'data'=>array_map([$this, 'serializeAgent'], $agents)]);
    }

    // ==================== Serialization Helpers ====================

    private function serializeConversation(Conversation $c): array
    {
        return [
            'id' => $c->getId(),
            'topic' => $c->getTopic(),
            'status' => $c->getStatus(),
            'participants' => $c->getParticipants(),
            'assigned_agent_id' => $c->getAssignedAgentId(),
            'creator_id' => $c->getCreatorId(),
            'created_at' => $c->getCreatedAt()->format('Y-m-d\TH:i:sP'),
            'updated_at' => $c->getUpdatedAt()->format('Y-m-d\TH:i:sP')
        ];
    }

    private function serializeMessage(Message $m): array
    {
        return [
            'id' => $m->getId(),
            'conversation_id' => $m->getConversation()?->getId(),
            'sender_id' => $m->getSenderId(),
            'content' => $m->getContent(),
            'is_read' => $m->isRead(),
            'created_at' => $m->getCreatedAt()->format('Y-m-d\TH:i:sP')
        ];
    }

    private function serializeAgent(Agent $a): array
    {
        return [
            'id' => $a->getId(),
            'agent_user_id' => $a->getAgentUserId(),
            'name' => $a->getName(),
            'skills' => $a->getSkills(),
            'status' => $a->getStatus(),
            'current_load' => $a->getCurrentLoad(),
            'last_heartbeat' => $a->getLastHeartbeat()?->format('Y-m-d\TH:i:sP'),
            'created_at' => $a->getCreatedAt()->format('Y-m-d\TH:i:sP'),
            'updated_at' => $a->getUpdatedAt()->format('Y-m-d\TH:i:sP')
        ];
    }
}
