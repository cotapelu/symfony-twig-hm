<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity\news;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\News\Entity\NewsArticle;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for NewsArticle entity.
 * 
 * @covers \App\News\Entity\NewsArticle
 */
#[AllowMockObjectsWithoutExpectations]
class NewsArticleTest extends TestCase
{
    private NewsArticle $article;

    protected function setUp(): void
    {
        $this->article = new NewsArticle(
            1,
            'Test Article',
            'Test content',
            'Test summary',
            'general'
        );
    }

    public function testConstructorInitializesProperties(): void
    {
        $this->assertEquals(1, $this->article->getAuthorId());
        $this->assertEquals('Test Article', $this->article->getTitle());
        $this->assertEquals('Test content', $this->article->getContent());
        $this->assertEquals('Test summary', $this->article->getSummary());
        $this->assertEquals('general', $this->article->getCategory());
        $this->assertEquals([], $this->article->getTags());
        $this->assertEquals('published', $this->article->getStatus());
        $this->assertNull($this->article->getFeaturedImage());
        $this->assertEquals(0, $this->article->getViewCount());
        $this->assertNull($this->article->getId());
        $this->assertNull($this->article->getPublishedAt()); // not set until setStatus('published')
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->article->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->article->getUpdatedAt());
    }

    public function testSetTitle(): void
    {
        $newTitle = 'Updated Title';
        $this->article->setTitle($newTitle);
        $this->assertEquals($newTitle, $this->article->getTitle());
    }

    public function testSetContent(): void
    {
        $newContent = 'Updated content';
        $this->article->setContent($newContent);
        $this->assertEquals($newContent, $this->article->getContent());
    }

    public function testSetTags(): void
    {
        $tags = ['tag1', 'tag2', 'tag3'];
        $this->article->setTags($tags);
        $this->assertEquals($tags, $this->article->getTags());
    }

    public function testSetStatusToPublishedSetsPublishedAt(): void
    {
        // Initially published at construction? Default status is 'published' but publishedAt not set in constructor (only set when setStatus called)
        // So initially null
        $this->assertNull($this->article->getPublishedAt());

        // Calling setStatus('published') should set publishedAt
        $this->article->setStatus('published');
        $this->assertEquals('published', $this->article->getStatus());
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->article->getPublishedAt());
    }

    public function testSetStatusToOtherDoesNotSetPublishedAt(): void
    {
        $this->article->setStatus('draft');
        $this->assertEquals('draft', $this->article->getStatus());
        $this->assertNull($this->article->getPublishedAt());
    }

    public function testSetFeaturedImage(): void
    {
        $this->article->setFeaturedImage('image.jpg');
        $this->assertEquals('image.jpg', $this->article->getFeaturedImage());
        $this->article->setFeaturedImage(null);
        $this->assertNull($this->article->getFeaturedImage());
    }

    public function testIncrementViewCount(): void
    {
        $this->assertEquals(0, $this->article->getViewCount());
        $this->article->incrementViewCount();
        $this->assertEquals(1, $this->article->getViewCount());
        $this->article->incrementViewCount();
        $this->article->incrementViewCount();
        $this->assertEquals(3, $this->article->getViewCount());
    }

    public function testSetterReturnsSelf(): void
    {
        $result = $this->article->setTitle('New')->setContent('New content');
        $this->assertSame($this->article, $result);
    }

    public function testSetId(): void
    {
        $this->article->setId(42);
        $this->assertEquals(42, $this->article->getId());
        $this->article->setId(100);
        $this->assertEquals(100, $this->article->getId());
    }
}
