<?php declare(strict_types=1);

namespace App\Tests\Service\Common;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Common\EventStore;
use App\Repository\Common\EventStoreRepository;
use App\Service\Common\EventStoreService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[AllowMockObjectsWithoutExpectations]
class EventStoreServiceTest extends TestCase
{
    private EntityManagerInterface $em;
    private EventStoreRepository $eventRepo;
    private LoggerInterface $logger;
    private EventStoreService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->eventRepo = $this->createMock(EventStoreRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->em->method('getRepository')
            ->willReturnMap([
                [EventStore::class, $this->eventRepo],
            ]);
    }

    public function testAppendEvent(): void
    {
        $this->eventRepo->expects($this->never())->method('find');
        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $this->logger->expects($this->once())
            ->method('debug')
            ->with('Event appended', $this->arrayHasKey('event_id'));

        $this->service = new EventStoreService($this->em, $this->logger);
        $result = $this->service->appendEvent('user.created', ['userId' => '123'], 'agg-1', 'User', 1, ['source' => 'api']);

        $this->assertInstanceOf(EventStore::class, $result);
        $this->assertSame('user.created', $result->getEventType());
        $this->assertNotNull($result->getId());
    }

    public function testAppendEventMinimal(): void
    {
        $this->eventRepo->expects($this->never())->method('find');
        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $this->service = new EventStoreService($this->em, $this->logger);
        $result = $this->service->appendEvent('system.health');
        $this->assertInstanceOf(EventStore::class, $result);
        $this->assertSame('system.health', $result->getEventType());
    }

    public function testGetEvent(): void
    {
        $event = $this->createMock(EventStore::class);
        $this->eventRepo->expects($this->once())
            ->method('find')
            ->with('event-1')
            ->willReturn($event);

        $this->service = new EventStoreService($this->em, $this->logger);
        $result = $this->service->getEvent('event-1');
        $this->assertSame($event, $result);
    }

    public function testGetEventsForAggregate(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $qb->method('where')->willReturnSelf();
        $qb->method('andWhere')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('orderBy')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);
        $query->method('getResult')->willReturn([$this->createMock(EventStore::class)]);

        $this->eventRepo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('e')
            ->willReturn($qb);

        $this->service = new EventStoreService($this->em, $this->logger);
        $result = $this->service->getEventsForAggregate('agg-2', 'Order');
        $this->assertCount(1, $result);
    }

    public function testGetEventsForAggregateWithoutType(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $qb->expects($this->once())->method('where')->willReturnSelf();
        $qb->expects($this->never())->method('andWhere');
        $qb->expects($this->any())->method('setParameter')->willReturnSelf();
        $qb->expects($this->once())->method('orderBy')->willReturnSelf();
        $qb->expects($this->once())->method('getQuery')->willReturn($query);
        $query->expects($this->once())->method('getResult')->willReturn([]);

        $this->eventRepo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('e')
            ->willReturn($qb);

        $this->service = new EventStoreService($this->em, $this->logger);
        $result = $this->service->getEventsForAggregate('agg-3');
        $this->assertSame([], $result);
    }


    public function testDeleteOldEvents(): void
    {
        $before = new \DateTime('-1 month');
        $eventType = 'audit.event';
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $qb->expects($this->once())->method('delete')->willReturnSelf();
        $qb->expects($this->once())->method('where')->willReturnSelf();
        $qb->expects($this->never())->method('andWhere');
        $qb->expects($this->once())->method('setParameter')->willReturnSelf();
        $qb->expects($this->once())->method('getQuery')->willReturn($query);
        $query->expects($this->once())->method('execute')->willReturn(5);

        $this->eventRepo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('e')
            ->willReturn($qb);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Old events deleted');

        $this->service = new EventStoreService($this->em, $this->logger);
        $result = $this->service->deleteOldEvents($before, $eventType);
        $this->assertSame(5, $result);
    }

    public function testDeleteOldEventsWithoutType(): void
    {
        $before = new \DateTime('-1 year');
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $qb->expects($this->once())->method('delete')->willReturnSelf();
        $qb->expects($this->once())->method('where')->willReturnSelf();
        $qb->expects($this->never())->method('andWhere');
        $qb->expects($this->any())->method('setParameter')->willReturnSelf();
        $qb->expects($this->once())->method('getQuery')->willReturn($query);
        $query->expects($this->once())->method('execute')->willReturn(0);

        $this->eventRepo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('e')
            ->willReturn($qb);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Old events deleted');

        $this->service = new EventStoreService($this->em, $this->logger);
        $result = $this->service->deleteOldEvents($before);
        $this->assertSame(0, $result);
    }

    public function testGetEventsByDateRangeOnlyFromTo(): void
    {
        $from = new \DateTime('-1 hour');
        $to = new \DateTime();

        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $qb->expects($this->once())->method('where')->with('e.createdAt >= :start')->willReturnSelf();
        $qb->expects($this->once())->method('andWhere')->willReturnSelf();
        $qb->expects($this->atLeast(2))->method('setParameter')->willReturnSelf();
        $qb->expects($this->once())->method('orderBy')->with('e.createdAt', 'ASC')->willReturnSelf();
        $qb->expects($this->never())->method('setMaxResults');
        $qb->expects($this->once())->method('getQuery')->willReturn($query);
        $query->expects($this->once())->method('getResult')->willReturn([]);

        $this->eventRepo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('e')
            ->willReturn($qb);

        $this->service = new EventStoreService($this->em, $this->logger);
        $result = $this->service->getEventsByDateRange($from, $to);
        $this->assertSame([], $result);
    }
}
