<?php declare(strict_types=1);

namespace App\Tests\Integration;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\CustomerService\Entity\Ticket;
use App\CustomerService\Repository\TicketRepository;
use App\CustomerService\Service\CustomerServiceService;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class CustomerServiceIntegrationTest extends TestCase
{
    private ?\PDO $pdo = null;
    private TicketRepository $repository;
    private CustomerServiceService $service;

    protected function setUp(): void
    {
        $this->pdo = new \PDO('sqlite::memory:');
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        // Create table
        $this->pdo->exec("
            CREATE TABLE customer_service_tickets (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                subject VARCHAR(255) NOT NULL,
                description TEXT,
                category VARCHAR(50) NOT NULL,
                priority VARCHAR(20) DEFAULT 'medium',
                status VARCHAR(20) DEFAULT 'open',
                assigned_to INTEGER,
                order_id INTEGER,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                deleted_at DATETIME
            )
        ");

        $this->repository = new TicketRepository($this->pdo);
        $this->service = new CustomerServiceService($this->repository);
    }

    protected function tearDown(): void
    {
        $this->pdo = null;
    }

    // ==================== CRUD Tests ====================

    public function testCreateAndRetrieveTicket(): void
    {
        $ticket = $this->service->createTicket(123, 'Test Subject', 'billing');

        $this->assertNotNull($ticket->getId());
        $this->assertEquals(123, $ticket->getUserId());
        $this->assertEquals('Test Subject', $ticket->getSubject());
        $this->assertEquals('billing', $ticket->getCategory());
        $this->assertEquals('medium', $ticket->getPriority());
        $this->assertEquals('open', $ticket->getStatus());

        // Retrieve from database
        $retrieved = $this->repository->findById($ticket->getId());
        $this->assertNotNull($retrieved);
        $this->assertEquals('Test Subject', $retrieved->getSubject());
    }

    public function testGetUserTickets(): void
    {
        $this->service->createTicket(123, 'Ticket 1', 'billing');
        $this->service->createTicket(123, 'Ticket 2', 'shipping');
        $this->service->createTicket(456, 'Ticket 3', 'billing');

        $user123Tickets = $this->service->getUserTickets(123);
        $user456Tickets = $this->service->getUserTickets(456);

        $this->assertCount(2, $user123Tickets);
        $this->assertCount(1, $user456Tickets);
    }

    public function testUpdateStatus(): void
    {
        $ticket = $this->service->createTicket(123, 'Test', 'billing');
        $updated = $this->service->updateStatus($ticket->getId(), 'in_progress');

        $this->assertEquals('in_progress', $updated->getStatus());

        // Verify in database
        $retrieved = $this->repository->findById($ticket->getId());
        $this->assertEquals('in_progress', $retrieved->getStatus());
    }

    public function testAssignTicket(): void
    {
        $ticket = $this->service->createTicket(123, 'Test', 'billing');
        $updated = $this->service->assignTicket($ticket->getId(), 456);

        $this->assertEquals(456, $updated->getAssignedTo());
    }

    public function testSetPriority(): void
    {
        $ticket = $this->service->createTicket(123, 'Test', 'billing');
        $updated = $this->service->setPriority($ticket->getId(), 'urgent');

        $this->assertEquals('urgent', $updated->getPriority());
    }

    public function testLinkToOrder(): void
    {
        $ticket = $this->service->createTicket(123, 'Test', 'billing');
        $updated = $this->service->linkToOrder($ticket->getId(), 789);

        $this->assertEquals(789, $updated->getOrderId());
    }

    public function testGetOpenTickets(): void
    {
        $this->service->createTicket(123, 'Ticket 1', 'billing');
        $ticket2 = $this->service->createTicket(123, 'Ticket 2', 'shipping');
        $this->service->updateStatus($ticket2->getId(), 'resolved');

        $openTickets = $this->service->getOpenTickets();

        $this->assertCount(1, $openTickets);
    }

    public function testGetAssignedTickets(): void
    {
        $this->service->createTicket(123, 'Ticket 1', 'billing');
        $ticket2 = $this->service->createTicket(123, 'Ticket 2', 'shipping');
        $this->service->assignTicket($ticket2->getId(), 456);

        $assignedTo456 = $this->service->getAssignedTickets(456);

        $this->assertCount(1, $assignedTo456);
    }

    public function testGetTicketStats(): void
    {
        $this->service->createTicket(123, 'Ticket 1', 'billing');
        $ticket2 = $this->service->createTicket(123, 'Ticket 2', 'billing');
        $this->service->updateStatus($ticket2->getId(), 'in_progress');
        $ticket3 = $this->service->createTicket(123, 'Ticket 3', 'billing');
        $this->service->updateStatus($ticket3->getId(), 'resolved');

        $stats = $this->service->getTicketStats();

        // Ticket 1 is 'open', Ticket 2 is 'in_progress', Ticket 3 is 'resolved'
        $this->assertEquals(3, $stats['total']);
        $this->assertEquals(1, $stats['open']);
        $this->assertEquals(1, $stats['in_progress']);
        $this->assertEquals(1, $stats['resolved']);
    }

    // ==================== Data Integrity Tests ====================

    public function testTicketIsolationBetweenUsers(): void
    {
        $this->service->createTicket(123, 'User 123 Ticket', 'billing');
        $this->service->createTicket(456, 'User 456 Ticket', 'shipping');

        $user123Tickets = $this->service->getUserTickets(123);
        $user456Tickets = $this->service->getUserTickets(456);

        $this->assertCount(1, $user123Tickets);
        $this->assertCount(1, $user456Tickets);
        $this->assertEquals('User 123 Ticket', $user123Tickets[0]->getSubject());
        $this->assertEquals('User 456 Ticket', $user456Tickets[0]->getSubject());
    }

    public function testAllPriorityLevels(): void
    {
        $levels = ['low', 'medium', 'high', 'urgent'];
        
        foreach ($levels as $level) {
            $ticket = $this->service->createTicket(123, "Test $level", 'billing');
            $updated = $this->service->setPriority($ticket->getId(), $level);
            $this->assertEquals($level, $updated->getPriority());
        }
    }

    public function testAllStatusTransitions(): void
    {
        $ticket = $this->service->createTicket(123, 'Test', 'billing');
        $this->assertEquals('open', $ticket->getStatus());

        $updated = $this->service->updateStatus($ticket->getId(), 'in_progress');
        $this->assertEquals('in_progress', $updated->getStatus());

        $updated = $this->service->updateStatus($ticket->getId(), 'waiting');
        $this->assertEquals('waiting', $updated->getStatus());

        $updated = $this->service->updateStatus($ticket->getId(), 'resolved');
        $this->assertEquals('resolved', $updated->getStatus());
    }

    public function testDescriptionAndOptionalFields(): void
    {
        $ticket = $this->service->createTicket(123, 'Test', 'billing');
        $ticket->setDescription('This is a test ticket description');

        $this->assertEquals('This is a test ticket description', $ticket->getDescription());
    }
}
