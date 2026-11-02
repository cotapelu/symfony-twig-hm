<?php

declare(strict_types=1);

namespace App\Messaging\Entity;

use App\Core\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'messages')]
class Message
{
    public const TYPE_TEXT = 'text';
    public const TYPE_IMAGE = 'image';
    public const TYPE_VIDEO = 'video';
    public const TYPE_AUDIO = 'audio';
    public const TYPE_FILE = 'file';
    public const TYPE_SYSTEM = 'system';

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Conversation::class)]
    #[ORM\JoinColumn(name: 'conversation_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Conversation $conversation;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'sender_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    private ?User $sender = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $type = self::TYPE_TEXT;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $content = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $attachments = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $readAt = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    public function __construct(Conversation $conversation, string $type)
    {
        $this->id = Uuid::uuid4()->toString();
        $this->conversation = $conversation;
        $this->type = $type;
        $this->createdAt = new \DateTime();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getConversation()
    {
        return $this->conversation;
    }

    public function setConversation(Conversation $conversation)
    {
        $this->conversation = $conversation;
        return $this;
    }

    public function getSender()
    {
        return $this->sender;
    }

    public function setSender(?User $sender)
    {
        $this->sender = $sender;
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

    public function getContent()
    {
        return $this->content;
    }

    public function setContent(?string $content)
    {
        $this->content = $content;
        return $this;
    }

    public function getAttachments()
    {
        return $this->attachments;
    }

    public function setAttachments(?array $attachments)
    {
        $this->attachments = $attachments;
        return $this;
    }

    public function getReadAt()
    {
        return $this->readAt;
    }

    public function setReadAt(?\DateTime $readAt)
    {
        $this->readAt = $readAt;
        return $this;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function isRead()
    {
        return $this->readAt !== null;
    }

    public function markAsRead()
    {
        $this->readAt = new \DateTime();
        return $this;
    }

    public function toArray()
    {
        return [
            'id' => $this->id,
            'conversationId' => $this->conversation->getId(),
            'senderId' => $this->sender?->getId(),
            'type' => $this->type,
            'content' => $this->content,
            'attachments' => $this->attachments,
            'readAt' => $this->readAt?->format('Y-m-d H:i:s'),
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }
}
