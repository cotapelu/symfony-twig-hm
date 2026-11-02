<?php declare(strict_types=1);

namespace App\Tests\Unit\Notifications\Application\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Notifications\Domain\Entity\PushNotification;
use App\Notifications\Domain\Entity\PushSubscription;
use App\Notifications\Domain\Repository\PushNotificationRepositoryInterface;
use App\Notifications\Domain\Repository\PushSubscriptionRepositoryInterface;
use App\Notifications\Application\Service\DeviceSenderRegistry;
use App\Notifications\Application\Service\PushNotificationService;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[AllowMockObjectsWithoutExpectations]
class PushNotificationServiceTest extends TestCase
{
    private PushNotificationRepositoryInterface $notificationRepository;
    private PushSubscriptionRepositoryInterface $subscriptionRepository;
    private DeviceSenderRegistry $senderRegistry;
    private PushNotificationService $service;

    protected function setUp(): void
    {
        $this->notificationRepository = $this->createMock(PushNotificationRepositoryInterface::class);
        $this->subscriptionRepository = $this->createMock(PushSubscriptionRepositoryInterface::class);
        $this->senderRegistry = $this->createMock(DeviceSenderRegistry::class);
        $this->service = new PushNotificationService(
            $this->notificationRepository,
            $this->subscriptionRepository,
            $this->senderRegistry
        );
    }

    // ================= sendNotification =================

    public function testSendNotificationThrowsWhenUserIdEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User ID is required');
        $this->service->sendNotification('', 'type', 'Title', 'Body');
    }

    public function testSendNotificationWithScheduledSkipsDelivery(): void
    {
        $userId = 'user123';
        $type = PushNotification::TYPE_ANNOUNCEMENT;
        $title = 'Test Title';
        $body = 'Test Body';
        $scheduledAt = new DateTimeImmutable('+1 day');

        $this->notificationRepository->expects($this->once())->method('save');
        $this->subscriptionRepository->expects($this->never())->method('findByUserId');

        $result = $this->service->sendNotification($userId, $type, $title, $body, scheduledAt: $scheduledAt);

        $this->assertInstanceOf(PushNotification::class, $result);
        $this->assertSame($userId, $result->getUserId());
        $this->assertSame($type, $result->getNotificationType());
        $this->assertSame($title, $result->getTitle());
        $this->assertSame($body, $result->getBody());
    }

    public function testSendNotificationImmediateTriggersDelivery(): void
    {
        $userId = 'user123';
        $type = PushNotification::TYPE_ANNOUNCEMENT;
        $title = 'Hello';
        $body = 'World';
        $data = ['key' => 'value'];

        $saveCount = 0;
        $this->notificationRepository->method('save')->willReturnCallback(function () use (&$saveCount) {
            $saveCount++;
        });
        $this->subscriptionRepository->expects($this->once())
            ->method('findByUserId')
            ->with($userId)
            ->willReturn([]); // no subscriptions, triggers failure save

        $result = $this->service->sendNotification($userId, $type, $title, $body, $data);

        $this->assertSame(2, $saveCount);
        $this->assertInstanceOf(PushNotification::class, $result);
        $this->assertSame($userId, $result->getUserId());
        $this->assertSame($data, $result->getData());
    }

    public function testSendNotificationSetsActionUrlAndLabel(): void
    {
        $userId = 'user123';
        $type = PushNotification::TYPE_DOCUMENT_STATUS;
        $title = 'Doc Updated';
        $body = 'Your document has been updated.';
        $actionUrl = 'https://example.com/documents/123';
        $actionLabel = 'View Document';

        $saveCount = 0;
        $this->notificationRepository->method('save')->willReturnCallback(function () use (&$saveCount) {
            $saveCount++;
        });
        $this->subscriptionRepository->expects($this->once())
            ->method('findByUserId')
            ->with($userId)
            ->willReturn([]);

        $result = $this->service->sendNotification($userId, $type, $title, $body, actionUrl: $actionUrl, actionLabel: $actionLabel);

        $this->assertSame(2, $saveCount);
        $this->assertSame($actionUrl, $result->getActionUrl());
        $this->assertSame($actionLabel, $result->getActionLabel());
    }

    // ================= subscribe =================

    public function testSubscribeReactivatesExistingSubscription(): void
    {
        $userId = 'user123';
        $deviceToken = 'token_abc';
        $deviceType = PushSubscription::DEVICE_TYPE_ANDROID;
        $deviceName = 'My Android Phone';

        $existing = $this->createMock(PushSubscription::class);
        $existing->method('getUserId')->willReturn($userId);
        $existing->method('getDeviceToken')->willReturn($deviceToken);

        $this->subscriptionRepository->expects($this->once())
            ->method('findByUserAndToken')
            ->with($userId, $deviceToken)
            ->willReturn($existing);

        $existing->expects($this->once())->method('activate');
        $existing->expects($this->once())->method('markUsed');

        $this->subscriptionRepository->expects($this->once())->method('save')->with($existing);

        $result = $this->service->subscribe($userId, $deviceToken, $deviceType, $deviceName);

        $this->assertSame($existing, $result);
    }

    public function testSubscribeCreatesNewSubscriptionWhenNotExists(): void
    {
        $userId = 'user123';
        $deviceToken = 'token_new';
        $deviceType = PushSubscription::DEVICE_TYPE_IOS;
        $deviceName = 'iPhone';

        $this->subscriptionRepository->expects($this->once())
            ->method('findByUserAndToken')
            ->with($userId, $deviceToken)
            ->willReturn(null);

        $this->subscriptionRepository->expects($this->once())->method('save')
            ->with($this->callback(function (PushSubscription $sub) use ($userId, $deviceToken, $deviceType, $deviceName) {
                return $sub->getUserId() === $userId
                    && $sub->getDeviceToken() === $deviceToken
                    && $sub->getDeviceType() === $deviceType
                    && $sub->getDeviceName() === $deviceName;
            }));

        $result = $this->service->subscribe($userId, $deviceToken, $deviceType, $deviceName);

        $this->assertInstanceOf(PushSubscription::class, $result);
        $this->assertSame($userId, $result->getUserId());
        $this->assertSame($deviceToken, $result->getDeviceToken());
        $this->assertSame($deviceType, $result->getDeviceType());
        $this->assertSame($deviceName, $result->getDeviceName());
    }


    // ================= unsubscribe =================

    public function testUnsubscribeReturnsNullWhenSubscriptionNotFound(): void
    {
        $subscriptionId = 'sub123';
        $authenticatedUserId = 'user123';

        $this->subscriptionRepository->expects($this->once())
            ->method('findById')
            ->with($subscriptionId)
            ->willReturn(null);

        $result = $this->service->unsubscribe($subscriptionId, $authenticatedUserId);

        $this->assertNull($result);
    }

    public function testUnsubscribeThrowsAccessDeniedWhenUserNotOwner(): void
    {
        $subscriptionId = 'sub123';
        $authenticatedUserId = 'user123';
        $subscription = $this->createMock(PushSubscription::class);
        $subscription->method('getUserId')->willReturn('other_user');

        $this->subscriptionRepository->expects($this->once())
            ->method('findById')
            ->with($subscriptionId)
            ->willReturn($subscription);

        $this->expectException(AccessDeniedException::class);
        $this->service->unsubscribe($subscriptionId, $authenticatedUserId);
    }

    public function testUnsubscribeDeactivatesAndSavesWhenAuthorized(): void
    {
        $subscriptionId = 'sub123';
        $authenticatedUserId = 'user123';
        $subscription = $this->createMock(PushSubscription::class);
        $subscription->method('getUserId')->willReturn($authenticatedUserId);
        $subscription->expects($this->once())->method('deactivate');

        $this->subscriptionRepository->expects($this->once())
            ->method('findById')
            ->with($subscriptionId)
            ->willReturn($subscription);
        $this->subscriptionRepository->expects($this->once())
            ->method('save')
            ->with($subscription);

        $result = $this->service->unsubscribe($subscriptionId, $authenticatedUserId);

        $this->assertSame($subscription, $result);
    }

    // ================= getUserSubscriptions =================

    public function testGetUserSubscriptionsDelegatesToRepository(): void
    {
        $userId = 'user123';
        $expected = [$this->createMock(PushSubscription::class)];

        $this->subscriptionRepository->expects($this->once())
            ->method('findByUserId')
            ->with($userId)
            ->willReturn($expected);

        $result = $this->service->getUserSubscriptions($userId);

        $this->assertSame($expected, $result);
    }

    // ================= markNotificationRead =================

    public function testMarkNotificationReadThrowsIfNotificationNotFound(): void
    {
        $notificationId = 'nonexistent';
        $authenticatedUserId = 'user123';

        $this->notificationRepository->expects($this->once())
            ->method('findById')
            ->with($notificationId)
            ->willReturn(null);

        $this->expectException(NotFoundHttpException::class);
        $this->service->markNotificationRead($notificationId, $authenticatedUserId);
    }

    public function testMarkNotificationReadThrowsIfNotAuthorized(): void
    {
        $notification = $this->createMock(PushNotification::class);
        $notificationId = 'id123';
        $authenticatedUserId = 'user123';
        $notification->method('getUserId')->willReturn('other_user');

        $this->notificationRepository->expects($this->once())
            ->method('findById')
            ->with($notificationId)
            ->willReturn($notification);

        $this->expectException(AccessDeniedException::class);
        $this->service->markNotificationRead($notificationId, $authenticatedUserId);
    }

    public function testMarkNotificationReadMarksClickedWhenDelivered(): void
    {
        $notification = $this->createMock(PushNotification::class);
        $notificationId = 'id123';
        $authenticatedUserId = 'user123';
        $notification->method('getUserId')->willReturn($authenticatedUserId);
        $notification->method('getStatus')->willReturn(PushNotification::STATUS_DELIVERED);
        $notification->expects($this->once())->method('markClicked');

        $this->notificationRepository->expects($this->once())
            ->method('findById')
            ->with($notificationId)
            ->willReturn($notification);
        $this->notificationRepository->expects($this->once())
            ->method('save')
            ->with($notification);

        $this->service->markNotificationRead($notificationId, $authenticatedUserId);
    }

    public function testMarkNotificationReadDoesNothingWhenNotDelivered(): void
    {
        $notification = $this->createMock(PushNotification::class);
        $notificationId = 'id123';
        $authenticatedUserId = 'user123';
        $notification->method('getUserId')->willReturn($authenticatedUserId);
        $notification->method('getStatus')->willReturn(PushNotification::STATUS_PENDING);
        $notification->expects($this->never())->method('markClicked');

        $this->notificationRepository->expects($this->once())
            ->method('findById')
            ->with($notificationId)
            ->willReturn($notification);
        $this->notificationRepository->expects($this->never())->method('save');

        $this->service->markNotificationRead($notificationId, $authenticatedUserId);
    }

    public function testGetUserNotificationsWithoutFilters(): void
    {
        $userId = 'user123';
        $expected = [$this->createMock(PushNotification::class)];

        $this->notificationRepository->expects($this->once())
            ->method('findByUserId')
            ->with($userId, null, 20)
            ->willReturn($expected);

        $result = $this->service->getUserNotifications($userId);
        $this->assertSame($expected, $result);
    }

    public function testGetUserNotificationsWithStatusAndLimit(): void
    {
        $userId = 'user456';
        $status = PushNotification::STATUS_DELIVERED;
        $limit = 10;
        $expected = [$this->createMock(PushNotification::class)];

        $this->notificationRepository->expects($this->once())
            ->method('findByUserId')
            ->with($userId, $status, $limit)
            ->willReturn($expected);

        $result = $this->service->getUserNotifications($userId, $status, $limit);
        $this->assertSame($expected, $result);
    }

    public function testGetUserNotificationsReturnsEmptyArrayWhenNoResults(): void
    {
        $userId = 'user789';
        $this->notificationRepository->expects($this->once())
            ->method('findByUserId')
            ->with($userId, null, 20)
            ->willReturn([]);

        $result = $this->service->getUserNotifications($userId);
        $this->assertSame([], $result);
    }

    public function testProcessScheduledNotificationsDeliversPending(): void
    {
        $now = new DateTimeImmutable();
        $notifications = [$this->createMock(PushNotification::class), $this->createMock(PushNotification::class)];
        foreach ($notifications as $n) {
            $n->method('getUserId')->willReturn('user1');
        }

        $this->notificationRepository->expects($this->once())
            ->method('findPendingScheduled')
            ->with($this->isInstanceOf(DateTimeImmutable::class))
            ->willReturn($notifications);

        $this->subscriptionRepository->expects($this->atLeastOnce())
            ->method('findByUserId')
            ->with('user1')
            ->willReturn([]);

        $saveCount = 0;
        $this->notificationRepository->method('save')->willReturnCallback(function () use (&$saveCount) {
            $saveCount++;
        });

        $this->service->processScheduledNotifications();

        $this->assertSame(count($notifications), $saveCount);
    }

    public function testRetryFailedNotificationsRetriesEligible(): void
    {
        $notifications = [$this->createMock(PushNotification::class), $this->createMock(PushNotification::class)];
        foreach ($notifications as $n) {
            $n->method('canRetry')->willReturn(true);
            $n->expects($this->once())->method('resetForRetry');
        }

        $this->notificationRepository->expects($this->once())
            ->method('findFailedRetryable')
            ->willReturn($notifications);

        $this->subscriptionRepository->method('findByUserId')->willReturn([]);

        $saveCount = 0;
        $this->notificationRepository->method('save')->willReturnCallback(function () use (&$saveCount) {
            $saveCount++;
        });

        $this->service->retryFailedNotifications();

        $this->assertGreaterThan(0, $saveCount);
    }

}
