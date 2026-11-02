<?php

declare(strict_types=1);

namespace App\Messaging\Entity;

use App\Core\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'conversation_members')]
class ConversationMember
{
    public const ROLE_OWNER = 'owner';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_MEMBER = 'member';

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Conversation::class)]
    #[ORM\JoinColumn(name: 'conversation_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Conversation $conversation;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(type: 'string', length: 20)]
    private string $role = self::ROLE_MEMBER;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $joinedAt;

    public function __construct(Conversation $conversation, User $user)
    {
        $this->id = Uuid::uuid4()->toString();
        $this->conversation = $conversation;
        $this->user = $user;
        $this->joinedAt = new \DateTime();
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

    public function getUser()
    {
        return $this->user;
    }

    public function setUser(User $user)
    {
        $this->user = $user;
        return $this;
    }

    public function getRole()
    {
        return $this->role;
    }

    public function setRole(string $role)
    {
        $this->role = $role;
        return $this;
    }

    public function getJoinedAt()
    {
        return $this->joinedAt;
    }

    public function isOwner()
    {
        return $this->role === self::ROLE_OWNER;
    }

    public function isAdmin()
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function toArray()
    {
        return [
            'id' => $this->id,
            'conversationId' => $this->conversation->getId(),
            'userId' => $this->user->getId(),
            'role' => $this->role,
            'joinedAt' => $this->joinedAt->format('Y-m-d H:i:s'),
        ];
    }
}
