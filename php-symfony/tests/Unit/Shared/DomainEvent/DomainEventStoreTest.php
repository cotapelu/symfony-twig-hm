<?php declare(strict_types=1);

namespace App\Tests\Unit\Shared\DomainEvent;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Shared\DomainEvent\DomainEventStore;
use App\Shared\Entity\StoredDomainEvent;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class DomainEventStoreTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private QueryBuilder&MockObject $qb;
    private Query&MockObject $query;
    private DomainEventStore $store;

    private function createDomainEventMock(string $type, array $data, ?\DateTime $occurredAt = null): MockObject
    {
        $event = $this->createMock(\App\Shared\DomainEvent\DomainEvent::class);
        $event->method('eventType')->willReturn($type);
        $event->method('toArray')->willReturn($data + ['occurredAt' => $occurredAt ?? new \DateTime()]);
        return $event;
    }

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->qb = $this->createMock(QueryBuilder::class);
        $this->query = $this->createMock(Query::class);
        $this->store = new DomainEventStore($this->em);
    }

    public function testSavePersistsStoredEventAndFlushes(): void
    {
        $aggregateId = 'agg_123';
        $aggregateType = 'Order';
        $eventType = 'OrderCreated';
        $data = ['aggregateId' => $aggregateId, 'aggregateType' => $aggregateType, 'amount' => 100];

        $domainEvent = $this->createDomainEventMock($eventType, $data);

        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (StoredDomainEvent $stored) use ($eventType, $aggregateId, $aggregateType) {
                $this->assertInstanceOf(StoredDomainEvent::class, $stored);
                $this->assertSame($eventType, $stored->getEventType());
                $this->assertSame($aggregateId, $stored->getAggregateId());
                $this->assertSame($aggregateType, $stored->getAggregateType());
                return true;
            }))
            ->willReturnSelf();
        $this->em->expects($this->once())
            ->method('flush')
            ->willReturnSelf();

        $this->store->save($domainEvent);
        $this->addToAssertionCount(1);
    }

    public function testGetEventsForAggregateCallsFindByOnRepository(): void
    {
        $aggregateId = 'agg_99';
        $aggregateType = 'User';

        $entityRepo = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $expectedEvents = [$this->createMock(StoredDomainEvent::class), $this->createMock(StoredDomainEvent::class)];

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(StoredDomainEvent::class)
            ->willReturn($entityRepo);

        $entityRepo->expects($this->once())
            ->method('findBy')
            ->with(
                ['aggregateId' => $aggregateId, 'aggregateType' => $aggregateType],
                ['occurredAt' => 'ASC']
            )
            ->willReturn($expectedEvents);

        $result = $this->store->getEventsForAggregate($aggregateId, $aggregateType);
        $this->assertSame($expectedEvents, $result);
    }

    public function testGetAllEventsSinceReturnsEmptyWhenEventNotFound(): void
    {
        $sinceEventId = 'evt_missing';

        $entityRepo = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(StoredDomainEvent::class)
            ->willReturn($entityRepo);

        $entityRepo->expects($this->once())
            ->method('find')
            ->with($sinceEventId)
            ->willReturn(null);

        $result = $this->store->getAllEventsSince($sinceEventId);
        $this->assertEmpty($result);
    }

    public function testGetAllEventsSinceBuildsQueryAndReturnsResults(): void
    {
        $sinceEventId = 'evt_prev';
        $sinceOccurredAt = new \DateTime('2024-01-01 00:00:00');

        $sinceEvent = $this->createMock(StoredDomainEvent::class);
        $sinceEvent->method('getOccurredAt')->willReturn($sinceOccurredAt);

        $expected = [$this->createMock(StoredDomainEvent::class), $this->createMock(StoredDomainEvent::class)];

        $entityRepo = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->em->expects($this->exactly(2))
            ->method('getRepository')
            ->with(StoredDomainEvent::class)
            ->willReturn($entityRepo);

        $entityRepo->expects($this->once())
            ->method('find')
            ->with($sinceEventId)
            ->willReturn($sinceEvent);
        $entityRepo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('e')
            ->willReturn($this->qb);

        $this->qb->expects($this->once())
            ->method('where')
            ->with('e.occurredAt > :since')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('setParameter')
            ->with('since', $sinceOccurredAt)
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('orderBy')
            ->with('e.occurredAt', 'ASC')
            ->willReturnSelf();
        $this->qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($this->query);
        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn($expected);

        $result = $this->store->getAllEventsSince($sinceEventId);
        $this->assertSame($expected, $result);
    }
}
