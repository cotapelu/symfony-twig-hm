<?php

namespace App\LiveSupport\Entity;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'live_support_conversations')]
class Conversation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $topic;

    #[ORM\Column(type: 'string', length: 50)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[Assert\Choice(choices: ['active', 'ended'])]
    private string $status = 'active';

    #[ORM\Column(type: 'simple_array')]
    private array $participants = [];

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $assignedAgentId = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $creatorId;

    #[ORM\OneToMany(mappedBy: 'conversation', targetEntity: Message::class, cascade: ['persist', 'remove'])]
    private Collection $messages;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Version]
    #[ORM\Column(type: 'integer')]
    private int $version = 1;

    public function __construct(string $topic, string $creatorId, array $participants = [])
    {
        $this->topic = $topic;
        $this->creatorId = trim($creatorId);
        // Ensure creator is in participants
        $participants[] = $creatorId;
        $this->participants = array_values(array_unique(array_map('trim', $participants)));
        if (empty($this->participants)) {
            throw new \InvalidArgumentException('At least one participant is required');
        }
        $now = new DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
        $this->messages = new ArrayCollection();
    }

    public function getId() { return $this->id; }

    public function getTopic() { return $this->topic; }
    public function setTopic(string $topic)
    {
        if (empty(trim($topic))) {
            throw new \InvalidArgumentException('Topic cannot be empty');
        }
        $this->topic = trim($topic);
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function getStatus() { return $this->status; }
    public function setStatus(string $status)
    {
        $allowed = ['active', 'ended'];
        if (!in_array($status, $allowed, true)) {
            throw new \InvalidArgumentException('Invalid status');
        }
        $this->status = $status;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function getParticipants() { return $this->participants; }
    public function addParticipant(string $userId)
    {
        $userId = trim($userId);
        if (empty($userId)) {
            throw new \InvalidArgumentException('Participant ID cannot be empty');
        }
        if (!in_array($userId, $this->participants, true)) {
            $this->participants[] = $userId;
            $this->updatedAt = new DateTimeImmutable();
        }
        return $this;
    }

    public function removeParticipant(string $userId)
    {
        $key = array_search($userId, $this->participants, true);
        if ($key !== false) {
            unset($this->participants[$key]);
            $this->participants = array_values($this->participants);
            $this->updatedAt = new DateTimeImmutable();
        }
        return $this;
    }

    public function hasParticipant(string $userId)
    {
        return in_array($userId, $this->participants, true);
    }

    public function getAssignedAgentId() { return $this->assignedAgentId; }
    public function setAssignedAgentId(?int $agentId)
    {
        $this->assignedAgentId = $agentId;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function getCreatedAt() { return $this->createdAt; }
    public function getUpdatedAt() { return $this->updatedAt; }
    public function getVersion() { return $this->version; }

    public function getCreatorId() { return $this->creatorId; }

    public function getMessages() { return $this->messages; }
    public function addMessage(Message $message)
    {
        if (!$this->messages->contains($message)) {
            $this->messages[] = $message;
            $message->setConversation($this);
        }
        return $this;
    }
}
