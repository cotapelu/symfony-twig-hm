<?php

declare(strict_types=1);

namespace App\Health\Entity;

use App\Attribute\Module;
use Doctrine\ORM\Mapping as ORM;

#[Module(name: 'Health', dependencies: ['User'])]
#[ORM\Entity(repositoryClass: 'App\Health\Repository\HealthRecordRepository')]
#[ORM\Table(name: 'health_records')]
class HealthRecord
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'user_id', type: 'integer')]
    private int $userId;

    #[ORM\Column(type: 'string', length: 50)]
    private string $type;

    #[ORM\Column(name: 'value', type: 'json', nullable: true)]
    private ?array $value = null;

    #[ORM\Column(name: 'metadata', type: 'json', nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTime $createdAt;

    public function __construct(int $userId, string $type, ?array $value = null, ?array $metadata = null)
    {
        $this->userId = $userId;
        $this->type = $type;
        $this->value = $value;
        $this->metadata = $metadata;
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getValue(): ?array
    {
        return $this->value;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }
}