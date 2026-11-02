<?php

declare(strict_types=1);

namespace App\OptimizedSchema\Entity;

use App\OptimizedSchema\Entity\Trait\IdentifiableTrait;
use App\OptimizedSchema\Entity\Trait\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'households')]
#[ORM\Index(name: 'idx_households_ward_id', columns: ['ward_id'])]
class Household
{
    use IdentifiableTrait;
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private string $id;

    #[ORM\Column(name: 'ward_id', type: 'string', length: 50, nullable: true)]
    private ?string $wardId;

    #[ORM\Column(name: 'head_id', type: 'uuid', nullable: true)]
    private ?string $headId;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $address;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    private \DateTime $updatedAt;

    public function __construct(string $id)
    {
        $this->id = $id;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getWardId(): ?string
    {
        return $this->wardId;
    }

    public function setWardId(?string $wardId): self
    {
        $this->wardId = $wardId;
        return $this;
    }

    public function getHeadId(): ?string
    {
        return $this->headId;
    }

    public function setHeadId(?string $headId): self
    {
        $this->headId = $headId;
        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): self
    {
        $this->address = $address;
        return $this;
    }
}