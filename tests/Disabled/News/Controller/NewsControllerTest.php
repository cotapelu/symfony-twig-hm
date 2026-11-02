<?php declare(strict_types=1);

namespace App\Tests\Unit\News\Controller;

use App\News\Controller\NewsController;
use PHPUnit\Framework\TestCase;

class NewsControllerTest extends TestCase
{
    private NewsController $controller;

    protected function setUp(): void
    {
        $this->controller = new NewsController();
    }

    public function testCreateArticleReturns201Status(): void
    {
        $request = [
            'body' => [
                'title' => 'Test Article',
                'content' => 'Article content',
                'summary' => 'Summary',
                'category' => 'technology',
            ],
            'user_id' => 1,
        ];

        $response = $this->controller->createArticle($request);

        $this->assertEquals(201, $response['status']);
        $this->assertArrayHasKey('data', $response);
    }

    public function testCreateArticleWithTags(): void
    {
        $request = [
            'body' => [
                'title' => 'Test',
                'content' => 'Content',
                'summary' => 'Summary',
                'category' => 'tech',
                'tags' => ['php', 'programming'],
            ],
            'user_id' => 1,
        ];

        $response = $this->controller->createArticle($request);

        $this->assertEquals(201, $response['status']);
    }

    public function testGetArticlesReturns200Status(): void
    {
        $request = ['query' => []];

        $response = $this->controller->getArticles($request);

        $this->assertEquals(200, $response['status']);
        $this->assertArrayHasKey('data', $response);
    }

    public function testGetArticlesWithCategoryFilter(): void
    {
        $request = ['query' => ['category' => 'technology']];

        $response = $this->controller->getArticles($request);

        $this->assertEquals(200, $response['status']);
    }

    public function testGetArticlesWithAuthorId(): void
    {
        $request = ['query' => ['author_id' => 1]];

        $response = $this->controller->getArticles($request);

        $this->assertEquals(200, $response['status']);
    }

    public function testSearchArticlesReturns200Status(): void
    {
        $request = ['query' => ['q' => 'test']];

        $response = $this->controller->searchArticles($request);

        $this->assertEquals(200, $response['status']);
        $this->assertArrayHasKey('data', $response);
    }

    public function testSearchArticlesWithEmptyQuery(): void
    {
        $request = ['query' => []];

        $response = $this->controller->searchArticles($request);

        $this->assertEquals(200, $response['status']);
    }

    public function testGetArticleReturns200WhenFound(): void
    {
        // First create an article
        $createRequest = [
            'body' => [
                'title' => 'Test Article',
                'content' => 'Content',
                'summary' => 'Summary',
                'category' => 'news',
            ],
            'user_id' => 1,
        ];
        $createResponse = $this->controller->createArticle($createRequest);
        $articleId = $createResponse['data']->getId();

        // Now get it
        $request = ['params' => ['id' => $articleId]];
        $response = $this->controller->getArticle($request);

        $this->assertEquals(200, $response['status']);
        $this->assertEquals($articleId, $response['data']->getId());
    }

    public function testGetArticleReturns404WhenNotFound(): void
    {
        $request = ['params' => ['id' => 9999]];

        $response = $this->controller->getArticle($request);

        $this->assertEquals(404, $response['status']);
        $this->assertArrayHasKey('error', $response);
    }

    public function testUpdateArticleReturns200WhenSuccessful(): void
    {
        // Create article first
        $createRequest = [
            'body' => [
                'title' => 'Original Title',
                'content' => 'Original Content',
                'summary' => 'Summary',
                'category' => 'news',
            ],
            'user_id' => 1,
        ];
        $createResponse = $this->controller->createArticle($createRequest);
        $articleId = $createResponse['data']->getId();

        // Update it
        $request = [
            'params' => ['id' => $articleId],
            'body' => ['title' => 'Updated Title'],
        ];
        $response = $this->controller->updateArticle($request);

        $this->assertEquals(200, $response['status']);
    }

    public function testUpdateArticleReturns404WhenNotFound(): void
    {
        $request = [
            'params' => ['id' => 9999],
            'body' => ['title' => 'Test'],
        ];

        $response = $this->controller->updateArticle($request);

        $this->assertEquals(404, $response['status']);
    }

    public function testDeleteArticleReturns204WhenSuccessful(): void
    {
        // Create article first
        $createRequest = [
            'body' => [
                'title' => 'To Delete',
                'content' => 'Content',
                'summary' => 'Summary',
                'category' => 'news',
            ],
            'user_id' => 1,
        ];
        $createResponse = $this->controller->createArticle($createRequest);
        $articleId = $createResponse['data']->getId();

        // Delete it
        $request = ['params' => ['id' => $articleId]];
        $response = $this->controller->deleteArticle($request);

        $this->assertEquals(204, $response['status']);
    }

    public function testDeleteArticleReturns404WhenNotFound(): void
    {
        $request = ['params' => ['id' => 9999]];

        $response = $this->controller->deleteArticle($request);

        $this->assertEquals(404, $response['status']);
    }

    public function testGetCommentsReturns200Status(): void
    {
        // Create article first
        $createRequest = [
            'body' => [
                'title' => 'Test',
                'content' => 'Content',
                'summary' => 'Summary',
                'category' => 'news',
            ],
            'user_id' => 1,
        ];
        $createResponse = $this->controller->createArticle($createRequest);
        $articleId = $createResponse['data']->getId();

        $request = ['params' => ['id' => $articleId]];
        $response = $this->controller->getComments($request);

        $this->assertEquals(200, $response['status']);
        $this->assertArrayHasKey('data', $response);
    }

    public function testAddCommentReturns201Status(): void
    {
        // Create article first
        $createRequest = [
            'body' => [
                'title' => 'Test',
                'content' => 'Content',
                'summary' => 'Summary',
                'category' => 'news',
            ],
            'user_id' => 1,
        ];
        $createResponse = $this->controller->createArticle($createRequest);
        $articleId = $createResponse['data']->getId();

        $request = [
            'body' => [
                'article_id' => $articleId,
                'content' => 'Great article!',
            ],
            'user_id' => 2,
        ];
        $response = $this->controller->addComment($request);

        $this->assertEquals(201, $response['status']);
    }

    public function testAddCommentWithParentId(): void
    {
        // Create article first
        $createRequest = [
            'body' => [
                'title' => 'Test',
                'content' => 'Content',
                'summary' => 'Summary',
                'category' => 'news',
            ],
            'user_id' => 1,
        ];
        $createResponse = $this->controller->createArticle($createRequest);
        $articleId = $createResponse['data']->getId();

        // Add parent comment
        $parentRequest = [
            'body' => ['article_id' => $articleId, 'content' => 'Parent'],
            'user_id' => 1,
        ];
        $parentResponse = $this->controller->addComment($parentRequest);

        // Add reply
        $replyRequest = [
            'body' => [
                'article_id' => $articleId,
                'content' => 'Reply',
                'parent_id' => $parentResponse['data']->getId(),
            ],
            'user_id' => 2,
        ];
        $response = $this->controller->addComment($replyRequest);

        $this->assertEquals(201, $response['status']);
    }

    public function testDeleteCommentReturns204WhenSuccessful(): void
    {
        // Create article and comment
        $createRequest = [
            'body' => [
                'title' => 'Test',
                'content' => 'Content',
                'summary' => 'Summary',
                'category' => 'news',
            ],
            'user_id' => 1,
        ];
        $createResponse = $this->controller->createArticle($createRequest);
        $articleId = $createResponse['data']->getId();

        $commentRequest = [
            'body' => ['article_id' => $articleId, 'content' => 'Comment'],
            'user_id' => 1,
        ];
        $commentResponse = $this->controller->addComment($commentRequest);
        $commentId = $commentResponse['data']->getId();

        // Delete comment
        $request = ['params' => ['id' => $commentId]];
        $response = $this->controller->deleteComment($request);

        $this->assertEquals(204, $response['status']);
    }

    public function testDeleteCommentReturns404WhenNotFound(): void
    {
        $request = ['params' => ['id' => 9999]];

        $response = $this->controller->deleteComment($request);

        $this->assertEquals(404, $response['status']);
    }
}
