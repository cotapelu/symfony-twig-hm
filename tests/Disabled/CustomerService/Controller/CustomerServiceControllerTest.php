<?php declare(strict_types=1);

namespace App\Tests\Unit\CustomerService\Controller;

use App\CustomerService\Controller\CustomerServiceController;
use App\CustomerService\Entity\Ticket;
use App\CustomerService\Service\CustomerServiceService;
use PHPUnit\Framework\TestCase;

class CustomerServiceControllerTest extends TestCase
{
    private CustomerServiceController $controller;
    private CustomerServiceService $service;

    protected function setUp(): void
    {
        $this->service = $this->createMock(CustomerServiceService::class);
        $reflection = new \ReflectionClass(CustomerServiceController::class);
        $controller = $reflection->newInstanceWithoutConstructor();
        $reflectionProperty = $reflection->getProperty('service');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($controller, $this->service);
        $this->controller = $controller;
    }

    public function testCreateTicketSuccess(): void
    {
        $ticket = new Ticket(123, 'Test Subject', 'billing');
        $ticket->setId(1);
        $this->service->expects($this->once())
            ->method('createTicket')
            ->with(123, 'Test Subject', 'billing')
            ->willReturn($ticket);

        $result = $this->controller->createTicket([
            'user_id' => 123,
            'body' => [
                'subject' => 'Test Subject',
                'category' => 'billing'
            ]
        ]);

        $this->assertEquals(201, $result['status']);
        $this->assertInstanceOf(Ticket::class, $result['data']);
    }

    public function testGetTicketFound(): void
    {
        $ticket = new Ticket(123, 'Test', 'billing');
        $ticket->setId(1);
        $this->service->expects($this->once())
            ->method('getTicket')
            ->with(1)
            ->willReturn($ticket);

        $result = $this->controller->getTicket(['params' => ['id' => 1]]);

        $this->assertEquals(200, $result['status']);
        $this->assertSame($ticket, $result['data']);
    }

    public function testGetTicketNotFound(): void
    {
        $this->service->expects($this->once())
            ->method('getTicket')
            ->with(999)
            ->willReturn(null);

        $result = $this->controller->getTicket(['params' => ['id' => 999]]);

        $this->assertEquals(404, $result['status']);
    }

    public function testGetUserTickets(): void
    {
        $ticket = new Ticket(123, 'Test', 'billing');
        $ticket->setId(1);
        $this->service->expects($this->once())
            ->method('getUserTickets')
            ->with(123)
            ->willReturn([$ticket]);

        $result = $this->controller->getUserTickets(['params' => ['user_id' => 123]]);

        $this->assertEquals(200, $result['status']);
        $this->assertCount(1, $result['data']);
    }

    public function testGetAllTicketsWithFilters(): void
    {
        $ticket = new Ticket(123, 'Test', 'billing');
        $ticket->setId(1);
        $this->service->expects($this->once())
            ->method('getAllTickets')
            ->with(['status' => 'open'])
            ->willReturn([$ticket]);

        $result = $this->controller->getAllTickets(['query' => ['status' => 'open']]);

        $this->assertEquals(200, $result['status']);
        $this->assertCount(1, $result['data']);
    }

    public function testUpdateStatus(): void
    {
        $ticket = new Ticket(123, 'Test', 'billing');
        $ticket->setId(1);
        $ticket->setStatus('resolved');
        $this->service->expects($this->once())
            ->method('updateStatus')
            ->with(1, 'resolved')
            ->willReturn($ticket);

        $result = $this->controller->updateStatus([
            'params' => ['id' => 1],
            'body' => ['status' => 'resolved']
        ]);

        $this->assertEquals(200, $result['status']);
    }

    public function testAssignTicket(): void
    {
        $ticket = new Ticket(123, 'Test', 'billing');
        $ticket->setId(1);
        $ticket->setAssignedTo(456);
        $this->service->expects($this->once())
            ->method('assignTicket')
            ->with(1, 456)
            ->willReturn($ticket);

        $result = $this->controller->assignTicket([
            'params' => ['id' => 1],
            'body' => ['assigned_to' => 456]
        ]);

        $this->assertEquals(200, $result['status']);
    }

    public function testSetPriority(): void
    {
        $ticket = new Ticket(123, 'Test', 'billing');
        $ticket->setId(1);
        $ticket->setPriority('urgent');
        $this->service->expects($this->once())
            ->method('setPriority')
            ->with(1, 'urgent')
            ->willReturn($ticket);

        $result = $this->controller->setPriority([
            'params' => ['id' => 1],
            'body' => ['priority' => 'urgent']
        ]);

        $this->assertEquals(200, $result['status']);
    }

    public function testGetOpenTickets(): void
    {
        $ticket = new Ticket(123, 'Test', 'billing');
        $ticket->setId(1);
        $this->service->expects($this->once())
            ->method('getOpenTickets')
            ->willReturn([$ticket]);

        $result = $this->controller->getOpenTickets([]);

        $this->assertEquals(200, $result['status']);
        $this->assertCount(1, $result['data']);
    }

    public function testGetAssignedTickets(): void
    {
        $ticket = new Ticket(123, 'Test', 'billing');
        $ticket->setId(1);
        $ticket->setAssignedTo(456);
        $this->service->expects($this->once())
            ->method('getAssignedTickets')
            ->with(456)
            ->willReturn([$ticket]);

        $result = $this->controller->getAssignedTickets(['params' => ['agent_id' => 456]]);

        $this->assertEquals(200, $result['status']);
    }

    public function testGetTicketStats(): void
    {
        $this->service->expects($this->once())
            ->method('getTicketStats')
            ->willReturn([
                'total' => 10,
                'open' => 3,
                'in_progress' => 2
            ]);

        $result = $this->controller->getTicketStats([]);

        $this->assertEquals(200, $result['status']);
        $this->assertEquals(10, $result['data']['total']);
    }
}
