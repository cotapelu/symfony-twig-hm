<?php

declare(strict_types=1);

namespace App\Message\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Doctrine entity for Message - Infrastructure layer.
 */
#[ORM\Entity]
#[ORM\Table(name: 'communication_messages')]
#[ORM\Index(name: 'idx_message_conversation', columns: ['conversation_id'])]
#[ORM\Index(name: 'idx_message_sender', columns: ['sender_id'])]
#[ORM\Index(name: 'idx_message_created', columns: ['created_at'])]
#[ORM\Index(name: 'idx_message_conversation_created', columns: ['conversation_id', 'created_at'])]
#[ORM\Index(name: 'idx_message_thread', columns: ['thread_id'])]
class Message
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'conversation_id', type: 'string', length: 36)]
    private string $conversationId;

    #[ORM\Column(name: 'sender_id', type: 'string', length: 36)]
    private string $senderId;

    #[ORM\Column(type: 'text')]
    private string $content;

    #[ORM\Column(type: 'string', length: 20)]
    private string $type;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime', nullable: true)]
    private ?\DateTime $updatedAt;

    #[ORM\Column(name: 'is_edited', type: 'boolean', options: ['default' => false])]
    private bool $isEdited;

    #[ORM\Column(name: 'edit_history', type: 'text', nullable: true)]
    private ?string $editHistory;

    #[ORM\Column(name: 'is_deleted', type: 'boolean', options: ['default' => false])]
    private bool $isDeleted;

    #[ORM\Column(name: 'edited_at', type: 'datetime', nullable: true)]
    private ?\DateTime $editedAt;

    #[ORM\Column(name: 'deleted_at', type: 'datetime', nullable: true)]
    private ?\DateTime $deletedAt;

    #[ORM\Column(name: 'deleted_by', type: 'string', length: 36, nullable: true)]
    private ?string $deletedBy;

    #[ORM\Column(name: 'is_pinned', type: 'boolean', options: ['default' => false])]
    private bool $isPinned;

    #[ORM\Column(name: 'reply_to_message_id', type: 'string', length: 36, nullable: true)]
    private ?string $replyToMessageId;

    #[ORM\Column(name: 'thread_id', type: 'string', length: 36, nullable: true)]
    private ?string $threadId;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $attachments;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $reactions;

    #[ORM\Column(name: 'delivered_at', type: 'datetime', nullable: true)]
    private ?\DateTime $deliveredAt;

    #[ORM\Column(name: 'read_at', type: 'datetime', nullable: true)]
    private ?\DateTime $readAt;

    public function __construct(
        string $id,
        string $conversationId,
        string $senderId,
        string $content,
        string $type = 'text',
        ?string $threadId = null,
    ) {
        $this->id = $id;
        $this->conversationId = $conversationId;
        $this->senderId = $senderId;
        $this->content = $content;
        $this->type = $type;
        $this->createdAt = new \DateTime();
        $this->updatedAt = null;
        $this->isEdited = false;
        $this->editHistory = null;
        $this->isDeleted = false;
        $this->editedAt = null;
        $this->deletedAt = null;
        $this->deletedBy = null;
        $this->isPinned = false;
        $this->replyToMessageId = null;
        $this->threadId = $threadId;
        $this->attachments = null;
        $this->reactions = null;
        $this->deliveredAt = null;
        $this->readAt = null;
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

    public function getSenderId()
    {
        return $this->senderId;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function isEdited()
    {
        return $this->isEdited;
    }

    public function getReplyToMessageId()
    {
        return $this->replyToMessageId;
    }

    public function getThreadId()
    {
        return $this->threadId;
    }

    public function getAttachments()
    {
        if (!$this->attachments) {
            return null;
        }
        $data = json_decode($this->attachments, true);

        return JSON_ERROR_NONE === json_last_error() ? $data : null;
    }

    public function getReactions()
    {
        if (!$this->reactions) {
            return null;
        }
        $data = json_decode($this->reactions, true);

        return JSON_ERROR_NONE === json_last_error() ? $data : null;
    }

    public function getDeliveredAt()
    {
        return $this->deliveredAt;
    }

    public function getReadAt()
    {
        return $this->readAt;
    }

    // Setters
    public function setContent(string $content)
    {
        $this->content = $content;
        $this->isEdited = true;
        $this->updatedAt = new \DateTime();
    }

    public function setIsEdited(bool $isEdited)
    {
        $this->isEdited = $isEdited;
    }

    public function setUpdatedAt(?\DateTime $updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    public function setReplyToMessageId(?string $replyToMessageId)
    {
        $this->replyToMessageId = $replyToMessageId;
    }

    public function setThreadId(?string $threadId)
    {
        $this->threadId = $threadId;
    }

    public function setAttachments(?array $attachments)
    {
        $this->attachments = $attachments ? json_encode($attachments) : null;
    }

    public function setReactions(?array $reactions)
    {
        $this->reactions = $reactions ? json_encode($reactions) : null;
    }

    public function setDeliveredAt(?\DateTime $deliveredAt)
    {
        $this->deliveredAt = $deliveredAt;
    }

    public function setReadAt(?\DateTime $readAt)
    {
        $this->readAt = $readAt;
    }

    public function getEditHistory()
    {
        if (!$this->editHistory) {
            return null;
        }
        $data = json_decode($this->editHistory, true);

        return JSON_ERROR_NONE === json_last_error() ? $data : null;
    }

    public function setEditHistory(?array $editHistory)
    {
        $this->editHistory = $editHistory ? json_encode($editHistory) : null;
    }

    public function getEditedAt()
    {
        return $this->editedAt;
    }

    public function setEditedAt(?\DateTime $editedAt)
    {
        $this->editedAt = $editedAt;
    }

    public function isDeleted()
    {
        return $this->isDeleted;
    }

    public function setIsDeleted(bool $isDeleted)
    {
        $this->isDeleted = $isDeleted;
    }

    public function getDeletedAt()
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTime $deletedAt)
    {
        $this->deletedAt = $deletedAt;
    }

    public function getDeletedBy()
    {
        return $this->deletedBy;
    }

    public function setDeletedBy(?string $deletedBy)
    {
        $this->deletedBy = $deletedBy;
    }

    public function isPinned()
    {
        return $this->isPinned;
    }

    public function setIsPinned(bool $isPinned)
    {
        $this->isPinned = $isPinned;
    }
}
