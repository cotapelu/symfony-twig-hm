<?php declare(strict_types=1);

namespace App\Tests\Unit\Queue\Repository;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Queue\Domain\Entity\Queue;
use App\Queue\Infrastructure\Repository\QueueRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\Query\Expr;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class QueueRepositoryTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private QueueRepository $repo;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repo = $this->getMockBuilder(QueueRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getEntityManager', 'find', 'createQueryBuilder'])
            ->getMock();
        $this->repo->method('getEntityManager')->willReturn($this->em);
    }

    public function testFindById(): void
    {
        $queueId = 'queue-123';
        $queue = $this->createMock(Queue::class);

        $this->repo->expects($this->once())
            ->method('find')
            ->with($queueId)
            ->willReturn($queue);

        $result = $this->repo->findById($queueId);
        $this->assertSame($queue, $result);
    }

    public function testFindByWardCode(): void
    {
        $wardCode = 'W001';
        $queue = $this->createMock(Queue::class);
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $this->repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('q')
            ->willReturn($qb);
        $qb->expects($this->once())
            ->method('where')
            ->with('q.wardCode = :wardCode')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('wardCode', $wardCode)
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('orderBy')
            ->with('q.createdAt', 'DESC')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([$queue]);

        $result = $this->repo->findByWardCode($wardCode);
        $this->assertCount(1, $result);
    }

    public function testFindActiveQueues(): void
    {
        $queue = $this->createMock(Queue::class);
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $this->repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('q')
            ->willReturn($qb);
        $qb->expects($this->once())
            ->method('where')
            ->with('q.status = :status')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('status', Queue::STATUS_ACTIVE)
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('orderBy')
            ->with('q.name', 'ASC')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([$queue]);

        $result = $this->repo->findActiveQueues();
        $this->assertCount(1, $result);
    }

    public function testFindOpenQueues(): void
    {
        $dayOfWeek = (int)date('N'); // Current day of week (1-7)

        $queueOpen = $this->createMock(Queue::class);
        $queueOpen->method('isOpen')->willReturn(true);
        $queueClosed = $this->createMock(Queue::class);
        $queueClosed->method('isOpen')->willReturn(false);

        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $this->repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('q')
            ->willReturn($qb);

        $qb->expects($this->once())
            ->method('where')
            ->with('q.status = :status')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('status', Queue::STATUS_ACTIVE)
            ->willReturnSelf();

        // Verify andWhere is called (the exact expression is complex and tested at integration level)
        $qb->expects($this->once())
            ->method('andWhere')
            ->with($this->anything())
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('orderBy')
            ->with('q.name', 'ASC')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([$queueOpen, $queueClosed]);

        $result = $this->repo->findOpenQueues();

        $this->assertCount(1, $result);
        $this->assertSame($queueOpen, $result[0]);
    }

    public function testSave(): void
    {
        $queue = $this->createMock(Queue::class);
        $this->em->expects($this->once())->method('persist')->with($queue)->willReturnSelf();
        $this->em->expects($this->once())->method('flush')->willReturnSelf();
        $this->em->method('contains')->with($queue)->willReturn(false);
        $this->repo->save($queue);
    }

    public function testRemove(): void
    {
        $queue = $this->createMock(Queue::class);
        $this->em->expects($this->once())->method('remove')->with($queue)->willReturnSelf();
        $this->em->expects($this->once())->method('flush')->willReturnSelf();
        $this->repo->remove($queue);
    }

    public function testIncrementAndGetNextSequenceNumber(): void
    {
        $queueId = 'q-123';
        $queue = $this->createMock(Queue::class);
        $queue->method('getCurrentSequence')->willReturn(5);
        $queue->expects($this->once())->method('setCurrentSequence')->with(6)->willReturnSelf();

        $this->em->method('contains')->with($queue)->willReturn(false);
        $this->em->expects($this->once())->method('lock')->with($queue, LockMode::PESSIMISTIC_WRITE)->willReturnSelf();
        $this->em->expects($this->once())->method('flush')->willReturnSelf();

        $this->repo->method('find')->with($queueId)->willReturn($queue);
        $result = $this->repo->incrementAndGetNextSequenceNumber($queueId);
        $this->assertSame(6, $result);
    }

    public function testIncrementAndGetNextSequenceNumberThrowsIfNotFound(): void
    {
        $queueId = 'missing';
        $this->repo->method('find')->with($queueId)->willReturn(null);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Queue not found');
        $this->repo->incrementAndGetNextSequenceNumber($queueId);
    }
}
