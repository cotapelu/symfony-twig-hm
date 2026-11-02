<?php

declare(strict_types=1);

namespace App\Discover\Entity;

use App\Attribute\Module;

#[Module(name: 'Discover', dependencies: ['User'])]
class Action
{
    private ?int $id = null;
    private string $userId;
    private string $actionType;
    private string $contentType;
    private ?int $contentId;
    private \DateTime $createdAt;

    public function __construct(string $userId, string $actionType, string $contentType, ?int $contentId = null)
    {
        $this->userId = $userId;
        $this->actionType = $actionType;
        $this->contentType = $contentType;
        $this->contentId = $contentId;
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getActionType(): string
    {
        return $this->actionType;
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }

    public function getContentId(): ?int
    {
        return $this->contentId;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }
}
