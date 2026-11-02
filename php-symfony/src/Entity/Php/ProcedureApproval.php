<?php

declare(strict_types=1);

namespace App\Entity\Php;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;

#[ORM\Entity]
#[ORM\Table(name: 'php.procedure_approvals')]
#[ORM\Index(name: 'idx_procedure_approvals_application_id', columns: ['application_id'])]
#[ORM\Index(name: 'idx_procedure_approvals_officer_id', columns: ['officer_id'])]
#[ORM\Index(name: 'idx_procedure_approvals_created_at', columns: ['created_at'])]
class ProcedureApproval
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $applicationId;

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $officerId = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $decision;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    public function __construct(string $id, string $applicationId, string $decision)
    {
        $this->id = $id;
        $this->applicationId = $applicationId;
        $this->decision = $decision;
        $this->createdAt = new \DateTime();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getApplicationId()
    {
        return $this->applicationId;
    }

    public function setApplicationId(string $applicationId)
    {
        $this->applicationId = $applicationId;
        return $this;
    }

    public function getOfficerId()
    {
        return $this->officerId;
    }

    public function setOfficerId(?string $officerId)
    {
        $this->officerId = $officerId;
        return $this;
    }

    public function getDecision()
    {
        return $this->decision;
    }

    public function setDecision(string $decision)
    {
        $this->decision = $decision;
        return $this;
    }

    public function getNotes()
    {
        return $this->notes;
    }

    public function setNotes(?string $notes)
    {
        $this->notes = $notes;
        return $this;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function isApproved()
    {
        return $this->decision === 'APPROVED';
    }

    public function isRejected()
    {
        return $this->decision === 'REJECTED';
    }

    public function isPending()
    {
        return $this->decision === 'PENDING';
    }

    public function isCancelled()
    {
        return $this->decision === 'CANCELLED';
    }
}
