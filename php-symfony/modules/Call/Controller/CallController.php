<?php

declare(strict_types=1);

namespace App\Call\Controller;

use App\Call\Service\CallService;
use App\User\Service\AuthService;
use App\User\Entity\User;
use App\Conversation\Entity\Group;
use App\Shared\Controller\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use InvalidArgumentException;
use DateTimeImmutable;

#[Route('/calls')]
class CallController extends BaseController
{
    public function __construct(private CallService $service, private AuthService $authService)
    {
        parent::__construct();
    }

    private function getJsonData(Request $request): array
    {
        $dataOr400 = $this->decodeJsonOr400($request);
        if ($dataOr400 instanceof JsonResponse) throw new InvalidArgumentException('Invalid JSON');
        return $dataOr400 ?? [];
    }

    private function handle(callable $action): JsonResponse
    {
        try {
            return $action();
        } catch (InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (NotFoundHttpException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (AccessDeniedHttpException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_FORBIDDEN);
        } catch (HttpException $e) {
            return $this->json(['error' => $e->getMessage()], $e->getStatusCode());
        } catch (\Exception $e) {
            return $this->handleException('call', $e);
        }
    }

    // ========== AUTH HELPERS ==========

    private function requireAuthOr401(): JsonResponse|string
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }
        return (string) $user->getId();
    }

    private function requireAuthUser(): User
    {
        try {
            $user = $this->authService->getAuthenticatedUser($this->getUser());
            if (!$user) {
                throw new HttpException(401, 'Authentication required');
            }
            return $user;
        } catch (\Exception $e) {
            throw new HttpException(401, $e->getMessage(), $e);
        }
    }

    // ==================== Endpoints ====================

    #[Route('', name: 'call_initiate', methods: ['POST'])]
    public function initiateCall(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->initiateCallAction($request));
    }

    private function initiateCallAction(Request $request): JsonResponse
    {
        $user = $this->requireAuthUser();
        $data = $this->getJsonData($request);
        if (!isset($data['recipientId']) && !isset($data['groupId'])) {
            throw new InvalidArgumentException('Recipient or group ID is required');
        }
        $doctrine = $this->service->getDoctrine();
        if (isset($data['recipientId'])) {
            $recipient = $doctrine->getRepository(User::class)->find($data['recipientId']);
            if (!$recipient) throw new NotFoundHttpException('User not found');
            $call = $this->service->initiateCall($user, $recipient, null, $data['type'] ?? 'video');
        } else {
            $group = $doctrine->getRepository(Group::class)->find($data['groupId']);
            if (!$group) throw new NotFoundHttpException('Group not found');
            $call = $this->service->initiateCall($user, null, $group, $data['type'] ?? 'video');
        }
        $this->logAction('call_initiated', ['call_id' => $call->getId(), 'user_id' => $user->getId()]);
        $this->incrementMetric('calls_initiated_total');
        return $this->json([
            'call_id' => $call->getCallId(),
            'type' => $call->getType(),
            'status' => $call->getStatus(),
        ]);
    }

    #[Route('/{id}', name: 'call_get', methods: ['GET'])]
    public function getCall(int $id): JsonResponse
    {
        return $this->handle(fn() => $this->getCallAction($id));
    }

    private function getCallAction(int $id): JsonResponse
    {
        $userIdOr401 = $this->requireAuthOr401();
        if ($userIdOr401 instanceof JsonResponse) return $userIdOr401;
        $userId = (int) $userIdOr401;
        $call = $this->service->getCall($id);
        if (!$call) throw new NotFoundHttpException('Call not found');
        if ($call->getCallerId() !== $userId && $call->getReceiverId() !== $userId) {
            throw new AccessDeniedHttpException('Access denied');
        }
        return $this->json(['data' => $call]);
    }

    #[Route('/user/{userId}', name: 'call_get_user', methods: ['GET'])]
    public function getUserCalls(int $userId): JsonResponse
    {
        return $this->handle(fn() => $this->getUserCallsAction($userId));
    }

    private function getUserCallsAction(int $userId): JsonResponse
    {
        $this->validatePositiveInt($userId, 'user ID');
        $userIdOr401 = $this->requireAuthOr401();
        if ($userIdOr401 instanceof JsonResponse) return $userIdOr401;
        $currentUserId = (int) $userIdOr401;
        if ($userId !== $currentUserId) throw new AccessDeniedHttpException('Access denied');
        $calls = $this->service->getUserCalls($userId);
        return $this->json(['data' => array_values($calls)]);
    }

    #[Route('/user/{userId}/recent', name: 'call_get_recent', methods: ['GET'])]
    public function getRecentCalls(int $userId, Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->getRecentCallsAction($userId, $request));
    }

    private function getRecentCallsAction(int $userId, Request $request): JsonResponse
    {
        $this->validatePositiveInt($userId, 'user ID');
        $userIdOr401 = $this->requireAuthOr401();
        if ($userIdOr401 instanceof JsonResponse) return $userIdOr401;
        $currentUserId = (int) $userIdOr401;
        if ($userId !== $currentUserId) throw new AccessDeniedHttpException('Access denied');
        $limit = $this->validatePositiveIntParam((int) $request->query->get('limit', 10), 'limit', 1, 100);
        $calls = $this->service->getRecentCalls($userId, $limit);
        return $this->json(['data' => array_values($calls)]);
    }

    #[Route('/user/{userId}/missed', name: 'call_get_missed', methods: ['GET'])]
    public function getMissedCalls(int $userId): JsonResponse
    {
        return $this->handle(fn() => $this->getMissedCallsAction($userId));
    }

    private function getMissedCallsAction(int $userId): JsonResponse
    {
        $this->validatePositiveInt($userId, 'user ID');
        $userIdOr401 = $this->requireAuthOr401();
        if ($userIdOr401 instanceof JsonResponse) return $userIdOr401;
        $currentUserId = (int) $userIdOr401;
        if ($userId !== $currentUserId) throw new AccessDeniedHttpException('Access denied');
        $calls = $this->service->getMissedCalls($userId);
        return $this->json(['data' => array_values($calls)]);
    }

    #[Route('/user/{userId}/stats', name: 'call_get_stats', methods: ['GET'])]
    public function getCallStats(int $userId): JsonResponse
    {
        return $this->handle(fn() => $this->getCallStatsAction($userId));
    }

    private function getCallStatsAction(int $userId): JsonResponse
    {
        $this->validatePositiveInt($userId, 'user ID');
        $userIdOr401 = $this->requireAuthOr401();
        if ($userIdOr401 instanceof JsonResponse) return $userIdOr401;
        $currentUserId = (int) $userIdOr401;
        if ($userId !== $currentUserId) throw new AccessDeniedHttpException('Access denied');
        $stats = $this->service->getCallStats($userId);
        return $this->json(['data' => $stats]);
    }

    #[Route('/{id}/answer', name: 'call_answer', methods: ['POST'])]
    public function answerCall(int $id): JsonResponse
    {
        return $this->handle(fn() => $this->answerCallAction($id));
    }

    private function answerCallAction(int $id): JsonResponse
    {
        $call = $this->service->answerCall($id);
        if (!$call) throw new NotFoundHttpException('Call not found');
        return $this->json(['data' => $call]);
    }

    #[Route('/{id}/end', name: 'call_end', methods: ['POST'])]
    public function endCall($id, Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->endCallAction((int)$id, $request));
    }

    private function endCallAction(int $id, Request $request): JsonResponse
    {
        $user = $this->requireAuthUser();
        $call = $this->service->getCallById($id);
        if (!$call) throw new NotFoundHttpException('Call not found');
        $this->service->endCall($call, $user);
        return $this->json(['success' => true]);
    }

    #[Route('', name: 'call_list', methods: ['GET'])]
    public function getActiveCalls(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->getActiveCallsAction($request));
    }

    private function getActiveCallsAction(Request $request): JsonResponse
    {
        $user = $this->requireAuthUser();
        $calls = $this->service->getActiveCalls($user);
        return $this->json(['active_calls' => array_values($calls)]);
    }

    #[Route('/start', name: 'call_start', methods: ['POST'])]
    public function startCall(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->startCallAction($request));
    }

    private function startCallAction(Request $request): JsonResponse
    {
        $user = $this->requireAuthUser();
        $data = $this->getJsonData($request);
        if (!isset($data['recipientId']) && !isset($data['groupId'])) {
            throw new InvalidArgumentException('Recipient or group ID is required');
        }
        $doctrine = $this->service->getDoctrine();
        if (isset($data['recipientId'])) {
            $recipient = $doctrine->getRepository(User::class)->find($data['recipientId']);
            if (!$recipient) throw new NotFoundHttpException('User not found');
            $call = $this->service->initiateCall($user, $recipient, null, $data['type'] ?? 'audio');
        } else {
            $group = $doctrine->getRepository(Group::class)->find($data['groupId']);
            if (!$group) throw new NotFoundHttpException('Group not found');
            $call = $this->service->initiateCall($user, null, $group, $data['type'] ?? 'audio');
        }
        $this->logAction('call_started', ['call_id' => $call->getId(), 'user_id' => $user->getId()]);
        $this->incrementMetric('calls_started_total');
        return $this->json([
            'call_id' => $call->getCallId(),
            'type' => $call->getType(),
            'status' => $call->getStatus(),
        ]);
    }

    #[Route('/{id}/join', name: 'call_join', methods: ['POST'])]
    public function joinCall($id): JsonResponse
    {
        return $this->handle(fn() => $this->joinCallAction((int)$id));
    }

    private function joinCallAction(int $id): JsonResponse
    {
        $user = $this->requireAuthUser();
        $call = $this->service->getCallById($id);
        if (!$call) throw new NotFoundHttpException('Call not found');
        $this->service->joinCall($call, $user);
        return $this->json(['success' => true]);
    }

    #[Route('/{id}/accept', name: 'call_accept', methods: ['POST'])]
    public function acceptCall($id): JsonResponse
    {
        return $this->handle(fn() => $this->acceptCallAction((int)$id));
    }

    private function acceptCallAction(int $id): JsonResponse
    {
        $user = $this->requireAuthUser();
        $call = $this->service->getCallById($id);
        if (!$call) throw new NotFoundHttpException('Call not found');
        $this->service->acceptCall($call, $user);
        return $this->json(['success' => true]);
    }

    #[Route('/{id}/reject', name: 'call_reject', methods: ['POST'])]
    public function rejectCall($id): JsonResponse
    {
        return $this->handle(fn() => $this->rejectCallAction((int)$id));
    }

    private function rejectCallAction(int $id): JsonResponse
    {
        $user = $this->requireAuthUser();
        $call = $this->service->getCallById($id);
        if (!$call) throw new NotFoundHttpException('Call not found');
        $this->service->rejectCall($call, $user);
        return $this->json(['success' => true]);
    }

    // ========== PRIVATE HELPERS ==========

    private function validatePositiveInt(int $value, string $fieldName, int $min = 1): void
    {
        if ($value < $min) {
            throw new InvalidArgumentException("$fieldName must be at least $min");
        }
    }

    private function validatePositiveIntParam(int $value, string $fieldName, int $min = 1, ?int $max = null): int
    {
        if ($value < $min) throw new InvalidArgumentException("$fieldName must be at least $min");
        if ($max !== null && $value > $max) throw new InvalidArgumentException("$fieldName must not exceed $max");
        return $value;
    }

    private function handleException(string $context, \Exception $e): JsonResponse
    {
        error_log("CallController::{$context} error: " . $e->getMessage());
        $this->logAction("{$context}_error", ['error' => $e->getMessage()]);
        $this->incrementMetric("{$context}_errors");
        return $this->json(['error' => 'Internal server error'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    private function logAction(string $action, array $context = []): void
    {
        $logEntry = [
            'timestamp' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            'action' => $action,
            'context' => $context,
        ];
        error_log(json_encode($logEntry));
    }

    private function incrementMetric(string $metricName): void
    {
        error_log(json_encode([
            'timestamp' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            'type' => 'metric',
            'name' => $metricName,
            'value' => 1,
        ]));
    }

    private function validateCallInitiationData(array $data): void
    {
        if (!isset($data['caller_id']) || !is_numeric($data['caller_id'])) {
            throw new InvalidArgumentException('Valid caller ID is required');
        }
        if (!isset($data['receiver_id']) || !is_numeric($data['receiver_id'])) {
            throw new InvalidArgumentException('Valid receiver ID is required');
        }
        if (isset($data['type']) && !in_array($data['type'], ['audio', 'video'])) {
            throw new InvalidArgumentException('Call type must be either audio or video');
        }
    }
}