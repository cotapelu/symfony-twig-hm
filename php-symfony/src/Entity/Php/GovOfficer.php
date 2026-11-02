<?php

declare(strict_types=1);

namespace App\Entity\Php;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;

#[ORM\Entity]
#[ORM\Table(name: 'php.gov_officers')]
#[ORM\Index(name: 'idx_gov_officers_user_id', columns: ['user_id'])]
#[ORM\Index(name: 'idx_gov_officers_department', columns: ['department'])]
#[ORM\Index(name: 'idx_gov_officers_status', columns: ['status'])]
class GovOfficer
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $userId;

    #[ORM\Column(type: 'string', length: 100)]
    private string $department;

    #[ORM\Column(type: 'string', length: 100)]
    private string $position;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'ACTIVE';

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $updatedAt;

    public function __construct(string $id, string $userId, string $department, string $position)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->department = $department;
        $this->position = $position;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function getDepartment()
    {
        return $this->department;
    }

    public function setDepartment(string $department)
    {
        $this->department = $department;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getPosition()
    {
        return $this->position;
    }

    public function setPosition(string $position)
    {
        $this->position = $position;
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

    public function getMetadata()
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata)
    {
        $this->metadata = $metadata;
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

    public function isActive()
    {
        return $this->status === 'ACTIVE';
    }

    public function isSuspended()
    {
        return $this->status === 'SUSPENDED';
    }

    public function isDeleted()
    {
        return $this->status === 'DELETED';
    }
}
