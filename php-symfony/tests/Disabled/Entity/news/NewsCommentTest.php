<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity\news;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\News\Entity\NewsComment;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for NewsComment entity.
 * 
 * @covers \App\News\Entity\NewsComment
 */
#[AllowMockObjectsWithoutExpectations]
class NewsCommentTest extends TestCase
{
    private NewsComment $comment;

    protected function setUp(): void
    {
        $this->comment = new NewsComment(
            10,
            5,
            'Great article!'
        );
    }

    public function testConstructorInitializesProperties(): void
    {
        $this->assertEquals(10, $this->comment->getArticleId());
        $this->assertEquals(5, $this->comment->getUserId());
        $this->assertEquals('Great article!', $this->comment->getContent());
        $this->assertNull($this->comment->getParentId());
        $this->assertNull($this->comment->getId());
        $this->assertInstanceOf(\DateTime::class, $this->comment->getCreatedAt());
    }

    public function testSetContent(): void
    {
        $newContent = 'Updated comment';
        $this->comment->setContent($newContent);
        $this->assertEquals($newContent, $this->comment->getContent());
    }

    public function testSetParentId(): void
    {
        $this->comment->setParentId(42);
        $this->assertEquals(42, $this->comment->getParentId());
        $this->comment->setParentId(null);
        $this->assertNull($this->comment->getParentId());
    }

    public function testSetId(): void
    {
        $this->comment->setId(100);
        $this->assertEquals(100, $this->comment->getId());
        $this->comment->setId(200);
        $this->assertEquals(200, $this->comment->getId());
    }

    public function testSetterReturnsSelf(): void
    {
        $result = $this->comment->setContent('New')->setParentId(5);
        $this->assertSame($this->comment, $result);
    }
}
