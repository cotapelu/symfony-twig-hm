<?php

declare(strict_types=1);

namespace App\News\Entity;

use App\Attribute\Module;

#[Module(name: 'News', dependencies: ['User'])]
class NewsArticle
{
    private ?int $id = null;
    private int $authorId;
    private string $title;
    private string $content;
    private string $summary;
    private string $category;
    private array $tags = [];
    private string $status = 'published';
    private ?string $featuredImage = null;
    private int $viewCount = 0;
    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $updatedAt;
    private ?\DateTimeImmutable $publishedAt = null;

    public function __construct(int $authorId, string $title, string $content, string $summary, string $category)
    {
        $this->authorId = $authorId;
        $this->title = $title;
        $this->content = $content;
        $this->summary = $summary;
        $this->category = $category;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId() { return $this->id; }
    public function setId(int $id) { $this->id = $id; return $this; }

    public function getAuthorId() { return $this->authorId; }

    public function getTitle() { return $this->title; }
    public function setTitle(string $title)
    {
        if (empty(trim($title))) {
            throw new \InvalidArgumentException('Title cannot be empty');
        }
        $this->title = trim($title);
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getContent() { return $this->content; }
    public function setContent(string $content)
    {
        if (empty(trim($content))) {
            throw new \InvalidArgumentException('Content cannot be empty');
        }
        $this->content = $content;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getSummary() { return $this->summary; }
    public function setSummary(string $summary)
    {
        // Summary is intended to be immutable after creation, but allow if needed
        $this->summary = trim($summary);
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getCategory() { return $this->category; }
    public function setCategory(string $category)
    {
        if (empty(trim($category))) {
            throw new \InvalidArgumentException('Category cannot be empty');
        }
        $this->category = trim($category);
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getTags() { return $this->tags; }
    public function setTags(array $tags)
    {
        $this->tags = array_map('trim', $tags);
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getStatus() { return $this->status; }
    public function setStatus(string $status)
    {
        $allowed = ['draft', 'published', 'archived'];
        if (!in_array($status, $allowed, true)) {
            throw new \InvalidArgumentException('Invalid status');
        }
        $this->status = $status;
        if ($status === 'published' && $this->publishedAt === null) {
            $this->publishedAt = new \DateTimeImmutable();
        }
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getFeaturedImage() { return $this->featuredImage; }
    public function setFeaturedImage(?string $image)
    {
        $this->featuredImage = $image;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getViewCount() { return $this->viewCount; }
    public function incrementViewCount()
    {
        $this->viewCount++;
        return $this;
    }

    public function getCreatedAt() { return $this->createdAt; }
    public function getUpdatedAt() { return $this->updatedAt; }
    public function getPublishedAt() { return $this->publishedAt; }
}
