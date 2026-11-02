<?php

declare(strict_types=1);

namespace App\Social\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\User\Entity\User;

#[ORM\Entity]
#[ORM\Table(name: 'posts')]
#[ORM\HasLifecycleCallbacks]
class Post
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $userId;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    private User $user;

    #[ORM\Column(type: 'text')]
    private string $content;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $mediaUrl = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $mediaType = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isPublic = true;

    #[ORM\Column(type: 'integer')]
    private int $likeCount = 0;

    #[ORM\Column(type: 'integer')]
    private int $commentCount = 0;

    #[ORM\Column(type: 'integer')]
    private int $shareCount = 0;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $deletedAt = null;

    public function __construct(?string $userId = null, ?string $content = null, ?string $mediaUrl = null, ?string $mediaType = null, ?array $metadata = null, bool $isPublic = true)
    {
        $this->id = (string) \Ramsey\Uuid\Uuid::uuid4()->toString();
        $this->userId = $userId ?? '';
        $this->content = $content ?? '';
        $this->mediaUrl = $mediaUrl ?? null;
        $this->mediaType = $mediaType ?? null;
        $this->metadata = $metadata ?? null;
        $this->isPublic = $isPublic;
        $this->createdAt = new \DateTime();
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
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setUser(User $user)
    {
        $this->user = $user;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function setContent(string $content)
    {
        $this->content = $content;
    }

    public function getMediaUrl()
    {
        return $this->mediaUrl ?? '';
    }

    public function setMediaUrl(?string $mediaUrl)
    {
        $this->mediaUrl = $mediaUrl;
    }

    public function getMediaType()
    {
        return $this->mediaType;
    }

    public function setMediaType(?string $mediaType)
    {
        $this->mediaType = $mediaType;
    }

    public function getMetadata()
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata)
    {
        $this->metadata = $metadata;
    }

    public function getLikeCount()
    {
        return $this->likeCount;
    }

    public function setLikeCount(int $likeCount)
    {
        $this->likeCount = $likeCount;
    }

    public function getCommentCount()
    {
        return $this->commentCount;
    }

    public function setCommentCount(int $commentCount)
    {
        $this->commentCount = $commentCount;
    }

    public function getShareCount()
    {
        return $this->shareCount;
    }

    public function setShareCount(int $shareCount)
    {
        $this->shareCount = $shareCount;
    }

    public function isPublic()
    {
        return $this->isPublic;
    }

    public function setPublic(bool $isPublic)
    {
        $this->isPublic = $isPublic;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function getDeletedAt()
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTime $deletedAt)
    {
        $this->deletedAt = $deletedAt;
    }

    public function isDeleted()
    {
        return $this->deletedAt !== null;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'userId' => $this->userId,
            'content' => $this->content,
            'mediaUrl' => $this->mediaUrl,
            'mediaType' => $this->mediaType,
            'metadata' => $this->metadata,
            'isPublic' => $this->isPublic,
            'likeCount' => $this->likeCount,
            'commentCount' => $this->commentCount,
            'shareCount' => $this->shareCount,
            'createdAt' => $this->createdAt ? $this->createdAt->format('c') : null,
            'deletedAt' => $this->deletedAt ? $this->deletedAt->format('c') : null,
        ];
    }
}
