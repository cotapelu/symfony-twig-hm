<?php

declare(strict_types=1);

namespace App\CustomerService\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'support_tickets')]
class SupportTicket
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $userId;

    #[ORM\Column(type: 'string', length: 50)]
    private string $category;

    #[ORM\Column(type: 'text')]
    private string $subject;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $message;

    #[ORM\Column(type: 'string', length: 50)]
    private string $status = 'open';

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $priority;

    public function __construct()
    {
        $this->id = (string) \Ramsey\Uuid\Uuid::uuid4()->toString();
    }

    public function getId() { return $this->id; }
    public function getUserId() { return $this->userId; }
    public function setUserId(string $userId) { $this->userId = $userId; }
    public function getCategory() { return $this->category; }
    public function setCategory(string $category) { $this->category = $category; }
    public function getSubject() { return $this->subject; }
    public function setSubject(string $subject) { $this->subject = $subject; }
    public function getStatus() { return $this->status; }
    public function setStatus(string $status) { $this->status = $status; }
    
    public function getMessage() { return $this->message; }
    public function setMessage(?string $message) { $this->message = $message; }
    
    public function getPriority() { return $this->priority; }
    public function setPriority(?string $priority) { $this->priority = $priority; }
}
