<?php

declare(strict_types=1);

namespace App\Entity\Php;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;

#[ORM\Entity]
#[ORM\Table(name: 'php.businesses')]
#[ORM\Index(name: 'idx_businesses_owner_id', columns: ['owner_id'])]
#[ORM\Index(name: 'idx_businesses_type', columns: ['type'])]
#[ORM\Index(name: 'idx_businesses_status', columns: ['status'])]
class Business
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $ownerId;

    #[ORM\Column(type: 'string', length: 100)]
    private string $name;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $taxCode = null;

    #[ORM\Column(type: 'string', length: 50)]
    private string $type;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'ACTIVE';

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $updatedAt;

    public function __construct(string $id, string $ownerId, string $name, string $type)
    {
        $this->id = $id;
        $this->ownerId = $ownerId;
        $this->name = $name;
        $this->type = $type;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getOwnerId()
    {
        return $this->ownerId;
    }

    public function setOwnerId(string $ownerId)
    {
        $this->ownerId = $ownerId;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName(string $name)
    {
        $this->name = $name;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getAddress()
    {
        return $this->address;
    }

    public function setAddress(?string $address)
    {
        $this->address = $address;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getPhone()
    {
        return $this->phone;
    }

    public function setPhone(?string $phone)
    {
        $this->phone = $phone;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail(?string $email)
    {
        $this->email = $email;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getTaxCode()
    {
        return $this->taxCode;
    }

    public function setTaxCode(?string $taxCode)
    {
        $this->taxCode = $taxCode;
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
}
