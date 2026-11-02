<?php declare(strict_types=1);

namespace App\Tests\Unit\Social\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Social\Entity\Post;
use App\Social\Repository\PostRepository;
use App\Shared\EventBus\EventBus;
use App\Social\Service\PostService;
use App\Social\Entity\PostLike;
use App\Social\Entity\PostComment;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class PostServiceTest extends TestCase
{
    private PostRepository $postRepoMock;
    private EventBus $eventBusMock;
    private PostService $service;

    protected function setUp(): void
    {
        $this->postRepoMock = $this->createMock(PostRepository::class);
        $this->eventBusMock = $this->createMock(EventBus::class);
        $this->service = new PostService($this->postRepoMock, $this->eventBusMock);
    }

    private function createPost(?string $id = null, $userId = '1', ?string $content = null, ?string $mediaUrl = null, ?string $mediaType = null, array $metadata = [], bool $isPublic = true): Post
    {
        $post = new Post(
            userId: (string)$userId,
            content: $content ?? '',
            mediaUrl: $mediaUrl,
            mediaType: $mediaType,
            metadata: $metadata,
            isPublic: $isPublic,
        );
        if ($id !== null) {
            $ref = new \ReflectionClass($post);
            $prop = $ref->getProperty('id');
            $prop->setAccessible(true);
            $prop->setValue($post, $id);
        }
        return $post;
    }

    // ==================== createPost ====================

    public function testCreatePostSuccess(): void
    {
        $this->postRepoMock->method('save')->willReturnArgument(0);
        $this->eventBusMock->expects($this->once())->method('dispatch')->with($this->isInstanceOf(\App\Social\Event\PostCreatedEvent::class));

        $result = $this->service->createPost(
            userId: '5',
            content: 'Hello world',
            mediaUrl: null,
            mediaType: null,
            metadata: ['foo' => 'bar'],
            isPublic: true
        );

        $this->assertInstanceOf(Post::class, $result);
        $this->assertSame('5', $result->getUserId());
        $this->assertSame('Hello world', $result->getContent());
    }

    // ==================== likePost ====================

    public function testLikePostCreatesLike(): void
    {
        $post = $this->createPost(id: '1');
        $this->postRepoMock->method('find')->with('1')->willReturn($post);
        $this->postRepoMock->method('findLike')->with('1', '2')->willReturn(null);
        $this->postRepoMock->method('saveLike')->willReturnArgument(0);
        $this->postRepoMock->method('incrementLikeCount')->with('1');

        $this->service->likePost('1', '2');

        $this->assertTrue(true);
    }

    public function testLikePostRemovesLikeWhenAlreadyLiked(): void
    {
        $post = $this->createPost(id: '1');
        $existingLike = new PostLike(postId: '1', userId: '2');
        $this->postRepoMock->method('find')->with('1')->willReturn($post);
        $this->postRepoMock->method('findLike')->with('1', '2')->willReturn($existingLike);
        $this->postRepoMock->method('removeLike')->with($existingLike->getId());
        $this->postRepoMock->method('decrementLikeCount')->with('1');

        $this->service->likePost('1', '2');

        $this->assertTrue(true);
    }

    public function testLikePostThrowsIfPostNotFound(): void
    {
        $this->postRepoMock->method('find')->with('99')->willReturn(null);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Post not found');
        $this->service->likePost('99', '2');
    }

    public function testLikePostThrowsIfPostDeleted(): void
    {
        $post = $this->createPost(id: '1');
        // Set deletedAt via reflection
        $ref = new \ReflectionClass($post);
        $prop = $ref->getProperty('deletedAt');
        $prop->setAccessible(true);
        $prop->setValue($post, new \DateTime());
        $this->postRepoMock->method('find')->with('1')->willReturn($post);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Post not found');
        $this->service->likePost('1', '2');
    }

    // ==================== commentPost ====================

    public function testCommentPostSuccess(): void
    {
        $post = $this->createPost(id: '1');
        $this->postRepoMock->method('find')->with('1')->willReturn($post);
        $this->postRepoMock->method('saveComment')->willReturnArgument(0);
        $this->postRepoMock->method('incrementCommentCount')->with('1');

        $this->service->commentPost('1', '2', 'Nice!');

        $this->assertTrue(true);
    }

    public function testCommentPostThrowsIfPostNotFound(): void
    {
        $this->postRepoMock->method('find')->with('1')->willReturn(null);
        $this->expectException(\InvalidArgumentException::class);
        $this->service->commentPost('1', '2', 'text');
    }

    public function testCommentPostThrowsIfPostDeleted(): void
    {
        $post = $this->createPost(id: '1');
        $ref = new \ReflectionClass($post);
        $prop = $ref->getProperty('deletedAt');
        $prop->setAccessible(true);
        $prop->setValue($post, new \DateTime());
        $this->postRepoMock->method('find')->with('1')->willReturn($post);
        $this->expectException(\InvalidArgumentException::class);
        $this->service->commentPost('1', '2', 'text');
    }

    // ==================== sharePost ====================

    public function testSharePostSuccess(): void
    {
        $post = $this->createPost(id: '1');
        $this->postRepoMock->method('find')->with('1')->willReturn($post);
        $this->postRepoMock->method('incrementShareCount')->with('1');

        $this->service->sharePost('1', '2');

        $this->assertTrue(true);
    }

    public function testSharePostThrowsIfPostNotFound(): void
    {
        $this->postRepoMock->method('find')->with('1')->willReturn(null);
        $this->expectException(\InvalidArgumentException::class);
        $this->service->sharePost('1', '2');
    }

    public function testSharePostThrowsIfPostDeleted(): void
    {
        $post = $this->createPost(id: '1');
        $ref = new \ReflectionClass($post);
        $prop = $ref->getProperty('deletedAt');
        $prop->setAccessible(true);
        $prop->setValue($post, new \DateTime());
        $this->postRepoMock->method('find')->with('1')->willReturn($post);
        $this->expectException(\InvalidArgumentException::class);
        $this->service->sharePost('1', '2');
    }

    // ==================== deletePost ====================

    public function testDeletePostSuccess(): void
    {
        $post = $this->createPost(id: '1', userId: '5');
        $this->postRepoMock->method('find')->with('1')->willReturn($post);
        $this->postRepoMock->expects($this->once())->method('delete')->with($post);

        $this->service->deletePost('1', '5');
    }

    public function testDeletePostThrowsIfNotFound(): void
    {
        $this->postRepoMock->method('find')->with('1')->willReturn(null);
        $this->expectException(\InvalidArgumentException::class);
        $this->service->deletePost('1', '5');
    }

    public function testDeletePostThrowsIfUnauthorized(): void
    {
        $post = $this->createPost(id: '1', userId: '5');
        $this->postRepoMock->method('find')->with('1')->willReturn($post);
        $this->expectException(\InvalidArgumentException::class);
        $this->service->deletePost('1', '2');
    }

    // ==================== getFeedForUser ====================

    public function testGetFeedForUser(): void
    {
        $posts = [$this->createPost(id: '1'), $this->createPost(id: '2')];
        $this->postRepoMock->method('findFeedForUser')->with('5', 0, 20)->willReturn($posts);
        $result = $this->service->getFeedForUser('5');
        $this->assertSame($posts, $result);
    }

    // ==================== getPublicFeed ====================

    public function testGetPublicFeed(): void
    {
        $posts = [$this->createPost(), $this->createPost()];
        $this->postRepoMock->method('findPublicPosts')->with(0, 50)->willReturn($posts);
        $result = $this->service->getPublicFeed();
        $this->assertSame($posts, $result);
    }

    // ==================== getPostDetail ====================

    public function testGetPostDetailFound(): void
    {
        $post = $this->createPost(id: '1');
        $this->postRepoMock->method('find')->with('1')->willReturn($post);
        $result = $this->service->getPostDetail('1');
        $this->assertSame($post, $result);
    }

    public function testGetPostDetailReturnsNullIfDeleted(): void
    {
        $post = $this->createPost(id: '1');
        $ref = new \ReflectionClass($post);
        $prop = $ref->getProperty('deletedAt');
        $prop->setAccessible(true);
        $prop->setValue($post, new \DateTime());
        $this->postRepoMock->method('find')->with('1')->willReturn($post);
        $result = $this->service->getPostDetail('1');
        $this->assertNull($result);
    }

    public function testGetPostDetailReturnsNullIfNotFound(): void
    {
        $this->postRepoMock->method('find')->with('1')->willReturn(null);
        $result = $this->service->getPostDetail('1');
        $this->assertNull($result);
    }
}
