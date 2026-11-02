<?php

declare(strict_types=1);

namespace App\Notifications\Api\Controller;

use App\Notifications\Application\Service\PushNotificationService;
use App\Shared\Controller\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use InvalidArgumentException;
use DateTimeImmutable;

class NotificationsController extends BaseController
{
    private PushNotificationService $notificationService;
    private ?TokenStorageInterface $tokenStorage;

    public function __construct(PushNotificationService $notificationService, ?TokenStorageInterface $tokenStorage = null)
    {
        $this->notificationService = $notificationService;
        $this->tokenStorage = $tokenStorage;
    }

    private function requireAuth(): string
    {
        $user = null;
        if (null !== $this->tokenStorage) {
            $token = $this->tokenStorage->getToken();
            if (null !== $token) {
                $user = $token->getUser();
            }
        }
        if (!$user instanceof UserInterface) {
            $user = $this->getUser();
        }
        if (!$user instanceof UserInterface) {
            throw new InvalidArgumentException('Authentication required');
        }
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
        catch (InvalidArgumentException $e) { return $this->json(['success'=>false, 'error'=>$e->getMessage()], 400); }
        catch (\Exception $e) { return $this->handleException('controller', $e); }
    }

    private function handleException(string $ctx, \Exception $e): JsonResponse
    {
        error_log("NotificationsController::{$ctx} error: ".$e->getMessage());
        $this->logAction("{$ctx}_error", ['error'=>$e->getMessage()]);
        $this->incrementMetric("{$ctx}_errors");
        return $this->json(['success'=>false, 'error'=>'Internal server error'], 500);
    }

    private function logAction(string $action, array $context = []): void
    {
        error_log(json_encode(['timestamp'=>(new DateTimeImmutable())->format('Y-m-d H:i:s'), 'action'=>$action, 'userId'=>$this->getUser()?->getUserIdentifier() ?? 'anonymous', 'module'=>'Notifications', 'context'=>$context]));
    }

    private function incrementMetric(string $metric, array $tags = []): void
    {
        error_log(json_encode(['timestamp'=>(new DateTimeImmutable())->format('Y-m-d H:i:s'), 'type'=>'metric', 'name'=>$metric, 'tags'=>$tags, 'value'=>1]));
    }

    private function sanitizeString(?string $value): string
    {
        return htmlspecialchars(trim($value ?? ''), ENT_QUOTES, 'UTF-8');
    }

    // ==================== Endpoints ====================

    public function subscribe(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->subscribeAction($request));
    }

    private function subscribeAction(Request $request): JsonResponse
    {
        $data = $this->getJsonData($request);
        $required = ['deviceToken', 'deviceType'];
        foreach ($required as $field) {
            if (empty(trim((string)($data[$field] ?? '')))) throw new InvalidArgumentException("Missing required field: $field");
        }
        $deviceToken = trim($data['deviceToken']);
        $deviceType = trim($data['deviceType']);
        $deviceName = isset($data['deviceName']) ? $this->sanitizeString((string)$data['deviceName']) : null;

        $userId = $this->requireAuth();
        $subscription = $this->notificationService->subscribe($userId, $deviceToken, $deviceType, $deviceName);

        $this->logAction('subscription_created', ['subscription_id'=>$subscription->getId()]);
        $this->incrementMetric('notifications_subscriptions_created_total');

        return $this->json(['success'=>true, 'data'=>$subscription->toArray()], 201);
    }

    public function unsubscribe(string $subscriptionId): JsonResponse
    {
        return $this->handle(fn() => $this->unsubscribeAction($subscriptionId));
    }

    private function unsubscribeAction(string $subscriptionId): JsonResponse
    {
        $userId = $this->requireAuth();
        $subscription = $this->notificationService->unsubscribe($subscriptionId, $userId);
        if ($subscription === null) return $this->json(['success'=>false, 'error'=>'Subscription not found'], 404);

        $this->logAction('subscription_unsubscribed', ['subscription_id'=>$subscriptionId]);
        $this->incrementMetric('notifications_subscriptions_unsubscribed_total');

        return $this->json(['success'=>true, 'data'=>$subscription->toArray()]);
    }

    public function getMySubscriptions(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->getMySubscriptionsAction($request));
    }

    private function getMySubscriptionsAction(Request $request): JsonResponse
    {
        $userId = $this->requireAuth();
        $subscriptions = $this->notificationService->getUserSubscriptions($userId);
        $this->logAction('subscriptions_listed', ['user_id'=>$userId, 'count'=>count($subscriptions)]);
        $this->incrementMetric('notifications_subscriptions_listed_total');
        return $this->json(['success'=>true, 'data'=>array_map(fn($s)=>$s->toArray(), $subscriptions)]);
    }

    public function getMyNotifications(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->getMyNotificationsAction($request));
    }

    private function getMyNotificationsAction(Request $request): JsonResponse
    {
        $userId = $this->requireAuth();
        $status = $request->query->get('status');
        $limit = $request->query->getInt('limit', 20);
        if ($limit <= 0) $limit = 20;
        if ($limit > 100) $limit = 100;
        $notifications = $this->notificationService->getUserNotifications($userId, $status, $limit);
        $this->logAction('notifications_listed', ['user_id'=>$userId, 'count'=>count($notifications)]);
        $this->incrementMetric('notifications_listed_total');
        return $this->json(['success'=>true, 'data'=>array_map(fn($n)=>$n->toArray(), $notifications)]);
    }

    public function markRead(string $notificationId): JsonResponse
    {
        return $this->handle(fn() => $this->markReadAction($notificationId));
    }

    private function markReadAction(string $notificationId): JsonResponse
    {
        $userId = $this->requireAuth();
        try {
            $this->notificationService->markNotificationRead($notificationId, $userId);
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            // Idempotent: return success even if notification doesn't exist
            return $this->json(['success'=>true]);
        }
        $this->logAction('notification_marked_read', ['notification_id'=>$notificationId]);
        $this->incrementMetric('notification_marked_read_total');
        return $this->json(['success'=>true]);
    }

    public function processScheduled(): JsonResponse
    {
        // This endpoint might be called by a cron job; optionally protect with admin role.
        try {
            $this->notificationService->processScheduledNotifications();
            $this->notificationService->retryFailedNotifications();
            $this->logAction('scheduled_notifications_processed');
            $this->incrementMetric('scheduled_notifications_processed_total');
            return $this->json(['success'=>true, 'message'=>'Notifications processed']);
        } catch (\Exception $e) {
            return $this->handleException('process_scheduled', $e);
        }
    }
}
