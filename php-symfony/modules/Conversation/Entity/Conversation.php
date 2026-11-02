<?php

namespace App\Conversation\Entity;

use App\Attribute\Module;
use Doctrine\ORM\Mapping as ORM;

#[Module(name: 'Conversation', dependencies: ['User', 'Message'])]
#[ORM\Entity]
#[ORM\Table(name: 'conversations')]
class Conversation implements \JsonSerializable
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private ?int $id = null;
    #[ORM\Column(type: 'json')]
    private array $participantIds;
    #[ORM\Column(type: 'string', length: 20)]
    private string $type; // direct, group
    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $title = null;
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $createdBy = null;
    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $avatar = null;
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $lastMessageId = null;
    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;
    #[ORM\Column(type: 'datetime')]
    private \DateTime $updatedAt;
    #[ORM\Column(type: 'boolean')]
    private bool $isArchived = false;

    public function __construct(array $participantIds, string $type)
    {
        $this->participantIds = $participantIds;
        $this->type = $type;
        $this->id = null; // Let DB assign auto-increment ID
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    // Getters and setters
    public function getId() { return $this->id; }
    public function getParticipantIds() { return $this->participantIds; }
    public function getType() { return $this->type; }
    public function getTitle() { return $this->title; }
    public function getCreatedBy() { return $this->createdBy; }
    public function getAvatar() { return $this->avatar; }
    public function getLastMessageId() { return $this->lastMessageId; }
    public function getCreatedAt() { return $this->createdAt; }
    public function getUpdatedAt() { return $this->updatedAt; }

    public function setId(?int $id) { $this->id = $id; return $this; }
    public function setTitle(?string $title) { $this->title = $title; return $this; }
    public function setCreatedBy(int $userId) { $this->createdBy = $userId; return $this; }
    public function setAvatar(?string $avatar) { $this->avatar = $avatar; return $this; }
    public function setLastMessageId(?int $id) { $this->lastMessageId = $id; return $this; }
    public function setParticipantIds(array $ids) { $this->participantIds = $ids; return $this; }

    public function isArchived(): bool
    {
        return $this->isArchived;
    }

    public function setIsArchived(bool $isArchived): self
    {
        $this->isArchived = $isArchived;
        return $this;
    }

    public function hasParticipant(int $userId): bool
    {
        return in_array($userId, $this->participantIds, true);
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'participantIds' => $this->participantIds,
            'type' => $this->type,
            'title' => $this->title,
            'createdBy' => $this->createdBy,
            'avatar' => $this->avatar,
            'lastMessageId' => $this->lastMessageId,
            'createdAt' => $this->createdAt ? $this->createdAt->format(\DateTimeInterface::ATOM) : null,
            'updatedAt' => $this->updatedAt ? $this->updatedAt->format(\DateTimeInterface::ATOM) : null,
            'isArchived' => $this->isArchived,
        ];
    }
}
