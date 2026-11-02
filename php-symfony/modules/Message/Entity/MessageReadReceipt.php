<?php

declare(strict_types=1);

namespace App\Message\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Doctrine entity for Message Read Receipt.
 */
#[ORM\Entity]
#[ORM\Table(name: 'communication_message_reads')]
class MessageReadReceipt
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $messageId;

    #[ORM\Column(type: 'string', length: 36)]
    private string $userId;

    #[ORM\Column(name: 'read_at', type: 'datetime')]
    private \DateTime $readAt;

    public function __construct(
        string $messageId,
        string $userId,
    ) {
        $this->id = uniqid('read_', true);
        $this->messageId = $messageId;
        $this->userId = $userId;
        $this->readAt = new \DateTime();
    }

    // Getters
    public function getId()
    {
        return $this->id;
    }

    public function getMessageId()
    {
        return $this->messageId;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function getReadAt()
    {
        return $this->readAt;
    }
}
