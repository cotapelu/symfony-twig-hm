<?php

declare(strict_types=1);

namespace App\Government\Entity;

use App\Citizen\Entity\Citizen;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'procedure_applications')]
#[ORM\HasLifecycleCallbacks]
class ProcedureApplication
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Citizen::class)]
    #[ORM\JoinColumn(name: 'citizen_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Citizen $citizen;

    #[ORM\ManyToOne(targetEntity: AdministrativeProcedure::class)]
    #[ORM\JoinColumn(name: 'procedure_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private AdministrativeProcedure $procedure;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $data = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $submittedAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $updatedAt;

    public function __construct(Citizen $citizen, AdministrativeProcedure $procedure)
    {
        $this->citizen = $citizen;
        $this->procedure = $procedure;
        $this->submittedAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getCitizen()
    {
        return $this->citizen;
    }

    public function setCitizen(Citizen $citizen)
    {
        $this->citizen = $citizen;
        return $this;
    }

    public function getProcedure()
    {
        return $this->procedure;
    }

    public function setProcedure(AdministrativeProcedure $procedure)
    {
        $this->procedure = $procedure;
        return $this;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus(string $status)
    {
        $this->status = $status;
        return $this;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setData(?array $data)
    {
        $this->data = $data;
        return $this;
    }

    public function getSubmittedAt()
    {
        return $this->submittedAt;
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved()
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected()
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isProcessing()
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    public function isCompleted()
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    #[ORM\PreUpdate]
    public function updateTimestamp()
    {
        $this->updatedAt = new \DateTime();
    }

    public function toArray()
    {
        return [
            'id' => $this->id,
            'citizenId' => $this->citizen->getId(),
            'procedureId' => $this->procedure->getId(),
            'status' => $this->status,
            'data' => $this->data,
            'submittedAt' => $this->submittedAt->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
