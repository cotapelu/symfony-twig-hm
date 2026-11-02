<?php

namespace App\Tests\Unit\News\Entity;

use App\News\Entity\NewsArticle;
use App\News\Entity\NewsComment;
use PHPUnit\Framework\TestCase;

class NewsArticleTest extends TestCase
{
    public function testConstructorCreatesArticle(): void
    {
        $article = new NewsArticle(1, 'Title', 'Content', 'Summary', 'tech');

        $this->assertEquals(1, $article->getAuthorId());
        $this->assertEquals('Title', $article->getTitle());
        $this->assertEquals('Content', $article->getContent());
        $this->assertEquals('Summary', $article->getSummary());
        $this->assertEquals('tech', $article->getCategory());
        $this->assertEquals('published', $article->getStatus());
        $this->assertEquals(0, $article->getViewCount());
    }

    public function testGetIdReturnsNullByDefault(): void
    {
        $article = new NewsArticle(1, 'Title', 'Content', 'Summary', 'cat');
        $this->assertNull($article->getId());
    }

    public function testSetId(): void
    {
        $article = new NewsArticle(1, 'Title', 'Content', 'Summary', 'cat');
        $result = $article->setId(5);
        
        $this->assertEquals(5, $article->getId());
        $this->assertSame($article, $result);
    }

    public function testSetTitle(): void
    {
        $article = new NewsArticle(1, 'Title', 'Content', 'Summary', 'cat');
        $result = $article->setTitle('New Title');
        
        $this->assertEquals('New Title', $article->getTitle());
        $this->assertSame($article, $result);
    }

    public function testSetContent(): void
    {
        $article = new NewsArticle(1, 'Title', 'Content', 'Summary', 'cat');
        $result = $article->setContent('New Content');
        
        $this->assertEquals('New Content', $article->getContent());
        $this->assertSame($article, $result);
    }

    public function testSetTags(): void
    {
        $article = new NewsArticle(1, 'Title', 'Content', 'Summary', 'cat');
        $result = $article->setTags(['php', 'symfony']);
        
        $this->assertEquals(['php', 'symfony'], $article->getTags());
        $this->assertSame($article, $result);
    }

    public function testSetStatusToPublished(): void
    {
        $article = new NewsArticle(1, 'Title', 'Content', 'Summary', 'cat');
        $result = $article->setStatus('published');
        
        $this->assertEquals('published', $article->getStatus());
        $this->assertInstanceOf(\DateTime::class, $article->getPublishedAt());
        $this->assertSame($article, $result);
    }

    public function testSetStatusToDraft(): void
    {
        $article = new NewsArticle(1, 'Title', 'Content', 'Summary', 'cat');
        $article->setStatus('published');
        $result = $article->setStatus('draft');
        
        $this->assertEquals('draft', $article->getStatus());
        $this->assertNull($article->getPublishedAt());
    }

    public function testSetFeaturedImage(): void
    {
        $article = new NewsArticle(1, 'Title', 'Content', 'Summary', 'cat');
        $result = $article->setFeaturedImage('/images/featured.jpg');
        
        $this->assertEquals('/images/featured.jpg', $article->getFeaturedImage());
        $this->assertSame($article, $result);
    }

    public function testIncrementViewCount(): void
    {
        $article = new NewsArticle(1, 'Title', 'Content', 'Summary', 'cat');
        $result = $article->incrementViewCount();
        
        $this->assertEquals(1, $article->getViewCount());
        $this->assertSame($article, $result);
    }

    public function testMultipleViewCountIncrements(): void
    {
        $article = new NewsArticle(1, 'Title', 'Content', 'Summary', 'cat');
        $article->incrementViewCount();
        $article->incrementViewCount();
        $article->incrementViewCount();
        
        $this->assertEquals(3, $article->getViewCount());
    }
}

class NewsCommentTest extends TestCase
{
    public function testConstructorCreatesComment(): void
    {
        $comment = new NewsComment(1, 2, 'Comment content');

        $this->assertEquals(1, $comment->getArticleId());
        $this->assertEquals(2, $comment->getUserId());
        $this->assertEquals('Comment content', $comment->getContent());
        $this->assertNull($comment->getParentId());
    }

    public function testGetIdReturnsNullByDefault(): void
    {
        $comment = new NewsComment(1, 2, 'Content');
        $this->assertNull($comment->getId());
    }

    public function testSetId(): void
    {
        $comment = new NewsComment(1, 2, 'Content');
        $result = $comment->setId(10);
        
        $this->assertEquals(10, $comment->getId());
        $this->assertSame($comment, $result);
    }

    public function testSetContent(): void
    {
        $comment = new NewsComment(1, 2, 'Content');
        $result = $comment->setContent('Updated content');
        
        $this->assertEquals('Updated content', $comment->getContent());
        $this->assertSame($comment, $result);
    }

    public function testSetParentId(): void
    {
        $comment = new NewsComment(1, 2, 'Content');
        $result = $comment->setParentId(5);
        
        $this->assertEquals(5, $comment->getParentId());
        $this->assertSame($comment, $result);
    }

    public function testGetCreatedAtReturnsDateTime(): void
    {
        $comment = new NewsComment(1, 2, 'Content');
        $this->assertInstanceOf(\DateTime::class, $comment->getCreatedAt());
    }
}
