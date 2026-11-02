<?php

declare(strict_types=1);

namespace Tests\Unit\News\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\News\Service\NewsService;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class NewsServiceTest extends TestCase
{
    private NewsService $service;

    protected function setUp(): void
    {
        $this->service = new NewsService();
    }

    public function testCreateArticleSuccess(): void
    {
        $article = $this->service->createArticle(
            authorId: 1,
            title: 'Test Title',
            content: 'Content here',
            summary: 'Summary',
            category: 'general'
        );

        $this->assertNotNull($article->getId());
        $this->assertSame('Test Title', $article->getTitle());
        $this->assertSame('published', $article->getStatus()); // default
    }

    public function testCreateArticleFailsWhenInvalidAuthorId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid author ID');
        $this->service->createArticle(0, 'Title', 'Content', 'Summary', 'general');
    }

    public function testGetArticleFound(): void
    {
        $article = $this->service->createArticle(1, 'Title', 'Content', 'Summary', 'general');
        $found = $this->service->getArticle($article->getId());
        $this->assertSame($article, $found);
    }

    public function testGetArticleNotFoundReturnsNull(): void
    {
        $this->assertNull($this->service->getArticle(999));
    }

    public function testIncrementViewCount(): void
    {
        $article = $this->service->createArticle(1, 'Title', 'Content', 'Summary', 'general');
        $initial = $article->getViewCount();
        $this->service->incrementViewCount($article->getId());
        $this->assertSame($initial + 1, $article->getViewCount());
    }

    public function testGetPublishedArticlesFiltersByStatus(): void
    {
        // Create 3 articles: 1 draft, 2 published
        $a1 = $this->service->createArticle(1, 'A1', 'C', 'S', 'general');
        $a1->setStatus('draft');

        $a2 = $this->service->createArticle(2, 'A2', 'C', 'S', 'tech');
        // default published

        $a3 = $this->service->createArticle(3, 'A3', 'C', 'S', 'general');
        // default published

        $published = $this->service->getPublishedArticles();
        $this->assertCount(2, $published);
        $titles = array_map(fn($a) => $a->getTitle(), $published);
        $this->assertContains('A2', $titles);
        $this->assertContains('A3', $titles);
    }

    public function testGetPublishedArticlesFiltersByCategory(): void
    {
        $a1 = $this->service->createArticle(1, 'A1', 'C', 'S', 'general');
        $a1->setStatus('published');

        $a2 = $this->service->createArticle(2, 'A2', 'C', 'S', 'tech');
        $a2->setStatus('published');

        $result = $this->service->getPublishedArticles('tech');
        $this->assertCount(1, $result);
        $this->assertSame('A2', $result[0]->getTitle());
    }

    public function testGetAuthorArticles(): void
    {
        $a1 = $this->service->createArticle(1, 'A1', 'C', 'S', 'general');
        $a2 = $this->service->createArticle(2, 'A2', 'C', 'S', 'general');
        $a3 = $this->service->createArticle(1, 'A3', 'C', 'S', 'general');

        $articles = $this->service->getAuthorArticles(1);
        $this->assertCount(2, $articles);
        $titles = array_map(fn($a) => $a->getTitle(), $articles);
        $this->assertContains('A1', $titles);
        $this->assertContains('A3', $titles);
    }

    public function testUpdateArticleSuccess(): void
    {
        $article = $this->service->createArticle(1, 'Title', 'Content', 'Summary', 'general');
        $updated = $this->service->updateArticle($article->getId(), [
            'title' => 'New Title',
            'content' => 'New Content',
            'status' => 'draft',
            'tags' => ['tag1', 'tag2']
        ], 1);

        $this->assertSame($article, $updated);
        $this->assertSame('New Title', $article->getTitle());
        $this->assertSame('draft', $article->getStatus());
        $this->assertSame(['tag1', 'tag2'], $article->getTags());
    }

    public function testUpdateArticleFailsWhenUnauthorized(): void
    {
        $article = $this->service->createArticle(1, 'Title', 'Content', 'Summary', 'general');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unauthorized: not the author');
        $this->service->updateArticle($article->getId(), ['title' => 'Hacked'], 2);
    }

    public function testUpdateArticleFailsWhenInvalidStatus(): void
    {
        $article = $this->service->createArticle(1, 'Title', 'Content', 'Summary', 'general');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid status');
        $this->service->updateArticle($article->getId(), ['status' => 'invalid'], 1);
    }

    public function testDeleteArticleSuccess(): void
    {
        $article = $this->service->createArticle(1, 'Title', 'Content', 'Summary', 'general');
        $result = $this->service->deleteArticle($article->getId(), 1);
        $this->assertTrue($result);
        $this->assertNull($this->service->getArticle($article->getId()));
    }

    public function testDeleteArticleFailsWhenUnauthorized(): void
    {
        $article = $this->service->createArticle(1, 'Title', 'Content', 'Summary', 'general');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unauthorized: not the author');
        $this->service->deleteArticle($article->getId(), 2);
    }

    public function testSearchArticlesByTitle(): void
    {
        $this->service->createArticle(1, 'My Great Article', 'Content', 'S', 'general');
        $this->service->createArticle(2, 'Another News', 'Content', 'S', 'general');

        $results = $this->service->searchArticles('Great');
        $this->assertCount(1, $results);
        $this->assertSame('My Great Article', $results[0]->getTitle());
    }

    public function testSearchArticlesByContent(): void
    {
        $this->service->createArticle(1, 'Title', 'Some important announcement content', 'S', 'general');
        $this->service->createArticle(2, 'Title2', 'Nothing here', 'S', 'general');

        $results = $this->service->searchArticles('announcement');
        $this->assertCount(1, $results);
        $this->assertSame('Some important announcement content', $results[0]->getContent());
    }

    public function testSearchArticlesWithEmptyQueryReturnsEmpty(): void
    {
        $this->service->createArticle(1, 'Title', 'Content', 'S', 'general');
        $results = $this->service->searchArticles('   ');
        $this->assertEmpty($results);
    }

    public function testAddCommentSuccess(): void
    {
        $article = $this->service->createArticle(1, 'Title', 'Content', 'S', 'general');
        $comment = $this->service->addComment($article->getId(), 2, 'Nice article!');

        $this->assertNotNull($comment->getId());
        $this->assertSame(2, $comment->getUserId());
        $this->assertSame('Nice article!', $comment->getContent());
    }

    public function testAddCommentFailsWhenArticleNotFound(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Article not found');
        $this->service->addComment(999, 1, 'Hello');
    }

    public function testGetArticleComments(): void
    {
        $article = $this->service->createArticle(1, 'Title', 'Content', 'S', 'general');
        $this->service->addComment($article->getId(), 2, 'Comment1');
        $this->service->addComment($article->getId(), 3, 'Comment2');

        $comments = $this->service->getArticleComments($article->getId());
        $this->assertCount(2, $comments);
    }
}
