<?php

namespace App\LiveSupport\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'live_support_messages')]
class Message
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Conversation::class, inversedBy: 'messages')]
    #[ORM\JoinColumn(name: 'conversation_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Conversation $conversation;

    #[ORM\Column(type: 'string')]
    #[Assert\NotBlank]
    private string $senderId;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank]
    private string $content;

    #[ORM\Column(type: 'boolean')]
    private bool $isRead = false;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Version]
    #[ORM\Column(type: 'integer')]
    private int $version = 1;

    public function __construct(Conversation $conversation, string $senderId, string $content)
    {
        $this->conversation = $conversation;
        $this->senderId = trim($senderId);
        $this->content = $content;
        $this->createdAt = new DateTimeImmutable();

        if (empty($this->senderId)) {
            throw new \InvalidArgumentException('Sender ID cannot be empty');
        }
        if (empty(trim($this->content))) {
            throw new \InvalidArgumentException('Message content cannot be empty');
        }
    }

    public function getId() { return $this->id; }

    public function getConversation() { return $this->conversation; }
    public function setConversation(Conversation $conversation)
    {
        $this->conversation = $conversation;
        return $this;
    }

    public function getSenderId() { return $this->senderId; }
    public function setSenderId(string $senderId)
    {
        $this->senderId = trim($senderId);
        return $this;
    }

    public function getContent() { return $this->content; }
    public function setContent(string $content)
    {
        if (empty(trim($content))) {
            throw new \InvalidArgumentException('Message content cannot be empty');
        }
        $this->content = $content;
        return $this;
    }

    public function isRead() { return $this->isRead; }
    public function setRead(bool $isRead)
    {
        $this->isRead = $isRead;
        return $this;
    }

    public function getCreatedAt() { return $this->createdAt; }
    public function getVersion() { return $this->version; }
}
