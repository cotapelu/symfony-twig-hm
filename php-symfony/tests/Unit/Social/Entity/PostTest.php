<?php declare(strict_types=1);

namespace App\Tests\Unit\Social\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Social\Entity\Post;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class PostTest extends TestCase
{
    private function createPost(array $overrides = []): Post
    {
        $post = new Post(
            userId: 'user-1',
            content: 'Hello world',
            mediaUrl: null,
            mediaType: null,
            metadata: null,
            isPublic: true
        );
        foreach ($overrides as $prop => $value) {
            // Use setters directly; many properties have setters.
            $setter = 'set' . ucfirst($prop);
            if (method_exists($post, $setter)) {
                $post->$setter($value);
            } else {
                // For read-only or internal properties, skip.
            }
        }
        return $post;
    }

    public function testConstructorGeneratesIdAndTimestamps(): void
    {
        $post = $this->createPost();
        $this->assertIsString($post->getId());
        $this->assertNotEmpty($post->getId());
        $this->assertInstanceOf(\DateTime::class, $post->getCreatedAt());
    }

    public function testConstructorInitializesOptionalFields(): void
    {
        $post = new Post(
            userId: 'user-2',
            content: 'My content',
            mediaUrl: 'http://example.com/img.jpg',
            mediaType: 'image',
            metadata: ['size' => 1024],
            isPublic: false
        );
        $this->assertSame('user-2', $post->getUserId());
        $this->assertSame('My content', $post->getContent());
        $this->assertSame('http://example.com/img.jpg', $post->getMediaUrl());
        $this->assertSame('image', $post->getMediaType());
        $this->assertSame(['size' => 1024], $post->getMetadata());
        $this->assertFalse($post->isPublic());
    }

    public function testGettersAndSetters(): void
    {
        $post = $this->createPost();

        $post->setUserId('user-3');
        $this->assertSame('user-3', $post->getUserId());

        $post->setContent('New content');
        $this->assertSame('New content', $post->getContent());

        $post->setMediaUrl('http://example.com/video.mp4');
        $this->assertSame('http://example.com/video.mp4', $post->getMediaUrl());

        $post->setMediaType('video');
        $this->assertSame('video', $post->getMediaType());

        $post->setMetadata(['duration' => 120]);
        $this->assertSame(['duration' => 120], $post->getMetadata());

        $post->setPublic(true);
        $this->assertTrue($post->isPublic());

        $post->setLikeCount(5);
        $this->assertSame(5, $post->getLikeCount());

        $post->setCommentCount(2);
        $this->assertSame(2, $post->getCommentCount());

        $post->setShareCount(1);
        $this->assertSame(1, $post->getShareCount());

        $post->setDeletedAt(new \DateTime());
        $this->assertTrue($post->isDeleted());
        $this->assertInstanceOf(\DateTime::class, $post->getDeletedAt());

        $post->setDeletedAt(null);
        $this->assertFalse($post->isDeleted());
    }

    public function testGetMediaUrlReturnsEmptyStringWhenNull(): void
    {
        $post = new Post(null, null, null, null, null, true);
        $this->assertSame('', $post->getMediaUrl());
    }

    public function testToArrayOutput(): void
    {
        $post = new Post(
            userId: 'user-x',
            content: 'Test',
            isPublic: true
        );
        $post->setLikeCount(10);
        $post->setCommentCount(5);
        $post->setShareCount(2);
        $deleted = new \DateTime('2024-01-01 00:00:00');
        $post->setDeletedAt($deleted);

        $arr = $post->toArray();
        $this->assertSame('user-x', $arr['userId']);
        $this->assertSame('Test', $arr['content']);
        $this->assertNull($arr['mediaUrl']);
        $this->assertNull($arr['mediaType']);
        $this->assertNull($arr['metadata']);
        $this->assertTrue($arr['isPublic']);
        $this->assertSame(10, $arr['likeCount']);
        $this->assertSame(5, $arr['commentCount']);
        $this->assertSame(2, $arr['shareCount']);
        $this->assertSame('2024-01-01T00:00:00+00:00', $arr['deletedAt']);
        $this->assertArrayHasKey('id', $arr);
        $this->assertArrayHasKey('createdAt', $arr);
    }
}
