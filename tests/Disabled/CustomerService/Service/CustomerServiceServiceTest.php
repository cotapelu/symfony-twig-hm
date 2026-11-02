<?php declare(strict_types=1);

namespace App\Tests\Unit\CustomerService\Service;

use App\CustomerService\Entity\Ticket;
use App\CustomerService\Repository\TicketRepositoryInterface;
use App\CustomerService\Service\CustomerServiceService;
use PHPUnit\Framework\TestCase;

class CustomerServiceServiceTest extends TestCase
{
    private CustomerServiceService $service;
    private TicketRepositoryInterface $repository;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(TicketRepositoryInterface::class);
        $this->service = new CustomerServiceService($this->repository);
    }

    public function testCreateTicketSuccess(): void
    {
        $ticket = new Ticket(123, 'Test Subject', 'billing');
        $ticket->setId(1);
        $this->repository->expects($this->once())
            ->method('save')
            ->willReturn($ticket);

        $result = $this->service->createTicket(123, 'Test Subject', 'billing');

        $this->assertInstanceOf(Ticket::class, $result);
        $this->assertEquals(123, $result->getUserId());
    }

    public function testGetTicketFound(): void
    {
        $ticket = new Ticket(123, 'Test', 'billing');
        $ticket->setId(1);
        $this->repository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($ticket);

        $result = $this->service->getTicket(1);

        $this->assertSame($ticket, $result);
    }

    public function testGetTicketNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $result = $this->service->getTicket(999);

        $this->assertNull($result);
    }

    public function testGetUserTickets(): void
    {
        $ticket1 = new Ticket(123, 'Test1', 'billing');
        $ticket1->setId(1);
        $ticket2 = new Ticket(123, 'Test2', 'shipping');
        $ticket2->setId(2);
        $this->repository->expects($this->once())
            ->method('findByUserId')
            ->with(123)
            ->willReturn([$ticket1, $ticket2]);

        $result = $this->service->getUserTickets(123);

        $this->assertCount(2, $result);
    }

    public function testGetAllTicketsByStatus(): void
    {
        $ticket = new Ticket(123, 'Test', 'billing');
        $ticket->setId(1);
        $this->repository->expects($this->once())
            ->method('findByStatus')
            ->with('open')
            ->willReturn([$ticket]);

        $result = $this->service->getAllTickets(['status' => 'open']);

        $this->assertCount(1, $result);
    }

    public function testGetAllTicketsByPriority(): void
    {
        $ticket = new Ticket(123, 'Test', 'billing');
        $ticket->setId(1);
        $this->repository->expects($this->once())
            ->method('findByPriority')
            ->with('urgent')
            ->willReturn([$ticket]);

        $result = $this->service->getAllTickets(['priority' => 'urgent']);

        $this->assertCount(1, $result);
    }

    public function testUpdateStatus(): void
    {
        $ticket = new Ticket(123, 'Test', 'billing');
        $ticket->setId(1);
        $this->repository->method('findById')->willReturn($ticket);
        $this->repository->method('save')->willReturn($ticket);

        $result = $this->service->updateStatus(1, 'resolved');

        $this->assertEquals('resolved', $result->getStatus());
    }

    public function testAssignTicket(): void
    {
        $ticket = new Ticket(123, 'Test', 'billing');
        $ticket->setId(1);
        $this->repository->method('findById')->willReturn($ticket);
        $this->repository->method('save')->willReturn($ticket);

        $result = $this->service->assignTicket(1, 456);

        $this->assertEquals(456, $result->getAssignedTo());
    }

    public function testSetPriority(): void
    {
        $ticket = new Ticket(123, 'Test', 'billing');
        $ticket->setId(1);
        $this->repository->method('findById')->willReturn($ticket);
        $this->repository->method('save')->willReturn($ticket);

        $result = $this->service->setPriority(1, 'urgent');

        $this->assertEquals('urgent', $result->getPriority());
    }

    public function testLinkToOrder(): void
    {
        $ticket = new Ticket(123, 'Test', 'billing');
        $ticket->setId(1);
        $this->repository->method('findById')->willReturn($ticket);
        $this->repository->method('save')->willReturn($ticket);

        $result = $this->service->linkToOrder(1, 789);

        $this->assertEquals(789, $result->getOrderId());
    }

    public function testGetOpenTickets(): void
    {
        $ticket = new Ticket(123, 'Test', 'billing');
        $ticket->setId(1);
        $this->repository->expects($this->once())
            ->method('findOpenTickets')
            ->willReturn([$ticket]);

        $result = $this->service->getOpenTickets();

        $this->assertCount(1, $result);
    }

    public function testGetAssignedTickets(): void
    {
        $ticket = new Ticket(123, 'Test', 'billing');
        $ticket->setId(1);
        $ticket->setAssignedTo(456);
        $this->repository->expects($this->once())
            ->method('findAssignedTickets')
            ->with(456)
            ->willReturn([$ticket]);

        $result = $this->service->getAssignedTickets(456);

        $this->assertCount(1, $result);
    }

    public function testGetTicketStats(): void
    {
        $open = new Ticket(123, 'Test1', 'billing');
        $open->setId(1);
        $inProgress = new Ticket(123, 'Test2', 'billing');
        $inProgress->setId(2);
        $inProgress->setStatus('in_progress');
        $resolved = new Ticket(123, 'Test3', 'billing');
        $resolved->setId(3);
        $resolved->setStatus('resolved');
        $this->repository->method('findByStatus')->willReturnMap([
            ['open', [$open]],
            ['in_progress', [$inProgress]],
            ['waiting', []],
            ['resolved', [$resolved]],
            ['closed', []]
        ]);

        $result = $this->service->getTicketStats();

        $this->assertEquals(3, $result['total']);
        $this->assertEquals(1, $result['open']);
        $this->assertEquals(1, $result['in_progress']);
        $this->assertEquals(1, $result['resolved']);
    }
}
