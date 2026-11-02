<?php

namespace App\Call\Entity;

use App\Attribute\Module;

#[Module(name: 'Call', dependencies: ['User', 'Conversation'])]
class CallRecord
{
    private ?int $id = null;
    private int $callerId;
    private ?int $receiverId = null;
    private ?int $conversationId = null;
    private string $type; // audio, video
    private string $status; // initiated, answered, missed, ended
    private int $duration = 0; // seconds
    private \DateTime $startedAt;
    private ?\DateTime $answeredAt = null;
    private ?\DateTime $endedAt = null;

    public function __construct(int $callerId, ?int $receiverId, string $type)
    {
        $this->callerId = $callerId;
        $this->receiverId = $receiverId;
        $this->type = $type;
        $this->status = 'initiated';
        $this->startedAt = new \DateTime();
    }

    // Getters and setters
    public function getId() { return $this->id; }

    public function getCallId()
    {
        return $this->id;
    }
    public function getCallerId() { return $this->callerId; }
    public function getReceiverId() { return $this->receiverId; }
    public function getConversationId() { return $this->conversationId; }
    public function getType() { return $this->type; }
    public function getStatus() { return $this->status; }
    public function getDuration() { return $this->duration; }
    public function getStartedAt() { return $this->startedAt; }
    public function getAnsweredAt() { return $this->answeredAt; }
    public function getEndedAt() { return $this->endedAt; }

    public function setId(?int $id) { $this->id = $id; return $this; }
    public function setConversationId(?int $id) { $this->conversationId = $id; return $this; }
    public function setAnswered() { $this->answeredAt = new \DateTime(); $this->status = 'answered'; return $this; }
    public function end() { $this->endedAt = new \DateTime(); $this->status = 'ended'; 
        $this->duration = $this->answeredAt ? $this->endedAt->getTimestamp() - $this->startedAt->getTimestamp() : 0; return $this; }
    public function markMissed() { $this->status = 'missed'; return $this; }
}
