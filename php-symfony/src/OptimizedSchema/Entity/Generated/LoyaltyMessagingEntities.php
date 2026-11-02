<?php

declare(strict_types=1);

namespace App\OptimizedSchema\Entity\Generated;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'loyalty_programs')]
class LoyaltyProgram
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private string $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'string', length: 20)]
    private string $type;

    #[ORM\Column(type: 'json', options: ['default' => '{}'])]
    private array $rules = [];

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'active';

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    private \DateTime $updatedAt;

    public const TYPE_POINTS = 'POINTS';
    public const TYPE_BADGES = 'BADGES';

    public function __construct(string $id, string $name, string $type)
    {
        $this->id = $id;
        $this->name = $name;
        $this->type = $type;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): string { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getType(): string { return $this->type; }
    public function getRules(): array { return $this->rules; }
    public function setRules(array $rules): self { $this->rules = $rules; return $this; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }
}

#[ORM\Entity]
#[ORM\Table(name: 'loyalty_accounts')]
#[ORM\UniqueConstraint(name: 'uq_loyalty_accounts', columns: ['user_id', 'program_id'])]
#[ORM\Index(name: 'idx_loyalty_accounts_user_id', columns: ['user_id'])]
class LoyaltyAccount
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private string $id;

    #[ORM\Column(name: 'user_id', type: 'uuid')]
    private string $userId;

    #[ORM\Column(name: 'program_id', type: 'uuid')]
    private string $programId;

    #[ORM\Column(type: 'integer')]
    private int $points = 0;

    #[ORM\Column(type: 'string', length: 20)]
    private string $tier = 'bronze';

    #[ORM\Column(type: 'json', options: ['default' => '{}'])]
    private array $metadata = [];

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    private \DateTime $updatedAt;

    public function __construct(string $id, string $userId, string $programId)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->programId = $programId;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): string { return $this->id; }
    public function getUserId(): string { return $this->userId; }
    public function getProgramId(): string { return $this->programId; }
    public function getPoints(): int { return $this->points; }
    public function addPoints(int $points): self { $this->points += $points; return $this; }
    public function getTier(): string { return $this->tier; }
    public function setTier(string $tier): self { $this->tier = $tier; return $this; }
}

#[ORM\Entity]
#[ORM\Table(name: 'achievements')]
#[ORM\Index(name: 'idx_achievements_user_id', columns: ['user_id'])]
#[ORM\Index(name: 'idx_achievements_program_id', columns: ['program_id'])]
class Achievement
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private string $id;

    #[ORM\Column(name: 'user_id', type: 'uuid')]
    private string $userId;

    #[ORM\Column(name: 'program_id', type: 'uuid', nullable: true)]
    private ?string $programId;

    #[ORM\Column(type: 'string', length: 50)]
    private string $type;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(name: 'earned_at', type: 'datetime')]
    private \DateTime $earnedAt;

    #[ORM\Column(type: 'json', options: ['default' => '{}'])]
    private array $metadata = [];

    public function __construct(string $id, string $userId, string $type, string $name)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->type = $type;
        $this->name = $name;
        $this->earnedAt = new \DateTime();
    }

    public function getId(): string { return $this->id; }
    public function getUserId(): string { return $this->userId; }
    public function getType(): string { return $this->type; }
    public function getName(): string { return $this->name; }
    public function getEarnedAt(): \DateTime { return $this->earnedAt; }
}

#[ORM\Entity]
#[ORM\Table(name: 'conversations')]
#[ORM\Index(name: 'idx_conversations_created_by', columns: ['created_by'])]
#[ORM\Index(name: 'idx_conversations_type', columns: ['type'])]
class Conversation
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private string $id;

    #[ORM\Column(type: 'string', length: 20)]
    private string $type;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $name;

    #[ORM\Column(name: 'created_by', type: 'uuid', nullable: true)]
    private ?string $createdBy;

    #[ORM\Column(type: 'json', options: ['default' => '{}'])]
    private array $metadata = [];

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    private \DateTime $updatedAt;

    public const TYPE_DIRECT = 'DIRECT';
    public const TYPE_GROUP = 'GROUP';

    public function __construct(string $id, string $type)
    {
        $this->id = $id;
        $this->type = $type;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): string { return $this->id; }
    public function getType(): string { return $this->type; }
    public function getName(): ?string { return $this->name; }
    public function setName(?string $name): self { $this->name = $name; return $this; }
    public function getCreatedBy(): ?string { return $this->createdBy; }
    public function setCreatedBy(?string $createdBy): self { $this->createdBy = $createdBy; return $this; }
}

#[ORM\Entity]
#[ORM\Table(name: 'conversation_members')]
#[ORM\UniqueConstraint(name: 'uq_conversation_member', columns: ['conversation_id', 'user_id'])]
#[ORM\Index(name: 'idx_conversation_members_conversation_id', columns: ['conversation_id'])]
class ConversationMember
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private string $id;

    #[ORM\Column(name: 'conversation_id', type: 'uuid')]
    private string $conversationId;

    #[ORM\Column(name: 'user_id', type: 'uuid')]
    private string $userId;

    #[ORM\Column(type: 'string', length: 20)]
    private string $role = 'member';

    #[ORM\Column(name: 'joined_at', type: 'datetime')]
    private \DateTime $joinedAt;

    public function __construct(string $id, string $conversationId, string $userId)
    {
        $this->id = $id;
        $this->conversationId = $conversationId;
        $this->userId = $userId;
        $this->joinedAt = new \DateTime();
    }

    public function getId(): string { return $this->id; }
    public function getConversationId(): string { return $this->conversationId; }
    public function getUserId(): string { return $this->userId; }
    public function getRole(): string { return $this->role; }
    public function setRole(string $role): self { $this->role = $role; return $this; }
}

#[ORM\Entity]
#[ORM\Table(name: 'messages')]
#[ORM\Index(name: 'idx_messages_conversation_id', columns: ['conversation_id'])]
#[ORM\Index(name: 'idx_messages_sender_id', columns: ['sender_id'])]
#[ORM\Index(name: 'idx_messages_created_at', columns: ['created_at'])]
class Message
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private string $id;

    #[ORM\Column(name: 'conversation_id', type: 'uuid')]
    private string $conversationId;

    #[ORM\Column(name: 'sender_id', type: 'uuid', nullable: true)]
    private ?string $senderId;

    #[ORM\Column(type: 'string', length: 20)]
    private string $type = 'text';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $content;

    #[ORM\Column(type: 'json', options: ['default' => '[]'])]
    private array $attachments = [];

    #[ORM\Column(name: 'read_at', type: 'datetime', nullable: true)]
    private ?\DateTime $readAt;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTime $createdAt;

    public function __construct(string $id, string $conversationId)
    {
        $this->id = $id;
        $this->conversationId = $conversationId;
        $this->createdAt = new \DateTime();
    }

    public function getId(): string { return $this->id; }
    public function getConversationId(): string { return $this->conversationId; }
    public function getSenderId(): ?string { return $this->senderId; }
    public function setSenderId(?string $senderId): self { $this->senderId = $senderId; return $this; }
    public function getType(): string { return $this->type; }
    public function setType(string $type): self { $this->type = $type; return $this; }
    public function getContent(): ?string { return $this->content; }
    public function setContent(?string $content): self { $this->content = $content; return $this; }
    public function getAttachments(): array { return $this->attachments; }
    public function setAttachments(array $attachments): self { $this->attachments = $attachments; return $this; }
    public function getReadAt(): ?\DateTime { return $this->readAt; }
    public function getCreatedAt(): \DateTime { return $this->createdAt; }
}