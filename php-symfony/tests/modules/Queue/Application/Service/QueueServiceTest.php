<?php

declare(strict_types=1);

namespace App\Tests\Modules\Queue\Application\Service;
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
use DateTimeImmutable;

#[AllowMockObjectsWithoutExpectations]
class QueueServiceTest extends TestCase
{
    private QueueRepositoryInterface&MockObject $queueRepository;
    private QueueTicketRepositoryInterface&MockObject $ticketRepository;
    private QueueService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->queueRepository = $this->createMock(QueueRepositoryInterface::class);
        $this->ticketRepository = $this->createMock(QueueTicketRepositoryInterface::class);
        $this->service = new QueueService($this->queueRepository, $this->ticketRepository);
    }

    // ==================== JOIN QUEUE TESTS ====================

    public function testJoinQueueReturnsNullWhenQueueNotFound(): void
    {
        $this->queueRepository->expects($this->once())
            ->method('findById')
            ->with('non-existent-queue')
            ->willReturn(null);

        $result = $this->service->joinQueue(
            queueId: 'non-existent-queue',
            serviceType: 'registration',
            citizenName: 'John Doe',
            citizenPhone: '0123456789'
        );

        $this->assertNull($result);
    }

    public function testJoinQueueThrowsWhenQueueNotActive(): void
    {
        $queue = $this->createMock(Queue::class);
        $queue->expects($this->once())
            ->method('isActive')
            ->willReturn(false);

        $this->queueRepository->expects($this->once())
            ->method('findById')
            ->with('queue-123')
            ->willReturn($queue);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Queue is not active');

        $this->service->joinQueue(
            queueId: 'queue-123',
            serviceType: 'registration',
            citizenName: 'John Doe'
        );
    }

    public function testJoinQueueThrowsWhenQueueNotOpen(): void
    {
        $queue = $this->createMock(Queue::class);
        $queue->expects($this->once())
            ->method('isActive')
            ->willReturn(true);
        $queue->expects($this->once())
            ->method('isOpen')
            ->willReturn(false);

        $this->queueRepository->expects($this->once())
            ->method('findById')
            ->with('queue-123')
            ->willReturn($queue);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Queue is not currently open');

        $this->service->joinQueue(
            queueId: 'queue-123',
            serviceType: 'registration',
            citizenName: 'John Doe'
        );
    }

    public function testJoinQueueThrowsWhenServiceTypeNotAvailable(): void
    {
        $queue = $this->createMock(Queue::class);
        $queue->expects($this->once())
            ->method('isActive')
            ->willReturn(true);
        $queue->expects($this->once())
            ->method('isOpen')
            ->willReturn(true);
        $queue->expects($this->once())
            ->method('hasServiceType')
            ->with('invalid-service')
            ->willReturn(false);

        $this->queueRepository->expects($this->once())
            ->method('findById')
            ->with('queue-123')
            ->willReturn($queue);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Service type not available in this queue');

        $this->service->joinQueue(
            queueId: 'queue-123',
            serviceType: 'invalid-service',
            citizenName: 'John Doe'
        );
    }

    public function testJoinQueueThrowsWhenMaxTicketsPerDayReached(): void
    {
        $queue = $this->createMock(Queue::class);
        $queue->expects($this->once())
            ->method('isActive')
            ->willReturn(true);
        $queue->expects($this->once())
            ->method('isOpen')
            ->willReturn(true);
        $queue->expects($this->once())
            ->method('hasServiceType')
            ->with('registration')
            ->willReturn(true);
        $queue->expects($this->any())
            ->method('getMaxTicketsPerDay')
            ->willReturn(100);

        $this->ticketRepository->expects($this->once())
            ->method('countCompletedTickets')
            ->willReturn(100);

        $this->queueRepository->expects($this->once())
            ->method('findById')
            ->with('queue-123')
            ->willReturn($queue);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Maximum tickets per day reached');

        $this->service->joinQueue(
            queueId: 'queue-123',
            serviceType: 'registration',
            citizenName: 'John Doe'
        );
    }

    public function testJoinQueueSuccessfullyCreatesTicket(): void
    {
        $queue = $this->createMock(Queue::class);
        $queue->expects($this->once())
            ->method('isActive')
            ->willReturn(true);
        $queue->expects($this->once())
            ->method('isOpen')
            ->willReturn(true);
        $queue->expects($this->once())
            ->method('hasServiceType')
            ->with('registration')
            ->willReturn(true);
        $queue->expects($this->once())
            ->method('getMaxTicketsPerDay')
            ->willReturn(null);
        $queue->expects($this->any())
            ->method('getId')
            ->willReturn('queue-123');
        $queue->expects($this->once())
            ->method('getAverageServiceTime')
            ->willReturn(null);

        $this->queueRepository->expects($this->once())
            ->method('findById')
            ->with('queue-123')
            ->willReturn($queue);

        $this->queueRepository->expects($this->once())
            ->method('incrementAndGetNextSequenceNumber')
            ->with('queue-123')
            ->willReturn(5);

        $this->ticketRepository->expects($this->once())
            ->method('save')
            ->willReturnCallback(function ($ticket) {
                $this->assertInstanceOf(QueueTicket::class, $ticket);
            });

        $result = $this->service->joinQueue(
            queueId: 'queue-123',
            serviceType: 'registration',
            citizenName: 'John Doe',
            citizenPhone: '0123456789',
            citizenId: 'citizen-123',
            priority: 0
        );

        $this->assertNotNull($result);
        $this->assertEquals('registration', $result->getServiceType());
        $this->assertEquals('John Doe', $result->getCitizenName());
        $this->assertEquals('0123456789', $result->getCitizenPhone());
        $this->assertEquals('REGISTRATION-0005', $result->getTicketNumber());
    }

    // ==================== CALL NEXT TICKET TESTS ====================

    public function testCallNextTicketReturnsNullWhenQueueNotFound(): void
    {
        $this->queueRepository->expects($this->once())
            ->method('findById')
            ->with('non-existent-queue')
            ->willReturn(null);

        $officer = $this->createMock(Officer::class);
        $result = $this->service->callNextTicket('non-existent-queue', $officer, 'Counter 1');

        $this->assertNull($result);
    }

    public function testCallNextTicketThrowsWhenQueueNotActive(): void
    {
        $queue = $this->createMock(Queue::class);
        $queue->expects($this->once())
            ->method('isActive')
            ->willReturn(false);

        $this->queueRepository->expects($this->once())
            ->method('findById')
            ->with('queue-123')
            ->willReturn($queue);

        $officer = $this->createMock(Officer::class);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Queue is not active');

        $this->service->callNextTicket('queue-123', $officer, 'Counter 1');
    }

    public function testCallNextTicketReturnsNullWhenNoWaitingTickets(): void
    {
        $queue = $this->createMock(Queue::class);
        $queue->expects($this->once())
            ->method('isActive')
            ->willReturn(true);

        $this->queueRepository->expects($this->once())
            ->method('findById')
            ->with('queue-123')
            ->willReturn($queue);

        $this->ticketRepository->expects($this->once())
            ->method('findNextWaitingTicket')
            ->with('queue-123')
            ->willReturn(null);

        $officer = $this->createMock(Officer::class);
        $result = $this->service->callNextTicket('queue-123', $officer, 'Counter 1');

        $this->assertNull($result);
    }

    public function testCallNextTicketSuccessfullyCallsTicket(): void
    {
        $queue = $this->createMock(Queue::class);
        $queue->expects($this->once())
            ->method('isActive')
            ->willReturn(true);
        $queue->expects($this->any())
            ->method('getId')
            ->willReturn('queue-123');

        $ticket = $this->createMock(QueueTicket::class);
        $ticket->expects($this->once())
            ->method('call')
            ->willReturnCallback(function ($officer, $counter) {
                $this->assertInstanceOf(Officer::class, $officer);
                $this->assertEquals('Counter 1', $counter);
            });

        $this->queueRepository->expects($this->once())
            ->method('findById')
            ->with('queue-123')
            ->willReturn($queue);

        $this->ticketRepository->expects($this->once())
            ->method('findNextWaitingTicket')
            ->with('queue-123')
            ->willReturn($ticket);

        $this->ticketRepository->expects($this->once())
            ->method('save')
            ->with($ticket);

        $officer = $this->createMock(Officer::class);
        $result = $this->service->callNextTicket('queue-123', $officer, 'Counter 1');

        $this->assertNotNull($result);
    }

    // ==================== START TICKET TESTS ====================

    public function testStartTicketReturnsNullWhenTicketNotFound(): void
    {
        $this->ticketRepository->expects($this->once())
            ->method('findById')
            ->with('non-existent-ticket')
            ->willReturn(null);

        $result = $this->service->startTicket('non-existent-ticket');

        $this->assertNull($result);
    }

    public function testStartTicketSuccessfullyStartsTicket(): void
    {
        $ticket = $this->createMock(QueueTicket::class);
        $ticket->expects($this->once())
            ->method('start');

        $this->ticketRepository->expects($this->once())
            ->method('findById')
            ->with('ticket-123')
            ->willReturn($ticket);

        $this->ticketRepository->expects($this->once())
            ->method('save')
            ->with($ticket);

        $result = $this->service->startTicket('ticket-123');

        $this->assertNotNull($result);
    }

    // ==================== COMPLETE TICKET TESTS ====================

    public function testCompleteTicketReturnsNullWhenTicketNotFound(): void
    {
        $this->ticketRepository->expects($this->once())
            ->method('findById')
            ->with('non-existent-ticket')
            ->willReturn(null);

        $result = $this->service->completeTicket('non-existent-ticket');

        $this->assertNull($result);
    }

    public function testCompleteTicketSuccessfullyCompletesTicket(): void
    {
        $queue = $this->createMock(Queue::class);
        $queue->expects($this->any())
            ->method('getTickets')
            ->willReturn(new \Doctrine\Common\Collections\ArrayCollection([]));

        $ticket = $this->createMock(QueueTicket::class);
        $ticket->expects($this->once())
            ->method('complete')
            ->with(null);
        $ticket->expects($this->any())
            ->method('getQueue')
            ->willReturn($queue);

        $this->ticketRepository->expects($this->once())
            ->method('findById')
            ->with('ticket-123')
            ->willReturn($ticket);

        $this->ticketRepository->expects($this->once())
            ->method('save')
            ->with($ticket);

        // When tickets collection is empty, updateQueueStatistics doesn't save the queue
        $this->queueRepository->expects($this->never())
            ->method('save');

        $result = $this->service->completeTicket('ticket-123');

        $this->assertNotNull($result);
    }

    public function testCompleteTicketWithNotes(): void
    {
        $queue = $this->createMock(Queue::class);
        $queue->expects($this->any())
            ->method('getTickets')
            ->willReturn(new \Doctrine\Common\Collections\ArrayCollection([]));

        $ticket = $this->createMock(QueueTicket::class);
        $ticket->expects($this->once())
            ->method('complete')
            ->with('Customer satisfied');
        $ticket->expects($this->any())
            ->method('getQueue')
            ->willReturn($queue);

        $this->ticketRepository->expects($this->once())
            ->method('findById')
            ->with('ticket-123')
            ->willReturn($ticket);

        $this->ticketRepository->expects($this->once())
            ->method('save')
            ->with($ticket);

        // When tickets collection is empty, updateQueueStatistics doesn't save the queue
        $this->queueRepository->expects($this->never())
            ->method('save');

        $result = $this->service->completeTicket('ticket-123', 'Customer satisfied');

        $this->assertNotNull($result);
    }

    // ==================== CANCEL TICKET TESTS ====================

    public function testCancelTicketReturnsNullWhenTicketNotFound(): void
    {
        $this->ticketRepository->expects($this->once())
            ->method('findById')
            ->with('non-existent-ticket')
            ->willReturn(null);

        $result = $this->service->cancelTicket('non-existent-ticket');

        $this->assertNull($result);
    }

    public function testCancelTicketSuccessfullyCancelsTicket(): void
    {
        $ticket = $this->createMock(QueueTicket::class);
        $ticket->expects($this->once())
            ->method('cancel')
            ->with(null);

        $this->ticketRepository->expects($this->once())
            ->method('findById')
            ->with('ticket-123')
            ->willReturn($ticket);

        $this->ticketRepository->expects($this->once())
            ->method('save')
            ->with($ticket);

        $result = $this->service->cancelTicket('ticket-123');

        $this->assertNotNull($result);
    }

    public function testCancelTicketWithReason(): void
    {
        $ticket = $this->createMock(QueueTicket::class);
        $ticket->expects($this->once())
            ->method('cancel')
            ->with('Customer requested cancellation');

        $this->ticketRepository->expects($this->once())
            ->method('findById')
            ->with('ticket-123')
            ->willReturn($ticket);

        $this->ticketRepository->expects($this->once())
            ->method('save')
            ->with($ticket);

        $result = $this->service->cancelTicket('ticket-123', 'Customer requested cancellation');

        $this->assertNotNull($result);
    }

    // ==================== SKIP TICKET TESTS ====================

    public function testSkipTicketReturnsNullWhenTicketNotFound(): void
    {
        $this->ticketRepository->expects($this->once())
            ->method('findById')
            ->with('non-existent-ticket')
            ->willReturn(null);

        $result = $this->service->skipTicket('non-existent-ticket');

        $this->assertNull($result);
    }

    public function testSkipTicketSuccessfullySkipsTicket(): void
    {
        $ticket = $this->createMock(QueueTicket::class);
        $ticket->expects($this->once())
            ->method('skip');

        $this->ticketRepository->expects($this->once())
            ->method('findById')
            ->with('ticket-123')
            ->willReturn($ticket);

        $this->ticketRepository->expects($this->once())
            ->method('save')
            ->with($ticket);

        $result = $this->service->skipTicket('ticket-123');

        $this->assertNotNull($result);
    }

    // ==================== GET TICKET TESTS ====================

    public function testGetTicketReturnsNullWhenNotFound(): void
    {
        $this->ticketRepository->expects($this->once())
            ->method('findById')
            ->with('non-existent-ticket')
            ->willReturn(null);

        $result = $this->service->getTicket('non-existent-ticket');

        $this->assertNull($result);
    }

    public function testGetTicketReturnsTicketWhenFound(): void
    {
        $ticket = $this->createMock(QueueTicket::class);

        $this->ticketRepository->expects($this->once())
            ->method('findById')
            ->with('ticket-123')
            ->willReturn($ticket);

        $result = $this->service->getTicket('ticket-123');

        $this->assertNotNull($result);
        $this->assertEquals($ticket, $result);
    }

    public function testGetTicketByNumberReturnsNullWhenNotFound(): void
    {
        $this->ticketRepository->expects($this->once())
            ->method('findByTicketNumber')
            ->with('REGISTRATION-0001')
            ->willReturn(null);

        $result = $this->service->getTicketByNumber('REGISTRATION-0001');

        $this->assertNull($result);
    }

    public function testGetTicketByNumberReturnsTicketWhenFound(): void
    {
        $ticket = $this->createMock(QueueTicket::class);

        $this->ticketRepository->expects($this->once())
            ->method('findByTicketNumber')
            ->with('REGISTRATION-0001')
            ->willReturn($ticket);

        $result = $this->service->getTicketByNumber('REGISTRATION-0001');

        $this->assertNotNull($result);
        $this->assertEquals($ticket, $result);
    }

    // ==================== QUEUE MANAGEMENT TESTS ====================

    public function testGetQueueReturnsNullWhenNotFound(): void
    {
        $this->queueRepository->expects($this->once())
            ->method('findById')
            ->with('non-existent-queue')
            ->willReturn(null);

        $result = $this->service->getQueue('non-existent-queue');

        $this->assertNull($result);
    }

    public function testGetQueueReturnsQueueWhenFound(): void
    {
        $queue = $this->createMock(Queue::class);

        $this->queueRepository->expects($this->once())
            ->method('findById')
            ->with('queue-123')
            ->willReturn($queue);

        $result = $this->service->getQueue('queue-123');

        $this->assertNotNull($result);
        $this->assertEquals($queue, $result);
    }

    public function testActivateQueueReturnsNullWhenNotFound(): void
    {
        $this->queueRepository->expects($this->once())
            ->method('findById')
            ->with('non-existent-queue')
            ->willReturn(null);

        $result = $this->service->activateQueue('non-existent-queue');

        $this->assertNull($result);
    }

    public function testActivateQueueSuccessfullyActivatesQueue(): void
    {
        $queue = $this->createMock(Queue::class);
        $queue->expects($this->once())
            ->method('activate');

        $this->queueRepository->expects($this->once())
            ->method('findById')
            ->with('queue-123')
            ->willReturn($queue);

        $this->queueRepository->expects($this->once())
            ->method('save')
            ->with($queue);

        $result = $this->service->activateQueue('queue-123');

        $this->assertNotNull($result);
    }

    public function testDeactivateQueueSuccessfullyDeactivatesQueue(): void
    {
        $queue = $this->createMock(Queue::class);
        $queue->expects($this->once())
            ->method('deactivate');

        $this->queueRepository->expects($this->once())
            ->method('findById')
            ->with('queue-123')
            ->willReturn($queue);

        $this->queueRepository->expects($this->once())
            ->method('save')
            ->with($queue);

        $result = $this->service->deactivateQueue('queue-123');

        $this->assertNotNull($result);
    }

    public function testGetActiveQueuesReturnsAllActiveQueues(): void
    {
        $queue1 = $this->createMock(Queue::class);
        $queue2 = $this->createMock(Queue::class);

        $this->queueRepository->expects($this->once())
            ->method('findActiveQueues')
            ->willReturn([$queue1, $queue2]);

        $result = $this->service->getActiveQueues();

        $this->assertCount(2, $result);
    }

    public function testGetOpenQueuesReturnsAllOpenQueues(): void
    {
        $queue = $this->createMock(Queue::class);

        $this->queueRepository->expects($this->once())
            ->method('findOpenQueues')
            ->willReturn([$queue]);

        $result = $this->service->getOpenQueues();

        $this->assertCount(1, $result);
    }
}