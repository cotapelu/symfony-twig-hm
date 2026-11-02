<?php

declare(strict_types=1);

namespace App\Notification\Controller;

use App\Notification\Service\NotificationService;
use App\Shared\Controller\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use InvalidArgumentException;
use DateTimeImmutable;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[Route('/api/notifications')]
class NotificationController extends BaseController
{
    private NotificationService $service;
    private ?TokenStorageInterface $tokenStorage;

    public function __construct(NotificationService $service, ?TokenStorageInterface $tokenStorage = null)
    {
        $this->service = $service;
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
        catch (InvalidArgumentException $e) { return $this->json(['status'=>400, 'error'=>$e->getMessage()], 400); }
        catch (\Exception $e) { return $this->handleException('controller', $e); }
    }

    private function handleException(string $ctx, \Exception $e): JsonResponse
    {
        error_log("NotificationController::{$ctx} error: ".$e->getMessage());
        $this->logAction("{$ctx}_error", ['error'=>$e->getMessage()]);
        $this->incrementMetric("{$ctx}_errors");
        return $this->json(['status'=>500, 'error'=>'Internal server error'], 500);
    }

    private function logAction(string $action, array $context = []): void
    {
        error_log(json_encode(['timestamp'=>(new DateTimeImmutable())->format('Y-m-d H:i:s'), 'action'=>$action, 'userId'=>$this->getUser()?->getUserIdentifier() ?? 'anonymous', 'module'=>'Notification', 'context'=>$context]));
    }

    private function incrementMetric(string $metric, array $tags = []): void
    {
        error_log(json_encode(['timestamp'=>(new DateTimeImmutable())->format('Y-m-d H:i:s'), 'type'=>'metric', 'name'=>$metric, 'tags'=>$tags, 'value'=>1]));
    }

    private function validateSendData(array $data): void
    {
        if (empty(trim($data['type'] ?? ''))) throw new InvalidArgumentException('Type is required');
        if (empty(trim($data['title'] ?? ''))) throw new InvalidArgumentException('Title is required');
        if (empty(trim($data['message'] ?? ''))) throw new InvalidArgumentException('Message is required');
        $allowedChannels = ['in_app', 'email', 'push', 'sms'];
        if (!in_array($data['channel'] ?? 'in_app', $allowedChannels, true)) {
            throw new InvalidArgumentException('Invalid channel');
        }
    }

    private function sanitizeString(?string $value): string
    {
        return htmlspecialchars(trim($value ?? ''), ENT_QUOTES, 'UTF-8');
    }

    // ==================== Endpoints ====================

    #[Route('/send', name: 'notification_send', methods: ['POST'])]
    public function sendNotification(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->sendNotificationAction($request));
    }

    private function sendNotificationAction(Request $request): JsonResponse
    {
        $userId = $this->requireAuth();
        $data = $this->getJsonData($request);
        $this->validateSendData($data);

        $notification = $this->service->sendNotification(
            (string)$userId,
            $this->sanitizeString($data['type']),
            $this->sanitizeString($data['title']),
            $this->sanitizeString($data['message']),
            $data['channel'] ?? 'in_app',
            $data['data'] ?? []
        );

        $this->logAction('notification_sent', ['notification_id'=>$notification->getId(), 'user_id'=>$userId]);
        $this->incrementMetric('notifications_sent_total');

        return $this->json(['status'=>201, 'data'=>$notification], 201);
    }

    #[Route('', name: 'notification_list', methods: ['GET'])]
    public function getNotifications(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->getNotificationsAction($request));
    }

    private function getNotificationsAction(Request $request): JsonResponse
    {
        $userId = $this->requireAuth();
        $unreadOnly = filter_var($request->query->get('unread', false), FILTER_VALIDATE_BOOLEAN);
        $page = max(1, (int)($request->query->get('page', 1)));
        $perPage = max(1, min(100, (int)($request->query->get('perPage', 20))));

        $result = $this->service->getUserNotificationsPaginated((string)$userId, $unreadOnly, $page, $perPage);
        $notifications = $result['data'] ?? [];
        $total = $result['total'] ?? count($notifications);

        $this->logAction('notifications_retrieved', ['user_id'=>$userId, 'unread_only'=>$unreadOnly, 'count'=>count($notifications), 'page'=>$page, 'per_page'=>$perPage]);
        $this->incrementMetric('notifications_listed_total');

        return $this->json([
            'status'=>200,
            'data'=>array_values($notifications),
            'pagination'=>['page'=>$page, 'per_page'=>$perPage, 'total'=>$total, 'total_pages'=>(int)ceil($total/$perPage)]
        ]);
    }

    #[Route('/{id}', name: 'notification_get', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getNotification(Request $request, int $id): JsonResponse
    {
        return $this->handle(fn() => $this->getNotificationAction($id));
    }

    private function getNotificationAction(int $id): JsonResponse
    {
        if ($id <= 0) throw new InvalidArgumentException('Notification ID must be positive');
        $userId = $this->requireAuth();
        $notification = $this->service->getNotification($id, (string)$userId);
        if (!$notification) return $this->json(['status'=>404, 'error'=>'Notification not found'], 404);
        $this->logAction('notification_viewed', ['notification_id'=>$id, 'user_id'=>$userId]);
        $this->incrementMetric('notification_viewed_total');
        return $this->json(['status'=>200, 'data'=>$notification]);
    }

    #[Route('/{id}/read', name: 'notification_mark_read', methods: ['PUT'])]
    public function markAsRead(Request $request, int $id): JsonResponse
    {
        return $this->handle(fn() => $this->markAsReadAction($id));
    }

    private function markAsReadAction(int $id): JsonResponse
    {
        if ($id <= 0) throw new InvalidArgumentException('Notification ID must be positive');
        $userId = $this->requireAuth();
        $notification = $this->service->markAsRead($id, (string)$userId);
        if (!$notification) return $this->json(['status'=>404, 'error'=>'Notification not found'], 404);
        $this->logAction('notification_marked_read', ['notification_id'=>$id, 'user_id'=>$userId]);
        $this->incrementMetric('notification_marked_read_total');
        return $this->json(['status'=>200, 'data'=>$notification]);
    }

    #[Route('/read-all', name: 'notification_mark_all_read', methods: ['PUT'])]
    public function markAllAsRead(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->markAllAsReadAction($request));
    }

    private function markAllAsReadAction(Request $request): JsonResponse
    {
        $userId = $this->requireAuth();
        $count = $this->service->markAllAsRead((string)$userId);
        $this->logAction('all_notifications_marked_read', ['user_id'=>$userId, 'count'=>$count]);
        $this->incrementMetric('all_notifications_marked_read_total');
        return $this->json(['status'=>200, 'data'=>['marked'=>$count]]);
    }

    #[Route('/{id}', name: 'notification_delete', methods: ['DELETE'])]
    public function deleteNotification(Request $request, int $id): JsonResponse
    {
        return $this->handle(fn() => $this->deleteNotificationAction($id));
    }

    private function deleteNotificationAction(int $id): JsonResponse
    {
        if ($id <= 0) throw new InvalidArgumentException('Notification ID must be positive');
        $userId = $this->requireAuth();
        $deleted = $this->service->deleteNotification($id, (string)$userId);
        if (!$deleted) return $this->json(['status'=>404, 'error'=>'Notification not found'], 404);
        $this->logAction('notification_deleted', ['notification_id'=>$id, 'user_id'=>$userId]);
        $this->incrementMetric('notifications_deleted_total');
        return $this->json(['status'=>204, 'data'=>null], 204);
    }

    #[Route('/preferences', name: 'notification_get_preferences', methods: ['GET'])]
    public function getPreferences(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->getPreferencesAction($request));
    }

    private function getPreferencesAction(Request $request): JsonResponse
    {
        $userId = $this->requireAuth();
        $type = $request->query->get('type', '');
        if (empty(trim($type))) throw new InvalidArgumentException('Type is required');
        $pref = $this->service->getPreference((string)$userId, $type);
        $this->logAction('preferences_retrieved', ['user_id'=>$userId, 'type'=>$type]);
        return $this->json(['status'=>200, 'data'=>$pref]);
    }

    #[Route('/preferences', name: 'notification_set_preferences', methods: ['POST'])]
    public function setPreferences(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->setPreferencesAction($request));
    }

    private function setPreferencesAction(Request $request): JsonResponse
    {
        $userId = $this->requireAuth();
        $data = $this->getJsonData($request);
        $type = $data['type'] ?? '';
        $enabled = $data['enabled'] ?? true;
        if (empty(trim($type))) throw new InvalidArgumentException('Type is required');
        $pref = $this->service->setPreference((string)$userId, $type, [$type => (bool)$enabled]);
        $this->logAction('preferences_set', ['user_id'=>$userId, 'type'=>$type, 'enabled'=>$enabled]);
        $this->incrementMetric('preferences_set_total');
        return $this->json(['status'=>200, 'data'=>$pref]);
    }
}
