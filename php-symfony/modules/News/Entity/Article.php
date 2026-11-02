<?php declare(strict_types=1);
namespace App\News\Entity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: "news_articles")]
class Article
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(type: "string", length: 200)]
    private string $title;

    #[ORM\Column(type: "text")]
    private string $content;

    #[ORM\Column(type: "string", length: 500)]
    private string $summary;

    #[ORM\Column(type: "string", length: 100)]
    private string $category;

    #[ORM\Column(type: "string", length: 50)]
    private string $author; // stores user ID as string

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $imageUrl = null;

    #[ORM\Column(type: "string", length: 20)]
    private string $status = 'published';

    #[ORM\Column(type: "integer")]
    private int $viewCount = 0;

    #[ORM\Column(type: "datetime_immutable")]
    private \DateTimeImmutable $publishedAt;

    #[ORM\Column(type: "datetime_immutable")]
    private \DateTimeImmutable $createdAt;

    #[ORM\OneToMany(mappedBy: 'article', targetEntity: Comment::class, cascade: ['persist', 'remove'])]
    private Collection $comments;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->publishedAt = $now;
        $this->comments = new ArrayCollection();
    }

    public function getId() { return $this->id; }
    public function getTitle() { return $this->title; }
    public function setTitle(string $title)
    {
        if (empty(trim($title))) {
            throw new \InvalidArgumentException('Title cannot be empty');
        }
        $this->title = trim($title);
        return $this;
    }
    public function getContent() { return $this->content; }
    public function setContent(string $content)
    {
        if (empty(trim($content))) {
            throw new \InvalidArgumentException('Content cannot be empty');
        }
        $this->content = $content;
        return $this;
    }
    public function getSummary() { return $this->summary; }
    public function setSummary(string $summary)
    {
        $this->summary = trim($summary);
        return $this;
    }
    public function getCategory() { return $this->category; }
    public function setCategory(string $category)
    {
        if (empty(trim($category))) {
            throw new \InvalidArgumentException('Category cannot be empty');
        }
        $this->category = trim($category);
        return $this;
    }
    public function getAuthor() { return $this->author; }
    public function setAuthor(string $author)
    {
        if (empty(trim($author))) {
            throw new \InvalidArgumentException('Author cannot be empty');
        }
        $this->author = trim($author);
        return $this;
    }
    public function getImageUrl() { return $this->imageUrl; }
    public function setImageUrl(?string $imageUrl) { $this->imageUrl = $imageUrl; return $this; }
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
        return $this;
    }
    public function getViewCount() { return $this->viewCount; }
    public function setViewCount(int $count)
    {
        if ($count < 0) {
            throw new \InvalidArgumentException('View count cannot be negative');
        }
        $this->viewCount = $count;
        return $this;
    }
    public function incrementViewCount()
    {
        $this->viewCount++;
        return $this;
    }
    public function getPublishedAt() { return $this->publishedAt; }
    public function setPublishedAt(\DateTimeImmutable $dt) { $this->publishedAt = $dt; return $this; }
    public function getCreatedAt() { return $this->createdAt; }
    public function getComments() { return $this->comments; }
    public function addComment(Comment $comment)
    {
        if (!$this->comments->contains($comment)) {
            $this->comments[] = $comment;
            $comment->setArticle($this);
        }
        return $this;
    }
}

#[ORM\Entity(repositoryClass: App\News\Repository\CategoryRepository::class)]
#[ORM\Table(name: "news_categories")]
class Category {
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: "integer")]
    /** @phpstan-readonly */
    private int $id;
    #[ORM\Column(type: "string", length: 50)] private string $name;
    #[ORM\Column(type: "string", length: 50)] private string $slug;
    /** @phpstan-readonly @unused */
    #[ORM\Column(type: "string", length: 255, nullable: true)] private ?string $description = null;
    #[ORM\Column(type: "integer")] private int $articleCount = 0;
    public function getId() { return $this->id; }

    /**
     * @ORM\PrePersist
     */
    public function setId(int $id)
    {
        $this->id = $id;
        return $this;
    }

    public function getName() { return $this->name; }
    public function setName(string $name) { $this->name = $name; return $this; }
    public function getSlug() { return $this->slug; }
    public function setSlug(string $slug) { $this->slug = $slug; return $this; }
    public function getArticleCount() { return $this->articleCount; }
    public function setArticleCount(int $articleCount) { $this->articleCount = $articleCount; return $this; }
}
