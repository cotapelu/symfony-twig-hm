<?php declare(strict_types=1);

namespace App\Tests\Service\Common;

use App\Entity\Common\Notification;
use App\Service\Common\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class NotificationServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private LoggerInterface&MockObject $logger;
    private NotificationService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->service = new NotificationService($this->em, $this->logger);
    }

    public function testCreateSuccess(): void
    {
        $userId = 'user123';
        $type = 'info';
        $title = 'Test';
        $body = 'Test body';
        $id = '123e4567-e89b-12d3-a456-426614174000';

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->never())
            ->method('find');

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(Notification::class)
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (Notification $n) use ($userId, $type, $title, $body, $id) {
                return $n->getUserId() === $userId && $n->getType() === $type && $n->getTitle() === $title && $n->getBody() === $body && $n->getId() === $id;
            }));
        $this->em->expects($this->once())
            ->method('flush');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Notification created', ['id' => $id]);

        // Use Reflection to trick ID generation? Actually we can't control ID generation easily. We'll assume any UUID is fine.

        $result = $this->service->create($userId, $type, $title, $body);
        $this->assertInstanceOf(Notification::class, $result);
    }

    public function testCreateFailsValidation(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->create('', 'type', 'title', 'body');
    }

    public function testGetSuccess(): void
    {
        $id = '123e4567-e89b-12d3-a456-426614174000';
        $notification = new Notification($id, 'user', 'type', 'title', 'body');

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($notification);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(Notification::class)
            ->willReturn($repo);

        $result = $this->service->get($id);
        $this->assertSame($notification, $result);
    }

    public function testMarkAsReadSuccess(): void
    {
        $id = '123e4567-e89b-12d3-a456-426614174000';
        $notification = new Notification($id, 'user', 'type', 'title', 'body');

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($notification);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('flush');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Notification marked read', ['id' => $id]);

        $result = $this->service->markAsRead($id);
        $this->assertSame($notification, $result);
        $this->assertNotNull($notification->getReadAt());
    }

    public function testCountUnread(): void
    {
        $userId = 'user123';
        $count = 5;

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->any())
            ->method('where')
            ->willReturnSelf();
        $qb->expects($this->any())
            ->method('andWhere')
            ->willReturnSelf();
        $qb->expects($this->any())
            ->method('setParameter')
            ->willReturnSelf();
        $qb->expects($this->any())
            ->method('select')
            ->with('COUNT(n.id)')
            ->willReturnSelf();
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn((string) $count);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('n')
            ->willReturn($qb);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(Notification::class)
            ->willReturn($repo);

        $result = $this->service->countUnread($userId);
        $this->assertSame($count, $result);
    }
}
