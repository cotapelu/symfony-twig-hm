<?php

declare(strict_types=1);

namespace App\Messaging\Entity;

use App\Core\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'social_posts')]
#[ORM\HasLifecycleCallbacks]
class SocialPost
{
    public const TYPE_POST = 'post';
    public const TYPE_STORY = 'story';
    public const TYPE_REEL = 'reel';

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private string $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(type: 'string', length: 20)]
    private string $type = self::TYPE_POST;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $content = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $media = null;

    #[ORM\Column(type: 'integer')]
    private int $likesCount = 0;

    #[ORM\Column(type: 'integer')]
    private int $commentsCount = 0;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $updatedAt;

    public function __construct(User $user, string $type)
    {
        $this->id = Uuid::uuid4()->toString();
        $this->user = $user;
        $this->type = $type;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId()
    {
        return $this->id;
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

    public function getType()
    {
        return $this->type;
    }

    public function setType(string $type)
    {
        $this->type = $type;
        return $this;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function setContent(?string $content)
    {
        $this->content = $content;
        return $this;
    }

    public function getMedia()
    {
        return $this->media;
    }

    public function setMedia(?array $media)
    {
        $this->media = $media;
        return $this;
    }

    public function getLikesCount()
    {
        return $this->likesCount;
    }

    public function setLikesCount(int $likesCount)
    {
        $this->likesCount = $likesCount;
        return $this;
    }

    public function incrementLikesCount()
    {
        $this->likesCount++;
        return $this;
    }

    public function decrementLikesCount()
    {
        $this->likesCount = max(0, $this->likesCount - 1);
        return $this;
    }

    public function getCommentsCount()
    {
        return $this->commentsCount;
    }

    public function setCommentsCount(int $commentsCount)
    {
        $this->commentsCount = $commentsCount;
        return $this;
    }

    public function incrementCommentsCount()
    {
        $this->commentsCount++;
        return $this;
    }

    public function decrementCommentsCount()
    {
        $this->commentsCount = max(0, $this->commentsCount - 1);
        return $this;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    #[ORM\PreUpdate]
    public function updateTimestamp()
    {
        $this->updatedAt = new \DateTime();
    }

    public function toArray()
    {
        return [
            'id' => $this->id,
            'userId' => $this->user->getId(),
            'type' => $this->type,
            'content' => $this->content,
            'media' => $this->media,
            'likesCount' => $this->likesCount,
            'commentsCount' => $this->commentsCount,
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
