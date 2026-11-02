<?php declare(strict_types=1);

namespace App\Tests\Unit\Messaging\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Messaging\Entity\SocialPost;
use App\Core\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class SocialPostTest extends TestCase
{
    private function createMockUser(string $id = 'user-123'): MockObject&User
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($id);
        return $user;
    }

    public function testConstructor(): void
    {
        $user = $this->createMockUser();
        $post = new SocialPost($user, SocialPost::TYPE_POST);
        $this->assertIsString($post->getId());
        $this->assertSame($user, $post->getUser());
        $this->assertSame(SocialPost::TYPE_POST, $post->getType());
        $this->assertNull($post->getContent());
        $this->assertNull($post->getMedia());
        $this->assertSame(0, $post->getLikesCount());
        $this->assertSame(0, $post->getCommentsCount());
        $this->assertInstanceOf(\DateTime::class, $post->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $post->getUpdatedAt());
    }

    public function testGettersAndSetters(): void
    {
        $user = $this->createMockUser();
        $post = new SocialPost($user, SocialPost::TYPE_STORY);
        $post->setContent('My story');
        $post->setMedia(['image' => 'story.jpg']);
        $post->setLikesCount(5);
        $post->setCommentsCount(2);

        $this->assertSame('My story', $post->getContent());
        $this->assertSame(['image' => 'story.jpg'], $post->getMedia());
        $this->assertSame(5, $post->getLikesCount());
        $this->assertSame(2, $post->getCommentsCount());
    }

    public function testLifecycleMethods(): void
    {
        $user = $this->createMockUser();
        $post = new SocialPost($user, SocialPost::TYPE_REEL);
        $post->incrementLikesCount();
        $this->assertSame(1, $post->getLikesCount());
        $post->decrementLikesCount();
        $this->assertSame(0, $post->getLikesCount());

        $post->incrementCommentsCount();
        $this->assertSame(1, $post->getCommentsCount());
        $post->decrementCommentsCount();
        $this->assertSame(0, $post->getCommentsCount());
    }

    public function testToArray(): void
    {
        $user = $this->createMockUser('user-456');
        $post = new SocialPost($user, SocialPost::TYPE_POST);
        $post->setContent('Hello world');
        $post->setMedia([['type' => 'image', 'url' => 'img.jpg']]);
        $post->setLikesCount(10);

        $arr = $post->toArray();

        $this->assertIsArray($arr);
        $this->assertArrayHasKey('id', $arr);
        $this->assertSame('user-456', $arr['userId']);
        $this->assertSame(SocialPost::TYPE_POST, $arr['type']);
        $this->assertSame('Hello world', $arr['content']);
        $this->assertSame([['type' => 'image', 'url' => 'img.jpg']], $arr['media']);
        $this->assertSame(10, $arr['likesCount']);
        $this->assertArrayHasKey('createdAt', $arr);
        $this->assertArrayHasKey('updatedAt', $arr);
    }
}
