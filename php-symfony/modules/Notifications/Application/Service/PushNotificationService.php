<?php

declare(strict_types=1);

namespace App\Notifications\Application\Service;

use App\Notifications\Domain\Entity\PushNotification;
use App\Notifications\Domain\Entity\PushSubscription;
use App\Notifications\Domain\Repository\PushNotificationRepositoryInterface;
use App\Notifications\Domain\Repository\PushSubscriptionRepositoryInterface;
use Ramsey\Uuid\Uuid;
use DateTimeImmutable;
use InvalidArgumentException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PushNotificationService
{
    private DeviceSenderRegistry $senderRegistry;
    private PushNotificationRepositoryInterface $notificationRepository;
    private PushSubscriptionRepositoryInterface $subscriptionRepository;

    public function __construct(
        PushNotificationRepositoryInterface $notificationRepository,
        PushSubscriptionRepositoryInterface $subscriptionRepository,
        ?DeviceSenderRegistry $senderRegistry = null
    ) {
        $this->notificationRepository = $notificationRepository;
        $this->subscriptionRepository = $subscriptionRepository;
        $this->senderRegistry = $senderRegistry ?? $this->createDefaultRegistry();
    }

    private function createDefaultRegistry(): DeviceSenderRegistry
    {
        $senders = [];
        $fcmKey = getenv('FCM_SERVER_KEY');
        if ($fcmKey) {
            $senders[] = new FcmSender($fcmKey);
        }
        $apnsKeyPath = getenv('APNS_KEY_PATH');
        $apnsKeyId = getenv('APNS_KEY_ID');
        $apnsTeamId = getenv('APNS_TEAM_ID');
        $apnsTopic = getenv('APNS_TOPIC');
        if ($apnsKeyPath && $apnsKeyId && $apnsTeamId && $apnsTopic) {
            $senders[] = new ApnsSender($apnsKeyPath, $apnsKeyId, $apnsTeamId, $apnsTopic);
        }
        $senders[] = new WebPushSender();
        return new DeviceSenderRegistry($senders);
    }

    public function sendNotification(
        string $userId, string $notificationType, string $title, string $body,
        ?array $data = null, ?string $actionUrl = null, ?string $actionLabel = null,
        ?DateTimeImmutable $scheduledAt = null
    ): PushNotification {
        $this->validateUserId($userId);
        $notification = $this->buildNotification($userId, $notificationType, $title, $body, $data, $actionUrl, $actionLabel, $scheduledAt);
        $this->notificationRepository->save($notification);
        if ($this->shouldDeliverImmediately($scheduledAt)) {
            $this->deliverNotification($notification);
        }
        return $notification;
    }

    public function subscribe(string $userId, string $deviceToken, string $deviceType, ?string $deviceName = null): PushSubscription
    {
        $this->validateUserId($userId);

        $existing = $this->findExistingSubscription($userId, $deviceToken);
        if ($existing !== null) {
            $this->activateSubscription($existing);
            return $existing;
        }

        $subscription = $this->createNewSubscription($userId, $deviceToken, $deviceType, $deviceName);
        $this->subscriptionRepository->save($subscription);

        return $subscription;
    }

    public function unsubscribe(string $subscriptionId, string $authenticatedUserId): ?PushSubscription
    {
        $subscription = $this->subscriptionRepository->findById($subscriptionId);

        if ($subscription === null) {
            return null;
        }

        if ($subscription->getUserId() !== $authenticatedUserId) {
            throw new AccessDeniedException('Not authorized to unsubscribe this subscription');
        }

        $subscription->deactivate();
        $this->subscriptionRepository->save($subscription);

        return $subscription;
    }

    public function getUserSubscriptions(string $userId): array
    {
        return $this->subscriptionRepository->findByUserId($userId);
    }

    public function getUserNotifications(string $userId, ?string $status = null, ?int $limit = 20): array
    {
        return $this->notificationRepository->findByUserId($userId, $status, $limit);
    }

    public function markNotificationRead(string $notificationId, string $authenticatedUserId): void
    {
        $notification = $this->notificationRepository->findById($notificationId);

        if ($notification === null) {
            throw new NotFoundHttpException('Notification not found');
        }

        if ($notification->getUserId() !== $authenticatedUserId) {
            throw new AccessDeniedException('Not authorized to mark this notification as read');
        }

        if ($notification->getStatus() === PushNotification::STATUS_DELIVERED) {
            $notification->markClicked();
            $this->notificationRepository->save($notification);
        }
    }

    public function processScheduledNotifications(): void
    {
        $now = new DateTimeImmutable();
        $notifications = $this->notificationRepository->findPendingScheduled($now);

        foreach ($notifications as $notification) {
            $this->deliverNotification($notification);
        }
    }

    public function retryFailedNotifications(): void
    {
        $notifications = $this->notificationRepository->findFailedRetryable();

        foreach ($notifications as $notification) {
            if ($notification->canRetry()) {
                $notification->resetForRetry();
                $this->notificationRepository->save($notification);
                $this->deliverNotification($notification);
            }
        }
    }

    private function deliverNotification(PushNotification $notification): void
    {
        $activeSubscriptions = $this->getActiveSubscriptionsForUser($notification->getUserId());

        if (empty($activeSubscriptions)) {
            $this->markDeliveredNoDevice($notification);
            return;
        }

        $successCount = $this->sendToAllDevices($notification, $activeSubscriptions);
        $this->aggregateAndUpdateNotificationStatus($notification, $successCount);
    }

    /**
     * Filter only active subscriptions.
     */
    private function filterActiveSubscriptions(array $subscriptions): array
    {
        return array_filter($subscriptions, fn($sub) => $sub->isActive());
    }

    /**
     * Attempt to send notification to all subscriptions, return success count.
     */
    private function sendToAllDevices(PushNotification $notification, array $subscriptions): int
    {
        $successCount = 0;
        foreach ($subscriptions as $subscription) {
            try {
                $result = $this->sendToDevice($subscription, $notification);
                if ($result) {
                    $successCount++;
                    $subscription->markUsed();
                    $this->subscriptionRepository->save($subscription);
                }
            } catch (\Throwable $e) {
                error_log('Send to device failed: ' . $e->getMessage());
            }
        }
        return $successCount;
    }

    /**
     * Update notification status based on delivery results.
     */
    private function aggregateAndUpdateNotificationStatus(PushNotification $notification, int $successCount): void
    {
        if ($successCount > 0) {
            $notification->markDelivered();
        } else {
            $notification->markFailed('Failed to deliver to any device');
        }
        $this->notificationRepository->save($notification);
    }

    private function sendToDevice(PushSubscription $subscription, PushNotification $notification): bool
    {
        $deviceType = $subscription->getDeviceType();
        $deviceToken = $subscription->getDeviceToken();

        $payload = [
            'notification' => [
                'title' => $notification->getTitle(),
                'body' => $notification->getBody(),
                'sound' => 'default',
            ],
            'data' => array_merge([
                'notificationId' => $notification->getId(),
                'type' => $notification->getNotificationType(),
            ], $notification->getData() ?? []),
        ];

        if ($notification->getActionUrl()) {
            $payload['data']['actionUrl'] = $notification->getActionUrl();
            $payload['data']['actionLabel'] = $notification->getActionLabel() ?? 'Open';
        }

        $sender = $this->senderRegistry->getSenderForType($deviceType);
        if (!$sender) {
            return false;
        }

        return $sender->send($deviceToken, $payload);
    }

    private function validateUserId(string $userId): void
    {
        if (empty(trim($userId))) {
            throw new InvalidArgumentException('User ID is required');
        }
    }

    private function buildNotification(
        string $userId, string $type, string $title, string $body,
        ?array $data, ?string $actionUrl, ?string $actionLabel,
        ?DateTimeImmutable $scheduledAt
    ): PushNotification {
        $notification = new PushNotification(
            Uuid::uuid4()->toString(),
            $userId,
            $type,
            $title,
            $body,
            $data,
            $scheduledAt
        );
        $notification->setActionUrl($actionUrl);
        $notification->setActionLabel($actionLabel);

        return $notification;
    }

    private function shouldDeliverImmediately(?DateTimeImmutable $scheduledAt): bool
    {
        return $scheduledAt === null;
    }

    private function getActiveSubscriptionsForUser(string $userId): array
    {
        $subscriptions = $this->subscriptionRepository->findByUserId($userId);
        return $this->filterActiveSubscriptions($subscriptions);
    }

    private function markDeliveredNoDevice(PushNotification $notification): void
    {
        $notification->markDelivered();
        $this->notificationRepository->save($notification);
    }

    private function findExistingSubscription(string $userId, string $deviceToken): ?PushSubscription
    {
        return $this->subscriptionRepository->findByUserAndToken($userId, $deviceToken);
    }

    private function activateSubscription(PushSubscription $subscription): void
    {
        $subscription->activate();
        $subscription->markUsed();
        $this->subscriptionRepository->save($subscription);
    }

    private function createNewSubscription(string $userId, string $deviceToken, string $deviceType, ?string $deviceName): PushSubscription
    {
        return new PushSubscription(
            Uuid::uuid4()->toString(),
            $userId,
            $deviceToken,
            $deviceType,
            $deviceName
        );
    }
}

