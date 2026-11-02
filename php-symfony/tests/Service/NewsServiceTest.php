<?php

declare(strict_types=1);

namespace App\Tests\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\News\Entity\NewsArticle;
use App\News\Entity\NewsComment;
use App\News\Service\NewsService;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for NewsService.
 * 
 * Tests all public methods with in-memory data store.
 * 
 * @covers \App\News\Service\NewsService
 */
#[AllowMockObjectsWithoutExpectations]
class NewsServiceTest extends TestCase
{
    private NewsService $service;

    protected function setUp(): void
    {
        $this->service = new NewsService();
    }

    // ========== createArticle() ==========
    public function testCreateArticle(): void
    {
        $article = $this->service->createArticle(1, 'Title', 'Content', 'Summary', 'tech');

        $this->assertInstanceOf(NewsArticle::class, $article);
        $this->assertEquals(1, $article->getAuthorId());
        $this->assertEquals('Title', $article->getTitle());
        $this->assertEquals('Content', $article->getContent());
        $this->assertEquals('Summary', $article->getSummary());
        $this->assertEquals('tech', $article->getCategory());
        $this->assertEquals('published', $article->getStatus());
        $this->assertGreaterThan(0, $article->getId());
    }

    // ========== getArticle() ==========
    public function testGetArticleFound(): void
    {
        $created = $this->service->createArticle(1, 'Title', 'Content', 'Summary', 'tech');
        $retrieved = $this->service->getArticle($created->getId());

        $this->assertSame($created, $retrieved);
        // Verify view count starts at 0
        $this->assertEquals(0, $retrieved->getViewCount());
    }

    public function testGetArticleNotFound(): void
    {
        $result = $this->service->getArticle(999);
        $this->assertNull($result);
    }

    // ========== getPublishedArticles() ==========
    public function testGetPublishedArticles(): void
    {
        $this->service->createArticle(1, 'A1', 'C1', 'S1', 'cat1'); // published by default
        $this->service->createArticle(1, 'A2', 'C2', 'S2', 'cat2');
        // Set one to draft
        $articleDraft = $this->service->createArticle(2, 'Draft', 'C', 'S', 'cat1');
        $articleDraft->setStatus('draft');

        $published = $this->service->getPublishedArticles();
        $this->assertCount(2, $published);
        foreach ($published as $a) {
            $this->assertEquals('published', $a->getStatus());
        }
    }

    public function testGetPublishedArticlesWithCategoryFilter(): void
    {
        $this->service->createArticle(1, 'A1', 'C1', 'S1', 'tech');
        $this->service->createArticle(1, 'A2', 'C2', 'S2', 'news');

        $tech = $this->service->getPublishedArticles('tech');
        $this->assertCount(1, $tech);
        $this->assertEquals('tech', $tech[0]->getCategory());
    }

    public function testGetPublishedArticlesEmpty(): void
    {
        $result = $this->service->getPublishedArticles();
        $this->assertEmpty($result);
    }

    // ========== getAuthorArticles() ==========
    public function testGetAuthorArticles(): void
    {
        $author1 = $this->service->createArticle(1, 'A1', 'C1', 'S1', 'cat');
        $this->service->createArticle(2, 'A2', 'C2', 'S2', 'cat'); // different author
        $this->service->createArticle(1, 'A3', 'C3', 'S3', 'cat');

        $author1Articles = $this->service->getAuthorArticles(1);
        $this->assertCount(2, $author1Articles);
        foreach ($author1Articles as $a) {
            $this->assertEquals(1, $a->getAuthorId());
        }
    }

    // ========== updateArticle() ==========
    public function testUpdateArticleFound(): void
    {
        $article = $this->service->createArticle(1, 'Original', 'Orig content', 'Summary', 'cat');
        $originalUpdatedAt = $article->getUpdatedAt();

        // Small delay to ensure time changes
        usleep(1000);

        $updated = $this->service->updateArticle($article->getId(), [
            'title' => 'Updated Title',
            'content' => 'Updated content',
            'tags' => ['news', 'update'],
            'status' => 'draft',
            'featured_image' => 'image.jpg'
        ], 1);

        $this->assertNotNull($updated);
        $this->assertEquals('Updated Title', $updated->getTitle());
        $this->assertEquals('Updated content', $updated->getContent());
        $this->assertEquals(['news', 'update'], $updated->getTags());
        $this->assertEquals('draft', $updated->getStatus());
        $this->assertEquals('image.jpg', $updated->getFeaturedImage());
        // updatedAt should be newer
        $this->assertGreaterThan($originalUpdatedAt, $updated->getUpdatedAt());
    }

    public function testUpdateArticleNotFound(): void
    {
        $result = $this->service->updateArticle(999, ['title' => 'New'], 1);
        $this->assertNull($result);
    }

    public function testUpdateArticleDoesNotChangeSummary(): void
    {
        $article = $this->service->createArticle(1, 'Title', 'Content', 'Original Summary', 'cat');
        $this->service->updateArticle($article->getId(), ['summary' => 'Should be ignored'], 1);
        $this->assertEquals('Original Summary', $article->getSummary());
    }

    // ========== deleteArticle() ==========
    public function testDeleteArticleFound(): void
    {
        $article = $this->service->createArticle(1, 'Title', 'Content', 'Summary', 'cat');
        $result = $this->service->deleteArticle($article->getId(), 1);
        $this->assertTrue($result);
        $this->assertNull($this->service->getArticle($article->getId()));
    }

    public function testDeleteArticleNotFound(): void
    {
        $result = $this->service->deleteArticle(999, 1);
        $this->assertFalse($result);
    }

    // ========== searchArticles() ==========
    public function testSearchArticlesMatchesTitle(): void
    {
        $this->service->createArticle(1, 'Breaking News', 'Content', 'Summary', 'cat');
        $this->service->createArticle(1, 'Another Story', 'Content about something', 'Summary', 'cat');

        $results = $this->service->searchArticles('Breaking');
        $this->assertCount(1, $results);
        $this->assertEquals('Breaking News', $results[0]->getTitle());
    }

    public function testSearchArticlesMatchesContent(): void
    {
        $this->service->createArticle(1, 'Title1', 'Content contains keyword election', 'Summary', 'cat');
        $this->service->createArticle(1, 'Title2', 'Other content', 'Summary', 'cat');

        $results = $this->service->searchArticles('election');
        $this->assertCount(1, $results);
        $this->assertEquals('Title1', $results[0]->getTitle());
    }

    public function testSearchArticlesCaseInsensitive(): void
    {
        $this->service->createArticle(1, 'Election Results', 'Content', 'Summary', 'cat');
        $results = $this->service->searchArticles('election');
        $this->assertCount(1, $results);
        $results = $this->service->searchArticles('ELECTION');
        $this->assertCount(1, $results);
    }

    public function testSearchArticlesNoMatch(): void
    {
        $this->service->createArticle(1, 'Title', 'Content', 'Summary', 'cat');
        $results = $this->service->searchArticles('nonexistent');
        $this->assertEmpty($results);
    }

    // ========== addComment() & getArticleComments() ==========
    public function testAddComment(): void
    {
        $article = $this->service->createArticle(1, 'Title', 'Content', 'Summary', 'cat');
        $comment = $this->service->addComment($article->getId(), 2, 'Nice article!');

        $this->assertInstanceOf(NewsComment::class, $comment);
        $this->assertEquals($article->getId(), $comment->getArticleId());
        $this->assertEquals(2, $comment->getUserId());
        $this->assertEquals('Nice article!', $comment->getContent());
        $this->assertNull($comment->getParentId());
        $this->assertGreaterThan(0, $comment->getId());
    }

    public function testAddCommentWithParent(): void
    {
        $article = $this->service->createArticle(1, 'Title', 'Content', 'Summary', 'cat');
        $parent = $this->service->addComment($article->getId(), 2, 'Parent comment');
        $child = $this->service->addComment($article->getId(), 3, 'Reply', $parent->getId());

        $this->assertEquals($parent->getId(), $child->getParentId());
    }

    public function testGetArticleComments(): void
    {
        $article = $this->service->createArticle(1, 'Title', 'Content', 'Summary', 'cat');
        $this->service->addComment($article->getId(), 2, 'C1');
        $this->service->addComment($article->getId(), 3, 'C2');

        $comments = $this->service->getArticleComments($article->getId());
        $this->assertCount(2, $comments);
    }

    public function testGetArticleCommentsEmpty(): void
    {
        $article = $this->service->createArticle(1, 'Title', 'Content', 'Summary', 'cat');
        $comments = $this->service->getArticleComments($article->getId());
        $this->assertEmpty($comments);
    }

    // ========== deleteComment() ==========
    public function testDeleteCommentFound(): void
    {
        $article = $this->service->createArticle(1, 'Title', 'Content', 'Summary', 'cat');
        $comment = $this->service->addComment($article->getId(), 2, 'Comment');
        $result = $this->service->deleteComment($comment->getId(), 2);
        $this->assertTrue($result);
        $this->assertEmpty($this->service->getArticleComments($article->getId()));
    }

    public function testDeleteCommentNotFound(): void
    {
        $result = $this->service->deleteComment(999, 1);
        $this->assertFalse($result);
    }

    // ========== Integration-like tests ==========
    public function testFullWorkflow(): void
    {
        // Create article
        $article = $this->service->createArticle(1, 'Workflow Test', 'Content', 'Summary', 'news');
        $this->assertNotNull($article->getId());

        // Add comments
        $c1 = $this->service->addComment($article->getId(), 2, 'First!');
        $c2 = $this->service->addComment($article->getId(), 3, 'Nice', $c1->getId());

        // Retrieve comments
        $comments = $this->service->getArticleComments($article->getId());
        $this->assertCount(2, $comments);

        // Update article
        $updated = $this->service->updateArticle($article->getId(), ['status' => 'draft'], 1);
        $this->assertEquals('draft', $updated->getStatus());

        // Delete comment
        $this->service->deleteComment($c1->getId(), 2);
        $remaining = $this->service->getArticleComments($article->getId());
        $this->assertCount(1, $remaining);

        // Delete article
        $this->service->deleteArticle($article->getId(), 1);
        $this->assertNull($this->service->getArticle($article->getId()));
    }
}
