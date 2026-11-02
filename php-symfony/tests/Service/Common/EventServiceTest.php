<?php declare(strict_types=1);

namespace App\Tests\Service\Common;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Common\Event;
use App\Service\Common\EventService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[AllowMockObjectsWithoutExpectations]
class EventServiceTest extends TestCase
{
    private EntityManagerInterface $em;
    private EntityRepository $eventRepo;
    private LoggerInterface $logger;
    private EventService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->eventRepo = $this->createMock(EntityRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->em->method('getRepository')
            ->willReturnMap([
                [Event::class, $this->eventRepo],
            ]);
    }

    public function testRecordEvent(): void
    {
        $entityType = 'order';
        $entityId = 'order-123';
        $eventType = 'order.created';
        $data = ['total' => 100];
        $userId = 'user-1';

        $captured = null;
        $this->eventRepo->expects($this->never())->method('find');
        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (Event $e) use (&$captured, $entityType, $entityId, $eventType, $data, $userId) {
                $captured = $e;
                return $captured !== null
                    && $captured->getEntityType() === $entityType
                    && $captured->getEntityId() === $entityId
                    && $captured->getEventType() === $eventType
                    && $captured->getData() === $data
                    && $captured->getUserId() === $userId;
            }));
        $this->em->expects($this->once())->method('flush');

        $this->service = new EventService($this->em, $this->logger);
        $result = $this->service->record($entityType, $entityId, $eventType, $data, $userId);

        $this->assertInstanceOf(Event::class, $result);
        $this->assertSame($eventType, $result->getEventType());
        $this->assertNotNull($result->getId());
    }

    public function testRecordEventMinimal(): void
    {
        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $this->service = new EventService($this->em, $this->logger);
        $this->service = new EventService($this->em, $this->logger);
        $result = $this->service->record('product', 'prod-1', 'product.created');
        $this->assertInstanceOf(Event::class, $result);
    }

    public function testRecordEventInvalidEntityType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Entity type is required');

        $this->service = new EventService($this->em, $this->logger);
        $this->service->record('', 'id', 'type');
    }

    public function testRecordEventInvalidEntityId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Entity ID is required');

        $this->service = new EventService($this->em, $this->logger);
        $this->service->record('type', '', 'type');
    }

    public function testRecordEventInvalidEventType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Event type is required');

        $this->service = new EventService($this->em, $this->logger);
        $this->service->record('entity', 'id', '');
    }

    public function testRecordEventPersistenceException(): void
    {
        $this->eventRepo->expects($this->never())->method('find');
        $this->em->expects($this->once())->method('persist');
        $ormException = $this->createMock(ORMException::class);
        $this->em->expects($this->once())->method('flush')->willThrowException($ormException);

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Failed to record event', $this->arrayHasKey('error'));

        $this->service = new EventService($this->em, $this->logger);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to record event');
        $this->service->record('entity', 'id', 'type');
    }

    public function testGetEvent(): void
    {
        $id = 'event-1';
        $event = $this->createMock(Event::class);
        $this->eventRepo->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($event);

        $this->service = new EventService($this->em, $this->logger);
        $result = $this->service->getEvent($id);
        $this->assertSame($event, $result);
    }

    public function testGetForEntity(): void
    {
        $entityType = 'order';
        $entityId = 'order-1';
        $expected = [$this->createMock(Event::class)];

        $this->eventRepo->expects($this->once())
            ->method('findBy')
            ->with(
                ['entityType' => $entityType, 'entityId' => $entityId],
                ['createdAt' => 'ASC']
            )
            ->willReturn($expected);

        $this->service = new EventService($this->em, $this->logger);
        $result = $this->service->getEventsForEntity($entityType, $entityId);
        $this->assertCount(1, $result);
    }

    public function testGetForEntityDefaultPagination(): void
    {
        $this->eventRepo->expects($this->once())
            ->method('findBy')
            ->with(['entityType' => 'type', 'entityId' => 'id'], ['createdAt' => 'ASC'])
            ->willReturn([]);

        $this->service = new EventService($this->em, $this->logger);
        $result = $this->service->getEventsForEntity('type', 'id');
        $this->assertSame([], $result);
    }

    public function testGetByUser(): void
    {
        $userId = 'user-2';
        $expected = [$this->createMock(Event::class)];

        $this->eventRepo->expects($this->once())
            ->method('findBy')
            ->with(
                ['userId' => $userId],
                ['createdAt' => 'DESC']
            )
            ->willReturn($expected);

        $this->service = new EventService($this->em, $this->logger);
        $result = $this->service->getEventsByUser($userId);
        $this->assertCount(1, $result);
    }
}
