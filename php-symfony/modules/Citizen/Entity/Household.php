<?php

declare(strict_types=1);

namespace App\Citizen\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'households')]
#[ORM\HasLifecycleCallbacks]
class Household
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private string $id;

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $wardId = null;

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $headId = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $address;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $updatedAt;

    public function __construct(string $address)
    {
        $this->id = Uuid::uuid4()->toString();
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
        return $this;
    }

    public function getHeadId()
    {
        return $this->headId;
    }

    public function setHeadId(?string $headId)
    {
        $this->headId = $headId;
        return $this;
    }

    public function getAddress()
    {
        return $this->address;
    }

    public function setAddress(string $address)
    {
        $this->address = $address;
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

    #[ORM\PreUpdate]
    public function updateTimestamp()
    {
        $this->updatedAt = new \DateTime();
    }

    public function toArray()
    {
        return [
            'id' => $this->id,
            'wardId' => $this->wardId,
            'headId' => $this->headId,
            'address' => $this->address,
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
