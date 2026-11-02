<?php

declare(strict_types=1);

namespace App\Government\Entity;

use App\Government\Entity\GovOfficer;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'procedure_approvals')]
#[ORM\HasLifecycleCallbacks]
class ProcedureApproval
{
    public const DECISION_APPROVED = 'approved';
    public const DECISION_REJECTED = 'rejected';
    public const DECISION_PENDING = 'pending';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: ProcedureApplication::class)]
    #[ORM\JoinColumn(name: 'application_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ProcedureApplication $application;

    #[ORM\ManyToOne(targetEntity: GovOfficer::class)]
    #[ORM\JoinColumn(name: 'officer_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    private ?GovOfficer $officer = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $decision = self::DECISION_PENDING;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $approvedAt;

    public function __construct(ProcedureApplication $application)
    {
        $this->application = $application;
        $this->approvedAt = new \DateTime();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getApplication()
    {
        return $this->application;
    }

    public function setApplication(ProcedureApplication $application)
    {
        $this->application = $application;
        return $this;
    }

    public function getOfficer()
    {
        return $this->officer;
    }

    public function setOfficer(?GovOfficer $officer)
    {
        $this->officer = $officer;
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

    public function getApprovedAt()
    {
        return $this->approvedAt;
    }

    public function isApproved()
    {
        return $this->decision === self::DECISION_APPROVED;
    }

    public function isRejected()
    {
        return $this->decision === self::DECISION_REJECTED;
    }

    public function isPending()
    {
        return $this->decision === self::DECISION_PENDING;
    }

    public function toArray()
    {
        return [
            'id' => $this->id,
            'applicationId' => $this->application->getId(),
            'officerId' => $this->officer?->getId(),
            'decision' => $this->decision,
            'notes' => $this->notes,
            'approvedAt' => $this->approvedAt->format('Y-m-d H:i:s'),
        ];
    }
}
