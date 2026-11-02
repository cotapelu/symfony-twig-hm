<?php declare(strict_types=1);

namespace App\Tests\Unit\Social\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Social\Entity\PostComment;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class PostCommentTest extends TestCase
{
    private function createComment(?string $postId = null, ?string $userId = null, ?string $content = null): PostComment
    {
        return new PostComment(
            postId: $postId ?? 'post-1',
            userId: $userId ?? 'user-1',
            content: $content ?? 'Nice!'
        );
    }

    public function testConstructorInitializesProperties(): void
    {
        $comment = $this->createComment();
        $this->assertIsString($comment->getId());
        $this->assertNotEmpty($comment->getId());
        $this->assertSame('post-1', $comment->getPostId());
        $this->assertSame('user-1', $comment->getUserId());
        $this->assertSame('Nice!', $comment->getContent());
        $this->assertInstanceOf(\DateTime::class, $comment->getCreatedAt());
        $this->assertNull($comment->getPost()); // not set
    }

    public function testGettersAndSetters(): void
    {
        $comment = $this->createComment();

        $comment->setPostId('post-2');
        $this->assertSame('post-2', $comment->getPostId());

        $comment->setUserId('user-2');
        $this->assertSame('user-2', $comment->getUserId());

        $comment->setContent('Edited comment');
        $this->assertSame('Edited comment', $comment->getContent());
    }
}
