<?php

declare(strict_types=1);

namespace App\Tests\Controller\Social;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Social\Controller\SocialController;
use App\Social\Entity\Post;
use App\Social\Service\PostService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use App\User\Entity\User;

#[AllowMockObjectsWithoutExpectations]
class SocialControllerTest extends TestCase
{
    private SocialController $controller;
    private PostService|MockObject $postService;
    private User|MockObject $currentUser;

    protected function setUp(): void
    {
        $this->postService = $this->createMock(PostService::class);
        $this->currentUser = $this->createMock(User::class);
        $this->currentUser->method('getId')->willReturn(123);

        $this->controller = new SocialController($this->postService);
        $this->setTestUser();
    }

    private function setTestUser(): void
    {
        $this->controller->setTestUser($this->currentUser);
    }

    public function testCreatePostSuccessWithContent(): void
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'content' => 'Test post content',
                'is_public' => true,
            ])
        );

        $post = $this->createMock(Post::class);
        $post->method('toArray')->willReturn([
            'id' => 'post123',
            'userId' => 123,
            'content' => 'Test post content',
            'isPublic' => true,
        ]);

        $this->postService
            ->expects($this->once())
            ->method('createPost')
            ->with(
                123,
                'Test post content',
                null,
                null,
                [],
                true
            )
            ->willReturn($post);

        $result = $this->controller->createPost($request);

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(201, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('post123', $data['id']);
        $this->assertEquals('Test post content', $data['content']);
    }

    public function testCreatePostSuccessWithMedia(): void
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'media_url' => 'https://example.com/image.jpg',
                'media_type' => 'image',
                'metadata' => ['alt' => 'Test image'],
            ])
        );

        $post = $this->createMock(Post::class);
        $post->method('toArray')->willReturn([
            'id' => 'post456',
            'userId' => 123,
            'mediaUrl' => 'https://example.com/image.jpg',
            'mediaType' => 'image',
        ]);

        $this->postService
            ->expects($this->once())
            ->method('createPost')
            ->with(
                123,
                null,
                'https://example.com/image.jpg',
                'image',
                ['alt' => 'Test image'],
                true
            )
            ->willReturn($post);

        $result = $this->controller->createPost($request);

        $this->assertEquals(201, $result->getStatusCode());
        $data = json_decode($result->getContent(), true);
        $this->assertEquals('post456', $data['id']);
    }

    public function testCreatePostInvalidJson(): void
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            'invalid json'
        );

        $result = $this->controller->createPost($request);

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(400, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Invalid JSON', $data['error']);
    }

    public function testCreatePostMissingContentAndMedia(): void
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['metadata' => []])
        );

        $result = $this->controller->createPost($request);

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(400, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Validation failed', $data['error']);
        $this->assertContains('Post must have content or media', $data['errors']);
    }

    public function testGetPostsUserFeed(): void
    {
        $request = new Request(['offset' => 0, 'limit' => 10]);

        $posts = [
            ['id' => 'post1', 'content' => 'Post 1'],
            ['id' => 'post2', 'content' => 'Post 2'],
        ];

        $this->postService
            ->expects($this->once())
            ->method('getFeedForUser')
            ->with(123, 0, 10)
            ->willReturn($posts);

        $result = $this->controller->getPosts($request);

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertCount(2, $data);
    }

    public function testGetPostsPublicFeed(): void
    {
        $request = new Request(['public' => 'true', 'offset' => 0, 'limit' => 20]);

        $posts = [
            ['id' => 'post1', 'content' => 'Public post 1'],
        ];

        $this->postService
            ->expects($this->once())
            ->method('getPublicFeed')
            ->with(0, 20)
            ->willReturn($posts);

        $result = $this->controller->getPosts($request);

        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertCount(1, $data);
    }

    public function testGetPostsDefaultPagination(): void
    {
        $request = new Request();

        $posts = [];

        $this->postService
            ->expects($this->once())
            ->method('getFeedForUser')
            ->with(123, 0, 20)
            ->willReturn($posts);

        $result = $this->controller->getPosts($request);

        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testGetPostSuccess(): void
    {
        $postDetail = [
            'id' => 'post123',
            'content' => 'Test post',
            'likes' => [],
            'comments' => [],
        ];

        $this->postService
            ->expects($this->once())
            ->method('getPostDetail')
            ->with('post123')
            ->willReturn($postDetail);

        $result = $this->controller->getPost('post123');

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('post123', $data['id']);
    }

    public function testGetPostNotFound(): void
    {
        $this->postService
            ->expects($this->once())
            ->method('getPostDetail')
            ->with('notfound')
            ->willReturn(null);

        $result = $this->controller->getPost('notfound');

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(404, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Post not found', $data['error']);
    }

    public function testLikePostSuccess(): void
    {
        $this->postService
            ->expects($this->once())
            ->method('likePost')
            ->with('post123', 123)
            ->willReturn(null);

        $result = $this->controller->likePost('post123');

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Post liked/unliked successfully', $data['message']);
    }

    public function testLikePostNotFound(): void
    {
        $this->postService
            ->expects($this->once())
            ->method('likePost')
            ->with('notfound', 123)
            ->willThrowException(new \InvalidArgumentException('Post not found'));

        $result = $this->controller->likePost('notfound');

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(404, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Post not found', $data['error']);
    }

    public function testCommentPostSuccess(): void
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['content' => 'Test comment'])
        );

        $this->postService
            ->expects($this->once())
            ->method('commentPost')
            ->with('post123', 123, 'Test comment')
            ->willReturn(null);

        $result = $this->controller->commentPost($request, 'post123');

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(201, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Comment added successfully', $data['message']);
    }

    public function testCommentPostInvalidJson(): void
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            'invalid json'
        );

        $result = $this->controller->commentPost($request, 'post123');

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(400, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Invalid JSON', $data['error']);
    }

    public function testCommentPostMissingContent(): void
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([])
        );

        $result = $this->controller->commentPost($request, 'post123');

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(400, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Comment content is required', $data['error']);
    }

    public function testCommentPostNotFound(): void
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['content' => 'Test comment'])
        );

        $this->postService
            ->expects($this->once())
            ->method('commentPost')
            ->with('notfound', 123, 'Test comment')
            ->willThrowException(new \InvalidArgumentException('Post not found'));

        $result = $this->controller->commentPost($request, 'notfound');

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(404, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Post not found', $data['error']);
    }

    public function testSharePostSuccess(): void
    {
        $this->postService
            ->expects($this->once())
            ->method('sharePost')
            ->with('post123', 123)
            ->willReturn(null);

        $result = $this->controller->sharePost('post123');

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Post shared successfully', $data['message']);
    }

    public function testSharePostNotFound(): void
    {
        $this->postService
            ->expects($this->once())
            ->method('sharePost')
            ->with('notfound', 123)
            ->willThrowException(new \InvalidArgumentException('Post not found'));

        $result = $this->controller->sharePost('notfound');

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(404, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Post not found', $data['error']);
    }

    public function testDeletePostSuccess(): void
    {
        $this->postService
            ->expects($this->once())
            ->method('deletePost')
            ->with('post123', 123)
            ->willReturn(null);

        $result = $this->controller->deletePost('post123');

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Post deleted successfully', $data['message']);
    }

    public function testDeletePostNotFound(): void
    {
        $this->postService
            ->expects($this->once())
            ->method('deletePost')
            ->with('notfound', 123)
            ->willThrowException(new \InvalidArgumentException('Post not found'));

        $result = $this->controller->deletePost('notfound');

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(404, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Post not found', $data['error']);
    }

    public function testDeletePostNotAuthorized(): void
    {
        $this->postService
            ->expects($this->once())
            ->method('deletePost')
            ->with('post123', 123)
            ->willThrowException(new \InvalidArgumentException('Not authorized to delete this post'));

        $result = $this->controller->deletePost('post123');

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(403, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Not authorized to delete this post', $data['error']);
    }

    public function testGetPostCommentsSuccess(): void
    {
        $postDetail = [
            'id' => 'post123',
            'comments' => [
                ['id' => 'comment1', 'content' => 'Comment 1'],
                ['id' => 'comment2', 'content' => 'Comment 2'],
            ],
        ];

        $this->postService
            ->expects($this->once())
            ->method('getPostDetail')
            ->with('post123')
            ->willReturn($postDetail);

        $request = new Request();
        $result = $this->controller->getPostComments('post123', $request);

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertCount(2, $data);
        $this->assertEquals('Comment 1', $data[0]['content']);
    }

    public function testGetPostCommentsPostNotFound(): void
    {
        $this->postService
            ->expects($this->once())
            ->method('getPostDetail')
            ->with('notfound')
            ->willReturn(null);

        $request = new Request();
        $result = $this->controller->getPostComments('notfound', $request);

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(404, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Post not found', $data['error']);
    }

    public function testGetPostLikesSuccess(): void
    {
        $postDetail = [
            'id' => 'post123',
            'likes' => [
                ['userId' => 'user1', 'name' => 'User 1'],
                ['userId' => 'user2', 'name' => 'User 2'],
            ],
        ];

        $this->postService
            ->expects($this->once())
            ->method('getPostDetail')
            ->with('post123')
            ->willReturn($postDetail);

        $request = new Request();
        $result = $this->controller->getPostLikes('post123', $request);

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertCount(2, $data);
        $this->assertEquals('user1', $data[0]['userId']);
    }

    public function testGetPostLikesPostNotFound(): void
    {
        $this->postService
            ->expects($this->once())
            ->method('getPostDetail')
            ->with('notfound')
            ->willReturn(null);

        $request = new Request();
        $result = $this->controller->getPostLikes('notfound', $request);

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(404, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Post not found', $data['error']);
    }
}
