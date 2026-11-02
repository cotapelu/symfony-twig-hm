<?php declare(strict_types=1);

namespace App\Tests\Unit\Notification\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Notification\Entity\Notification;
use App\Notification\Entity\NotificationPreference;
use App\Notification\Repository\NotificationRepositoryInterface;
use App\Notification\Service\NotificationService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class NotificationServiceTest extends TestCase
{
    private NotificationRepositoryInterface&MockObject $repo;
    private NotificationService $service;

    protected function setUp(): void
    {
        $this->repo = $this->createMock(NotificationRepositoryInterface::class);
        $this->service = new NotificationService($this->repo);
    }

    public function testSendNotificationCreatesAndSavesNotification(): void
    {
        $userId = '123';
        $type = 'alert';
        $title = 'Test Title';
        $message = 'Test Message';
        $channel = 'in_app';
        $data = ['key' => 'value'];

        $this->repo->expects($this->once())
            ->method('save')
            ->with($this->callback(function ($n) use ($userId, $type, $title, $message, $channel, $data) {
                return $n->getUserId() === $userId
                    && $n->getType() === $type
                    && $n->getTitle() === $title
                    && $n->getMessage() === $message
                    && $n->getChannel() === $channel
                    && $n->getData() === $data
                    && $n->isSent();
            }));

        $result = $this->service->sendNotification($userId, $type, $title, $message, $channel, $data);
        $this->assertInstanceOf(Notification::class, $result);
        $this->assertSame($userId, (string)$result->getUserId());
        $this->assertSame($type, $result->getType());
        $this->assertSame($title, $result->getTitle());
        $this->assertSame($message, $result->getMessage());
        $this->assertSame($channel, $result->getChannel());
        $this->assertSame($data, $result->getData());
        $this->assertTrue($result->isSent());
    }

    public function testSendNotificationThrowsForEmptyType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Type is required');
        $this->service->sendNotification('1', '', 'Title', 'Msg', 'in_app');
    }

    public function testSendNotificationThrowsForEmptyTitle(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Title is required');
        $this->service->sendNotification('1', 'type', '', 'Msg', 'in_app');
    }

    public function testSendNotificationThrowsForEmptyMessage(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Message is required');
        $this->service->sendNotification('1', 'type', 'Title', '', 'in_app');
    }

    public function testSendNotificationThrowsForInvalidChannel(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid channel');
        $this->service->sendNotification('1', 'type', 'Title', 'Msg', 'invalid');
    }

    public function testGetUserNotifications(): void
    {
        $userId = '5';
        $unreadOnly = true;
        $notifs = [$this->createMock(Notification::class), $this->createMock(Notification::class)];
        $this->repo->expects($this->once())
            ->method('findByUserId')
            ->with($userId, $unreadOnly)
            ->willReturn($notifs);

        $result = $this->service->getUserNotifications($userId, $unreadOnly);
        $this->assertSame($notifs, $result);
    }

    public function testGetUserNotificationsPaginated(): void
    {
        $userId = '5';
        $unreadOnly = false;
        $page = 2;
        $perPage = 10;
        $data = [$this->createMock(Notification::class)];
        $total = 25;

        $this->repo->expects($this->once())
            ->method('findByUserIdWithPagination')
            ->with($userId, $unreadOnly, $perPage, ($page-1)*$perPage)
            ->willReturn($data);
        $this->repo->expects($this->once())
            ->method('countByUserId')
            ->with($userId, $unreadOnly)
            ->willReturn($total);

        $result = $this->service->getUserNotificationsPaginated($userId, $unreadOnly, $page, $perPage);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertSame($data, $result['data']);
        $this->assertSame($total, $result['total']);
    }

    public function testGetNotificationReturnsNullIfNotFound(): void
    {
        $id = 99;
        $userId = '1';
        $this->repo->expects($this->once())
            ->method('findById')
            ->with($id)
            ->willReturn(null);

        $result = $this->service->getNotification($id, $userId);
        $this->assertNull($result);
    }

    public function testGetNotificationReturnsNullIfNotOwnedByUser(): void
    {
        $id = 99;
        $userId = '1';
        $notification = $this->createMock(Notification::class);
        $notification->method('getUserId')->willReturn('2'); // different user

        $this->repo->expects($this->once())
            ->method('findById')
            ->with($id)
            ->willReturn($notification);

        $result = $this->service->getNotification($id, $userId);
        $this->assertNull($result);
    }

    public function testGetNotificationReturnsNotificationIfOwned(): void
    {
        $id = 99;
        $userId = '1';
        $notification = $this->createMock(Notification::class);
        $notification->method('getUserId')->willReturn($userId);

        $this->repo->expects($this->once())
            ->method('findById')
            ->with($id)
            ->willReturn($notification);

        $result = $this->service->getNotification($id, $userId);
        $this->assertSame($notification, $result);
    }

    public function testMarkAsReadReturnsNullIfNotFound(): void
    {
        $id = 99;
        $userId = '1';
        $this->repo->expects($this->once())
            ->method('findById')
            ->with($id)
            ->willReturn(null);

        $result = $this->service->markAsRead($id, $userId);
        $this->assertNull($result);
    }

    public function testMarkAsReadSavesWhenFound(): void
    {
        $id = 99;
        $userId = '1';
        $notification = $this->createMock(Notification::class);
        $notification->method('getUserId')->willReturn($userId);
        $notification->expects($this->once())->method('markAsRead');
        $this->repo->expects($this->once())
            ->method('findById')
            ->with($id)
            ->willReturn($notification);
        $this->repo->expects($this->once())
            ->method('save')
            ->with($notification);

        $result = $this->service->markAsRead($id, $userId);
        $this->assertSame($notification, $result);
    }

    public function testMarkAllAsReadMarksOnlyUnread(): void
    {
        $userId = '1';
        $n1 = $this->createMock(Notification::class);
        $n1->method('isRead')->willReturn(false);
        $n1->expects($this->once())->method('markAsRead');
        $n2 = $this->createMock(Notification::class);
        $n2->method('isRead')->willReturn(true);
        $n2->expects($this->never())->method('markAsRead');
        $n3 = $this->createMock(Notification::class);
        $n3->method('isRead')->willReturn(false);
        $n3->expects($this->once())->method('markAsRead');

        $this->repo->expects($this->once())
            ->method('findByUserId')
            ->with($userId, false)
            ->willReturn([$n1, $n2, $n3]);

        $this->repo->expects($this->exactly(2))
            ->method('save')
            ->with($this->logicalOr($n1, $n3));

        $count = $this->service->markAllAsRead($userId);
        $this->assertSame(2, $count);
    }

    public function testDeleteNotificationReturnsFalseIfNotFound(): void
    {
        $id = 99;
        $userId = '1';
        $this->repo->expects($this->once())
            ->method('findById')
            ->with($id)
            ->willReturn(null);

        $result = $this->service->deleteNotification($id, $userId);
        $this->assertFalse($result);
    }

    public function testDeleteNotificationDeletesWhenFound(): void
    {
        $id = 99;
        $userId = '1';
        $notification = $this->createMock(Notification::class);
        $notification->method('getUserId')->willReturn($userId);

        $this->repo->expects($this->once())
            ->method('findById')
            ->with($id)
            ->willReturn($notification);
        $this->repo->expects($this->once())
            ->method('delete')
            ->with($id)
            ->willReturn(true);

        $result = $this->service->deleteNotification($id, $userId);
        $this->assertTrue($result);
    }

    public function testGetPreferenceWhenNotFound(): void
    {
        $userId = '1';
        $type = 'email';
        $result = $this->service->getPreference($userId, $type);
        $this->assertNull($result);
    }

    public function testGetPreferenceWhenFound(): void
    {
        $userId = '1';
        $type = 'email';
        $pref = new NotificationPreference($userId, $type);
        $pref->setEmailEnabled(true);
        // inject into service's preferences array via reflection? Since preferences is private. We can use service method setPreference to add one first, then get.
        $this->service->setPreference($userId, $type, ['email' => true]);
        $found = $this->service->getPreference($userId, $type);
        $this->assertInstanceOf(NotificationPreference::class, $found);
        $this->assertTrue($found->isEmailEnabled());
    }

    public function testSetPreferenceCreatesNew(): void
    {
        $userId = '1';
        $type = 'push';
        $channels = ['push' => true, 'email' => false];
        // Since we cannot directly access preferences, we rely on setPreference to create and we can verify via getPreference after.
        $pref = $this->service->setPreference($userId, $type, $channels);
        $this->assertInstanceOf(NotificationPreference::class, $pref);
        $this->assertTrue($pref->isPushEnabled());
        $this->assertFalse($pref->isEmailEnabled());
    }

    public function testSetPreferenceUpdatesExisting(): void
    {
        $userId = '1';
        $type = 'sms';
        $this->service->setPreference($userId, $type, ['sms' => true]);
        $pref1 = $this->service->getPreference($userId, $type);
        $this->assertNotNull($pref1);
        $this->assertTrue($pref1->isSmsEnabled());

        // Update
        $pref2 = $this->service->setPreference($userId, $type, ['sms' => false]);
        $this->assertSame($pref1, $pref2); // same instance? Not guaranteed but same object
        $this->assertFalse($pref2->isSmsEnabled());
    }
}
