<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notification;

use App\Controller\Notification\NotificationController;
use App\Entity\Common\Notification;
use App\Repository\Common\NotificationRepository;
use App\Service\Security\OwnershipChecker;
use App\Tests\Unit\TestUser;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class NotificationControllerTest extends TestCase
{
    private NotificationRepository $notificationRepositoryMock;
    private OwnershipChecker $ownershipCheckerMock;
    private NotificationController $controller;

    protected function setUp(): void
    {
        $this->notificationRepositoryMock = $this->createMock(NotificationRepository::class);
        $this->ownershipCheckerMock = $this->createMock(OwnershipChecker::class);
        $this->controller = new NotificationController(
            $this->notificationRepositoryMock,
            $this->ownershipCheckerMock
        );
    }

    #[Test]
    public function it_lists_user_notifications(): void
    {
        $userId = 'user-1';
        $notifications = [
            $this->createNotification('n1', $userId, 'Welcome', 'Welcome message'),
            $this->createNotification('n2', $userId, 'Update', 'Update message'),
        ];

        $this->notificationRepositoryMock->method('findByUserId')->with($userId)->willReturn($notifications);

        $user = new TestUser($userId, ['ROLE_USER']);
        $this->setAuthenticatedUser($user);

        $request = Request::create('/api/notification', 'GET', ['page' => '1', 'limit' => '20']);
        $response = $this->controller->list($request);

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertCount(2, $data['data']);
        $this->assertSame(1, $data['pagination']['current_page']);
        $this->assertSame(2, $data['pagination']['total_items']);
    }

    #[Test]
    public function it_paginates_notifications(): void
    {
        $userId = 'user-1';
        $notifications = [
            $this->createNotification('n1', $userId, 'N1', 'Message 1'),
            $this->createNotification('n2', $userId, 'N2', 'Message 2'),
            $this->createNotification('n3', $userId, 'N3', 'Message 3'),
        ];

        $this->notificationRepositoryMock->method('findByUserId')->with($userId)->willReturn($notifications);

        $user = new TestUser($userId, ['ROLE_USER']);
        $this->setAuthenticatedUser($user);

        $request = Request::create('/api/notification', 'GET', ['page' => '2', 'limit' => '1']);
        $response = $this->controller->list($request);

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertCount(1, $data['data']);
        $this->assertSame(2, $data['pagination']['current_page']);
        $this->assertSame(3, $data['pagination']['total_pages']);
    }

    #[Test]
    public function it_marks_notification_as_read(): void
    {
        $userId = 'user-1';
        $notification = $this->createNotification('n1', $userId, 'Welcome', 'Welcome message');

        $this->notificationRepositoryMock->method('find')->with('n1')->willReturn($notification);
        $this->ownershipCheckerMock->method('isOwner')->willReturn(true);
        $this->notificationRepositoryMock->expects($this->once())->method('markAsRead')->with('n1');

        $user = new TestUser($userId, ['ROLE_USER']);
        $this->setAuthenticatedUser($user);

        $response = $this->controller->markRead('n1');
        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['read']);
    }

    #[Test]
    public function it_denies_marking_other_user_notification(): void
    {
        $notification = $this->createNotification('n1', 'user-other', 'Welcome', 'Welcome message');

        $this->notificationRepositoryMock->method('find')->with('n1')->willReturn($notification);
        $this->ownershipCheckerMock->method('isOwner')->willReturn(false);

        $user = new TestUser('user-1', ['ROLE_USER']);
        $this->setAuthenticatedUser($user);

        $response = $this->controller->markRead('n1');
        $this->assertSame(403, $response->getStatusCode());
    }

    #[Test]
    public function it_denies_marking_nonexistent_notification(): void
    {
        $this->notificationRepositoryMock->method('find')->with('nonexistent')->willReturn(null);

        $user = new TestUser('user-1', ['ROLE_USER']);
        $this->setAuthenticatedUser($user);

        $response = $this->controller->markRead('nonexistent');
        $this->assertSame(403, $response->getStatusCode());
    }

    private function createNotification(string $id, string $userId, string $title, string $body): Notification
    {
        $notification = $this->createMock(Notification::class);
        $notification->method('getId')->willReturn($id);
        $notification->method('getUserId')->willReturn($userId);
        $notification->method('getTitle')->willReturn($title);
        $notification->method('getBody')->willReturn($body);
        $notification->method('isRead')->willReturn(false);
        return $notification;
    }

    private function setAuthenticatedUser(TestUser $user): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $property = $reflection->getProperty('testUser');
        $property->setAccessible(true);
        $property->setValue($this->controller, $user);
    }
}
