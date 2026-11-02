<?php

declare(strict_types=1);

namespace App\CustomerService\Entity;

use App\Attribute\Module;

#[Module(name: 'CustomerService', dependencies: ['User'])]
class Ticket
{
    private ?int $id = null;
    private int $userId;
    private string $subject;
    private string $description = '';
    private string $category;
    private string $priority; // low, medium, high, urgent
    private string $status; // open, in_progress, waiting, resolved, closed
    private ?int $assignedTo = null;
    private ?int $orderId = null;
    private \DateTime $createdAt;
    private \DateTime $updatedAt;

    public function __construct(int $userId, string $subject, string $category)
    {
        $this->userId = $userId;
        $this->subject = $subject;
        $this->category = $category;
        $this->priority = 'medium';
        $this->status = 'open';
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    // Getters and setters
    public function getId() { return $this->id; }
    public function getUserId() { return $this->userId; }
    public function getSubject() { return $this->subject; }
    public function getDescription() { return $this->description; }
    public function getCategory() { return $this->category; }
    public function getPriority() { return $this->priority; }
    public function getStatus() { return $this->status; }
    public function getAssignedTo() { return $this->assignedTo; }
    public function getOrderId() { return $this->orderId; }

    public function setDescription(string $description) { $this->description = $description; return $this; }
    public function setPriority(string $priority) { $this->priority = $priority; return $this; }
    public function setStatus(string $status) { $this->status = $status; return $this; }
    public function setAssignedTo(?int $userId) { $this->assignedTo = $userId; return $this; }
    public function setOrderId(?int $orderId) { $this->orderId = $orderId; return $this; }
    public function setId(?int $id) { $this->id = $id; return $this; }
}