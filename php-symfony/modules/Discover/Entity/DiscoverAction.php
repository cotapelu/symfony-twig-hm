<?php

declare(strict_types=1);

namespace App\Discover\Entity;

use App\User\Entity\User;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class DiscoverAction
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $userId;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(type: 'string', length: 50)]
    private string $actionType;

    #[ORM\Column(type: 'string', length: 50)]
    private string $contentType;

    #[ORM\Column(type: 'string', length: 100)]
    private string $contentId;

    #[ORM\Column(type: 'json')]
    private array $metadata = [];

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        string $id,
        string $userId,
        User $user,
        string $actionType,
        string $contentType,
        string $contentId,
        array $metadata = []
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->user = $user;
        $this->actionType = $actionType;
        $this->contentType = $contentType;
        $this->contentId = $contentId;
        $this->metadata = $metadata;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function setUserId(string $userId)
    {
        $this->userId = $userId;
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

    public function getActionType()
    {
        return $this->actionType;
    }

    public function setActionType(string $actionType)
    {
        $this->actionType = $actionType;
        return $this;
    }

    public function getContentType()
    {
        return $this->contentType;
    }

    public function setContentType(string $contentType)
    {
        $this->contentType = $contentType;
        return $this;
    }

    public function getContentId()
    {
        return $this->contentId;
    }

    public function setContentId(string $contentId)
    {
        $this->contentId = $contentId;
        return $this;
    }

    public function getMetadata()
    {
        return $this->metadata;
    }

    public function setMetadata(array $metadata)
    {
        $this->metadata = $metadata;
        return $this;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }
}
