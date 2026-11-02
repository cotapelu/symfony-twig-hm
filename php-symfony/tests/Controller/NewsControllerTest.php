<?php

declare(strict_types=1);

namespace App\Tests\Controller;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\News\Controller\NewsController;
use PHPUnit\Framework\TestCase;

/**
 * Controller tests for NewsController.
 * These tests call controller methods directly with mock request arrays.
 * 
 * @covers \App\News\Controller\NewsController
 */
#[AllowMockObjectsWithoutExpectations]
class NewsControllerTest extends TestCase
{
    private NewsController $controller;

    protected function setUp(): void
    {
        $this->controller = new NewsController();
    }

    // Helper to create request array
    private function createRequest(array $body = [], array $query = [], array $params = [], int $userId = 1): array
    {
        return [
            'body' => $body,
            'query' => $query,
            'params' => $params,
            'user_id' => $userId
        ];
    }

    // ========== createArticle ==========
    public function testCreateArticle(): void
    {
        $request = $this->createRequest([
            'title' => 'Test Article',
            'content' => 'Content',
            'summary' => 'Summary',
            'category' => 'news'
        ]);

        $response = $this->controller->createArticle($request);

        $this->assertEquals(201, $response['status']);
        $this->assertArrayHasKey('data', $response);
        $this->assertEquals('Test Article', $response['data']->getTitle());
    }

    // ========== getArticles ==========
    public function testGetArticlesByAuthor(): void
    {
        $request = $this->createRequest(query: ['author_id' => '1']);
        $response = $this->controller->getArticles($request);
        $this->assertEquals(200, $response['status']);
        $this->assertIsArray($response['data']);
    }

    public function testGetArticlesByCategory(): void
    {
        $request = $this->createRequest(query: ['category' => 'tech']);
        $response = $this->controller->getArticles($request);
        $this->assertEquals(200, $response['status']);
    }

    // ========== searchArticles ==========
    public function testSearchArticles(): void
    {
        $request = $this->createRequest(query: ['q' => 'test']);
        $response = $this->controller->searchArticles($request);
        $this->assertEquals(200, $response['status']);
        $this->assertIsArray($response['data']);
    }

    // ========== getArticle ==========
    public function testGetArticleFound(): void
    {
        // First create an article to get ID
        $createReq = $this->createRequest([
            'title' => 'Get Test',
            'content' => 'C',
            'summary' => 'S',
            'category' => 'cat'
        ]);
        $createResp = $this->controller->createArticle($createReq);
        $id = $createResp['data']->getId();

        $request = $this->createRequest(params: ['id' => (string)$id]);
        $response = $this->controller->getArticle($request);

        $this->assertEquals(200, $response['status']);
        $this->assertEquals($id, $response['data']->getId());
    }

    public function testGetArticleNotFound(): void
    {
        $request = $this->createRequest(params: ['id' => '999']);
        $response = $this->controller->getArticle($request);
        $this->assertEquals(404, $response['status']);
        $this->assertArrayHasKey('error', $response);
    }

    // ========== updateArticle ==========
    public function testUpdateArticle(): void
    {
        $createReq = $this->createRequest([
            'title' => 'Original',
            'content' => 'C',
            'summary' => 'S',
            'category' => 'cat'
        ]);
        $createResp = $this->controller->createArticle($createReq);
        $id = $createResp['data']->getId();

        $updateReq = $this->createRequest(
            body: ['title' => 'Updated'],
            params: ['id' => (string)$id]
        );
        $response = $this->controller->updateArticle($updateReq);

        $this->assertEquals(200, $response['status']);
        $this->assertEquals('Updated', $response['data']->getTitle());
    }

    public function testUpdateArticleNotFound(): void
    {
        $updateReq = $this->createRequest(
            body: ['title' => 'Updated'],
            params: ['id' => '999']
        );
        $response = $this->controller->updateArticle($updateReq);
        $this->assertEquals(404, $response['status']);
    }

    // ========== deleteArticle ==========
    public function testDeleteArticle(): void
    {
        $createReq = $this->createRequest([
            'title' => 'To Delete',
            'content' => 'C',
            'summary' => 'S',
            'category' => 'cat'
        ]);
        $createResp = $this->controller->createArticle($createReq);
        $id = $createResp['data']->getId();

        $deleteReq = $this->createRequest(params: ['id' => (string)$id]);
        $response = $this->controller->deleteArticle($deleteReq);
        $this->assertEquals(204, $response['status']);

        // Verify deleted
        $getResp = $this->controller->getArticle($deleteReq);
        $this->assertEquals(404, $getResp['status']);
    }

    // ========== getComments ==========
    public function testGetComments(): void
    {
        $createReq = $this->createRequest([
            'title' => 'Article',
            'content' => 'C',
            'summary' => 'S',
            'category' => 'cat'
        ]);
        $createResp = $this->controller->createArticle($createReq);
        $id = $createResp['data']->getId();

        // Add comment via service? We can call addComment endpoint
        $commentReq = $this->createRequest(body: ['article_id' => $id, 'content' => 'Nice!']);
        $commentResp = $this->controller->addComment($commentReq);

        $getCommentsReq = $this->createRequest(params: ['id' => (string)$id]);
        $response = $this->controller->getComments($getCommentsReq);

        $this->assertEquals(200, $response['status']);
        $this->assertCount(1, $response['data']);
    }

    // ========== addComment ==========
    public function testAddComment(): void
    {
        $createReq = $this->createRequest([
            'title' => 'A',
            'content' => 'C',
            'summary' => 'S',
            'category' => 'cat'
        ]);
        $createResp = $this->controller->createArticle($createReq);
        $id = $createResp['data']->getId();

        $commentReq = $this->createRequest(body: [
            'article_id' => $id,
            'content' => 'Comment'
        ]);
        $response = $this->controller->addComment($commentReq);

        $this->assertEquals(201, $response['status']);
        $this->assertEquals($id, $response['data']->getArticleId());
    }

    // ========== deleteComment ==========
    public function testDeleteComment(): void
    {
        $createReq = $this->createRequest([
            'title' => 'A',
            'content' => 'C',
            'summary' => 'S',
            'category' => 'cat'
        ]);
        $createResp = $this->controller->createArticle($createReq);
        $id = $createResp['data']->getId();

        $commentReq = $this->createRequest(body: ['article_id' => $id, 'content' => 'C']);
        $commentResp = $this->controller->addComment($commentReq);
        $commentId = $commentResp['data']->getId();

        $deleteReq = $this->createRequest(params: ['id' => (string)$commentId]);
        $response = $this->controller->deleteComment($deleteReq);
        $this->assertEquals(204, $response['status']);
    }
}
