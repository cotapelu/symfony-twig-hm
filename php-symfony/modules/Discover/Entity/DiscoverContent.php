<?php

declare(strict_types=1);

namespace App\Discover\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\User\Entity\User;

#[ORM\Entity]
#[ORM\Table(name: 'discover_content')]
class DiscoverContent
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $title;

    #[ORM\Column(type: 'string', length: 50)]
    private string $type;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $mediaUrl = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $linkUrl = null;

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $userId = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $publishedAt;

    #[ORM\Column(type: 'integer')]
    private int $viewCount = 0;

    #[ORM\Column(type: 'integer')]
    private int $likeCount = 0;

    public function __construct()
    {
        $this->id = (string) \Ramsey\Uuid\Uuid::uuid4()->toString();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle(string $title)
    {
        $this->title = $title;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType(string $type)
    {
        $this->type = $type;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription(?string $description)
    {
        $this->description = $description;
    }

    public function getMediaUrl()
    {
        return $this->mediaUrl;
    }

    public function setMediaUrl(?string $mediaUrl)
    {
        $this->mediaUrl = $mediaUrl;
    }

    public function getLinkUrl()
    {
        return $this->linkUrl;
    }

    public function setLinkUrl(?string $linkUrl)
    {
        $this->linkUrl = $linkUrl;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function setUserId(?string $userId)
    {
        $this->userId = $userId;
    }

    public function getMetadata()
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata)
    {
        $this->metadata = $metadata;
    }

    public function getPublishedAt()
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(\DateTime $publishedAt)
    {
        $this->publishedAt = $publishedAt;
    }

    public function getViewCount()
    {
        return $this->viewCount;
    }

    public function setViewCount(int $viewCount)
    {
        $this->viewCount = $viewCount;
    }

    public function getLikeCount()
    {
        return $this->likeCount;
    }

    public function setLikeCount(int $likeCount)
    {
        $this->likeCount = $likeCount;
    }
}
