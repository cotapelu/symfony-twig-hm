<?php

declare(strict_types=1);

namespace App\Entity\Php;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;

#[ORM\Entity]
#[ORM\Table(name: 'php.procedure_applications')]
#[ORM\Index(name: 'idx_procedure_applications_citizen_id', columns: ['citizen_id'])]
#[ORM\Index(name: 'idx_procedure_applications_procedure_id', columns: ['procedure_id'])]
#[ORM\Index(name: 'idx_procedure_applications_status', columns: ['status'])]
#[ORM\Index(name: 'idx_procedure_applications_created_at', columns: ['created_at'])]
class ProcedureApplication
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $citizenId;

    #[ORM\Column(type: 'string', length: 36)]
    private string $procedureId;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'PENDING';

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $data = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $updatedAt;

    public function __construct(string $id, string $citizenId, string $procedureId)
    {
        $this->id = $id;
        $this->citizenId = $citizenId;
        $this->procedureId = $procedureId;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getCitizenId()
    {
        return $this->citizenId;
    }

    public function setCitizenId(string $citizenId)
    {
        $this->citizenId = $citizenId;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getProcedureId()
    {
        return $this->procedureId;
    }

    public function setProcedureId(string $procedureId)
    {
        $this->procedureId = $procedureId;
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

    public function getData()
    {
        return $this->data;
    }

    public function setData(?array $data)
    {
        $this->data = $data;
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

    public function isApproved()
    {
        return $this->status === 'APPROVED';
    }

    public function isRejected()
    {
        return $this->status === 'REJECTED';
    }

    public function isProcessing()
    {
        return $this->status === 'PROCESSING';
    }

    public function isCompleted()
    {
        return $this->status === 'COMPLETED';
    }

    public function isCancelled()
    {
        return $this->status === 'CANCELLED';
    }
}
