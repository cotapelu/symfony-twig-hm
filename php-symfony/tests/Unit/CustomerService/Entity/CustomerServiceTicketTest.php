<?php declare(strict_types=1);

namespace App\Tests\Unit\CustomerService\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\CustomerService\Entity\CustomerServiceTicket;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class CustomerServiceTicketTest extends TestCase
{
    public function testConstructorInitializesIdAndCreatedAt(): void
    {
        $ticket = new CustomerServiceTicket();
        $this->assertIsString($ticket->getId());
        $this->assertNotEmpty($ticket->getId());
        $this->assertInstanceOf(\DateTime::class, $ticket->getCreatedAt());
        $this->assertNull($ticket->getUpdatedAt());
    }

    public function testGettersAndSetters(): void
    {
        $ticket = new CustomerServiceTicket();

        $ticket->setUserId('user-1');
        $this->assertSame('user-1', $ticket->getUserId());

        $ticket->setSubject('Issue with payment');
        $this->assertSame('Issue with payment', $ticket->getSubject());

        $ticket->setDescription('Payment failed twice.');
        $this->assertSame('Payment failed twice.', $ticket->getDescription());

        $ticket->setCategory('billing');
        $this->assertSame('billing', $ticket->getCategory());

        $ticket->setPriority('high');
        $this->assertSame('high', $ticket->getPriority());

        $ticket->setStatus('in_progress');
        $this->assertSame('in_progress', $ticket->getStatus());

        $ticket->setAssignedTo('agent-5');
        $this->assertSame('agent-5', $ticket->getAssignedTo());

        $ticket->setAssignedTo(null);
        $this->assertNull($ticket->getAssignedTo());

        $ticket->setUpdatedAt(new \DateTime());
        $this->assertInstanceOf(\DateTime::class, $ticket->getUpdatedAt());
    }

    public function testToArray(): void
    {
        $ticket = new CustomerServiceTicket();
        $ticket->setUserId('user-2');
        $ticket->setSubject('Test subject');
        $ticket->setDescription('Test description');
        $ticket->setCategory('technical');
        $ticket->setPriority('low');
        $ticket->setStatus('pending');
        $updated = new \DateTime('2024-01-02 12:00:00');
        $ticket->setUpdatedAt($updated);

        $arr = $ticket->toArray();
        $this->assertSame('user-2', $arr['userId']);
        $this->assertSame('Test subject', $arr['subject']);
        $this->assertSame('Test description', $arr['description']);
        $this->assertSame('technical', $arr['category']);
        $this->assertSame('low', $arr['priority']);
        $this->assertSame('pending', $arr['status']);
        $this->assertNull($arr['assignedTo']);
        $this->assertSame('2024-01-02 12:00:00', $arr['updatedAt']);
        $this->assertArrayHasKey('id', $arr);
        $this->assertArrayHasKey('createdAt', $arr);
    }
}
