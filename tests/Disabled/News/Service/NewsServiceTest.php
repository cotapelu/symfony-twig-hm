<?php declare(strict_types=1);

namespace App\Tests\Unit\News\Service;

use App\News\Entity\NewsArticle;
use App\News\Entity\NewsComment;
use App\News\Service\NewsService;
use PHPUnit\Framework\TestCase;

class NewsServiceTest extends TestCase
{
    private NewsService $service;

    protected function setUp(): void
    {
        $this->service = new NewsService();
    }

    public function testCreateArticleReturnsNewsArticle(): void
    {
        $article = $this->service->createArticle(
            authorId: 1,
            title: 'Test Article Title',
            content: 'Test article content here',
            summary: 'Test summary',
            category: 'technology'
        );

        $this->assertInstanceOf(NewsArticle::class, $article);
        $this->assertEquals('Test Article Title', $article->getTitle());
        $this->assertEquals('Test article content here', $article->getContent());
        $this->assertEquals('Test summary', $article->getSummary());
        $this->assertEquals('technology', $article->getCategory());
        $this->assertEquals(1, $article->getAuthorId());
    }

    public function testCreateArticleGeneratesId(): void
    {
        $article = $this->service->createArticle(
            authorId: 1,
            title: 'Title',
            content: 'Content',
            summary: 'Summary',
            category: 'news'
        );

        $this->assertNotNull($article->getId());
    }

    public function testGetArticleReturnsArticleWhenExists(): void
    {
        $created = $this->service->createArticle(
            authorId: 1,
            title: 'Test',
            content: 'Content',
            summary: 'Summary',
            category: 'test'
        );

        $retrieved = $this->service->getArticle($created->getId());

        $this->assertNotNull($retrieved);
        $this->assertEquals($created->getId(), $retrieved->getId());
    }

    public function testGetArticleIncrementsViewCount(): void
    {
        $article = $this->service->createArticle(
            authorId: 1,
            title: 'Test',
            content: 'Content',
            summary: 'Summary',
            category: 'test'
        );

        $initialViews = $article->getViewCount();
        $this->service->getArticle($article->getId());

        $this->assertEquals($initialViews + 1, $article->getViewCount());
    }

    public function testGetArticleReturnsNullWhenNotFound(): void
    {
        $result = $this->service->getArticle(9999);

        $this->assertNull($result);
    }

    public function testGetPublishedArticlesReturnsOnlyPublished(): void
    {
        $this->service->createArticle(1, 'Published', 'Content', 'Summary', 'news');
        $this->service->createArticle(1, 'Draft', 'Content', 'Summary', 'news');

        $articles = $this->service->getPublishedArticles();

        $this->assertCount(2, $articles);
        $this->assertEquals('Published', $articles[0]->getTitle());
    }

    public function testGetPublishedArticlesFiltersByCategory(): void
    {
        $this->service->createArticle(1, 'Tech News', 'Content', 'Summary', 'technology');
        $this->service->createArticle(1, 'Sports News', 'Content', 'Summary', 'sports');

        $techArticles = $this->service->getPublishedArticles('technology');

        $this->assertCount(1, $techArticles);
        $this->assertEquals('technology', $techArticles[0]->getCategory());
    }

    public function testGetAuthorArticlesReturnsAuthorSpecific(): void
    {
        $this->service->createArticle(1, 'Author1 Post', 'Content', 'Summary', 'news');
        $this->service->createArticle(2, 'Author2 Post', 'Content', 'Summary', 'news');

        $author1Articles = $this->service->getAuthorArticles(1);

        $this->assertCount(1, $author1Articles);
        $this->assertEquals(1, $author1Articles[0]->getAuthorId());
    }

    public function testUpdateArticleUpdatesFields(): void
    {
        $article = $this->service->createArticle(
            authorId: 1,
            title: 'Original Title',
            content: 'Original Content',
            summary: 'Original Summary',
            category: 'news'
        );

        $updated = $this->service->updateArticle($article->getId(), [
            'title' => 'Updated Title',
            'content' => 'Updated Content',
        ]);

        $this->assertNotNull($updated);
        $this->assertEquals('Updated Title', $updated->getTitle());
        $this->assertEquals('Updated Content', $updated->getContent());
    }

    public function testUpdateArticleReturnsNullWhenNotFound(): void
    {
        $result = $this->service->updateArticle(9999, ['title' => 'Test']);

        $this->assertNull($result);
    }

    public function testDeleteArticleRemovesArticle(): void
    {
        $article = $this->service->createArticle(
            authorId: 1,
            title: 'To Delete',
            content: 'Content',
            summary: 'Summary',
            category: 'news'
        );

        $deleted = $this->service->deleteArticle($article->getId());

        $this->assertTrue($deleted);
        $this->assertNull($this->service->getArticle($article->getId()));
    }

    public function testDeleteArticleReturnsFalseWhenNotFound(): void
    {
        $result = $this->service->deleteArticle(9999);

        $this->assertFalse($result);
    }

    public function testSearchArticlesFindsByTitle(): void
    {
        $this->service->createArticle(1, 'PHP Tutorial', 'Content', 'Summary', 'tech');
        $this->service->createArticle(1, 'JavaScript Guide', 'Content', 'Summary', 'tech');

        $results = $this->service->searchArticles('PHP');

        $this->assertCount(1, $results);
        $this->assertStringContainsString('PHP', $results[0]->getTitle());
    }

    public function testSearchArticlesFindsByContent(): void
    {
        $this->service->createArticle(1, 'Article One', 'Content about PHP', 'Summary', 'tech');
        $this->service->createArticle(1, 'Article Two', 'Content about JavaScript', 'Summary', 'tech');

        $results = $this->service->searchArticles('PHP');

        $this->assertCount(1, $results);
        $this->assertStringContainsString('PHP', $results[0]->getContent());
    }

    public function testAddCommentCreatesComment(): void
    {
        $article = $this->service->createArticle(
            authorId: 1,
            title: 'Test',
            content: 'Content',
            summary: 'Summary',
            category: 'news'
        );

        $comment = $this->service->addComment(
            articleId: $article->getId(),
            userId: 1,
            content: 'Great article!'
        );

        $this->assertInstanceOf(NewsComment::class, $comment);
        $this->assertEquals('Great article!', $comment->getContent());
        $this->assertEquals($article->getId(), $comment->getArticleId());
    }

    public function testAddCommentWithParentId(): void
    {
        $article = $this->service->createArticle(
            authorId: 1,
            title: 'Test',
            content: 'Content',
            summary: 'Summary',
            category: 'news'
        );

        $parentComment = $this->service->addComment($article->getId(), 1, 'Parent comment');
        $reply = $this->service->addComment($article->getId(), 2, 'Reply', $parentComment->getId());

        $this->assertEquals($parentComment->getId(), $reply->getParentId());
    }

    public function testGetArticleCommentsReturnsComments(): void
    {
        $article = $this->service->createArticle(
            authorId: 1,
            title: 'Test',
            content: 'Content',
            summary: 'Summary',
            category: 'news'
        );

        $this->service->addComment($article->getId(), 1, 'Comment 1');
        $this->service->addComment($article->getId(), 2, 'Comment 2');

        $comments = $this->service->getArticleComments($article->getId());

        $this->assertCount(2, $comments);
    }

    public function testDeleteCommentRemovesComment(): void
    {
        $article = $this->service->createArticle(
            authorId: 1,
            title: 'Test',
            content: 'Content',
            summary: 'Summary',
            category: 'news'
        );

        $comment = $this->service->addComment($article->getId(), 1, 'To delete');
        $deleted = $this->service->deleteComment($comment->getId());

        $this->assertTrue($deleted);
    }

    public function testDeleteCommentReturnsFalseWhenNotFound(): void
    {
        $result = $this->service->deleteComment(9999);

        $this->assertFalse($result);
    }
}
