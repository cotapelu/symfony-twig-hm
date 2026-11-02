<?php declare(strict_types=1);

namespace App\Tests\Unit\News\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\News\Entity\Article;
use App\News\Entity\Comment;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ArticleTest extends TestCase
{
    private function createArticle(array $overrides = []): Article
    {
        $article = new Article();
        // Set required fields
        $article->setTitle('Test Title');
        $article->setContent('Test content');
        $article->setSummary('Test summary');
        $article->setCategory('General');
        $article->setAuthor('user-123');
        foreach ($overrides as $prop => $value) {
            $setter = 'set' . $prop;
            if (method_exists($article, $setter)) {
                $article->$setter($value);
            }
        }
        return $article;
    }

    public function testConstructorInitializesTimestampsAndCollection(): void
    {
        $article = $this->createArticle([]);
        $this->assertInstanceOf(\DateTimeImmutable::class, $article->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $article->getPublishedAt());
        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $article->getComments());
        $this->assertEmpty($article->getComments()->toArray());
    }

    public function testDefaultValues(): void
    {
        $article = $this->createArticle([]);
        $this->assertNull($article->getImageUrl());
        $this->assertSame('published', $article->getStatus());
        $this->assertSame(0, $article->getViewCount());
    }

    public function testTitleValidation(): void
    {
        $article = $this->createArticle([]);
        $article->setTitle('New Title');
        $this->assertSame('New Title', $article->getTitle());

        $this->expectException(\InvalidArgumentException::class);
        $article->setTitle('   '); // empty after trim
    }

    public function testContentValidation(): void
    {
        $article = $this->createArticle([]);
        $article->setContent('New content');
        $this->assertSame('New content', $article->getContent());

        $this->expectException(\InvalidArgumentException::class);
        $article->setContent('');
    }

    public function testSummaryTrimming(): void
    {
        $article = $this->createArticle([]);
        $article->setSummary('  Trimmed summary  ');
        $this->assertSame('Trimmed summary', $article->getSummary());
    }

    public function testCategoryValidation(): void
    {
        $article = $this->createArticle([]);
        $article->setCategory('Tech');
        $this->assertSame('Tech', $article->getCategory());

        $this->expectException(\InvalidArgumentException::class);
        $article->setCategory('   ');
    }

    public function testAuthorValidation(): void
    {
        $article = $this->createArticle([]);
        $article->setAuthor('author-123');
        $this->assertSame('author-123', $article->getAuthor());

        $this->expectException(\InvalidArgumentException::class);
        $article->setAuthor("\t\n");
    }

    public function testImageUrl(): void
    {
        $article = $this->createArticle([]);
        $this->assertNull($article->getImageUrl());
        $article->setImageUrl('https://example.com/image.jpg');
        $this->assertSame('https://example.com/image.jpg', $article->getImageUrl());
        $article->setImageUrl(null);
        $this->assertNull($article->getImageUrl());
    }

    public function testStatusTransitions(): void
    {
        $article = $this->createArticle([]);
        $this->assertSame('published', $article->getStatus());

        $article->setStatus('draft');
        $this->assertSame('draft', $article->getStatus());

        $article->setStatus('archived');
        $this->assertSame('archived', $article->getStatus());

        $this->expectException(\InvalidArgumentException::class);
        $article->setStatus('invalid');
    }

    public function testPublishedAtIsSetOnlyOnceWhenStatusBecomesPublished(): void
    {
        $article = $this->createArticle([]);
        // Initially publishedAt set to now in constructor
        $originalPublished = $article->getPublishedAt();

        // Change to draft then back to published should NOT update publishedAt (only set once)
        $article->setStatus('draft');
        $article->setStatus('published');
        $this->assertSame($originalPublished, $article->getPublishedAt());
    }

    public function testViewCount(): void
    {
        $article = $this->createArticle([]);
        $this->assertSame(0, $article->getViewCount());

        $article->setViewCount(100);
        $this->assertSame(100, $article->getViewCount());

        $article->incrementViewCount();
        $this->assertSame(101, $article->getViewCount());

        $this->expectException(\InvalidArgumentException::class);
        $article->setViewCount(-5);
    }

    public function testPublishedAtSetter(): void
    {
        $article = $this->createArticle([]);
        $dt = new \DateTimeImmutable('2024-01-01');
        $article->setPublishedAt($dt);
        $this->assertSame($dt, $article->getPublishedAt());
    }

    public function testCommentsManagement(): void
    {
        $article = $this->createArticle([]);
        $this->assertEmpty($article->getComments()->toArray());

        $comment = $this->createMock(Comment::class);
        $comment->expects($this->once())
            ->method('setArticle')
            ->with($article);
        $article->addComment($comment);
        $this->assertCount(1, $article->getComments());
        $this->assertTrue($article->getComments()->contains($comment));
    }
}
