<?php

declare(strict_types=1);

namespace App\Social\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\User\Entity\User;

#[ORM\Entity]
#[ORM\Table(name: 'post_likes')]
#[ORM\Index(name: 'idx_post_like', columns: ['postId', 'userId'])]
class PostLike
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $postId;

    #[ORM\Column(type: 'string', length: 36)]
    private string $userId;

    #[ORM\ManyToOne(targetEntity: Post::class, inversedBy: null)]
    #[ORM\JoinColumn(name: 'post_id', referencedColumnName: 'id')]
    private ?Post $post = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    private User $user;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    public function __construct(string $postId = '', string $userId = '')
    {
        $this->id = (string) \Ramsey\Uuid\Uuid::uuid4()->toString();
        $this->postId = $postId;
        $this->userId = $userId;
        $this->createdAt = new \DateTime();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getPostId()
    {
        return $this->postId;
    }

    public function setPostId(string $postId)
    {
        $this->postId = $postId;
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

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function getPost()
    {
        return $this->post;
    }

    public function setPost(?Post $post)
    {
        $this->post = $post;
    }
}
