<?php

declare(strict_types=1);

namespace App\Entity\Common;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;

#[ORM\Entity]
#[ORM\Table(name: 'common.workflow_instances')]
#[ORM\Index(name: 'idx_workflow_instances_workflow_id', columns: ['workflow_id'])]
#[ORM\Index(name: 'idx_workflow_instances_status', columns: ['status'])]
#[ORM\Index(name: 'idx_workflow_instances_started_at', columns: ['started_at'])]
class WorkflowInstance
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $workflowId;

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $userId = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'PENDING';

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $state = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $context = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $startedAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $completedAt = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $updatedAt;

    public function __construct(string $id, string $workflowId)
    {
        $this->id = $id;
        $this->workflowId = $workflowId;
        $this->startedAt = new \DateTime();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getWorkflowId()
    {
        return $this->workflowId;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function setUserId(?string $userId)
    {
        $this->userId = $userId;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus(string $status)
    {
        $this->status = $status;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getState()
    {
        return $this->state;
    }

    public function setState(?array $state)
    {
        $this->state = $state;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getContext()
    {
        return $this->context;
    }

    public function setContext(?array $context)
    {
        $this->context = $context;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getStartedAt()
    {
        return $this->startedAt;
    }

    public function getCompletedAt()
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTime $completedAt)
    {
        $this->completedAt = $completedAt;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function isPending()
    {
        return $this->status === 'PENDING';
    }

    public function isRunning()
    {
        return $this->status === 'RUNNING';
    }

    public function isCompleted()
    {
        return $this->status === 'COMPLETED';
    }

    public function isFailed()
    {
        return $this->status === 'FAILED';
    }

    public function isCancelled()
    {
        return $this->status === 'CANCELLED';
    }

    public function complete()
    {
        $this->status = 'COMPLETED';
        $this->completedAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function fail()
    {
        $this->status = 'FAILED';
        $this->completedAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function cancel()
    {
        $this->status = 'CANCELLED';
        $this->completedAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        return $this;
    }
}
