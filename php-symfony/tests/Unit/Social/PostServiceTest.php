<?php declare(strict_types=1);

namespace App\Tests\Unit\Social;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Social\Entity\Post;
use App\Social\Entity\PostLike;
use App\Social\Entity\PostComment;
use App\Social\Repository\PostRepository;
use App\Social\Service\PostService;
use App\Social\Event\PostCreatedEvent;
use App\Shared\EventBus\EventBus;
use PHPUnit\Framework\TestCase;
use DateTime;

#[AllowMockObjectsWithoutExpectations]
class PostServiceTest extends TestCase
{
    private PostRepository $postRepo;
    private EventBus $eventBus;
    private PostService $service;

    protected function setUp(): void
    {
        $this->postRepo = $this->createMock(PostRepository::class);
        $this->eventBus = $this->createMock(EventBus::class);
        $this->service = new PostService($this->postRepo, $this->eventBus);
    }

    public function testCreatePostSavesPostAndDispatchesEvent(): void
    {
        $userId = 'user-123';
        $content = 'Hello world';
        $mediaUrl = 'https://example.com/image.jpg';
        $mediaType = 'image/jpeg';
        $metadata = ['width' => 800, 'height' => 600];
        $isPublic = true;

        $capturedPost = null;

        $this->postRepo->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Post $post) use (&$capturedPost, $userId, $content, $mediaUrl, $mediaType, $metadata, $isPublic) {
                $capturedPost = $post;
                $this->assertEquals($userId, $post->getUserId());
                $this->assertEquals($content, $post->getContent());
                $this->assertEquals($mediaUrl, $post->getMediaUrl());
                $this->assertEquals($mediaType, $post->getMediaType());
                $this->assertEquals($metadata, $post->getMetadata());
                $this->assertEquals($isPublic, $post->isPublic());
                return true;
            }));

        $this->eventBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (PostCreatedEvent $event) use (&$capturedPost, $isPublic) {
                return $event->getPostId() === $capturedPost->getId()
                    && $event->getUserId() === $capturedPost->getUserId()
                    && $event->isPublic() === $isPublic;
            }));

        $result = $this->service->createPost($userId, $content, $mediaUrl, $mediaType, $metadata, $isPublic);

        $this->assertInstanceOf(Post::class, $result);
    }

    public function testLikePostCreatesLikeWhenNoneExists(): void
    {
        $postId = 'post-123';
        $userId = 'user-123';
        $post = new Post($userId, 'Content');

        $this->postRepo->expects($this->once())
            ->method('find')
            ->with($postId)
            ->willReturn($post);

        $this->postRepo->expects($this->once())
            ->method('findLike')
            ->with($postId, $userId)
            ->willReturn(null);

        $this->postRepo->expects($this->once())
            ->method('saveLike')
            ->with($this->callback(function (PostLike $like) use ($postId, $userId) {
                return $like->getPostId() === $postId && $like->getUserId() === $userId;
            }));

        $this->postRepo->expects($this->once())
            ->method('incrementLikeCount')
            ->with($postId);

        $this->service->likePost($postId, $userId);
    }

    public function testLikePostRemovesLikeWhenAlreadyExists(): void
    {
        $postId = 'post-123';
        $userId = 'user-123';
        $post = new Post($userId, 'Content');
        $existingLike = new PostLike($postId, $userId);

        $this->postRepo->expects($this->once())
            ->method('find')
            ->with($postId)
            ->willReturn($post);

        $this->postRepo->expects($this->once())
            ->method('findLike')
            ->with($postId, $userId)
            ->willReturn($existingLike);

        $this->postRepo->expects($this->once())
            ->method('removeLike')
            ->with($existingLike->getId());

        $this->postRepo->expects($this->once())
            ->method('decrementLikeCount')
            ->with($postId);

        $this->service->likePost($postId, $userId);
    }

    public function testLikePostThrowsIfPostNotFound(): void
    {
        $postId = 'post-123';
        $userId = 'user-123';

        $this->postRepo->expects($this->once())
            ->method('find')
            ->with($postId)
            ->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Post not found');

        $this->service->likePost($postId, $userId);
    }

    public function testCommentPostAddsComment(): void
    {
        $postId = 'post-123';
        $userId = 'user-456';
        $content = 'Nice post!';
        $post = new Post($userId, 'Parent post');

        $this->postRepo->expects($this->once())
            ->method('find')
            ->with($postId)
            ->willReturn($post);

        $this->postRepo->expects($this->once())
            ->method('saveComment')
            ->with($this->callback(function (PostComment $comment) use ($postId, $userId, $content) {
                return $comment->getPostId() === $postId
                    && $comment->getUserId() === $userId
                    && $comment->getContent() === $content;
            }));

        $this->postRepo->expects($this->once())
            ->method('incrementCommentCount')
            ->with($postId);

        $this->service->commentPost($postId, $userId, $content);
    }

    public function testCommentPostThrowsIfPostNotFound(): void
    {
        $postId = 'post-123';
        $userId = 'user-456';
        $content = 'Comment';

        $this->postRepo->expects($this->once())
            ->method('find')
            ->with($postId)
            ->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Post not found');

        $this->service->commentPost($postId, $userId, $content);
    }

    public function testSharePostIncrementsShareCount(): void
    {
        $postId = 'post-123';
        $userId = 'user-456';
        $post = new Post($userId, 'Post to share');

        $this->postRepo->expects($this->once())
            ->method('find')
            ->with($postId)
            ->willReturn($post);

        $this->postRepo->expects($this->once())
            ->method('incrementShareCount')
            ->with($postId);

        $this->service->sharePost($postId, $userId);
    }

    public function testDeletePostCallsRepositoryWhenAuthorized(): void
    {
        $postId = 'post-123';
        $userId = 'user-123';
        $post = new Post($userId, 'My post');

        $this->postRepo->expects($this->once())
            ->method('find')
            ->with($postId)
            ->willReturn($post);

        $this->postRepo->expects($this->once())
            ->method('delete')
            ->with($post);

        $this->service->deletePost($postId, $userId);
    }

    public function testDeletePostThrowsIfPostNotFound(): void
    {
        $postId = 'post-123';
        $userId = 'user-123';

        $this->postRepo->expects($this->once())
            ->method('find')
            ->with($postId)
            ->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Post not found or unauthorized');

        $this->service->deletePost($postId, $userId);
    }

    public function testDeletePostThrowsIfUnauthorized(): void
    {
        $postId = 'post-123';
        $userId = 'user-123';
        $otherUser = 'user-456';
        $post = new Post($otherUser, 'Not yours');

        $this->postRepo->expects($this->once())
            ->method('find')
            ->with($postId)
            ->willReturn($post);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Post not found or unauthorized');

        $this->service->deletePost($postId, $userId);
    }

    public function testGetFeedForUserDelegatesToRepository(): void
    {
        $userId = 'user-123';
        $offset = 0;
        $limit = 20;
        $posts = [new Post($userId, 'Post 1'), new Post($userId, 'Post 2')];

        $this->postRepo->expects($this->once())
            ->method('findFeedForUser')
            ->with($userId, $offset, $limit)
            ->willReturn($posts);

        $result = $this->service->getFeedForUser($userId, $offset, $limit);

        $this->assertSame($posts, $result);
    }

    public function testGetPublicFeedDelegatesToRepository(): void
    {
        $offset = 0;
        $limit = 50;
        $posts = [new Post('user-1', 'Public 1'), new Post('user-2', 'Public 2')];

        $this->postRepo->expects($this->once())
            ->method('findPublicPosts')
            ->with($offset, $limit)
            ->willReturn($posts);

        $result = $this->service->getPublicFeed($offset, $limit);

        $this->assertSame($posts, $result);
    }

    public function testGetPostDetailReturnsPostWhenFound(): void
    {
        $postId = 'post-123';
        $post = new Post('user-123', 'Content');

        $this->postRepo->expects($this->once())
            ->method('find')
            ->with($postId)
            ->willReturn($post);

        $result = $this->service->getPostDetail($postId);

        $this->assertSame($post, $result);
    }

    public function testGetPostDetailReturnsNullIfDeleted(): void
    {
        $postId = 'post-123';
        $post = new Post('user-123', 'Content');
        // mark as deleted via reflection
        $ref = new \ReflectionProperty(Post::class, 'deletedAt');
        $ref->setAccessible(true);
        $ref->setValue($post, new DateTime());

        $this->postRepo->expects($this->once())
            ->method('find')
            ->with($postId)
            ->willReturn($post);

        $result = $this->service->getPostDetail($postId);

        $this->assertNull($result);
    }

    public function testGetPostDetailReturnsNullIfNotFound(): void
    {
        $postId = 'post-123';

        $this->postRepo->expects($this->once())
            ->method('find')
            ->with($postId)
            ->willReturn(null);

        $result = $this->service->getPostDetail($postId);

        $this->assertNull($result);
    }
}
