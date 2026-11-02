<?php declare(strict_types=1);

namespace App\Tests\Unit\CustomerService\Entity;

use App\CustomerService\Entity\Ticket;
use PHPUnit\Framework\TestCase;

class TicketTest extends TestCase
{
    public function testConstructorSetsRequiredProperties(): void
    {
        $ticket = new Ticket(123, 'Test Subject', 'billing');

        $this->assertNull($ticket->getId());
        $this->assertEquals(123, $ticket->getUserId());
        $this->assertEquals('Test Subject', $ticket->getSubject());
        $this->assertEquals('billing', $ticket->getCategory());
        $this->assertEquals('medium', $ticket->getPriority());
        $this->assertEquals('open', $ticket->getStatus());
    }

    public function testSetId(): void
    {
        $ticket = new Ticket(123, 'Test', 'billing');
        $ticket->setId(456);

        $this->assertEquals(456, $ticket->getId());
    }

    public function testSetDescription(): void
    {
        $ticket = new Ticket(123, 'Test', 'billing');
        $ticket->setDescription('Test description');

        $this->assertEquals('Test description', $ticket->getDescription());
    }

    public function testSetPriority(): void
    {
        $ticket = new Ticket(123, 'Test', 'billing');
        $ticket->setPriority('urgent');

        $this->assertEquals('urgent', $ticket->getPriority());
    }

    public function testSetStatus(): void
    {
        $ticket = new Ticket(123, 'Test', 'billing');
        $ticket->setStatus('in_progress');

        $this->assertEquals('in_progress', $ticket->getStatus());
    }

    public function testSetAssignedTo(): void
    {
        $ticket = new Ticket(123, 'Test', 'billing');
        $ticket->setAssignedTo(456);

        $this->assertEquals(456, $ticket->getAssignedTo());
    }

    public function testSetAssignedToNull(): void
    {
        $ticket = new Ticket(123, 'Test', 'billing');
        $ticket->setAssignedTo(null);

        $this->assertNull($ticket->getAssignedTo());
    }

    public function testSetOrderId(): void
    {
        $ticket = new Ticket(123, 'Test', 'billing');
        $ticket->setOrderId(789);

        $this->assertEquals(789, $ticket->getOrderId());
    }

    public function testSetOrderIdNull(): void
    {
        $ticket = new Ticket(123, 'Test', 'billing');
        $ticket->setOrderId(null);

        $this->assertNull($ticket->getOrderId());
    }

    public function testSettersAreChainable(): void
    {
        $ticket = new Ticket(123, 'Test', 'billing');

        $result = $ticket
            ->setDescription('Desc')
            ->setPriority('high')
            ->setStatus('in_progress')
            ->setAssignedTo(456);

        $this->assertSame($ticket, $result);
    }

    public function testPriorityLevels(): void
    {
        $levels = ['low', 'medium', 'high', 'urgent'];
        
        foreach ($levels as $level) {
            $ticket = new Ticket(123, 'Test', 'billing');
            $ticket->setPriority($level);
            $this->assertEquals($level, $ticket->getPriority());
        }
    }

    public function testStatusTransitions(): void
    {
        $statuses = ['open', 'in_progress', 'waiting', 'resolved', 'closed'];
        
        foreach ($statuses as $status) {
            $ticket = new Ticket(123, 'Test', 'billing');
            $ticket->setStatus($status);
            $this->assertEquals($status, $ticket->getStatus());
        }
    }

    public function testCategoryIsSetInConstructor(): void
    {
        $categories = ['billing', 'shipping', 'technical', 'general'];
        
        foreach ($categories as $category) {
            $ticket = new Ticket(123, 'Test', $category);
            $this->assertEquals($category, $ticket->getCategory());
        }
    }
}
