<?php

declare(strict_types=1);

namespace App\Conversation\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Doctrine entity for ConversationParticipant - Many-to-many relationship.
 */
#[ORM\Entity]
#[ORM\Table(name: 'communication_conversation_participants')]
#[ORM\UniqueConstraint(name: 'unique_conversation_user', columns: ['conversation_id', 'user_id'])]
class ConversationParticipant
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'conversation_id', type: 'string', length: 36)]
    private string $conversationId;

    #[ORM\Column(name: 'user_id', type: 'string', length: 36)]
    private string $userId;

    #[ORM\Column(name: 'joined_at', type: 'datetime')]
    private \DateTime $joinedAt;

    #[ORM\Column(name: 'role', type: 'string', length: 20, options: ['default' => 'member'])]
    private string $role;

    #[ORM\Column(name: 'is_active', type: 'boolean', options: ['default' => true])]
    private bool $isActive;

    public function __construct(
        string $conversationId,
        string $userId,
        string $role = 'member',
    ) {
        $this->id = uniqid('cp_', true);
        $this->conversationId = $conversationId;
        $this->userId = $userId;
        $this->role = $role;
        $this->joinedAt = new \DateTime();
        $this->isActive = true;
    }

    // Getters
    public function getId()
    {
        return $this->id;
    }

    public function getConversationId()
    {
        return $this->conversationId;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function getJoinedAt()
    {
        return $this->joinedAt;
    }

    public function getRole()
    {
        return $this->role;
    }

    public function isActive()
    {
        return $this->isActive;
    }

    // Setters
    public function setRole(string $role)
    {
        $this->role = $role;
    }

    public function setIsActive(bool $isActive)
    {
        $this->isActive = $isActive;
    }
}
