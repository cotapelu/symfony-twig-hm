<?php

declare(strict_types=1);

namespace App\Controller\Notification;

use App\Entity\Common\Notification;
use App\Repository\Common\NotificationRepository;
use App\Service\Security\OwnershipChecker;
use App\Shared\Controller\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use InvalidArgumentException;

#[Route('/api/notification', name: 'api_notification_')]
#[IsGranted('ROLE_USER')]
final class NotificationController extends BaseController
{
    public function __construct(
        private NotificationRepository $notificationRepository,
        private OwnershipChecker $ownershipChecker
    ) {
        parent::__construct();
    }

    private function getJsonData(Request $request): array
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            throw new InvalidArgumentException('Invalid JSON payload');
        }
        return $data;
    }

    private function handle(callable $action): JsonResponse
    {
        try {
            return $action();
        } catch (InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (AccessDeniedHttpException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_FORBIDDEN);
        } catch (\Throwable $e) {
            return $this->handleException('notification', $e);
        }
    }

    private function handleException(string $context, \Throwable $e): JsonResponse
    {
        return $this->json(['error' => 'Internal server error'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    private function assertNotificationOwnership(Notification $notification): void
    {
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException('Authentication required');
        }

        $currentUserId = $user->getId();
        if (!$this->ownershipChecker->isOwner($notification->getUserId(), $currentUserId)) {
            throw new AccessDeniedHttpException('Access denied: Not your notification');
        }
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->listAction($request));
    }

    private function listAction(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException('Authentication required');
        }

        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(100, max(1, (int) $request->query->get('limit', 20)));
        $offset = ($page - 1) * $limit;

        $notifications = $this->notificationRepository->findByUserId($user->getId());
        $totalItems = count($notifications);
        $paginated = array_slice($notifications, $offset, $limit);

        $data = [];
        foreach ($paginated as $notification) {
            $data[] = [
                'id' => $notification->getId(),
                'title' => $notification->getTitle(),
                'message' => $notification->getBody(),
                'read' => $notification->isRead(),
            ];
        }

        return $this->json([
            'data' => $data,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total_items' => $totalItems,
                'total_pages' => (int) ceil($totalItems / $limit),
            ]
        ]);
    }

    #[Route('/{id}/read', name: 'mark_read', methods: ['PUT'])]
    public function markRead(string $id): JsonResponse
    {
        return $this->handle(fn() => $this->markReadAction($id));
    }

    private function markReadAction(string $id): JsonResponse
    {
        $notification = $this->notificationRepository->find($id);
        if (!$notification instanceof Notification) {
            throw new AccessDeniedHttpException('Notification not found');
        }

        $this->assertNotificationOwnership($notification);
        $this->notificationRepository->markAsRead($id);

        return $this->json([
            'id' => $notification->getId(),
            'read' => true,
        ]);
    }
}