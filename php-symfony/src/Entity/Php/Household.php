<?php

declare(strict_types=1);

namespace App\Entity\Php;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;

#[ORM\Entity]
#[ORM\Table(name: 'php.households')]
#[ORM\Index(name: 'idx_households_ward_id', columns: ['ward_id'])]
#[ORM\Index(name: 'idx_households_head_id', columns: ['head_id'])]
class Household
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $wardId = null;

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $headId = null;

    #[ORM\Column(type: 'string', length: 500)]
    private string $address;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $updatedAt;

    public function __construct(string $id, string $address)
    {
        $this->id = $id;
        $this->address = $address;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getWardId()
    {
        return $this->wardId;
    }

    public function setWardId(?string $wardId)
    {
        $this->wardId = $wardId;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getHeadId()
    {
        return $this->headId;
    }

    public function setHeadId(?string $headId)
    {
        $this->headId = $headId;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getAddress()
    {
        return $this->address;
    }

    public function setAddress(string $address)
    {
        $this->address = $address;
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

    public function hasHead()
    {
        return $this->headId !== null;
    }

    public function hasWard()
    {
        return $this->wardId !== null;
    }
}
