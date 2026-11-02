<?php declare(strict_types=1);

namespace App\Tests\Unit\Queue\Domain\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Queue\Domain\Entity\Queue;
use App\Queue\Domain\Entity\QueueTicket;
use App\Government\Domain\Entity\Officer;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class QueueTicketTest extends TestCase
{
    private function createQueue(?Queue $queue = null): Queue
    {
        if ($queue === null) {
            $queue = new Queue('q-123', 'Test Queue', 'W001', ['GENERAL']);
        }
        return $queue;
    }

    private function createOfficerMock(?string $name = 'Officer'): Officer&MockObject
    {
        $officer = $this->createMock(Officer::class);
        // The ticket uses $officer only for storing reference; later toArray uses getFullName().
        $officer->method('getFullName')->willReturn($name);
        return $officer;
    }

    public function testConstructorInitializesDefaults(): void
    {
        $queue = $this->createQueue();
        $ticket = new QueueTicket('t-1', $queue, 'GENERAL', 'Citizen One');
        $this->assertSame('t-1', $ticket->getId());
        $this->assertSame($queue, $ticket->getQueue());
        $this->assertSame('GENERAL', $ticket->getServiceType());
        $this->assertSame('Citizen One', $ticket->getCitizenName());
        $this->assertNull($ticket->getCitizen());
        $this->assertNull($ticket->getCitizenPhone());
        $this->assertNull($ticket->getServedByOfficer());
        $this->assertNull($ticket->getCounterNumber());
        $this->assertSame(QueueTicket::STATUS_WAITING, $ticket->getStatus());
        $this->assertTrue($ticket->isWaiting());
        $this->assertSame(0, $ticket->getPriority());
        $this->assertNull($ticket->getEstimatedWaitTime());
        $this->assertNull($ticket->getCalledAt());
        $this->assertNull($ticket->getStartedAt());
        $this->assertNull($ticket->getCompletedAt());
        $this->assertNull($ticket->getCancelledAt());
        $this->assertNull($ticket->getNotes());
        $this->assertInstanceOf(DateTimeImmutable::class, $ticket->getCreatedAt());
        $this->assertNotNull($ticket->getTicketNumber()); // auto-generated
        $this->assertStringStartsWith('GENERAL-', $ticket->getTicketNumber());
    }

    public function testSetCitizen(): void
    {
        $queue = $this->createQueue();
        $ticket = new QueueTicket('t-2', $queue, 'GEN', 'Name', null);
        $citizen = $this->createMock(\App\Government\Domain\Entity\Citizen::class);
        $ticket->setCitizen($citizen);
        $this->assertSame($citizen, $ticket->getCitizen());
        $this->assertNotNull($ticket->getUpdatedAt());
    }

    public function testSetCitizenName(): void
    {
        $queue = $this->createQueue();
        $ticket = new QueueTicket('t-3', $queue, 'GEN', 'Old');
        $ticket->setCitizenName('New Name');
        $this->assertSame('New Name', $ticket->getCitizenName());
        $this->assertNotNull($ticket->getUpdatedAt());
    }

    public function testSetCitizenPhone(): void
    {
        $queue = $this->createQueue();
        $ticket = new QueueTicket('t-4', $queue, 'GEN', 'Name', '123');
        $ticket->setCitizenPhone('456');
        $this->assertSame('456', $ticket->getCitizenPhone());
        $this->assertNotNull($ticket->getUpdatedAt());
    }

    public function testSetPriority(): void
    {
        $queue = $this->createQueue();
        $ticket = new QueueTicket('t-5', $queue, 'GEN', 'Name');
        $ticket->setPriority(5);
        $this->assertSame(5, $ticket->getPriority());
        $this->assertNotNull($ticket->getUpdatedAt());
    }

    public function testSetEstimatedWaitTime(): void
    {
        $queue = $this->createQueue();
        $ticket = new QueueTicket('t-6', $queue, 'GEN', 'Name');
        $ticket->setEstimatedWaitTime(30);
        $this->assertSame(30, $ticket->getEstimatedWaitTime());
        $this->assertNotNull($ticket->getUpdatedAt());
    }

    public function testCall(): void
    {
        $queue = $this->createQueue();
        $ticket = new QueueTicket('t-7', $queue, 'GEN', 'Name');
        $officer = $this->createOfficerMock('Officer A');
        $ticket->call($officer, 'Counter-1');
        $this->assertSame(QueueTicket::STATUS_CALLED, $ticket->getStatus());
        $this->assertTrue($ticket->isCalled());
        $this->assertSame($officer, $ticket->getServedByOfficer());
        $this->assertSame('Counter-1', $ticket->getCounterNumber());
        $this->assertInstanceOf(DateTimeImmutable::class, $ticket->getCalledAt());
        $this->assertNotNull($ticket->getUpdatedAt());
    }

    public function testStartThrowsIfNotCalled(): void
    {
        $queue = $this->createQueue();
        $ticket = new QueueTicket('t-8', $queue, 'GEN', 'Name');
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Can only start a called ticket');
        $ticket->start();
    }

    public function testStart(): void
    {
        $queue = $this->createQueue();
        $ticket = new QueueTicket('t-9', $queue, 'GEN', 'Name');
        $officer = $this->createOfficerMock();
        $ticket->call($officer, 'C1');
        $ticket->start();
        $this->assertSame(QueueTicket::STATUS_IN_PROGRESS, $ticket->getStatus());
        $this->assertTrue($ticket->isInProgress());
        $this->assertInstanceOf(DateTimeImmutable::class, $ticket->getStartedAt());
        $this->assertNotNull($ticket->getUpdatedAt());
    }

    public function testCompleteThrowsIfNotInProgress(): void
    {
        $queue = $this->createQueue();
        $ticket = new QueueTicket('t-10', $queue, 'GEN', 'Name');
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Can only complete a ticket in progress');
        $ticket->complete('done');
    }

    public function testComplete(): void
    {
        $queue = $this->createQueue();
        $ticket = new QueueTicket('t-11', $queue, 'GEN', 'Name');
        $officer = $this->createOfficerMock();
        $ticket->call($officer, 'C2');
        $ticket->start();
        $ticket->complete('Service provided');
        $this->assertSame(QueueTicket::STATUS_COMPLETED, $ticket->getStatus());
        $this->assertTrue($ticket->isCompleted());
        $this->assertInstanceOf(DateTimeImmutable::class, $ticket->getCompletedAt());
        $this->assertSame('Service provided', $ticket->getNotes());
        $this->assertNotNull($ticket->getUpdatedAt());
    }

    public function testCancelThrowsIfCompletedOrCancelled(): void
    {
        $queue = $this->createQueue();
        $ticket = new QueueTicket('t-12', $queue, 'GEN', 'Name');
        // cancel from waiting should succeed? Actually cancel allows waiting? Let's check code: cancel() throws if status is completed or cancelled. So waiting can be cancelled.
        // But test for completed: first complete then cancel.
        $officer = $this->createOfficerMock();
        $ticket->call($officer, 'C3');
        $ticket->start();
        $ticket->complete();
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot cancel completed or already cancelled ticket');
        $ticket->cancel('should fail');
    }

    public function testCancel(): void
    {
        $queue = $this->createQueue();
        $ticket = new QueueTicket('t-13', $queue, 'GEN', 'Name');
        // cancel from waiting
        $ticket->cancel('No show');
        $this->assertSame(QueueTicket::STATUS_CANCELLED, $ticket->getStatus());
        $this->assertTrue($ticket->isCancelled());
        $this->assertInstanceOf(DateTimeImmutable::class, $ticket->getCancelledAt());
        $this->assertSame('No show', $ticket->getNotes());
        $this->assertNotNull($ticket->getUpdatedAt());
    }

    public function testSkipThrowsIfNotCalled(): void
    {
        $queue = $this->createQueue();
        $ticket = new QueueTicket('t-14', $queue, 'GEN', 'Name');
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Can only skip a called ticket');
        $ticket->skip();
    }

    public function testSkip(): void
    {
        $queue = $this->createQueue();
        $ticket = new QueueTicket('t-15', $queue, 'GEN', 'Name');
        $officer = $this->createOfficerMock();
        $ticket->call($officer, 'C4');
        $ticket->skip();
        $this->assertSame(QueueTicket::STATUS_SKIPPED, $ticket->getStatus());
        $this->assertTrue($ticket->isSkipped());
        $this->assertNull($ticket->getCalledAt());
        $this->assertNull($ticket->getCounterNumber());
        $this->assertNull($ticket->getServedByOfficer());
        $this->assertNotNull($ticket->getUpdatedAt());
    }

    public function testIsWaiting(): void
    {
        $queue = $this->createQueue();
        $ticket = new QueueTicket('t-16', $queue, 'GEN', 'Name');
        $this->assertTrue($ticket->isWaiting());
        $ticket->call($this->createOfficerMock(), 'C5');
        $this->assertFalse($ticket->isWaiting());
    }

    public function testIsCalled(): void
    {
        $queue = $this->createQueue();
        $ticket = new QueueTicket('t-17', $queue, 'GEN', 'Name');
        $this->assertFalse($ticket->isCalled());
        $ticket->call($this->createOfficerMock(), 'C6');
        $this->assertTrue($ticket->isCalled());
    }

    public function testIsInProgress(): void
    {
        $queue = $this->createQueue();
        $ticket = new QueueTicket('t-18', $queue, 'GEN', 'Name');
        $officer = $this->createOfficerMock();
        $ticket->call($officer, 'C7');
        $this->assertFalse($ticket->isInProgress());
        $ticket->start();
        $this->assertTrue($ticket->isInProgress());
    }

    public function testIsCompleted(): void
    {
        $queue = $this->createQueue();
        $ticket = new QueueTicket('t-19', $queue, 'GEN', 'Name');
        $officer = $this->createOfficerMock();
        $ticket->call($officer, 'C8');
        $ticket->start();
        $this->assertFalse($ticket->isCompleted());
        $ticket->complete();
        $this->assertTrue($ticket->isCompleted());
    }

    public function testIsCancelled(): void
    {
        $queue = $this->createQueue();
        $ticket = new QueueTicket('t-20', $queue, 'GEN', 'Name');
        $this->assertFalse($ticket->isCancelled());
        $ticket->cancel();
        $this->assertTrue($ticket->isCancelled());
    }

    public function testIsSkipped(): void
    {
        $queue = $this->createQueue();
        $ticket = new QueueTicket('t-21', $queue, 'GEN', 'Name');
        $this->assertFalse($ticket->isSkipped());
        $ticket->call($this->createOfficerMock(), 'C9');
        $ticket->skip();
        $this->assertTrue($ticket->isSkipped());
    }

    public function testGetWaitingDuration(): void
    {
        $queue = $this->createQueue();
        // For waiting, calledAt is null -> null
        $ticket = new QueueTicket('t-22', $queue, 'GEN', 'Name');
        $this->assertNull($ticket->getWaitingDuration());
        // after called, waiting duration is difference between calledAt and createdAt (minutes)
        $officer = $this->createOfficerMock();
        // We'll need to manipulate time? The method calculates: (calledAt->getTimestamp() - createdAt->getTimestamp())/60.
        // Since we cannot control now, we can approximate: but we can compute expected using actual values.
        $ticket->call($officer, 'C10');
        $waiting = $ticket->getWaitingDuration();
        $this->assertIsInt($waiting);
        $this->assertGreaterThanOrEqual(0, $waiting);
    }

    public function testGetServiceDuration(): void
    {
        $queue = $this->createQueue();
        $ticket = new QueueTicket('t-23', $queue, 'GEN', 'Name');
        $officer = $this->createOfficerMock();
        $ticket->call($officer, 'C11');
        $ticket->start();
        // before complete -> null
        $this->assertNull($ticket->getServiceDuration());
        // now complete; need to set completedAt in the past? Can't; it uses current time.
        // We'll just call complete and get duration; should be >0 or 0 depending on speed.
        $ticket->complete();
        $duration = $ticket->getServiceDuration();
        $this->assertIsInt($duration);
        $this->assertGreaterThanOrEqual(0, $duration);
    }

    public function testSetTicketNumber(): void
    {
        $queue = $this->createQueue();
        $ticket = new QueueTicket('t-24', $queue, 'GEN', 'Name');
        $newNumber = 'CUSTOM-001';
        $ticket->setTicketNumber($newNumber);
        $this->assertSame($newNumber, $ticket->getTicketNumber());
    }

    public function testToArrayStructure(): void
    {
        $queue = $this->createQueue();
        $queue->setName('My Queue'); // void, separate
        $officer = $this->createOfficerMock('John Doe');
        $ticket = new QueueTicket('t-25', $queue, 'GEN', 'Citizen X', '123456', 2);
        $ticket->call($officer, 'C12');
        $ticket->start();
        $ticket->complete('All good');
        $arr = $ticket->toArray();
        $this->assertIsArray($arr);
        $this->assertArrayHasKey('id', $arr);
        $this->assertArrayHasKey('ticketNumber', $arr);
        $this->assertArrayHasKey('queueId', $arr);
        $this->assertArrayHasKey('queueName', $arr);
        $this->assertArrayHasKey('serviceType', $arr);
        $this->assertArrayHasKey('citizenName', $arr);
        $this->assertArrayHasKey('status', $arr);
        $this->assertArrayHasKey('waitingDuration', $arr);
        $this->assertArrayHasKey('serviceDuration', $arr);
        $this->assertSame('t-25', $arr['id']);
        $this->assertSame($queue->getId(), $arr['queueId']);
        $this->assertSame('My Queue', $arr['queueName']);
        $this->assertSame('GEN', $arr['serviceType']);
        $this->assertSame('Citizen X', $arr['citizenName']);
        $this->assertSame(2, $arr['priority']);
        $this->assertSame('completed', $arr['status']);
        $this->assertSame('John Doe', $arr['servedByOfficer']);
        $this->assertSame('C12', $arr['counterNumber']);
        $this->assertSame('All good', $arr['notes']);
    }
}
