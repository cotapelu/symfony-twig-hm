<?php declare(strict_types=1);

namespace App\Tests\Unit\Queue\Repository;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Queue\Domain\Entity\QueueTicket;
use App\Queue\Infrastructure\Repository\QueueTicketRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class QueueTicketRepositoryTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private QueueTicketRepository $repo;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repo = $this->getMockBuilder(QueueTicketRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getEntityManager', 'find', 'createQueryBuilder'])
            ->getMock();
        $this->repo->method('getEntityManager')->willReturn($this->em);
    }

    public function testFindById(): void
    {
        $id = 'ticket-123';
        $ticket = $this->createMock(QueueTicket::class);
        $this->repo->method('find')->with($id)->willReturn($ticket);
        $result = $this->repo->findById($id);
        $this->assertSame($ticket, $result);
    }

    public function testFindByTicketNumber(): void
    {
        $number = 'GEN-0001';
        $ticket = $this->createMock(QueueTicket::class);
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $this->repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('t')
            ->willReturn($qb);
        $qb->expects($this->once())
            ->method('where')
            ->with('t.ticketNumber = :ticketNumber')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('ticketNumber', $number)
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn($ticket);

        $result = $this->repo->findByTicketNumber($number);
        $this->assertSame($ticket, $result);
    }

    public function testFindByQueueWithoutStatus(): void
    {
        $queueId = 'queue-abc';
        $ticket = $this->createMock(QueueTicket::class);
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $this->repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('t')
            ->willReturn($qb);
        $qb->expects($this->once())
            ->method('innerJoin')
            ->with('t.queue', 'q')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('where')
            ->with('q.id = :queueId')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('queueId', $queueId)
            ->willReturnSelf();
        $qb->expects($this->never())
            ->method('andWhere');
        $qb->expects($this->once())
            ->method('orderBy')
            ->with('t.createdAt', 'DESC')
            ->willReturnSelf();
        $qb->expects($this->never())
            ->method('setMaxResults');
        $qb->expects($this->never())
            ->method('setFirstResult');
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([$ticket]);

        $result = $this->repo->findByQueue($queueId);
        $this->assertCount(1, $result);
    }

    public function testFindByQueueWithStatus(): void
    {
        $queueId = 'q-1';
        $status = 'waiting';
        $ticket = $this->createMock(QueueTicket::class);
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $this->repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('t')
            ->willReturn($qb);
        $qb->expects($this->once())
            ->method('innerJoin')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('where')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('andWhere')
            ->with('t.status = :status')
            ->willReturnSelf();
        $qb->expects($this->exactly(2))
            ->method('setParameter')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('orderBy')
            ->willReturnSelf();
        $qb->expects($this->never())
            ->method('setMaxResults');
        $qb->expects($this->never())
            ->method('setFirstResult');
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([$ticket]);

        $result = $this->repo->findByQueue($queueId, $status);
        $this->assertCount(1, $result);
    }

    public function testFindByQueueWithLimitAndOffset(): void
    {
        $queueId = 'q-limit';
        $status = 'called';
        $ticket = $this->createMock(QueueTicket::class);
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $this->repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('t')
            ->willReturn($qb);
        $qb->expects($this->once())->method('innerJoin')->willReturnSelf();
        $qb->expects($this->once())->method('where')->willReturnSelf();
        $qb->expects($this->once())->method('andWhere')->willReturnSelf();
        $qb->expects($this->exactly(2))
            ->method('setParameter')
            ->willReturnSelf();
        $qb->expects($this->once())->method('orderBy')->willReturnSelf();
        $qb->expects($this->once())
            ->method('setMaxResults')
            ->with(5)
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setFirstResult')
            ->with(10)
            ->willReturnSelf();
        $qb->expects($this->once())->method('getQuery')->willReturn($query);
        $query->expects($this->once())->method('getResult')->willReturn([$ticket]);

        $result = $this->repo->findByQueue($queueId, $status, 5, 10);
        $this->assertCount(1, $result);
    }

    public function testFindByCitizen(): void
    {
        $citizenId = 'cit-123';
        $ticket = $this->createMock(QueueTicket::class);
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $this->repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('t')
            ->willReturn($qb);
        $qb->expects($this->once())
            ->method('innerJoin')
            ->with('t.citizen', 'c')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('where')
            ->with('c.id = :citizenId')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('citizenId', $citizenId)
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('orderBy')
            ->with('t.createdAt', 'DESC')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([$ticket]);

        $result = $this->repo->findByCitizen($citizenId);
        $this->assertCount(1, $result);
    }

    public function testFindByCitizenPhone(): void
    {
        $phone = '0123456789';
        $ticket = $this->createMock(QueueTicket::class);
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $this->repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('t')
            ->willReturn($qb);
        $qb->expects($this->once())
            ->method('where')
            ->with('t.citizenPhone = :phone')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('phone', $phone)
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('orderBy')
            ->with('t.createdAt', 'DESC')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([$ticket]);

        $result = $this->repo->findByCitizenPhone($phone);
        $this->assertCount(1, $result);
    }

    public function testFindWaitingTickets(): void
    {
        $queueId = 'q-wait';
        $ticket = $this->createMock(QueueTicket::class);
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $this->repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('t')
            ->willReturn($qb);
        $qb->expects($this->once())
            ->method('innerJoin')
            ->with('t.queue', 'q')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('where')
            ->with('q.id = :queueId')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('andWhere')
            ->with('t.status = :status')
            ->willReturnSelf();
        $qb->expects($this->exactly(2))
            ->method('setParameter')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('orderBy')
            ->with('t.priority', 'DESC')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('addOrderBy')
            ->with('t.createdAt', 'ASC')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([$ticket]);

        $result = $this->repo->findWaitingTickets($queueId);
        $this->assertCount(1, $result);
    }

    public function testFindCalledTickets(): void
    {
        $queueId = 'q-called';
        $ticket = $this->createMock(QueueTicket::class);
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $this->repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('t')
            ->willReturn($qb);
        $qb->expects($this->once())
            ->method('innerJoin')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('where')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('andWhere')
            ->willReturnSelf();
        $qb->expects($this->exactly(2))
            ->method('setParameter')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('orderBy')
            ->with('t.calledAt', 'DESC')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([$ticket]);

        $result = $this->repo->findCalledTickets($queueId);
        $this->assertCount(1, $result);
    }

    public function testFindNextWaitingTicket(): void
    {
        $queueId = 'q-next';
        $ticket = $this->createMock(QueueTicket::class);
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $this->repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('t')
            ->willReturn($qb);
        $qb->expects($this->once())
            ->method('innerJoin')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('where')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('andWhere')
            ->willReturnSelf();
        $qb->expects($this->exactly(2))
            ->method('setParameter')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('orderBy')
            ->with('t.priority', 'DESC')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('addOrderBy')
            ->with('t.createdAt', 'ASC')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setMaxResults')
            ->with(1)
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn($ticket);

        $result = $this->repo->findNextWaitingTicket($queueId);
        $this->assertSame($ticket, $result);
    }

    public function testCountWaitingTickets(): void
    {
        $queueId = 'q-count';
        $count = 5;
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $this->repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('t')
            ->willReturn($qb);
        $qb->expects($this->once())
            ->method('select')
            ->with('COUNT(t.id)')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('innerJoin')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('where')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('andWhere')
            ->willReturnSelf();
        $qb->expects($this->exactly(2))
            ->method('setParameter')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn((string)$count);

        $result = $this->repo->countWaitingTickets($queueId);
        $this->assertSame($count, $result);
    }

    public function testCountCompletedTicketsWithoutDateRange(): void
    {
        $queueId = 'q-comp';
        $count = 10;
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $this->repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('t')
            ->willReturn($qb);
        $qb->expects($this->once())
            ->method('select')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('innerJoin')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('where')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('andWhere')
            ->willReturnSelf();
        $qb->expects($this->exactly(2))
            ->method('setParameter')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn((string)$count);

        $result = $this->repo->countCompletedTickets($queueId);
        $this->assertSame($count, $result);
    }

    public function testCountCompletedTicketsWithDateRange(): void
    {
        $queueId = 'q-comp2';
        $start = new \DateTimeImmutable('2025-06-01');
        $end = new \DateTimeImmutable('2025-06-30');
        $count = 7;
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $this->repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('t')
            ->willReturn($qb);
        $qb->expects($this->once())
            ->method('select')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('innerJoin')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('where')
            ->willReturnSelf();
        $qb->expects($this->exactly(3))
            ->method('andWhere')
            ->willReturnSelf();
        $qb->expects($this->exactly(4))
            ->method('setParameter')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn((string)$count);

        $result = $this->repo->countCompletedTickets($queueId, $start, $end);
        $this->assertSame($count, $result);
    }

    public function testSave(): void
    {
        $ticket = $this->createMock(QueueTicket::class);
        $this->em->expects($this->once())->method('persist')->with($ticket)->willReturnSelf();
        $this->em->expects($this->once())->method('flush')->willReturnSelf();
        $this->em->method('contains')->with($ticket)->willReturn(false);
        $this->repo->save($ticket);
    }

    public function testRemove(): void
    {
        $ticket = $this->createMock(QueueTicket::class);
        $this->em->expects($this->once())->method('remove')->with($ticket)->willReturnSelf();
        $this->em->expects($this->once())->method('flush')->willReturnSelf();
        $this->repo->remove($ticket);
    }
}
