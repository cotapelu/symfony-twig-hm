<?php declare(strict_types=1);

namespace App\Tests\Unit\Social\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Social\Entity\PostLike;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class PostLikeTest extends TestCase
{
    private function createLike(?string $postId = null, ?string $userId = null): PostLike
    {
        return new PostLike(
            postId: $postId ?? 'post-1',
            userId: $userId ?? 'user-1'
        );
    }

    public function testConstructorInitializesProperties(): void
    {
        $like = $this->createLike();
        $this->assertIsString($like->getId());
        $this->assertNotEmpty($like->getId());
        $this->assertSame('post-1', $like->getPostId());
        $this->assertSame('user-1', $like->getUserId());
        $this->assertInstanceOf(\DateTime::class, $like->getCreatedAt());
        $this->assertNull($like->getPost()); // not set
    }

    public function testGettersAndSetters(): void
    {
        $like = $this->createLike();

        $like->setPostId('post-2');
        $this->assertSame('post-2', $like->getPostId());

        $like->setUserId('user-2');
        $this->assertSame('user-2', $like->getUserId());
    }
}
