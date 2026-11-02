<?php

declare(strict_types=1);

namespace App\Rewards\Entity;

use App\Attribute\Module;

#[Module(name: 'Rewards', dependencies: ['User'])]
class Badge
{
    private ?int $id = null;
    private string $userId;
    private string $type;
    private string $name;
    private ?string $description = null;
    private ?string $icon = null;
    private array $metadata = [];
    private \DateTime $earnedAt;

    public function __construct(
        string $userId,
        string $type,
        string $name,
        ?string $description = null,
        ?string $icon = null,
        ?array $metadata = null
    ) {
        $this->userId = $userId;
        $this->type = $type;
        $this->name = $name;
        $this->description = $description;
        $this->icon = $icon;
        $this->metadata = $metadata ?? [];
        $this->earnedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getEarnedAt(): \DateTime
    {
        return $this->earnedAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'userId' => $this->userId,
            'type' => $this->type,
            'name' => $this->name,
            'description' => $this->description,
            'icon' => $this->icon,
            'metadata' => $this->metadata,
            'earnedAt' => $this->earnedAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
