<?php declare(strict_types=1);

namespace App\Tests\Service\Common;

use App\Entity\Common\EventStore;
use App\Service\Common\EventStoreService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class EventStoreServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private LoggerInterface&MockObject $logger;
    private EventStoreService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->service = new EventStoreService($this->em, $this->logger);
    }

    public function testAppendEventSuccess(): void
    {
        $eventType = 'order.created';
        $payload = ['order_id' => '123', 'amount' => 100];
        $aggregateId = 'order_123';
        $aggregateType = 'order';
        $version = 1;
        $metadata = ['source' => 'web'];

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->never())
            ->method('find');

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(EventStore::class)
            ->willReturn($repo);
        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (EventStore $e) use (
                $eventType, $payload, $aggregateId, $aggregateType, $version, $metadata
            ) {
                return $e->getEventType() === $eventType
                    && $e->getPayload() === $payload
                    && $e->getAggregateId() === $aggregateId
                    && $e->getAggregateType() === $aggregateType
                    && $e->getVersion() === $version
                    && $e->getMetadata() === $metadata;
            }));
        $this->em->expects($this->once())
            ->method('flush');

        $this->logger->expects($this->once())
            ->method('debug')
            ->with('Event appended', $this->anything());

        $result = $this->service->appendEvent($eventType, $payload, $aggregateId, $aggregateType, $version, $metadata);
        $this->assertInstanceOf(EventStore::class, $result);
        $this->assertSame($eventType, $result->getEventType());
        $this->assertSame($payload, $result->getPayload());
    }

    public function testAppendEventMinimal(): void
    {
        $eventType = 'user.login';
        $repo = $this->createMock(ObjectRepository::class);
        $this->em->method('getRepository')->willReturn($repo);
        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $result = $this->service->appendEvent($eventType);
        $this->assertInstanceOf(EventStore::class, $result);
        $this->assertSame($eventType, $result->getEventType());
    }

    public function testGetEvent(): void
    {
        $id = 'event_123';
        $event = new EventStore($id, 'type');

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($event);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(EventStore::class)
            ->willReturn($repo);

        $result = $this->service->getEvent($id);
        $this->assertSame($event, $result);
    }

    public function testGetEventsForAggregate(): void
    {
        $aggregateId = 'order_123';
        $aggregateType = 'order';
        $event1 = new EventStore('id1', 'type1');
        $event1->setAggregateId($aggregateId);
        $event1->setAggregateType($aggregateType);
        $event2 = new EventStore('id2', 'type2');
        $event2->setAggregateId($aggregateId);
        $event2->setAggregateType($aggregateType);

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('where')
            ->with('e.aggregateId = :aggregateId')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('andWhere')
            ->with('e.aggregateType = :aggregateType')
            ->willReturnSelf();
        $qb->expects($this->exactly(2))
            ->method('setParameter')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('orderBy')
            ->with('e.createdAt', 'ASC')
            ->willReturnSelf();
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([$event1, $event2]);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('e')
            ->willReturn($qb);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(EventStore::class)
            ->willReturn($repo);

        $result = $this->service->getEventsForAggregate($aggregateId, $aggregateType);
        $this->assertCount(2, $result);
    }

    public function testGetEventsForAggregateWithoutType(): void
    {
        $aggregateId = 'order_123';
        $event = new EventStore('id1', 'type1');
        $event->setAggregateId($aggregateId);

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('where')
            ->with('e.aggregateId = :aggregateId')
            ->willReturnSelf();
        $qb->expects($this->never())
            ->method('andWhere');
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('aggregateId', $aggregateId)
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('orderBy')
            ->with('e.createdAt', 'ASC')
            ->willReturnSelf();
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([$event]);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('e')
            ->willReturn($qb);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getEventsForAggregate($aggregateId);
        $this->assertCount(1, $result);
    }

    public function testGetEventsByType(): void
    {
        $eventType = 'order.created';
        $event = new EventStore('id', $eventType);

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('where')
            ->with('e.eventType = :eventType')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('eventType', $eventType)
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('orderBy')
            ->with('e.createdAt', 'DESC')
            ->willReturnSelf();
        $qb->expects($this->never())
            ->method('setMaxResults');
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([$event]);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('e')
            ->willReturn($qb);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(EventStore::class)
            ->willReturn($repo);

        $result = $this->service->getEventsByType($eventType);
        $this->assertCount(1, $result);
    }

    public function testGetEventsByTypeWithLimit(): void
    {
        $eventType = 'order.updated';
        $limit = 5;
        $event = new EventStore('id', $eventType);

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('setMaxResults')
            ->with($limit)
            ->willReturnSelf();

        // We'll reuse the same mock expectations from testGetEventsByType and add limit
        $qb->expects($this->once())
            ->method('where')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('orderBy')
            ->willReturnSelf();
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([$event]);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($qb);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $result = $this->service->getEventsByType($eventType, $limit);
        $this->assertCount(1, $result);
    }

    public function testGetEventsByDateRange(): void
    {
        $from = new \DateTime('-1 day');
        $to = new \DateTime();
        $eventType = 'order.created';
        $event = new EventStore('id', $eventType);

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('where')
            ->with('e.createdAt >= :from')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('andWhere')
            ->with('e.createdAt <= :to')
            ->willReturnSelf();
        $qb->expects($this->exactly(3))
            ->method('setParameter')
            ->willReturnSelf();
        $qb->expects($this->never())
            ->method('setMaxResults');
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([$event]);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('e')
            ->willReturn($qb);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(EventStore::class)
            ->willReturn($repo);

        $result = $this->service->getEventsByDateRange($from, $to, $eventType);
        $this->assertCount(1, $result);
    }

    public function testDeleteOldEvents(): void
    {
        $before = new \DateTime('-30 days');
        $affected = 5;

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('delete')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('where')
            ->with('e.createdAt < :before')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('before', $before)
            ->willReturnSelf();
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->expects($this->once())
            ->method('execute')
            ->willReturn($affected);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('e')
            ->willReturn($qb);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(EventStore::class)
            ->willReturn($repo);

        $result = $this->service->deleteOldEvents($before);
        $this->assertSame($affected, $result);
    }

    public function testCountByAggregate(): void
    {
        $aggregateId = 'order_123';
        $count = 3;

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('select')
            ->with('COUNT(e.id)')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('where')
            ->with('e.aggregateId = :aggregateId')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('aggregateId', $aggregateId)
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
            ->with('e')
            ->willReturn($qb);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(EventStore::class)
            ->willReturn($repo);

        $result = $this->service->countByAggregate($aggregateId);
        $this->assertSame($count, $result);
    }

    public function testGetLatestEvents(): void
    {
        $limit = 10;
        $event1 = new EventStore('id1', 'type1');
        $event2 = new EventStore('id2', 'type2');

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('orderBy')
            ->with('e.createdAt', 'DESC')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setMaxResults')
            ->with($limit)
            ->willReturnSelf();
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([$event1, $event2]);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('e')
            ->willReturn($qb);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(EventStore::class)
            ->willReturn($repo);

        $result = $this->service->getLatestEvents($limit);
        $this->assertCount(2, $result);
    }
}
