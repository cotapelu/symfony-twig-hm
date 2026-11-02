<?php declare(strict_types=1);

namespace App\Tests\Unit\CustomerService\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\CustomerService\Service\CustomerServiceService;
use App\CustomerService\Repository\TicketRepositoryInterface;
use App\CustomerService\Entity\Ticket;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class CustomerServiceServiceTest extends TestCase
{
    private TicketRepositoryInterface&MockObject $repository;
    private CustomerServiceService $service;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(TicketRepositoryInterface::class);
        $this->service = new CustomerServiceService($this->repository);
    }

    public function testCreateTicketSavesAndReturnsTicket(): void
    {
        $userId = 100;
        $subject = 'Test subject';
        $category = 'billing';

        $ticket = new Ticket($userId, $subject, $category);
        // Simulate saved ticket with ID
        $ticket->setId(1);

        $this->repository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Ticket $t) use ($userId, $subject, $category) {
                return $t->getUserId() === $userId
                    && $t->getSubject() === $subject
                    && $t->getCategory() === $category;
            }))
            ->willReturn($ticket);

        $result = $this->service->createTicket($userId, $subject, $category);
        $this->assertSame($ticket, $result);
    }

    public function testGetTicketReturnsTicket(): void
    {
        $ticket = new Ticket(1, 'Subj', 'cat');
        $ticket->setId(10);
        $this->repository->expects($this->once())
            ->method('findById')
            ->with(10)
            ->willReturn($ticket);

        $result = $this->service->getTicket(10);
        $this->assertSame($ticket, $result);
    }

    public function testGetTicketReturnsNullWhenNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $result = $this->service->getTicket(999);
        $this->assertNull($result);
    }

    public function testGetUserTicketsReturnsTickets(): void
    {
        $tickets = [new Ticket(1, 'A', 'cat'), new Ticket(1, 'B', 'cat')];
        $this->repository->expects($this->once())
            ->method('findByUserId')
            ->with(1)
            ->willReturn($tickets);

        $result = $this->service->getUserTickets(1);
        $this->assertSame($tickets, $result);
    }

    public function testGetAllTicketsWithNoFilterReturnsAll(): void
    {
        // Implementation loops over all statuses and merges results.
        $openTicket = new Ticket(1, 'Open', 'cat');
        // Expect findByStatus to be called 5 times, returning one ticket for first call and empty for others.
        $this->repository->expects($this->exactly(5))
            ->method('findByStatus')
            ->willReturnOnConsecutiveCalls(
                [$openTicket],
                [], [], [], []
            );

        $result = $this->service->getAllTickets();
        $this->assertCount(1, $result);
        $this->assertSame($openTicket, $result[0]);
    }

    public function testGetAllTicketsFiltersByStatus(): void
    {
        $tickets = [new Ticket(1, 'S', 'cat')];
        $this->repository->expects($this->once())
            ->method('findByStatus')
            ->with('open')
            ->willReturn($tickets);

        $result = $this->service->getAllTickets(['status' => 'open']);
        $this->assertSame($tickets, $result);
    }

    public function testGetAllTicketsFiltersByPriority(): void
    {
        $tickets = [new Ticket(1, 'S', 'cat')];
        $this->repository->expects($this->once())
            ->method('findByPriority')
            ->with('high')
            ->willReturn($tickets);

        $result = $this->service->getAllTickets(['priority' => 'high']);
        $this->assertSame($tickets, $result);
    }

    public function testGetAllTicketsFiltersByCategory(): void
    {
        $tickets = [new Ticket(1, 'S', 'billing')];
        $this->repository->expects($this->once())
            ->method('findByCategory')
            ->with('billing')
            ->willReturn($tickets);

        $result = $this->service->getAllTickets(['category' => 'billing']);
        $this->assertSame($tickets, $result);
    }

    public function testUpdateStatusUpdatesAndSavesWhenTicketFound(): void
    {
        $ticket = new Ticket(1, 'Subj', 'cat');
        $ticket->setId(10);
        $this->repository->expects($this->once())
            ->method('findById')
            ->with(10)
            ->willReturn($ticket);
        $this->repository->expects($this->once())
            ->method('save')
            ->with($ticket)
            ->willReturnArgument(0);

        $result = $this->service->updateStatus(10, 'resolved');
        $this->assertSame($ticket, $result);
        $this->assertSame('resolved', $ticket->getStatus());
    }

    public function testUpdateStatusReturnsNullWhenTicketNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('findById')
            ->with(10)
            ->willReturn(null);
        $this->repository->expects($this->never())->method('save');

        $result = $this->service->updateStatus(10, 'resolved');
        $this->assertNull($result);
    }

    public function testAssignTicketAssignsAndSavesWhenFound(): void
    {
        $ticket = new Ticket(1, 'Subj', 'cat');
        $ticket->setId(10);
        $this->repository->expects($this->once())->method('findById')->with(10)->willReturn($ticket);
        $this->repository->expects($this->once())->method('save')->with($ticket)->willReturnArgument(0);

        $result = $this->service->assignTicket(10, 5);
        $this->assertSame($ticket, $result);
        $this->assertSame(5, $ticket->getAssignedTo());
    }

    public function testAssignTicketReturnsNullWhenNotFound(): void
    {
        $this->repository->expects($this->once())->method('findById')->with(10)->willReturn(null);
        $this->repository->expects($this->never())->method('save');
        $result = $this->service->assignTicket(10, 5);
        $this->assertNull($result);
    }

    public function testSetPrioritySetsAndSavesWhenFound(): void
    {
        $ticket = new Ticket(1, 'Subj', 'cat');
        $ticket->setId(10);
        $this->repository->expects($this->once())->method('findById')->with(10)->willReturn($ticket);
        $this->repository->expects($this->once())->method('save')->with($ticket)->willReturnArgument(0);

        $result = $this->service->setPriority(10, 'urgent');
        $this->assertSame($ticket, $result);
        $this->assertSame('urgent', $ticket->getPriority());
    }

    public function testSetPriorityReturnsNullWhenNotFound(): void
    {
        $this->repository->expects($this->once())->method('findById')->with(10)->willReturn(null);
        $this->repository->expects($this->never())->method('save');
        $result = $this->service->setPriority(10, 'low');
        $this->assertNull($result);
    }
}
