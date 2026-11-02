<?php

declare(strict_types=1);

namespace App\Entity\Php;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;

#[ORM\Entity]
#[ORM\Table(name: 'php.citizen_incidents')]
#[ORM\Index(name: 'idx_citizen_incidents_citizen_id', columns: ['citizen_id'])]
#[ORM\Index(name: 'idx_citizen_incidents_type', columns: ['type'])]
#[ORM\Index(name: 'idx_citizen_incidents_status', columns: ['status'])]
#[ORM\Index(name: 'idx_citizen_incidents_created_at', columns: ['created_at'])]
class CitizenIncident
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $citizenId;

    #[ORM\Column(type: 'string', length: 50)]
    private string $type;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'PENDING';

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $data = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $updatedAt;

    public function __construct(string $id, string $citizenId, string $type)
    {
        $this->id = $id;
        $this->citizenId = $citizenId;
        $this->type = $type;
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

    public function getType()
    {
        return $this->type;
    }

    public function setType(string $type)
    {
        $this->type = $type;
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

    public function isInvestigating()
    {
        return $this->status === 'INVESTIGATING';
    }

    public function isResolved()
    {
        return $this->status === 'RESOLVED';
    }

    public function isClosed()
    {
        return $this->status === 'CLOSED';
    }

    public function isCancelled()
    {
        return $this->status === 'CANCELLED';
    }
}
