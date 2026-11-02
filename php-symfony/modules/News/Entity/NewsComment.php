<?php

namespace App\News\Entity;

use App\Attribute\Module;

#[Module(name: 'News', dependencies: ['User', 'News'])]
class NewsComment
{
    private ?int $id = null;
    private int $articleId;
    private int $userId;
    private string $content;
    private ?int $parentId = null;
    private \DateTime $createdAt;

    public function __construct(int $articleId, int $userId, string $content)
    {
        $this->articleId = $articleId;
        $this->userId = $userId;
        $this->content = $content;
        $this->createdAt = new \DateTime();
    }

    public function getId() { return $this->id; }
    public function getArticleId() { return $this->articleId; }
    public function getUserId() { return $this->userId; }
    public function getContent() { return $this->content; }
    public function getParentId() { return $this->parentId; }
    public function getCreatedAt() { return $this->createdAt; }

    public function setContent(string $content) { $this->content = $content; return $this; }
    public function setParentId(?int $id) { $this->parentId = $id; return $this; }
    public function setId(int $id) { $this->id = $id; return $this; }
}
