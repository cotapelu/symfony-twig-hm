<?php declare(strict_types=1);

namespace App\Tests\Unit\Queue\Application\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Queue\Application\Service\QueueService;
use App\Queue\Domain\Entity\Queue;
use App\Queue\Domain\Entity\QueueTicket;
use App\Queue\Domain\Repository\QueueRepositoryInterface;
use App\Queue\Domain\Repository\QueueTicketRepositoryInterface;
use App\Government\Domain\Entity\Officer;
use App\Government\Domain\Entity\Citizen;
use App\Government\Domain\ValueObject\CitizenId;
use DateTimeImmutable;
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
        // Set additional properties via setters if needed
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
            // Use activate/deactivate to set status
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
        // Apply status override if provided
        if (array_key_exists('status', $overrides)) {
            $this->setTicketStatus($ticket, $overrides['status']);
        }
        // Apply citizen override if provided
        if (array_key_exists('citizenId', $overrides)) {
            $this->assignCitizenToTicket($ticket, $overrides['citizenId']);
        }
        return $ticket;
    }

    private function setTicketStatus(QueueTicket $ticket, string $status): void
    {
        $ref = new \ReflectionProperty(QueueTicket::class, 'status');
        $ref->setAccessible(true);
        $ref->setValue($ticket, $status);
    }

    private function assignCitizenToTicket(QueueTicket $ticket, string $citizenId): void
    {
        $citizen = $this->createMock(Citizen::class);
        $citizen->method('getId')->willReturn(new CitizenId($citizenId));
        $ticket->setCitizen($citizen);
    }

    public function testCreateQueue(): void
    {
        $queue = $this->createQueue();
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
        $this->assertSame('Test Queue', $result->getName());
        $this->assertSame('W001', $result->getWardCode());
        $this->assertSame(['general'], $result->getServiceTypes());
        $this->assertSame('Dept A', $result->getDepartmentName());
        $this->assertSame('Desc', $result->getDescription());
        $this->assertSame(2, $result->getCounterCount());
        $this->assertSame(['mon' => ['09:00', '17:00']], $result->getOperatingHours());
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
            'New Name',
            ['general', 'vip'],
            'New Desc',
            3,
            ['tue' => ['08:00', '16:00']]
        );

        $this->assertSame($queue, $result);
        $this->assertSame('New Name', $queue->getName());
        $this->assertSame(['general', 'vip'], $queue->getServiceTypes());
        $this->assertSame('New Desc', $queue->getDescription());
        $this->assertSame(3, $queue->getCounterCount());
        $this->assertSame(['tue' => ['08:00', '16:00']], $queue->getOperatingHours());
    }

    public function testUpdateQueueWhenNotFound(): void
    {
        $this->queueRepository->expects($this->once())
            ->method('findById')
            ->with('nonexistent')
            ->willReturn(null);

        $result = $this->service->updateQueue('nonexistent', 'New Name');

        $this->assertNull($result);
    }

    public function testActivateQueue(): void
    {
        $queue = $this->createQueue('q1', ['status' => Queue::STATUS_INACTIVE]);
        $this->queueRepository->expects($this->once())
            ->method('findById')
            ->with('q1')
            ->willReturn($queue);
        $this->queueRepository->expects($this->once())
            ->method('save')
            ->with($queue)
            ->willReturnArgument(0);

        $result = $this->service->activateQueue('q1');

        $this->assertSame($queue, $result);
        $this->assertTrue($queue->isActive());
    }

    public function testDeactivateQueue(): void
    {
        $queue = $this->createQueue('q1', ['status' => Queue::STATUS_ACTIVE]);
        $this->queueRepository->expects($this->once())
            ->method('findById')
            ->with('q1')
            ->willReturn($queue);
        $this->queueRepository->expects($this->once())
            ->method('save')
            ->with($queue);

        $result = $this->service->deactivateQueue('q1');

        $this->assertSame($queue, $result);
        $this->assertFalse($queue->isActive());
    }

    public function testGetQueue(): void
    {
        $queue = $this->createQueue('q1');
        $this->queueRepository->expects($this->once())
            ->method('findById')
            ->with('q1')
            ->willReturn($queue);

        $result = $this->service->getQueue('q1');

        $this->assertSame($queue, $result);
    }

    public function testGetQueuesByWard(): void
    {
        $queues = [$this->createQueue('q1'), $this->createQueue('q2')];
        $this->queueRepository->expects($this->once())
            ->method('findByWardCode')
            ->with('W001')
            ->willReturn($queues);

        $result = $this->service->getQueuesByWard('W001');

        $this->assertSame($queues, $result);
    }

    public function testGetActiveQueues(): void
    {
        $queues = [$this->createQueue('q1'), $this->createQueue('q2')];
        $this->queueRepository->expects($this->once())
            ->method('findActiveQueues')
            ->willReturn($queues);

        $result = $this->service->getActiveQueues();

        $this->assertSame($queues, $result);
    }

    public function testGetOpenQueues(): void
    {
        $queues = [$this->createQueue('q1'), $this->createQueue('q2')];
        $this->queueRepository->expects($this->once())
            ->method('findOpenQueues')
            ->willReturn($queues);

        $result = $this->service->getOpenQueues();

        $this->assertSame($queues, $result);
    }

    public function testJoinQueue(): void
    {
        $queue = $this->createQueue('q1', [
            'status' => Queue::STATUS_ACTIVE,
            'serviceTypes' => ['general'],
            'operatingHours' => [
                1 => ['open' => '00:00', 'close' => '23:59'],
                2 => ['open' => '00:00', 'close' => '23:59'],
                3 => ['open' => '00:00', 'close' => '23:59'],
                4 => ['open' => '00:00', 'close' => '23:59'],
                5 => ['open' => '00:00', 'close' => '23:59'],
                6 => ['open' => '00:00', 'close' => '23:59'],
                7 => ['open' => '00:00', 'close' => '23:59'],
            ]
        ]);
        // Need to ensure queue is open; default behavior is active but isOpen() based on status? In Queue entity, isOpen() likely checks status===open. Actually without DB, we need to see: Maybe status 'active' means open. There's also close() method. We'll assume active means open.
        $this->queueRepository->expects($this->once())
            ->method('findById')
            ->with('q1')
            ->willReturn($queue);
        $this->queueRepository->expects($this->once())
            ->method('incrementAndGetNextSequenceNumber')
            ->with('q1')
            ->willReturn(1);
        $this->ticketRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (QueueTicket $ticket) use ($queue) {
                return $ticket->getQueue()->getId() === $queue->getId()
                    && $ticket->getServiceType() === 'general'
                    && $ticket->getCitizenName() === 'Jane'
                    && $ticket->getTicketNumber() === 'GENERAL-0001';
            }));

        $result = $this->service->joinQueue('q1', 'general', 'Jane');

        $this->assertInstanceOf(QueueTicket::class, $result);
        $this->assertSame('GENERAL-0001', $result->getTicketNumber());
        $this->assertSame('q1', $result->getQueue()->getId());
        $this->assertSame('general', $result->getServiceType());
        $this->assertSame('Jane', $result->getCitizenName());
    }

    public function testJoinQueueWhenQueueNotFound(): void
    {
        $this->queueRepository->expects($this->once())
            ->method('findById')
            ->with('q1')
            ->willReturn(null);

        $result = $this->service->joinQueue('q1', 'general', 'Jane');

        $this->assertNull($result);
    }

    public function testJoinQueueWhenNotActiveThrows(): void
    {
        $queue = $this->createQueue('q1', ['status' => Queue::STATUS_INACTIVE]);
        $this->queueRepository->expects($this->once())
            ->method('findById')
            ->with('q1')
            ->willReturn($queue);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Queue is not active');

        $this->service->joinQueue('q1', 'general', 'Jane');
    }

    public function testJoinQueueWhenNotOpenThrows(): void
    {
        // Active queue but operatingHours not set (null) -> isOpen() returns false
        $queue = $this->createQueue('q1', ['status' => Queue::STATUS_ACTIVE]);
        $this->queueRepository->expects($this->once())
            ->method('findById')
            ->with('q1')
            ->willReturn($queue);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Queue is not currently open');

        $this->service->joinQueue('q1', 'general', 'Jane');
    }

    public function testJoinQueueWhenServiceTypeNotAvailableThrows(): void
    {
        $queue = $this->createQueue('q1', [
            'status' => Queue::STATUS_ACTIVE,
            'serviceTypes' => ['vip'],
            'operatingHours' => [
                1 => ['open' => '00:00', 'close' => '23:59'],
                2 => ['open' => '00:00', 'close' => '23:59'],
                3 => ['open' => '00:00', 'close' => '23:59'],
                4 => ['open' => '00:00', 'close' => '23:59'],
                5 => ['open' => '00:00', 'close' => '23:59'],
                6 => ['open' => '00:00', 'close' => '23:59'],
                7 => ['open' => '00:00', 'close' => '23:59'],
            ]
        ]);
        $this->queueRepository->expects($this->once())
            ->method('findById')
            ->with('q1')
            ->willReturn($queue);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Service type not available');

        $this->service->joinQueue('q1', 'general', 'Jane');
    }

    public function testCallNextTicket(): void
    {
        $queue = $this->createQueue('q1', ['status' => Queue::STATUS_ACTIVE]);
        $ticket = $this->createTicket($queue, ['status' => QueueTicket::STATUS_WAITING]);
        $officer = $this->createMock(Officer::class);

        $this->queueRepository->expects($this->once())
            ->method('findById')
            ->with('q1')
            ->willReturn($queue);
        $this->ticketRepository->expects($this->once())
            ->method('findNextWaitingTicket')
            ->with('q1')
            ->willReturn($ticket);
        $this->ticketRepository->expects($this->once())
            ->method('save')
            ->with($ticket);

        $result = $this->service->callNextTicket('q1', $officer, 'C1');

        $this->assertSame($ticket, $result);
        $this->assertSame(QueueTicket::STATUS_CALLED, $ticket->getStatus());
        $this->assertSame($officer, $ticket->getServedByOfficer());
        $this->assertSame('C1', $ticket->getCounterNumber());
    }

    public function testCallNextTicketWhenQueueNotFound(): void
    {
        $officer = $this->createMock(Officer::class);
        $this->queueRepository->expects($this->once())
            ->method('findById')
            ->with('q1')
            ->willReturn(null);

        $result = $this->service->callNextTicket('q1', $officer, 'C1');

        $this->assertNull($result);
    }

    public function testCallNextTicketWhenNoWaitingTicket(): void
    {
        $queue = $this->createQueue('q1', ['status' => Queue::STATUS_ACTIVE]);
        $this->queueRepository->expects($this->once())
            ->method('findById')
            ->with('q1')
            ->willReturn($queue);
        $this->ticketRepository->expects($this->once())
            ->method('findNextWaitingTicket')
            ->with('q1')
            ->willReturn(null);

        $result = $this->service->callNextTicket('q1', $this->createMock(Officer::class), 'C1');

        $this->assertNull($result);
    }

    public function testStartTicket(): void
    {
        $queue = $this->createQueue('q1');
        $ticket = $this->createTicket($queue, ['status' => QueueTicket::STATUS_CALLED]);

        $this->ticketRepository->expects($this->once())
            ->method('findById')
            ->with('ticket-123')
            ->willReturn($ticket);
        $this->ticketRepository->expects($this->once())
            ->method('save')
            ->with($ticket);

        $result = $this->service->startTicket('ticket-123');

        $this->assertSame($ticket, $result);
        $this->assertSame(QueueTicket::STATUS_IN_PROGRESS, $ticket->getStatus());
        $this->assertNotNull($ticket->getStartedAt());
    }

    public function testStartTicketWhenNotFound(): void
    {
        $this->ticketRepository->expects($this->once())
            ->method('findById')
            ->with('ticket-123')
            ->willReturn(null);

        $result = $this->service->startTicket('ticket-123');

        $this->assertNull($result);
    }

    public function testStartTicketWithUserIdMismatchThrows(): void
    {
        $queue = $this->createQueue('q1');
        $ticket = $this->createTicket($queue, ['status' => QueueTicket::STATUS_CALLED, 'citizenId' => 'citizen-456']);

        $this->ticketRepository->expects($this->once())
            ->method('findById')
            ->with('ticket-123')
            ->willReturn($ticket);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Access denied');

        $this->service->startTicket('ticket-123', 'citizen-789');
    }

    public function testCompleteTicket(): void
    {
        $queue = $this->createQueue('q1');
        $ticket = $this->createTicket($queue, ['status' => QueueTicket::STATUS_IN_PROGRESS]);

        $this->assertSame(QueueTicket::STATUS_IN_PROGRESS, $ticket->getStatus());

        $this->ticketRepository->expects($this->once())
            ->method('findById')
            ->with('ticket-123')
            ->willReturn($ticket);
        $this->ticketRepository->expects($this->once())
            ->method('save')
            ->with($ticket);

        $result = $this->service->completeTicket('ticket-123', 'Done');

        $this->assertSame($ticket, $result);
        $this->assertSame(QueueTicket::STATUS_COMPLETED, $ticket->getStatus());
        $this->assertSame('Done', $ticket->getNotes());
        $this->assertNotNull($ticket->getCompletedAt());
    }

    public function testCancelTicket(): void
    {
        $queue = $this->createQueue('q1');
        $ticket = $this->createTicket($queue, ['status' => QueueTicket::STATUS_WAITING]);

        $this->ticketRepository->expects($this->once())
            ->method('findById')
            ->with('ticket-123')
            ->willReturn($ticket);
        $this->ticketRepository->expects($this->once())
            ->method('save')
            ->with($ticket);

        $result = $this->service->cancelTicket('ticket-123', 'No show');

        $this->assertSame($ticket, $result);
        $this->assertSame(QueueTicket::STATUS_CANCELLED, $ticket->getStatus());
        $this->assertSame('No show', $ticket->getNotes());
    }

    public function testSkipTicketByCitizenThrows(): void
    {
        $queue = $this->createQueue('q1');
        $ticket = $this->createTicket($queue, ['status' => QueueTicket::STATUS_WAITING, 'citizenId' => 'citizen-123']);

        $this->ticketRepository->expects($this->once())
            ->method('findById')
            ->with('ticket-123')
            ->willReturn($ticket);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('only officers can skip');

        $this->service->skipTicket('ticket-123', 'citizen-123');
    }

    public function testGetTicketByNumber(): void
    {
        $queue = $this->createQueue('q1');
        $ticket = $this->createTicket($queue, ['ticketNumber' => 'GEN-0001']);

        $this->ticketRepository->expects($this->once())
            ->method('findByTicketNumber')
            ->with('GEN-0001')
            ->willReturn($ticket);

        $result = $this->service->getTicketByNumber('GEN-0001');

        $this->assertSame($ticket, $result);
    }

    public function testGetTicketsByQueue(): void
    {
        $queue = $this->createQueue('q1');
        $tickets = [$this->createTicket($queue), $this->createTicket($queue)];

        $this->ticketRepository->expects($this->once())
            ->method('findByQueue')
            ->with('q1', 'waiting', 10, 0)
            ->willReturn($tickets);

        $result = $this->service->getTicketsByQueue('q1', 'waiting', 10, 0);

        $this->assertSame($tickets, $result);
    }

    public function testGetQueueStatistics(): void
    {
        $queue = $this->createQueue('q1', [
            'averageWaitTime' => 15,
            'averageServiceTime' => 20,
            'operatingHours' => [
                1 => ['open' => '00:00', 'close' => '23:59'],
                2 => ['open' => '00:00', 'close' => '23:59'],
                3 => ['open' => '00:00', 'close' => '23:59'],
                4 => ['open' => '00:00', 'close' => '23:59'],
                5 => ['open' => '00:00', 'close' => '23:59'],
                6 => ['open' => '00:00', 'close' => '23:59'],
                7 => ['open' => '00:00', 'close' => '23:59'],
            ]
        ]);
        $this->queueRepository->expects($this->once())
            ->method('findById')
            ->with('q1')
            ->willReturn($queue);
        $this->ticketRepository->expects($this->once())
            ->method('countWaitingTickets')
            ->with('q1')
            ->willReturn(3);
        $this->ticketRepository->expects($this->once())
            ->method('countCompletedTickets')
            ->with($this->anything(), $this->anything(), $this->anything())
            ->willReturn(5);

        $result = $this->service->getQueueStatistics('q1');

        $this->assertNotNull($result);
        $this->assertSame('q1', $result['queueId']);
        $this->assertSame('Test Queue', $result['queueName']);
        $this->assertTrue($result['isOpen']);
        $this->assertSame(3, $result['waitingCount']);
    }
}
