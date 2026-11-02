<?php declare(strict_types=1);

namespace App\Tests\Unit\Queue\Domain\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Queue\Domain\Entity\Queue;
use App\Queue\Domain\Entity\QueueTicket;
use App\Government\Domain\Entity\Officer;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class QueueTest extends TestCase
{
    private function createQueue(array $serviceTypes = ['GENERAL'], ?string $departmentName = null): Queue
    {
        $id = 'queue-' . bin2hex(random_bytes(8));
        $name = 'Test Queue';
        $wardCode = 'W001';
        return new Queue($id, $name, $wardCode, $serviceTypes, $departmentName);
    }

    private function createOfficer(string $id = 'off-123', string $name = ' Officer One'): Officer
    {
        // Create a minimal Officer; however Officer entity may have required fields. We'll mock instead.
        return $this->createMock(Officer::class);
    }

    public function testConstructorInitializesDefaults(): void
    {
        $queue = $this->createQueue();
        $this->assertNotNull($queue->getId());
        $this->assertSame('Test Queue', $queue->getName());
        $this->assertSame('W001', $queue->getWardCode());
        $this->assertSame(['GENERAL'], $queue->getServiceTypes());
        $this->assertNull($queue->getDepartmentName());
        $this->assertSame(Queue::STATUS_ACTIVE, $queue->getStatus());
        $this->assertTrue($queue->isActive());
        $this->assertSame(1, $queue->getCounterCount());
        $this->assertSame(0, $queue->getCurrentSequence());
        $this->assertEmpty($queue->getTickets()->toArray());
        $this->assertEmpty($queue->getAssignedOfficers()->toArray());
        $this->assertInstanceOf(DateTimeImmutable::class, $queue->getCreatedAt());
        $this->assertNull($queue->getUpdatedAt());
    }

    public function testSetName(): void
    {
        $queue = $this->createQueue();
        $queue->setName('New Name');
        $this->assertSame('New Name', $queue->getName());
        $this->assertInstanceOf(DateTimeImmutable::class, $queue->getUpdatedAt());
    }

    public function testSetDepartmentName(): void
    {
        $queue = $this->createQueue();
        $queue->setDepartmentName('Dept A');
        $this->assertSame('Dept A', $queue->getDepartmentName());
        $this->assertNotNull($queue->getUpdatedAt());
    }

    public function testSetDescription(): void
    {
        $queue = $this->createQueue();
        $queue->setDescription('Desc');
        $this->assertSame('Desc', $queue->getDescription());
        $this->assertNotNull($queue->getUpdatedAt());
    }

    public function testActivateDeactivateClose(): void
    {
        $queue = $this->createQueue();
        $queue->deactivate();
        $this->assertSame(Queue::STATUS_INACTIVE, $queue->getStatus());
        $this->assertFalse($queue->isActive());
        $queue->activate();
        $this->assertSame(Queue::STATUS_ACTIVE, $queue->getStatus());
        $this->assertTrue($queue->isActive());
        $queue->close();
        $this->assertSame(Queue::STATUS_CLOSED, $queue->getStatus());
        $this->assertFalse($queue->isActive());
    }

    public function testServiceTypeMethods(): void
    {
        $queue = $this->createQueue(['GENERAL']);
        $this->assertTrue($queue->hasServiceType('GENERAL'));
        $this->assertFalse($queue->hasServiceType('OTHER'));
        $queue->addServiceType('OTHER');
        $this->assertTrue($queue->hasServiceType('OTHER'));
        $this->assertCount(2, $queue->getServiceTypes());
        // duplicate
        $queue->addServiceType('GENERAL');
        $this->assertCount(2, $queue->getServiceTypes());
        $queue->removeServiceType('GENERAL');
        $this->assertFalse($queue->hasServiceType('GENERAL'));
        $this->assertCount(1, $queue->getServiceTypes());
    }

    public function testCounterCount(): void
    {
        $queue = $this->createQueue();
        $queue->setCounterCount(5);
        $this->assertSame(5, $queue->getCounterCount());
        // ensure minimum 1
        $queue->setCounterCount(0);
        $this->assertSame(1, $queue->getCounterCount());
        $queue->setCounterCount(-2);
        $this->assertSame(1, $queue->getCounterCount());
    }

    public function testSequence(): void
    {
        $queue = $this->createQueue();
        $this->assertSame(0, $queue->getCurrentSequence());
        $next1 = $queue->getNextSequenceNumber();
        $this->assertSame(1, $next1);
        $this->assertSame(1, $queue->getCurrentSequence());
        $next2 = $queue->getNextSequenceNumber();
        $this->assertSame(2, $next2);
        $this->assertSame(2, $queue->getCurrentSequence());
        // setSequence also works
        $queue->setCurrentSequence(100);
        $this->assertSame(100, $queue->getCurrentSequence());
    }

    public function testOperatingHours(): void
    {
        $queue = $this->createQueue();
        $hours = [
            1 => ['open' => '08:00', 'close' => '17:00'], // Monday
            2 => ['open' => '08:00', 'close' => '17:00'],
            // other days missing
        ];
        $queue->setOperatingHours($hours);
        $this->assertSame($hours, $queue->getOperatingHours());
        $this->assertNotNull($queue->getUpdatedAt());
    }

    public function testIsOpenWhenInactive(): void
    {
        $queue = $this->createQueue();
        $queue->deactivate();
        $this->assertFalse($queue->isActive());
        $this->assertFalse($queue->isOpen());
    }

    public function testIsOpenWhenNoOperatingHours(): void
    {
        $queue = $this->createQueue();
        $queue->setOperatingHours(null);
        $this->assertTrue($queue->isActive());
        $this->assertFalse($queue->isOpen());
    }

    public function testIsOpenWhenDayNotConfigured(): void
    {
        $queue = $this->createQueue();
        // Set operating hours for Monday (1) only, covering the full day.
        // This ensures that on Monday the queue is open regardless of actual time of test execution.
        $hours = [
            1 => ['open' => '00:00', 'close' => '23:59'],
        ];
        $queue->setOperatingHours($hours);
        $today = (int)(new DateTimeImmutable())->format('N');
        if ($today !== 1) {
            $this->assertFalse($queue->isOpen());
        } else {
            $this->assertTrue($queue->isOpen()); // today is Monday, open
        }
    }

    public function testIsOpenWhenOutsideHours(): void
    {
        $queue = $this->createQueue();
        $now = new DateTimeImmutable();
        // Set open time 2 hours from now, close 3 hours from now -> currently closed
        $open = $now->modify('+2 hours')->format('H:i');
        $close = $now->modify('+3 hours')->format('H:i');
        $dayOfWeek = (int)$now->format('N');
        $hours = [
            $dayOfWeek => ['open' => $open, 'close' => $close],
        ];
        $queue->setOperatingHours($hours);
        $this->assertFalse($queue->isOpen());
    }

    public function testIsOpenWhenDuringBreak(): void
    {
        $queue = $this->createQueue();
        $now = new DateTimeImmutable();
        $dayOfWeek = (int)$now->format('N');
        // Open all day (00:00-23:59) but break from 12:00-13:00.
        // If now is around noon, likely in break. But we cannot guarantee.
        // Instead, set break to cover entire open period, so it's always on break.
        $hours = [
            $dayOfWeek => [
                'open' => '00:00',
                'close' => '23:59',
                'breakStart' => '00:00',
                'breakEnd' => '23:59',
            ],
        ];
        $queue->setOperatingHours($hours);
        $this->assertFalse($queue->isOpen());
    }

    public function testIsOpenWhenOpen(): void
    {
        $queue = $this->createQueue();
        $now = new DateTimeImmutable();
        $dayOfWeek = (int)$now->format('N');
        // Open all day long
        $hours = [
            $dayOfWeek => ['open' => '00:00', 'close' => '23:59'],
        ];
        $queue->setOperatingHours($hours);
        $this->assertTrue($queue->isOpen());
    }

    public function testSetAverageServiceTime(): void
    {
        $queue = $this->createQueue();
        $queue->setAverageServiceTime(15);
        $this->assertSame(15, $queue->getAverageServiceTime());
        $this->assertNotNull($queue->getUpdatedAt());
    }

    public function testSetMaxTicketsPerDay(): void
    {
        $queue = $this->createQueue();
        $queue->setMaxTicketsPerDay(100);
        $this->assertSame(100, $queue->getMaxTicketsPerDay());
        $this->assertNotNull($queue->getUpdatedAt());
    }

    public function testAddTicket(): void
    {
        $queue = $this->createQueue();
        $ticket = new QueueTicket(
            'ticket-123',
            $queue,
            'GENERAL',
            'John Doe'
        );
        $queue->addTicket($ticket);
        $this->assertCount(1, $queue->getTickets());
        $this->assertTrue($queue->getTickets()->contains($ticket));
        // adding same ticket again doesn't duplicate
        $queue->addTicket($ticket);
        $this->assertCount(1, $queue->getTickets());
        $this->assertNotNull($queue->getUpdatedAt());
    }

    public function testGetWaitingTickets(): void
    {
        $queue = $this->createQueue();
        // Create mocks for tickets with different status
        $waitingMock = $this->createMock(QueueTicket::class);
        $waitingMock->method('isWaiting')->willReturn(true);
        $waitingMock->method('isInProgress')->willReturn(false);
        $inProgressMock = $this->createMock(QueueTicket::class);
        $inProgressMock->method('isWaiting')->willReturn(false);
        $inProgressMock->method('isInProgress')->willReturn(true);
        // Add to collection directly
        $queue->getTickets()->add($waitingMock);
        $queue->getTickets()->add($inProgressMock);
        $waitingList = $queue->getWaitingTickets();
        $inProgressList = $queue->getInProgressTickets();
        $this->assertCount(1, $waitingList);
        $this->assertContains($waitingMock, $waitingList);
        $this->assertCount(1, $inProgressList);
        $this->assertContains($inProgressMock, $inProgressList);
    }

    public function testGetWaitingTicketCount(): void
    {
        $queue = $this->createQueue();
        // Add 3 waiting tickets (default status)
        for ($i = 0; $i < 3; $i++) {
            $ticket = new QueueTicket("t-$i", $queue, 'GEN', "Name $i");
            $queue->addTicket($ticket);
        }
        $this->assertSame(3, $queue->getWaitingTicketCount());
    }

    public function testGetAverageWaitTime(): void
    {
        $queue = $this->createQueue();
        // Create tickets with various waiting durations
        // We'll create mocks to control getWaitingDuration
        $ticket1 = $this->createMock(QueueTicket::class);
        $ticket1->method('isCompleted')->willReturn(true);
        $ticket1->method('getWaitingDuration')->willReturn(10);
        $ticket2 = $this->createMock(QueueTicket::class);
        $ticket2->method('isCompleted')->willReturn(true);
        $ticket2->method('getWaitingDuration')->willReturn(20);
        $ticket3 = $this->createMock(QueueTicket::class);
        $ticket3->method('isCompleted')->willReturn(false);
        $ticket3->method('getWaitingDuration')->willReturn(null);
        // Add to collection
        $queue->getTickets()->add($ticket1);
        $queue->getTickets()->add($ticket2);
        $queue->getTickets()->add($ticket3);
        $avg = $queue->getAverageWaitTime();
        $this->assertSame(15, $avg); // (10+20)/2
    }

    public function testGetAverageWaitTimeReturnsNullWhenNoCompleted(): void
    {
        $queue = $this->createQueue();
        $ticket = $this->createMock(QueueTicket::class);
        $ticket->method('isCompleted')->willReturn(false);
        $queue->getTickets()->add($ticket);
        $this->assertNull($queue->getAverageWaitTime());
    }

    public function testAssignOfficer(): void
    {
        $queue = $this->createQueue();
        $officer = $this->createOfficer();
        $officer->method('getFullName')->willReturn('John Officer');
        $queue->assignOfficer($officer);
        $this->assertCount(1, $queue->getAssignedOfficers());
        $this->assertTrue($queue->getAssignedOfficers()->contains($officer));
        // assign same officer again -> no duplicate
        $queue->assignOfficer($officer);
        $this->assertCount(1, $queue->getAssignedOfficers());
        $this->assertNotNull($queue->getUpdatedAt());
    }

    public function testRemoveOfficer(): void
    {
        $queue = $this->createQueue();
        $officer = $this->createOfficer();
        $queue->assignOfficer($officer);
        $this->assertCount(1, $queue->getAssignedOfficers());
        $queue->removeOfficer($officer);
        $this->assertCount(0, $queue->getAssignedOfficers());
        $this->assertNotNull($queue->getUpdatedAt());
    }

    public function testToArrayStructure(): void
    {
        $queue = $this->createQueue(['GENERAL', 'VIP']);
        $queue->setDescription('Desc');
        $queue->setCounterCount(3);
        $queue->setAverageServiceTime(20);
        $queue->setMaxTicketsPerDay(200);
        $queue->setOperatingHours([
            1 => ['open' => '08:00', 'close' => '17:00'],
        ]);
        // Add a waiting ticket
        $ticket = new QueueTicket('t1', $queue, 'GENERAL', 'Citizen A');
        $queue->addTicket($ticket);
        // Assign an officer mock
        $officer = $this->createOfficer();
        $officer->method('getFullName')->willReturn('Officer A');
        $queue->assignOfficer($officer);

        $arr = $queue->toArray();

        $this->assertIsArray($arr);
        $this->assertArrayHasKey('id', $arr);
        $this->assertArrayHasKey('name', $arr);
        $this->assertArrayHasKey('wardCode', $arr);
        $this->assertArrayHasKey('status', $arr);
        $this->assertArrayHasKey('isOpen', $arr);
        $this->assertArrayHasKey('waitingTicketCount', $arr);
        $this->assertArrayHasKey('assignedOfficers', $arr);
        $this->assertSame('Test Queue', $arr['name']);
        $this->assertSame('W001', $arr['wardCode']);
        $this->assertSame(['GENERAL', 'VIP'], $arr['serviceTypes']);
        $this->assertSame(3, $arr['counterCount']);
        $this->assertSame(20, $arr['averageServiceTime']);
        $this->assertSame(200, $arr['maxTicketsPerDay']);
        $this->assertSame(1, $arr['waitingTicketCount']);
        $this->assertContains('Officer A', $arr['assignedOfficers']);
    }
}
