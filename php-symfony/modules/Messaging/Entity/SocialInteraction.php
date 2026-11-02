<?php

declare(strict_types=1);

namespace App\Messaging\Entity;

use App\Core\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'social_interactions')]
class SocialInteraction
{
    public const TYPE_LIKE = 'LIKE';
    public const TYPE_COMMENT = 'COMMENT';
    public const TYPE_SHARE = 'SHARE';
    public const TYPE_BOOKMARK = 'BOOKMARK';

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private string $id;

    #[ORM\ManyToOne(targetEntity: SocialPost::class)]
    #[ORM\JoinColumn(name: 'post_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private SocialPost $post;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(type: 'string', length: 20)]
    private string $type;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $content = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    public function __construct(SocialPost $post, User $user, string $type)
    {
        $this->id = Uuid::uuid4()->toString();
        $this->post = $post;
        $this->user = $user;
        $this->type = $type;
        $this->createdAt = new \DateTime();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getPost()
    {
        return $this->post;
    }

    public function setPost(SocialPost $post)
    {
        $this->post = $post;
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

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function isLike()
    {
        return $this->type === self::TYPE_LIKE;
    }

    public function isComment()
    {
        return $this->type === self::TYPE_COMMENT;
    }

    public function isShare()
    {
        return $this->type === self::TYPE_SHARE;
    }

    public function isBookmark()
    {
        return $this->type === self::TYPE_BOOKMARK;
    }

    public function toArray()
    {
        return [
            'id' => $this->id,
            'postId' => $this->post->getId(),
            'userId' => $this->user->getId(),
            'type' => $this->type,
            'content' => $this->content,
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }
}
