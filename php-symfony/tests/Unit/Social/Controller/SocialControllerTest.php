<?php

declare(strict_types=1);

namespace Tests\Unit\Social\Controller;

use App\Social\Controller\SocialController;
use App\Social\Entity\Post as SocialPost;
use App\Social\Service\PostService;
use App\User\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class SocialControllerTest extends TestCase
{
    private PostService $postServiceMock;
    private SocialController $controller;
    private TokenStorageInterface $tokenStorageMock;

    protected function setUp(): void
    {
        $this->postServiceMock = $this->createMock(PostService::class);
        $this->tokenStorageMock = $this->createMock(TokenStorageInterface::class);
        $this->controller = new SocialController($this->postServiceMock, $this->tokenStorageMock);
        $this->controller->setContainer(new ContainerBuilder());
    }

    private function setAuthenticatedUser(?User $user = null): void
    {
        $this->controller->setTestUser($user);
        if ($user) {
            $token = $this->createMock(TokenInterface::class);
            $token->method('getUser')->willReturn($user);
            $this->tokenStorageMock->method('getToken')->willReturn($token);
        } else {
            $this->tokenStorageMock->method('getToken')->willReturn(null);
        }
    }

    private function createUser(int $id, string $email): User
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($id);
        $user->method('getUserIdentifier')->willReturn($email);
        return $user;
    }

    private function createPostMock(array $data): SocialPost
    {
        $mock = $this->createMock(SocialPost::class);
        $mock->method('toArray')->willReturn($data);
        // Also stub individual getters used in tests
        foreach ([ 'id', 'user_id', 'content', 'media_url', 'media_type', 'is_public' ] as $getter) {
            $snake = $getter; // 'id', 'user_id', etc.
            $camel = lcfirst(str_replace('_', '', ucwords($snake, '_')));
            if (method_exists(SocialPost::class, 'get' . $camel)) {
                $value = $data[$snake] ?? null;
                $mock->method('get' . $camel)->willReturn($value);
            }
        }
        return $mock;
    }

    // ==================== createPost ====================

    public function testCreatePost_Success(): void
    {
        $user = $this->createUser(1, 'user1@example.com');
        $this->setAuthenticatedUser($user);

        $post = $this->createMock(SocialPost::class);
        $post->method('toArray')->willReturn([
            'id' => 'post-123',
            'userId' => '1',
            'content' => 'Hello world',
            'mediaUrl' => null,
            'mediaType' => null,
            'metadata' => [],
            'isPublic' => true,
            'likeCount' => 0,
            'commentCount' => 0,
            'shareCount' => 0,
            'createdAt' => null,
            'deletedAt' => null,
        ]);

        $this->postServiceMock->expects($this->once())
            ->method('createPost')
            ->with(
                $this->equalTo('1'),
                $this->equalTo('Hello world'),
                $this->equalTo(null),
                $this->equalTo(null),
                $this->equalTo([]),
                $this->equalTo(true)
            )
            ->willReturn($post);

        $request = Request::create('/api/social/posts', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'content' => 'Hello world',
            'is_public' => true,
        ]));

        $response = $this->controller->createPost($request);
        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('post-123', $data['id']);
    }

    public function testCreatePost_WithMedia(): void
    {
        $user = $this->createUser(1, 'user1@example.com');
        $this->setAuthenticatedUser($user);

        $post = $this->createMock(SocialPost::class);
        $post->method('toArray')->willReturn([
            'id' => 'post-124',
            'userId' => '1',
            'content' => null,
            'mediaUrl' => 'https://example.com/image.jpg',
            'mediaType' => 'image',
            'metadata' => [],
            'isPublic' => false,
            'likeCount' => 0,
            'commentCount' => 0,
            'shareCount' => 0,
            'createdAt' => null,
            'deletedAt' => null,
        ]);

        $this->postServiceMock->expects($this->once())
            ->method('createPost')
            ->with(
                $this->equalTo('1'),
                $this->equalTo(null),
                $this->equalTo('https://example.com/image.jpg'),
                $this->equalTo('image'),
                $this->equalTo([]),
                $this->equalTo(false)
            )
            ->willReturn($post);

        $request = Request::create('/api/social/posts', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'media_url' => 'https://example.com/image.jpg',
            'media_type' => 'image',
            'is_public' => false,
        ]));

        $response = $this->controller->createPost($request);
        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode());
    }

    public function testCreatePost_Unauthenticated_Returns401(): void
    {
        $this->setAuthenticatedUser(null);

        $request = Request::create('/api/social/posts', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['content' => 'test']));

        $response = $this->controller->createPost($request);
        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testCreatePost_InvalidJson_Returns400(): void
    {
        $user = $this->createUser(1, 'user1@example.com');
        $this->setAuthenticatedUser($user);

        $request = Request::create('/api/social/posts', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], 'invalid json');

        $response = $this->controller->createPost($request);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testCreatePost_NoContentOrMedia_Returns400(): void
    {
        $user = $this->createUser(1, 'user1@example.com');
        $this->setAuthenticatedUser($user);

        $request = Request::create('/api/social/posts', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['is_public' => true]));

        $response = $this->controller->createPost($request);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('errors', $data);
        $this->assertContains('Post must have content or media', $data['errors']);
    }

    public function testCreatePost_ContentTooLong_Returns400(): void
    {
        $user = $this->createUser(1, 'user1@example.com');
        $this->setAuthenticatedUser($user);

        $longContent = str_repeat('a', 5001);

        $request = Request::create('/api/social/posts', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['content' => $longContent]));

        $response = $this->controller->createPost($request);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testCreatePost_InvalidMediaUrl_Returns400(): void
    {
        $user = $this->createUser(1, 'user1@example.com');
        $this->setAuthenticatedUser($user);

        $request = Request::create('/api/social/posts', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'media_url' => 'not-a-valid-url',
            'media_type' => 'image',
        ]));

        $response = $this->controller->createPost($request);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testCreatePost_InvalidMediaType_Returns400(): void
    {
        $user = $this->createUser(1, 'user1@example.com');
        $this->setAuthenticatedUser($user);

        $request = Request::create('/api/social/posts', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'media_url' => 'https://example.com/file.jpg',
            'media_type' => 'invalid_type',
        ]));

        $response = $this->controller->createPost($request);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testCreatePost_NonBooleanIsPublic_Returns400(): void
    {
        $user = $this->createUser(1, 'user1@example.com');
        $this->setAuthenticatedUser($user);

        $request = Request::create('/api/social/posts', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'content' => 'test',
            'is_public' => 'yes',
        ]));

        $response = $this->controller->createPost($request);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    // ==================== getPosts ====================

    public function testGetPosts_Authenticated_Feed(): void
    {
        $user = $this->createUser(1, 'user1@example.com');
        $this->setAuthenticatedUser($user);

        $posts = [$this->createPostMock(['id' => 'p1']), $this->createPostMock(['id' => 'p2'])];
        $this->postServiceMock->method('getFeedForUser')->with('1', 0, 20)->willReturn($posts);

        $request = Request::create('/api/social/posts', 'GET');
        $response = $this->controller->getPosts($request);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertCount(2, $data);
    }

    public function testGetPosts_PublicFeed(): void
    {
        $user = $this->createUser(1, 'user1@example.com');
        $this->setAuthenticatedUser($user);

        $posts = [$this->createPostMock(['id' => 'p1'])];
        $this->postServiceMock->method('getPublicFeed')->with(0, 20)->willReturn($posts);

        $request = Request::create('/api/social/posts', 'GET', ['public' => '1']);
        $response = $this->controller->getPosts($request);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testGetPosts_Pagination(): void
    {
        $user = $this->createUser(1, 'user1@example.com');
        $this->setAuthenticatedUser($user);

        $posts = [];
        $this->postServiceMock->method('getFeedForUser')->with('1', 10, 50)->willReturn($posts);

        $request = Request::create('/api/social/posts', 'GET', ['offset' => '10', 'limit' => '50']);
        $response = $this->controller->getPosts($request);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testGetPosts_InvalidOffset_UsesDefault(): void
    {
        $user = $this->createUser(1, 'user1@example.com');
        $this->setAuthenticatedUser($user);

        $posts = [];
        $this->postServiceMock->method('getFeedForUser')->with('1', 0, 20)->willReturn($posts);

        $request = Request::create('/api/social/posts', 'GET', ['offset' => '-1']);
        $response = $this->controller->getPosts($request);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testGetPosts_InvalidLimit_UsesDefault(): void
    {
        $user = $this->createUser(1, 'user1@example.com');
        $this->setAuthenticatedUser($user);

        $posts = [];
        $this->postServiceMock->method('getFeedForUser')->with('1', 0, 20)->willReturn($posts);

        $request = Request::create('/api/social/posts', 'GET', ['limit' => '200']);
        $response = $this->controller->getPosts($request);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testGetPosts_Unauthenticated_Returns401(): void
    {
        $this->setAuthenticatedUser(null);

        $request = Request::create('/api/social/posts', 'GET');
        $response = $this->controller->getPosts($request);
        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    // ==================== getPost ====================

    public function testGetPost_Success(): void
    {
        $postDetail = [
            'id' => 'post-123',
            'content' => 'Hello',
            'likes' => [],
            'comments' => [],
        ];
        $this->postServiceMock->method('getPostDetail')->with('post-123')->willReturn($postDetail);

        $response = $this->controller->getPost('post-123');

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('post-123', $data['id']);
    }

    public function testGetPost_NotFound_Returns404(): void
    {
        $this->postServiceMock->method('getPostDetail')->with('missing')->willReturn(null);

        $response = $this->controller->getPost('missing');

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testGetUserReturnsSetUser(): void
    {
        $user = $this->createUser(1, 'user1@example.com');
        $this->setAuthenticatedUser($user);
        $ref = new \ReflectionProperty($this->controller, 'testUser');
        $ref->setAccessible(true);
        error_log('value after setAuthenticatedUser: ' . var_export($ref->getValue($this->controller), true));
        $this->assertSame($user, $this->controller->getUser());
    }

    public function testSetTestUserDirectly(): void
    {
        $user = $this->createUser(1, 'user1@example.com');
        $this->controller->setTestUser($user);
        $this->assertSame($user, $this->controller->getUser());
    }

    // ==================== likePost ====================

    public function testLikePost_Success(): void
    {
        $user = $this->createUser(1, 'user1@example.com');
        $this->setAuthenticatedUser($user);

        $this->postServiceMock->expects($this->once())
            ->method('likePost')
            ->with('post-123', '1');

        $response = $this->controller->likePost('post-123');

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testLikePost_PostNotFound_Returns404(): void
    {
        $user = $this->createUser(1, 'user1@example.com');
        $this->setAuthenticatedUser($user);

        $this->postServiceMock->method('likePost')
            ->with('missing', '1')
            ->willThrowException(new \InvalidArgumentException('Post not found'));

        $response = $this->controller->likePost('missing');

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testLikePost_Unauthenticated_Returns401(): void
    {
        $this->setAuthenticatedUser(null);

        $response = $this->controller->likePost('post-123');

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    // ==================== commentPost ====================

    public function testCommentPost_Success(): void
    {
        $user = $this->createUser(1, 'user1@example.com');
        $this->setAuthenticatedUser($user);

        $this->postServiceMock->expects($this->once())
            ->method('commentPost')
            ->with('post-123', '1', 'Nice post!');

        $request = Request::create('/api/social/posts/post-123/comments', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['content' => 'Nice post!']));

        $response = $this->controller->commentPost($request, 'post-123');

        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode());
    }

    public function testCommentPost_EmptyContent_Returns400(): void
    {
        $user = $this->createUser(1, 'user1@example.com');
        $this->setAuthenticatedUser($user);

        $request = Request::create('/api/social/posts/post-123/comments', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['content' => '']));

        $response = $this->controller->commentPost($request, 'post-123');

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testCommentPost_TooLong_Returns400(): void
    {
        $user = $this->createUser(1, 'user1@example.com');
        $this->setAuthenticatedUser($user);

        $longContent = str_repeat('a', 1001);

        $request = Request::create('/api/social/posts/post-123/comments', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['content' => $longContent]));

        $response = $this->controller->commentPost($request, 'post-123');

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testCommentPost_PostNotFound_Returns404(): void
    {
        $user = $this->createUser(1, 'user1@example.com');
        $this->setAuthenticatedUser($user);

        $this->postServiceMock->method('commentPost')
            ->with('missing', '1', 'test')
            ->willThrowException(new \InvalidArgumentException('Post not found'));

        $request = Request::create('/api/social/posts/missing/comments', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['content' => 'test']));

        $response = $this->controller->commentPost($request, 'missing');

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testCommentPost_Unauthenticated_Returns401(): void
    {
        $this->setAuthenticatedUser(null);

        $request = Request::create('/api/social/posts/post-123/comments', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['content' => 'test']));

        $response = $this->controller->commentPost($request, 'post-123');

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    // ==================== sharePost ====================

    public function testSharePost_Success(): void
    {
        $user = $this->createUser(1, 'user1@example.com');
        $this->setAuthenticatedUser($user);

        $this->postServiceMock->expects($this->once())
            ->method('sharePost')
            ->with('post-123', '1');

        $response = $this->controller->sharePost('post-123');

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testSharePost_PostNotFound_Returns404(): void
    {
        $user = $this->createUser(1, 'user1@example.com');
        $this->setAuthenticatedUser($user);

        $this->postServiceMock->method('sharePost')
            ->with('missing', '1')
            ->willThrowException(new \InvalidArgumentException('Post not found'));

        $response = $this->controller->sharePost('missing');

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testSharePost_Unauthenticated_Returns401(): void
    {
        $this->setAuthenticatedUser(null);

        $response = $this->controller->sharePost('post-123');

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    // ==================== deletePost ====================

    public function testDeletePost_Success(): void
    {
        $user = $this->createUser(1, 'user1@example.com');
        $this->setAuthenticatedUser($user);

        $this->postServiceMock->expects($this->once())
            ->method('deletePost')
            ->with('post-123', '1');

        $response = $this->controller->deletePost('post-123');
        error_log('RESPONSE: ' . $response->getContent());

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testDeletePost_NotOwner_Returns403(): void
    {
        $user = $this->createUser(1, 'user1@example.com');
        $this->setAuthenticatedUser($user);

        $this->postServiceMock->method('deletePost')
            ->with('post-123', '1')
            ->willThrowException(new \InvalidArgumentException('Not authorized'));

        $response = $this->controller->deletePost('post-123');

        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function testDeletePost_PostNotFound_Returns404(): void
    {
        $user = $this->createUser(1, 'user1@example.com');
        $this->setAuthenticatedUser($user);

        $this->postServiceMock->method('deletePost')
            ->with('missing', '1')
            ->willThrowException(new \InvalidArgumentException('Post not found'));

        $response = $this->controller->deletePost('missing');

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testDeletePost_Unauthenticated_Returns401(): void
    {
        $this->setAuthenticatedUser(null);

        $response = $this->controller->deletePost('post-123');

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    // ==================== getPostComments ====================

    public function testGetPostComments_Success(): void
    {
        $postDetail = [
            'id' => 'post-123',
            'comments' => [
                ['id' => 'c1', 'content' => 'Nice!'],
                ['id' => 'c2', 'content' => 'Great!'],
            ],
        ];
        $this->postServiceMock->method('getPostDetail')->with('post-123')->willReturn($postDetail);

        $response = $this->controller->getPostComments('post-123', new Request());

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertCount(2, $data);
    }

    public function testGetPostComments_PostNotFound_Returns404(): void
    {
        $this->postServiceMock->method('getPostDetail')->with('missing')->willReturn(null);

        $response = $this->controller->getPostComments('missing', new Request());

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    // ==================== getPostLikes ====================

    public function testGetPostLikes_Success(): void
    {
        $postDetail = [
            'id' => 'post-123',
            'likes' => ['user-1', 'user-2'],
        ];
        $this->postServiceMock->method('getPostDetail')->with('post-123')->willReturn($postDetail);

        $response = $this->controller->getPostLikes('post-123', new Request());

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertCount(2, $data);
    }

    public function testGetPostLikes_PostNotFound_Returns404(): void
    {
        $this->postServiceMock->method('getPostDetail')->with('missing')->willReturn(null);

        $response = $this->controller->getPostLikes('missing', new Request());

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }
}
