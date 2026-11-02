<?php declare(strict_types=1);

namespace App\Tests\Unit\Queue\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Queue\Application\Service\QueueService;
use App\Queue\Domain\Entity\Queue;
use App\Queue\Domain\Entity\QueueTicket;
use App\Queue\Domain\Repository\QueueRepositoryInterface;
use App\Queue\Domain\Repository\QueueTicketRepositoryInterface;
use App\Government\Domain\Entity\Officer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

#[AllowMockObjectsWithoutExpectations]
class QueueServiceTest extends TestCase
{
    private QueueRepositoryInterface&MockObject $queueRepository;
    private QueueTicketRepositoryInterface&MockObject $ticketRepository;
    private QueueService $service;

    protected function setUp(): void
    {
        $this->queueRepository = $this->createMock(QueueRepositoryInterface::class);
        $this->ticketRepository = $this->createMock(QueueTicketRepositoryInterface::class);
        $this->service = new QueueService($this->queueRepository, $this->ticketRepository);
    }

    private function createQueue(?string $id = null, array $overrides = []): Queue
    {
        $queueId = $id ?? Uuid::uuid4()->toString();
        $queue = new Queue(
            $queueId,
            $overrides['name'] ?? 'Test Queue',
            $overrides['wardCode'] ?? 'W001',
            $overrides['serviceTypes'] ?? ['general'],
            $overrides['departmentName'] ?? null
        );
        if (isset($overrides['description'])) {
            $queue->setDescription($overrides['description']);
        }
        if (isset($overrides['counterCount'])) {
            $queue->setCounterCount($overrides['counterCount']);
        }
        if (isset($overrides['operatingHours'])) {
            $queue->setOperatingHours($overrides['operatingHours']);
        }
        if (isset($overrides['status'])) {
            if ($overrides['status'] === Queue::STATUS_ACTIVE) {
                $queue->activate();
            } elseif ($overrides['status'] === Queue::STATUS_INACTIVE) {
                $queue->deactivate();
            } elseif ($overrides['status'] === Queue::STATUS_CLOSED) {
                $queue->close();
            }
        }
        return $queue;
    }

    private function createTicket(Queue $queue, array $overrides = []): QueueTicket
    {
        $ticketId = $overrides['id'] ?? Uuid::uuid4()->toString();
        $ticket = new QueueTicket(
            $ticketId,
            $queue,
            $overrides['serviceType'] ?? 'general',
            $overrides['citizenName'] ?? 'John Doe',
            $overrides['citizenPhone'] ?? null,
            $overrides['priority'] ?? 0,
            $overrides['ticketNumber'] ?? 'GEN-0001'
        );
        // Allow setting status via reflection if needed
        if (array_key_exists('status', $overrides)) {
            $ref = new \ReflectionProperty(QueueTicket::class, 'status');
            $ref->setAccessible(true);
            $ref->setValue($ticket, $overrides['status']);
        }
        return $ticket;
    }

    public function testCreateQueue(): void
    {
        $queue = new Queue(
            Uuid::uuid4()->toString(),
            'Test Queue',
            'W001',
            ['general'],
            'Dept A'
        );
        $queue->setDescription('Desc');
        $queue->setCounterCount(2);
        $queue->setOperatingHours(['mon' => ['09:00', '17:00']]);

        $this->queueRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Queue $q) {
                return $q->getName() === 'Test Queue'
                    && $q->getWardCode() === 'W001'
                    && $q->getServiceTypes() === ['general']
                    && $q->getDepartmentName() === 'Dept A'
                    && $q->getDescription() === 'Desc'
                    && $q->getCounterCount() === 2
                    && $q->getOperatingHours() === ['mon' => ['09:00', '17:00']];
            }))
            ->willReturnArgument(0);

        $result = $this->service->createQueue(
            'Test Queue',
            'W001',
            ['general'],
            'Dept A',
            'Desc',
            2,
            ['mon' => ['09:00', '17:00']]
        );

        $this->assertInstanceOf(Queue::class, $result);
    }

    public function testUpdateQueueWhenExists(): void
    {
        $queue = $this->createQueue('queue-123', ['name' => 'Old Name', 'counterCount' => 1]);
        $this->queueRepository->expects($this->once())
            ->method('findById')
            ->with('queue-123')
            ->willReturn($queue);
        $this->queueRepository->expects($this->once())
            ->method('save')
            ->with($queue)
            ->willReturnArgument(0);

        $result = $this->service->updateQueue(
            'queue-123',
            name: 'New Name',
            counterCount: 2
        );

        $this->assertSame($queue, $result);
        $this->assertSame('New Name', $queue->getName());
        $this->assertSame(2, $queue->getCounterCount());
    }

    public function testUpdateQueueReturnsNullWhenNotFound(): void
    {
        $this->queueRepository->expects($this->once())
            ->method('findById')
            ->with('missing')
            ->willReturn(null);

        $result = $this->service->updateQueue('missing', name: 'New Name');
        $this->assertNull($result);
    }

    public function testActivateQueue(): void
    {
        $queue = $this->createQueue('queue-id');
        $this->queueRepository->expects($this->once())->method('findById')->with('queue-id')->willReturn($queue);
        $this->queueRepository->expects($this->once())->method('save')->with($queue);

        $result = $this->service->activateQueue('queue-id');
        $this->assertSame($queue, $result);
    }

    public function testDeactivateQueue(): void
    {
        $queue = $this->createQueue('queue-id');
        $this->queueRepository->expects($this->once())->method('findById')->with('queue-id')->willReturn($queue);
        $this->queueRepository->expects($this->once())->method('save')->with($queue);

        $result = $this->service->deactivateQueue('queue-id');
        $this->assertSame($queue, $result);
    }

    public function testGetQueue(): void
    {
        $queue = $this->createQueue('queue-id');
        $this->queueRepository->expects($this->once())->method('findById')->with('queue-id')->willReturn($queue);
        $result = $this->service->getQueue('queue-id');
        $this->assertSame($queue, $result);
    }

    public function testGetQueuesByWard(): void
    {
        $queues = [$this->createQueue(), $this->createQueue()];
        $this->queueRepository->expects($this->once())
            ->method('findByWardCode')
            ->with('W001')
            ->willReturn($queues);
        $result = $this->service->getQueuesByWard('W001');
        $this->assertSame($queues, $result);
    }

    public function testGetActiveQueues(): void
    {
        $queues = [$this->createQueue()];
        $this->queueRepository->expects($this->once())
            ->method('findActiveQueues')
            ->willReturn($queues);
        $result = $this->service->getActiveQueues();
        $this->assertSame($queues, $result);
    }

    public function testGetOpenQueues(): void
    {
        $queues = [$this->createQueue()];
        $this->queueRepository->expects($this->once())
            ->method('findOpenQueues')
            ->willReturn($queues);
        $result = $this->service->getOpenQueues();
        $this->assertSame($queues, $result);
    }

    public function testJoinQueueCreatesTicket(): void
    {
        // Build a queue that is active and always open (operating hours all days 00:00-23:59)
        $allDays = [];
        for ($d = 1; $d <= 7; $d++) {
            $allDays[$d] = ['open' => '00:00', 'close' => '23:59'];
        }
        $queue = $this->createQueue('queue-123', [
            'operatingHours' => $allDays,
            'serviceTypes' => ['general'],
            'counterCount' => 1
        ]);

        $serviceType = 'general';
        $citizenName = 'John Doe';
        $citizenPhone = '123456';
        $priority = 5;

        $this->queueRepository->expects($this->once())
            ->method('findById')
            ->with('queue-123')
            ->willReturn($queue);
        $this->ticketRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (QueueTicket $t) use ($queue, $serviceType, $citizenName, $citizenPhone, $priority) {
                return $t->getQueue() === $queue
                    && $t->getServiceType() === $serviceType
                    && $t->getCitizenName() === $citizenName
                    && $t->getCitizenPhone() === $citizenPhone
                    && $t->getPriority() === $priority;
            }))
            ->willReturnArgument(0);

        $result = $this->service->joinQueue(
            'queue-123',
            $serviceType,
            $citizenName,
            $citizenPhone,
            null,
            $priority
        );

        $this->assertInstanceOf(QueueTicket::class, $result);
    }

    public function testJoinQueueReturnsNullIfQueueNotFound(): void
    {
        $this->queueRepository->expects($this->once())
            ->method('findById')
            ->with('missing')
            ->willReturn(null);
        $result = $this->service->joinQueue('missing', 'general', 'John');
        $this->assertNull($result);
    }

    public function testCallNextTicket(): void
    {
        $queue = $this->createQueue('queue-123');
        $officer = $this->createMock(Officer::class);
        $ticket = $this->createMock(QueueTicket::class);
        $ticket->expects($this->once())->method('call')->with($officer, 'C1')->willReturnSelf();

        $this->queueRepository->expects($this->once())->method('findById')->with('queue-123')->willReturn($queue);
        $this->ticketRepository->expects($this->once())
            ->method('findNextWaitingTicket')
            ->with('queue-123')
            ->willReturn($ticket);
        $this->ticketRepository->expects($this->once())->method('save')->with($ticket);

        $result = $this->service->callNextTicket('queue-123', $officer, 'C1');
        $this->assertSame($ticket, $result);
    }

    public function testCallNextTicketReturnsNullIfNoWaitingTickets(): void
    {
        $queue = $this->createQueue('queue-123');
        $this->queueRepository->expects($this->once())->method('findById')->with('queue-123')->willReturn($queue);
        $this->ticketRepository->expects($this->once())
            ->method('findNextWaitingTicket')
            ->with('queue-123')
            ->willReturn(null);
        $result = $this->service->callNextTicket('queue-123', $this->createMock(Officer::class), 'C1');
        $this->assertNull($result);
    }

    public function testStartTicket(): void
    {
        $queue = $this->createQueue();
        $ticket = $this->createMock(QueueTicket::class);
        $ticket->expects($this->once())->method('start')->willReturnSelf();

        $this->ticketRepository->expects($this->once())->method('findById')->with('ticket-1')->willReturn($ticket);
        $this->ticketRepository->expects($this->once())->method('save')->with($ticket);

        $result = $this->service->startTicket('ticket-1');
        $this->assertSame($ticket, $result);
    }

    public function testStartTicketReturnsNullIfNotFound(): void
    {
        $this->ticketRepository->expects($this->once())->method('findById')->with('missing')->willReturn(null);
        $result = $this->service->startTicket('missing');
        $this->assertNull($result);
    }

    public function testCompleteTicket(): void
    {
        $queue = $this->createQueue();
        $ticket = $this->createMock(QueueTicket::class);
        $ticket->expects($this->once())->method('complete')->with('Done')->willReturnSelf();
        $ticket->method('getQueue')->willReturn($queue);

        $this->ticketRepository->expects($this->once())->method('findById')->with('ticket-1')->willReturn($ticket);
        $this->ticketRepository->expects($this->once())->method('save')->with($ticket);

        $result = $this->service->completeTicket('ticket-1', 'Done');
        $this->assertSame($ticket, $result);
    }

    public function testCompleteTicketReturnsNullIfNotFound(): void
    {
        $this->ticketRepository->expects($this->once())->method('findById')->with('missing')->willReturn(null);
        $result = $this->service->completeTicket('missing');
        $this->assertNull($result);
    }

    public function testCancelTicket(): void
    {
        $queue = $this->createQueue();
        $ticket = $this->createMock(QueueTicket::class);
        $ticket->expects($this->once())->method('cancel')->with('No show')->willReturnSelf();

        $this->ticketRepository->expects($this->once())->method('findById')->with('ticket-1')->willReturn($ticket);
        $this->ticketRepository->expects($this->once())->method('save')->with($ticket);

        $result = $this->service->cancelTicket('ticket-1', 'No show');
        $this->assertSame($ticket, $result);
    }

    public function testCancelTicketReturnsNullIfNotFound(): void
    {
        $this->ticketRepository->expects($this->once())->method('findById')->with('missing')->willReturn(null);
        $result = $this->service->cancelTicket('missing');
        $this->assertNull($result);
    }

    public function testSkipTicket(): void
    {
        $queue = $this->createQueue();
        $ticket = $this->createMock(QueueTicket::class);
        $ticket->expects($this->once())->method('skip')->willReturnSelf();

        $this->ticketRepository->expects($this->once())->method('findById')->with('ticket-1')->willReturn($ticket);
        $this->ticketRepository->expects($this->once())->method('save')->with($ticket);

        $result = $this->service->skipTicket('ticket-1');
        $this->assertSame($ticket, $result);
    }

    public function testSkipTicketReturnsNullIfNotFound(): void
    {
        $this->ticketRepository->expects($this->once())->method('findById')->with('missing')->willReturn(null);
        $result = $this->service->skipTicket('missing');
        $this->assertNull($result);
    }

    public function testGetTicket(): void
    {
        $ticket = $this->createTicket($this->createQueue());
        $this->ticketRepository->expects($this->once())->method('findById')->with('ticket-1')->willReturn($ticket);
        $result = $this->service->getTicket('ticket-1');
        $this->assertSame($ticket, $result);
    }

    public function testGetTicketByNumber(): void
    {
        $ticket = $this->createTicket($this->createQueue());
        $this->ticketRepository->expects($this->once())
            ->method('findByTicketNumber')
            ->with('T001')
            ->willReturn($ticket);
        $result = $this->service->getTicketByNumber('T001');
        $this->assertSame($ticket, $result);
    }

    public function testGetTicketsByQueue(): void
    {
        $queue = $this->createQueue('queue-1');
        $tickets = [$this->createTicket($queue), $this->createTicket($queue)];
        $this->ticketRepository->expects($this->once())
            ->method('findByQueue')
            ->with('queue-1', 'waiting', 10, 5)
            ->willReturn($tickets);
        $result = $this->service->getTicketsByQueue('queue-1', 'waiting', 10, 5);
        $this->assertSame($tickets, $result);
    }

    public function testGetWaitingTickets(): void
    {
        $queue = $this->createQueue('queue-1');
        $tickets = [$this->createTicket($queue), $this->createTicket($queue)];
        $this->ticketRepository->expects($this->once())
            ->method('findWaitingTickets')
            ->with('queue-1')
            ->willReturn($tickets);
        $result = $this->service->getWaitingTickets('queue-1');
        $this->assertSame($tickets, $result);
    }

    public function testGetCalledTickets(): void
    {
        $queue = $this->createQueue('queue-1');
        $tickets = [$this->createTicket($queue)];
        $this->ticketRepository->expects($this->once())
            ->method('findCalledTickets')
            ->with('queue-1')
            ->willReturn($tickets);
        $result = $this->service->getCalledTickets('queue-1');
        $this->assertSame($tickets, $result);
    }

    public function testGetTicketsByCitizen(): void
    {
        $tickets = [$this->createTicket($this->createQueue()), $this->createTicket($this->createQueue())];
        $this->ticketRepository->expects($this->once())
            ->method('findByCitizen')
            ->with('citizen-123')
            ->willReturn($tickets);
        $result = $this->service->getTicketsByCitizen('citizen-123');
        $this->assertSame($tickets, $result);
    }

    public function testGetTicketsByPhone(): void
    {
        $tickets = [$this->createTicket($this->createQueue()), $this->createTicket($this->createQueue())];
        $this->ticketRepository->expects($this->once())
            ->method('findByCitizenPhone')
            ->with('123456')
            ->willReturn($tickets);
        $result = $this->service->getTicketsByPhone('123456');
        $this->assertSame($tickets, $result);
    }

    public function testGetQueueStatistics(): void
    {
        $queue = $this->createMock(Queue::class);
        $queue->method('getId')->willReturn('queue-1');
        $queue->method('getName')->willReturn('Queue 1');
        $queue->method('isOpen')->willReturn(true);
        $queue->method('getAverageWaitTime')->willReturn(10);
        $queue->method('getAverageServiceTime')->willReturn(5);
        $queue->method('getInProgressTickets')->willReturn([$this->createTicket($this->createMock(Queue::class))]);
        $queue->method('getOperatingHours')->willReturn(['mon' => ['09:00', '17:00']]);

        $this->queueRepository->expects($this->once())->method('findById')->with('queue-1')->willReturn($queue);
        $this->ticketRepository->expects($this->once())
            ->method('countWaitingTickets')
            ->with('queue-1')
            ->willReturn(3);
        $this->ticketRepository->expects($this->once())
            ->method('countCompletedTickets')
            ->with('queue-1', $this->anything(), $this->anything())
            ->willReturn(2);

        $result = $this->service->getQueueStatistics('queue-1');
        $this->assertSame([
            'queueId' => 'queue-1',
            'queueName' => 'Queue 1',
            'isOpen' => true,
            'waitingCount' => 3,
            'inProgressCount' => 1,
            'todayCompleted' => 2,
            'averageWaitTime' => 10,
            'averageServiceTime' => 5,
            'operatingHours' => ['mon' => ['09:00', '17:00']],
        ], $result);
    }

    public function testGetQueueStatisticsReturnsNullIfQueueNotFound(): void
    {
        $this->queueRepository->expects($this->once())->method('findById')->with('missing')->willReturn(null);
        $result = $this->service->getQueueStatistics('missing');
        $this->assertNull($result);
    }
}
