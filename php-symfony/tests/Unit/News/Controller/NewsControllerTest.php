<?php declare(strict_types=1);

namespace App\Tests\Unit\News\Controller;

use App\News\Controller\NewsController;
use App\News\Service\NewsService;
use App\News\Entity\NewsArticle;
use App\News\Entity\NewsComment;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use App\User\Entity\User;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class NewsControllerTest extends TestCase
{
    private NewsService&MockObject $serviceMock;
    private NewsController $controller;
    private User&MockObject $userMock;

    protected function setUp(): void
    {
        $this->serviceMock = $this->createMock(NewsService::class);
        $this->controller = new NewsController($this->serviceMock);
        $this->controller->setContainer(new ContainerBuilder());

        $this->userMock = $this->createMock(User::class);
        $this->userMock->method('getId')->willReturn('1');
        $this->userMock->method('getUserIdentifier')->willReturn('user1');
        $this->controller->setTestUser($this->userMock);
    }

    private function createRequest(array $query = [], array $request = null, string $method = 'GET', int $id = null): Request
    {
        $path = $id !== null ? '/api/news/articles/'.$id : '/api/news/articles';
        $content = $request !== null ? json_encode($request) : null;
        return Request::create($path, $method, $query, [], [], ['CONTENT_TYPE' => 'application/json'], $content);
    }

    private function createArticleEntity(array $overrides = []): NewsArticle
    {
        $article = new NewsArticle(
            $overrides['authorId'] ?? 1,
            $overrides['title'] ?? 'Title',
            $overrides['content'] ?? 'Content',
            $overrides['summary'] ?? 'Summary',
            $overrides['category'] ?? 'General'
        );
        $article->setId($overrides['id'] ?? 1);
        if (isset($overrides['status'])) $article->setStatus($overrides['status']);
        if (isset($overrides['tags'])) $article->setTags($overrides['tags']);
        if (isset($overrides['viewCount'])) {
            $ref = new \ReflectionClass($article);
            $prop = $ref->getProperty('viewCount');
            $prop->setAccessible(true);
            $prop->setValue($article, $overrides['viewCount']);
        }
        // Override timestamps if needed - ignore for tests
        return $article;
    }

    private function createCommentEntity(array $overrides = []): NewsComment
    {
        $comment = new NewsComment(
            $overrides['articleId'] ?? 1,
            $overrides['userId'] ?? 1,
            $overrides['content'] ?? 'Comment'
        );
        $comment->setId($overrides['id'] ?? 1);
        if (isset($overrides['parentId'])) $comment->setParentId($overrides['parentId']);
        return $comment;
    }

    // ==================== createArticle ====================

    public function testCreateArticle_Success(): void
    {
        $data = [
            'title' => 'New Article',
            'content' => 'Content body',
            'category' => 'Tech',
            'summary' => 'Summary text',
            'tags' => ['php', 'testing'],
        ];
        $request = $this->createRequest([], $data, 'POST');

        $article = $this->createArticleEntity([
            'id' => 123,
            'title' => $data['title'],
            'tags' => $data['tags'],
        ]);

        $this->serviceMock->expects($this->once())
            ->method('createArticle')
            ->with(1, 'New Article', 'Content body', 'Summary text', 'Tech')
            ->willReturn($article);

        $response = $this->controller->createArticle($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(201, $response->getStatusCode());
    }

    public function testCreateArticle_AuthenticationRequired(): void
    {
        $this->controller->setTestUser(null);
        $data = ['title' => 'Test', 'content' => 'Body', 'category' => 'General'];
        $request = $this->createRequest([], $data, 'POST');
        $response = $this->controller->createArticle($request);
        $this->assertSame(500, $response->getStatusCode());
    }

    public function testCreateArticle_ValidationMissingTitle(): void
    {
        $data = ['content' => 'Body', 'category' => 'General'];
        $request = $this->createRequest([], $data, 'POST');
        $response = $this->controller->createArticle($request);
        $this->assertSame(400, $response->getStatusCode());
        $decoded = json_decode($response->getContent(), true);
        $this->assertStringContainsString('Title is required', $decoded['error']);
    }

    public function testCreateArticle_ValidationMissingContent(): void
    {
        $data = ['title' => 'Title', 'category' => 'General'];
        $request = $this->createRequest([], $data, 'POST');
        $response = $this->controller->createArticle($request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Content is required', json_decode($response->getContent(), true)['error']);
    }

    public function testCreateArticle_ValidationMissingCategory(): void
    {
        $data = ['title' => 'Title', 'content' => 'Body'];
        $request = $this->createRequest([], $data, 'POST');
        $response = $this->controller->createArticle($request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Category is required', json_decode($response->getContent(), true)['error']);
    }

    public function testCreateArticle_SanitizesInputs(): void
    {
        $data = [
            'title' => '  <script>alert(1)</script>  ',
            'content' => '  <b>Bold</b>  ',
            'category' => '  Tech  ',
            'summary' => '  Summary & More  ',
        ];
        $request = $this->createRequest([], $data, 'POST');

        $article = $this->createArticleEntity([
            'id' => 1,
            'title' => htmlspecialchars(trim($data['title']), ENT_QUOTES, 'UTF-8'),
            'content' => htmlspecialchars(trim($data['content']), ENT_QUOTES, 'UTF-8'),
            'summary' => htmlspecialchars(trim($data['summary']), ENT_QUOTES, 'UTF-8'),
            'category' => htmlspecialchars(trim($data['category']), ENT_QUOTES, 'UTF-8'),
        ]);
        $this->serviceMock->expects($this->once())->method('createArticle')->with($this->anything())->willReturn($article);

        $response = $this->controller->createArticle($request);
        $this->assertSame(201, $response->getStatusCode());
    }

    public function testCreateArticle_ServiceThrowsInvalidAuthor(): void
    {
        $data = ['title' => 'T', 'content' => 'C', 'category' => 'Gen'];
        $request = $this->createRequest([], $data, 'POST');
        $this->serviceMock->method('createArticle')->willThrowException(new InvalidArgumentException('Invalid author ID'));
        $response = $this->controller->createArticle($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    // ==================== getArticles ====================

    public function testGetArticles_PublishedOnly(): void
    {
        $articles = [$this->createArticleEntity(['id'=>1]), $this->createArticleEntity(['id'=>2])];
        $this->serviceMock->method('getPublishedArticles')->with(null)->willReturn($articles);
        $request = $this->createRequest([], null, 'GET');
        $response = $this->controller->getArticles($request);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testGetArticles_FilterByCategory(): void
    {
        $articles = [$this->createArticleEntity(['id'=>1, 'category'=>'Tech'])];
        $this->serviceMock->method('getPublishedArticles')->with('Tech')->willReturn($articles);
        $request = $this->createRequest(['category' => 'Tech'], null, 'GET');
        $response = $this->controller->getArticles($request);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testGetArticles_ByAuthor(): void
    {
        $articles = [$this->createArticleEntity(['id'=>1, 'authorId'=>1])];
        $this->serviceMock->method('getAuthorArticles')->with(1)->willReturn($articles);
        $request = $this->createRequest(['author_id' => '1'], null, 'GET');
        $response = $this->controller->getArticles($request);
        $this->assertSame(200, $response->getStatusCode());
    }

    // ==================== searchArticles ====================

    public function testSearchArticles_Success(): void
    {
        $articles = [$this->createArticleEntity(['id'=>1, 'title'=>'Test'])];
        $this->serviceMock->method('searchArticles')->with('test')->willReturn($articles);
        $request = $this->createRequest(['q' => 'test'], null, 'GET');
        $response = $this->controller->searchArticles($request);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testSearchArticles_MissingQuery(): void
    {
        $request = $this->createRequest([], null, 'GET');
        $response = $this->controller->searchArticles($request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame(['error' => 'Search query "q" is required'], json_decode($response->getContent(), true));
    }

    public function testSearchArticles_EmptyQuery(): void
    {
        $request = $this->createRequest(['q' => '   '], null, 'GET');
        $response = $this->controller->searchArticles($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    // ==================== getArticle ====================

    public function testGetArticle_Success(): void
    {
        $article = $this->createArticleEntity(['id'=>1, 'viewCount'=>0]);
        $this->serviceMock->method('getArticle')->with(1)->willReturn($article);
        $this->serviceMock->expects($this->once())->method('incrementViewCount')->with(1);
        $request = $this->createRequest([], null, 'GET', 1);
        $response = $this->controller->getArticle(1, $request);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testGetArticle_NotFound(): void
    {
        $this->serviceMock->method('getArticle')->with(999)->willReturn(null);
        $request = $this->createRequest([], null, 'GET', 999);
        $response = $this->controller->getArticle(999, $request);
        $this->assertSame(404, $response->getStatusCode());
    }

    // ==================== updateArticle ====================

    public function testUpdateArticle_Success(): void
    {
        $article = $this->createArticleEntity(['id'=>1, 'authorId'=>1]);
        $this->serviceMock->method('getArticle')->with(1)->willReturn($article);
        $this->serviceMock->expects($this->once())->method('updateArticle')->with(1, $this->callback(function($data) {
            return isset($data['title']) && $data['title'] === 'New Title';
        }), 1)->willReturn($article);
        $request = $this->createRequest([], ['title' => 'New Title'], 'PUT', 1);
        $response = $this->controller->updateArticle(1, $request);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testUpdateArticle_NotFound(): void
    {
        $this->serviceMock->method('getArticle')->with(1)->willReturn(null);
        $request = $this->createRequest([], ['title' => 'New'], 'PUT', 1);
        $response = $this->controller->updateArticle(1, $request);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testUpdateArticle_Unauthorized(): void
    {
        $article = $this->createArticleEntity(['id'=>1, 'authorId'=>2]);
        $this->serviceMock->method('getArticle')->with(1)->willReturn($article);
        $this->serviceMock->expects($this->once())->method('updateArticle')->with(1, $this->anything(), 1)->willThrowException(new InvalidArgumentException('Unauthorized: not the author'));
        $request = $this->createRequest([], ['title' => 'New'], 'PUT', 1);
        $response = $this->controller->updateArticle(1, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    // ==================== deleteArticle ====================

    public function testDeleteArticle_Success(): void
    {
        $this->serviceMock->method('deleteArticle')->with(1, 1)->willReturn(true);
        $request = $this->createRequest([], null, 'DELETE', 1);
        $response = $this->controller->deleteArticle(1, $request);
        $this->assertSame(204, $response->getStatusCode());
    }

    public function testDeleteArticle_NotFound(): void
    {
        $this->serviceMock->method('deleteArticle')->with(1, 1)->willReturn(false);
        $request = $this->createRequest([], null, 'DELETE', 1);
        $response = $this->controller->deleteArticle(1, $request);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testDeleteArticle_Unauthorized(): void
    {
        $this->serviceMock->method('deleteArticle')->with(1, 1)->willThrowException(new InvalidArgumentException('Unauthorized'));
        $request = $this->createRequest([], null, 'DELETE', 1);
        $response = $this->controller->deleteArticle(1, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    // ==================== getComments ====================

    public function testGetComments_Success(): void
    {
        $comments = [$this->createCommentEntity(['id'=>1])];
        $this->serviceMock->method('getArticleComments')->with(1)->willReturn($comments);
        $response = $this->controller->getComments(1);
        $this->assertSame(200, $response->getStatusCode());
    }

    // ==================== addComment ====================

    public function testAddComment_Success(): void
    {
        $data = ['article_id' => 1, 'content' => 'Nice article!'];
        $request = $this->createRequest([], $data, 'POST');
        $comment = $this->createCommentEntity(['id'=>123, 'articleId'=>1, 'userId'=>1]);
        $this->serviceMock->expects($this->once())->method('addComment')->with(1, 1, 'Nice article!', null)->willReturn($comment);
        $response = $this->controller->addComment($request);
        $this->assertSame(201, $response->getStatusCode());
    }

    public function testAddComment_MissingArticleId(): void
    {
        $data = ['content' => 'Comment'];
        $request = $this->createRequest([], $data, 'POST');
        $response = $this->controller->addComment($request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame(['error' => 'Article ID is required'], json_decode($response->getContent(), true));
    }

    public function testAddComment_EmptyContent(): void
    {
        $data = ['article_id' => 1, 'content' => '   '];
        $request = $this->createRequest([], $data, 'POST');
        $response = $this->controller->addComment($request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame(['error' => 'Comment content is required'], json_decode($response->getContent(), true));
    }

    public function testAddComment_ArticleNotFound(): void
    {
        $data = ['article_id' => 999, 'content' => 'Comment'];
        $request = $this->createRequest([], $data, 'POST');
        $this->serviceMock->method('addComment')->willThrowException(new InvalidArgumentException('Article not found'));
        $response = $this->controller->addComment($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    // ==================== deleteComment ====================

    public function testDeleteComment_Success(): void
    {
        $this->serviceMock->method('deleteComment')->with(1, 1)->willReturn(true);
        $response = $this->controller->deleteComment(1, $this->createRequest([], null, 'DELETE'));
        $this->assertSame(204, $response->getStatusCode());
    }

    public function testDeleteComment_NotFound(): void
    {
        $this->serviceMock->method('deleteComment')->with(1, 1)->willReturn(false);
        $response = $this->controller->deleteComment(1, $this->createRequest([], null, 'DELETE'));
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testDeleteComment_Unauthorized(): void
    {
        $this->serviceMock->method('deleteComment')->with(1, 1)->willThrowException(new InvalidArgumentException('Unauthorized'));
        $response = $this->controller->deleteComment(1, $this->createRequest([], null, 'DELETE'));
        $this->assertSame(400, $response->getStatusCode());
    }
}
