<?php

declare(strict_types=1);

namespace App\Government\Entity;

use App\Citizen\Entity\Citizen;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'citizen_incidents')]
#[ORM\HasLifecycleCallbacks]
class CitizenIncident
{
    public const TYPE_ANTT = 'ANTT';
    public const TYPE_SECURITY = 'SECURITY';
    public const TYPE_MEDICAL = 'MEDICAL';
    public const TYPE_OTHER = 'OTHER';

    public const STATUS_OPEN = 'open';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_CLOSED = 'closed';

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Citizen::class)]
    #[ORM\JoinColumn(name: 'citizen_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    private ?Citizen $citizen = null;

    #[ORM\Column(type: 'string', length: 50)]
    private string $type;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = self::STATUS_OPEN;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $data = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $updatedAt;

    public function __construct(string $type)
    {
        $this->id = Uuid::uuid4()->toString();
        $this->type = $type;
        $this->createdAt = new \DateTime();
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

    public function setCitizen(?Citizen $citizen)
    {
        $this->citizen = $citizen;
        return $this;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType(string $type)
    {
        $this->type = $type;
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

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function isOpen()
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function isInProgress()
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function isResolved()
    {
        return $this->status === self::STATUS_RESOLVED;
    }

    public function isClosed()
    {
        return $this->status === self::STATUS_CLOSED;
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
            'citizenId' => $this->citizen?->getId(),
            'type' => $this->type,
            'status' => $this->status,
            'data' => $this->data,
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
