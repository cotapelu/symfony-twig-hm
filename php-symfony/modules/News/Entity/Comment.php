<?php

declare(strict_types=1);

namespace App\News\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'news_comments')]
class Comment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'integer')]
    private int $articleId;

    #[ORM\ManyToOne(targetEntity: Article::class, inversedBy: 'comments')]
    #[ORM\JoinColumn(name: 'article_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?Article $article = null;

    #[ORM\Column(type: 'integer')]
    private int $userId;

    #[ORM\Column(type: 'text')]
    private string $content;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $parentId = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(int $articleId, int $userId, string $content)
    {
        $this->articleId = $articleId;
        $this->userId = $userId;
        $this->content = $content;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId() { return $this->id; }

    public function getArticleId() { return $this->articleId; }
    public function getArticle() { return $this->article; }
    public function setArticle(?Article $article) { $this->article = $article; return $this; }

    public function getUserId() { return $this->userId; }
    public function setUserId(int $userId) { $this->userId = $userId; return $this; }

    public function getContent() { return $this->content; }
    public function setContent(string $content) { $this->content = $content; return $this; }

    public function getParentId() { return $this->parentId; }
    public function setParentId(?int $id) { $this->parentId = $id; return $this; }

    public function getCreatedAt() { return $this->createdAt; }
}
