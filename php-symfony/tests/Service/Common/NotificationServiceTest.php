<?php declare(strict_types=1);

namespace App\Tests\Service\Common;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Common\Notification;
use App\Repository\Common\NotificationRepository;
use App\Service\Common\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[AllowMockObjectsWithoutExpectations]
class NotificationServiceTest extends TestCase
{
    private EntityManagerInterface $em;
    private NotificationRepository $repository;
    private LoggerInterface $logger;
    private NotificationService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(NotificationRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->em->method('getRepository')
            ->with(Notification::class)
            ->willReturn($this->repository);
    }

    public function testValidateNotificationThrowsWhenEmptyUserId(): void
    {
        $this->service = new NotificationService($this->em, $this->logger);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('User ID is required');
        // Call private method via reflection
        $method = new \ReflectionMethod($this->service, 'validateNotification');
        $method->invoke($this->service, '', 'type', 'title', 'body');
    }

    public function testValidateNotificationThrowsWhenEmptyType(): void
    {
        $this->service = new NotificationService($this->em, $this->logger);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Notification type is required');
        $method = new \ReflectionMethod($this->service, 'validateNotification');
        $method->invoke($this->service, 'user1', '', 'title', 'body');
    }

    public function testValidateNotificationThrowsWhenEmptyTitle(): void
    {
        $this->service = new NotificationService($this->em, $this->logger);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Notification title is required');
        $method = new \ReflectionMethod($this->service, 'validateNotification');
        $method->invoke($this->service, 'user1', 'type', '', 'body');
    }

    public function testValidateNotificationThrowsWhenEmptyBody(): void
    {
        $this->service = new NotificationService($this->em, $this->logger);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Notification body is required');
        $method = new \ReflectionMethod($this->service, 'validateNotification');
        $method->invoke($this->service, 'user1', 'type', 'title', '');
    }

    public function testValidateNotificationAcceptsNonEmpty(): void
    {
        $this->service = new NotificationService($this->em, $this->logger);
        $method = new \ReflectionMethod($this->service, 'validateNotification');
        // Should not throw
        $method->invoke($this->service, 'user1', 'type', 'title', 'body');
        $this->addToAssertionCount(1);
    }

    public function testCreateSuccess(): void
    {
        $userId = 'user-1';
        $type = 'info';
        $title = 'Test Title';
        $body = 'Test Body';
        $data = ['key' => 'value'];

        // Spy on persist/flush
        $this->em->expects($this->once())->method('persist')->with($this->callback(function (Notification $n) use ($userId, $type, $title, $body, $data) {
            return $n->getUserId() === $userId
                && $n->getType() === $type
                && $n->getTitle() === $title
                && $n->getBody() === $body
                && $n->getData() === $data;
        }));
        $this->em->expects($this->once())->method('flush');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Notification created', $this->arrayHasKey('id'));

        $this->service = new NotificationService($this->em, $this->logger);
        $result = $this->service->create($userId, $type, $title, $body, $data);

        $this->assertInstanceOf(Notification::class, $result);
        $this->assertSame($userId, $result->getUserId());
        $this->assertSame($type, $result->getType());
        $this->assertSame($title, $result->getTitle());
        $this->assertSame($body, $result->getBody());
        $this->assertSame($data, $result->getData());
    }

    public function testCreateThrowsOnOrmException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to create notification');

        $userId = 'user-2';
        $ormException = $this->createMock(\Doctrine\ORM\Exception\ORMException::class);

        $this->em->expects($this->once())->method('persist')->with($this->isInstanceOf(Notification::class));
        $this->em->expects($this->once())->method('flush')->willThrowException($ormException);
        $this->logger->expects($this->once())
            ->method('error')
            ->with('Failed to create notification', $this->arrayHasKey('error'));

        $this->service = new NotificationService($this->em, $this->logger);
        $this->service->create($userId, 'type', 'title', 'body');
    }

    public function testGet(): void
    {
        $id = 'notif-id';
        $notification = $this->createMock(Notification::class);
        $this->repository->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($notification);

        $this->service = new NotificationService($this->em, $this->logger);
        $result = $this->service->get($id);
        $this->assertSame($notification, $result);
    }

    public function testCountUnread(): void
    {
        $userId = 'user-5';
        $expectedCount = 5;

        $this->repository->expects($this->once())
            ->method('count')
            ->with(['userId' => $userId, 'readAt' => null])
            ->willReturn($expectedCount);

        $this->service = new NotificationService($this->em, $this->logger);
        $result = $this->service->countUnread($userId);
        $this->assertSame($expectedCount, $result);
    }

    public function testMarkAsReadSuccess(): void
    {
        $id = 'notif-id';
        $notification = $this->createMock(Notification::class);
        $notification->expects($this->once())->method('setReadAt')->with($this->isInstanceOf(\DateTime::class))->willReturnSelf();

        $this->repository->expects($this->once())->method('find')->with($id)->willReturn($notification);
        $this->em->expects($this->once())->method('flush');
        $this->logger->expects($this->once())
            ->method('info')
            ->with('Notification marked as read', ['id' => $id]);

        $this->service = new NotificationService($this->em, $this->logger);
        $result = $this->service->markAsRead($id);
        $this->assertTrue($result);
    }

    public function testMarkAsReadNotFound(): void
    {
        $id = 'missing';
        $this->repository->expects($this->once())->method('find')->with($id)->willReturn(null);
        $this->em->expects($this->never())->method('flush');

        $this->service = new NotificationService($this->em, $this->logger);
        $result = $this->service->markAsRead($id);
        $this->assertFalse($result);
    }

    public function testMarkAsReadAlreadyRead(): void
    {
        $id = 'notif-id';
        $notification = $this->createMock(Notification::class);
        $notification->expects($this->once())->method('setReadAt')->with($this->isInstanceOf(\DateTime::class))->willReturnSelf();

        $this->repository->expects($this->once())->method('find')->with($id)->willReturn($notification);
        $this->em->expects($this->once())->method('flush');
        $this->logger->expects($this->once())
            ->method('info')
            ->with('Notification marked as read', ['id' => $id]);

        $this->service = new NotificationService($this->em, $this->logger);
        $result = $this->service->markAsRead($id);
        $this->assertTrue($result);
    }


    public function testDeleteSuccess(): void
    {
        $id = 'notif-id';
        $notification = $this->createMock(Notification::class);

        $this->repository->expects($this->once())->method('find')->with($id)->willReturn($notification);
        $this->em->expects($this->once())->method('remove')->with($notification);
        $this->em->expects($this->once())->method('flush');
        $this->logger->expects($this->once())
            ->method('info')
            ->with('Notification deleted', ['id' => $id]);

        $this->service = new NotificationService($this->em, $this->logger);
        $result = $this->service->delete($id);
        $this->assertTrue($result);
    }

    public function testDeleteNotFound(): void
    {
        $id = 'missing';
        $this->repository->expects($this->once())->method('find')->with($id)->willReturn(null);
        $this->em->expects($this->never())->method('remove');
        $this->em->expects($this->never())->method('flush');

        $this->service = new NotificationService($this->em, $this->logger);
        $result = $this->service->delete($id);
        $this->assertFalse($result);
    }
}

