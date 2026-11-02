<?php

declare(strict_types=1);

namespace Tests\Unit\CustomerService\Controller;

use App\CustomerService\Controller\CustomerServiceController;
use App\CustomerService\Service\CustomerServiceService;
use App\CustomerService\Entity\Ticket;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class CustomerServiceControllerTest extends TestCase
{
    private CustomerServiceService $serviceMock;
    private CustomerServiceController $controller;

    protected function setUp(): void
    {
        $this->serviceMock = $this->createMock(CustomerServiceService::class);
        $this->controller = new CustomerServiceController($this->serviceMock);
        $this->controller->setContainer(new ContainerBuilder());
    }

    private function setAuthenticatedUser(string $userId = '1'): void
    {
        $user = new class($userId) implements \Symfony\Component\Security\Core\User\UserInterface {
            private string $id;
            public function __construct(string $id) { $this->id = $id; }
            public function getId(): string { return $this->id; }
            public function getUserIdentifier(): string { return $this->id; }
            public function getRoles(): array { return ['ROLE_USER']; }
            public function getPassword(): ?string { return null; }
            public function getSalt(): ?string { return null; }
            public function eraseCredentials(): void {}
        };
        $this->controller->setTestUser($user);
        $this->controller->setContainer(new ContainerBuilder());
    }

    private function invokePrivate(object $obj, string $method, array $args = []): mixed
    {
        $ref = new ReflectionMethod($obj, $method);
        $ref->setAccessible(true);
        return $ref->invoke($obj, ...$args);
    }

    private function createTicket(int $id, int $userId, string $subject, string $category = 'general', string $status = 'open', ?string $description = null, string $priority = 'medium', ?int $assignedTo = null, ?int $orderId = null): Ticket
    {
        $ticket = new Ticket($userId, $subject, $category);
        $ticket->setId($id);
        $ticket->setDescription($description ?? '');
        $ticket->setPriority($priority);
        $ticket->setStatus($status);
        $ticket->setAssignedTo($assignedTo);
        $ticket->setOrderId($orderId);
        return $ticket;
    }

    // ==================== Validation Helpers ====================

    public function testValidateTicketData_SubjectMissing_Throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Subject is required');
        $this->invokePrivate($this->controller, 'validateTicketData', [['other' => 'data']]);
    }

    public function testValidateTicketData_SubjectTooLong_Throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Subject must not exceed 255 characters');
        $this->invokePrivate($this->controller, 'validateTicketData', [['subject' => str_repeat('a', 256)]]);
    }

    public function testValidateTicketData_DescriptionTooLong_Throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Description must not exceed 5000 characters');
        $this->invokePrivate($this->controller, 'validateTicketData', [['subject' => 'Test', 'description' => str_repeat('a', 5001)]]);
    }

    public function testValidateTicketData_InvalidCategory_Throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid category');
        $this->invokePrivate($this->controller, 'validateTicketData', [['subject' => 'Test', 'category' => 'invalid']]);
    }

    public function testValidateTicketData_InvalidPriority_Throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid priority');
        $this->invokePrivate($this->controller, 'validateTicketData', [['subject' => 'Test', 'priority' => 'invalid']]);
    }

    public function testValidateTicketData_Valid_NoException(): void
    {
        $this->invokePrivate($this->controller, 'validateTicketData', [['subject' => 'Test', 'category' => 'technical', 'priority' => 'high']]);
        $this->assertTrue(true);
    }

    // validateStatusData
    public function testValidateStatusData_Empty_Throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Status is required');
        $this->invokePrivate($this->controller, 'validateStatusData', [['other' => 'data']]);
    }

    public function testValidateStatusData_Invalid_Throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid status');
        $this->invokePrivate($this->controller, 'validateStatusData', [['status' => 'invalid']]);
    }

    public function testValidateStatusData_Valid_NoException(): void
    {
        $this->invokePrivate($this->controller, 'validateStatusData', [['status' => 'resolved']]);
        $this->assertTrue(true);
    }

    // validateAssigneeData
    public function testValidateAssigneeData_Missing_Throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Valid assignee ID is required');
        $this->invokePrivate($this->controller, 'validateAssigneeData', [['other' => 'data']]);
    }

    public function testValidateAssigneeData_NonNumeric_Throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Valid assignee ID is required');
        $this->invokePrivate($this->controller, 'validateAssigneeData', [['assigned_to' => 'abc']]);
    }

    public function testValidateAssigneeData_LessThanOne_Throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Valid assignee ID is required');
        $this->invokePrivate($this->controller, 'validateAssigneeData', [['assigned_to' => '0']]);
    }

    public function testValidateAssigneeData_Valid_NoException(): void
    {
        $this->invokePrivate($this->controller, 'validateAssigneeData', [['assigned_to' => '5']]);
        $this->assertTrue(true);
    }

    // validatePriorityData
    public function testValidatePriorityData_Invalid_Throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid priority');
        $this->invokePrivate($this->controller, 'validatePriorityData', [['priority' => 'invalid']]);
    }

    public function testValidatePriorityData_Valid_NoException(): void
    {
        $this->invokePrivate($this->controller, 'validatePriorityData', [['priority' => 'low']]);
        $this->assertTrue(true);
    }

    // validatePositiveInt
    public function testValidatePositiveInt_TooSmall_Throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ID must be at least 1');
        $this->invokePrivate($this->controller, 'validatePositiveInt', [0, 'ID']);
    }

    public function testValidatePositiveInt_Valid_NoException(): void
    {
        $this->invokePrivate($this->controller, 'validatePositiveInt', [1, 'ID']);
        $this->assertTrue(true);
    }

    // extractTicketFilters
    public function testExtractTicketFilters_NoFilters_ReturnsEmpty(): void
    {
        $request = Request::create('/api/support/tickets');
        $filters = $this->invokePrivate($this->controller, 'extractTicketFilters', [$request]);
        $this->assertEmpty($filters);
    }

    public function testExtractTicketFilters_InvalidStatus_Ignored(): void
    {
        $request = Request::create('/api/support/tickets', 'GET', ['status' => 'invalid']);
        $filters = $this->invokePrivate($this->controller, 'extractTicketFilters', [$request]);
        $this->assertArrayNotHasKey('status', $filters);
    }

    public function testExtractTicketFilters_ValidFilters(): void
    {
        $request = Request::create('/api/support/tickets', 'GET', ['status' => 'open', 'priority' => 'high', 'category' => 'technical']);
        $filters = $this->invokePrivate($this->controller, 'extractTicketFilters', [$request]);
        $this->assertSame(['status' => 'open', 'priority' => 'high', 'category' => 'technical'], $filters);
    }

    // ==================== Endpoints ====================

    // createTicket
    public function testCreateTicket_Unauthenticated_Returns400(): void
    {
        $payload = json_encode(['subject' => 'Test']);
        $request = Request::create('/api/support/tickets', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $response = $this->controller->createTicket($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testCreateTicket_InvalidJson_Returns400(): void
    {
        $this->setAuthenticatedUser('1');
        $request = Request::create('/api/support/tickets', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], 'invalid{');
        $response = $this->controller->createTicket($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testCreateTicket_ValidationError_Returns400(): void
    {
        $this->setAuthenticatedUser('1');
        $payload = json_encode(['subject' => '']); // empty subject
        $request = Request::create('/api/support/tickets', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $response = $this->controller->createTicket($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testCreateTicket_Success_Returns201(): void
    {
        $this->setAuthenticatedUser('1');
        $payload = json_encode(['subject' => 'Test Subject', 'category' => 'technical', 'priority' => 'high', 'description' => 'Details']);
        $request = Request::create('/api/support/tickets', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $ticket = $this->createTicket(1, (int)'1', 'Test Subject', 'technical', 'open', 'Details', 'high');
        $this->serviceMock->expects($this->once())
            ->method('createTicket')
            ->with((int)'1', 'Test Subject', 'technical')
            ->willReturn($ticket);
        $response = $this->controller->createTicket($request);
        $this->assertSame(201, $response->getStatusCode());
    }

    // getTicket
    public function testGetTicket_InvalidId_Returns400(): void
    {
        $this->setAuthenticatedUser('1');
        $request = Request::create('/api/support/tickets/0', 'GET');
        $response = $this->controller->getTicket(0, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testGetTicket_Unauthenticated_Returns400(): void
    {
        $request = Request::create('/api/support/tickets/1', 'GET');
        $response = $this->controller->getTicket(1, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testGetTicket_NotFound_Returns404(): void
    {
        $this->setAuthenticatedUser('1');
        $this->serviceMock->expects($this->once())
            ->method('getTicket')
            ->with(1)
            ->willReturn(null);
        $request = Request::create('/api/support/tickets/1', 'GET');
        $response = $this->controller->getTicket(1, $request);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testGetTicket_AccessDenied_Returns403(): void
    {
        $this->setAuthenticatedUser('1');
        $ticket = $this->createTicket(1, 2, 'Subject'); // userId 2
        $this->serviceMock->method('getTicket')->willReturn($ticket);
        $request = Request::create('/api/support/tickets/1', 'GET');
        $response = $this->controller->getTicket(1, $request);
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testGetTicket_Success_Returns200(): void
    {
        $this->setAuthenticatedUser('1');
        $ticket = $this->createTicket(1, (int)'1', 'Subject');
        $this->serviceMock->method('getTicket')->willReturn($ticket);
        $request = Request::create('/api/support/tickets/1', 'GET');
        $response = $this->controller->getTicket(1, $request);
        $this->assertSame(200, $response->getStatusCode());
    }

    // getUserTickets
    public function testGetUserTickets_InvalidUserId_Returns400(): void
    {
        $this->setAuthenticatedUser('1');
        $request = Request::create('/api/support/tickets/user/0', 'GET');
        $response = $this->controller->getUserTickets(0, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testGetUserTickets_Unauthenticated_Returns400(): void
    {
        $request = Request::create('/api/support/tickets/user/1', 'GET');
        $response = $this->controller->getUserTickets(1, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testGetUserTickets_AccessDenied_Returns403(): void
    {
        $this->setAuthenticatedUser('1');
        $request = Request::create('/api/support/tickets/user/2', 'GET');
        $response = $this->controller->getUserTickets(2, $request);
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testGetUserTickets_Success_Returns200(): void
    {
        $this->setAuthenticatedUser('1');
        $tickets = [$this->createTicket(1, (int)'1', 'Subject')];
        $this->serviceMock->method('getUserTickets')->with((int)'1')->willReturn($tickets);
        $request = Request::create('/api/support/tickets/user/1', 'GET');
        $response = $this->controller->getUserTickets((int)'1', $request);
        $this->assertSame(200, $response->getStatusCode());
    }

    // getAllTickets
    public function testGetAllTickets_Success_Returns200(): void
    {
        $tickets = [$this->createTicket(1, 1, 'Subject')];
        $this->serviceMock->method('getAllTickets')->willReturn($tickets);
        $request = Request::create('/api/support/tickets', 'GET');
        $response = $this->controller->getAllTickets($request);
        $this->assertSame(200, $response->getStatusCode());
    }

    // updateStatus
    public function testUpdateStatus_InvalidId_Returns400(): void
    {
        $this->setAuthenticatedUser('1');
        $payload = json_encode(['status' => 'open']);
        $request = Request::create('/api/support/tickets/0/status', 'PUT', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $response = $this->controller->updateStatus(0, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testUpdateStatus_Unauthenticated_Returns400(): void
    {
        $payload = json_encode(['status' => 'open']);
        $request = Request::create('/api/support/tickets/1/status', 'PUT', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $response = $this->controller->updateStatus(1, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testUpdateStatus_InvalidJson_Returns400(): void
    {
        $this->setAuthenticatedUser('1');
        $request = Request::create('/api/support/tickets/1/status', 'PUT', [], [], [], ['CONTENT_TYPE' => 'application/json'], 'invalid');
        $response = $this->controller->updateStatus(1, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testUpdateStatus_ValidationError_Returns400(): void
    {
        $this->setAuthenticatedUser('1');
        $payload = json_encode(['status' => 'invalid']);
        $request = Request::create('/api/support/tickets/1/status', 'PUT', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $response = $this->controller->updateStatus(1, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testUpdateStatus_NotFound_Returns404(): void
    {
        $this->setAuthenticatedUser('1');
        $payload = json_encode(['status' => 'open']);
        $request = Request::create('/api/support/tickets/1/status', 'PUT', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $this->serviceMock->expects($this->once())
            ->method('updateStatus')
            ->with(1, 'open')
            ->willReturn(null);
        $response = $this->controller->updateStatus(1, $request);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testUpdateStatus_Success_Returns200(): void
    {
        $this->setAuthenticatedUser('1');
        $payload = json_encode(['status' => 'in_progress']);
        $request = Request::create('/api/support/tickets/1/status', 'PUT', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $ticket = $this->createTicket(1, (int)'1', 'Subject', 'general', 'in_progress');
        $this->serviceMock->expects($this->once())
            ->method('updateStatus')
            ->with(1, 'in_progress')
            ->willReturn($ticket);
        $response = $this->controller->updateStatus(1, $request);
        $this->assertSame(200, $response->getStatusCode());
    }

    // assignTicket
    public function testAssignTicket_InvalidId_Returns400(): void
    {
        $this->setAuthenticatedUser('1');
        $payload = json_encode(['assigned_to' => 5]);
        $request = Request::create('/api/support/tickets/0/assign', 'PUT', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $response = $this->controller->assignTicket(0, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testAssignTicket_Unauthenticated_Returns400(): void
    {
        $payload = json_encode(['assigned_to' => 5]);
        $request = Request::create('/api/support/tickets/1/assign', 'PUT', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $response = $this->controller->assignTicket(1, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testAssignTicket_ValidationError_Returns400(): void
    {
        $this->setAuthenticatedUser('1');
        $payload = json_encode(['assigned_to' => 'abc']);
        $request = Request::create('/api/support/tickets/1/assign', 'PUT', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $response = $this->controller->assignTicket(1, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testAssignTicket_NotFound_Returns404(): void
    {
        $this->setAuthenticatedUser('1');
        $payload = json_encode(['assigned_to' => 5]);
        $request = Request::create('/api/support/tickets/1/assign', 'PUT', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $this->serviceMock->expects($this->once())
            ->method('assignTicket')
            ->with(1, 5)
            ->willReturn(null);
        $response = $this->controller->assignTicket(1, $request);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testAssignTicket_Success_Returns200(): void
    {
        $this->setAuthenticatedUser('1');
        $payload = json_encode(['assigned_to' => 5]);
        $request = Request::create('/api/support/tickets/1/assign', 'PUT', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $ticket = $this->createTicket(1, 1, 'Subject', 'general', 'open', null, 'medium', 5);
        $this->serviceMock->expects($this->once())
            ->method('assignTicket')
            ->with(1, 5)
            ->willReturn($ticket);
        $response = $this->controller->assignTicket(1, $request);
        $this->assertSame(200, $response->getStatusCode());
    }

    // setPriority
    public function testSetPriority_InvalidId_Returns400(): void
    {
        $this->setAuthenticatedUser('1');
        $payload = json_encode(['priority' => 'high']);
        $request = Request::create('/api/support/tickets/0/priority', 'PUT', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $response = $this->controller->setPriority(0, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testSetPriority_Unauthenticated_Returns400(): void
    {
        $payload = json_encode(['priority' => 'high']);
        $request = Request::create('/api/support/tickets/1/priority', 'PUT', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $response = $this->controller->setPriority(1, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testSetPriority_ValidationError_Returns400(): void
    {
        $this->setAuthenticatedUser('1');
        $payload = json_encode(['priority' => 'invalid']);
        $request = Request::create('/api/support/tickets/1/priority', 'PUT', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $response = $this->controller->setPriority(1, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testSetPriority_NotFound_Returns404(): void
    {
        $this->setAuthenticatedUser('1');
        $payload = json_encode(['priority' => 'low']);
        $request = Request::create('/api/support/tickets/1/priority', 'PUT', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $this->serviceMock->expects($this->once())
            ->method('setPriority')
            ->with(1, 'low')
            ->willReturn(null);
        $response = $this->controller->setPriority(1, $request);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testSetPriority_Success_Returns200(): void
    {
        $this->setAuthenticatedUser('1');
        $payload = json_encode(['priority' => 'urgent']);
        $request = Request::create('/api/support/tickets/1/priority', 'PUT', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $ticket = $this->createTicket(1, (int)'1', 'Subject', 'general', 'open', null, 'urgent');
        $this->serviceMock->expects($this->once())
            ->method('setPriority')
            ->with(1, 'urgent')
            ->willReturn($ticket);
        $response = $this->controller->setPriority(1, $request);
        $this->assertSame(200, $response->getStatusCode());
    }

    // getOpenTickets
    public function testGetOpenTickets_Success_Returns200(): void
    {
        $tickets = [$this->createTicket(1, 1, 'Subject', 'general', 'open')];
        $this->serviceMock->method('getOpenTickets')->willReturn($tickets);
        $request = Request::create('/api/support/tickets/open', 'GET');
        $response = $this->controller->getOpenTickets($request);
        $this->assertSame(200, $response->getStatusCode());
    }

    // getAssignedTickets
    public function testGetAssignedTickets_InvalidAgentId_Returns400(): void
    {
        $request = Request::create('/api/support/tickets/agent/0', 'GET');
        $response = $this->controller->getAssignedTickets(0, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testGetAssignedTickets_Success_Returns200(): void
    {
        $tickets = [$this->createTicket(1, 1, 'Subject', 'general', 'open', null, 'medium', 5)];
        $this->serviceMock->method('getAssignedTickets')->with(5)->willReturn($tickets);
        $request = Request::create('/api/support/tickets/agent/5', 'GET');
        $response = $this->controller->getAssignedTickets(5, $request);
        $this->assertSame(200, $response->getStatusCode());
    }

    // getTicketStats
    public function testGetTicketStats_Success_Returns200(): void
    {
        $stats = ['total' => 100, 'open' => 20, 'resolved' => 80];
        $this->serviceMock->method('getTicketStats')->willReturn($stats);
        $request = Request::create('/api/support/stats', 'GET');
        $response = $this->controller->getTicketStats($request);
        $this->assertSame(200, $response->getStatusCode());
    }
}
